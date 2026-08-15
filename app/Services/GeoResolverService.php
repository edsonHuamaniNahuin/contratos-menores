<?php

namespace App\Services;

use App\Models\Departamento;
use App\Models\Distrito;
use App\Models\Provincia;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Resuelve nombres de geografía (departamento/provincia/distrito) a IDs
 * de las tablas maestras, creándolos si no existen (firstOrCreate).
 *
 * Optimizaciones:
 *  - Cache estático en memoria por proceso (evita N queries por contrato).
 *  - Normalización de nombres (trim + uppercase) para dedup consistente.
 *  - Cache de "todos los departamentos" para el buscador (24h).
 */
class GeoResolverService
{
    protected static array $departamentosCache = [];
    protected static array $provinciasCache = [];
    protected static array $distritosCache = [];

    /**
     * Resuelve la terna (departamento, provincia, distrito) a IDs.
     * Si algún nombre viene vacío, su ID se resuelve como null.
     *
     * @return array{departamento_id: ?int, provincia_id: ?int, distrito_id: ?int}
     */
    public function resolver(string $departamento, string $provincia = '', string $distrito = ''): array
    {
        $depId = $this->departamentoId($departamento);
        $provId = null;
        $distId = null;

        if ($depId !== null && $provincia !== '') {
            $provId = $this->provinciaId($depId, $provincia);

            if ($provId !== null && $distrito !== '') {
                $distId = $this->distritoId($provId, $distrito);
            }
        }

        return [
            'departamento_id' => $depId,
            'provincia_id' => $provId,
            'distrito_id' => $distId,
        ];
    }

    public function departamentoId(string $nombre): ?int
    {
        $nombre = $this->normalizar($nombre);
        if ($nombre === '') {
            return null;
        }

        $key = $nombre;
        if (isset(self::$departamentosCache[$key])) {
            return self::$departamentosCache[$key];
        }

        $dep = Departamento::firstOrCreate(['nombre' => $nombre]);
        self::$departamentosCache[$key] = $dep->id;

        return $dep->id;
    }

    public function provinciaId(int $departamentoId, string $nombre): ?int
    {
        $nombre = $this->normalizar($nombre);
        if ($nombre === '') {
            return null;
        }

        $key = "{$departamentoId}:{$nombre}";
        if (isset(self::$provinciasCache[$key])) {
            return self::$provinciasCache[$key];
        }

        $prov = Provincia::firstOrCreate([
            'departamento_id' => $departamentoId,
            'nombre' => $nombre,
        ]);
        self::$provinciasCache[$key] = $prov->id;

        return $prov->id;
    }

    public function distritoId(int $provinciaId, string $nombre): ?int
    {
        $nombre = $this->normalizar($nombre);
        if ($nombre === '') {
            return null;
        }

        $key = "{$provinciaId}:{$nombre}";
        if (isset(self::$distritosCache[$key])) {
            return self::$distritosCache[$key];
        }

        $dist = Distrito::firstOrCreate([
            'provincia_id' => $provinciaId,
            'nombre' => $nombre,
        ]);
        self::$distritosCache[$key] = $dist->id;

        return $dist->id;
    }

    /**
     * Lista de departamentos con conteo de contratos (para filtros del buscador).
     * Cache 1h porque los conteos cambian con cada importación.
     */
    public function departamentosParaFiltro(): array
    {
        return Cache::remember('geo:departamentos_filtro', 3600, function () {
            return Departamento::query()
                ->orderBy('nombre')
                ->get(['id', 'nombre'])
                ->toArray();
        });
    }

    public function provinciasParaFiltro(int $departamentoId): array
    {
        return Cache::remember("geo:provincias_filtro:{$departamentoId}", 3600, function () use ($departamentoId) {
            return Provincia::where('departamento_id', $departamentoId)
                ->orderBy('nombre')
                ->get(['id', 'nombre'])
                ->toArray();
        });
    }

    public function distritosParaFiltro(int $provinciaId): array
    {
        return Cache::remember("geo:distritos_filtro:{$provinciaId}", 3600, function () use ($provinciaId) {
            return Distrito::where('provincia_id', $provinciaId)
                ->orderBy('nombre')
                ->get(['id', 'nombre'])
                ->toArray();
        });
    }

    protected function normalizar(string $nombre): string
    {
        $nombre = trim($nombre);
        $nombre = mb_strtoupper($nombre, 'UTF-8');

        return $nombre;
    }
}
