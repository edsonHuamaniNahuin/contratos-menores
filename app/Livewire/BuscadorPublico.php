<?php

namespace App\Livewire;

use App\Jobs\AnalizarTdrJob;
use App\Models\TdrAnalisis;
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
use App\Models\SubscriberProfile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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

    // ─── Parámetros de búsqueda (URL SEO-friendly con slugs) ────────

    #[Url(as: 'palabraClave')]
    public string $palabraClave = '';

    #[Url(as: 'entidad')]
    public string $entidadTexto = '';

    // Código CONSUCODE (interno, NO en URL)
    public string $codigoEntidad = '';

    // Slugs SEO-friendly (sincronizados con URL)
    #[Url(as: 'objeto')]
    public string $objetoSlug = '';

    #[Url(as: 'estado')]
    public string $estadoSlug = '';

    #[Url(as: 'dep')]
    public string $depSlug = '';

    #[Url(as: 'prov')]
    public string $provSlug = '';

    #[Url(as: 'dist')]
    public string $distSlug = '';

    // IDs numéricos internos (resueltos desde slugs, NO en URL)
    public int $objetoContrato = 0;
    public int $estadoContrato = 0;
    public int $departamento = 0;
    public int $provincia = 0;
    public int $distrito = 0;

    #[Url]
    public int $pagina = 1;

    #[Url(as: 'porPagina')]
    public int $registrosPorPagina = 5;

    // El año se envía automáticamente como el año actual (no es filtro)

    // Ordenamiento
    #[Url(as: 'ordenarPor')]
    public string $ordenarPor = 'fecha_publicacion'; // fecha_publicacion, codigo, entidad, estado

    #[Url(as: 'direccion')]
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

    // Job async para PDFs pesados (≥500 KB sin análisis en caché).
    // Null = modo síncrono activo. Con valor = polling activo.
    public ?int $analisisJobArchivoId = null;

    public ?int $seguimientoEnCurso = null;
    public ?int $cotizandoEnCurso = null;
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

    // Proforma técnica
    public ?array $resultadoProforma = null;
    public ?string $proformaToken = null;
    public ?int $proformaContratoId = null;

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

        // Resolver slugs de URL → IDs internos (+ backward compat con URLs viejas)
        $this->syncFromUrl();

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

    // ─── Slug ↔ ID resolution (SEO-friendly URLs) ──────────────────

    /**
     * Sincronizar parámetros de URL (slugs) con IDs internos.
     * Maneja backward compatibility con URLs antiguas que usan IDs numéricos.
     */
    private function syncFromUrl(): void
    {
        $request = request();

        // ── Backward compat: leer params numéricos de URLs viejas ──
        if (empty($this->objetoSlug) && $request->has('objetoContrato')) {
            $legacyId = (int) $request->get('objetoContrato');
            if ($legacyId > 0) {
                $this->objetoSlug = $this->resolveSlugFromId($legacyId, $this->objetos);
            }
        }
        if (empty($this->estadoSlug) && $request->has('estadoContrato')) {
            $legacyId = (int) $request->get('estadoContrato');
            if ($legacyId > 0) {
                $this->estadoSlug = $this->resolveSlugFromId($legacyId, $this->estados);
            }
        }
        if (empty($this->depSlug) && $request->has('departamento')) {
            $legacyId = (int) $request->get('departamento');
            if ($legacyId > 0) {
                $this->depSlug = $this->resolveSlugFromId($legacyId, $this->departamentos);
            }
        }
        // Legacy codigoEntidad
        if (empty($this->codigoEntidad) && $request->has('codigoEntidad')) {
            $this->codigoEntidad = (string) $request->get('codigoEntidad');
        }
        // Legacy entidadTexto (alias viejo)
        if (empty($this->entidadTexto) && $request->has('entidadTexto')) {
            $this->entidadTexto = (string) $request->get('entidadTexto');
        }

        // ── Resolver slugs a IDs internos ──
        $this->objetoContrato = $this->resolveIdFromSlug($this->objetoSlug, $this->objetos);
        $this->estadoContrato = $this->resolveIdFromSlug($this->estadoSlug, $this->estados);
        $this->departamento = $this->resolveIdFromSlug($this->depSlug, $this->departamentos);

        // ── Geo cascada: provincias (requiere departamento) ──
        if ($this->departamento > 0) {
            $resultado = $this->buscadorService->obtenerProvincias($this->departamento);
            $this->provincias = $resultado['data'] ?? [];

            // Backward compat: provincia numérica
            if (empty($this->provSlug) && $request->has('provincia')) {
                $legacyId = (int) $request->get('provincia');
                if ($legacyId > 0) {
                    $this->provSlug = $this->resolveSlugFromId($legacyId, $this->provincias);
                }
            }

            $this->provincia = $this->resolveIdFromSlug($this->provSlug, $this->provincias);

            // Distritos (requiere provincia)
            if ($this->provincia > 0) {
                $resultado = $this->buscadorService->obtenerDistritos($this->provincia);
                $this->distritos = $resultado['data'] ?? [];

                // Backward compat: distrito numérico
                if (empty($this->distSlug) && $request->has('distrito')) {
                    $legacyId = (int) $request->get('distrito');
                    if ($legacyId > 0) {
                        $this->distSlug = $this->resolveSlugFromId($legacyId, $this->distritos);
                    }
                }

                $this->distrito = $this->resolveIdFromSlug($this->distSlug, $this->distritos);
            }
        }

        // ── Resolver entidad texto → código CONSUCODE (si falta) ──
        if (!empty($this->entidadTexto) && empty($this->codigoEntidad)) {
            $resultado = $this->buscadorService->buscarEntidades($this->entidadTexto);
            foreach ($resultado['data'] ?? [] as $entidad) {
                if (strcasecmp(trim($entidad['razonSocial'] ?? ''), trim($this->entidadTexto)) === 0) {
                    $this->codigoEntidad = (string) ($entidad['codigoConsucode'] ?? '');
                    break;
                }
            }
        }

        // Abrir filtros geográficos si hay algún filtro geo activo
        if ($this->departamento > 0) {
            $this->mostrarFiltrosAvanzados = true;
        }
    }

    /**
     * Generar slug SEO-friendly desde un nombre de catálogo.
     * Ej: "LIMA" → "lima", "SAN MARTÍN" → "san-martin"
     */
    private function slugify(string $name): string
    {
        $slug = mb_strtolower(trim($name));
        $slug = strtr($slug, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ñ' => 'n', 'ü' => 'u',
        ]);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim($slug, '-');
    }

    /**
     * Resolver un slug (o valor numérico legacy) a un ID de catálogo.
     */
    private function resolveIdFromSlug(string $slug, array $catalog): int
    {
        if (empty($slug)) return 0;

        // Backward compat: si es numérico, devolver directo
        if (ctype_digit($slug)) return (int) $slug;

        foreach ($catalog as $item) {
            if ($this->slugify($item['nom'] ?? '') === $slug) {
                return (int) ($item['id'] ?? 0);
            }
        }
        return 0;
    }

    /**
     * Resolver un ID numérico al slug correspondiente del catálogo.
     */
    private function resolveSlugFromId(int $id, array $catalog): string
    {
        if ($id <= 0) return '';

        foreach ($catalog as $item) {
            if ((int) ($item['id'] ?? 0) === $id) {
                return $this->slugify($item['nom'] ?? '');
            }
        }
        return '';
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
        $this->depSlug = $this->resolveSlugFromId((int) $value, $this->departamentos);
        $this->provincia = 0;
        $this->provSlug = '';
        $this->distrito = 0;
        $this->distSlug = '';
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
        $this->provSlug = $this->resolveSlugFromId((int) $value, $this->provincias);
        $this->distrito = 0;
        $this->distSlug = '';
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
            'objetoSlug',
            'estadoSlug',
            'depSlug',
            'provSlug',
            'distSlug',
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
        $this->objetoSlug = $this->resolveSlugFromId((int) $value, $this->objetos);
        $this->buscar();
    }

    /**
     * Listener para cambios en estado (buscar automáticamente)
     */
    public function updatedEstadoContrato($value): void
    {
        $this->estadoSlug = $this->resolveSlugFromId((int) $value, $this->estados);
        $this->buscar();
    }

    /**
     * Listener para cambios en distrito (buscar automáticamente)
     */
    public function updatedDistrito($value): void
    {
        $this->distSlug = $this->resolveSlugFromId((int) $value, $this->distritos);
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
            $this->notify('Descarga preparada desde el repositorio local.', 'success', [
                'contrato_id' => $idContrato,
                'archivo_id' => $archivoPersistido->id,
            ]);
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

    public function reportarArchivoNoTdr(int $idContrato, int $archivoId): void
    {
        Log::warning('BuscadorPublico: reporte de TDR incorrecto', [
            'contrato_id' => $idContrato,
            'archivo_id' => $archivoId,
            'user_id' => Auth::id(),
            'ip' => request()->ip(),
        ]);

        $this->notify('Reporte recibido. Gracias por ayudarnos a corregir el repositorio.', 'info');
    }

    public function redescargarTdr(int $idContrato): void
    {
        if (!(auth()->user()?->hasRole('admin') ?? false)) {
            $this->notify('Solo administradores pueden forzar la re-descarga.', 'warning');
            return;
        }

        $this->descargandoTdr = true;

        try {
            $archivoMeta = $this->resolveArchivoMeta($idContrato);

            if (!$archivoMeta) {
                $this->notify('No se encontraron anexos publicos para este proceso.', 'warning');
                return;
            }

            $contratoSnapshot = $this->resolveContrato($idContrato);
            $archivoPersistido = $this->publicTdrService->refreshLocalArchivo($idContrato, $archivoMeta, $contratoSnapshot);

            $this->dispatch('descargar-archivo', url: route('tdr.archivos.download', $archivoPersistido));
            $this->notify('Descarga actualizada desde el portal publico.', 'success', [
                'contrato_id' => $idContrato,
                'archivo_id' => $archivoPersistido->id,
            ]);
        } catch (Exception $e) {
            Log::error('BuscadorPublico:redescargarTdr', [
                'id_contrato' => $idContrato,
                'error' => $e->getMessage(),
            ]);
            $this->notify('No se pudo re-descargar el archivo: ' . $e->getMessage(), 'error');
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

        // Eliminar el límite de ejecución PHP para esta petición pesada.
        // Los PDFs escaneados (OCR) pueden tardar varios minutos.
        set_time_limit(0);

        $this->analizandoTdr = true;
        $this->analisisJobArchivoId = null;
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

            // ── Fast-path: ya hay análisis en DB ──────────────────────────────
            $analisisCacheado = TdrAnalisis::where('contrato_archivo_id', $archivoPersistido->id)
                ->where('estado', TdrAnalisis::ESTADO_EXITOSO)
                ->where('tipo_analisis', TdrAnalisis::TIPO_GENERAL)
                ->latest('analizado_en')
                ->first();

            if ($analisisCacheado) {
                // Resultado ya existe → renderizar inmediatamente sin llamar al LLM.
                $analisis = $this->tdrAnalysisService->analizarArchivoLocal($archivoPersistido, $contratoSnapshot);
                $this->_mostrarResultadoAnalisis($analisis, $idContrato, $archivoMeta, $contratoSnapshot);
                return;
            }

            // ── PDF pesado (≥500 KB sin caché) → Job async ───────────────────
            // Evita que la petición Livewire quede bloqueada durante el OCR.
            $tamano = $archivoPersistido->tamano_bytes ?? 0;
            $usarJob = $tamano >= 500_000 && config('queue.default') !== 'sync';

            if ($usarJob) {
                $this->analisisJobArchivoId = $archivoPersistido->id;
                // Guardamos el contexto para reconstruirlo al completar el poll.
                $this->analisisContratoId = $idContrato;
                $this->analisisContratoSnapshot = $contratoSnapshot;
                $this->analisisContrato = $this->buildAnalisisContext($idContrato, $archivoMeta);

                AnalizarTdrJob::dispatch($archivoPersistido, $contratoSnapshot, Auth::id() ?? 0);

                $this->notify(
                    '🔄 El documento es extenso. El análisis IA está en proceso en segundo plano. La página se actualizará automáticamente al terminar.',
                    'info'
                );
                // analizandoTdr permanece true → el overlay sigue visible + wire:poll activo.
                return;
            }

            // ── PDF pequeño → análisis síncrono ──────────────────────────────
            $analisis = $this->tdrAnalysisService->analizarArchivoLocal($archivoPersistido, $contratoSnapshot);
            $this->_mostrarResultadoAnalisis($analisis, $idContrato, $archivoMeta, $contratoSnapshot);

        } catch (Exception $e) {
            $ref = 'TDR-' . strtoupper(Str::random(6));
            Log::error("BuscadorPublico:analizarTdr [{$ref}]", [
                'ref' => $ref,
                'id_contrato' => $idContrato,
                'error' => $e->getMessage(),
            ]);
            $this->notify(TdrAnalysisService::humanizeError($e->getMessage(), $ref), 'error');
        } finally {
            // Solo reseteamos si NO estamos esperando por un job async.
            if (!$this->analisisJobArchivoId) {
                $this->analizandoTdr = false;
            }
        }
    }

    /**
     * Extrae y muestra el resultado del análisis en el componente.
     * Usado tanto en modo síncrono como al resolver el poll del job.
     */
    private function _mostrarResultadoAnalisis(array $analisis, int $idContrato, array $archivoMeta, ?array $contratoSnapshot): void
    {
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
    }

    /**
     * Polling del resultado del Job async.
     * Livewire llama a este método cada 3s mientras analisisJobArchivoId esté seteado.
     */
    public function checkAnalisisJob(): void
    {
        if (!$this->analisisJobArchivoId) {
            return;
        }

        $terminado = TdrAnalisis::where('contrato_archivo_id', $this->analisisJobArchivoId)
            ->whereIn('estado', [TdrAnalisis::ESTADO_EXITOSO, TdrAnalisis::ESTADO_FALLIDO])
            ->where('tipo_analisis', TdrAnalisis::TIPO_GENERAL)
            ->latest('analizado_en')
            ->first();

        if (!$terminado) {
            return; // Job aún en proceso, seguir esperando.
        }

        $archivoId = $this->analisisJobArchivoId;
        $this->analisisJobArchivoId = null;
        $this->analizandoTdr = false;

        if ($terminado->estado === TdrAnalisis::ESTADO_FALLIDO) {
            $ref = 'TDR-' . strtoupper(Str::random(6));
            $this->notify(TdrAnalysisService::humanizeError($terminado->error ?? 'error desconocido', $ref), 'error');
            return;
        }

        // Exitoso → cargar el payload en el componente.
        $archivoPersistido = \App\Models\ContratoArchivo::find($archivoId);
        if (!$archivoPersistido) {
            return;
        }

        $analisis = $this->tdrAnalysisService->analizarArchivoLocal($archivoPersistido, $this->analisisContratoSnapshot);
        $archivoMeta = $this->resolveArchivoMeta($this->analisisContratoId ?? 0);
        $this->_mostrarResultadoAnalisis($analisis, $this->analisisContratoId ?? 0, $archivoMeta ?? [], $this->analisisContratoSnapshot);
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

    /**
     * Cotizar en el portal SEACE.
     *
     * 1. Verifica permiso 'cotizar-seace'
     * 2. Valida que el contrato tenga cotizar=true
     * 3. Abre modal guiado con pasos para cotizar en SEACE
     *
     * El portal SEACE usa navegación single-tab (sessionStorage):
     * abrir URLs externas pierde la sesión. El usuario debe buscar
     * el contrato dentro del portal usando el código de proceso.
     */
    public function cotizarEnSeace(int $idContrato): void
    {
        $this->cotizandoEnCurso = $idContrato;

        try {
            if (!$this->ensurePermission(
                'cotizar-seace',
                'Inicia sesión para cotizar en el portal SEACE.',
                'Tu cuenta no tiene acceso para cotizar. Solicita Proveedor Premium.'
            )) {
                return;
            }

            $contrato = $this->resolveContrato($idContrato);
            if (!$contrato) {
                $this->notify('No se encontraron datos del contrato.', 'warning');
                return;
            }

            // Verificar que el contrato acepta cotizaciones
            if (!($contrato['cotizar'] ?? false)) {
                $this->notify('Este proceso no está abierto para cotización actualmente.', 'warning');
                return;
            }

            $seaceBase = rtrim(config('services.seace.frontend_origin', 'https://prod6.seace.gob.pe'), '/');
            $urlLogin = "{$seaceBase}/auth-proveedor/";

            $this->dispatch('cotizar-seace-modal', [
                'urlLogin'        => $urlLogin,
                'idContrato'      => $idContrato,
                'desContratacion' => $contrato['desContratacion'] ?? "Contrato #{$idContrato}",
                'entidad'         => $contrato['nomEntidad'] ?? '',
            ]);
        } catch (Exception $e) {
            Log::error('BuscadorPublico:cotizarEnSeace', [
                'id_contrato' => $idContrato,
                'error' => $e->getMessage(),
            ]);
            $this->notify('No se pudo iniciar la cotización: ' . $e->getMessage(), 'error');
        } finally {
            $this->cotizandoEnCurso = null;
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
        $this->resultadoProforma = null;
        $this->proformaToken = null;
        $this->proformaContratoId = null;
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
            $ref = 'TDR-' . strtoupper(\Illuminate\Support\Str::random(6));
            Log::error("BuscadorPublico:compatibilidad [{$ref}]", [
                'ref' => $ref,
                'subscription_id' => $subscriptionId,
                'contrato' => $this->analisisContratoId,
                'error' => $e->getMessage(),
            ]);
            $this->notify(TdrAnalysisService::humanizeError($e->getMessage(), $ref), 'error');
        } finally {
            $this->compatibilidadEnCurso = null;
        }
    }

    public function generarProformaTecnica(int $idContrato): void
    {
        if (!$this->ensurePermission(
            'analyze-tdr',
            'Inicia sesión para generar la proforma técnica.',
            'Tu cuenta no tiene acceso a la proforma IA. Solicita Proveedor Premium.'
        )) {
            return;
        }

        $this->resultadoProforma = null;
        $this->proformaToken = null;
        $this->proformaContratoId = null;

        try {
            $user = Auth::user();
            $profile = SubscriberProfile::where('user_id', $user->id)->first();

            if (!$profile || blank($profile->company_copy)) {
                $this->notify('Configura el perfil de tu empresa antes de generar la proforma.', 'warning');
                return;
            }

            $archivoMeta = $this->resolveArchivoMeta($idContrato);
            if (!$archivoMeta) {
                $this->notify('No hay archivos disponibles para generar la proforma.', 'warning');
                return;
            }

            $contratoSnapshot = $this->resolveContrato($idContrato);
            $archivoPersistido = $this->publicTdrService->ensureLocalArchivo($idContrato, $archivoMeta, $contratoSnapshot);

            $resultado = $this->tdrAnalysisService->generarProforma(
                $archivoPersistido,
                $contratoSnapshot,
                $profile->company_name ?? '',
                $profile->company_copy
            );

            if (!($resultado['success'] ?? false)) {
                $this->notify($resultado['error'] ?? 'No se pudo generar la proforma.', 'error');
                return;
            }

            $proformaData = $resultado['data'] ?? [];
            $token = Str::uuid()->toString();

            Cache::put(
                "proforma:{$token}",
                $proformaData,
                now()->addHours(2)
            );

            $this->resultadoProforma = $proformaData;
            $this->proformaToken = $token;
            $this->proformaContratoId = $idContrato;

            $this->notify('Proforma técnica generada exitosamente.', 'success');
        } catch (\Throwable $e) {
            $ref = 'TDR-' . strtoupper(\Illuminate\Support\Str::random(6));
            Log::error("BuscadorPublico:generarProforma [{$ref}]", [
                'ref' => $ref,
                'id_contrato' => $idContrato,
                'error' => $e->getMessage(),
            ]);
            $this->notify(TdrAnalysisService::humanizeError($e->getMessage(), $ref), 'error');
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

    protected function notify(string $message, string $type = 'info', array $context = []): void
    {
        $this->tdrNotificacion = [
            'message' => $message,
            'type' => $type,
            'time' => now()->format('H:i:s'),
        ] + $context;
    }

    public function render()
    {
        return view('livewire.buscador-publico');
    }
}
