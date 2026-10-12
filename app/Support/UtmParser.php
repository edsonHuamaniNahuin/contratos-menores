<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Extrae los parámetros UTM/fbclid del formulario o del Referer
 * (el referer es la URL de la landing con los UTMs del anuncio).
 */
class UtmParser
{
    private const CAMPOS = [
        'utm_source' => 100,
        'utm_medium' => 100,
        'utm_campaign' => 150,
        'utm_content' => 150,
        'utm_term' => 150,
        'fbclid' => 255,
    ];

    public static function extraer(Request $request): array
    {
        $refererParams = self::paramsDelReferer($request);
        $datos = [];

        foreach (self::CAMPOS as $campo => $max) {
            $valor = $request->input($campo) ?? $refererParams[$campo] ?? null;

            $datos[$campo] = is_scalar($valor) && (string) $valor !== ''
                ? mb_substr((string) $valor, 0, $max)
                : null;
        }

        return $datos;
    }

    private static function paramsDelReferer(Request $request): array
    {
        $referer = (string) $request->headers->get('referer', '');
        if ($referer === '') {
            return [];
        }

        $query = parse_url($referer, PHP_URL_QUERY);
        if (! is_string($query) || $query === '') {
            return [];
        }

        parse_str($query, $params);

        return is_array($params) ? $params : [];
    }
}
