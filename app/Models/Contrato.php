<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Carbon\Carbon;


class Contrato extends Model
{
    use HasFactory;

    protected $table = 'contratos';

    // Usar id autoincremental estándar de Laravel
    // id_contrato_seace es unique pero no primary key

    protected $fillable = [
        'id_contrato_seace',
        'nro_contratacion',
        'codigo_proceso',
        'entidad',
        'codigo_departamento',
        'nombre_departamento',
        'codigo_provincia',
        'nombre_provincia',
        'id_objeto_contrato',
        'objeto',
        'descripcion',
        'id_estado_contrato',
        'estado',
        'fecha_publicacion',
        'inicio_cotizacion',
        'fin_cotizacion',
        'etapa_contratacion',
        'id_tipo_cotizacion',
        'num_subsanaciones_total',
        'num_subsanaciones_pendientes',
        'datos_raw',
    ];

    protected $casts = [
        'id_contrato_seace' => 'integer',
        'nro_contratacion' => 'integer',
        'id_objeto_contrato' => 'integer',
        'id_estado_contrato' => 'integer',
        'id_tipo_cotizacion' => 'integer',
        'num_subsanaciones_total' => 'integer',
        'num_subsanaciones_pendientes' => 'integer',
        'codigo_departamento' => 'integer',
        'codigo_provincia' => 'integer',
        'fecha_publicacion' => 'datetime',
        'inicio_cotizacion' => 'datetime',
        'fin_cotizacion' => 'datetime',
        'datos_raw' => 'array',
    ];

    /**
     * Scope para contratos vigentes
     */
    public function scopeVigentes($query)
    {
        return $query->where('estado', 'Vigente');
    }

    /**
     * Scope para contratos en evaluación
     */
    public function scopeEnEvaluacion($query)
    {
        return $query->where('estado', 'En Evaluación');
    }

    /**
     * Scope para contratos activos (Vigente o En Evaluación)
     */
    public function scopeActivos($query)
    {
        return $query->whereIn('estado', ['Vigente', 'En Evaluación']);
    }

    /**
     * Scope para contratos recientes
     */
    public function scopeRecientes($query)
    {
        return $query->orderBy('fecha_publicacion', 'desc');
    }

    /**
     * Scope para contratos por entidad
     */
    public function scopePorEntidad($query, $entidad)
    {
        return $query->where('entidad', 'like', "%{$entidad}%");
    }

    /**
     * Scope para contratos por objeto
     */
    public function scopePorObjeto($query, $objeto)
    {
        return $query->where('objeto', $objeto);
    }

    public function archivos(): HasMany
    {
        return $this->hasMany(ContratoArchivo::class);
    }

    public function analisisTdr(): HasManyThrough
    {
        return $this->hasManyThrough(TdrAnalisis::class, ContratoArchivo::class, 'contrato_id', 'contrato_archivo_id');
    }

    /**
     * Scope para contratos próximos a vencer (dentro de X días)
     */
    public function scopeProximosAVencer($query, $dias = 3)
    {
        $fechaLimite = Carbon::now()->addDays($dias);
        return $query->where('fin_cotizacion', '<=', $fechaLimite)
                     ->where('fin_cotizacion', '>=', Carbon::now());
    }

    /**
     * Accessor para saber si el contrato está próximo a vencer
     */
    public function getEstaProximoAVencerAttribute()
    {
        if (!$this->fin_cotizacion) {
            return false;
        }

        return $this->fin_cotizacion->diffInDays(Carbon::now()) <= 3;
    }

    /**
     * Accessor para obtener días restantes
     */
    public function getDiasRestantesAttribute()
    {
        if (!$this->fin_cotizacion) {
            return null;
        }

        $diasRestantes = Carbon::now()->diffInDays($this->fin_cotizacion, false);

        return $diasRestantes > 0 ? $diasRestantes : 0;
    }

    /**
     * Accessor para formatear el código del proceso
     */
    public function getCodigoFormateadoAttribute()
    {
        return strtoupper($this->codigo_proceso);
    }
}
