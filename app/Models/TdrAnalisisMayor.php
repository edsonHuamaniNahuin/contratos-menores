<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TdrAnalisisMayor extends Model
{
    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_EXITOSO = 'exitoso';
    public const ESTADO_FALLIDO = 'fallido';

    public const TIPO_GENERAL = 'general';
    public const TIPO_DIRECCIONAMIENTO = 'direccionamiento';

    protected $table = 'tdr_analisis_mayores';

    protected $fillable = [
        'ocid', 'tipo', 'documento_extraido_id', 'url_documento', 'estado',
        'proveedor', 'modelo', 'contexto_contrato', 'resumen', 'payload',
        'requisitos_calificacion', 'reglas_ejecucion', 'penalidades',
        'monto_referencial_text', 'duracion_ms',
        'tokens_prompt', 'tokens_respuesta', 'costo_estimado',
        'error', 'analizado_en',
        'requested_by_user_id', 'origin', 'share_token',
    ];

    protected $casts = [
        'contexto_contrato' => 'array',
        'resumen' => 'array',
        'payload' => 'array',
        'requisitos_calificacion' => 'array',
        'reglas_ejecucion' => 'array',
        'penalidades' => 'array',
        'analizado_en' => 'datetime',
        'costo_estimado' => 'decimal:4',
    ];

    public function ensureShareToken(): string
    {
        if (!$this->share_token) {
            $this->share_token = Str::uuid()->toString();
            $this->saveQuietly();
        }
        return $this->share_token;
    }
}
