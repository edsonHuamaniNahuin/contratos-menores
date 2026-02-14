<?php

namespace App\Services\Tdr;

use App\Models\Contrato;
use App\Models\ContratoArchivo;
use App\Models\TdrAnalisis;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TdrPersistenceService
{
    protected string $disk;
    protected string $root;
    protected int $cacheTtlMinutes;
    protected string $defaultProvider;
    protected ?string $defaultModel;

    public function __construct()
    {
        $this->disk = config('tdr.storage_disk', config('filesystems.default'));
        $this->root = trim(config('tdr.storage_root', 'tdr'), '/');
        $this->cacheTtlMinutes = (int) config('tdr.analysis_cache_minutes', 0);
        $this->defaultProvider = config('tdr.default_provider', config('services.analizador_tdr.provider', 'gemini'));
        $this->defaultModel = config('tdr.default_model', config('services.analizador_tdr.model'));
    }

    public function resolveArchivo(
        int $archivoSeaceId,
        string $nombreOriginal,
        ?int $contratoSeaceId = null,
        ?array $contratoSnapshot = null
    ): ContratoArchivo {
        $archivo = ContratoArchivo::where('id_archivo_seace', $archivoSeaceId)->first();

        if ($archivo) {
            $this->maybeUpdateMetadata($archivo, $contratoSeaceId, $contratoSnapshot);
            return $archivo;
        }

        $contratoId = null;
        if ($contratoSeaceId) {
            $contrato = Contrato::where('id_contrato_seace', $contratoSeaceId)->first();
            if ($contrato) {
                $contratoId = $contrato->id;
            }
        }

        $archivo = new ContratoArchivo([
            'contrato_id' => $contratoId,
            'id_contrato_seace' => $contratoSeaceId,
            'id_archivo_seace' => $archivoSeaceId,
            'nombre_original' => $nombreOriginal,
            'extension' => $this->extractAllowedExtension($nombreOriginal),
            'datos_contrato' => $contratoSnapshot,
        ]);

        $this->hydrateContratoMetadata($archivo, $contratoSnapshot);
        $archivo->save();

        return $archivo;
    }

    public function storeBinary(ContratoArchivo $archivo, string $binary, ?string $mimeType = null, ?string $nombreOriginal = null): string
    {
        $disk = Storage::disk($this->disk);
        $directory = $this->buildDirectory($archivo);
        $filename = $this->buildFilename($archivo, $nombreOriginal);
        $path = ltrim($directory . '/' . $filename, '/');

        $disk->put($path, $binary);

        $detectedMime = $mimeType ?: $this->detectMimeFromBinary($binary);
        $sizeInBytes = strlen($binary);

        $archivo->fill([
            'storage_disk' => $this->disk,
            'storage_path' => $path,
            'nombre_sistema' => $filename,
            'extension' => $archivo->extension ?: $this->extractAllowedExtension($filename, 'pdf'),
            'mime_type' => $detectedMime,
            'tamano_bytes' => $sizeInBytes,
            'sha256' => hash('sha256', $binary),
            'descargado_en' => Carbon::now(),
            'verificado_en' => Carbon::now(),
        ])->save();

        return $disk->path($path);
    }

    public function getAbsolutePath(ContratoArchivo $archivo): ?string
    {
        if (!$archivo->hasStoredFile()) {
            return null;
        }

        return Storage::disk($archivo->storage_disk)->path($archivo->storage_path);
    }

    public function purgeStoredFile(ContratoArchivo $archivo): void
    {
        if ($archivo->storage_path) {
            $disk = Storage::disk($archivo->storage_disk);

            if ($disk->exists($archivo->storage_path)) {
                $disk->delete($archivo->storage_path);
            }
        }

        $archivo->fill([
            'storage_path' => null,
            'nombre_sistema' => null,
            'mime_type' => null,
            'tamano_bytes' => null,
            'sha256' => null,
            'descargado_en' => null,
            'verificado_en' => null,
        ])->save();
    }

    public function getCachedAnalysis(ContratoArchivo $archivo, bool $forceRefresh = false): ?TdrAnalisis
    {
        if ($forceRefresh) {
            return null;
        }

        $analisis = $archivo->analisis()
            ->where('estado', TdrAnalisis::ESTADO_EXITOSO)
            ->latest('analizado_en')
            ->first();

        if (!$analisis) {
            return null;
        }

        if ($this->cacheTtlMinutes <= 0 || !$analisis->analizado_en) {
            return $analisis;
        }

        return $analisis->analizado_en->gt(Carbon::now()->subMinutes($this->cacheTtlMinutes))
            ? $analisis
            : null;
    }

    public function storeAnalysis(
        ContratoArchivo $archivo,
        array $normalizado,
        array $payload,
        ?array $contextoContrato = null,
        array $meta = []
    ): TdrAnalisis {
        $attributes = [
            'contrato_archivo_id' => $archivo->id,
            'proveedor' => $meta['proveedor'] ?? $this->defaultProvider,
            'modelo' => $meta['modelo'] ?? $this->defaultModel,
        ];

        $data = [
            'estado' => TdrAnalisis::ESTADO_EXITOSO,
            'contexto_contrato' => $contextoContrato,
            'resumen' => $normalizado,
            'payload' => $payload,
            'requisitos_calificacion' => $normalizado['requisitos_calificacion'] ?? null,
            'reglas_ejecucion' => $normalizado['reglas_ejecucion'] ?? null,
            'penalidades' => $normalizado['penalidades'] ?? null,
            'monto_referencial_text' => $this->extractMonto($normalizado),
            'duracion_ms' => $meta['duracion_ms'] ?? null,
            'tokens_prompt' => $meta['tokens_prompt'] ?? null,
            'tokens_respuesta' => $meta['tokens_respuesta'] ?? null,
            'costo_estimado' => $meta['costo_estimado'] ?? null,
            'error' => null,
            'analizado_en' => $meta['analizado_en'] ?? Carbon::now(),
        ];

        return TdrAnalisis::updateOrCreate($attributes, $data);
    }

    public function storeFail(ContratoArchivo $archivo, string $mensaje, array $meta = []): TdrAnalisis
    {
        return TdrAnalisis::create([
            'contrato_archivo_id' => $archivo->id,
            'estado' => TdrAnalisis::ESTADO_FALLIDO,
            'proveedor' => $meta['proveedor'] ?? $this->defaultProvider,
            'modelo' => $meta['modelo'] ?? $this->defaultModel,
            'error' => $mensaje,
            'analizado_en' => Carbon::now(),
        ]);
    }

    public function buildPayloadFromAnalysis(TdrAnalisis $analisis, bool $fromCache = true): array
    {
        $analisis->loadMissing('archivo');

        return [
            'archivo' => $analisis->archivo?->nombre_original,
            'analisis' => $analisis->resumen ?? [],
            'contexto_contrato' => $analisis->contexto_contrato,
            'timestamp' => optional($analisis->analizado_en)->toDateTimeString() ?? optional($analisis->updated_at)->toDateTimeString(),
            'cache' => $fromCache,
            'analisis_id' => $analisis->id,
        ];
    }

    protected function maybeUpdateMetadata(
        ContratoArchivo $archivo,
        ?int $contratoSeaceId = null,
        ?array $contratoSnapshot = null
    ): void {
        $needsSave = false;

        if ($contratoSeaceId && !$archivo->id_contrato_seace) {
            $archivo->id_contrato_seace = $contratoSeaceId;
            $needsSave = true;
        }

        if ($contratoSnapshot && empty($archivo->datos_contrato)) {
            $archivo->datos_contrato = $contratoSnapshot;
            $needsSave = true;
        }

        if ($contratoSnapshot) {
            $needsSave = $this->hydrateContratoMetadata($archivo, $contratoSnapshot) || $needsSave;
        }

        if ($needsSave) {
            $archivo->save();
        }
    }

    protected function hydrateContratoMetadata(ContratoArchivo $archivo, ?array $snapshot): bool
    {
        if (!$snapshot) {
            return false;
        }

        $dirty = false;

        if (!$archivo->codigo_proceso && Arr::get($snapshot, 'desContratacion')) {
            $archivo->codigo_proceso = $snapshot['desContratacion'];
            $dirty = true;
        }

        if (!$archivo->entidad && Arr::get($snapshot, 'nomEntidad')) {
            $archivo->entidad = $snapshot['nomEntidad'];
            $dirty = true;
        }

        return $dirty;
    }

    protected function buildDirectory(ContratoArchivo $archivo): string
    {
        $parts = [$this->root ?: 'tdr'];
        $parts[] = (string) ($archivo->id_contrato_seace ?: 'sin-contrato');

        return implode('/', $parts);
    }

    protected function buildFilename(ContratoArchivo $archivo, ?string $nombreOriginal = null): string
    {
        $extension = $this->extractAllowedExtension($nombreOriginal ?? $archivo->nombre_original, 'pdf') ?: 'pdf';
        $slug = Str::slug(pathinfo($nombreOriginal ?? $archivo->nombre_original, PATHINFO_FILENAME));

        return sprintf('%s_%s_%s.%s',
            $archivo->id_archivo_seace,
            $archivo->id ?? 'temp',
            $slug ?: 'tdr',
            strtolower($extension)
        );
    }

    protected function extractAllowedExtension(?string $filename, ?string $fallback = null): ?string
    {
        if (!is_string($filename) || trim($filename) === '') {
            return $fallback;
        }

        $extension = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));

        // Solo extensiones esperadas del dominio de TDR
        $allowed = ['pdf', 'zip', 'rar'];

        if (in_array($extension, $allowed, true)) {
            return $extension;
        }

        // Nombres truncados/sanitizados del callback suelen perder el .pdf
        // y dejan pseudo-extensiones largas (ej: _PAT_SUBSANACION_O).
        // Nunca persistir esos valores en DB.
        return $fallback;
    }

    protected function extractMonto(array $normalizado): ?string
    {
        foreach (['monto_referencial', 'monto', 'presupuesto_referencial'] as $key) {
            if (!empty($normalizado[$key])) {
                return is_array($normalizado[$key]) ? json_encode($normalizado[$key]) : (string) $normalizado[$key];
            }
        }

        return null;
    }

    protected function detectMimeFromBinary(string $binary): ?string
    {
        $resource = finfo_open(FILEINFO_MIME_TYPE);

        if (!$resource) {
            return null;
        }

        $mime = finfo_buffer($resource, $binary) ?: null;
        finfo_close($resource);

        return $mime;
    }
}
