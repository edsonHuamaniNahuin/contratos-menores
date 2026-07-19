<?php

namespace App\Services;

use App\Models\EntidadMayor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de entidades para Contratos Mayores.
 * 
 * Cachea la lista de entidades por 1 semana para evitar consultas
 * constantes a la BD en cada búsqueda. Se refresca vía comando semanal.
 */
class EntidadesMayoresService
{
    private const CACHE_KEY = 'entidades_mayores:lista';
    private const CACHE_TTL = 604800; // 7 días

    /**
     * Obtener lista de entidades desde cache o BD.
     */
    public function listar(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return EntidadMayor::orderBy('nombre')
                ->get(['id', 'nombre', 'ruc'])
                ->toArray();
        });
    }

    /**
     * Buscar entidades que coincidan con un texto (para autocompletado).
     */
    public function buscar(string $texto): array
    {
        $todas = $this->listar();
        
        if (empty($texto)) {
            return $todas;
        }

        $lower = mb_strtolower(trim($texto));

        return array_values(array_filter($todas, function ($e) use ($lower) {
            return str_contains(mb_strtolower($e['nombre']), $lower)
                || str_contains($e['ruc'] ?? '', $lower);
        }));
    }

    /**
     * Refrescar entidades desde la tabla contratos_mayores.
     * Extrae entidades únicas (nombre + RUC) y las persiste.
     */
    public function refrescar(): array
    {
        $entidades = \App\Models\ContratoMayor::selectRaw(
                'entidad_nombre as nombre, MAX(entidad_ruc) as ruc'
            )
            ->whereNotNull('entidad_nombre')
            ->where('entidad_nombre', '!=', '')
            ->groupBy('entidad_nombre')
            ->orderBy('entidad_nombre')
            ->get();

        $insertadas = 0;
        $omitidas = 0;

        foreach ($entidades as $e) {
            try {
                EntidadMayor::updateOrCreate(
                    ['nombre' => $e->nombre],
                    ['ruc' => $e->ruc]
                );
                $insertadas++;
            } catch (\Exception $ex) {
                $omitidas++;
            }
        }

        // Invalidar cache para que recargue
        Cache::forget(self::CACHE_KEY);

        Log::info('EntidadesMayores: refresco completado', [
            'insertadas' => $insertadas,
            'omitidas' => $omitidas,
        ]);

        return ['insertadas' => $insertadas, 'omitidas' => $omitidas];
    }
}
