<?php

namespace App\Models;

use App\Contracts\ChannelSubscriptionContract;
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
    use HasFactory;

    protected $table = 'whatsapp_subscriptions';

    protected $fillable = [
        'user_id',
        'phone_number',
        'nombre',
        'activo',
        'filtros',
        'company_copy',
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

    public function keywords(): BelongsToMany
    {
        return $this->belongsToMany(
            NotificationKeyword::class,
            'whatsapp_subscription_keyword'
        )->withTimestamps();
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
        return $this->company_copy;
    }

    public function resolverCoincidenciasContrato(array $contratoData): array
    {
        $resultado = [
            'pasa' => false,
            'keywords' => [],
        ];

        if (!$this->pasaFiltrosBasicos($contratoData)) {
            return $resultado;
        }

        $keywords = $this->obtenerKeywordsNormalizados();

        if ($keywords->isEmpty()) {
            $resultado['pasa'] = true;
            return $resultado;
        }

        $haystack = $this->buildContratoHaystack($contratoData);

        if ($haystack === '') {
            return $resultado;
        }

        foreach ($keywords as $keyword) {
            if ($keyword !== '' && str_contains($haystack, $keyword)) {
                $resultado['keywords'][] = $keyword;
            }
        }

        $resultado['pasa'] = !empty($resultado['keywords']);
        $resultado['keywords'] = array_values(array_unique($resultado['keywords']));

        return $resultado;
    }

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

    // ─── Helpers (reutilizados de TelegramSubscription) ─────────────

    public function coincideConFiltros(array $contratoData): bool
    {
        return $this->resolverCoincidenciasContrato($contratoData)['pasa'];
    }

    protected function obtenerKeywordsNormalizados(): Collection
    {
        $this->loadMissing('keywords');

        return $this->keywords
            ->pluck('nombre')
            ->filter()
            ->map(fn ($keyword) => $this->normalizarTexto($keyword))
            ->filter()
            ->values();
    }

    protected function buildContratoHaystack(array $contratoData): string
    {
        $fragmentos = array_filter([
            $contratoData['nomEntidad'] ?? '',
            $contratoData['desContratacion'] ?? '',
            $contratoData['desObjetoContrato'] ?? '',
            $contratoData['nomObjetoContrato'] ?? '',
        ]);

        if (empty($fragmentos)) {
            return '';
        }

        return $this->normalizarTexto(implode(' ', $fragmentos));
    }

    protected function normalizarTexto(?string $valor): string
    {
        if ($valor === null) {
            return '';
        }

        return Str::of($valor)
            ->lower()
            ->ascii()
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->value();
    }

    protected function pasaFiltrosBasicos(array $contratoData): bool
    {
        return true;
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
