<?php

namespace App\Services;

use App\Models\ContratoMayor;
use App\Models\ContratoMayorDocumento;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Resolución de documentos del SEACE (CMS Alfresco).
 *
 * Los documentos capturados por el scraper se guardan como `file_code`; la
 * URL de descarga se resuelve on-demand contra el endpoint público del CMS
 * porque el `alf_ticket` que devuelve expira.
 */
class DocumentoSeaceService
{
    /** Dominios del CMS del SEACE: nube (200) y on-premise (201). */
    protected const ECM_DOMAIN = 'https://alfprod.seace.gob.pe/alfresco';
    protected const ECM_DOMAIN_OP = 'https://prodcont2.seace.gob.pe/alfresco';

    /**
     * Resolver la URL directa de descarga de un documento (con cache corto).
     */
    public function resolverUrl(string $fileCode): ?string
    {
        $cacheKey = 'doc-seace:' . $fileCode;
        $url = Cache::get($cacheKey);

        if ($url) {
            return $url;
        }

        $url = $this->consultarCms($fileCode);

        if ($url) {
            // Corto: el alf_ticket de la URL expira en horas.
            Cache::put($cacheKey, $url, now()->addMinutes(5));
        }

        return $url;
    }

    /**
     * Primer documento del contrato (los bots lo usan como fallback cuando el
     * release OCDS aún no publicó `url_documento`).
     */
    public function primerDocumento(ContratoMayor $contrato): ?ContratoMayorDocumento
    {
        return $contrato->documentos()->orderBy('id')->first();
    }

    /**
     * URL resuelta del primer documento del contrato (o null).
     */
    public function urlParaContrato(ContratoMayor $contrato): ?string
    {
        $doc = $this->primerDocumento($contrato);

        return $doc ? $this->resolverUrl($doc->file_code) : null;
    }

    /**
     * Consulta el endpoint público del CMS y devuelve la URL directa.
     * Respuesta JSONP: c12345( {"result":"200","downloadUrl":"/..."} );
     */
    protected function consultarCms(string $fileCode): ?string
    {
        try {
            $resp = Http::timeout(20)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
                ])
                ->get(self::ECM_DOMAIN . '/service/osce/downloadDoc', [
                    'id' => $fileCode,
                    'doc' => 'c' . random_int(1, 99999999),
                    'guest' => 'false',
                ]);
        } catch (\Throwable $e) {
            Log::warning('DocumentoSeace: error consultando el CMS', [
                'file_code' => $fileCode,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if (!$resp->ok()) {
            return null;
        }

        if (!preg_match('/\(\s*(\{.*\})\s*\)\s*;?\s*$/s', $resp->body(), $m)) {
            return null;
        }

        $json = json_decode($m[1], true);
        $relativo = $json['downloadUrl'] ?? null;

        if (!$relativo) {
            Log::warning('DocumentoSeace: respuesta sin downloadUrl', [
                'file_code' => $fileCode,
                'result' => $json['result'] ?? null,
            ]);

            return null;
        }

        $base = ((string) ($json['result'] ?? '')) === '201'
            ? self::ECM_DOMAIN_OP
            : self::ECM_DOMAIN;

        return $base . $relativo;
    }
}
