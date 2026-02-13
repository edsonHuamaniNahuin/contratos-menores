<?php

namespace App\Livewire;

use App\Models\TelegramSubscription;
use App\Models\SubscriptionContractMatch;
use App\Models\ContratoSeguimiento;
use App\Services\SeaceBuscadorPublicoService;
use App\Services\SeacePublicArchivoService;
use App\Services\Tdr\CompatibilityScoreService;
use App\Services\Tdr\PublicTdrDocumentService;
use App\Services\TdrAnalysisService;
use Carbon\Carbon;
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

    public ?int $seguimientoEnCurso = null;
    public bool $mostrarLoginModal = false;
    public bool $mostrarAccesoRestringido = false;
    public string $loginModalMensaje = 'Inicia sesion para continuar.';
    public string $accesoRestringidoMensaje = 'Tu cuenta no tiene acceso a esta funcionalidad.';
    public string $loginEmail = '';
    public string $loginPassword = '';
    public bool $loginRemember = false;
    public ?string $loginError = null;
    public ?string $loginRedirectUrl = null;

    // Modal "Ver" detalle de contrato
    public ?array $contratoDetalle = null;
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

        // Ejecutar búsqueda inicial incluso con filtros por defecto
        $this->buscar();
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
     * Procesa las fechas del SEACE y agrega versiones amigables.
     * Las fechas del SEACE llegan en hora Lima (America/Lima).
     */
    protected function procesarFechas(array $resultados): array
    {
        $ahora = \Carbon\Carbon::now('America/Lima');

        foreach ($resultados as &$resultado) {
            $resultado = $this->procesarCampoFecha($resultado, 'fecPublica', $ahora);
            $resultado = $this->procesarCampoFecha($resultado, 'fecIniCotizacion', $ahora);
            $resultado = $this->procesarCampoFecha($resultado, 'fecFinCotizacion', $ahora);
        }

        return $resultados;
    }

    /**
     * Procesa un campo de fecha individual y genera las versiones legibles.
     */
    protected function procesarCampoFecha(array $resultado, string $campo, \Carbon\Carbon $ahora): array
    {
        if (empty($resultado[$campo])) {
            return $resultado;
        }

        try {
            $fecha = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $resultado[$campo], 'America/Lima');
            $fecha->locale('es');

            // Formato legible: "13 de feb. de 2026 a las 5:00 p.m."
            $resultado[$campo . '_completa'] = $this->formatearFechaLegible($fecha);

            // Relativo inteligente: "hace 2 minutos" (pasado) / "en 3 días" (futuro)
            $resultado[$campo . '_amigable'] = $fecha->locale('es')->diffForHumans([
                'parts' => 1,
            ]);

            // Timestamp para ordenamiento
            if ($campo === 'fecPublica') {
                $resultado[$campo . '_timestamp'] = $fecha->timestamp;
            }
        } catch (\Exception $e) {
            $resultado[$campo . '_completa'] = $resultado[$campo];
            $resultado[$campo . '_amigable'] = $resultado[$campo];
            if ($campo === 'fecPublica') {
                $resultado[$campo . '_timestamp'] = 0;
            }
        }

        return $resultado;
    }

    /**
     * Formatea una fecha Carbon en formato legible en español.
     * Ejemplo: "13 de feb. de 2026 a las 5:00 p.m."
     */
    protected function formatearFechaLegible(\Carbon\Carbon $fecha): string
    {
        $meses = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];

        $dia = $fecha->day;
        $mes = $meses[$fecha->month];
        $anio = $fecha->year;
        $hora = $fecha->format('g:i');
        $ampm = $fecha->hour >= 12 ? 'p.m.' : 'a.m.';

        return "{$dia} de {$mes} de {$anio} a las {$hora} {$ampm}";
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

    /**
     * Abrir modal con los datos del contrato desde la API.
     */
    public function verContrato(int $idContrato): void
    {
        $contrato = $this->resolveContrato($idContrato);

        if (!$contrato) {
            $this->notify('No se encontraron datos del contrato.', 'warning');
            return;
        }

        $this->contratoDetalle = $contrato;
    }

    /**
     * Cerrar modal de detalle de contrato.
     */
    public function cerrarDetalle(): void
    {
        $this->contratoDetalle = null;
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
        if (!$this->ensurePermission(
            'analyze-tdr',
            'Inicia sesion para analizar TDR con IA.',
            'Tu cuenta no tiene acceso al analisis IA. Solicita Proveedor Premium.'
        )) {
            return;
        }

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

    public function hacerSeguimiento(int $idContrato): void
    {
        $this->seguimientoEnCurso = $idContrato;

        try {
            if (!$this->ensurePermission(
                'follow-contracts',
                'Inicia sesion para hacer seguimiento del proceso.',
                'Tu cuenta no tiene acceso al seguimiento. Solicita Proveedor Premium.'
            )) {
                return;
            }

            $contrato = $this->resolveContrato($idContrato);
            if (!$contrato) {
                $this->notify('No se encontraron datos del contrato.', 'warning');
                return;
            }

            $payload = $this->buildSeguimientoPayload($contrato);
            if (($payload['contrato_seace_id'] ?? 0) <= 0) {
                $this->notify('No se pudo identificar el contrato para seguimiento.', 'warning');
                return;
            }

            ContratoSeguimiento::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'contrato_seace_id' => $payload['contrato_seace_id'],
                ],
                $payload
            );

            $this->notify('Seguimiento activado para el proceso.', 'success');
        } catch (Exception $e) {
            Log::error('BuscadorPublico:hacerSeguimiento', [
                'id_contrato' => $idContrato,
                'error' => $e->getMessage(),
            ]);
            $this->notify('No se pudo activar el seguimiento: ' . $e->getMessage(), 'error');
        } finally {
            $this->seguimientoEnCurso = null;
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

        // Determinar etapa basada en estado y si se puede cotizar
        $etapa = 'No disponible';
        $estadoNombre = $contrato['nomEstadoContrato'] ?? '';
        $cotizar = $contrato['cotizar'] ?? false;
        if ($cotizar) {
            $etapa = 'Recepción de cotizaciones';
        } elseif (str_contains(strtolower($estadoNombre), 'vigente')) {
            $etapa = 'Vigente';
        } elseif (str_contains(strtolower($estadoNombre), 'evaluación')) {
            $etapa = 'En evaluación';
        } elseif (str_contains(strtolower($estadoNombre), 'culminado')) {
            $etapa = 'Culminado';
        }

        return [
            'codigo' => $contrato['desContratacion'] ?? 'N/D',
            'entidad' => $contrato['nomEntidad'] ?? 'N/D',
            'objeto' => $contrato['nomObjetoContrato'] ?? 'N/D',
            'estado' => $contrato['nomEstadoContrato'] ?? 'N/D',
            'archivo' => $archivoMeta['nombre'] ?? ($archivoMeta['nombreTipoArchivo'] ?? 'TDR'),
            'fecha_publicacion' => $contrato['fecPublica_completa'] ?? $contrato['fecPublica'] ?? 'N/D',
            'fecha_cierre' => $contrato['fecFinCotizacion_completa'] ?? $contrato['fecFinCotizacion'] ?? 'N/D',
            'fecha_inicio_cotizacion' => $contrato['fecIniCotizacion_completa'] ?? $contrato['fecIniCotizacion'] ?? 'N/D',
            'etapa' => $etapa,
        ];
    }

    protected function ensurePermission(string $permission, string $loginMessage, string $deniedMessage): bool
    {
        $user = Auth::user();
        if (!$user) {
            $this->solicitarLogin($loginMessage);
            return false;
        }

        if (!$user->hasPermission($permission)) {
            $this->mostrarAccesoRestringido = true;
            $this->accesoRestringidoMensaje = $deniedMessage;
            return false;
        }

        return true;
    }

    protected function solicitarLogin(string $mensaje): void
    {
        $this->loginModalMensaje = $mensaje;
        $this->mostrarLoginModal = true;
        $this->loginError = null;
        $referer = request()->headers->get('referer');
        $this->loginRedirectUrl = $referer ?: url()->previous();
        if (!$this->loginRedirectUrl || str_contains($this->loginRedirectUrl, '/livewire-')) {
            $this->loginRedirectUrl = url('/buscador-publico');
        }
        session(['url.intended' => $this->loginRedirectUrl]);
    }

    public function cerrarLoginModal(): void
    {
        $this->mostrarLoginModal = false;
        $this->reset(['loginEmail', 'loginPassword', 'loginRemember', 'loginError', 'loginRedirectUrl']);
    }

    public function login(): void
    {
        $this->loginError = null;

        $credentials = $this->validate([
            'loginEmail' => ['required', 'email'],
            'loginPassword' => ['required', 'string'],
        ]);

        $ok = Auth::attempt([
            'email' => $credentials['loginEmail'],
            'password' => $credentials['loginPassword'],
        ], $this->loginRemember);

        if (!$ok) {
            $this->loginError = 'Las credenciales proporcionadas no son validas.';
            return;
        }

        request()->session()->regenerate();
        $this->mostrarLoginModal = false;
        $targetUrl = $this->loginRedirectUrl
            ?? session('url.intended')
            ?? url('/buscador-publico');
        if (!$targetUrl || str_contains($targetUrl, '/livewire-')) {
            $targetUrl = url('/buscador-publico');
        }
        $this->reset(['loginEmail', 'loginPassword', 'loginRemember', 'loginError', 'loginRedirectUrl']);
        $this->dispatch('login-redirect', url: $targetUrl);
    }

    public function cerrarAccesoRestringido(): void
    {
        $this->mostrarAccesoRestringido = false;
    }

    protected function buildSeguimientoPayload(array $contrato): array
    {
        $fechaPublicacion = $this->parseSeaceDate($contrato['fecPublica'] ?? null);
        $fechaInicio = $this->parseSeaceDate($contrato['fecIniCotizacion'] ?? null) ?? $fechaPublicacion;
        $fechaFin = $this->parseSeaceDate($contrato['fecFinCotizacion'] ?? null) ?? $fechaInicio;

        return [
            'user_id' => Auth::id(),
            'contrato_seace_id' => (int) ($contrato['idContrato'] ?? 0),
            'codigo_proceso' => (string) ($contrato['desContratacion'] ?? 'N/D'),
            'entidad' => $contrato['nomEntidad'] ?? null,
            'objeto' => $contrato['nomObjetoContrato'] ?? null,
            'estado' => $contrato['nomEstadoContrato'] ?? null,
            'fecha_publicacion' => $fechaPublicacion,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'snapshot' => $contrato,
        ];
    }

    protected function parseSeaceDate(?string $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::createFromFormat('d/m/Y H:i:s', $value, 'America/Lima');
        } catch (Exception $e) {
            return null;
        }
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
