<?php

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CuentaSeaceController;
use App\Http\Controllers\ContratoArchivoController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\SubscriptionController;
use App\Models\TdrAnalisis;
use Illuminate\Support\Facades\Cache;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout')
    ->middleware('auth');

// ─── Rutas públicas ───────────────────────────────────────────────
Route::get('/', function () {
    return view('landing');
})->name('landing');

Route::get('/buscador-publico', function () {
    return view('buscador-publico');
})->name('buscador.publico');

Route::get('/planes', function () {
    return view('planes');
})->name('planes');

Route::get('/contacto', function () {
    return view('contacto');
})->name('contacto');

Route::get('/manual', function () {
    return view('manual');
})->name('manual');

// ─── Páginas legales ───────────────────────────────────────────────
Route::get('/politica-de-privacidad', function () {
    return view('legal.politica-privacidad');
})->name('legal.politica-privacidad');

Route::get('/eliminacion-de-datos', function () {
    return view('legal.eliminacion-datos');
})->name('legal.eliminacion-datos');

Route::get('/condiciones-del-servicio', function () {
    return view('legal.condiciones-servicio');
})->name('legal.condiciones-servicio');

// ─── Análisis compartido (público) ────────────────────────────────
Route::get('/analisis/{token}', function (string $token) {
    $analisis = TdrAnalisis::where('share_token', $token)
        ->where('estado', TdrAnalisis::ESTADO_EXITOSO)
        ->firstOrFail();

    $analisis->loadMissing('archivo');

    return view('analisis-compartido', compact('analisis'));
})->name('analisis.compartido')->where('token', '[0-9a-f\-]{36}');

// ─── Guía de cotización (accesible desde bots) ───────────────────
Route::get('/cotizar-guia', function () {
    return view('cotizar-guia');
})->name('cotizar.guia');

// ─── Sitemap XML (SEO) ──────────────────────────────────────────
Route::get('/sitemap.xml', function () {
    // Solo producción genera sitemap real
    if (!app()->environment('production')) {
        return response('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>', 200, ['Content-Type' => 'application/xml']);
    }

    $xml = Cache::remember('sitemap_xml', 3600, function () {
        $baseUrl = rtrim(config('app.url'), '/');
        $now = now()->toAtomString();

        $urls = [];

        // Páginas estáticas
        $staticPages = [
            ['loc' => '/', 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => '/buscador-publico', 'priority' => '1.0', 'changefreq' => 'hourly'],
            ['loc' => '/planes', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => '/contacto', 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['loc' => '/manual', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => '/cotizar-guia', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => '/politica-de-privacidad', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => '/eliminacion-de-datos', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => '/condiciones-del-servicio', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ];

        foreach ($staticPages as $page) {
            $urls[] = [
                'loc' => $baseUrl . $page['loc'],
                'lastmod' => $now,
                'changefreq' => $page['changefreq'],
                'priority' => $page['priority'],
            ];
        }

        // Buscador por departamento (URLs indexables)
        try {
            $buscadorService = app(\App\Services\SeaceBuscadorPublicoService::class);
            $departamentos = $buscadorService->obtenerDepartamentos();

            foreach ($departamentos['data'] ?? [] as $depto) {
                $slug = mb_strtolower(trim($depto['nom'] ?? ''));
                $slug = strtr($slug, [
                    'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
                    'ñ' => 'n', 'ü' => 'u',
                ]);
                $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
                $slug = trim($slug, '-');

                if ($slug) {
                    $urls[] = [
                        'loc' => $baseUrl . '/buscador-publico?dep=' . $slug,
                        'lastmod' => $now,
                        'changefreq' => 'daily',
                        'priority' => '0.9',
                    ];
                }
            }

            // Buscador por objeto de contratación
            $objetos = $buscadorService->obtenerObjetosContratacion();
            foreach ($objetos['data'] ?? [] as $obj) {
                $slug = mb_strtolower(trim($obj['nom'] ?? ''));
                $slug = strtr($slug, [
                    'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
                    'ñ' => 'n', 'ü' => 'u',
                ]);
                $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
                $slug = trim($slug, '-');

                if ($slug) {
                    $urls[] = [
                        'loc' => $baseUrl . '/buscador-publico?objeto=' . $slug,
                        'lastmod' => $now,
                        'changefreq' => 'daily',
                        'priority' => '0.8',
                    ];
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Sitemap: Error cargando catálogos SEACE', ['error' => $e->getMessage()]);
        }

        // Análisis compartidos públicos
        $analisis = TdrAnalisis::where('estado', TdrAnalisis::ESTADO_EXITOSO)
            ->whereNotNull('share_token')
            ->orderByDesc('updated_at')
            ->limit(500)
            ->get(['share_token', 'updated_at']);

        foreach ($analisis as $a) {
            $urls[] = [
                'loc' => $baseUrl . '/analisis/' . $a->share_token,
                'lastmod' => $a->updated_at->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ];
        }

        // Generar XML
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xmlContent .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xmlContent .= "  <url>\n";
            $xmlContent .= '    <loc>' . htmlspecialchars($url['loc'], ENT_XML1) . "</loc>\n";
            $xmlContent .= '    <lastmod>' . $url['lastmod'] . "</lastmod>\n";
            $xmlContent .= '    <changefreq>' . $url['changefreq'] . "</changefreq>\n";
            $xmlContent .= '    <priority>' . $url['priority'] . "</priority>\n";
            $xmlContent .= "  </url>\n";
        }

        $xmlContent .= '</urlset>';

        return $xmlContent;
    });

    return response($xml, 200, ['Content-Type' => 'application/xml']);
})->name('sitemap');

// ─── Robots.txt dinámico (bloquea indexación en QA) ─────────────
Route::get('/robots.txt', function () {
    // Solo producción permite indexación
    if (app()->environment('production')) {
        $baseUrl = rtrim(config('app.url'), '/');
        $content = <<<ROBOTS
User-agent: *
Allow: /
Allow: /buscador-publico
Allow: /planes
Allow: /manual
Allow: /contacto
Allow: /cotizar-guia
Allow: /analisis/
Allow: /politica-de-privacidad
Allow: /eliminacion-de-datos
Allow: /condiciones-del-servicio

# Rutas privadas
Disallow: /dashboard
Disallow: /direccionamiento
Disallow: /cuentas
Disallow: /prueba-endpoints
Disallow: /configuracion
Disallow: /tdr-repository
Disallow: /configuracion-alertas
Disallow: /roles-permisos
Disallow: /seguimientos
Disallow: /perfil
Disallow: /mis-procesos
Disallow: /suscripciones-premium
Disallow: /email/
Disallow: /login
Disallow: /register
Disallow: /forgot-password
Disallow: /reset-password
Disallow: /logout
Disallow: /seace/
Disallow: /tdr/
Disallow: /livewire/

Sitemap: {$baseUrl}/sitemap.xml
ROBOTS;
    } else {
        // QA, staging, local → bloquear TODO
        $content = <<<ROBOTS
# Entorno NO productivo — NO indexar
User-agent: *
Disallow: /
ROBOTS;
    }

    return response($content, 200, ['Content-Type' => 'text/plain']);
});

// Ruta para descargar archivos temporales del SEACE
Route::get('/seace/download/{filename}', function ($filename) {
    $path = storage_path('app/temp/' . $filename);

    if (!file_exists($path)) {
        abort(404, 'Archivo no encontrado o ya fue descargado');
    }

    // Descargar y eliminar archivo temporal
    return response()->download($path, $filename)->deleteFileAfterSend(true);
})->name('seace.download.temp');

Route::get('/tdr/archivos/{archivo}/descargar', [ContratoArchivoController::class, 'download'])
    ->name('tdr.archivos.download');

// ─── Verificación de correo electrónico ───────────────────────────────
Route::middleware('auth')->group(function () {
    // Pantalla "revisa tu correo"
    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verification.notice');

    // Enlace firmado que llega al correo
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect()->route('home')->with('status', '¡Correo verificado exitosamente!');
    })->middleware('signed')->name('verification.verify');

    // Reenviar enlace de verificación
    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('status', 'verification-link-sent');
    })->middleware('throttle:6,1')->name('verification.send');
});

// ─── Rutas protegidas (requieren auth + email verificado) ─────────────
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('home');
    })->name('home');

    // Rutas de gestión de cuentas SEACE
    Route::resource('cuentas', CuentaSeaceController::class)->middleware('can:view-cuentas');

    // Ruta adicional para cuentas
    Route::post('cuentas/{cuenta}/toggle-activa', [CuentaSeaceController::class, 'toggleActiva'])
        ->name('cuentas.toggle-activa')
        ->middleware('can:view-cuentas');

    // Ruta para prueba de endpoints
    Route::get('/prueba-endpoints', function () {
        return view('prueba-endpoints');
    })->name('prueba-endpoints')->middleware('can:view-prueba-endpoints');

    // Ruta para configuración del sistema
    Route::get('/configuracion', function () {
        return view('configuracion');
    })->name('configuracion')->middleware('can:view-configuracion');

    Route::get('/tdr-repository', function () {
        return view('tdr-repository');
    })->name('tdr.repository')->middleware('can:view-tdr-repository');

    Route::get('/direccionamiento', function () {
        return view('direccionamiento');
    })->name('direccionamiento')->middleware('can:view-tdr-repository');

    Route::get('/configuracion-alertas', function () {
        return view('configuracion-alertas');
    })->name('configuracion-alertas')->middleware('can:view-configuracion-alertas');

    Route::get('/roles-permisos', function () {
        return view('roles-permisos');
    })->name('roles.permisos')->middleware('can:manage-roles-permissions');

    Route::get('/seguimientos', function () {
        return view('seguimientos');
    })->name('seguimientos')->middleware('can:follow-contracts');

    Route::get('/perfil', function () {
        return view('perfil');
    })->name('perfil');

    Route::get('/mi-suscripcion', function () {
        return view('mi-suscripcion');
    })->name('mi.suscripcion');

    Route::get('/mis-procesos', function () {
        return view('mis-procesos');
    })->name('mis.procesos')->middleware('can:view-mis-procesos');

    // ─── Suscripciones / Premium ──────────────────────────────────────

    Route::get('/planes/checkout/{plan}', [SubscriptionController::class, 'checkout'])
        ->name('planes.checkout');

    Route::post('/planes/charge', [SubscriptionController::class, 'charge'])
        ->name('planes.charge');

    // Callback de MercadoPago (retorno después de pagar en MP)
    Route::get('/planes/callback', [SubscriptionController::class, 'callback'])
        ->name('planes.callback');

    Route::get('/suscripciones-premium', function () {
        return view('suscripciones-premium');
    })->name('suscripciones.premium')->middleware('can:manage-subscriptions');

    // ─── Proforma técnica ─────────────────────────────────────────────
    // (Intencionalmente dentro del grupo auth — se acceden via enlace desde bots)

});

// ─── Proforma técnica (rutas públicas por token UUID) ──────────────────────
// El token UUID de 2h actúa como clave de acceso opaca — no requiere sesión.
// Esto permite que los bots (Telegram, WhatsApp) envíen los enlaces directamente.
Route::get('/proforma/{token}/word', [\App\Http\Controllers\ProformaController::class, 'downloadWord'])
    ->name('proforma.word');
Route::get('/proforma/{token}/print', [\App\Http\Controllers\ProformaController::class, 'viewPrint'])
    ->name('proforma.print');


