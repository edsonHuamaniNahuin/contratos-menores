<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContratoSeguimiento extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'contrato_seace_id',
        'codigo_proceso',
        'entidad',
        'objeto',
        'estado',
        'fecha_publicacion',
        'fecha_inicio',
        'fecha_fin',
        'snapshot',
    ];

    protected $casts = [
        'fecha_publicacion' => 'datetime',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'snapshot' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
