<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoExtraido extends Model
{
    protected $table = 'documentos_extraidos';

    protected $fillable = [
        'tipo_contrato',
        'contrato_ref',
        'nombre_archivo',
        'ruta_archivo',
        'tamano_bytes',
        'extraido_en',
    ];

    protected $casts = [
        'extraido_en' => 'datetime',
    ];
}
