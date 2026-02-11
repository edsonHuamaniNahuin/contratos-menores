<?php

namespace App\Livewire;

use App\Models\TelegramSubscription;
use App\Models\SubscriptionContractMatch;
use App\Services\SeaceBuscadorPublicoService;
use App\Services\SeacePublicArchivoService;
use App\Services\Tdr\CompatibilityScoreService;
use App\Services\Tdr\PublicTdrDocumentService;
use App\Services\TdrAnalysisService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

class BuscadorPublico extends Component
{
    use WithPagination;

    protected SeaceBuscadorPublicoService $buscadorService;
    protected SeacePublicArchivoService $archivoService;
    protected PublicTdrDocumentService $publicTdrService;
    protected TdrAnalysisService $tdrAnalysisService;
    protected CompatibilityScoreService $compatibilityService;
    protected array $contratosIndexados = [];

    // Parámetros de búsqueda (sincronizados con URL)
    #[Url(keep: true)]
    public string $palabraClave = '';

    #[Url(keep: true)]
    public string $entidadTexto = '';

    #[Url(keep: true)]
    public string $codigoEntidad = '';

    #[Url(keep: true)]
    public int $objetoContrato = 0;

    #[Url(keep: true)]
    public int $estadoContrato = 0;

    #[Url(keep: true)]
    public int $departamento = 0;

    #[Url(keep: true)]
    public int $provincia = 0;

    #[Url(keep: true)]
    public int $distrito = 0;

    #[Url(keep: true)]
    public int $pagina = 1;

    #[Url(keep: true)]
    public int $registrosPorPagina = 20;

    // El año se envía automáticamente como el año actual (no es filtro)

    // Ordenamiento
    #[Url(keep: true)]
    public string $ordenarPor = 'fecha_publicacion'; // fecha_publicacion, codigo, entidad, estado

    #[Url(keep: true)]
    public string $direccionOrden = 'desc'; // asc, desc

    // Estados de carga
    public bool $buscando = false;
    public bool $cargandoFiltros = false;

    // Datos
    public array $resultados = [];
    public array $paginacion = [];

    // Catálogos maestros
    public array $objetos = [];
    public array $estados = [];
    public array $departamentos = [];
    public array $provincias = [];
    public array $distritos = [];

    // Autocompletado de entidades
    public array $entidadesSugeridas = [];
    public bool $mostrarSugerenciasEntidades = false;

    // Filtros avanzados (expandible/colapsable)
    public bool $mostrarFiltrosAvanzados = false;

    // Mensaje de error
    public ?string $errorMensaje = null;

    // Gestión de archivos TDR
    public array $archivosPorContrato = [];
    public array $archivosErrores = [];
    public ?array $tdrNotificacion = null;
    public bool $descargandoTdr = false;
    public bool $analizandoTdr = false;
    public ?array $resultadoAnalisis = null;
    public ?array $analisisContrato = null;
    public ?int $analisisContratoId = null;
    public ?array $analisisContratoSnapshot = null;
    public array $suscriptoresUsuario = [];
    public array $compatibilidadPorSuscriptor = [];
    public ?int $compatibilidadEnCurso = null;

    public function boot(
        SeaceBuscadorPublicoService $buscadorService,
        SeacePublicArchivoService $archivoService,
        PublicTdrDocumentService $publicTdrService,
        TdrAnalysisService $tdrAnalysisService,
        CompatibilityScoreService $compatibilityService
    ) {
        $this->buscadorService = $buscadorService;
        $this->archivoService = $archivoService;
        $this->publicTdrService = $publicTdrService;
        $this->tdrAnalysisService = $tdrAnalysisService;
        $this->compatibilityService = $compatibilityService;
    }

    public function mount()
    {
        $this->cargarCatalogos();

        // NO realizar búsqueda inicial automática
        // Solo buscar cuando el usuario aplique filtros
    }

    /**
     * Cargar catálogos maestros (objetos, estados, departamentos)
     */
    public function cargarCatalogos(): void
    {
        $this->cargandoFiltros = true;

        // Objetos de contratación
        $objetosResponse = $this->buscadorService->obtenerObjetosContratacion();
        $this->objetos = $objetosResponse['data'] ?? [];

        // Estados de contratación
        $estadosResponse = $this->buscadorService->obtenerEstadosContratacion();
        $this->estados = $estadosResponse['data'] ?? [];

        // Departamentos
        $departamentosResponse = $this->buscadorService->obtenerDepartamentos();
        $this->departamentos = $departamentosResponse['data'] ?? [];

        $this->cargandoFiltros = false;
    }

    /**
     * Buscar contratos con los filtros actuales
     */
    public function buscar(): void
    {
        $this->buscando = true;
        $this->errorMensaje = null;
        $this->resetPage();

        $params = [
            'anio' => now()->year, // Siempre el año actual
            'orden' => 2, // 2 = Descendente (más recientes primero)
            'page' => $this->pagina,
            'page_size' => $this->registrosPorPagina,
        ];

        // Palabra clave
        if (!empty($this->palabraClave)) {
            $params['palabra_clave'] = $this->palabraClave;
        }

        // Entidad
        if (!empty($this->codigoEntidad)) {
            $params['codigo_entidad'] = $this->codigoEntidad;
        }

        // Objeto de contratación
        if ($this->objetoContrato > 0) {
            $params['lista_codigo_objeto'] = $this->objetoContrato;
        }

        // Estado de contratación
        if ($this->estadoContrato > 0) {
            $params['lista_estado_contrato'] = $this->estadoContrato;
        }

        // Ubicación geográfica (usar codigo_departamento en lugar de departamento)
        if ($this->departamento > 0) {
            $params['codigo_departamento'] = $this->departamento;
        }

        if ($this->provincia > 0) {
            $params['codigo_provincia'] = $this->provincia;
        }

        if ($this->distrito > 0) {
            $params['codigo_distrito'] = $this->distrito;
        }

        $resultado = $this->buscadorService->buscarContratos($params);

        if ($resultado['success']) {
            $this->resultados = $this->procesarFechas($resultado['data']);
            $this->resultados = $this->ordenarResultados($this->resultados);
            $this->paginacion = $resultado['pagination'];

            $this->contratosIndexados = [];
            foreach ($this->resultados as $contrato) {
                if (!empty($contrato['idContrato'])) {
                    $this->contratosIndexados[$contrato['idContrato']] = $contrato;
                }
            }

            $this->archivosPorContrato = [];
            $this->archivosErrores = [];
            $this->tdrNotificacion = null;
            $this->resultadoAnalisis = null;
            $this->analisisContrato = null;
        } else {
            $this->errorMensaje = $resultado['error'] ?? 'Error desconocido';
            $this->resultados = [];
            $this->paginacion = [];
            $this->contratosIndexados = [];
        }

        $this->buscando = false;
    }

    /**
     * Cargar provincias cuando se selecciona un departamento
     */
    public function updatedDepartamento($value): void
    {
        $this->provincia = 0;
        $this->distrito = 0;
        $this->provincias = [];
        $this->distritos = [];

        if ($value > 0) {
            $resultado = $this->buscadorService->obtenerProvincias($value);
            $this->provincias = $resultado['data'] ?? [];
        }

        // Buscar automáticamente cuando cambia el departamento
        $this->buscar();
    }

    /**
     * Cargar distritos cuando se selecciona una provincia
     */
    public function updatedProvincia($value): void
    {
        $this->distrito = 0;
        $this->distritos = [];

        if ($value > 0) {
            $resultado = $this->buscadorService->obtenerDistritos($value);
            $this->distritos = $resultado['data'] ?? [];
        }

        // Buscar automáticamente cuando cambia la provincia
        $this->buscar();
    }

    /**
     * Autocompletado de entidades (debounced)
     */
    public function buscarEntidades(): void
    {
        if (strlen($this->entidadTexto) < 3) {
            $this->entidadesSugeridas = [];
            $this->mostrarSugerenciasEntidades = false;
            return;
        }

        $resultado = $this->buscadorService->buscarEntidades($this->entidadTexto);

        if ($resultado['success']) {
            $this->entidadesSugeridas = $resultado['data'];
            $this->mostrarSugerenciasEntidades = count($this->entidadesSugeridas) > 0;

            // Disparar evento para abrir dropdown (si hay resultados)
            if (count($this->entidadesSugeridas) > 0) {
                $this->dispatch('abrir-dropdown-entidades');
            }
        }
    }

    /**
     * Listener para cambios en palabraClave (con debounce desde la vista)
     */
    public function updatedPalabraClave($value): void
    {
        // Buscar automáticamente cuando cambia la palabra clave (debounce controlado desde la vista)
        $this->buscar();
    }

    /**
     * Listener para cambios en entidadTexto (con debounce desde la vista)
     */
    public function updatedEntidadTexto($value): void
    {
        // Si el usuario borró todo el texto, limpiar el filtro y buscar automáticamente
        if (empty(trim($value))) {
            $this->codigoEntidad = '';
            $this->entidadesSugeridas = [];
            $this->mostrarSugerenciasEntidades = false;

            // Buscar automáticamente cuando se limpia el filtro
            $this->buscar();
            return;
        }

        // Si hay texto (3+ caracteres), buscar entidades para autocompletado
        $this->buscarEntidades();
    }

    /**
     * Seleccionar una entidad del autocompletado
     */
    public function seleccionarEntidad(string $razonSocial, string $codigoConsucode): void
    {
        $this->entidadTexto = $razonSocial;
        $this->codigoEntidad = $codigoConsucode;
        $this->mostrarSugerenciasEntidades = false;
        $this->entidadesSugeridas = [];

        // Buscar automáticamente cuando se selecciona una entidad
        $this->buscar();
    }

    /**
     * Limpiar filtro de entidad
     */
    public function limpiarEntidad(): void
    {
        $this->entidadTexto = '';
        $this->codigoEntidad = '';
        $this->entidadesSugeridas = [];
        $this->mostrarSugerenciasEntidades = false;

        // Buscar automáticamente al limpiar
        $this->buscar();
    }

    /**
     * Limpiar todos los filtros
     */
    public function limpiarFiltros(): void
    {
        $this->reset([
            'palabraClave',
            'entidadTexto',
            'codigoEntidad',
            'objetoContrato',
            'estadoContrato',
            'departamento',
            'provincia',
            'distrito',
            'pagina',
        ]);

        $this->provincias = [];
        $this->distritos = [];
        $this->resultados = [];
        $this->paginacion = [];
        $this->errorMensaje = null;

        // Buscar automáticamente con filtros por defecto
        $this->buscar();
    }

    /**
     * Cambiar de página
     */
    public function irAPagina(int $numeroPagina): void
    {
        $this->pagina = $numeroPagina;
        $this->buscar();
    }

    /**
     * Ordena los resultados de la búsqueda
     */
    protected function ordenarResultados(array $resultados): array
    {
        if (empty($resultados)) {
            return $resultados;
        }

        usort($resultados, function ($a, $b) {
            $valorA = null;
            $valorB = null;

            switch ($this->ordenarPor) {
                case 'fecha_publicacion':
                    $valorA = $a['fecPublica_timestamp'] ?? 0;
                    $valorB = $b['fecPublica_timestamp'] ?? 0;
                    break;
                case 'codigo':
                    $valorA = $a['desContratacion'] ?? '';
                    $valorB = $b['desContratacion'] ?? '';
                    break;
                case 'entidad':
                    $valorA = $a['nomEntidad'] ?? '';
                    $valorB = $b['nomEntidad'] ?? '';
                    break;
                case 'estado':
                    $valorA = $a['nomEstadoContrato'] ?? '';
                    $valorB = $b['nomEstadoContrato'] ?? '';
                    break;
                default:
                    return 0;
            }

            if ($this->direccionOrden === 'asc') {
                return $valorA <=> $valorB;
            } else {
                return $valorB <=> $valorA;
            }
        });

        return $resultados;
    }

    /**
     * Procesa las fechas del SEACE y agrega versiones amigables
     */
    protected function procesarFechas(array $resultados): array
    {
        foreach ($resultados as &$resultado) {
            // Fecha de publicación
            if (!empty($resultado['fecPublica'])) {
                try {
                    $fecha = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $resultado['fecPublica']);
                    $fecha->locale('es'); // Configurar locale en español
                    $resultado['fecPublica_amigable'] = $fecha->diffForHumans();
                    $resultado['fecPublica_completa'] = $fecha->format('d/m/Y H:i:s');
                    $resultado['fecPublica_timestamp'] = $fecha->timestamp;
                } catch (\Exception $e) {
                    $resultado['fecPublica_amigable'] = $resultado['fecPublica'];
                    $resultado['fecPublica_completa'] = $resultado['fecPublica'];
                    $resultado['fecPublica_timestamp'] = 0;
                }
            }

            // Fecha inicio cotización
            if (!empty($resultado['fecIniCotizacion'])) {
                try {
                    $fecha = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $resultado['fecIniCotizacion']);
                    $fecha->locale('es'); // Configurar locale en español
                    $resultado['fecIniCotizacion_amigable'] = $fecha->diffForHumans();
                    $resultado['fecIniCotizacion_completa'] = $fecha->format('d/m/Y H:i:s');
                } catch (\Exception $e) {
                    $resultado['fecIniCotizacion_amigable'] = $resultado['fecIniCotizacion'];
                    $resultado['fecIniCotizacion_completa'] = $resultado['fecIniCotizacion'];
                }
            }

            // Fecha fin cotización
            if (!empty($resultado['fecFinCotizacion'])) {
                try {
                    $fecha = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $resultado['fecFinCotizacion']);
                    $fecha->locale('es'); // Configurar locale en español
                    $resultado['fecFinCotizacion_amigable'] = $fecha->diffForHumans();
                    $resultado['fecFinCotizacion_completa'] = $fecha->format('d/m/Y H:i:s');
                } catch (\Exception $e) {
                    $resultado['fecFinCotizacion_amigable'] = $resultado['fecFinCotizacion'];
                    $resultado['fecFinCotizacion_completa'] = $resultado['fecFinCotizacion'];
                }
            }
        }

        return $resultados;
    }

    /**
     * Cambia el ordenamiento de la tabla
     */
    public function cambiarOrden(string $columna): void
    {
        if ($this->ordenarPor === $columna) {
            // Cambiar dirección si ya está ordenando por esta columna
            $this->direccionOrden = $this->direccionOrden === 'asc' ? 'desc' : 'asc';
        } else {
            // Nueva columna, ordenar descendente por defecto
            $this->ordenarPor = $columna;
            $this->direccionOrden = 'desc';
        }

        // Reordenar resultados actuales sin hacer nueva petición
        if (!empty($this->resultados)) {
            $this->resultados = $this->ordenarResultados($this->resultados);
        }
    }

    /**
     * Cambiar cantidad de registros por página (se ejecuta automáticamente con wire:model.live)
     */
    public function updatedRegistrosPorPagina($value): void
    {
        $this->pagina = 1;
        $this->buscar();
    }

    /**
     * Listener para cambios en objeto (buscar automáticamente)
     */
    public function updatedObjetoContrato($value): void
    {
        $this->buscar();
    }

    /**
     * Listener para cambios en estado (buscar automáticamente)
     */
    public function updatedEstadoContrato($value): void
    {
        $this->buscar();
    }

    /**
     * Listener para cambios en distrito (buscar automáticamente)
     */
    public function updatedDistrito($value): void
    {
        $this->buscar();
    }

    /**
     * Alternar filtros avanzados
     */
    public function toggleFiltrosAvanzados(): void
    {
        $this->mostrarFiltrosAvanzados = !$this->mostrarFiltrosAvanzados;
    }

    /**
     * Verificar si hay filtros activos
     */
    protected function tieneFiltrosActivos(): bool
    {
        return !empty($this->palabraClave)
            || !empty($this->codigoEntidad)
            || $this->objetoContrato > 0
            || $this->estadoContrato > 0
            || $this->departamento > 0;
    }

    /**
     * Obtener el total de filtros activos (para badge)
     */
    public function contarFiltrosActivos(): int
    {
        $count = 0;

        if (!empty($this->palabraClave)) $count++;
        if (!empty($this->codigoEntidad)) $count++;
        if ($this->objetoContrato > 0) $count++;
        if ($this->estadoContrato > 0) $count++;
        if ($this->departamento > 0) $count++;
        if ($this->provincia > 0) $count++;
        if ($this->distrito > 0) $count++;

        return $count;
    }

    public function descargarTdr(int $idContrato): void
    {
        $this->descargandoTdr = true;

        try {
            $archivoMeta = $this->resolveArchivoMeta($idContrato);

            if (!$archivoMeta) {
                $this->notify('No se encontraron anexos públicos para este proceso.', 'warning');
                return;
            }
            $contratoSnapshot = $this->resolveContrato($idContrato);
            $archivoPersistido = $this->publicTdrService->ensureLocalArchivo($idContrato, $archivoMeta, $contratoSnapshot);

            $this->dispatch('descargar-archivo', url: route('tdr.archivos.download', $archivoPersistido));
            $this->notify('Descarga preparada desde el repositorio local.', 'success');
        } catch (Exception $e) {
            Log::error('BuscadorPublico:descargarTdr', [
                'id_contrato' => $idContrato,
                'error' => $e->getMessage(),
            ]);
            $this->notify('No se pudo preparar la descarga: ' . $e->getMessage(), 'error');
        } finally {
            $this->descargandoTdr = false;
        }
    }

    public function analizarTdr(int $idContrato): void
    {
        $this->analizandoTdr = true;
        $this->resultadoAnalisis = null;
        $this->analisisContrato = null;
        $this->analisisContratoId = null;
        $this->analisisContratoSnapshot = null;
        $this->suscriptoresUsuario = [];
        $this->compatibilidadPorSuscriptor = [];

        try {
            $archivoMeta = $this->resolveArchivoMeta($idContrato);

            if (!$archivoMeta) {
                $this->notify('No hay archivos disponibles para analizar.', 'warning');
                return;
            }
            $contratoSnapshot = $this->resolveContrato($idContrato);
            $archivoPersistido = $this->publicTdrService->ensureLocalArchivo($idContrato, $archivoMeta, $contratoSnapshot);

            $analisis = $this->tdrAnalysisService->analizarArchivoLocal($archivoPersistido, $contratoSnapshot);

            if (!($analisis['success'] ?? false)) {
                $this->notify($analisis['error'] ?? 'No se pudo completar el análisis IA.', 'error');
                return;
            }

            $payload = $analisis['data'] ?? [];
            $this->resultadoAnalisis = $payload;
            $this->analisisContrato = $this->buildAnalisisContext($idContrato, $archivoMeta);
            $this->analisisContratoId = $idContrato;
            $this->analisisContratoSnapshot = $contratoSnapshot;

            $this->loadUserCompatibility($contratoSnapshot);

            $mensaje = ($payload['cache'] ?? false)
                ? 'Mostrando el análisis IA almacenado previamente.'
                : 'Análisis IA completado exitosamente.';

            $this->notify($mensaje, 'success');
        } catch (Exception $e) {
            Log::error('BuscadorPublico:analizarTdr', [
                'id_contrato' => $idContrato,
                'error' => $e->getMessage(),
            ]);
            $this->notify('No se pudo completar el análisis IA: ' . $e->getMessage(), 'error');
        } finally {
            $this->analizandoTdr = false;
        }
    }

    public function limpiarAnalisis(): void
    {
        $this->resultadoAnalisis = null;
        $this->analisisContrato = null;
        $this->analisisContratoId = null;
        $this->analisisContratoSnapshot = null;
        $this->suscriptoresUsuario = [];
        $this->compatibilidadPorSuscriptor = [];
        $this->compatibilidadEnCurso = null;
    }

    public function calcularCompatibilidad(int $subscriptionId): void
    {
        $this->compatibilidadEnCurso = $subscriptionId;

        try {
            $user = Auth::user();
            if (!$user) {
                $this->notify('Inicia sesión para calcular compatibilidad.', 'warning');
                return;
            }

            $subscription = $user->telegramSubscriptions()
                ->activas()
                ->where('id', $subscriptionId)
                ->first();

            if (!$subscription) {
                $this->notify('No se encontró el suscriptor seleccionado.', 'warning');
                return;
            }

            if (blank($subscription->company_copy)) {
                $this->notify('Este suscriptor no tiene copy configurado.', 'warning');
                return;
            }

            $contratoSnapshot = $this->analisisContratoSnapshot
                ?? $this->resolveContrato($this->analisisContratoId ?? 0);

            if (!$contratoSnapshot) {
                $this->notify('No se pudo resolver el contrato para compatibilidad.', 'error');
                return;
            }

            $payload = $this->resultadoAnalisis ?? [];
            $compatResult = $this->compatibilityService->ensureScore($subscription, $contratoSnapshot, $payload);

            if (!empty($compatResult['error'])) {
                $this->notify($compatResult['error'], 'warning');
            }

            $match = $compatResult['match'] ?? null;
            $entry = $this->buildCompatibilityEntry($match, $compatResult['payload'] ?? null);
            if ($entry) {
                $this->compatibilidadPorSuscriptor[$subscription->id] = $entry;
            }

            $this->notify('Compatibilidad actualizada para ' . $this->formatSubscriptionLabel($subscription) . '.', 'success');
        } catch (\Throwable $e) {
            Log::error('BuscadorPublico:compatibilidad', [
                'subscription_id' => $subscriptionId,
                'contrato' => $this->analisisContratoId,
                'error' => $e->getMessage(),
            ]);
            $this->notify('No se pudo evaluar la compatibilidad: ' . $e->getMessage(), 'error');
        } finally {
            $this->compatibilidadEnCurso = null;
        }
    }

    protected function loadUserCompatibility(?array $contratoSnapshot): void
    {
        $this->suscriptoresUsuario = [];
        $this->compatibilidadPorSuscriptor = [];

        $user = Auth::user();
        if (!$user || !$contratoSnapshot) {
            return;
        }

        $contratoId = (int) ($contratoSnapshot['idContrato'] ?? $contratoSnapshot['id_contrato_seace'] ?? 0);
        if ($contratoId <= 0) {
            return;
        }

        $subscriptions = $user->telegramSubscriptions()
            ->activas()
            ->orderByRaw('IFNULL(nombre, chat_id) asc')
            ->get();

        $this->suscriptoresUsuario = $subscriptions->map(function (TelegramSubscription $subscription) {
            return [
                'id' => $subscription->id,
                'label' => $this->formatSubscriptionLabel($subscription),
                'has_copy' => !blank($subscription->company_copy),
            ];
        })->toArray();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $matches = SubscriptionContractMatch::query()
            ->where('contrato_seace_id', $contratoId)
            ->whereIn('telegram_subscription_id', $subscriptions->pluck('id'))
            ->get()
            ->keyBy('telegram_subscription_id');

        foreach ($subscriptions as $subscription) {
            $match = $matches->get($subscription->id);
            if ($match) {
                $this->compatibilidadPorSuscriptor[$subscription->id] = $this->buildCompatibilityEntry($match, null);
            }
        }
    }

    protected function buildCompatibilityEntry(?SubscriptionContractMatch $match, ?array $payload): ?array
    {
        if (!$match && !$payload) {
            return null;
        }

        $payload = $payload ?? ($match?->analisis_payload ?? []);

        return [
            'score' => $match?->score ?? (is_numeric($payload['score'] ?? null) ? (float) $payload['score'] : null),
            'nivel' => $payload['nivel'] ?? null,
            'explicacion' => $payload['explicacion'] ?? null,
            'actualizado' => $match?->analizado_en?->format('d/m/Y H:i')
                ?? ($payload['timestamp'] ?? null),
        ];
    }

    protected function formatSubscriptionLabel(TelegramSubscription $subscription): string
    {
        return $subscription->nombre ? $subscription->nombre : 'Chat ' . $subscription->chat_id;
    }

    protected function resolveContrato(int $idContrato): ?array
    {
        if (!isset($this->contratosIndexados[$idContrato])) {
            foreach ($this->resultados as $contrato) {
                if ((int) ($contrato['idContrato'] ?? 0) === $idContrato) {
                    $this->contratosIndexados[$idContrato] = $contrato;
                    break;
                }
            }
        }

        return $this->contratosIndexados[$idContrato] ?? null;
    }

    protected function resolveArchivoMeta(int $idContrato, ?int $idContratoArchivo = null): ?array
    {
        if (!isset($this->archivosPorContrato[$idContrato])) {
            $response = $this->archivoService->listarArchivos($idContrato);

            if (!($response['success'] ?? false)) {
                $this->archivosErrores[$idContrato] = $response['error'] ?? 'No fue posible obtener los archivos.';
                return null;
            }

            $this->archivosPorContrato[$idContrato] = $response['data'] ?? [];
        }

        $archivos = $this->archivosPorContrato[$idContrato];

        if (empty($archivos)) {
            $this->archivosErrores[$idContrato] = 'La entidad no publicó anexos en el portal público.';
            return null;
        }

        if ($idContratoArchivo) {
            foreach ($archivos as $archivo) {
                if ((int) ($archivo['idContratoArchivo'] ?? 0) === $idContratoArchivo) {
                    return $archivo;
                }
            }
        }

        return $this->selectPreferredArchivo($archivos);
    }

    protected function selectPreferredArchivo(array $archivos): array
    {
        foreach ($archivos as $archivo) {
            $nombre = strtolower($archivo['nombre'] ?? '');
            if (str_contains($nombre, 'tdr') || str_contains($nombre, 'bases') || str_contains($nombre, 'pliego')) {
                return $archivo;
            }
        }

        return $archivos[0];
    }

    protected function buildAnalisisContext(int $idContrato, array $archivoMeta): array
    {
        $contrato = $this->resolveContrato($idContrato) ?? [];

        return [
            'codigo' => $contrato['desContratacion'] ?? 'N/D',
            'entidad' => $contrato['nomEntidad'] ?? 'N/D',
            'objeto' => $contrato['nomObjetoContrato'] ?? 'N/D',
            'estado' => $contrato['nomEstadoContrato'] ?? 'N/D',
            'archivo' => $archivoMeta['nombre'] ?? ($archivoMeta['nombreTipoArchivo'] ?? 'TDR'),
        ];
    }

    protected function notify(string $message, string $type = 'info'): void
    {
        $this->tdrNotificacion = [
            'message' => $message,
            'type' => $type,
            'time' => now()->format('H:i:s'),
        ];
    }

    public function render()
    {
        return view('livewire.buscador-publico');
    }
}
