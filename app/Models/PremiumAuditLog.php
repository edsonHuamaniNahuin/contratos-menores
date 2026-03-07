<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PremiumAuditLog extends Model
{
    // Solo timestamp de creación (no necesita updated_at)
    public $timestamps = false;

    public const ACTION_GRANTED = 'granted';
    public const ACTION_REVOKED = 'revoked';

    public const SOURCE_PURCHASE      = 'purchase';
    public const SOURCE_TRIAL          = 'trial';
    public const SOURCE_ADMIN          = 'admin';
    public const SOURCE_SYSTEM_EXPIRY  = 'system_expiry';
    public const SOURCE_ADMIN_ROLE     = 'admin_role_change';
    public const SOURCE_RENEWAL        = 'renewal';

    protected $fillable = [
        'user_id',
        'action',
        'source',
        'plan',
        'subscription_id',
        'granted_by',
        'premium_starts_at',
        'premium_ends_at',
        'days_remaining',
        'gateway_provider',
        'charge_id',
        'amount',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'premium_starts_at' => 'datetime',
        'premium_ends_at'   => 'datetime',
        'amount'            => 'decimal:2',
        'metadata'          => 'array',
        'created_at'        => 'datetime',
    ];

    // ── Relaciones ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function grantedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    // ── Scopes ──

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeGranted($query)
    {
        return $query->where('action', self::ACTION_GRANTED);
    }

    public function scopeRevoked($query)
    {
        return $query->where('action', self::ACTION_REVOKED);
    }

    // ── Helpers ──

    public function isGranted(): bool
    {
        return $this->action === self::ACTION_GRANTED;
    }

    public function isRevoked(): bool
    {
        return $this->action === self::ACTION_REVOKED;
    }

    public function sourceLabel(): string
    {
        return match ($this->source) {
            self::SOURCE_PURCHASE      => 'Compra',
            self::SOURCE_TRIAL         => 'Prueba gratuita',
            self::SOURCE_ADMIN         => 'Administrador',
            self::SOURCE_SYSTEM_EXPIRY => 'Expiración automática',
            self::SOURCE_ADMIN_ROLE    => 'Cambio de rol (admin)',
            self::SOURCE_RENEWAL       => 'Renovación automática',
            default                    => ucfirst($this->source),
        };
    }
}
