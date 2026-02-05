<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Contrato;
use Carbon\Carbon;

class ContratosDashboard extends Component
{
    use WithPagination;

    // Filtros
    public $busqueda = '';
    public $filtroEstado = '';
    public $filtroObjeto = '';
    public $ordenar = 'fecha_publicacion';
    public $direccion = 'desc';

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

        // Ordenamiento
        $query->orderBy($this->ordenar, $this->direccion);

        return $query->paginate(20);
    }

    public function getEstadisticasProperty()
    {
        return [
            'total' => Contrato::count(),
            'vigentes' => Contrato::where('id_estado_contrato', 2)->count(),
            'en_evaluacion' => Contrato::where('id_estado_contrato', 3)->count(),
            'por_vencer' => Contrato::where('fin_cotizacion', '<=', Carbon::now()->addDays(3))
                ->where('fin_cotizacion', '>=', Carbon::now())
                ->count(),
        ];
    }

    public function render()
    {
        return view('livewire.contratos-dashboard', [
            'contratos' => $this->contratos,
            'estadisticas' => $this->estadisticas,
        ]);
    }
}
