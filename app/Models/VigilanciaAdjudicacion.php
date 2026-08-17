<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Proceso de Contrato Mayor en vigilancia automática (valor >= umbral).
 * El job VigilarAdjudicacionesMayoresJob consulta 1x1 su estado actual
 * contra la API OCDS y dispara alertas cuando llega la buena pro.
 */
class VigilanciaAdjudicacion extends Model
{
    use HasFactory;

    protected $table = 'vigilancia_adjudicaciones';

    protected $fillable = [
        'ocid',
        'nomenclatura',
        'entidad_nombre',
        'valor_referencial',
        'estado',
        'fecha_publicacion',
        'estado_notificado',
        'notificado_en',
    ];

    protected $casts = [
        'valor_referencial' => 'decimal:2',
        'fecha_publicacion' => 'datetime',
        'notificado_en' => 'datetime',
    ];

    public const ESTADOS_BUENA_PRO = ['ADJUDICADO', 'CONSENTIDO', 'OTORGADO', 'CONTRATADO'];

    public const ESTADOS_FINALES = [
        'ADJUDICADO', 'CONSENTIDO', 'OTORGADO', 'CONTRATADO',
        'DESIERTO', 'CANCELADO', 'NULO', 'SUSPENDIDO', 'ARCHIVADO',
    ];

    public function estaNotificado(): bool
    {
        return $this->notificado_en !== null;
    }
}
