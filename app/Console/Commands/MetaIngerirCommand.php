<?php

namespace App\Console\Commands;

use App\Models\MetaInsightDiario;
use App\Models\MetaInsightIntradia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Ingesta de métricas de la campaña Meta Ads hacia la BD del servidor.
 *
 * - Por defecto: guarda el acumulado del día (intradía) por anuncio.
 * - --cerrar: guarda además los cierres diarios (ayer, o rango con --dias).
 */
class MetaIngerirCommand extends Command
{
    protected $signature = 'meta:ingerir
        {--cerrar : Guarda los cierres diarios (ayer o el rango indicado con --dias)}
        {--dias=1 : Cuántos días hacia atrás cerrar (con --cerrar)}';

    protected $description = 'Guarda en BD las métricas de la campaña Meta (intradía de hoy + cierres diarios).';

    /** Campañas a ingerir (traffic E1E2E3 + lead ads J0) — cada centavo se guarda. */
    private const CAMPAIGNS = [
        '120247999030430108',
        '120248044120960108',
    ];
    private const API = 'https://graph.facebook.com/v26.0';
    private const FIELDS = 'ad_id,ad_name,adset_id,adset_name,campaign_id,campaign_name,spend,impressions,reach,clicks,ctr,cpc,cpm,frequency,actions';

    public function handle(): int
    {
        $token = $this->resolverToken();
        if ($token === null) {
            $this->error('Falta META_ACCESS_TOKEN en .env (o /root/.meta-token).');
            return self::FAILURE;
        }

        $guardadas = $this->ingerirIntradia($token);

        if ($this->option('cerrar')) {
            $guardadas += $this->ingerirCierres($token, max(1, (int) $this->option('dias')));
        }

        $this->info("Filas guardadas/actualizadas: {$guardadas}");

        return self::SUCCESS;
    }

    private function ingerirIntradia(string $token): int
    {
        $capturado = now()->startOfHour();
        $total = 0;

        foreach (self::CAMPAIGNS as $campaignId) {
            $rows = $this->graph($campaignId . '/insights', [
                'level' => 'ad',
                'date_preset' => 'today',
                'fields' => self::FIELDS,
                'limit' => '200',
            ], $token);

            if (! empty($rows['error'])) {
                $this->error("API (intradía, {$campaignId}): " . ($rows['error']['message'] ?? 'error'));
                continue;
            }

            foreach ($rows['data'] ?? [] as $row) {
                MetaInsightIntradia::updateOrCreate(
                    ['capturado_at' => $capturado, 'ad_id' => $row['ad_id']],
                    $this->metricas($row) + [
                        'fecha' => now()->toDateString(),
                        'ad_name' => $row['ad_name'] ?? null,
                        'adset_id' => $row['adset_id'] ?? null,
                        'adset_name' => $row['adset_name'] ?? null,
                    ]
                );
                $total++;
            }
        }

        return $total;
    }

    private function ingerirCierres(string $token, int $dias): int
    {
        $desde = now()->subDays($dias)->toDateString();
        $hasta = now()->subDay()->toDateString();
        $total = 0;

        foreach (self::CAMPAIGNS as $campaignId) {
            $rows = $this->graph($campaignId . '/insights', [
                'level' => 'ad',
                'time_range' => json_encode(['since' => $desde, 'until' => $hasta]),
                'time_increment' => '1',
                'fields' => self::FIELDS,
                'limit' => '500',
            ], $token);

            if (! empty($rows['error'])) {
                $this->error("API (cierres, {$campaignId}): " . ($rows['error']['message'] ?? 'error'));
                continue;
            }

            foreach ($rows['data'] ?? [] as $row) {
                MetaInsightDiario::updateOrCreate(
                    ['fecha' => $row['date_start'], 'ad_id' => $row['ad_id']],
                    $this->metricas($row) + [
                        'ad_name' => $row['ad_name'] ?? null,
                        'adset_id' => $row['adset_id'] ?? null,
                        'adset_name' => $row['adset_name'] ?? null,
                        'campaign_id' => $row['campaign_id'] ?? null,
                        'campaign_name' => $row['campaign_name'] ?? null,
                    ]
                );
                $total++;
            }
        }

        return $total;
    }

    private function metricas(array $row): array
    {
        [$leads, $linkClicks] = $this->acciones($row['actions'] ?? []);

        return [
            'spend' => (float) ($row['spend'] ?? 0),
            'impressions' => (int) ($row['impressions'] ?? 0),
            'reach' => (int) ($row['reach'] ?? 0),
            'clicks' => (int) ($row['clicks'] ?? 0),
            'link_clicks' => $linkClicks,
            'leads' => $leads,
            'ctr' => (float) ($row['ctr'] ?? 0),
            'cpc' => (float) ($row['cpc'] ?? 0),
            'cpm' => (float) ($row['cpm'] ?? 0),
            'frequency' => (float) ($row['frequency'] ?? 0),
        ];
    }

    private function acciones(array $actions): array
    {
        $leads = 0;
        $linkClicks = 0;

        foreach ($actions as $action) {
            $tipo = $action['action_type'] ?? '';

            if (in_array($tipo, ['lead', 'offsite_conversion.fb_pixel_lead'], true)) {
                $leads += (int) round((float) ($action['value'] ?? 0));
            }

            if ($tipo === 'link_click') {
                $linkClicks += (int) round((float) ($action['value'] ?? 0));
            }
        }

        return [$leads, $linkClicks];
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
}
