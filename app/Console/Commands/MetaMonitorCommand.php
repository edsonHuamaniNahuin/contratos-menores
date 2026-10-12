<?php

namespace App\Console\Commands;

use App\Models\DemoLead;
use App\Models\MetaInsightDiario;
use App\Models\PagoYape;
use App\Services\AdminTelegramNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Monitor de la campaña de Meta Ads (embudos E1/E2/E3).
 *
 * Revisa entrega, costos, anuncios con problemas y leads del día (BD),
 * guarda un snapshot histórico y puede alertar por Telegram.
 */
class MetaMonitorCommand extends Command
{
    protected $signature = 'meta:monitor
        {--json : Imprime el resumen en JSON}
        {--notificar : Envía el resumen por Telegram siempre}
        {--solo-alertas : Envía Telegram solo si hay alertas}';

    protected $description = 'Monitorea la campaña Meta Ads: entrega, costos, issues y leads del día.';

    private const CAMPAIGN_ID = '120247999030430108';
    private const BUDGET_DIARIO = 21.0;
    private const API = 'https://graph.facebook.com/v26.0';

    public function handle(): int
    {
        $token = $this->resolverToken();
        if ($token === null) {
            $this->error('Falta META_ACCESS_TOKEN en .env (o archivo /root/.meta-token).');
            return self::FAILURE;
        }

        $campaign = self::CAMPAIGN_ID;

        $insights = $this->graph("{$campaign}/insights", [
            'fields' => 'spend,impressions,clicks,ctr,cpc,cpm,frequency,actions',
            'date_preset' => 'today',
        ], $token);

        $row = $insights['data'][0] ?? [];
        $spend = (float) ($row['spend'] ?? 0);
        $impressions = (int) ($row['impressions'] ?? 0);
        $clicks = (int) ($row['clicks'] ?? 0);
        $ctr = round((float) ($row['ctr'] ?? 0), 2);
        $cpc = round((float) ($row['cpc'] ?? 0), 2);
        $cpm = round((float) ($row['cpm'] ?? 0), 2);
        $frequency = round((float) ($row['frequency'] ?? 0), 2);
        $leadsMeta = $this->contarLeadsMeta($row['actions'] ?? []);

        $adsets = $this->graph("{$campaign}/adsets", [
            'fields' => 'name,effective_status,issues_info,optimization_goal',
            'limit' => '50',
        ], $token)['data'] ?? [];

        $ads = $this->graph("{$campaign}/ads", [
            'fields' => 'name,effective_status,issues_info',
            'limit' => '100',
        ], $token)['data'] ?? [];

        $hoy = now()->toDateString();
        $leadsDb = DemoLead::whereDate('created_at', $hoy)->count();
        $leadsPorLanding = DemoLead::whereDate('created_at', $hoy)
            ->selectRaw('landing, COUNT(*) as total')
            ->groupBy('landing')
            ->pluck('total', 'landing')
            ->toArray();

        $alertas = $this->evaluarAlertas($spend, $impressions, $ctr, $cpc, $frequency, $adsets, $ads);
        if ($insights['error'] ?? null) {
            $alertas[] = 'Error API Meta: ' . mb_substr((string) ($insights['error']['message'] ?? 'desconocido'), 0, 120);
        }

        $roi = $this->calcularRoi($spend);
        if ($roi['yape_pendientes_2h'] > 0) {
            $alertas[] = "Yape: {$roi['yape_pendientes_2h']} pago(s) PENDIENTE(S) hace +2h — aprobar en /admin/pagos-yape para no perder la venta.";
        }
        if ($roi['roas_mes'] !== null && $roi['roas_mes'] < 10 && $roi['gasto_mes'] >= 50) {
            $alertas[] = "ROAS del mes {$roi['roas_mes']}x por debajo del mínimo 10x (objetivo 40x).";
        }

        $resumen = [
            'fecha' => now()->format('Y-m-d H:i'),
            'gasto_soles' => round($spend, 2),
            'presupuesto_dia' => self::BUDGET_DIARIO,
            'impresiones' => $impressions,
            'clics' => $clicks,
            'ctr' => $ctr,
            'cpc' => $cpc,
            'cpm' => $cpm,
            'frecuencia' => $frequency,
            'leads_meta' => $leadsMeta,
            'leads_db' => $leadsDb,
            'leads_por_landing' => $leadsPorLanding,
            'cpl_db' => $leadsDb > 0 ? round($spend / $leadsDb, 2) : null,
            'roi' => $roi,
            'alertas' => $alertas,
        ];

        $this->guardarSnapshot($resumen);

        if ($this->option('json')) {
            $this->line(json_encode($resumen, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } else {
            $this->imprimirTabla($resumen);
        }

        $debeNotificar = $this->option('notificar')
            || ($this->option('solo-alertas') && count($alertas) > 0);

        if ($debeNotificar) {
            $this->notificar($resumen);
        }

        return self::SUCCESS;
    }

    private function resolverToken(): ?string
    {
        $token = trim((string) config('services.meta.access_token'));
        if ($token !== '') {
            return $token;
        }

        if (is_readable('/root/.meta-token')) {
            return trim((string) file_get_contents('/root/.meta-token')) ?: null;
        }

        return null;
    }

    private function graph(string $path, array $query, string $token): array
    {
        $response = Http::timeout(60)
            ->retry(2, 1500)
            ->get(self::API . '/' . $path, $query + ['access_token' => $token]);

        if (! $response->successful()) {
            return ['error' => $response->json()['error'] ?? ['message' => $response->body()]];
        }

        return $response->json() ?? [];
    }

    private function contarLeadsMeta(array $actions): int
    {
        $total = 0;
        foreach ($actions as $action) {
            if (in_array($action['action_type'] ?? '', ['lead', 'offsite_conversion.fb_pixel_lead'], true)) {
                $total += (int) round((float) ($action['value'] ?? 0));
            }
        }

        return $total;
    }

    private function calcularRoi(float $spendHoy): array
    {
        $hoy = now()->toDateString();
        $inicioMes = now()->startOfMonth()->toDateString();

        $yapeHoy = PagoYape::where('estado', PagoYape::ESTADO_APROBADO)
            ->whereDate('processed_at', $hoy)
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(monto), 0) as monto')
            ->first();

        $yapeMes = PagoYape::where('estado', PagoYape::ESTADO_APROBADO)
            ->whereDate('processed_at', '>=', $inicioMes)
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(monto), 0) as monto')
            ->first();

        $gastoMes = (float) MetaInsightDiario::whereBetween('fecha', [$inicioMes, now()->subDay()->toDateString()])
            ->sum('spend') + $spendHoy;

        $roasMes = $gastoMes > 0.01 ? round((float) $yapeMes->monto / $gastoMes, 2) : null;

        $emailsCampana = DemoLead::where('utm_source', 'meta')->pluck('email');

        $yapeCampanaMes = $emailsCampana->isEmpty() ? 0 : PagoYape::where('estado', PagoYape::ESTADO_APROBADO)
            ->whereDate('processed_at', '>=', $inicioMes)
            ->whereHas('user', fn ($q) => $q->whereIn('email', $emailsCampana))
            ->count();

        $pendientesViejos = PagoYape::where('estado', PagoYape::ESTADO_PENDIENTE)
            ->where('created_at', '<=', now()->subHours(2))
            ->count();

        return [
            'yape_hoy' => ['ventas' => (int) $yapeHoy->total, 'monto' => round((float) $yapeHoy->monto, 2)],
            'yape_mes' => ['ventas' => (int) $yapeMes->total, 'monto' => round((float) $yapeMes->monto, 2)],
            'yape_campana_mes' => $yapeCampanaMes,
            'yape_pendientes_2h' => $pendientesViejos,
            'leads_mes' => DemoLead::where('created_at', '>=', $inicioMes)->count(),
            'wa_clicks_hoy' => (int) DB::table('wa_clicks')->whereDate('created_at', $hoy)->count(),
            'gasto_mes' => round($gastoMes, 2),
            'roas_mes' => $roasMes,
            'meta_roas_min' => 10,
            'meta_roas_optimo' => 40,
        ];
    }

    private function evaluarAlertas(
        float $spend,
        int $impressions,
        float $ctr,
        float $cpc,
        float $frequency,
        array $adsets,
        array $ads
    ): array {
        $alertas = [];

        foreach ($adsets as $adset) {
            $issues = $adset['issues_info'] ?? [];
            if (! empty($issues)) {
                $alertas[] = "Ad set con problemas: {$adset['name']} ({$issues[0]['error_summary']})";
            }
        }

        foreach ($ads as $ad) {
            if (in_array($ad['effective_status'] ?? '', ['DISAPPROVED', 'WITH_ISSUES'], true)) {
                $alertas[] = "Anuncio {$ad['name']} en estado {$ad['effective_status']}";
            }
            $issues = $ad['issues_info'] ?? [];
            if (! empty($issues)) {
                $alertas[] = "Anuncio con issues: {$ad['name']} ({$issues[0]['error_summary']})";
            }
        }

        $hora = (int) now()->format('H');
        if ($hora >= 11 && $spend <= 0) {
            $alertas[] = "Sin entrega: son las {$hora}:00 y no hay gasto registrado (revisar cuenta/pagos).";
        }

        if ($impressions > 1500 && $ctr < 0.5) {
            $alertas[] = "CTR bajo: {$ctr}% con {$impressions} impresiones.";
        }

        if ($frequency > 3.0 && $impressions >= 2000) {
            $alertas[] = "Frecuencia alta: {$frequency} con {$impressions} impresiones (público saturándose — evaluar ampliar a broad).";
        }

        if ($cpc > 3.0 && $impressions > 300) {
            $alertas[] = "CPC alto: S/ {$cpc}.";
        }

        return $alertas;
    }

    private function guardarSnapshot(array $resumen): void
    {
        $archivo = 'meta-monitor/' . now()->format('Y-m') . '.json';
        $historial = [];

        if (Storage::disk('local')->exists($archivo)) {
            $historial = json_decode((string) Storage::disk('local')->get($archivo), true) ?: [];
        }

        $historial[] = $resumen;
        Storage::disk('local')->put($archivo, json_encode($historial, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    private function imprimirTabla(array $resumen): void
    {
        $this->info("Monitor Meta Ads · {$resumen['fecha']}");
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Gasto hoy', 'S/ ' . number_format($resumen['gasto_soles'], 2) . ' de S/ ' . number_format($resumen['presupuesto_dia'], 2)],
                ['Impresiones', $resumen['impresiones']],
                ['Clics', $resumen['clics']],
                ['CTR', $resumen['ctr'] . '%'],
                ['CPC', 'S/ ' . number_format($resumen['cpc'], 2)],
                ['CPM', 'S/ ' . number_format($resumen['cpm'], 2)],
                ['Frecuencia', $resumen['frecuencia']],
                ['Leads BD (Meta)', $resumen['leads_db'] . ' (' . $resumen['leads_meta'] . ')'],
                ['CPL', $resumen['cpl_db'] !== null ? 'S/ ' . number_format($resumen['cpl_db'], 2) : '—'],
                ['Yape hoy', $resumen['roi']['yape_hoy']['ventas'] . ' venta(s) · S/ ' . number_format($resumen['roi']['yape_hoy']['monto'], 2)],
                ['Yape del mes', $resumen['roi']['yape_mes']['ventas'] . ' venta(s) · S/ ' . number_format($resumen['roi']['yape_mes']['monto'], 2)
                    . ' (' . $resumen['roi']['yape_campana_mes'] . ' atribuibles a campaña)'],
                ['Gasto del mes', 'S/ ' . number_format($resumen['roi']['gasto_mes'], 2)],
                ['ROAS del mes', ($resumen['roi']['roas_mes'] !== null ? $resumen['roi']['roas_mes'] . 'x' : '—')
                    . ' (meta: 10x mín / 40x óptimo)'],
                ['Leads del mes', $resumen['roi']['leads_mes']],
                ['Clics WhatsApp hoy', $resumen['roi']['wa_clicks_hoy']],
            ]
        );

        foreach ($resumen['leads_por_landing'] as $landing => $total) {
            $this->line("  · {$landing}: {$total} lead(s)");
        }

        if (! empty($resumen['alertas'])) {
            foreach ($resumen['alertas'] as $alerta) {
                $this->error('⚠️ ' . $alerta);
            }
        } else {
            $this->info('Sin alertas. ✅');
        }
    }

    private function notificar(array $resumen): void
    {
        $lineas = [
            '📊 *MONITOR META ADS* · ' . $resumen['fecha'],
            "💸 Gasto: S/ " . number_format($resumen['gasto_soles'], 2) . ' de S/ ' . number_format($resumen['presupuesto_dia'], 2),
            "👁 {$resumen['impresiones']} impr · 🖱 {$resumen['clics']} clics",
            "📈 CTR {$resumen['ctr']}% · CPC S/ {$resumen['cpc']} · CPM S/ {$resumen['cpm']}",
            "🔁 Frecuencia: {$resumen['frecuencia']}",
            "🎯 Leads hoy: {$resumen['leads_db']} (Meta {$resumen['leads_meta']})" .
                ($resumen['cpl_db'] !== null ? ' · CPL S/ ' . number_format($resumen['cpl_db'], 2) : ''),
            "💰 Yape hoy: {$resumen['roi']['yape_hoy']['ventas']} · S/ " . number_format($resumen['roi']['yape_hoy']['monto'], 2),
            "📅 Yape mes: {$resumen['roi']['yape_mes']['ventas']} · S/ " . number_format($resumen['roi']['yape_mes']['monto'], 2)
                . " · Gasto mes: S/ " . number_format($resumen['roi']['gasto_mes'], 2),
            "📈 ROAS mes: " . ($resumen['roi']['roas_mes'] !== null ? $resumen['roi']['roas_mes'] . 'x' : '—')
                . " (meta: 10x mín / 40x óptimo)",
            "🟢 Clics WhatsApp hoy: {$resumen['roi']['wa_clicks_hoy']}",
        ];

        foreach ($resumen['leads_por_landing'] as $landing => $total) {
            $lineas[] = "   · {$landing}: {$total}";
        }

        if (! empty($resumen['alertas'])) {
            $lineas[] = '';
            foreach ($resumen['alertas'] as $alerta) {
                $lineas[] = '⚠️ ' . $alerta;
            }
        } else {
            $lineas[] = '✅ Sin alertas';
        }

        $servicio = app(AdminTelegramNotificationService::class);
        $ok = $servicio->enviarMensaje(implode("\n", $lineas));

        $ok ? $this->info('Telegram enviado.') : $this->warn('No se pudo enviar Telegram.');
    }
}
