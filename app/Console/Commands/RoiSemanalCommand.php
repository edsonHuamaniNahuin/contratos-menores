<?php

namespace App\Console\Commands;

use App\Models\DemoLead;
use App\Models\MetaInsightDiario;
use App\Models\PagoYape;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AdminTelegramNotificationService;
use Illuminate\Console\Command;

/**
 * Reporte semanal de ROI por cohortes: gasto Meta → leads → registros →
 * trials → ventas Yape, con atribución (utm/fbclid) y ROAS.
 *
 * Corre los lunes 09:00 (Telegram) o a mano: php artisan roi:semanal
 */
class RoiSemanalCommand extends Command
{
    protected $signature = 'roi:semanal
        {--dias=7 : Días hacia atrás del periodo}
        {--sin-telegram : Solo imprime, no envía Telegram}';

    protected $description = 'Reporte semanal de ROI (campaña Meta → Yape) por cohortes y atribución.';

    public function handle(): int
    {
        $dias = max(1, (int) $this->option('dias'));
        $desde = now()->subDays($dias)->startOfDay();
        $hasta = now()->subDay()->endOfDay();

        $datos = $this->calcular($desde, $hasta);
        $acumulado = $this->acumuladoCampana();

        $this->imprimir($datos, $acumulado, $desde, $hasta);

        if (! $this->option('sin-telegram')) {
            $servicio = app(AdminTelegramNotificationService::class);
            $ok = $servicio->enviarMensaje($this->mensajeTelegram($datos, $acumulado, $desde, $hasta));
            $this->info($ok ? 'Telegram enviado.' : 'No se pudo enviar Telegram.');
        }

        return self::SUCCESS;
    }

    private function calcular($desde, $hasta): array
    {
        $gasto = (float) MetaInsightDiario::whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->sum('spend');

        $leads = DemoLead::whereBetween('created_at', [$desde, $hasta]);
        $leadsTotal = (clone $leads)->count();
        $leadsMeta = (clone $leads)->where('utm_source', 'meta')->count();

        $users = User::whereBetween('created_at', [$desde, $hasta]);
        $registrosTotal = (clone $users)->count();
        $registrosMeta = (clone $users)->where('utm_source', 'meta')->count();

        $trials = Subscription::where('plan', Subscription::PLAN_TRIAL)
            ->whereBetween('created_at', [$desde, $hasta])
            ->count();
        $trialsMeta = Subscription::where('plan', Subscription::PLAN_TRIAL)
            ->whereBetween('created_at', [$desde, $hasta])
            ->whereHas('user', fn ($q) => $q->where('utm_source', 'meta'))
            ->count();

        $yape = PagoYape::where('estado', PagoYape::ESTADO_APROBADO)
            ->whereBetween('processed_at', [$desde, $hasta]);
        $yapeVentas = (clone $yape)->count();
        $yapeMonto = (float) (clone $yape)->sum('monto');

        $emailsMeta = DemoLead::where('utm_source', 'meta')->pluck('email');
        $yapeCampana = (clone $yape)->whereHas('user', function ($q) use ($emailsMeta) {
            $q->where('utm_source', 'meta');
            if ($emailsMeta->isNotEmpty()) {
                $q->orWhereIn('email', $emailsMeta);
            }
        })->count();

        return [
            'gasto' => round($gasto, 2),
            'leads_total' => $leadsTotal,
            'leads_meta' => $leadsMeta,
            'registros_total' => $registrosTotal,
            'registros_meta' => $registrosMeta,
            'trials' => $trials,
            'trials_meta' => $trialsMeta,
            'yape_ventas' => $yapeVentas,
            'yape_monto' => round($yapeMonto, 2),
            'yape_campana' => $yapeCampana,
            'cpl' => $leadsTotal > 0 ? round($gasto / $leadsTotal, 2) : null,
            'costo_registro' => $registrosTotal > 0 ? round($gasto / $registrosTotal, 2) : null,
            'costo_venta' => $yapeVentas > 0 ? round($gasto / $yapeVentas, 2) : null,
            'roas' => $gasto > 0.01 ? round($yapeMonto / $gasto, 2) : null,
            'conv_lead_registro' => $leadsMeta > 0 ? round($registrosMeta / $leadsMeta * 100, 1) : null,
            'conv_registro_trial' => $registrosMeta > 0 ? round($trialsMeta / $registrosMeta * 100, 1) : null,
            'conv_trial_pago' => $trialsMeta > 0 ? round($yapeCampana / $trialsMeta * 100, 1) : null,
        ];
    }

    private function acumuladoCampana(): array
    {
        $gasto = (float) MetaInsightDiario::where('fecha', '>=', '2026-10-12')->sum('spend');
        $yape = (float) PagoYape::where('estado', PagoYape::ESTADO_APROBADO)
            ->where('processed_at', '>=', '2026-10-12')
            ->sum('monto');

        return [
            'gasto' => round($gasto, 2),
            'yape_monto' => round($yape, 2),
            'roas' => $gasto > 0.01 ? round($yape / $gasto, 2) : null,
        ];
    }

    private function imprimir(array $d, array $a, $desde, $hasta): void
    {
        $this->info("ROI SEMANAL · {$desde->format('d/m')} – {$hasta->format('d/m/Y')}");
        $this->table(['Métrica', 'Valor'], [
            ['Gasto Meta (semana)', 'S/ ' . number_format($d['gasto'], 2)],
            ['Leads', "{$d['leads_total']} (meta: {$d['leads_meta']}) · CPL " . ($d['cpl'] !== null ? 'S/ ' . $d['cpl'] : '—')],
            ['Registros', "{$d['registros_total']} (meta: {$d['registros_meta']}) · Costo/reg " . ($d['costo_registro'] !== null ? 'S/ ' . $d['costo_registro'] : '—')],
            ['Trials iniciados', (string) $d['trials']],
            ['Ventas Yape', "{$d['yape_ventas']} · S/ " . number_format($d['yape_monto'], 2) . " (campaña: {$d['yape_campana']})"],
            ['Costo por venta', $d['costo_venta'] !== null ? 'S/ ' . $d['costo_venta'] : '—'],
            ['ROAS semana', ($d['roas'] !== null ? $d['roas'] . 'x' : '—') . ' (meta: 10x mín / 40x óptimo)'],
            ['Funnel', 'lead→reg ' . ($d['conv_lead_registro'] ?? '—') . '% · reg→trial ' . ($d['conv_registro_trial'] ?? '—') . '% · trial→pago ' . ($d['conv_trial_pago'] ?? '—') . '%'],
            ['ACUMULADO campaña', 'gasto S/ ' . $a['gasto'] . ' · Yape S/ ' . $a['yape_monto'] . ' · ROAS ' . ($a['roas'] !== null ? $a['roas'] . 'x' : '—')],
        ]);
    }

    private function mensajeTelegram(array $d, array $a, $desde, $hasta): string
    {
        $roas = $d['roas'] !== null ? $d['roas'] . 'x' : '—';

        $lineas = [
            '📊 *ROI SEMANAL* · ' . $desde->format('d/m') . '–' . $hasta->format('d/m/Y'),
            '💸 Gasto Meta: S/ ' . number_format($d['gasto'], 2),
            "🎯 Leads: {$d['leads_total']} (meta {$d['leads_meta']})" . ($d['cpl'] !== null ? ' · CPL S/ ' . $d['cpl'] : ''),
            "👤 Registros: {$d['registros_total']} (meta {$d['registros_meta']})",
            "🧪 Trials: {$d['trials']}",
            "💰 Yape: {$d['yape_ventas']} ventas · S/ " . number_format($d['yape_monto'], 2) . " (campaña {$d['yape_campana']})",
            "📈 ROAS semana: {$roas} (meta 10x mín / 40x ópt)",
            '🔻 Funnel: lead→reg ' . ($d['conv_lead_registro'] ?? '—') . '% · reg→trial ' . ($d['conv_registro_trial'] ?? '—') . '% · trial→pago ' . ($d['conv_trial_pago'] ?? '—') . '%',
            '🧮 Acumulado: gasto S/ ' . $a['gasto'] . ' · Yape S/ ' . $a['yape_monto'] . ' · ROAS ' . ($a['roas'] !== null ? $a['roas'] . 'x' : '—'),
        ];

        if ($d['roas'] !== null && $d['roas'] < 10) {
            $lineas[] = '⚠️ ROAS bajo el mínimo — priorizar cierre/onboarding antes de escalar.';
        }

        return implode("\n", $lineas);
    }
}
