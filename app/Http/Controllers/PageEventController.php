<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Recibe eventos de navegación de las landings (heatmap first-party):
 * clics con coordenadas (% del documento) y profundidad de scroll.
 */
class PageEventController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $tipo = (string) $request->input('tipo', '');
        if (! in_array($tipo, ['click', 'scroll'], true)) {
            return response()->noContent();
        }

        $url = (string) $request->input('url', '');
        $query = parse_url($url, PHP_URL_QUERY);
        parse_str(is_string($query) ? $query : '', $params);

        $param = fn (string $clave, int $max) => is_scalar($params[$clave] ?? null)
            ? mb_substr((string) $params[$clave], 0, $max)
            : null;

        $numero = fn (string $clave, float $min, float $max) => is_numeric($request->input($clave))
            ? max($min, min($max, (float) $request->input($clave)))
            : null;

        DB::table('page_events')->insert([
            'landing' => mb_substr((string) $request->input('landing', ''), 0, 100) ?: null,
            'tipo' => $tipo,
            'x_pct' => $numero('x_pct', 0, 100),
            'y_pct' => $numero('y_pct', 0, 500),
            'scroll_pct' => $tipo === 'scroll' ? (int) $numero('scroll_pct', 0, 100) : null,
            'target' => mb_substr((string) $request->input('target', ''), 0, 120) ?: null,
            'vw' => is_numeric($request->input('vw')) ? (int) $request->input('vw') : null,
            'vh' => is_numeric($request->input('vh')) ? (int) $request->input('vh') : null,
            'utm_source' => $param('utm_source', 100),
            'utm_content' => $param('utm_content', 150),
            'fbclid' => $param('fbclid', 255),
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->noContent();
    }
}
