<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailContractSend extends Model
{
    protected $fillable = [
        'email_subscription_id',
        'contrato_seace_id',
        'contrato_codigo',
        'enviado_at',
    ];

    protected $casts = [
        'enviado_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(EmailSubscription::class, 'email_subscription_id');
    }
}
