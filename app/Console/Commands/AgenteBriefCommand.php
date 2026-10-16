<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Compila el DOSSIER DIARIO para la sesión autónoma del agente:
 * métricas, embudo, Clarity, día de campaña, checkpoints y pendientes.
 * Salida: storage/agent/DAILY_BRIEF.md (+ Telegram con --notificar).
 */
class AgenteBriefCommand extends Command
{
    protected $signature = 'agente:brief {--notificar : Envía resumen por Telegram}';

    protected $description = 'Compila el dossier diario del agente (métricas + patrones + checkpoints).';

    private const INICIO_CAMPANA = '2026-10-12';

    public function handle(): int
    {
        $hoy = now()->toDateString();
        $diaN = Carbon::parse(self::INICIO_CAMPANA)->diffInDays(now()) + 1;

        $brief = [];
        $brief[] = '# 📋 DAILY BRIEF — ' . now()->format('d/m/Y H:i') . ' (Día ' . $diaN . ' de campaña)';
        $brief[] = '';

        // ── 1) Almacén: últimos 7 días ──
        $diarios = DB::table('meta_insights_diarios')
            ->selectRaw('fecha, ROUND(SUM(spend),2) gasto, SUM(impressions) impr, SUM(clicks) clics, SUM(reach) alcance')
            ->where('fecha', '>=', now()->subDays(7)->toDateString())
            ->groupBy('fecha')->orderBy('fecha')->get();

        $brief[] = '## 1. Gasto por día (almacén, cierre)';
        $brief[] = '| Fecha | Gasto | Impr | Clics |';
        $brief[] = '|---|---|---|---|';
        foreach ($diarios as $d) {
            $brief[] = "| {$d->fecha} | S/ {$d->gasto} | {$d->impr} | {$d->clics} |";
        }
        $brief[] = '';

        // ── 2) Intradía de hoy (última captura) ──
        $ultima = DB::table('meta_insights_intradia')->where('fecha', $hoy)->max('capturado_at');
        $brief[] = '## 2. Hoy (intradía, última captura ' . ($ultima ?: 'sin datos') . ')';
        if ($ultima) {
            $filas = DB::table('meta_insights_intradia')
                ->selectRaw('ad_name, ROUND(spend,2) gasto, impressions, clicks')
                ->where('fecha', $hoy)->where('capturado_at', $ultima)
                ->orderByDesc('spend')->limit(8)->get();
            foreach ($filas as $f) {
                $brief[] = "- {$f->ad_name}: S/ {$f->gasto} · {$f->impressions} impr · {$f->clicks} clics";
            }
        }
        $brief[] = '';

        // ── 3) Embudo first-party 7 días ──
        $wa = DB::table('wa_clicks')->where('created_at', '>=', now()->subDays(7))
            ->where('landing', '!=', 'audit')
            ->selectRaw('DATE(created_at) d, COALESCE(NULLIF(utm_content,""),"(sin utm)") u, COUNT(*) n')
            ->groupBy('d', 'u')->orderByDesc('d')->get();
        $leads = DB::table('demo_leads')->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) d, COUNT(*) n, COALESCE(NULLIF(utm_content,""),"(organico)") u')
            ->groupBy('d', 'u')->orderByDesc('d')->get();
        $events = DB::table('page_events')->where('created_at', '>=', now()->subDays(7))
            ->selectRaw("DATE(created_at) d, SUM(tipo='click') clics_landing, SUM(tipo='scroll') scrolls")
            ->groupBy('d')->orderByDesc('d')->get();

        $brief[] = '## 3. Embudo first-party (7 días)';
        $brief[] = '**Clics WhatsApp (por anuncio/UTM):**';
        foreach ($wa as $w) {
            $brief[] = "- {$w->d} · {$w->u}: {$w->n}";
        }
        $brief[] = '**Leads de formulario:**';
        foreach ($leads as $l) {
            $brief[] = "- {$l->d} · {$l->u}: {$l->n}";
        }
        $brief[] = '**Eventos en landing (clic/scroll):**';
        foreach ($events as $e) {
            $brief[] = "- {$e->d}: {$e->clics_landing} clics · {$e->scrolls} scrolls";
        }
        $brief[] = '';

        // ── 4) Clarity (grabaciones UX) ──
        $brief[] = '## 4. Clarity (últimas 24h de procesamiento)';
        try {
            $token = trim(@file_get_contents('/root/.clarity-token') ?: '');
            if ($token) {
                $resp = Http::withToken($token)->timeout(45)
                    ->get('https://www.clarity.ms/export-data/api/v1/project-live-insights?numOfDays=1');
                foreach ($resp->json() ?? [] as $m) {
                    $name = $m['metricName'] ?? '';
                    if (in_array($name, ['Traffic', 'ScrollDepth', 'DeadClickCount', 'RageClickCount', 'QuickbackClick', 'ScriptErrorCount', 'EngagementTime'])) {
                        $info = $m['information'][0] ?? [];
                        $brief[] = '- ' . $name . ': ' . json_encode($info, JSON_UNESCAPED_UNICODE);
                    }
                }
            }
        } catch (\Throwable $e) {
            $brief[] = '- Clarity no disponible: ' . mb_substr($e->getMessage(), 0, 120);
        }
        $brief[] = '';

        // ── 5) Checkpoints ──
        $hitos = [3, 7, 8, 14, 21];
        $brief[] = '## 5. Checkpoints';
        if (in_array($diaN, $hitos)) {
            $brief[] = "⚠️ **HOY ES CHECKPOINT (día {$diaN})** — revisar PROTOCOLO_OPERATIVO.md §4.";
        } else {
            $prox = collect($hitos)->first(fn ($h) => $h > $diaN);
            $brief[] = "- Día actual: {$diaN}. Próximo checkpoint: día " . ($prox ?: '—') . '.';
        }
        $brief[] = '';

        // ── 6) Pendientes ──
        $brief[] = '## 6. Pendientes (PENDIENTES.md)';
        $pend = @file_get_contents(base_path('documents/05-tecnico-operaciones/PENDIENTES.md')) ?: '';
        preg_match_all('/^## (.+)$/m', $pend, $mm);
        foreach (($mm[1] ?? []) as $titulo) {
            $brief[] = '- ' . trim($titulo);
        }
        $brief[] = '';
        $brief[] = '> Runbook del agente: `.agents/DIARIO_AGENTE.md` · Protocolo: `documents/02-marketing-adquisicion/PROTOCOLO_OPERATIVO.md`';

        $md = implode(PHP_EOL, $brief);
        $path = storage_path('agent/DAILY_BRIEF.md');
        @mkdir(dirname($path), 0755, true);
        file_put_contents($path, $md);
        $this->info('Brief escrito: ' . $path);

        if ($this->option('notificar')) {
            try {
                $this->telegram("📋 DAILY BRIEF listo (Día {$diaN}).\nGasto hoy (últ. captura): " . ($ultima ?: 's/d') . "\nWA clicks 24h: " . ($wa->first()?->n ?? 0) . "\nLeads 7d: " . $leads->sum('n') . "\n→ storage/agent/DAILY_BRIEF.md");
            } catch (\Throwable $e) {
                Log::warning('agente:brief telegram: ' . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }

    private function telegram(string $texto): void
    {
        $tok = env('TELEGRAM_ADMIN_BOT_TOKEN');
        $chat = env('TELEGRAM_ADMIN_CHAT_ID');
        if ($tok && $chat) {
            Http::timeout(20)->post("https://api.telegram.org/bot{$tok}/sendMessage", [
                'chat_id' => $chat,
                'text' => $texto,
            ]);
        }
    }
}
