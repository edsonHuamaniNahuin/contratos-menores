<?php

namespace App\Models;

use App\Contracts\ChannelSubscriptionContract;
use App\Models\Concerns\HasKeywordMatching;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Modelo de suscripción WhatsApp.
 *
 * Estructura espejo de TelegramSubscription para garantizar
 * paridad funcional. Reutiliza notification_keywords vía tabla
 * pivot propia (whatsapp_subscription_keyword).
 */
class WhatsAppSubscription extends Model implements ChannelSubscriptionContract
{
    use HasFactory, HasKeywordMatching;

    protected $table = 'whatsapp_subscriptions';

    protected $fillable = [
        'user_id',
        'phone_number',
        'nombre',
        'activo',
        'filtros',
        'subscrito_at',
        'ultima_notificacion_at',
        'notificaciones_recibidas',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'filtros' => 'array',
        'subscrito_at' => 'datetime',
        'ultima_notificacion_at' => 'datetime',
    ];

    // ─── Relationships ──────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Keywords delegados al perfil unificado del usuario.
     */
    public function keywords(): BelongsToMany
    {
        $profile = $this->user?->subscriberProfile;

        if ($profile) {
            return $profile->keywords();
        }

        return $this->belongsToMany(
            NotificationKeyword::class,
            'subscriber_profile_keyword',
            'subscriber_profile_id',
            'notification_keyword_id'
        )->whereRaw('1 = 0');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(SubscriptionContractMatch::class, 'whatsapp_subscription_id');
    }

    // ─── ChannelSubscriptionContract ────────────────────────────────

    public function getRecipientId(): string
    {
        return $this->phone_number;
    }

    /**
     * Nombre del canal de notificación.
     * Usado por ImportadorTdrEngine para resolver el servicio correcto.
     */
    public function channelName(): string
    {
        return 'whatsapp';
    }

    public function getCompanyCopy(): ?string
    {
        return $this->user?->subscriberProfile?->company_copy;
    }

    /**
     * Accessor: $subscription->company_copy sigue funcionando.
     */
    public function getCompanyCopyAttribute(): ?string
    {
        return $this->getCompanyCopy();
    }

    // resolverCoincidenciasContrato(), coincideConFiltros(), obtenerKeywordsNormalizados(),
    // buildContratoHaystack(), normalizarTexto(), pasaFiltrosBasicos()
    // → provistos por HasKeywordMatching trait

    public function registrarNotificacion(): void
    {
        $this->increment('notificaciones_recibidas');
        $this->update(['ultima_notificacion_at' => now()]);
    }

    // ─── Scopes ─────────────────────────────────────────────────────

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    // ─── Accessors ──────────────────────────────────────────────────

    public function getEstadoTextAttribute(): string
    {
        return $this->activo ? 'Activo' : 'Inactivo';
    }

    public function getDiasDesdeUltimaNotificacionAttribute(): ?int
    {
        if (!$this->ultima_notificacion_at) {
            return null;
        }

        return now()->diffInDays($this->ultima_notificacion_at);
    }

    /**
     * Formato legible del número: +51 987 654 321
     */
    public function getPhoneFormattedAttribute(): string
    {
        $phone = $this->phone_number;

        if (strlen($phone) === 11 && str_starts_with($phone, '51')) {
            return '+' . substr($phone, 0, 2) . ' ' . substr($phone, 2, 3) . ' ' . substr($phone, 5, 3) . ' ' . substr($phone, 8);
        }

        return '+' . $phone;
    }
}
