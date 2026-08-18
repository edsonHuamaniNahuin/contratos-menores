<?php

namespace App\Livewire;

use App\Models\ContratoMayor;
use App\Models\SystemSetting;
use App\Models\VigilanciaAdjudicacion;
use App\Services\SeaceMayoresService;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Bandeja de vigilancia de adjudicaciones (procesos >= umbral, default S/ 1M).
 *
 * Muestra TODOS los procesos vigilados (relación por ocid a contratos_mayores,
 * sin duplicar información) con filtros que atacan ÚNICAMENTE a ese universo.
 *
 * Debajo, la configuración administrativa: destinatarios de alertas, umbral
 * y ejecución manual del job.
 */
class VigilanciaAdjudicacionesAdmin extends Component
{
    use WithPagination;

    // ── Filtros de la bandeja ─────────────────────────────────────
    public string $palabraClave = '';
    public string $estado = '';
    public int $departamentoId = 0;
    public float $montoMin = 0;
    public float $montoMax = 0;
    public string $fechaDesde = '';
    public string $fechaHasta = '';
    public string $estadoVigilancia = 'todos'; // todos | pendientes | notificados
    public int $registrosPorPagina = 15;

    public array $estadosDisponibles = [];
    public array $departamentosDisponibles = [];

    // ── Config admin ──────────────────────────────────────────────
    public string $umbral = '1000000';
    public array $stats = [
        'vigilados' => 0,
        'pendientes' => 0,
        'notificados' => 0,
    ];

    protected SeaceMayoresService $seace;

    public function boot(SeaceMayoresService $seace): void
    {
        $this->seace = $seace;
    }

    public function mount(): void
    {
        $this->cargarCatalogos();
        $this->cargarConfig();
    }

    protected function cargarCatalogos(): void
    {
        $this->estadosDisponibles = Cache::remember('vigilancia:estados-disponibles', 3600, function () {
            return ContratoMayor::query()
                ->whereExists(fn ($q) => $q->select('id')->from('vigilancia_adjudicaciones')
                    ->whereColumn('vigilancia_adjudicaciones.ocid', 'contratos_mayores.ocid'))
                ->select('estado')
                ->distinct()
                ->whereNotNull('estado')
                ->where('estado', '!=', '')
                ->orderBy('estado')
                ->pluck('estado')
                ->toArray();
        });

        $geo = app(\App\Services\GeoResolverService::class);
        $this->departamentosDisponibles = $geo->departamentosParaFiltro();
    }

    protected function cargarConfig(): void
    {
        $this->umbral = (string) SystemSetting::getValue('vigilancia_monto_min', 1_000_000);

        $this->stats = [
            'vigilados' => VigilanciaAdjudicacion::count(),
            'pendientes' => VigilanciaAdjudicacion::whereNull('notificado_en')->count(),
            'notificados' => VigilanciaAdjudicacion::whereIn('estado_notificado', VigilanciaAdjudicacion::ESTADOS_BUENA_PRO)->count(),
        ];
    }

    public function updatedPalabraClave(): void
    {
        $this->resetPage();
    }

    public function updatedEstado(): void
    {
        $this->resetPage();
    }

    public function updatedDepartamentoId(): void
    {
        $this->resetPage();
    }

    public function updatedMontoMin(): void
    {
        $this->resetPage();
    }

    public function updatedMontoMax(): void
    {
        $this->resetPage();
    }

    public function updatedFechaDesde(): void
    {
        $this->resetPage();
    }

    public function updatedFechaHasta(): void
    {
        $this->resetPage();
    }

    public function updatedEstadoVigilancia(): void
    {
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->palabraClave = '';
        $this->estado = '';
        $this->departamentoId = 0;
        $this->montoMin = 0;
        $this->montoMax = 0;
        $this->fechaDesde = '';
        $this->fechaHasta = '';
        $this->estadoVigilancia = 'todos';
        $this->resetPage();
    }

    protected function notify(string $message, string $type = 'info'): void
    {
        $this->dispatch('notify', message: $message, type: $type);
    }

    public function render()
    {
        // JOIN 1:1 con vigilancia_adjudicaciones (ocid único): una sola
        // consulta trae el estado de vigilancia sin consulta adicional,
        // y el índice del JOIN evita el EXISTS por fila.
        $query = ContratoMayor::query()
            ->with(['departamento', 'provincia', 'distrito'])
            // Solo columnas necesarias para la bandeja: sin `datos_raw`
            // (JSON pesado que reventaba el sort buffer de MySQL).
            ->select([
                'contratos_mayores.id',
                'contratos_mayores.ocid',
                'contratos_mayores.entidad_nombre',
                'contratos_mayores.nomenclatura',
                'contratos_mayores.objeto_contratacion',
                'contratos_mayores.valor_referencial',
                'contratos_mayores.estado',
                'contratos_mayores.fecha_publicacion',
                'contratos_mayores.departamento_id',
                'contratos_mayores.provincia_id',
                'contratos_mayores.distrito_id',
                'vigilancia_adjudicaciones.notificado_en',
                'vigilancia_adjudicaciones.estado_notificado',
            ])
            ->join('vigilancia_adjudicaciones', 'vigilancia_adjudicaciones.ocid', '=', 'contratos_mayores.ocid');

        // Filtro por estado de la vigilancia (pendiente / resuelta)
        if ($this->estadoVigilancia === 'pendientes') {
            $query->whereNull('vigilancia_adjudicaciones.notificado_en');
        } elseif ($this->estadoVigilancia === 'notificados') {
            $query->whereNotNull('vigilancia_adjudicaciones.notificado_en');
        }

        // Filtros del buscador reutilizados (atacan solo al universo vigilado)
        $this->seace->aplicarFiltros($query, [
            'query' => $this->palabraClave,
            'entidad' => '',
            'objeto' => '',
            'estado' => $this->estado,
            'departamento_id' => $this->departamentoId,
            'provincia_id' => 0,
            'distrito_id' => 0,
            'fecha_desde' => $this->fechaDesde,
            'fecha_hasta' => $this->fechaHasta,
            'monto_min' => $this->montoMin,
            'monto_max' => $this->montoMax,
        ]);

        $procesos = $query->orderBy('contratos_mayores.fecha_publicacion', 'desc')
            ->paginate($this->registrosPorPagina);

        return view('livewire.vigilancia-adjudicaciones-admin', [
            'procesos' => $procesos,
        ]);
    }
}
