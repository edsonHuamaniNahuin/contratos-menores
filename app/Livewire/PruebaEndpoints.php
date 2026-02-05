<?php

namespace App\Livewire;

use App\Models\CuentaSeace;
use App\Services\TelegramNotificationService;
use App\Services\Tdr\TdrDocumentService;
use App\Services\Tdr\TdrPersistenceService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Livewire\Component;
use Exception;

class PruebaEndpoints extends Component
{
    public ?int $cuentaSeleccionada = null;

    /** @var Collection<int, CuentaSeace> */
    public Collection $cuentas;

    // Resultados de las pruebas
    public ?array $resultadoLogin = null;
    public ?array $resultadoRefresh = null;
    public ?array $resultadoBuscador = null;
    public ?array $resultadoMaestras = null;

    // Loading states
    public bool $loadingLogin = false;
    public bool $loadingRefresh = false;
    public bool $loadingBuscador = false;
    public bool $loadingMaestras = false;

    // Parámetros de búsqueda
    public array $parametrosBuscador = [
        'pagina' => 1,
        'registros' => 10,
        'estado' => '',
        'texto' => '',
        'entidad' => '',
        'codigo_entidad' => '',
        'departamento' => '',
        'provincia' => '',
        'distrito' => '',
    ];

    public array $entidadesSugeridas = [];
    public bool $buscandoEntidades = false;

    // Filtros geográficos en cascada
    public array $departamentos = [];
    public array $provincias = [];
    public array $distritos = [];
    public bool $cargandoDepartamentos = false;
    public bool $cargandoProvincias = false;
    public bool $cargandoDistritos = false;

    // Búsqueda de texto en filtros geográficos
    public string $busquedaDepartamento = '';
    public string $busquedaProvincia = '';
    public string $busquedaDistrito = '';

    // Archivos TDR
    public ?array $resultadoArchivos = null;
    public bool $loadingArchivos = false;
    public ?int $contratoSeleccionadoArchivos = null;
    public ?array $contratoSeleccionadoData = null; // Datos completos del contrato seleccionado

    // Análisis TDR con IA
    public ?array $resultadoAnalisis = null;
    public bool $loadingAnalisis = false;

    public string $tipoMaestra = 'objetos';

    // URL de la API SEACE REAL
    protected string $baseUrl = 'https://prod6.seace.gob.pe/v1/s8uit-services';

    // URL base para Referer (evitar hardcodeo)
    protected string $refererBase = 'https://prod6.seace.gob.pe/auth-proveedor';

    public function mount($cuentaId = null)
    {
        $this->cuentas = collect(); // Inicializar como colección vacía
        $this->cargarCuentas();

        if ($cuentaId) {
            $this->cuentaSeleccionada = $cuentaId;
        } elseif ($this->cuentas instanceof Collection && $this->cuentas->isNotEmpty()) {
            // Seleccionar la primera cuenta activa
            $this->cuentaSeleccionada = $this->cuentas->first()->id;
        }

        // Cargar departamentos al inicio
        $this->cargarDepartamentos();
    }

    public function cargarCuentas()
    {
        // Mostrar TODAS las cuentas, ordenadas por activa primero
        $this->cuentas = CuentaSeace::orderBy('activa', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getCuentaActual()
    {
        if (!$this->cuentaSeleccionada) {
            return null;
        }

        return CuentaSeace::find($this->cuentaSeleccionada);
    }

    public function probarLogin()
    {
        $this->loadingLogin = true;
        $this->resultadoLogin = null;

        try {
            $cuenta = $this->getCuentaActual();

            if (!$cuenta) {
                $this->resultadoLogin = [
                    'success' => false,
                    'error' => 'No hay cuenta seleccionada',
                ];
                return;
            }

            // Validar que la cuenta tiene username y password
            if (!$cuenta->username) {
                $this->resultadoLogin = [
                    'success' => false,
                    'error' => 'La cuenta no tiene username configurado',
                ];
                return;
            }

            $passwordDescifrado = $cuenta->getPasswordDescifrado();
            if (!$passwordDescifrado) {
                $this->resultadoLogin = [
                    'success' => false,
                    'error' => 'La cuenta no tiene password configurado o no se pudo descifrar',
                ];
                return;
            }

            // Headers MÍNIMOS como Postman
            $payload = [
                'username' => $cuenta->username,
                'password' => $passwordDescifrado,
            ];

            Log::info('🔹 INICIO LOGIN', [
                'url' => "{$this->baseUrl}/seguridadproveedor/seguridad/validausuariornp",
                'username' => $cuenta->username,
                'password_length' => strlen($passwordDescifrado),
                'password_preview' => substr($passwordDescifrado, 0, 3) . '***',
                'headers' => $this->getHeaders("{$this->refererBase}/busqueda"),
                'payload' => $payload,
            ]);

            $response = Http::withHeaders($this->getHeaders("{$this->refererBase}/busqueda"))
                ->timeout(30)
                ->post("{$this->baseUrl}/seguridadproveedor/seguridad/validausuariornp", $payload);

            Log::info('🔹 RESPUESTA HTTP RECIBIDA', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body_preview' => substr($response->body(), 0, 500),
            ]);

            $data = $response->json();

            Log::info('🔹 JSON PARSEADO', [
                'status' => $response->status(),
                'data' => $data,
                'success' => $response->successful(),
            ]);

            if ($response->successful() && isset($data['token'])) {
                // Guardar tokens en la cuenta
                $cuenta->actualizarTokens(
                    $data['token'],
                    $data['refreshToken'] ?? null,
                    300 // 5 minutos
                );

                $cuenta->registrarLogin(true);

                $this->resultadoLogin = [
                    'success' => true,
                    'message' => $data['mensaje'] ?? 'Login exitoso',
                    'data' => [
                        'token' => substr($data['token'], 0, 50) . '...',
                        'refreshToken' => isset($data['refreshToken']) ? substr($data['refreshToken'], 0, 50) . '...' : null,
                        'respuesta' => $data['respuesta'] ?? true,
                    ],
                    'raw' => $data,
                ];

                $this->cargarCuentas(); // Recargar para actualizar info
            } else {
                $cuenta->registrarLogin(false);

                // Mensaje de error descriptivo
                $errorMsg = $data['mensaje'] ?? $data['message'] ?? 'Error desconocido';

                // Agregar contexto según el código de error
                if ($response->status() === 403) {
                    if (strpos($errorMsg, 'Usuario o clave') !== false || strpos($errorMsg, 'inválido') !== false) {
                        $errorMsg = '🔐 Usuario o contraseña incorrectos. Verifica tus credenciales.';
                    } else {
                        $errorMsg = '🚫 Acceso denegado. ' . $errorMsg;
                    }
                } elseif ($response->status() === 401) {
                    $errorMsg = '⏰ Token expirado o inválido. ' . $errorMsg;
                } elseif ($response->status() >= 500) {
                    $errorMsg = '🔧 Error del servidor SEACE. ' . $errorMsg;
                }

                $this->resultadoLogin = [
                    'success' => false,
                    'error' => $errorMsg,
                    'status' => $response->status(),
                    'raw' => $data,
                    'debug' => [
                        'errorCode' => $data['errorCode'] ?? null,
                        'backendMessage' => $data['backendMessage'] ?? null,
                    ],
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error en probarLogin', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'cuenta_id' => $this->cuentaSeleccionada,
            ]);

            $this->resultadoLogin = [
                'success' => false,
                'error' => 'Excepción: ' . $e->getMessage(),
                'trace' => $e->getFile() . ':' . $e->getLine(),
            ];
        } finally {
            $this->loadingLogin = false;
        }
    }

    public function probarRefresh()
    {
        $this->loadingRefresh = true;
        $this->resultadoRefresh = null;

        try {
            $cuenta = $this->getCuentaActual();

            if (!$cuenta) {
                $this->resultadoRefresh = [
                    'success' => false,
                    'error' => 'No hay cuenta seleccionada',
                ];
                return;
            }

            if (!$cuenta->access_token) {
                $this->resultadoRefresh = [
                    'success' => false,
                    'error' => 'No hay access token disponible. Realiza login primero.',
                ];
                return;
            }

            if (!$cuenta->refresh_token) {
                $this->resultadoRefresh = [
                    'success' => false,
                    'error' => 'No hay refresh token disponible. Realiza login primero.',
                ];
                return;
            }

            // ✅ CORRECCIÓN: Enviar refreshToken y username en el body
            $payload = [
                'refreshToken' => $cuenta->refresh_token,
                'username' => $cuenta->username,
            ];

            Log::info('🔹 INICIO REFRESH TOKEN', [
                'url' => "{$this->baseUrl}/seguridadproveedor/seguridad/tokens/refresh",
                'refresh_token_length' => strlen($cuenta->refresh_token),
                'refresh_token_preview' => substr($cuenta->refresh_token, 0, 30) . '...',
                'username' => $cuenta->username,
                'payload' => $payload,
                'headers' => $this->getHeaders("{$this->refererBase}/cotizacion/contrataciones"),
            ]);

            $response = Http::withHeaders($this->getHeaders("{$this->refererBase}/cotizacion/contrataciones"))
                ->timeout(30)
                ->post("{$this->baseUrl}/seguridadproveedor/seguridad/tokens/refresh", $payload);

            Log::info('🔹 RESPUESTA REFRESH RECIBIDA', [
                'status' => $response->status(),
                'body_preview' => substr($response->body(), 0, 500),
            ]);

            $data = $response->json();

            Log::info('🔹 REFRESH JSON PARSEADO', [
                'status' => $response->status(),
                'data' => $data,
                'success' => $response->successful(),
            ]);

            if ($response->successful() && isset($data['token'])) {
                // Actualizar tokens (ambos cambian)
                $cuenta->actualizarTokens(
                    $data['token'],
                    $data['refreshToken'] ?? null,
                    300 // 5 minutos
                );

                $this->resultadoRefresh = [
                    'success' => true,
                    'message' => $data['mensaje'] ?? 'Token refrescado exitosamente',
                    'data' => [
                        'token' => substr($data['token'], 0, 50) . '...',
                        'refreshToken' => isset($data['refreshToken']) ? substr($data['refreshToken'], 0, 50) . '...' : 'Sin cambios',
                        'respuesta' => $data['respuesta'] ?? true,
                    ],
                    'raw' => $data,
                ];

                $this->cargarCuentas();
            } else {
                // Mensaje de error descriptivo
                $errorMsg = $data['mensaje'] ?? $data['message'] ?? $data['error_description'] ?? $data['error'] ?? 'Error desconocido';

                // Agregar contexto según el código de error
                if ($response->status() === 401) {
                    $errorMsg = '⏰ Token inválido o ya no es refrescable. ' . $errorMsg;
                } elseif ($response->status() === 403) {
                    $errorMsg = '🚫 Acceso denegado al refrescar. ' . $errorMsg;
                } elseif ($response->status() >= 500) {
                    // Detectar si el error es porque el token NO está expirado
                    if (strpos($errorMsg, 'servicio no est') !== false ||
                        strpos($errorMsg, 'no disponible') !== false) {
                        $errorMsg = '⚠️ Este endpoint solo funciona con tokens EXPIRADOS (después de 5 minutos del login). El token actual sigue VÁLIDO. Espera 5+ minutos después del login para probarlo. | Detalles: ' . $errorMsg;
                    } else {
                        $errorMsg = '🔧 Error del servidor SEACE. ' . $errorMsg;
                    }
                }

                $this->resultadoRefresh = [
                    'success' => false,
                    'error' => $errorMsg,
                    'status' => $response->status(),
                    'raw' => $data,
                    'debug' => [
                        'errorCode' => $data['errorCode'] ?? null,
                        'backendMessage' => $data['backendMessage'] ?? null,
                    ],
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error en probarRefresh', [
                'error' => $e->getMessage(),
                'cuenta_id' => $this->cuentaSeleccionada,
            ]);

            $this->resultadoRefresh = [
                'success' => false,
                'error' => 'Excepción: ' . $e->getMessage(),
            ];
        } finally {
            $this->loadingRefresh = false;
        }
    }

    public function probarBuscador()
    {
        $this->loadingBuscador = true;
        $this->resultadoBuscador = null;

        try {
            $cuenta = $this->getCuentaActual();

            if (!$cuenta) {
                $this->resultadoBuscador = [
                    'success' => false,
                    'error' => 'No hay cuenta seleccionada',
                ];
                return;
            }

            if (!$cuenta->token_valido) {
                $this->resultadoBuscador = [
                    'success' => false,
                    'error' => 'Token no válido o expirado. Realiza login primero.',
                ];
                return;
            }

            // Parámetros según documentación oficial
            $params = [
                'anio' => now()->year,
                'ruc' => $cuenta->username,
                'cotizaciones_enviadas' => false,
                'invitaciones_por_cotizar' => false,
                'orden' => 2, // Descendente
                'page' => $this->parametrosBuscador['pagina'] ?? 1,
                'page_size' => $this->parametrosBuscador['registros'] ?? 10,
            ];

            // Agregar palabra clave si existe
            if (!empty($this->parametrosBuscador['texto'])) {
                $params['palabra_clave'] = $this->parametrosBuscador['texto'];
            }

            // Agregar filtro de estado si está seleccionado (usar IDs no nombres)
            if (!empty($this->parametrosBuscador['estado'])) {
                $params['lista_estado_contrato'] = (int)$this->parametrosBuscador['estado'];
            }

            // ✅ NUEVO: Agregar código de entidad si existe
            if (!empty($this->parametrosBuscador['codigo_entidad'])) {
                $params['codigo_entidad'] = (int)$this->parametrosBuscador['codigo_entidad'];
            }

            // ✅ FILTROS GEOGRÁFICOS
            if (!empty($this->parametrosBuscador['departamento'])) {
                $params['codigo_departamento'] = (int)$this->parametrosBuscador['departamento'];
            }
            if (!empty($this->parametrosBuscador['provincia'])) {
                $params['codigo_provincia'] = (int)$this->parametrosBuscador['provincia'];
            }
            if (!empty($this->parametrosBuscador['distrito'])) {
                $params['codigo_distrito'] = (int)$this->parametrosBuscador['distrito'];
            }

            Log::info('🔹 INICIO BUSCADOR', [
                'url' => "{$this->baseUrl}/contratacion/contrataciones/buscador",
                'params' => $params,
                'headers' => $this->getHeaders("{$this->refererBase}/busqueda"),
            ]);

            $response = Http::timeout(30)
                ->withToken($cuenta->access_token)
                ->withHeaders($this->getHeaders("{$this->refererBase}/busqueda"))
                ->get("{$this->baseUrl}/contratacion/contrataciones/buscador", $params);

            Log::info('🔹 RESPUESTA BUSCADOR RECIBIDA', [
                'status' => $response->status(),
                'body_preview' => substr($response->body(), 0, 500),
            ]);

            $data = $response->json();

            Log::info('🔹 BUSCADOR JSON PARSEADO', [
                'status' => $response->status(),
                'data_keys' => array_keys($data ?? []),
                'total_elements' => $data['pageable']['totalElements'] ?? 0,
                'success' => $response->successful(),
            ]);

            if ($response->successful()) {
                $cuenta->registrarConsulta();

                $this->resultadoBuscador = [
                    'success' => true,
                    'message' => 'Búsqueda exitosa',
                    'data' => [
                        'totalElements' => $data['pageable']['totalElements'] ?? 0,
                        'pageNumber' => $data['pageable']['pageNumber'] ?? 1,
                        'pageSize' => $data['pageable']['pageSize'] ?? 0,
                        'registros_devueltos' => count($data['data'] ?? []),
                        'primer_registro' => $data['data'][0] ?? null,
                    ],
                    'raw' => $data,
                ];

                $this->cargarCuentas();
            } else {
                // Mensaje de error descriptivo
                $errorMsg = $data['mensaje'] ?? $data['message'] ?? 'Error en la búsqueda';

                // Agregar contexto según el código de error
                if ($response->status() === 401) {
                    if ($data['errorCode'] === 'TOKEN_EXPIRED') {
                        $errorMsg = '⏰ Token expirado. Por favor, realiza login nuevamente.';
                    } else {
                        $errorMsg = '🔐 No autorizado. ' . $errorMsg;
                    }
                } elseif ($response->status() === 403) {
                    $errorMsg = '🚫 Acceso denegado. ' . $errorMsg;
                } elseif ($response->status() >= 500) {
                    $errorMsg = '🔧 Error del servidor SEACE. ' . $errorMsg;
                }

                $this->resultadoBuscador = [
                    'success' => false,
                    'error' => $errorMsg,
                    'status' => $response->status(),
                    'raw' => $data,
                    'debug' => [
                        'errorCode' => $data['errorCode'] ?? null,
                        'backendMessage' => $data['backendMessage'] ?? null,
                    ],
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error en probarBuscador', [
                'error' => $e->getMessage(),
                'cuenta_id' => $this->cuentaSeleccionada,
            ]);

            $this->resultadoBuscador = [
                'success' => false,
                'error' => 'Excepción: ' . $e->getMessage(),
            ];
        } finally {
            $this->loadingBuscador = false;
        }
    }

    /**
     * Probar endpoint de listar archivos de un contrato
     */
    public function probarListarArchivos()
    {
        $this->loadingArchivos = true;
        $this->resultadoArchivos = null;

        try {
            $cuenta = $this->getCuentaActual();

            if (!$cuenta) {
                $this->resultadoArchivos = [
                    'success' => false,
                    'error' => 'No hay cuenta seleccionada',
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                ];
                return;
            }

            if (!$cuenta->token_valido) {
                $this->resultadoArchivos = [
                    'success' => false,
                    'error' => 'Token inválido. Haz login primero.',
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                ];
                return;
            }

            // Validar que haya contratos en resultadoBuscador
            if (empty($this->resultadoBuscador['raw']['data'])) {
                $this->resultadoArchivos = [
                    'success' => false,
                    'error' => 'Primero debes buscar contratos',
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                ];
                return;
            }

            // Usar el primer contrato encontrado si no hay selección
            if (!$this->contratoSeleccionadoArchivos) {
                $this->contratoSeleccionadoArchivos = $this->resultadoBuscador['raw']['data'][0]['idContrato'];
            }

            $idContrato = $this->contratoSeleccionadoArchivos;

            Log::info('SEACE: Listando archivos', [
                'idContrato' => $idContrato,
                'cuenta_id' => $cuenta->id,
            ]);

            $startTime = microtime(true);

            $response = Http::withToken($cuenta->access_token)
                ->withHeaders($this->getHeaders("{$this->refererBase}/cotizacion/contrataciones"))
                ->timeout(10)
                ->get("{$this->baseUrl}/archivo/archivos/listar-archivos-contrato/{$idContrato}/1");

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            if ($response->successful()) {
                $archivos = $response->json();

                $this->resultadoArchivos = [
                    'success' => true,
                    'data' => $archivos,
                    'count' => count($archivos),
                    'idContrato' => $idContrato,
                    'status' => $response->status(),
                    'duration_ms' => $duration,
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                ];

                Log::info('SEACE: Archivos listados exitosamente', [
                    'count' => count($archivos),
                    'duration_ms' => $duration,
                ]);
            } else {
                $errorData = $response->json();

                $this->resultadoArchivos = [
                    'success' => false,
                    'error' => $errorData['message'] ?? 'Error desconocido',
                    'error_code' => $errorData['errorCode'] ?? null,
                    'status' => $response->status(),
                    'duration_ms' => $duration,
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                    'raw_response' => $errorData,
                ];

                Log::error('SEACE: Error listando archivos', [
                    'status' => $response->status(),
                    'error' => $errorData,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('SEACE: Excepción listando archivos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->resultadoArchivos = [
                'success' => false,
                'error' => 'Excepción: ' . $e->getMessage(),
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ];
        } finally {
            $this->loadingArchivos = false;
        }
    }

    /**
     * Seleccionar un contrato específico para listar archivos
     */
    public function seleccionarContratoParaArchivos($idContrato)
    {
        $this->contratoSeleccionadoArchivos = $idContrato;

        // Guardar datos completos del contrato seleccionado
        if (!empty($this->resultadoBuscador['raw']['data'])) {
            foreach ($this->resultadoBuscador['raw']['data'] as $contrato) {
                if ($contrato['idContrato'] == $idContrato) {
                    $this->contratoSeleccionadoData = $contrato;
                    break;
                }
            }
        }

        $this->probarListarArchivos();
    }

    /**
     * Descargar un archivo específico (guarda temporalmente y dispara descarga con JS)
     */
    public function descargarArchivo($idContratoArchivo, $nombreArchivo)
    {
        try {
            $cuenta = $this->getCuentaActual();

            if (!$cuenta || !$cuenta->token_valido) {
                $this->addError('descarga', 'Token inválido. Haz login primero.');
                return;
            }

            $persistence = new TdrPersistenceService();
            $contratoSnapshot = is_array($this->contratoSeleccionadoData)
                ? $this->contratoSeleccionadoData
                : null;

            $archivoPersistido = $persistence->resolveArchivo(
                (int) $idContratoArchivo,
                $nombreArchivo,
                $contratoSnapshot['idContrato'] ?? null,
                $contratoSnapshot
            );

            $documentService = new TdrDocumentService($persistence);
            $documentService->ensureLocalFile($archivoPersistido, $cuenta, $nombreArchivo);

            if (!$archivoPersistido->hasStoredFile()) {
                throw new Exception('El archivo no se pudo almacenar correctamente en el repositorio local.');
            }

            $this->dispatch('descargar-archivo', url: route('tdr.archivos.download', $archivoPersistido));
            session()->flash('descarga_exitosa', "✓ Archivo '{$nombreArchivo}' listo para descargar desde caché local");

        } catch (Exception $e) {
            Log::error('SEACE: Excepción descargando archivo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $this->addError('descarga', 'Excepción: ' . $e->getMessage());
        }
    }

    /**
     * Analizar un archivo TDR con IA (Analizador TDR)
     */
    public function analizarArchivo($idContratoArchivo, $nombreArchivo, $contratoData = null)
    {
        $this->loadingAnalisis = true;
        $this->resultadoAnalisis = null;

        try {
            $cuenta = $this->getCuentaActual();

            if (!$cuenta || !$cuenta->token_valido) {
                $this->addError('analisis', 'Token inválido. Haz login primero.');
                $this->loadingAnalisis = false;
                return;
            }

            // Usar servicio centralizado para análisis
            $tdrService = new \App\Services\TdrAnalysisService();
            $resultado = $tdrService->analizarDesdeSeace(
                $idContratoArchivo,
                $nombreArchivo,
                $cuenta,
                $contratoData
            );

            // Formatear resultado para la vista
            $this->resultadoAnalisis = [
                'success' => $resultado['success'],
                'timestamp' => now()->format('d/m/Y H:i:s'),
            ];

            if ($resultado['success']) {
                $this->resultadoAnalisis['data'] = $resultado['data'];
            } else {
                $this->resultadoAnalisis['error'] = $resultado['error'];
            }

        } catch (\Exception $e) {
            Log::error('Error al analizar archivo', [
                'idContratoArchivo' => $idContratoArchivo,
                'error' => $e->getMessage(),
            ]);

            $this->resultadoAnalisis = [
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => now()->format('d/m/Y H:i:s'),
            ];
        } finally {
            $this->loadingAnalisis = false;
        }
    }

    public function probarMaestras()
    {
        $this->loadingMaestras = true;
        $this->resultadoMaestras = null;

        try {
            $cuenta = $this->getCuentaActual();

            if (!$cuenta) {
                $this->resultadoMaestras = [
                    'success' => false,
                    'error' => 'No hay cuenta seleccionada',
                ];
                return;
            }

            if (!$cuenta->token_valido) {
                $this->resultadoMaestras = [
                    'success' => false,
                    'error' => 'Token no válido o expirado. Realiza login primero.',
                ];
                return;
            }

            // Mapeo de endpoints según la documentación oficial
            $endpoints = [
                'objetos' => '/maestra/maestras/listar-objeto-contratacion',
                'estados' => '/maestra/maestras/listar-estado-contratacion',
                'departamentos' => '/maestra/maestras/listar-departamento',
            ];

            if (!isset($endpoints[$this->tipoMaestra])) {
                $this->resultadoMaestras = [
                    'success' => false,
                    'error' => 'Tipo de maestra no válido. Opciones: objetos, estados, departamentos',
                ];
                return;
            }

            $response = Http::timeout(30)
                ->withToken($cuenta->access_token)
                ->withHeaders($this->getHeaders("{$this->refererBase}/busqueda"))
                ->get($this->baseUrl . $endpoints[$this->tipoMaestra]);

            $data = $response->json();

            if ($response->successful()) {
                $cuenta->registrarConsulta();

                $this->resultadoMaestras = [
                    'success' => true,
                    'message' => 'Consulta de maestras exitosa',
                    'data' => [
                        'tipo' => $this->tipoMaestra,
                        'total_registros' => count($data ?? []),
                        'primeros_5' => array_slice($data ?? [], 0, 5),
                    ],
                    'raw' => $data,
                ];

                $this->cargarCuentas();
            } else {
                $this->resultadoMaestras = [
                    'success' => false,
                    'error' => $data['message'] ?? 'Error en consulta de maestras',
                    'status' => $response->status(),
                    'raw' => $data,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error en probarMaestras', [
                'error' => $e->getMessage(),
                'cuenta_id' => $this->cuentaSeleccionada,
            ]);

            $this->resultadoMaestras = [
                'success' => false,
                'error' => 'Excepción: ' . $e->getMessage(),
            ];
        } finally {
            $this->loadingMaestras = false;
        }
    }

    public function limpiarResultados()
    {
        $this->resultadoLogin = null;
        $this->resultadoRefresh = null;
        $this->resultadoBuscador = null;
        $this->resultadoMaestras = null;
    }

    /**
     * Buscar entidades con debouncing (se llama desde Alpine.js)
     */
    public function buscarEntidades()
    {
        // Limpiar sugerencias si el texto está vacío
        if (empty($this->parametrosBuscador['entidad']) || strlen($this->parametrosBuscador['entidad']) < 3) {
            $this->entidadesSugeridas = [];
            return;
        }

        $this->buscandoEntidades = true;

        try {
            $cuenta = $this->getCuentaActual();

            if (!$cuenta || !$cuenta->token_valido) {
                $this->entidadesSugeridas = [];
                return;
            }

            $response = Http::withToken($cuenta->access_token)
                ->withHeaders($this->getHeaders("{$this->refererBase}/cotizacion/contrataciones"))
                ->timeout(10)
                ->get("{$this->baseUrl}/servicio/servicios/obtener-entidades-cubso", [
                    'descEntidad' => $this->parametrosBuscador['entidad'],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->entidadesSugeridas = $data['lista'] ?? [];
            } else {
                $this->entidadesSugeridas = [];
            }
        } catch (\Exception $e) {
            Log::error('Error buscando entidades', ['error' => $e->getMessage()]);
            $this->entidadesSugeridas = [];
        } finally {
            $this->buscandoEntidades = false;
        }
    }

    /**
     * Seleccionar una entidad de las sugerencias
     */
    public function seleccionarEntidad($razonSocial, $codigoEntidad)
    {
        $this->parametrosBuscador['entidad'] = $razonSocial;
        $this->parametrosBuscador['codigo_entidad'] = $codigoEntidad;
        $this->entidadesSugeridas = [];
    }

    /**
     * Filtrar departamentos según texto de búsqueda
     */
    public function getDepartamentosFiltradosProperty()
    {
        if (empty($this->busquedaDepartamento)) {
            return $this->departamentos;
        }

        $busqueda = mb_strtolower($this->busquedaDepartamento);
        return array_filter($this->departamentos, function($dpto) use ($busqueda) {
            return str_contains(mb_strtolower($dpto['nom']), $busqueda);
        });
    }

    /**
     * Filtrar provincias según texto de búsqueda
     */
    public function getProvinciasFiltradosProperty()
    {
        if (empty($this->busquedaProvincia)) {
            return $this->provincias;
        }

        $busqueda = mb_strtolower($this->busquedaProvincia);
        return array_filter($this->provincias, function($prov) use ($busqueda) {
            return str_contains(mb_strtolower($prov['nom']), $busqueda);
        });
    }

    /**
     * Filtrar distritos según texto de búsqueda
     */
    public function getDistritosFiltradosProperty()
    {
        if (empty($this->busquedaDistrito)) {
            return $this->distritos;
        }

        $busqueda = mb_strtolower($this->busquedaDistrito);
        return array_filter($this->distritos, function($dist) use ($busqueda) {
            return str_contains(mb_strtolower($dist['nom']), $busqueda);
        });
    }

    /**
     * Seleccionar departamento de las sugerencias
     */
    public function seleccionarDepartamento($id, $nombre)
    {
        $this->parametrosBuscador['departamento'] = $id;
        $this->busquedaDepartamento = $nombre;
    }

    /**
     * Seleccionar provincia de las sugerencias
     */
    public function seleccionarProvincia($id, $nombre)
    {
        $this->parametrosBuscador['provincia'] = $id;
        $this->busquedaProvincia = $nombre;
    }

    /**
     * Seleccionar distrito de las sugerencias
     */
    public function seleccionarDistrito($id, $nombre)
    {
        $this->parametrosBuscador['distrito'] = $id;
        $this->busquedaDistrito = $nombre;
    }

    /**
     * Cargar lista de departamentos (se ejecuta al inicio)
     */
    public function cargarDepartamentos()
    {
        $this->cargandoDepartamentos = true;

        try {
            $cuenta = $this->getCuentaActual();

            if (!$cuenta || !$cuenta->token_valido) {
                $this->departamentos = [];
                return;
            }

            $response = Http::withToken($cuenta->access_token)
                ->withHeaders($this->getHeaders("{$this->refererBase}/cotizacion/contrataciones"))
                ->timeout(10)
                ->get("{$this->baseUrl}/maestra/maestras/listar-departamento");

            if ($response->successful()) {
                $this->departamentos = $response->json();
            }
        } catch (\Exception $e) {
            Log::error('Error cargando departamentos', ['error' => $e->getMessage()]);
            $this->departamentos = [];
        } finally {
            $this->cargandoDepartamentos = false;
        }
    }

    /**
     * Cargar provincias cuando se selecciona un departamento
     */
    public function updatedParametrosBuscadorDepartamento($idDepartamento)
    {
        // Limpiar provincia y distrito al cambiar departamento
        $this->parametrosBuscador['provincia'] = '';
        $this->parametrosBuscador['distrito'] = '';
        $this->busquedaProvincia = '';
        $this->busquedaDistrito = '';
        $this->provincias = [];
        $this->distritos = [];

        if (empty($idDepartamento)) {
            return;
        }

        $this->cargandoProvincias = true;

        try {
            $cuenta = $this->getCuentaActual();

            if (!$cuenta || !$cuenta->token_valido) {
                return;
            }

            $response = Http::withToken($cuenta->access_token)
                ->withHeaders($this->getHeaders("{$this->refererBase}/cotizacion/contrataciones"))
                ->timeout(10)
                ->get("{$this->baseUrl}/maestra/maestras/listar-provincia/{$idDepartamento}");

            if ($response->successful()) {
                $this->provincias = $response->json();
            }
        } catch (\Exception $e) {
            Log::error('Error cargando provincias', ['error' => $e->getMessage()]);
            $this->provincias = [];
        } finally {
            $this->cargandoProvincias = false;
        }
    }

    /**
     * Cargar distritos cuando se selecciona una provincia
     */
    public function updatedParametrosBuscadorProvincia($idProvincia)
    {
        // Limpiar distrito al cambiar provincia
        $this->parametrosBuscador['distrito'] = '';
        $this->busquedaDistrito = '';
        $this->distritos = [];

        if (empty($idProvincia)) {
            return;
        }

        $this->cargandoDistritos = true;

        try {
            $cuenta = $this->getCuentaActual();

            if (!$cuenta || !$cuenta->token_valido) {
                return;
            }

            $response = Http::withToken($cuenta->access_token)
                ->withHeaders($this->getHeaders("{$this->refererBase}/cotizacion/contrataciones"))
                ->timeout(10)
                ->get("{$this->baseUrl}/maestra/maestras/listar-distrito/{$idProvincia}");

            if ($response->successful()) {
                $this->distritos = $response->json();
            }
        } catch (\Exception $e) {
            Log::error('Error cargando distritos', ['error' => $e->getMessage()]);
            $this->distritos = [];
        } finally {
            $this->cargandoDistritos = false;
        }
    }

    /**
     * Headers "ninja" para simular navegador real Chrome 144
     * Optimizados: 31 de enero de 2026
     *
     * @param string|null $referer URL del referer (dinámico según endpoint)
     */
    private function getHeaders(?string $referer = null): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Accept-Language' => 'es-US,es-419;q=0.9,es;q=0.8,en;q=0.7',
            'Content-Type' => 'application/json',
            'Origin' => 'https://prod6.seace.gob.pe',
            'Sec-Ch-Ua' => '"Not(A:Brand";v="8", "Chromium";v="144", "Google Chrome";v="144"',
            'Sec-Ch-Ua-Mobile' => '?0',
            'Sec-Ch-Ua-Platform' => '"Windows"',
            'Sec-Fetch-Dest' => 'empty',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Site' => 'same-origin',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36',
        ];

        // Agregar Referer solo si se proporciona
        if ($referer) {
            $headers['Referer'] = $referer;
        }

        return $headers;
    }

    /**
     * Enviar información del proceso al Bot de Telegram (BROADCAST A TODOS LOS SUSCRIPTORES)
     */
    public function enviarAlBot(array $contratoData)
    {
        try {
            if (empty($contratoData)) {
                $this->addError('telegram', 'No hay datos del contrato para enviar');
                return;
            }

            // Usar servicio de broadcast multi-usuario
            $servicio = new TelegramNotificationService();
            $estadisticas = $servicio->enviarAlerta($contratoData);

            // Verificar si hay suscriptores activos
            if ($estadisticas['total'] === 0) {
                $this->addError('telegram', '⚠️ No hay suscriptores activos. Agrega Chat IDs en /configuracion');
                return;
            }

            // Mensaje de éxito con estadísticas
            $mensaje = "✅ Proceso enviado exitosamente a {$estadisticas['exitosos']} suscriptor(es)";

            if ($estadisticas['fallidos'] > 0) {
                $mensaje .= "\n⚠️ {$estadisticas['fallidos']} envío(s) fallido(s)";
            }

            session()->flash('telegram_exitoso', $mensaje);

            Log::info('Proceso enviado a Telegram (Broadcast)', [
                'contrato' => $contratoData['desContratacion'] ?? 'N/A',
                'entidad' => $contratoData['nomEntidad'] ?? 'N/A',
                'estadisticas' => $estadisticas,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al enviar al bot de Telegram', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->addError('telegram', 'Error inesperado: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.prueba-endpoints');
    }
}
