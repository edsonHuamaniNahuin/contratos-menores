<?php

namespace App\Services\Contratos;

use App\Models\Contrato;
use App\Models\TdrAnalisis;
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

    // ── TDR Direccionamiento Analytics ─────────────────────────────

    /**
     * Construye query base de TDR con filtros opcionales por contrato (departamento, entidad, fecha).
     */
    private function tdrBaseQuery(?int $codigoDepartamento = null, ?string $entidad = null, ?Carbon $desde = null, ?Carbon $hasta = null)
    {
        $query = TdrAnalisis::where('tdr_analisis.tipo_analisis', TdrAnalisis::TIPO_DIRECCIONAMIENTO)
            ->where('tdr_analisis.estado', TdrAnalisis::ESTADO_EXITOSO);

        if ($codigoDepartamento || $entidad || $desde || $hasta) {
            $query->join('contrato_archivos', 'tdr_analisis.contrato_archivo_id', '=', 'contrato_archivos.id')
                  ->join('contratos', 'contrato_archivos.contrato_id', '=', 'contratos.id');

            if ($codigoDepartamento) {
                $query->where('contratos.codigo_departamento', $codigoDepartamento);
            }
            if ($entidad) {
                $query->where('contratos.entidad', 'like', '%' . $entidad . '%');
            }
            if ($desde) {
                $query->where('contratos.fecha_publicacion', '>=', $desde);
            }
            if ($hasta) {
                $query->where('contratos.fecha_publicacion', '<=', $hasta);
            }

            $query->select('tdr_analisis.*');
        }

        return $query;
    }

    /**
     * Contadores generales de análisis de direccionamiento.
     */
    public function tdrCounters(?int $codigoDepartamento = null, ?string $entidad = null, ?Carbon $desde = null, ?Carbon $hasta = null): array
    {
        $base = $this->tdrBaseQuery($codigoDepartamento, $entidad, $desde, $hasta);

        $total = (clone $base)->count();

        $analisis = (clone $base)->get(['tdr_analisis.resumen']);

        $veredictos = $analisis->countBy(fn ($a) => $a->resumen['veredicto_flash'] ?? 'DESCONOCIDO');

        return [
            'total' => $total,
            'limpio' => $veredictos->get('LIMPIO', 0),
            'sospechoso' => $veredictos->get('SOSPECHOSO', 0),
            'direccionado' => $veredictos->get('ALTAMENTE DIRECCIONADO', 0),
            'score_promedio' => $total > 0
                ? round($analisis->avg(fn ($a) => $a->resumen['score_riesgo_corrupcion'] ?? 0))
                : 0,
        ];
    }

    /**
     * Distribución de veredictos (LIMPIO / SOSPECHOSO / ALTAMENTE DIRECCIONADO).
     */
    public function tdrVeredictos(?int $codigoDepartamento = null, ?string $entidad = null, ?Carbon $desde = null, ?Carbon $hasta = null): array
    {
        $analisis = $this->tdrBaseQuery($codigoDepartamento, $entidad, $desde, $hasta)
            ->get(['tdr_analisis.resumen']);

        $veredictos = $analisis->countBy(fn ($a) => $a->resumen['veredicto_flash'] ?? 'DESCONOCIDO')
            ->sortDesc();

        return [
            'labels' => $veredictos->keys()->values(),
            'values' => $veredictos->values()->values(),
        ];
    }

    /**
     * Distribución de scores por rangos (0-20, 21-40, 41-60, 61-80, 81-100).
     */
    public function tdrScoreRanges(?int $codigoDepartamento = null, ?string $entidad = null, ?Carbon $desde = null, ?Carbon $hasta = null): array
    {
        $analisis = $this->tdrBaseQuery($codigoDepartamento, $entidad, $desde, $hasta)
            ->get(['tdr_analisis.resumen']);

        $rangos = [
            '0-20' => 0,
            '21-40' => 0,
            '41-60' => 0,
            '61-80' => 0,
            '81-100' => 0,
        ];

        foreach ($analisis as $a) {
            $score = $a->resumen['score_riesgo_corrupcion'] ?? 0;
            match (true) {
                $score <= 20 => $rangos['0-20']++,
                $score <= 40 => $rangos['21-40']++,
                $score <= 60 => $rangos['41-60']++,
                $score <= 80 => $rangos['61-80']++,
                default => $rangos['81-100']++,
            };
        }

        return [
            'labels' => array_keys($rangos),
            'values' => array_values($rangos),
        ];
    }

    /**
     * Hallazgos por categoría (Técnica, Experiencia, Personal, etc.).
     */
    public function tdrHallazgosPorCategoria(?int $codigoDepartamento = null, ?string $entidad = null, ?Carbon $desde = null, ?Carbon $hasta = null): array
    {
        $analisis = $this->tdrBaseQuery($codigoDepartamento, $entidad, $desde, $hasta)
            ->get(['tdr_analisis.resumen']);

        $categorias = collect();
        foreach ($analisis as $a) {
            $hallazgos = $a->resumen['hallazgos_criticos'] ?? [];
            foreach ($hallazgos as $h) {
                $cat = $h['categoria'] ?? 'Otra';
                $categorias->push($cat);
            }
        }

        $conteo = $categorias->countBy()->sortDesc();

        return [
            'labels' => $conteo->keys()->values(),
            'values' => $conteo->values()->values(),
        ];
    }

    /**
     * Hallazgos por nivel de gravedad (Alto, Medio, Bajo).
     */
    public function tdrGravedadHallazgos(?int $codigoDepartamento = null, ?string $entidad = null, ?Carbon $desde = null, ?Carbon $hasta = null): array
    {
        $analisis = $this->tdrBaseQuery($codigoDepartamento, $entidad, $desde, $hasta)
            ->get(['tdr_analisis.resumen']);

        $niveles = collect();
        foreach ($analisis as $a) {
            $hallazgos = $a->resumen['hallazgos_criticos'] ?? [];
            foreach ($hallazgos as $h) {
                $niveles->push($h['nivel_de_gravedad'] ?? 'Bajo');
            }
        }

        $orden = ['Alto' => 0, 'Medio' => 0, 'Bajo' => 0];
        foreach ($niveles->countBy() as $nivel => $count) {
            $orden[$nivel] = $count;
        }

        return [
            'labels' => array_keys($orden),
            'values' => array_values($orden),
        ];
    }

    /**
     * Score promedio por mes (últimos N meses).
     */
    public function tdrScorePorMes(int $meses = 6, ?int $codigoDepartamento = null, ?string $entidad = null, ?Carbon $desde = null, ?Carbon $hasta = null): array
    {
        $inicio = $desde ? $desde->copy()->startOfMonth() : Carbon::now()->startOfMonth()->subMonths($meses - 1);

        $query = $this->tdrBaseQuery($codigoDepartamento, $entidad, null, null)
            ->where('tdr_analisis.analizado_en', '>=', $inicio);

        if ($hasta) {
            $query->where('tdr_analisis.analizado_en', '<=', $hasta);
        }

        $analisis = $query->get(['tdr_analisis.resumen', 'tdr_analisis.analizado_en']);

        $porMes = $analisis->groupBy(fn ($a) => $a->analizado_en->format('Y-m'));

        $labels = [];
        $valuesScore = [];
        $valuesCount = [];

        $fin = $hasta ? $hasta->copy()->endOfMonth() : Carbon::now()->endOfMonth();
        $mesesCalculados = $inicio->diffInMonths($fin) + 1;
        if ($mesesCalculados < 1) {
            $mesesCalculados = $meses;
        }

        for ($i = 0; $i < $mesesCalculados; $i++) {
            $mes = $inicio->copy()->addMonths($i);
            $key = $mes->format('Y-m');
            $labels[] = $mes->translatedFormat('M y');

            $grupo = $porMes->get($key, collect());
            $valuesScore[] = $grupo->isNotEmpty()
                ? round($grupo->avg(fn ($a) => $a->resumen['score_riesgo_corrupcion'] ?? 0))
                : 0;
            $valuesCount[] = $grupo->count();
        }

        return [
            'labels' => $labels,
            'scores' => $valuesScore,
            'counts' => $valuesCount,
        ];
    }

    /**
     * Top entidades con más análisis de direccionamiento.
     */
    public function tdrTopEntidades(int $limite = 10, ?int $codigoDepartamento = null, ?string $entidad = null, ?Carbon $desde = null, ?Carbon $hasta = null): array
    {
        $query = TdrAnalisis::where('tdr_analisis.tipo_analisis', TdrAnalisis::TIPO_DIRECCIONAMIENTO)
            ->where('tdr_analisis.estado', TdrAnalisis::ESTADO_EXITOSO)
            ->join('contrato_archivos', 'tdr_analisis.contrato_archivo_id', '=', 'contrato_archivos.id')
            ->join('contratos', 'contrato_archivos.contrato_id', '=', 'contratos.id');

        if ($codigoDepartamento) {
            $query->where('contratos.codigo_departamento', $codigoDepartamento);
        }
        if ($entidad) {
            $query->where('contratos.entidad', 'like', '%' . $entidad . '%');
        }
        if ($desde) {
            $query->where('contratos.fecha_publicacion', '>=', $desde);
        }
        if ($hasta) {
            $query->where('contratos.fecha_publicacion', '<=', $hasta);
        }

        $entidades = $query->selectRaw('contratos.entidad, COUNT(*) as total')
            ->groupBy('contratos.entidad')
            ->orderByDesc('total')
            ->limit($limite)
            ->get();

        return [
            'labels' => $entidades->pluck('entidad'),
            'values' => $entidades->pluck('total'),
        ];
    }
}
