<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Genera una operación aleatoria de verificación anti-spam en cada GET de la
 * landing: cada recarga muestra números distintos. La respuesta correcta vive
 * solo en la sesión; la validación en DemoLeadController nunca confía en el HTML.
 *
 * Nota: si el visitante falla la validación de otros campos (nombre, email...),
 * el back() re-renderiza la landing con un captcha nuevo que coincide con la
 * sesión recién generada, por lo que el reintento siempre es coherente.
 */
class DemoCaptcha
{
    public function handle(Request $request, Closure $next): Response
    {
        $a = random_int(3, 9);
        $b = random_int(2, 9);
        session()->put('demo_captcha', [
            'a'         => $a,
            'b'         => $b,
            'resultado' => $a + $b,
        ]);

        View::share('demoCaptchaA', $a);
        View::share('demoCaptchaB', $b);

        return $next($request);
    }
}
