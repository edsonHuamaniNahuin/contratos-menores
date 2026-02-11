<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CuentaSeaceController;
use App\Http\Controllers\ContratoArchivoController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;

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

Route::middleware('auth')->group(function () {
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

    // Ruta para buscador público SEACE
    Route::get('/buscador-publico', function () {
        return view('buscador-publico');
    })->name('buscador.publico')->middleware('can:view-buscador-publico');
});

