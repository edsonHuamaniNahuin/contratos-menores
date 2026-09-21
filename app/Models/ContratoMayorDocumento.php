<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Documento (Bases, TDR, etc.) de un procedimiento de Contratos Mayores,
 * capturado por el scraper desde la Ficha de Selección del buscador SEACE.
 *
 * El `file_code` resuelve la descarga on-demand vía el CMS del SEACE:
 *   GET {alfprod}/alfresco/service/osce/downloadDoc?id={file_code}&doc=<rand>&guest=false
 * (el `alf_ticket` de la URL final expira, por eso no se guarda la URL).
 */
class ContratoMayorDocumento extends Model
{
    protected $table = 'contratos_mayores_documentos';

    protected $fillable = [
        'contrato_mayor_id',
        'nomenclatura_normalizada',
        'nombre',
        'etapa',
        'tipo',
        'file_code',
        'filename',
        'fecha_documento',
    ];

    protected $casts = [
        'fecha_documento' => 'date',
    ];

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(ContratoMayor::class, 'contrato_mayor_id');
    }
}
