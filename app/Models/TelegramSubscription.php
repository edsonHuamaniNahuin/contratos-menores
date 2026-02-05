<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class TelegramSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'nombre',
        'username',
        'activo',
        'filtros',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'filtros' => 'array',
        'subscrito_at' => 'datetime',
        'ultima_notificacion_at' => 'datetime',
    ];

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
        // Si no hay filtros configurados, enviar todas las notificaciones
        if (empty($this->filtros)) {
            return true;
        }

        // Implementación futura: filtros por departamento, objeto, palabras clave
        // Por ahora, enviar a todos
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
