<?php

namespace App\Services;

use App\Contracts\ChannelSubscriptionContract;
use App\Models\SubscriptionContractMatch;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class AccountCompatibilityService
{
    /**
     * Mapeo canal → columna FK en subscription_contract_matches.
     * OCP: agregar un canal nuevo solo requiere un nuevo entry aquí.
     */
    protected const FK_MAP = [
        'telegram' => 'telegram_subscription_id',
        'whatsapp' => 'whatsapp_subscription_id',
    ];

    /**
     * Buscar match existente para cualquier canal de suscripción.
     *
     * @param  ChannelSubscriptionContract&\Illuminate\Database\Eloquent\Model  $subscription
     */
    public function findMatch(object $subscription, ?int $contratoId = null, ?string $ocid = null): ?SubscriptionContractMatch
    {
        if (method_exists($subscription, 'matches')) {
            $query = $subscription->matches();

            if ($ocid) {
                $query->where('ocid', $ocid);
            } elseif ($contratoId !== null) {
                $query->where('contrato_seace_id', $contratoId);
            }

            return $query->first();
        }

        $fk = $this->resolveForeignKey($subscription);
        $query = SubscriptionContractMatch::where($fk, $subscription->id);

        if ($ocid) {
            $query->where('ocid', $ocid);
        } elseif ($contratoId !== null) {
            $query->where('contrato_seace_id', $contratoId);
        }

        return $query->first();
    }

    /**
     * Determinar si un match existente puede reutilizarse (sin re-calcular).
     *
     * @param  ChannelSubscriptionContract&\Illuminate\Database\Eloquent\Model  $subscription
     */
    public function canReuseMatch(?SubscriptionContractMatch $match, object $subscription): bool
    {
        if (!$match) {
            return false;
        }

        $companyCopy = method_exists($subscription, 'getCompanyCopy')
            ? $subscription->getCompanyCopy()
            : ($subscription->company_copy ?? null);

        if (($match->copy_snapshot ?? null) !== $companyCopy) {
            return false;
        }

        return $match->score !== null;
    }

    /**
     * Persistir resultado de compatibilidad para cualquier canal.
     *
     * @param  ChannelSubscriptionContract&\Illuminate\Database\Eloquent\Model  $subscription
     */
    public function storeCompatibilityResult(
        object $subscription,
        array $contratoSnapshot,
        array $compatibilityPayload
    ): SubscriptionContractMatch {
        $subscription->loadMissing('keywords');

        $contratoId = Arr::get($contratoSnapshot, 'idContrato')
            ?? Arr::get($contratoSnapshot, 'id_contrato_seace');

        $ocid = Arr::get($contratoSnapshot, 'ocid');

        $timestamp = $this->resolveTimestamp(Arr::get($compatibilityPayload, 'timestamp'));
        $fk = $this->resolveForeignKey($subscription);

        $attributes = [
            'contrato_codigo' => Arr::get($contratoSnapshot, 'desContratacion')
                ?? Arr::get($contratoSnapshot, 'codigo_proceso'),
            'contrato_entidad' => Arr::get($contratoSnapshot, 'nomEntidad')
                ?? Arr::get($contratoSnapshot, 'entidad'),
            'contrato_objeto' => Arr::get($contratoSnapshot, 'nomObjetoContrato')
                ?? Arr::get($contratoSnapshot, 'objeto'),
            'score' => $this->resolveScore($compatibilityPayload),
            'keywords_snapshot' => $subscription->keywords->pluck('nombre')->filter()->values()->all(),
            'copy_snapshot' => method_exists($subscription, 'getCompanyCopy')
                ? $subscription->getCompanyCopy()
                : ($subscription->company_copy ?? null),
            'analisis_payload' => $compatibilityPayload,
            'source' => Arr::get($compatibilityPayload, 'source', 'compatibility-service'),
            'analizado_en' => $timestamp,
        ];

        $where = [$fk => $subscription->id];

        if ($ocid) {
            $where['ocid'] = $ocid;
        } elseif ($contratoId !== null) {
            $where['contrato_seace_id'] = $contratoId;
        }

        return SubscriptionContractMatch::updateOrCreate($where, $attributes);
    }

    /**
     * Resolver la columna FK según el canal de la suscripción.
     */
    protected function resolveForeignKey(object $subscription): string
    {
        $channel = method_exists($subscription, 'channelName')
            ? $subscription->channelName()
            : 'telegram';

        return self::FK_MAP[$channel]
            ?? throw new \RuntimeException("Canal de suscripción no soportado: {$channel}");
    }

    protected function resolveTimestamp($value): Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if (is_string($value)) {
            try {
                return Carbon::parse($value);
            } catch (\Exception $e) {
                Log::warning('Compatibilidad: timestamp inválido', ['value' => $value, 'error' => $e->getMessage()]);
            }
        }

        return Carbon::now();
    }

    protected function resolveScore(array $payload): ?float
    {
        $score = Arr::get($payload, 'score');

        if (is_numeric($score)) {
            return round((float) $score, 2);
        }

        return null;
    }
}
