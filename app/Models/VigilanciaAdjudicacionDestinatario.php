<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Destinatario de alertas de vigilancia de adjudicaciones.
 * Uno o varios, por email y/o WhatsApp.
 */
class VigilanciaAdjudicacionDestinatario extends Model
{
    use HasFactory;

    protected $table = 'vigilancia_adjudicacion_destinatarios';

    protected $fillable = [
        'email',
        'telefono',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
