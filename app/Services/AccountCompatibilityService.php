<?php

namespace App\Services;

use App\Models\SubscriptionContractMatch;
use App\Models\TelegramSubscription;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class AccountCompatibilityService
{
    public function findMatch(TelegramSubscription $subscription, int $contratoId): ?SubscriptionContractMatch
    {
        return $subscription->matches()
            ->where('contrato_seace_id', $contratoId)
            ->first();
    }

    public function canReuseMatch(?SubscriptionContractMatch $match, TelegramSubscription $subscription): bool
    {
        if (!$match) {
            return false;
        }

        if (($match->copy_snapshot ?? null) !== ($subscription->company_copy ?? null)) {
            return false;
        }

        return $match->score !== null;
    }

    public function storeCompatibilityResult(
        TelegramSubscription $subscription,
        array $contratoSnapshot,
        array $compatibilityPayload
    ): SubscriptionContractMatch {
        $subscription->loadMissing('keywords');

        $contratoId = Arr::get($contratoSnapshot, 'idContrato')
            ?? Arr::get($contratoSnapshot, 'id_contrato_seace');

        $timestamp = $this->resolveTimestamp(Arr::get($compatibilityPayload, 'timestamp'));

        return SubscriptionContractMatch::updateOrCreate(
            [
                'telegram_subscription_id' => $subscription->id,
                'contrato_seace_id' => $contratoId,
            ],
            [
                'contrato_codigo' => Arr::get($contratoSnapshot, 'desContratacion')
                    ?? Arr::get($contratoSnapshot, 'codigo_proceso'),
                'contrato_entidad' => Arr::get($contratoSnapshot, 'nomEntidad')
                    ?? Arr::get($contratoSnapshot, 'entidad'),
                'contrato_objeto' => Arr::get($contratoSnapshot, 'nomObjetoContrato')
                    ?? Arr::get($contratoSnapshot, 'objeto'),
                'score' => $this->resolveScore($compatibilityPayload),
                'keywords_snapshot' => $subscription->keywords->pluck('nombre')->filter()->values()->all(),
                'copy_snapshot' => $subscription->company_copy,
                'analisis_payload' => $compatibilityPayload,
                'source' => Arr::get($compatibilityPayload, 'source', 'compatibility-service'),
                'analizado_en' => $timestamp,
            ]
        );
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
