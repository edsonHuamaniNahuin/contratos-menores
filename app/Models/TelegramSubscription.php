<?php

namespace App\Models;

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
    use HasFactory;

    protected $fillable = [
        'user_id',
        'chat_id',
        'nombre',
        'username',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function keywords(): BelongsToMany
    {
        return $this->belongsToMany(
            NotificationKeyword::class,
            'notification_keyword_subscription'
        )->withTimestamps();
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
     * Registrar que se envió una notificación a este suscriptor
     */
    public function registrarNotificacion(): void
    {
        $this->increment('notificaciones_recibidas');
        $this->update(['ultima_notificacion_at' => now()]);
    }

    /**
     * Verificar si un filtro coincide con el contrato
     */
    public function coincideConFiltros(array $contratoData): bool
    {
        return $this->resolverCoincidenciasContrato($contratoData)['pasa'];
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
        // A futuro se pueden honrar filtros por departamento, objeto, etc.
        // Por ahora, mientras no existan reglas explícitas, siempre pasa.
        return true;
    }

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
