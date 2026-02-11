<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionContractMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'telegram_subscription_id',
        'contrato_seace_id',
        'contrato_codigo',
        'contrato_entidad',
        'contrato_objeto',
        'score',
        'keywords_snapshot',
        'copy_snapshot',
        'analisis_payload',
        'source',
        'analizado_en',
    ];

    protected $casts = [
        'keywords_snapshot' => 'array',
        'analisis_payload' => 'array',
        'score' => 'decimal:2',
        'analizado_en' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(TelegramSubscription::class, 'telegram_subscription_id');
    }
}
