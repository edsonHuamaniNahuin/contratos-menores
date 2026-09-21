<?php

namespace App\Http\Controllers;

use App\Models\ContratoMayorDocumento;
use App\Services\DocumentoSeaceService;

/**
 * Descarga de documentos (Bases, TDR, ...) de procesos de Contratos Mayores
 * capturados desde la Ficha de Selección del buscador SEACE.
 *
 * El CMS del SEACE entrega la URL final con un `alf_ticket` que expira, por
 * eso se resuelve on-demand en cada clic (con cache corto) y se redirige.
 */
class DocumentoSeaceController extends Controller
{
    public function __construct(protected DocumentoSeaceService $documentos)
    {
    }

    public function descargar(string $fileCode)
    {
        if (!preg_match('/^[A-Za-z0-9-]{20,64}$/', $fileCode)) {
            abort(404);
        }

        if (!ContratoMayorDocumento::where('file_code', $fileCode)->exists()) {
            abort(404);
        }

        $url = $this->documentos->resolverUrl($fileCode);

        if (!$url) {
            abort(502, 'No se pudo obtener el documento desde el SEACE. Intenta nuevamente.');
        }

        return redirect()->away($url);
    }

    /**
     * Deep-link público a la Ficha de Selección del SEACE (cronograma, ítems,
     * contratos, historial). No se almacena su contenido: se consulta allá.
     */
    public function ficha(string $fichaId)
    {
        if (!preg_match('/^[a-f0-9-]{36}$/i', $fichaId)) {
            abort(404);
        }

        if (!\App\Models\ContratoMayor::where('ficha_seace_id', $fichaId)->exists()) {
            abort(404);
        }

        return redirect()->away(
            'https://prod2.seace.gob.pe/seacebus-uiwd-pub/fichaSeleccion/fichaSeleccion.xhtml?id=' . $fichaId . '&ptoRetorno=LOCAL'
        );
    }
}
