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
Route::get('/buscador-publico', function () {
    return view('buscador-publico');
})->name('buscador.publico');

Route::get('/planes', function () {
    return view('planes');
})->name('planes');

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
    Route::get('/', function () {
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

    Route::get('/suscriptores', function () {
        return view('suscriptores');
    })->name('suscriptores')->middleware('can:view-suscriptores');

    Route::get('/roles-permisos', function () {
        return view('roles-permisos');
    })->name('roles.permisos')->middleware('can:manage-roles-permissions');

    Route::get('/seguimientos', function () {
        return view('seguimientos');
    })->name('seguimientos')->middleware('can:follow-contracts');

    Route::get('/perfil', function () {
        return view('perfil');
    })->name('perfil');

    Route::get('/mis-procesos', function () {
        return view('mis-procesos');
    })->name('mis.procesos')->middleware('can:view-mis-procesos');

    // ─── Suscripciones / Premium ──────────────────────────────────────

    // ⚠️ PASARELA DE PAGO DESACTIVADA TEMPORALMENTE (mantenimiento Openpay)
    // Descomentar las rutas originales cuando se resuelva el problema de tokens:
    //
    // Route::get('/planes/checkout/{plan}', [SubscriptionController::class, 'checkout'])
    //     ->name('planes.checkout');
    //
    // Route::post('/planes/charge', [SubscriptionController::class, 'charge'])
    //     ->name('planes.charge');

    Route::get('/planes/checkout/{plan}', function () {
        return view('pagos-mantenimiento');
    })->name('planes.checkout');

    Route::post('/planes/charge', function () {
        return redirect()->route('planes.checkout', ['plan' => 'monthly']);
    })->name('planes.charge');

    Route::get('/suscripciones-premium', function () {
        return view('suscripciones-premium');
    })->name('suscripciones.premium')->middleware('can:manage-subscriptions');

});

