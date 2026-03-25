<?php

namespace App\Models;

use App\Models\Concerns\HasKeywordMatching;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class EmailSubscription extends Model
{
    use HasFactory, HasKeywordMatching;

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

    // ── Keyword matching: provistos por HasKeywordMatching trait ───
    // Override: Email tiene lógica especial con notificar_todo

    public function resolverCoincidenciasContrato(array $contratoData): array
    {
        // Si notificar_todo está activado, siempre pasa (sin filtrar por keywords)
        if ($this->notificar_todo) {
            return ['pasa' => true, 'keywords' => []];
        }

        $resultado = ['pasa' => false, 'keywords' => []];

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

    public function getEstadoTextAttribute(): string
    {
        return $this->activo ? 'Activo' : 'Inactivo';
    }
}
