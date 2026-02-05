<?php

namespace App\Http\Controllers;

use App\Models\ContratoArchivo;
use Illuminate\Support\Facades\Storage;

class ContratoArchivoController extends Controller
{
    public function download(ContratoArchivo $archivo)
    {
        if (!$archivo->hasStoredFile()) {
            abort(404, 'Archivo no encontrado o fue eliminado del repositorio local');
        }

        $disk = Storage::disk($archivo->storage_disk ?? config('filesystems.default'));

        return $disk->download($archivo->storage_path, $archivo->nombre_original);
    }
}
