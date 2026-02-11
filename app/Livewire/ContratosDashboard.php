<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Contrato;
use App\Services\Contratos\ContratoAnalyticsService;
use App\Services\SeaceBuscadorPublicoService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class ContratosDashboard extends Component
{
    use WithPagination;

    protected ContratoAnalyticsService $analyticsService;
    protected SeaceBuscadorPublicoService $buscadorPublico;

    // Filtros
    public $busqueda = '';
    public $filtroEstado = '';
    public $filtroObjeto = '';
    public $filtroDepartamento = null;
    public ?string $chartFechaDesde = null;
    public ?string $chartFechaHasta = null;
    public $ordenar = 'fecha_publicacion';
    public $direccion = 'desc';

    // Catálogos
    public array $departamentos = [];

    // Carga dirigida por departamento/fecha
    public string|int|null $departamentoSeleccionado = null;
    public ?string $fechaFiltro = null;
    public ?string $mensajeCarga = null;
    public array $resumenCarga = [];

    // Estados disponibles
    public $estados = [
        '' => 'Todos',
        '2' => 'Vigente',
        '3' => 'En Evaluación',
        '4' => 'Culminado',
    ];

    // Objetos disponibles
    public $objetos = [
        '' => 'Todos',
        '1' => 'Bien',
        '2' => 'Servicio',
        '3' => 'Obra',
        '4' => 'Consultoría de Obra',
    ];

    protected $queryString = [
        'busqueda' => ['except' => ''],
        'filtroEstado' => ['except' => ''],
        'filtroObjeto' => ['except' => ''],
        'ordenar' => ['except' => 'fecha_publicacion'],
    ];

    public function boot(ContratoAnalyticsService $analyticsService, SeaceBuscadorPublicoService $buscadorPublico)
    {
        $this->analyticsService = $analyticsService;
        $this->buscadorPublico = $buscadorPublico;
    }

    public function mount(): void
    {
        $this->fechaFiltro = now()->toDateString();
        $this->chartFechaHasta = now()->toDateString();
        $this->chartFechaDesde = now()->copy()->subMonths(5)->startOfMonth()->toDateString();

        $departamentos = $this->buscadorPublico->obtenerDepartamentos();
        if (!empty($departamentos['success']) && isset($departamentos['data'])) {
            $this->departamentos = $departamentos['data'];
        }
    }

    public function updatingBusqueda()
    {
        $this->resetPage();
    }

    public function updatingFiltroEstado()
    {
        $this->resetPage();
    }

    public function updatingFiltroObjeto()
    {
        $this->resetPage();
    }

    public function limpiarFiltros()
    {
        $this->busqueda = '';
        $this->filtroEstado = '';
        $this->filtroObjeto = '';
        $this->ordenar = 'fecha_publicacion';
        $this->direccion = 'desc';
        $this->resetPage();
    }

    public function ordenarPor($campo)
    {
        if ($this->ordenar === $campo) {
            $this->direccion = $this->direccion === 'asc' ? 'desc' : 'asc';
        } else {
            $this->ordenar = $campo;
            $this->direccion = 'desc';
        }
    }

    public function getContratosProperty()
    {
        $query = Contrato::query();

        // Filtro de búsqueda
        if ($this->busqueda) {
            $query->where(function ($q) {
                $q->where('codigo_proceso', 'like', '%' . $this->busqueda . '%')
                    ->orWhere('entidad', 'like', '%' . $this->busqueda . '%')
                    ->orWhere('descripcion', 'like', '%' . $this->busqueda . '%');
            });
        }

        // Filtro de estado
        if ($this->filtroEstado) {
            $query->where('id_estado_contrato', $this->filtroEstado);
        }

        // Filtro de objeto
        if ($this->filtroObjeto) {
            $query->where('id_objeto_contrato', $this->filtroObjeto);
        }

        if ($this->filtroDepartamento) {
            $query->where('codigo_departamento', $this->filtroDepartamento);
        }

        // Ordenamiento
        $query->orderBy($this->ordenar, $this->direccion);

        return $query->paginate(20);
    }

    public function getEstadisticasProperty()
    {
        return $this->analyticsService->counters($this->filtroDepartamento ? (int) $this->filtroDepartamento : null);
    }

    public function getChartDataProperty()
    {
        $departamento = $this->filtroDepartamento ? (int) $this->filtroDepartamento : null;
        [$desde, $hasta] = $this->chartDateRange();

        return [
            'filtro_departamento' => $departamento,
            'departamento_nombre' => $this->nombreDepartamentoPorCodigo($departamento),
            'fecha_desde' => $this->chartFechaDesde,
            'fecha_hasta' => $this->chartFechaHasta,
            'por_estado' => $this->analyticsService->distribucionPorEstado($departamento, $desde, $hasta),
            'por_objeto' => $this->analyticsService->distribucionPorObjeto($departamento, $desde, $hasta),
            'por_mes' => $this->analyticsService->publicacionesPorMes(6, $departamento, $desde, $hasta),
            'top_entidades' => $this->analyticsService->topEntidades(8, $departamento, $desde, $hasta),
            'por_departamento' => $this->analyticsService->distribucionPorDepartamento(10, $departamento, $desde, $hasta),
        ];
    }

    public function actualizarFiltroDepartamento($codigo): void
    {
        $this->filtroDepartamento = $codigo ?: null;
        $this->departamentoSeleccionado = $this->filtroDepartamento;
    }

    public function importarPorDepartamento(): void
    {
        if (Gate::denies('import-tdr')) {
            abort(403);
        }

        $this->mensajeCarga = null;
        $this->resumenCarga = [];

        if (!$this->fechaFiltro) {
            $this->mensajeCarga = 'Selecciona una fecha de publicación.';
            return;
        }

        try {
            $fecha = Carbon::createFromFormat('Y-m-d', $this->fechaFiltro);
        } catch (\Exception $e) {
            $this->mensajeCarga = 'Fecha inválida. Usa el formato YYYY-MM-DD.';
            return;
        }

        $objetivoDepartamentos = $this->departamentos;

        if ($this->departamentoSeleccionado && $this->departamentoSeleccionado !== 'all' && $this->departamentoSeleccionado !== 0 && $this->departamentoSeleccionado !== '0') {
            $seleccion = collect($this->departamentos)->firstWhere('id', (int) $this->departamentoSeleccionado);
            if (!$seleccion) {
                $this->mensajeCarga = 'Departamento inválido.';
                return;
            }
            $objetivoDepartamentos = [$seleccion];
        }

        $totalRecibidos = 0;
        $totalFiltrados = 0;
        $nuevos = 0;
        $actualizados = 0;

        foreach ($objetivoDepartamentos as $dep) {
            $params = [
                'anio' => (int) $fecha->year,
                'codigo_departamento' => $dep['id'],
                'page' => 1,
                'page_size' => 150,
                'orden' => 2,
            ];

            $respuesta = $this->buscadorPublico->buscarContratos($params);

            if (empty($respuesta['success'])) {
                Log::warning('Importar departamento falló', ['dep' => $dep['id'], 'error' => $respuesta['error'] ?? '']);
                continue;
            }

            $datos = collect($respuesta['data'] ?? []);
            $totalRecibidos += $datos->count();

            $datosFiltrados = $datos->filter(function ($item) use ($fecha) {
                $fec = $item['fecPublica'] ?? null;
                if (!$fec) {
                    return false;
                }

                try {
                    $parsed = Carbon::createFromFormat('d/m/Y H:i:s', $fec);
                    return $parsed->isSameDay($fecha);
                } catch (\Exception $e) {
                    return false;
                }
            });

            $totalFiltrados += $datosFiltrados->count();

            foreach ($datosFiltrados as $data) {
                $fechaPublicacion = $this->parseSeaceDate($data['fecPublica'] ?? null);
                $inicioCotizacion = $this->parseSeaceDate($data['fecIniCotizacion'] ?? null);
                $finCotizacion = $this->parseSeaceDate($data['fecFinCotizacion'] ?? null);

                $contrato = Contrato::updateOrCreate(
                    ['id_contrato_seace' => $data['idContrato']],
                    [
                        'nro_contratacion' => $data['nroContratacion'] ?? 0,
                        'codigo_proceso' => $data['desContratacion'] ?? 'N/A',
                        'entidad' => $data['nomEntidad'] ?? 'N/A',
                        'codigo_departamento' => $dep['id'],
                        'nombre_departamento' => $dep['nom'] ?? null,
                        'codigo_provincia' => $data['codigoProvincia'] ?? null,
                        'nombre_provincia' => $data['nomProvincia'] ?? null,
                        'id_objeto_contrato' => $data['idObjetoContrato'] ?? 0,
                        'objeto' => $data['nomObjetoContrato'] ?? 'N/A',
                        'descripcion' => $data['desObjetoContrato'] ?? 'Sin descripción',
                        'id_estado_contrato' => $data['idEstadoContrato'] ?? 0,
                        'estado' => $data['nomEstadoContrato'] ?? 'Desconocido',
                        'fecha_publicacion' => $fechaPublicacion,
                        'inicio_cotizacion' => $inicioCotizacion,
                        'fin_cotizacion' => $finCotizacion,
                        'etapa_contratacion' => $data['nomEtapaContratacion'] ?? null,
                        'id_tipo_cotizacion' => $data['idTipoCotizacion'] ?? null,
                        'num_subsanaciones_total' => $data['numSubsanacionesTotal'] ?? 0,
                        'num_subsanaciones_pendientes' => $data['numSubsanacionesPendientes'] ?? 0,
                        'datos_raw' => $data,
                    ]
                );

                $contrato->wasRecentlyCreated ? $nuevos++ : $actualizados++;
            }
        }

        $this->resumenCarga = [
            'total_recibidos' => $totalRecibidos,
            'filtrados_fecha' => $totalFiltrados,
            'nuevos' => $nuevos,
            'actualizados' => $actualizados,
        ];

        $this->mensajeCarga = 'Importación completada';
    }

    protected function parseSeaceDate(?string $dateString): ?Carbon
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('d/m/Y H:i:s', $dateString);
        } catch (\Exception $e) {
            Log::warning('ContratosDashboard: Fecha inválida', ['date' => $dateString]);
            return null;
        }
    }

    protected function nombreDepartamentoActual(): ?string
    {
        if (!$this->departamentoSeleccionado || $this->departamentoSeleccionado === 'all') {
            return null;
        }

        $match = collect($this->departamentos)->firstWhere('id', (int) $this->departamentoSeleccionado);
        return $match['nom'] ?? null;
    }

    protected function nombreDepartamentoPorCodigo(?int $codigo): ?string
    {
        if (!$codigo) {
            return null;
        }

        $match = collect($this->departamentos)->firstWhere('id', $codigo);
        return $match['nom'] ?? null;
    }

    protected function chartDateRange(): array
    {
        try {
            $desde = $this->chartFechaDesde ? Carbon::createFromFormat('Y-m-d', $this->chartFechaDesde)->startOfDay() : null;
            $hasta = $this->chartFechaHasta ? Carbon::createFromFormat('Y-m-d', $this->chartFechaHasta)->endOfDay() : null;

            if ($desde && $hasta && $desde->gt($hasta)) {
                return [$hasta, $hasta];
            }

            return [$desde, $hasta];
        } catch (\Exception $e) {
            return [null, null];
        }
    }

    public function render()
    {
        return view('livewire.contratos-dashboard', [
            'contratos' => $this->contratos,
            'estadisticas' => $this->estadisticas,
            'chartData' => $this->chartData,
        ]);
    }
}
