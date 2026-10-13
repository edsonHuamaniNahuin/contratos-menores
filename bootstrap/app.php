<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->throttleApi();
        $middleware->append(\App\Http\Middleware\NoCacheAuthPages::class);
        $middleware->alias([
            'demo.captcha' => \App\Http\Middleware\DemoCaptcha::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 419 (Page Expired / CSRF): en vez de la pantalla fea, llevar al login
        // con un mensaje claro. Ocurre típicamente cuando la sesión expiró y el
        // formulario quedó con un token viejo.
        // Nota: Laravel 12 convierte TokenMismatchException a HttpException(419)
        // ANTES de los callbacks de render — por eso se tipa HttpException.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            if ($request->expectsJson() || $request->header('X-Livewire')) {
                return response()->json([
                    'message' => 'Tu sesión expiró. Recarga la página e intenta de nuevo.',
                ], 419);
            }

            return redirect()
                ->route('login')
                ->with('status', 'Tu sesión expiró por seguridad. Inicia sesión de nuevo.');
        });
    })->create();
