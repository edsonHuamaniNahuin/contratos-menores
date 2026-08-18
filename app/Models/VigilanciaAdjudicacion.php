<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Proceso de Contrato Mayor en vigilancia automática (valor >= umbral).
 *
 * SOLO almacena el identificador (ocid) del proceso; los datos viven en
 * contratos_mayores y se consultan por la relación contrato().
 *
 * El job VigilarAdjudicacionesMayoresJob consulta 1x1 el estado actual
 * contra la API y dispara alertas cuando llega la buena pro.
 */
class VigilanciaAdjudicacion extends Model
{
    use HasFactory;

    protected $table = 'vigilancia_adjudicaciones';

    protected $fillable = [
        'ocid',
        'estado_notificado',
        'notificado_en',
    ];

    protected $casts = [
        'notificado_en' => 'datetime',
    ];

    public const ESTADOS_BUENA_PRO = ['ADJUDICADO', 'CONSENTIDO', 'OTORGADO', 'CONTRATADO'];

    public const ESTADOS_FINALES = [
        'ADJUDICADO', 'CONSENTIDO', 'OTORGADO', 'CONTRATADO',
        'DESIERTO', 'CANCELADO', 'NULO', 'SUSPENDIDO', 'ARCHIVADO',
    ];

    /**
     * Proceso relacionado en contratos_mayores (por ocid, no por id).
     */
    public function contrato(): BelongsTo
    {
        return $this->belongsTo(ContratoMayor::class, 'ocid', 'ocid');
    }

    public function estaNotificado(): bool
    {
        return $this->notificado_en !== null;
    }
}
