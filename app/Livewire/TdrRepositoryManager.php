<?php

namespace App\Livewire;

use App\Models\ContratoArchivo;
use App\Models\CuentaSeace;
use App\Services\Tdr\TdrDocumentService;
use App\Services\Tdr\TdrPersistenceService;
use App\Services\TdrAnalysisService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;
use Exception;

class TdrRepositoryManager extends Component
{
    use WithPagination;

    public string $busqueda = '';
    public string $estadoAnalisis = '';
    public bool $soloConAnalisis = false;
    public int $perPage = 10;

    protected $queryString = [
        'busqueda' => ['except' => ''],
        'estadoAnalisis' => ['except' => ''],
        'soloConAnalisis' => ['except' => false],
    ];

    protected $listeners = ['refreshList' => '$refresh'];

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function updatingEstadoAnalisis(): void
    {
        $this->resetPage();
    }

    public function updatingSoloConAnalisis(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function descargar(int $archivoId): void
    {
        try {
            $archivo = ContratoArchivo::findOrFail($archivoId);

            if (!$archivo->hasStoredFile()) {
                $cuenta = CuentaSeace::activa()->first();

                if (!$cuenta) {
                    $this->addError('tdrRepo', 'No hay cuenta SEACE activa para completar la descarga.');
                    return;
                }

                $persistence = new TdrPersistenceService();
                $documentService = new TdrDocumentService($persistence);
                $documentService->ensureLocalFile($archivo, $cuenta, $archivo->nombre_original);
            }

            if (!$archivo->hasStoredFile()) {
                $this->addError('tdrRepo', 'El archivo no está disponible en el repositorio local.');
                return;
            }

            $this->dispatch('descargar-archivo', url: route('tdr.archivos.download', $archivo));
        } catch (Exception $e) {
            Log::error('Error descargando desde el repositorio TDR', [
                'archivo_id' => $archivoId,
                'error' => $e->getMessage(),
            ]);

            $this->addError('tdrRepo', 'Error al preparar la descarga: ' . $e->getMessage());
        }
    }

    public function reanalizar(int $archivoId): void
    {
        try {
            $archivo = ContratoArchivo::findOrFail($archivoId);
            $cuenta = CuentaSeace::activa()->first();

            if (!$cuenta) {
                $this->addError('tdrRepo', 'No hay cuenta SEACE activa para reanalizar.');
                return;
            }

            $servicio = new TdrAnalysisService();
            $resultado = $servicio->analizarDesdeSeace(
                $archivo->id_archivo_seace,
                $archivo->nombre_original,
                $cuenta,
                $archivo->datos_contrato ?? null,
                'dashboard',
                true
            );

            if ($resultado['success']) {
                session()->flash('tdr_repo_success', 'Análisis actualizado correctamente.');
                $this->dispatch('refreshList');
            } else {
                $this->addError('tdrRepo', $resultado['error'] ?? 'Error desconocido al reanalizar.');
            }
        } catch (Exception $e) {
            Log::error('Error reanalizando archivo TDR', [
                'archivo_id' => $archivoId,
                'error' => $e->getMessage(),
            ]);

            $this->addError('tdrRepo', 'Error al reanalizar: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $query = ContratoArchivo::query()
            ->with(['ultimoAnalisisExitoso'])
            ->when($this->busqueda !== '', function ($builder) {
                $busqueda = '%' . $this->busqueda . '%';
                $builder->where(function ($sub) use ($busqueda) {
                    $sub->where('nombre_original', 'like', $busqueda)
                        ->orWhere('codigo_proceso', 'like', $busqueda)
                        ->orWhere('entidad', 'like', $busqueda);
                });
            })
            ->when($this->soloConAnalisis, fn ($builder) => $builder->whereHas('analisis'))
            ->when($this->estadoAnalisis !== '', function ($builder) {
                $builder->whereHas('analisis', function ($sub) {
                    $sub->where('estado', $this->estadoAnalisis);
                });
            })
            ->latest('updated_at');

        $archivos = $query->paginate($this->perPage);

        $stats = [
            'total' => ContratoArchivo::count(),
            'analizados' => ContratoArchivo::whereHas('analisis', function ($q) {
                $q->where('estado', 'exitoso');
            })->count(),
        ];

        return view('livewire.tdr-repository-manager', [
            'archivos' => $archivos,
            'stats' => $stats,
        ]);
    }
}
