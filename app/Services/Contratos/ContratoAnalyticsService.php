<?php

namespace App\Services\Contratos;

use App\Models\Contrato;
use Carbon\Carbon;

class ContratoAnalyticsService
{
    /**
     * Aplica filtros de fecha de manera segura al query builder.
     */
    private function applyDateRange($query, ?Carbon $desde, ?Carbon $hasta): void
    {
        if ($desde) {
            $query->where('fecha_publicacion', '>=', $desde);
        }

        if ($hasta) {
            $query->where('fecha_publicacion', '<=', $hasta);
        }
    }

    public function counters(?int $codigoDepartamento = null, ?Carbon $desde = null, ?Carbon $hasta = null): array
    {
        $base = Contrato::query();

        if ($codigoDepartamento) {
            $base->where('codigo_departamento', $codigoDepartamento);
        }

        $this->applyDateRange($base, $desde, $hasta);

        return [
            'total' => (clone $base)->count(),
            'vigentes' => (clone $base)->where('id_estado_contrato', 2)->count(),
            'en_evaluacion' => (clone $base)->where('id_estado_contrato', 3)->count(),
            'por_vencer' => (clone $base)->proximosAVencer(3)->count(),
        ];
    }

    public function distribucionPorEstado(?int $codigoDepartamento = null, ?Carbon $desde = null, ?Carbon $hasta = null): array
    {
        $estados = Contrato::select('id_estado_contrato', 'estado')
            ->selectRaw('COUNT(*) as total')
            ->when($codigoDepartamento, fn ($q) => $q->where('codigo_departamento', $codigoDepartamento))
            ->tap(fn ($q) => $this->applyDateRange($q, $desde, $hasta))
            ->groupBy('id_estado_contrato', 'estado')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $estados->pluck('estado'),
            'values' => $estados->pluck('total'),
        ];
    }

    public function distribucionPorObjeto(?int $codigoDepartamento = null, ?Carbon $desde = null, ?Carbon $hasta = null): array
    {
        $objetos = Contrato::select('id_objeto_contrato', 'objeto')
            ->selectRaw('COUNT(*) as total')
            ->when($codigoDepartamento, fn ($q) => $q->where('codigo_departamento', $codigoDepartamento))
            ->tap(fn ($q) => $this->applyDateRange($q, $desde, $hasta))
            ->groupBy('id_objeto_contrato', 'objeto')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $objetos->pluck('objeto'),
            'values' => $objetos->pluck('total'),
        ];
    }

    public function publicacionesPorMes(int $meses = 6, ?int $codigoDepartamento = null, ?Carbon $desde = null, ?Carbon $hasta = null): array
    {
        $inicio = $desde ? $desde->copy()->startOfMonth() : Carbon::now()->startOfMonth()->subMonths($meses - 1);
        $fin = $hasta ? $hasta->copy()->endOfMonth() : Carbon::now()->endOfMonth();

        $mesesCalculados = $inicio->diffInMonths($fin) + 1;
        if ($mesesCalculados < 1) {
            $mesesCalculados = $meses;
        }

        $registros = Contrato::selectRaw("DATE_FORMAT(fecha_publicacion, '%Y-%m') as mes")
            ->selectRaw('COUNT(*) as total')
            ->when($codigoDepartamento, fn ($q) => $q->where('codigo_departamento', $codigoDepartamento))
            ->whereBetween('fecha_publicacion', [$inicio, $fin])
            ->groupBy('mes')
            ->orderBy('mes')
            ->get()
            ->keyBy('mes');

        $labels = [];
        $values = [];

        for ($i = 0; $i < $mesesCalculados; $i++) {
            $mes = $inicio->copy()->addMonths($i);
            $key = $mes->format('Y-m');
            $labels[] = $mes->translatedFormat('M y');
            $values[] = (int) ($registros[$key]->total ?? 0);
        }

        return compact('labels', 'values');
    }

    public function topEntidades(int $limite = 8, ?int $codigoDepartamento = null, ?Carbon $desde = null, ?Carbon $hasta = null): array
    {
        $entidades = Contrato::select('entidad')
            ->selectRaw('COUNT(*) as total')
            ->when($codigoDepartamento, fn ($q) => $q->where('codigo_departamento', $codigoDepartamento))
            ->tap(fn ($q) => $this->applyDateRange($q, $desde, $hasta))
            ->groupBy('entidad')
            ->orderByDesc('total')
            ->limit($limite)
            ->get();

        return [
            'labels' => $entidades->pluck('entidad'),
            'values' => $entidades->pluck('total'),
        ];
    }

    public function distribucionPorDepartamento(int $limite = 10, ?int $codigoDepartamento = null, ?Carbon $desde = null, ?Carbon $hasta = null): array
    {
        $departamentos = Contrato::selectRaw(
                'COALESCE(' .
                'nombre_departamento,' .
                'JSON_UNQUOTE(JSON_EXTRACT(datos_raw, "$.nomDepartamento")),' .
                'JSON_UNQUOTE(JSON_EXTRACT(datos_raw, "$.departamento")),' .
                'JSON_UNQUOTE(JSON_EXTRACT(datos_raw, "$.ubicacion.departamento"))' .
                ') as departamento'
            )
            ->selectRaw('COUNT(*) as total')
            ->when($codigoDepartamento, fn ($q) => $q->where('codigo_departamento', $codigoDepartamento))
            ->tap(fn ($q) => $this->applyDateRange($q, $desde, $hasta))
            ->groupBy('departamento')
            ->orderByDesc('total')
            ->limit($limite)
            ->get();

        return [
            'labels' => $departamentos->pluck('departamento')->map(fn ($d) => $d ?: 'Sin departamento'),
            'values' => $departamentos->pluck('total'),
        ];
    }
}
