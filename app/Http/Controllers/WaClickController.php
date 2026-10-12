<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Registra un clic en botones de WhatsApp de las landings (sendBeacon).
 * La conversación sigue en WhatsApp Business; aquí solo queda el rastro
 * medible (landing + UTM + fbclid) para atribución.
 */
class WaClickController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $url = (string) $request->input('url', '');
        $query = parse_url($url, PHP_URL_QUERY);
        parse_str(is_string($query) ? $query : '', $params);

        $param = fn (string $clave, int $max) => is_scalar($params[$clave] ?? null)
            ? mb_substr((string) $params[$clave], 0, $max)
            : null;

        DB::table('wa_clicks')->insert([
            'landing' => mb_substr((string) $request->input('landing', ''), 0, 100) ?: null,
            'utm_source' => $param('utm_source', 100),
            'utm_medium' => $param('utm_medium', 100),
            'utm_campaign' => $param('utm_campaign', 150),
            'utm_content' => $param('utm_content', 150),
            'utm_term' => $param('utm_term', 150),
            'fbclid' => $param('fbclid', 255),
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->noContent();
    }
}
