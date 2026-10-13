<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Evita que el navegador cachee (incluido BFCache) las páginas de autenticación.
 *
 * Sin esto, un usuario con una pestaña de /login abierta puede enviar un
 * formulario con el token CSRF de una sesión ya expirada → error 419.
 */
class NoCacheAuthPages
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->is('login', 'register', 'forgot-password', 'reset-password', 'reset-password/*')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }
}
