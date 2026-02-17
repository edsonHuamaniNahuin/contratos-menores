<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    // ─── Plan types ──────────────
    public const PLAN_TRIAL   = 'trial';
    public const PLAN_MONTHLY = 'monthly';
    public const PLAN_YEARLY  = 'yearly';

    // ─── Statuses ────────────────
    public const STATUS_ACTIVE          = 'active';
    public const STATUS_EXPIRED         = 'expired';
    public const STATUS_CANCELLED       = 'cancelled';
    public const STATUS_PAYMENT_PENDING = 'payment_pending';

    // ─── Trial duration ──────────
    public const TRIAL_DAYS = 15;

    protected $fillable = [
        'user_id',
        'plan',
        'status',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'openpay_charge_id',
        'openpay_customer_id',
        'openpay_card_id',
        'payment_method',
        'amount',
        'currency',
        'metadata',
        'cancelled_at',
    ];

    protected $casts = [
        'starts_at'      => 'datetime',
        'ends_at'        => 'datetime',
        'trial_ends_at'  => 'datetime',
        'cancelled_at'   => 'datetime',
        'amount'         => 'decimal:2',
        'metadata'       => 'array',
    ];

    /* ────────────────────────────────
     |  Relationships
     |──────────────────────────────── */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /* ────────────────────────────────
     |  Scopes
     |──────────────────────────────── */

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                     ->where('ends_at', '>', now());
    }

    public function scopeOnTrial($query)
    {
        return $query->where('plan', self::PLAN_TRIAL)
                     ->where('status', self::STATUS_ACTIVE)
                     ->where('trial_ends_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('ends_at', '<=', now())
                     ->orWhere('status', self::STATUS_EXPIRED);
    }

    public function scopePaid($query)
    {
        return $query->whereIn('plan', [self::PLAN_MONTHLY, self::PLAN_YEARLY]);
    }

    /* ────────────────────────────────
     |  Helpers
     |──────────────────────────────── */

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->ends_at?->isFuture();
    }

    public function isOnTrial(): bool
    {
        return $this->plan === self::PLAN_TRIAL
            && $this->isActive()
            && $this->trial_ends_at?->isFuture();
    }

    public function isPaid(): bool
    {
        return in_array($this->plan, [self::PLAN_MONTHLY, self::PLAN_YEARLY])
            && $this->isActive();
    }

    public function daysRemaining(): int
    {
        if (!$this->isActive()) {
            return 0;
        }
        return (int) max(0, now()->diffInDays($this->ends_at, false));
    }

    public function markExpired(): void
    {
        $this->update(['status' => self::STATUS_EXPIRED]);
    }

    public function cancel(): void
    {
        $this->update([
            'status'       => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);
    }
}
