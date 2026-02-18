<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Proceso SEACE notificado.
 *
 * Almacena un único registro por proceso SEACE (normalizado por seace_proceso_id).
 * Los envíos a usuarios se registran en la tabla notification_sends (relación 1:N).
 *
 * Single Responsibility (SRP): solo gestiona la entidad "proceso notificado".
 */
class NotifiedProcess extends Model
{
    use HasFactory;

    protected $table = 'notified_processes';

    protected $fillable = [
        'seace_proceso_id',
        'codigo',
        'entidad',
        'descripcion',
        'monto_referencial',
        'fecha_publicacion',
        'objeto_contratacion',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    // ─── Relationships ──────────────────────────────────────────────

    /**
     * Envíos de notificación asociados a este proceso.
     */
    public function sends(): HasMany
    {
        return $this->hasMany(NotificationSend::class, 'notified_process_id');
    }

    // ─── Scopes ─────────────────────────────────────────────────────

    /**
     * Filtrar por fecha de publicación (formato SEACE: d/m/Y...).
     */
    public function scopePorFechaPublicacion($query, string $fecha)
    {
        return $query->where('fecha_publicacion', 'like', $fecha . '%');
    }

    /**
     * Procesos notificados a un usuario específico.
     */
    public function scopeParaUsuario($query, int $userId)
    {
        return $query->whereHas('sends', fn ($q) => $q->where('user_id', $userId));
    }

    // ─── Factory Methods ────────────────────────────────────────────

    /**
     * Crear o encontrar un proceso a partir de datos crudos del SEACE.
     *
     * @param  string  $seaceProcesoId  Identificador único del proceso
     * @param  array   $contratoData    Datos crudos del SEACE
     * @return static
     */
    public static function findOrCreateFromSeace(string $seaceProcesoId, array $contratoData): static
    {
        return static::firstOrCreate(
            ['seace_proceso_id' => $seaceProcesoId],
            [
                'codigo' => $contratoData['desContratacion'] ?? null,
                'entidad' => $contratoData['nomEntidad'] ?? null,
                'descripcion' => $contratoData['desObjetoContrato']
                    ?? $contratoData['nomObjetoContrato']
                    ?? null,
                'monto_referencial' => $contratoData['montoReferencial'] ?? null,
                'fecha_publicacion' => $contratoData['fecPublica'] ?? null,
                'objeto_contratacion' => $contratoData['nomObjetoContrato'] ?? null,
                'payload' => $contratoData,
            ]
        );
    }
}
