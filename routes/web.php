<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CuentaSeaceController;
use App\Http\Controllers\ContratoArchivoController;

Route::get('/', function () {
    return view('home');
})->name('home');

// Rutas de gestión de cuentas SEACE
Route::resource('cuentas', CuentaSeaceController::class);

// Ruta adicional para cuentas
Route::post('cuentas/{cuenta}/toggle-activa', [CuentaSeaceController::class, 'toggleActiva'])
    ->name('cuentas.toggle-activa');

// Ruta para prueba de endpoints
Route::get('/prueba-endpoints', function () {
    return view('prueba-endpoints');
})->name('prueba-endpoints');

// Ruta para configuración del sistema
Route::get('/configuracion', function () {
    return view('configuracion');
})->name('configuracion');

Route::get('/tdr-repository', function () {
    return view('tdr-repository');
})->name('tdr.repository');

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

