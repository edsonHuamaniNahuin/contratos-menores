<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContratoSeguimientoMayor extends Model
{
    protected $table = 'contrato_seguimientos_mayores';

    protected $fillable = [
        'user_id', 'ocid', 'codigo_proceso',
        'entidad_nombre', 'objeto_contratacion', 'estado',
        'fecha_publicacion', 'valor_referencial', 'moneda',
        'snapshot',
    ];

    protected $casts = [
        'fecha_publicacion' => 'datetime',
        'valor_referencial' => 'decimal:2',
        'snapshot' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
