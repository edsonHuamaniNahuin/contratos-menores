<?php

namespace App\Models\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Trait reutilizable para coincidencia de keywords contra contratos SEACE.
 *
 * Requiere que el modelo tenga la relación: $this->user->subscriberProfile->keywords
 */
trait HasKeywordMatching
{
    public function resolverCoincidenciasContrato(array $contratoData): array
    {
        $resultado = ['pasa' => false, 'keywords' => []];

        if (method_exists($this, 'pasaFiltrosBasicos') && !$this->pasaFiltrosBasicos($contratoData)) {
            return $resultado;
        }

        $keywords = $this->obtenerKeywordsNormalizados();

        if ($keywords->isEmpty()) {
            $resultado['pasa'] = true;
            return $resultado;
        }

        $haystack = $this->buildContratoHaystack($contratoData);

        if ($haystack === '') {
            return $resultado;
        }

        foreach ($keywords as $keyword) {
            if ($keyword !== '' && str_contains($haystack, $keyword)) {
                $resultado['keywords'][] = $keyword;
            }
        }

        $resultado['pasa'] = !empty($resultado['keywords']);
        $resultado['keywords'] = array_values(array_unique($resultado['keywords']));

        return $resultado;
    }

    public function coincideConFiltros(array $contratoData): bool
    {
        return $this->resolverCoincidenciasContrato($contratoData)['pasa'];
    }

    protected function obtenerKeywordsNormalizados(): Collection
    {
        $keywords = $this->user?->subscriberProfile?->keywords ?? collect();

        return $keywords
            ->pluck('nombre')
            ->filter()
            ->map(fn ($keyword) => $this->normalizarTexto($keyword))
            ->filter()
            ->values();
    }

    protected function buildContratoHaystack(array $contratoData): string
    {
        $fragmentos = array_filter([
            $contratoData['nomEntidad'] ?? '',
            $contratoData['desContratacion'] ?? '',
            $contratoData['desObjetoContrato'] ?? '',
            $contratoData['nomObjetoContrato'] ?? '',
        ]);

        if (empty($fragmentos)) {
            return '';
        }

        return $this->normalizarTexto(implode(' ', $fragmentos));
    }

    protected function normalizarTexto(?string $valor): string
    {
        if ($valor === null) {
            return '';
        }

        return Str::of($valor)
            ->lower()
            ->ascii()
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->value();
    }

    protected function pasaFiltrosBasicos(array $contratoData): bool
    {
        return true;
    }
}
