<?php

namespace App\Services\Tdr;

use App\Models\SubscriptionContractMatch;
use App\Services\AccountCompatibilityService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CompatibilityScoreService
{
    protected string $endpoint;
    protected int $timeout;
    protected bool $enabled;

    public function __construct(protected AccountCompatibilityService $repository)
    {
        $baseUrl = rtrim((string) config('services.analizador_tdr.url', ''), '/');
        $this->endpoint = $baseUrl ? $baseUrl . '/compatibility/score' : '';
        $this->timeout = (int) config('services.analizador_tdr.timeout', 60);
        $this->enabled = (bool) config('services.analizador_tdr.enabled', false);
    }

    /**
     * @param  \App\Contracts\ChannelSubscriptionContract&\Illuminate\Database\Eloquent\Model  $subscription
     */
    public function ensureScore(
        object $subscription,
        array $contratoSnapshot,
        array $analysisPayload,
        bool $forceRefresh = false
    ): array {
        $contratoId = $this->resolveContratoId($contratoSnapshot);

        if ($contratoId === null) {
            throw new RuntimeException('No fue posible determinar el ID del contrato para evaluar compatibilidad.');
        }

        $existing = $this->repository->findMatch($subscription, $contratoId);

        if (!$forceRefresh && $this->repository->canReuseMatch($existing, $subscription)) {
            return [
                'match' => $existing,
                'from_cache' => true,
                'payload' => $existing?->analisis_payload ?? [],
            ];
        }

        if (!$this->enabled || $this->endpoint === '') {
            return [
                'match' => $existing,
                'from_cache' => false,
                'error' => 'El servicio de compatibilidad IA está deshabilitado en la configuración.',
            ];
        }

        if (blank($subscription->company_copy)) {
            return [
                'match' => $existing,
                'from_cache' => false,
                'error' => 'Define el copy del rubro en la suscripción de Telegram para evaluar compatibilidad.',
            ];
        }

        $subscription->loadMissing('keywords');

        $analysis = $analysisPayload['analisis'] ?? $analysisPayload;
        if (!is_array($analysis)) {
            $analysis = [];
        }

        $requestPayload = [
            'company_copy' => $subscription->company_copy,
            'analisis_tdr' => $analysis,
            'contrato_contexto' => $this->buildContratoContext($contratoSnapshot),
            'keywords' => $subscription->keywords->pluck('nombre')->filter()->values()->all(),
        ];

        $response = Http::timeout($this->timeout)
            ->post($this->endpoint, $requestPayload);

        if (!$response->successful()) {
            Log::error('Compatibilidad IA: error HTTP', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'match' => $existing,
                'from_cache' => false,
                'error' => $response->json('detail')
                    ?? $response->json('error')
                    ?? 'No fue posible evaluar la compatibilidad en este momento.',
            ];
        }

        $data = $response->json('data');
        if (!is_array($data)) {
            Log::warning('Compatibilidad IA: respuesta inesperada', ['response' => $response->json()]);
            return [
                'match' => $existing,
                'from_cache' => false,
                'error' => 'Respuesta inválida del servicio de compatibilidad.',
            ];
        }

        $match = $this->repository->storeCompatibilityResult($subscription, $contratoSnapshot, $data);

        return [
            'match' => $match,
            'from_cache' => false,
            'payload' => $match->analisis_payload ?? $data,
        ];
    }

    protected function resolveContratoId(array $contratoSnapshot): ?int
    {
        $id = Arr::get($contratoSnapshot, 'idContrato')
            ?? Arr::get($contratoSnapshot, 'id_contrato_seace');

        if ($id === null) {
            return null;
        }

        $id = (int) $id;

        return $id > 0 ? $id : null;
    }

    protected function buildContratoContext(array $contratoSnapshot): array
    {
        return [
            'codigo' => Arr::get($contratoSnapshot, 'desContratacion')
                ?? Arr::get($contratoSnapshot, 'codigo_proceso'),
            'entidad' => Arr::get($contratoSnapshot, 'nomEntidad')
                ?? Arr::get($contratoSnapshot, 'entidad'),
            'objeto' => Arr::get($contratoSnapshot, 'nomObjetoContrato')
                ?? Arr::get($contratoSnapshot, 'objeto'),
            'descripcion' => Arr::get($contratoSnapshot, 'desObjetoContrato')
                ?? Arr::get($contratoSnapshot, 'descripcion'),
            'estado' => Arr::get($contratoSnapshot, 'nomEstadoContrato')
                ?? Arr::get($contratoSnapshot, 'estado'),
            'fecha_publicacion' => Arr::get($contratoSnapshot, 'fecPublica')
                ?? Arr::get($contratoSnapshot, 'fecha_publicacion'),
            'fecha_cierre' => Arr::get($contratoSnapshot, 'fecFinCotizacion')
                ?? Arr::get($contratoSnapshot, 'fin_cotizacion'),
        ];
    }
}
