<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\Contratos\ContratoAnalyticsService;
use App\Services\SeaceBuscadorPublicoService;
use Carbon\Carbon;

class DireccionamientoDashboard extends Component
{
    protected ContratoAnalyticsService $analyticsService;
    protected SeaceBuscadorPublicoService $buscadorPublico;

    // Filtros
    public $filtroDepartamento = null;
    public ?string $filtroEntidad = null;
    public ?string $fechaDesde = null;
    public ?string $fechaHasta = null;

    // Catálogos
    public array $departamentos = [];

    protected $queryString = [
        'filtroDepartamento' => ['except' => null, 'as' => 'dep'],
        'filtroEntidad' => ['except' => null, 'as' => 'ent'],
        'fechaDesde' => ['except' => null, 'as' => 'desde'],
        'fechaHasta' => ['except' => null, 'as' => 'hasta'],
    ];

    public function boot(ContratoAnalyticsService $analyticsService, SeaceBuscadorPublicoService $buscadorPublico)
    {
        $this->analyticsService = $analyticsService;
        $this->buscadorPublico = $buscadorPublico;
    }

    public function mount(): void
    {
        $this->fechaHasta = now()->toDateString();
        $this->fechaDesde = now()->copy()->subMonths(5)->startOfMonth()->toDateString();

        $departamentos = $this->buscadorPublico->obtenerDepartamentos();
        if (!empty($departamentos['success']) && isset($departamentos['data'])) {
            $this->departamentos = $departamentos['data'];
        }
    }

    public function updatedFechaHasta($value): void
    {
        $hoy = now()->toDateString();
        if ($value && $value > $hoy) {
            $this->fechaHasta = $hoy;
        }
        if ($value && $this->fechaDesde && $value < $this->fechaDesde) {
            $this->fechaHasta = $this->fechaDesde;
        }
    }

    public function updatedFechaDesde($value): void
    {
        if ($value && $this->fechaHasta && $value > $this->fechaHasta) {
            $this->fechaDesde = $this->fechaHasta;
        }
    }

    public function limpiarFiltros(): void
    {
        $this->filtroDepartamento = null;
        $this->filtroEntidad = null;
        $this->fechaHasta = now()->toDateString();
        $this->fechaDesde = now()->copy()->subMonths(5)->startOfMonth()->toDateString();
    }

    protected function filterParams(): array
    {
        $departamento = $this->filtroDepartamento ? (int) $this->filtroDepartamento : null;
        $entidad = $this->filtroEntidad ?: null;

        try {
            $desde = $this->fechaDesde ? Carbon::createFromFormat('Y-m-d', $this->fechaDesde)->startOfDay() : null;
            $hasta = $this->fechaHasta ? Carbon::createFromFormat('Y-m-d', $this->fechaHasta)->endOfDay() : null;
            if ($desde && $hasta && $desde->gt($hasta)) {
                $hasta = $desde->copy()->endOfDay();
            }
        } catch (\Exception $e) {
            $desde = null;
            $hasta = null;
        }

        return [$departamento, $entidad, $desde, $hasta];
    }

    public function getCountersProperty(): array
    {
        return $this->analyticsService->tdrCounters(...$this->filterParams());
    }

    public function getChartDataProperty(): array
    {
        [$dep, $ent, $desde, $hasta] = $this->filterParams();

        return [
            'tdr_veredictos' => $this->analyticsService->tdrVeredictos($dep, $ent, $desde, $hasta),
            'tdr_score_ranges' => $this->analyticsService->tdrScoreRanges($dep, $ent, $desde, $hasta),
            'tdr_hallazgos_categoria' => $this->analyticsService->tdrHallazgosPorCategoria($dep, $ent, $desde, $hasta),
            'tdr_gravedad' => $this->analyticsService->tdrGravedadHallazgos($dep, $ent, $desde, $hasta),
            'tdr_score_mes' => $this->analyticsService->tdrScorePorMes(6, $dep, $ent, $desde, $hasta),
            'tdr_top_entidades' => $this->analyticsService->tdrTopEntidades(10, $dep, $ent, $desde, $hasta),
        ];
    }

    protected function nombreDepartamento(): ?string
    {
        if (!$this->filtroDepartamento) {
            return null;
        }
        $match = collect($this->departamentos)->firstWhere('id', (int) $this->filtroDepartamento);
        return $match['nom'] ?? null;
    }

    public function render()
    {
        return view('livewire.direccionamiento-dashboard', [
            'counters' => $this->counters,
            'chartData' => $this->chartData,
            'nombreDepartamento' => $this->nombreDepartamento(),
        ]);
    }
}
