<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de envío de notificación: proceso → usuario → canal.
 *
 * Cada fila representa UN envío concreto. La unicidad se garantiza por
 * (notified_process_id, user_id, canal, recipient_id) para evitar
 * duplicados en la misma combinación.
 *
 * Los campos recipient_id y subscription_label están desnormalizados
 * intencionalmente: si la suscripción se elimina, el historial se mantiene.
 *
 * Single Responsibility (SRP): solo gestiona el registro de envío.
 */
class NotificationSend extends Model
{
    use HasFactory;

    protected $table = 'notification_sends';

    /**
     * Desactivar updated_at ya que solo se crea (envíos son inmutables).
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'notified_process_id',
        'user_id',
        'canal',
        'recipient_id',
        'subscription_label',
        'keywords_matched',
        'notified_at',
    ];

    protected $casts = [
        'keywords_matched' => 'array',
        'notified_at' => 'datetime',
    ];

    // ─── Relationships ──────────────────────────────────────────────

    /**
     * Proceso notificado asociado.
     */
    public function notifiedProcess(): BelongsTo
    {
        return $this->belongsTo(NotifiedProcess::class, 'notified_process_id');
    }

    /**
     * Usuario destinatario.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Scopes ─────────────────────────────────────────────────────

    /**
     * Filtrar por canal de notificación.
     */
    public function scopeCanal($query, string $canal)
    {
        return $query->where('canal', $canal);
    }

    /**
     * Filtrar por usuario.
     */
    public function scopeParaUsuario($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Ordenar por fecha de notificación descendente.
     */
    public function scopeRecientes($query)
    {
        return $query->orderBy('notified_at', 'desc');
    }

    // ─── Display Helpers ────────────────────────────────────────────

    /**
     * Ícono del canal para la vista.
     */
    public function getCanalIconAttribute(): string
    {
        return match ($this->canal) {
            'telegram' => '✈️',
            'whatsapp' => '📱',
            'email' => '📧',
            default => '📬',
        };
    }

    /**
     * Etiqueta legible del canal.
     */
    public function getCanalLabelAttribute(): string
    {
        return match ($this->canal) {
            'telegram' => 'Telegram',
            'whatsapp' => 'WhatsApp',
            'email' => 'Email',
            default => ucfirst($this->canal),
        };
    }
}
