<?php

namespace App\Livewire;

use App\Jobs\AnalizarTdrMayorJob;
use App\Models\ContratoMayor;
use App\Models\ContratoSeguimientoMayor;
use App\Models\SubscriptionContractMatch;
use App\Models\SubscriberProfile;
use App\Models\TdrAnalisisMayor;
use App\Services\AnalizadorTDRService;
use App\Services\ArchiveExtractorService;
use App\Services\EntidadesMayoresService;
use App\Services\MayoresTdrService;
use App\Services\SeaceMayoresService;
use App\Services\Tdr\CompatibilityScoreService;
use App\Services\TdrAnalysisService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class BuscadorMayores extends Component
{
    use WithPagination;

    protected SeaceMayoresService $apiService;

    #[Url(as: 'q')]
    public string $palabraClave = '';

    #[Url(as: 'entidad')]
    public string $entidadTexto = '';
    public string $entidadFiltro = ''; // Solo se setea al hacer click en sugerencia

    #[Url(as: 'tipo')]
    public string $objetoContratacion = '';

    #[Url(as: 'estado')]
    public string $estado = '';
    public array $estadosDisponibles = [];

    #[Url]
    public int $pagina = 1;

    public int $registrosPorPagina = 15;

    public bool $buscando = false;
    public array $resultados = [];
    public array $paginacion = [];
    public string $mensajeError = '';
    public ?array $detalleContrato = null;
    public ?array $contratoAnalizado = null;

    // Autocompletado de entidades
    public array $entidadesSugeridas = [];
    public bool $mostrarSugerenciasEntidades = false; // Contexto del contrato en análisis (no mezclar con modal Ver)

    // ─── Modal Login ──────────────────────────────────────
    public bool $mostrarLoginModal = false;
    public string $loginEmail = '';
    public string $loginPassword = '';
    public bool $loginRemember = false;
    public ?string $loginError = null;
    public string $loginModalMensaje = '';

    // ─── Modal Premium ────────────────────────────────────
    public bool $mostrarAccesoRestringido = false;
    public string $accesoRestringidoMensaje = '';

    public function boot(SeaceMayoresService $apiService): void
    {
        $this->apiService = $apiService;
    }

    public function mount(): void
    {
        $this->cargarEstadosDisponibles();
        $this->buscar(1);
    }

    private function cargarEstadosDisponibles(): void
    {
        $this->estadosDisponibles = Cache::remember('contratos-mayores:estados-disponibles', 3600, function () {
            return ContratoMayor::query()
                ->select('estado')
                ->distinct()
                ->whereNotNull('estado')
                ->where('estado', '!=', '')
                ->orderByRaw("
                    CASE estado
                        WHEN 'CONVOCADO' THEN 1
                        WHEN 'ADJUDICADO' THEN 2
                        WHEN 'CONTRATADO' THEN 3
                        WHEN 'EN PROCESO' THEN 4
                        WHEN 'DESIERTO' THEN 5
                        WHEN 'CANCELADO' THEN 6
                        WHEN 'SUSPENDIDO' THEN 7
                        ELSE 99
                    END
                ")
                ->pluck('estado')
                ->toArray();
        });
    }

    // ══════════════════════════════════════════════════════
    // Búsqueda
    // ══════════════════════════════════════════════════════

    public function updatedRegistrosPorPagina(): void
    {
        $this->pagina = 1;
        $this->buscar(1);
    }

    public function updatedPalabraClave(): void
    {
        $this->pagina = 1;
        $this->buscar(1);
    }

    public function updatedEntidadTexto(): void
    {
        $this->buscarEntidades();
        // No buscar — solo actualizar sugerencias. El filtro se aplica al seleccionar.
    }

    public function updatedObjetoContratacion(): void
    {
        $this->pagina = 1;
        $this->buscar(1);
    }

    public function updatedEstado(): void
    {
        $this->pagina = 1;
        $this->buscar(1);
    }

    // ══════════════════════════════════════════════════════
    // Autocompletado de Entidades
    // ══════════════════════════════════════════════════════

    public function buscarEntidades(): void
    {
        $service = app(EntidadesMayoresService::class);
        $this->entidadesSugeridas = $service->buscar($this->entidadTexto);
        $this->mostrarSugerenciasEntidades = !empty($this->entidadesSugeridas);
    }

    public function seleccionarEntidad(string $nombre, string $ruc = ''): void
    {
        $this->entidadTexto = $nombre;
        $this->entidadFiltro = $nombre;
        $this->mostrarSugerenciasEntidades = false;
        $this->pagina = 1;
        $this->buscar(1);
    }

    public function limpiarEntidad(): void
    {
        $this->entidadTexto = '';
        $this->entidadFiltro = '';
        $this->entidadesSugeridas = [];
        $this->mostrarSugerenciasEntidades = false;
        $this->pagina = 1;
        $this->buscar(1);
    }

    public function cerrarSugerenciasEntidades(): void
    {
        $this->mostrarSugerenciasEntidades = false;
    }

    public function buscar(int $pagina = 1): void
    {
        $this->buscando = true;
        $this->pagina = $pagina;
        $this->mensajeError = '';

        $resultado = $this->apiService->buscar([
            'page' => $pagina,
            'paginateBy' => $this->registrosPorPagina,
            'source' => 'seace_v3',
            'query' => $this->palabraClave,
            'entidad' => $this->entidadFiltro,
            'objeto' => $this->objetoContratacion,
            'estado' => $this->estado,
        ]);

        if ($resultado['success']) {
            $this->resultados = array_map(function ($c) {
                $c['fecha_formateada'] = isset($c['fecha_publicacion'])
                    ? Carbon::parse($c['fecha_publicacion'])->format('d/m/Y')
                    : '';
                $c['fecha_inicio_fmt'] = isset($c['fecha_inicio'])
                    ? Carbon::parse($c['fecha_inicio'])->format('d/m/Y H:i')
                    : '';
                $c['fecha_fin_fmt'] = isset($c['fecha_fin'])
                    ? Carbon::parse($c['fecha_fin'])->format('d/m/Y H:i')
                    : '';
                $c['monto_formateado'] = ($c['valor_referencial'] ?? 0) > 0
                    ? 'S/ ' . number_format($c['valor_referencial'], 2)
                    : '---';
                return $c;
            }, $resultado['data']);
            $this->paginacion = $resultado['pagination'];
        } else {
            $this->resultados = [];
            $this->paginacion = [];
            $this->mensajeError = $resultado['message'];
        }

        $this->buscando = false;

        $this->cargarSeguimientosActivos();
    }

    private function cargarSeguimientosActivos(): void
    {
        if (!auth()->check()) {
            $this->seguimientosActivos = [];
            return;
        }

        $ocids = array_map(fn ($c) => $c['ocid'] ?? '', $this->resultados);
        $ocids = array_filter($ocids);

        if (empty($ocids)) {
            $this->seguimientosActivos = [];
            return;
        }

        $seguidos = ContratoSeguimientoMayor::where('user_id', auth()->id())
            ->whereIn('ocid', $ocids)
            ->pluck('ocid')
            ->toArray();

        $this->seguimientosActivos = array_fill_keys($seguidos, true);
    }

    public function irPagina(int $pagina): void
    {
        $this->buscar($pagina);
    }

    public function limpiarFiltros(): void
    {
        $this->palabraClave = '';
        $this->entidadTexto = '';
        $this->entidadFiltro = '';
        $this->objetoContratacion = '';
        $this->estado = '';
        $this->pagina = 1;
        $this->resultados = [];
        $this->paginacion = [];
        $this->mensajeError = '';
    }

    public function contarFiltrosActivos(): int
    {
        return count(array_filter([$this->palabraClave, $this->entidadFiltro, $this->objetoContratacion, $this->estado]));
    }

    // ══════════════════════════════════════════════════════
    // Detalle
    // ══════════════════════════════════════════════════════

    public function verDetalle(int $index): void
    {
        if (!$this->hasMayoresPermission('view-detalle-mayores')) return;

        $this->detalleContrato = $this->resultados[$index] ?? null;

        $ocid = $this->detalleContrato['ocid'] ?? null;
        $this->documentosCarpeta = $ocid ? $this->listarDocumentosCarpeta($ocid) : [];
    }

    public function cerrarDetalle(): void
    {
        $this->detalleContrato = null;
    }

    // ══════════════════════════════════════════════════════
    // Acciones (Seguimiento + Analizar)
    // ══════════════════════════════════════════════════════

    public function hacerSeguimiento(string $ocid): void
    {
        if (!$this->ensurePermission(
            'follow-mayores',
            'Inicia sesion para hacer seguimiento del proceso.',
            'Tu cuenta no tiene acceso al seguimiento. Solicita Proveedor Premium.'
        )) {
            return;
        }

        $contrato = collect($this->resultados)->first(fn ($c) => ($c['ocid'] ?? '') === $ocid);

        $yaExiste = ContratoSeguimientoMayor::where('user_id', auth()->id())->where('ocid', $ocid)->exists();

        ContratoSeguimientoMayor::firstOrCreate(
            ['user_id' => auth()->id(), 'ocid' => $ocid],
            [
                'codigo_proceso' => $contrato['nomenclatura'] ?? $ocid,
                'entidad_nombre' => $contrato['entidad_nombre'] ?? '',
                'objeto_contratacion' => $contrato['objeto_contratacion'] ?? '',
                'estado' => $contrato['estado'] ?? '',
                'fecha_publicacion' => $contrato['fecha_publicacion'] ?? now(),
                'valor_referencial' => $contrato['valor_referencial'] ?? 0,
                'moneda' => $contrato['moneda'] ?? '',
                'snapshot' => $contrato,
            ]
        );

        $this->seguimientosActivos[$ocid] = true;

        if ($yaExiste) {
            $this->notify('Ya estás siguiendo este proceso.', 'info');
        } else {
            $this->notify('Seguimiento activado. Recibirás notificaciones de cambios.', 'success');
        }
    }

    // ─── Análisis ────────────────────────────────────────
    public ?string $analizandoOcid = null;
    public ?array $resultadoAnalisis = null;
    public ?int $resultadoAnalisisMayorId = null;

    // ─── Direccionamiento ─────────────────────────────────
    public ?string $analizandoDireccOcid = null;
    public ?array $resultadoDireccionamiento = null;

    // ─── Compatibilidad ───────────────────────────────────
    public array $suscriptoresUsuarioMayor = [];
    public array $compatibilidadPorSuscriptorMayor = [];
    public ?int $compatibilidadEnCursoMayor = null;
    public ?string $compatibilidadOcidActual = null;

    // ─── Proforma ─────────────────────────────────────────
    public ?array $resultadoProformaMayor = null;
    public ?string $proformaTokenMayor = null;
    public ?string $proformaOcid = null;

    // ─── Cotizar ──────────────────────────────────────────
    public ?string $cotizandoEnCursoMayor = null;

    // ─── Extracción de ZIP/RAR ────────────────────────────
    public bool $mostrarSelectorDocumentos = false;
    public array $documentosExtraidos = [];
    public ?string $extraccionOcid = null;
    public ?string $extraccionPdfUrl = null;
    public ?array $extraccionCtx = null;
    public ?string $extrayendoOcid = null;
    public array $documentosCarpeta = [];
    public ?string $extraccionAccion = null;
    public array $seguimientosActivos = [];

    public function analizarTdr(string $pdfUrl): void
    {
        set_time_limit(600); // PDFs grandes pueden tardar

        $user = auth()->user();

        if (!$user) {
            $this->solicitarLogin('Inicia sesion para analizar TDR con IA.');
            return;
        }

        if (!$user->hasPermission('analyze-tdr-mayores')) {
            $this->mostrarFuncionalidadPremium = true;
            return;
        }

        $contrato = collect($this->resultados)->first(fn ($c) => ($c['url_documento'] ?? '') === $pdfUrl);
        $ocid = $contrato['ocid'] ?? null;

        if (!$ocid) {
            return;
        }

        // Guardar contexto para el modal de resultado
        $this->contratoAnalizado = $contrato;

        $ctx = [
            'entidad_nombre' => $contrato['entidad_nombre'] ?? '',
            'nomenclatura' => $contrato['nomenclatura'] ?? '',
            'descripcion_objeto' => $contrato['descripcion_objeto'] ?? '',
            'objeto_contratacion' => $contrato['objeto_contratacion'] ?? '',
            'valor_referencial' => $contrato['valor_referencial'] ?? 0,
            'moneda' => $contrato['moneda'] ?? '',
        ];

        // Check if already analyzed (general analysis)
        $existente = TdrAnalisisMayor::where('ocid', $ocid)
            ->where('estado', TdrAnalisisMayor::ESTADO_EXITOSO)
            ->where('tipo', TdrAnalisisMayor::TIPO_GENERAL)
            ->latest('analizado_en')
            ->first();

        if ($existente) {
            $this->resultadoAnalisis = $this->buildResultadoAnalisisMayor($existente);
            $this->loadUserCompatibilityMayor($ocid);
            return;
        }

        // Check if already in progress (reciente, no stale)
        $pendiente = TdrAnalisisMayor::where('ocid', $ocid)
            ->where('estado', TdrAnalisisMayor::ESTADO_PENDIENTE)
            ->first();

        if ($pendiente) {
            // Si el pendiente es viejo (>2 min), es stale — limpiarlo y seguir
            if ($pendiente->created_at->diffInMinutes(now()) > 2) {
                $pendiente->delete();
            } else {
                $this->analizandoOcid = $ocid;
                return;
            }
        }

        // ── Extract + dispatch via shared helper ──
        $this->extraccionAccion = 'analizar';
        $extr = $this->extraerDocumentoMayor($pdfUrl);

        if (!$extr['ok']) {
            $this->notify($extr['error'] ?? 'Error al procesar el documento.', 'error');
            return;
        }

        if ($extr['modal'] ?? false) {
            return;
        }

        $this->dispatchAnalisisMayor($ocid, $pdfUrl, $ctx, $user->id, $extr['path'], $extr['documento_id'] ?? null);
    }

    public function analizarDocumentoExtraidoMayor(int $index): void
    {
        $pdf = $this->documentosExtraidos[$index] ?? null;
        if (!$pdf || !$this->extraccionOcid) {
            $this->mostrarSelectorDocumentos = false;
            return;
        }

        $user = auth()->user();
        $documentoId = $pdf['documento_id'] ?? null;
        $this->mostrarSelectorDocumentos = false;

        $ocid = $this->extraccionOcid;
        $pdfUrl = $this->extraccionPdfUrl;
        $ctx = $this->extraccionCtx ?? [];
        $localPath = $pdf['path'];
        $accion = $this->extraccionAccion ?? 'analizar';

        switch ($accion) {
            case 'direccionamiento':
                $this->ejecutarDireccionamiento($ocid, $pdfUrl, $ctx, $user->id, $localPath);
                break;
            case 'proforma':
                $this->ejecutarProforma($ocid, $pdfUrl, $ctx, $user->id, $localPath);
                break;
            default:
                $this->dispatchAnalisisMayor($ocid, $pdfUrl, $ctx, $user->id, $localPath, $documentoId);
                break;
        }
    }

    private function buildResultadoAnalisisMayor(?TdrAnalisisMayor $analisis): ?array
    {
        if (!$analisis) {
            return null;
        }

        $this->dispatch('scroll-to-analisis-mayor');

        $ocid = $this->contratoAnalizado['ocid'] ?? null;
        if ($ocid) {
            $this->documentosCarpeta = $this->listarDocumentosCarpeta($ocid);
        }

        $payload = $analisis->payload ?? [];
        $data = $payload['data'] ?? $analisis->resumen ?? [];

        // Detectar formato: Mayores (>8UIT) vs Menores
        if (isset($data['metadatos_proceso']) || isset($data['anomalias_detectadas'])) {
            // ── Formato Mayores (Ley N° 32069) ──
            $meta = $data['metadatos_proceso'] ?? [];
            $calificacion = $data['requisitos_admisibilidad_y_calificacion'] ?? [];
            $result = array_merge($data, ['_formato' => 'mayores']);
            // Agregar resumen visible desde los metadatos
            if (empty($result['resumen_ejecutivo']) && !empty($meta['objeto_principal'])) {
                $monto = $meta['valor_monetario_referencial'] ?? '';
                $modalidad = $meta['modalidad_inferida'] ?? '';
                $result['resumen_ejecutivo'] = trim(
                    ($modalidad ? "Procedimiento: {$modalidad}. " : '') .
                    "Objeto: {$meta['objeto_principal']}" .
                    ($monto ? ". Monto referencial: {$monto}" : '')
                );
            }
            return $result;
        }

        // ── Formato Menores (legacy) ──
        return [
            'resumen_ejecutivo' => $data['resumen_ejecutivo']
                ?? $analisis->resumen
                ?? ($payload['data']['resumen_ejecutivo'] ?? null),
            'requisitos_calificacion' => $data['requisitos_tecnicos']
                ?? $analisis->requisitos_calificacion
                ?? ($payload['data']['requisitos_tecnicos'] ?? []),
            'reglas_ejecucion' => $data['reglas_de_negocio']
                ?? $analisis->reglas_ejecucion
                ?? ($payload['data']['reglas_de_negocio'] ?? []),
            'penalidades' => $data['politicas_y_penalidades']
                ?? $analisis->penalidades
                ?? ($payload['data']['politicas_y_penalidades'] ?? []),
            'presupuesto_referencial' => $data['presupuesto_referencial']
                ?? $analisis->monto_referencial_text
                ?? ($payload['data']['presupuesto_referencial'] ?? null),
        ];
    }

    private function dispatchAnalisisMayor(string $ocid, string $pdfUrl, array $ctx, int $userId, ?string $localPath = null, ?int $documentoId = null): void
    {
        $this->analizandoOcid = $ocid;
        $this->resultadoAnalisis = null;

        TdrAnalisisMayor::create([
            'ocid' => $ocid,
            'url_documento' => $pdfUrl,
            'estado' => TdrAnalisisMayor::ESTADO_PENDIENTE,
            'tipo' => TdrAnalisisMayor::TIPO_GENERAL,
            'documento_extraido_id' => $documentoId,
            'contexto_contrato' => $ctx,
            'requested_by_user_id' => $userId,
            'origin' => 'web',
        ]);

        // En local/dev o queue=sync: ejecutar sincrono para resultados inmediatos
        if (config('queue.default') === 'sync' || app()->isLocal()) {
            set_time_limit(600); // PDFs grandes con RAG pueden tomar varios minutos
            $service = app(MayoresTdrService::class);
            $resultado = $service->analizar($ocid, $pdfUrl, $ctx, $userId, $localPath);
            $this->analizandoOcid = null;

            if ($resultado['success']) {
                $analisis = TdrAnalisisMayor::where('ocid', $ocid)
                    ->where('tipo', TdrAnalisisMayor::TIPO_GENERAL)
                    ->where('estado', TdrAnalisisMayor::ESTADO_EXITOSO)
                    ->latest('analizado_en')
                    ->first();

                if ($analisis) {
                    $todos = TdrAnalisisMayor::where('ocid', $ocid)
                        ->where('tipo', TdrAnalisisMayor::TIPO_GENERAL)
                        ->where('estado', TdrAnalisisMayor::ESTADO_EXITOSO)
                        ->latest('analizado_en')
                        ->get();

                    if ($todos->count() > 1) {
                        $this->analisisDisponiblesMayor = $todos->map(fn($a) => [
                            'id' => $a->id,
                            'label' => $a->contexto_contrato['nomenclatura'] ?? 'Documento sin nombre',
                            'analizado_en' => $a->analizado_en?->format('d/m/Y H:i'),
                        ])->toArray();
                        $this->analisisSeleccionadoIdMayor = $analisis->id;
                    }

                    $this->resultadoAnalisisMayorId = $analisis->id;
                    $this->resultadoAnalisis = $this->buildResultadoAnalisisMayor($analisis);
                    $this->loadUserCompatibilityMayor($ocid);
                }
            } else {
                $this->notify($resultado['error'] ?? 'Error en el analisis.', 'error');
            }
            return;
        }

        // Produccion: despachar job async
        AnalizarTdrMayorJob::dispatch($ocid, $pdfUrl, $ctx, $userId, $localPath);
    }

    public function cancelarSelectorDocumentos(): void
    {
        $this->mostrarSelectorDocumentos = false;
        $this->documentosExtraidos = [];
        $this->extraccionOcid = null;
        $this->extraccionPdfUrl = null;
        $this->extraccionCtx = null;
        $this->extrayendoOcid = null;
    }

    // ─── Multi-análisis ──────────────────────────────────
    public ?array $analisisDisponiblesMayor = null;
    public ?int $analisisSeleccionadoIdMayor = null;

    public function checkAnalisisMayor(): void
    {
        if (!$this->analizandoOcid) {
            return;
        }

        $terminado = TdrAnalisisMayor::where('ocid', $this->analizandoOcid)
            ->whereIn('estado', [TdrAnalisisMayor::ESTADO_EXITOSO, TdrAnalisisMayor::ESTADO_FALLIDO])
            ->latest('analizado_en')
            ->first();

        if (!$terminado) {
            return;
        }

        $this->analizandoOcid = null;
        $ocid = $terminado->ocid;

        if ($terminado->estado === TdrAnalisisMayor::ESTADO_FALLIDO) {
            $this->notify('Error en el analisis: ' . ($terminado->error ?: 'Error desconocido'), 'error');
            return;
        }

        if ($terminado->estado === TdrAnalisisMayor::ESTADO_EXITOSO) {
            // Buscar TODOS los análisis exitosos para este OCID + tipo general
            $todos = TdrAnalisisMayor::where('ocid', $ocid)
                ->where('tipo', TdrAnalisisMayor::TIPO_GENERAL)
                ->where('estado', TdrAnalisisMayor::ESTADO_EXITOSO)
                ->latest('analizado_en')
                ->get();

            if ($todos->count() > 1) {
                $this->analisisDisponiblesMayor = $todos->map(fn($a) => [
                    'id' => $a->id,
                    'label' => $a->contexto_contrato['nomenclatura'] ?? 'Documento sin nombre',
                    'analizado_en' => $a->analizado_en?->format('d/m/Y H:i'),
                ])->toArray();
                $this->analisisSeleccionadoIdMayor = $terminado->id;
            } else {
                $this->analisisDisponiblesMayor = null;
                $this->analisisSeleccionadoIdMayor = null;
            }

            $this->resultadoAnalisisMayorId = $terminado->id;
            $this->resultadoAnalisis = $this->buildResultadoAnalisisMayor($terminado);
            $this->loadUserCompatibilityMayor($ocid);
            $this->dispatch('scroll-to-analisis-mayor');
        }
    }

    public function seleccionarAnalisisMayor(int $analisisId): void
    {
        $analisis = TdrAnalisisMayor::find($analisisId);
        if ($analisis && $analisis->estado === TdrAnalisisMayor::ESTADO_EXITOSO) {
            $this->analisisSeleccionadoIdMayor = $analisisId;
            $this->resultadoAnalisisMayorId = $analisisId;
            $this->resultadoAnalisis = $this->buildResultadoAnalisisMayor($analisis);
        }
    }

    public function cerrarAnalisis(): void
    {
        $this->resultadoAnalisis = null;
        $this->resultadoAnalisisMayorId = null;
        $this->analizandoOcid = null;
        $this->extrayendoOcid = null;
        $this->contratoAnalizado = null;
        $this->resultadoDireccionamiento = null;
        $this->suscriptoresUsuarioMayor = [];
        $this->compatibilidadPorSuscriptorMayor = [];
        $this->compatibilidadOcidActual = null;
        $this->resultadoProformaMayor = null;
        $this->proformaTokenMayor = null;
        $this->proformaOcid = null;
        $this->mostrarSelectorDocumentos = false;
        $this->documentosExtraidos = [];
        $this->extraccionOcid = null;
        $this->extraccionPdfUrl = null;
        $this->extraccionCtx = null;
        $this->analisisDisponiblesMayor = null;
        $this->analisisSeleccionadoIdMayor = null;
    }

    // ══════════════════════════════════════════════════════
    // Detectar Direccionamiento
    // ══════════════════════════════════════════════════════

    public function detectarDireccionamiento(string $pdfUrl): void
    {
        set_time_limit(600);
        $user = auth()->user();

        if (!$user) {
            $this->solicitarLogin('Inicia sesion para detectar direccionamiento.');
            return;
        }

        if (!$user->hasPermission('detect-direccionamiento-mayores')) {
            $this->mostrarFuncionalidadPremium = true;
            return;
        }

        $contrato = collect($this->resultados)->first(fn ($c) => ($c['url_documento'] ?? '') === $pdfUrl);
        $ocid = $contrato['ocid'] ?? null;

        if (!$ocid) {
            return;
        }

        // Check if already analyzed (direccionamiento)
        $existente = TdrAnalisisMayor::where('ocid', $ocid)
            ->where('tipo', TdrAnalisisMayor::TIPO_DIRECCIONAMIENTO)
            ->where('estado', TdrAnalisisMayor::ESTADO_EXITOSO)
            ->latest('analizado_en')
            ->first();

        if ($existente) {
            $this->resultadoDireccionamiento = $existente->payload['data'] ?? $existente->resumen;
            return;
        }

        $this->analizandoDireccOcid = $ocid;
        $this->resultadoDireccionamiento = null;

        $ctx = [
            'entidad_nombre' => $contrato['entidad_nombre'] ?? '',
            'nomenclatura' => $contrato['nomenclatura'] ?? '',
            'descripcion_objeto' => $contrato['descripcion_objeto'] ?? '',
            'objeto_contratacion' => $contrato['objeto_contratacion'] ?? '',
            'valor_referencial' => $contrato['valor_referencial'] ?? 0,
            'moneda' => $contrato['moneda'] ?? '',
        ];

        try {
            // ── Extraer documento (soporta ZIP/RAR) ──
            $this->extraccionAccion = 'direccionamiento';
            $extr = $this->extraerDocumentoMayor($pdfUrl);

            if (!$extr['ok']) {
                $this->notify($extr['error'] ?? 'Error al procesar el documento.', 'error');
                $this->analizandoDireccOcid = null;
                return;
            }

            if ($extr['modal'] ?? false) {
                return;
            }

            $localPath = $extr['path'];

            $this->ejecutarDireccionamientoInterno($ocid, $pdfUrl, $ctx, $user->id, $localPath);
        } catch (\Throwable $e) {
            Log::error('BuscadorMayores:direccionamiento', [
                'ocid' => $ocid ?? null,
                'error' => $e->getMessage(),
            ]);
            $this->notify('Error al analizar direccionamiento: ' . $e->getMessage(), 'error');
        } finally {
            $this->analizandoDireccOcid = null;
        }
    }

    private function ejecutarDireccionamiento(string $ocid, string $pdfUrl, array $ctx, int $userId, string $localPath): void
    {
        $this->analizandoDireccOcid = $ocid;
        $this->resultadoDireccionamiento = null;

        try {
            $this->ejecutarDireccionamientoInterno($ocid, $pdfUrl, $ctx, $userId, $localPath);
        } catch (\Throwable $e) {
            Log::error('BuscadorMayores:direccionamiento', [
                'ocid' => $ocid,
                'error' => $e->getMessage(),
            ]);
            $this->notify('Error al analizar direccionamiento: ' . $e->getMessage(), 'error');
        } finally {
            $this->analizandoDireccOcid = null;
        }
    }

    private function ejecutarDireccionamientoInterno(string $ocid, string $pdfUrl, array $ctx, int $userId, string $localPath): void
    {
        $analizador = new AnalizadorTDRService();
        $resultado = $analizador->analyzeDireccionamiento($localPath, 'mayores');

        if (!$resultado['success']) {
            TdrAnalisisMayor::updateOrCreate(
                ['ocid' => $ocid, 'tipo' => TdrAnalisisMayor::TIPO_DIRECCIONAMIENTO],
                [
                    'url_documento' => $pdfUrl,
                    'estado' => TdrAnalisisMayor::ESTADO_FALLIDO,
                    'error' => $resultado['error'] ?? 'Error desconocido',
                    'contexto_contrato' => $ctx,
                    'analizado_en' => now(),
                    'requested_by_user_id' => $userId,
                    'origin' => 'web',
                ]
            );
            $this->notify($resultado['error'] ?? 'Error al analizar direccionamiento.', 'error');
            return;
        }

        TdrAnalisisMayor::updateOrCreate(
            ['ocid' => $ocid, 'tipo' => TdrAnalisisMayor::TIPO_DIRECCIONAMIENTO],
            [
                'url_documento' => $pdfUrl,
                'estado' => TdrAnalisisMayor::ESTADO_EXITOSO,
                'contexto_contrato' => $ctx,
                'payload' => $resultado,
                'analizado_en' => now(),
                'requested_by_user_id' => $userId,
                'origin' => 'web',
            ]
        );

        $this->resultadoDireccionamiento = $resultado['data'] ?? [];
        $this->notify('Análisis de direccionamiento completado.', 'success');
    }

    public function cerrarDireccionamiento(): void
    {
        $this->resultadoDireccionamiento = null;
        $this->analizandoDireccOcid = null;
    }

    // ══════════════════════════════════════════════════════
    // Compatibilidad
    // ══════════════════════════════════════════════════════

    public function calcularCompatibilidadMayor(int $subscriptionId): void
    {
        $this->compatibilidadEnCursoMayor = $subscriptionId;

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

            $ocid = $this->compatibilidadOcidActual;
            $contrato = collect($this->resultados)->first(fn ($c) => ($c['ocid'] ?? '') === $ocid);

            if (!$contrato) {
                $this->notify('No se pudo resolver el contrato para compatibilidad.', 'error');
                return;
            }

            $payload = $this->resultadoAnalisis ?? [];
            $compatService = new CompatibilityScoreService(new \App\Services\AccountCompatibilityService());
            $compatResult = $compatService->ensureScore($subscription, $contrato, $payload);

            if (!empty($compatResult['error'])) {
                $this->notify($compatResult['error'], 'warning');
            }

            $match = $compatResult['match'] ?? null;
            if ($match) {
                $payloadMatch = $match->analisis_payload ?? [];
                $this->compatibilidadPorSuscriptorMayor[$subscription->id] = [
                    'score' => $match->score ?? (is_numeric($payloadMatch['score'] ?? null) ? (float) $payloadMatch['score'] : null),
                    'nivel' => $payloadMatch['nivel'] ?? null,
                    'explicacion' => $payloadMatch['explicacion'] ?? null,
                    'actualizado' => $match->analizado_en?->format('d/m/Y H:i') ?? ($payloadMatch['timestamp'] ?? null),
                ];
            }

            $label = $subscription->nombre ?: 'Chat ' . $subscription->chat_id;
            $this->notify('Compatibilidad actualizada para ' . $label . '.', 'success');
        } catch (\Throwable $e) {
            $ref = 'TDR-' . strtoupper(Str::random(6));
            Log::error("BuscadorMayores:compatibilidad [{$ref}]", [
                'ref' => $ref,
                'subscription_id' => $subscriptionId,
                'ocid' => $this->compatibilidadOcidActual,
                'error' => $e->getMessage(),
            ]);
            $this->notify(TdrAnalysisService::humanizeError($e->getMessage(), $ref), 'error');
        } finally {
            $this->compatibilidadEnCursoMayor = null;
        }
    }

    protected function loadUserCompatibilityMayor(string $ocid): void
    {
        $this->suscriptoresUsuarioMayor = [];
        $this->compatibilidadPorSuscriptorMayor = [];
        $this->compatibilidadOcidActual = $ocid;

        $user = Auth::user();
        if (!$user) {
            return;
        }

        $subscriptions = $user->telegramSubscriptions()
            ->activas()
            ->orderByRaw('IFNULL(nombre, chat_id) asc')
            ->get();

        $this->suscriptoresUsuarioMayor = $subscriptions->map(function ($subscription) {
            return [
                'id' => $subscription->id,
                'label' => $subscription->nombre ?: 'Chat ' . $subscription->chat_id,
                'has_copy' => !blank($subscription->company_copy),
            ];
        })->toArray();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $matches = SubscriptionContractMatch::query()
            ->where('ocid', $ocid)
            ->whereIn('telegram_subscription_id', $subscriptions->pluck('id'))
            ->get()
            ->keyBy('telegram_subscription_id');

        foreach ($subscriptions as $subscription) {
            $match = $matches->get($subscription->id);
            if ($match) {
                $p = $match->analisis_payload ?? [];
                $this->compatibilidadPorSuscriptorMayor[$subscription->id] = [
                    'score' => $match->score ?? (is_numeric($p['score'] ?? null) ? (float) $p['score'] : null),
                    'nivel' => $p['nivel'] ?? null,
                    'explicacion' => $p['explicacion'] ?? null,
                    'actualizado' => $match->analizado_en?->format('d/m/Y H:i') ?? ($p['timestamp'] ?? null),
                ];
            }
        }
    }

    // ══════════════════════════════════════════════════════
    // Proforma Técnica
    // ══════════════════════════════════════════════════════

    public function generarProformaTecnicaMayor(string $pdfUrl): void
    {
        set_time_limit(600);
        if (!$this->ensurePermission(
            'create-proforma-mayores',
            'Inicia sesión para generar la proforma técnica.',
            'Tu cuenta no tiene acceso a la proforma IA. Solicita Proveedor Premium.'
        )) {
            return;
        }

        $this->resultadoProformaMayor = null;
        $this->proformaTokenMayor = null;
        $this->proformaOcid = null;

        try {
            $user = Auth::user();
            $profile = SubscriberProfile::where('user_id', $user->id)->first();

            if (!$profile || blank($profile->company_copy)) {
                $this->notify('Configura el perfil de tu empresa antes de generar la proforma.', 'warning');
                return;
            }

            $contrato = collect($this->resultados)->first(fn ($c) => ($c['url_documento'] ?? '') === $pdfUrl);
            $ocid = $contrato['ocid'] ?? null;

            if (!$ocid) {
                $this->notify('No se pudo identificar el contrato.', 'error');
                return;
            }

            // ── Extraer documento (soporta ZIP/RAR) ──
            $this->extraccionAccion = 'proforma';
            $extr = $this->extraerDocumentoMayor($pdfUrl);

            if (!$extr['ok']) {
                $this->notify($extr['error'] ?? 'Error al procesar el documento.', 'error');
                return;
            }

            if ($extr['modal'] ?? false) {
                return;
            }

            $localPath = $extr['path'];

            $this->ejecutarProformaInterno($ocid, $pdfUrl, $profile->company_name ?? '', $profile->company_copy, $localPath);
        } catch (\Throwable $e) {
            $ref = 'TDR-' . strtoupper(Str::random(6));
            Log::error("BuscadorMayores:proforma [{$ref}]", [
                'ref' => $ref,
                'ocid' => $this->proformaOcid,
                'error' => $e->getMessage(),
            ]);
            $this->notify(TdrAnalysisService::humanizeError($e->getMessage(), $ref), 'error');
        }
    }

    private function ejecutarProforma(string $ocid, string $pdfUrl, array $ctx, int $userId, string $localPath): void
    {
        $user = Auth::user();
        $profile = SubscriberProfile::where('user_id', $user->id)->first();

        if (!$profile || blank($profile->company_copy)) {
            $this->notify('Configura el perfil de tu empresa antes de generar la proforma.', 'warning');
            return;
        }

        try {
            $this->ejecutarProformaInterno($ocid, $pdfUrl, $profile->company_name ?? '', $profile->company_copy, $localPath);
        } catch (\Throwable $e) {
            $ref = 'TDR-' . strtoupper(Str::random(6));
            Log::error("BuscadorMayores:proforma [{$ref}]", [
                'ref' => $ref,
                'ocid' => $ocid,
                'error' => $e->getMessage(),
            ]);
            $this->notify(TdrAnalysisService::humanizeError($e->getMessage(), $ref), 'error');
        }
    }

    private function ejecutarProformaInterno(string $ocid, string $pdfUrl, string $companyName, string $companyCopy, string $localPath): void
    {
        $analizador = new AnalizadorTDRService();
        $resultado = $analizador->analyzeProforma(
            $localPath,
            $companyName,
            $companyCopy,
            'mayores'
        );

        if (!($resultado['success'] ?? false)) {
            $this->notify($resultado['error'] ?? 'No se pudo generar la proforma.', 'error');
            return;
        }

        $proformaData = $resultado['data'] ?? [];
        $token = Str::uuid()->toString();

        Cache::put("proforma:{$token}", $proformaData, now()->addHours(2));

        $this->resultadoProformaMayor = $proformaData;
        $this->proformaTokenMayor = $token;
        $this->proformaOcid = $ocid;

        $this->notify('Proforma técnica generada exitosamente.', 'success');
    }

    public function cerrarProformaMayor(): void
    {
        $this->resultadoProformaMayor = null;
        $this->proformaTokenMayor = null;
        $this->proformaOcid = null;
    }

    public ?string $mostrandoPartesOcid = null;
    public array $partesProceso = [];
    public ?string $partesEntidadNombre = null;

    public function verPartesMayor(string $ocid): void
    {
        if (!$this->hasMayoresPermission('view-partes-mayores')) return;

        $contrato = ContratoMayor::where('ocid', $ocid)->first();
        if (!$contrato) {
            $this->notify('No se encontraron datos del proceso.', 'warning');
            return;
        }

        $raw = is_string($contrato->datos_raw) ? json_decode($contrato->datos_raw, true) : $contrato->datos_raw;
        $parties = $raw['parties'] ?? [];

        $this->partesProceso = array_map(function ($p) {
            $ruc = '';
            if (!empty($p['additionalIdentifiers'])) {
                foreach ($p['additionalIdentifiers'] as $ai) {
                    if (($ai['scheme'] ?? '') === 'PE-RUC') {
                        $ruc = $ai['id'] ?? '';
                        break;
                    }
                }
            }
            // Si no hay RUC en additionalIdentifiers, tomarlo del identifier si es PE-RUC
            if (empty($ruc) && ($p['identifier']['scheme'] ?? '') === 'PE-RUC') {
                $ruc = $p['identifier']['id'] ?? '';
            }

            $roles = array_map(fn ($r) => match ($r) {
                'buyer' => 'Comprador',
                'procuringEntity' => 'Entidad Convocante',
                'supplier' => 'Ganador',
                'tenderer' => 'Postor',
                'funder' => 'Financista',
                default => $r,
            }, $p['roles'] ?? []);

            $telefono = $p['contactPoint']['telephone'] ?? '';
            if (stripos($telefono, 'Ver dato') !== false || stripos($telefono, 'ficha del proveedor') !== false) {
                $telefono = '';
            }

            return [
                'nombre' => $p['name'] ?? '',
                'ruc' => $ruc,
                'identificador' => $p['identifier']['id'] ?? '',
                'roles' => $roles,
                'direccion' => $p['address']['streetAddress'] ?? '',
                'localidad' => $p['address']['locality'] ?? '',
                'region' => $p['address']['region'] ?? '',
                'departamento' => $p['address']['department'] ?? '',
                'telefono' => $telefono,
                'url' => $p['contactPoint']['url'] ?? '',
            ];
        }, $parties);

        $this->partesEntidadNombre = $contrato->entidad_nombre ?? '';
        $this->mostrandoPartesOcid = $ocid;
    }

    public function cerrarPartesMayor(): void
    {
        $this->mostrandoPartesOcid = null;
        $this->partesProceso = [];
    }

    public function cotizarEnSeaceMayor(string $ocid): void
    {
        $this->cotizandoEnCursoMayor = $ocid;

        try {
            if (!$this->ensurePermission(
                'cotizar-seace',
                'Inicia sesión para cotizar en el portal SEACE.',
                'Tu cuenta no tiene acceso para cotizar. Solicita Proveedor Premium.'
            )) {
                return;
            }

            $contrato = collect($this->resultados)->first(fn ($c) => ($c['ocid'] ?? '') === $ocid);
            if (!$contrato) {
                $this->notify('No se encontraron datos del contrato.', 'warning');
                return;
            }

            $seaceBase = rtrim(config('services.seace.frontend_origin', 'https://prod6.seace.gob.pe'), '/');
            $urlLogin = "{$seaceBase}/auth-proveedor/";

            $this->dispatch('cotizar-seace-modal', [
                'urlLogin' => $urlLogin,
                'idContrato' => 0,
                'desContratacion' => $contrato['nomenclatura'] ?? "Contrato {$ocid}",
                'entidad' => $contrato['entidad_nombre'] ?? '',
            ]);
        } catch (\Throwable $e) {
            Log::error('BuscadorMayores:cotizar', [
                'ocid' => $ocid,
                'error' => $e->getMessage(),
            ]);
            $this->notify('No se pudo iniciar la cotización: ' . $e->getMessage(), 'error');
        } finally {
            $this->cotizandoEnCursoMayor = null;
        }
    }

    protected function ensurePermission(string $permission, string $loginMessage, string $deniedMessage): bool
    {
        $user = Auth::user();
        if (!$user) {
            $this->solicitarLogin($loginMessage);
            return false;
        }

        if (!$user->hasPermission($permission)) {
            $this->mostrarFuncionalidadPremium = true;
            return false;
        }

        return true;
    }

    protected function hasMayoresPermission(string $permission): bool
    {
        $user = Auth::user();
        if (!$user) {
            $this->solicitarLogin('Inicia sesión para usar esta función.');
            return false;
        }

        if (!$user->hasPermission($permission)) {
            $this->mostrarFuncionalidadPremium = true;
            return false;
        }

        return true;
    }

    public bool $mostrarFuncionalidadPremium = false;

    public function cerrarFuncionalidadPremium(): void
    {
        $this->mostrarFuncionalidadPremium = false;
    }

    // ══════════════════════════════════════════════════════
    // Login Modal
    // ══════════════════════════════════════════════════════

    protected function solicitarLogin(string $mensaje): void
    {
        $this->loginModalMensaje = $mensaje;
        $this->mostrarLoginModal = true;
        $this->loginError = null;
    }

    public function cerrarLoginModal(): void
    {
        $this->mostrarLoginModal = false;
        $this->loginEmail = '';
        $this->loginPassword = '';
        $this->loginError = null;
    }

    public function login(): void
    {
        $credentials = [
            'email' => $this->loginEmail,
            'password' => $this->loginPassword,
        ];

        if (!Auth::attempt($credentials, $this->loginRemember)) {
            throw ValidationException::withMessages([
                'email' => 'Las credenciales no coinciden.',
            ]);
        }

        session()->regenerate();

        $this->mostrarLoginModal = false;
        $this->loginEmail = '';
        $this->loginPassword = '';
        $this->loginError = null;

        $this->dispatch('login-redirect', url: url()->previous());
    }

    // ══════════════════════════════════════════════════════
    // Premium Modal
    // ══════════════════════════════════════════════════════

    public function cerrarAccesoRestringido(): void
    {
        $this->mostrarAccesoRestringido = false;
    }

    // ─── Notificación ────────────────────────────────────
    public ?array $notificacion = null;

    protected function notify(string $message, string $type = 'info'): void
    {
        $this->notificacion = [
            'message' => $message,
            'type' => $type,
        ];
    }

    public function render()
    {
        return view('livewire.buscador-mayores');
    }

    /**
     * Extrae el documento usando ArchiveExtractorService (soporta ZIP/RAR).
     * Retorna ['ok'=>true, 'path'=>..., 'documento_id'=>...] para doc único,
     * o ['ok'=>true, 'modal'=>true] si necesita que el usuario elija entre PDFs.
     */
    private function extraerDocumentoMayor(string $pdfUrl): array
    {
        $contrato = collect($this->resultados)->first(fn ($c) => ($c['url_documento'] ?? '') === $pdfUrl);
        $ocid = $contrato['ocid'] ?? null;

        if (!$ocid) {
            return ['ok' => false, 'error' => 'No se pudo identificar el contrato.'];
        }

        $this->extrayendoOcid = $ocid;
        $extractor = new ArchiveExtractorService();
        $result = $extractor->process($pdfUrl, $ocid, 'mayores');

        if ($result['type'] === 'error') {
            $this->extrayendoOcid = null;
            return ['ok' => false, 'error' => $result['message']];
        }

        if ($result['type'] === 'archive' && count($result['pdfs']) > 1) {
            $this->extrayendoOcid = null;
            $this->documentosExtraidos = $result['pdfs'];
            $this->extraccionOcid = $ocid;
            $this->extraccionPdfUrl = $pdfUrl;
            $this->extraccionCtx = [
                'entidad_nombre' => $contrato['entidad_nombre'] ?? '',
                'nomenclatura' => $contrato['nomenclatura'] ?? '',
                'descripcion_objeto' => $contrato['descripcion_objeto'] ?? '',
                'objeto_contratacion' => $contrato['objeto_contratacion'] ?? '',
                'valor_referencial' => $contrato['valor_referencial'] ?? 0,
                'moneda' => $contrato['moneda'] ?? '',
            ];
            $this->mostrarSelectorDocumentos = true;
            return ['ok' => true, 'modal' => true];
        }

        $localPath = ($result['type'] === 'pdf') ? $result['path'] : $result['pdfs'][0]['path'];
        $documentoId = ($result['type'] === 'archive') ? ($result['pdfs'][0]['documento_id'] ?? null) : null;
        $this->extrayendoOcid = null;

        return ['ok' => true, 'path' => $localPath, 'documento_id' => $documentoId];
    }

    private function listarDocumentosCarpeta(string $ocid): array
    {
        $dir = storage_path('app/tdr-extracted/mayores/' . $ocid);

        if (!is_dir($dir)) {
            return [];
        }

        $docs = [];
        $this->scanDirRecursive($dir, $dir, $docs);

        return $docs;
    }

    private function scanDirRecursive(string $dir, string $baseDir, array &$docs): void
    {
        $items = scandir($dir) ?: [];

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $dir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($fullPath)) {
                $this->scanDirRecursive($fullPath, $baseDir, $docs);
            } elseif (is_file($fullPath)) {
                $relativeKey = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $fullPath);
                $relativeKey = str_replace('\\', '/', $relativeKey);
                $docs[] = [
                    'filename' => $relativeKey,
                    'size'     => filesize($fullPath),
                ];
            }
        }
    }
}
