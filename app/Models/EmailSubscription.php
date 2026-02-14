<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class EmailSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email',
        'activo',
        'notificar_todo',
        'ultima_notificacion_at',
        'notificaciones_enviadas',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'notificar_todo' => 'boolean',
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
            'email_subscription_keyword'
        )->withTimestamps();
    }

    public function sends(): HasMany
    {
        return $this->hasMany(EmailContractSend::class);
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Verificar si un contrato ya fue enviado a esta suscripción (dedup).
     */
    public function yaEnviado(int $contratoSeaceId): bool
    {
        return $this->sends()
            ->where('contrato_seace_id', $contratoSeaceId)
            ->exists();
    }

    /**
     * Registrar que se envió una notificación.
     */
    public function registrarEnvio(int $contratoSeaceId, ?string $contratoCodigo = null): void
    {
        $this->sends()->create([
            'contrato_seace_id' => $contratoSeaceId,
            'contrato_codigo' => $contratoCodigo,
        ]);

        $this->increment('notificaciones_enviadas');
        $this->update(['ultima_notificacion_at' => now()]);
    }

    // ── Lógica de matching (misma que TelegramSubscription) ───────

    /**
     * Verificar si un contrato coincide con los filtros de esta suscripción.
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

        // Si notificar_todo está activado, siempre pasa
        if ($this->notificar_todo) {
            $resultado['pasa'] = true;
            return $resultado;
        }

        $keywords = $this->obtenerKeywordsNormalizados();

        // Sin keywords y sin notificar_todo = no pasa
        if ($keywords->isEmpty()) {
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

    public function getEstadoTextAttribute(): string
    {
        return $this->activo ? 'Activo' : 'Inactivo';
    }
}
