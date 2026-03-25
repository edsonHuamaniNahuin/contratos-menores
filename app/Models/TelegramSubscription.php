<?php

namespace App\Models;

use App\Models\Concerns\HasKeywordMatching;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TelegramSubscription extends Model
{
    use HasFactory, HasKeywordMatching;

    protected $fillable = [
        'user_id',
        'chat_id',
        'nombre',
        'username',
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

        // Fallback: relación vacía para evitar errores si no hay perfil
        return $this->belongsToMany(
            NotificationKeyword::class,
            'subscriber_profile_keyword',
            'subscriber_profile_id',
            'notification_keyword_id'
        )->whereRaw('1 = 0');
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

    public function matches(): HasMany
    {
        return $this->hasMany(SubscriptionContractMatch::class);
    }

    /**
     * Scope para obtener solo suscripciones activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Nombre del canal de notificación.
     * Usado por ImportadorTdrEngine para resolver el servicio correcto.
     */
    public function channelName(): string
    {
        return 'telegram';
    }

    /**
     * Registrar que se envió una notificación a este suscriptor
     */
    public function registrarNotificacion(): void
    {
        $this->increment('notificaciones_recibidas');
        $this->update(['ultima_notificacion_at' => now()]);
    }

    // coincideConFiltros(), resolverCoincidenciasContrato(), obtenerKeywordsNormalizados(),
    // buildContratoHaystack(), normalizarTexto(), pasaFiltrosBasicos()
    // → provistos por HasKeywordMatching trait

    /**
     * Accessor para mostrar el estado
     */
    public function getEstadoTextAttribute(): string
    {
        return $this->activo ? 'Activo' : 'Inactivo';
    }

    /**
     * Accessor para mostrar días desde última notificación
     */
    public function getDiasDesdeUltimaNotificacionAttribute(): ?int
    {
        if (!$this->ultima_notificacion_at) {
            return null;
        }

        return now()->diffInDays($this->ultima_notificacion_at);
    }
}
