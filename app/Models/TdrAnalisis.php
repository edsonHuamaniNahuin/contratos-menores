<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TdrAnalisis extends Model
{
    use HasFactory;

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_EXITOSO = 'exitoso';
    public const ESTADO_FALLIDO = 'fallido';

    protected $table = 'tdr_analisis';

    protected $fillable = [
        'contrato_archivo_id',
        'estado',
        'proveedor',
        'modelo',
        'contexto_contrato',
        'resumen',
        'payload',
        'requisitos_calificacion',
        'reglas_ejecucion',
        'penalidades',
        'monto_referencial_text',
        'duracion_ms',
        'tokens_prompt',
        'tokens_respuesta',
        'costo_estimado',
        'error',
        'analizado_en',
    ];

    protected $casts = [
        'contexto_contrato' => 'array',
        'resumen' => 'array',
        'payload' => 'array',
        'analizado_en' => 'datetime',
        'costo_estimado' => 'decimal:4',
    ];

    public function archivo(): BelongsTo
    {
        return $this->belongsTo(ContratoArchivo::class, 'contrato_archivo_id');
    }

    public function esExitoso(): bool
    {
        return $this->estado === self::ESTADO_EXITOSO;
    }
}
