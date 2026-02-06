<?php

namespace App\Services;

use App\Models\CuentaSeace;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;
use RuntimeException;

class SeaceScraperService
{
    protected string $baseUrl;
    protected ?CuentaSeace $cuenta;
    protected int $tokenCacheDuration;
    protected string $frontendOrigin;
    protected string $authReferer;
    protected string $contratacionesReferer;
    protected bool $debugLogging;

    // Propiedades legacy para retrocompatibilidad
    protected ?string $rucProveedor;
    protected ?string $password;

    /**
     * Constructor
     *
     * @param CuentaSeace|null $cuenta Si se proporciona, se usa esta cuenta específica.
     *                                  Si es null, se usa la cuenta principal o config.
     */
    public function __construct(?CuentaSeace $cuenta = null)
    {
        $this->baseUrl = rtrim((string) config('services.seace.base_url', ''), '/');
        if (empty($this->baseUrl)) {
            throw new RuntimeException('Configura SEACE_BASE_URL en el .env');
        }

        $this->tokenCacheDuration = (int) config('services.seace.token_cache_duration', 300);

        $this->frontendOrigin = rtrim((string) config('services.seace.frontend_origin', ''), '/');
        if (empty($this->frontendOrigin)) {
            throw new RuntimeException('Configura SEACE_FRONTEND_ORIGIN en el .env');
        }

        $this->authReferer = rtrim((string) config('services.seace.auth_referer', ''), '/');
        if (empty($this->authReferer)) {
            $this->authReferer = $this->frontendOrigin . '/auth-proveedor';
        }
        $this->authReferer .= str_ends_with($this->authReferer, '/') ? '' : '/';

        $this->contratacionesReferer = rtrim((string) config('services.seace.contrataciones_referer', ''), '/');
        if (empty($this->contratacionesReferer)) {
            $this->contratacionesReferer = $this->frontendOrigin . '/cotizacion/contrataciones';
        }

        $this->debugLogging = (bool) config('services.seace.debug_logs', false);

        // Si se proporciona una cuenta, usarla
        if ($cuenta) {
            $this->cuenta = $cuenta;
            $this->rucProveedor = $cuenta->username;
            $this->password = $cuenta->getPasswordDescifrado();
        } else {
            // Intentar obtener la cuenta principal de la BD
            $this->cuenta = CuentaSeace::principal()->activa()->first();

            if ($this->cuenta) {
                $this->rucProveedor = $this->cuenta->username;
                $this->password = $this->cuenta->getPasswordDescifrado();
            } else {
                // Fallback a configuración (legacy)
                $this->rucProveedor = config('services.seace.username');
                $this->password = config('services.seace.password');
            }
        }
    }

    /**
     * Headers "ninja" para simular navegador real Chrome 144
     * Optimizados: 31 de enero de 2026
     *
     * @param string|null $referer URL del referer (dinámico según endpoint)
     */
    protected function ninjaHeaders(?string $referer = null): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Accept-Language' => 'es-US,es-419;q=0.9,es;q=0.8,en;q=0.7',
            'Content-Type' => 'application/json',
            'Origin' => $this->frontendOrigin,
            'Sec-Ch-Ua' => '"Not(A:Brand";v="8", "Chromium";v="144", "Google Chrome";v="144"',
            'Sec-Ch-Ua-Mobile' => '?0',
            'Sec-Ch-Ua-Platform' => '"Windows"',
            'Sec-Fetch-Dest' => 'empty',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Site' => 'same-origin',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36',
        ];

        $headers['Referer'] = $referer ?? $this->authReferer;

        return $headers;
    }

    protected function debug(string $message, array $context = []): void
    {
        if (!$this->debugLogging) {
            return;
        }

        Log::debug('SEACE: ' . $message, $context);
    }

    /**
     * Aplicar jitter (retraso aleatorio entre 0.5 y 2 segundos)
     */
    protected function applyJitter(): void
    {
        usleep(rand(500000, 2000000)); // 0.5 a 2 segundos
    }

    /**
     * Login completo con usuario y contraseña según API real
     */
    public function fullLogin(): bool
    {
        try {
            $this->debug('Iniciando login completo', [
                'username' => $this->rucProveedor,
                'timestamp' => now()
            ]);

            $this->applyJitter();

            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withHeaders($this->ninjaHeaders($this->authReferer))
                ->timeout(30)
                ->post("{$this->baseUrl}/seguridadproveedor/seguridad/validausuariornp", [
                    'username' => $this->rucProveedor,
                    'password' => $this->password,
                ]);

            if (!$response->successful()) {
                Log::error('SEACE: Error en login', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                // Registrar login fallido en la cuenta si existe
                if ($this->cuenta) {
                    $this->cuenta->registrarLogin(false);
                }

                return false;
            }

            $data = $response->json();

            if (!isset($data['token'])) {
                Log::error('SEACE: Respuesta sin token', ['data' => $data]);

                if ($this->cuenta) {
                    $this->cuenta->registrarLogin(false);
                }

                return false;
            }

            // Si tenemos una cuenta en BD, actualizar sus tokens
            if ($this->cuenta) {
                $this->cuenta->actualizarTokens(
                    $data['token'],
                    $data['refreshToken'] ?? null,
                    300 // 5 minutos según documentación
                );
                $this->cuenta->registrarLogin(true);
            } else {
                // Fallback a cache (legacy)
                Cache::put('seace_access_token', $data['token'], $this->tokenCacheDuration);

                if (isset($data['refreshToken'])) {
                    Cache::put('seace_refresh_token', $data['refreshToken'], $this->tokenCacheDuration * 2);
                }
            }

            $this->debug('Login exitoso', [
                'token_length' => strlen($data['token']),
                'has_refresh' => isset($data['refreshToken']),
                'usando_bd' => $this->cuenta !== null
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('SEACE: Excepción en login', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($this->cuenta) {
                $this->cuenta->registrarLogin(false);
            }

            return false;
        }
    }

    /**
     * MÉTODO PRINCIPAL: Petición HTTP resiliente con auto-refresh y auto-login
     *
     * Maneja 3 escenarios automáticamente:
     * 1. Token válido → Ejecuta petición directamente
     * 2. Token expirado pero refresh válido → Refresca token y reintenta
     * 3. Refresh token expirado → Login completo y reintenta
     *
     * @param string $method GET, POST, PUT, DELETE
     * @param string $endpoint URL completa o ruta relativa
     * @param array $data Datos para POST/PUT
     * @param array $queryParams Query params para GET
     * @param string|null $referer Referer personalizado
     * @return \Illuminate\Http\Client\Response
     * @throws Exception Si falla después de todos los reintentos
     */
    public function makeResilientRequest(
        string $method,
        string $endpoint,
        array $data = [],
        array $queryParams = [],
        ?string $referer = null,
        array $customHeaders = []
    ) {
        $method = strtoupper($method);
        $maxRetries = 2; // Intento inicial + 2 reintentos
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                // 1. VALIDAR TOKEN ANTES DE CADA INTENTO
                if (!$this->validarToken()) {
                    $this->debug("Token inválido en intento {$attempt}, intentando recuperar...");

                    // 2. INTENTAR REFRESH PRIMERO
                    if ($this->refreshToken()) {
                        $this->debug('Token refrescado exitosamente');
                        continue; // Reintentar petición con nuevo token
                    }

                    // 3. SI REFRESH FALLÓ, HACER LOGIN COMPLETO
                    Log::warning("SEACE: Refresh falló, haciendo login completo...");
                    if ($this->fullLogin()) {
                        $this->debug('Login completo exitoso');
                        continue; // Reintentar petición con nuevo token
                    }

                    // 4. SI LOGIN TAMBIÉN FALLÓ, NO HAY NADA QUE HACER
                    throw new Exception("No se pudo autenticar: login y refresh fallaron");
                }

                // 5. OBTENER TOKEN VÁLIDO
                $token = $this->cuenta
                    ? $this->cuenta->access_token
                    : Cache::get('seace_access_token');

                if (!$token) {
                    throw new Exception("Token válido pero no disponible (estado inconsistente)");
                }

                // 6. PREPARAR URL COMPLETA
                $url = str_starts_with($endpoint, 'http')
                    ? $endpoint
                    : "{$this->baseUrl}{$endpoint}";

                // 7. PREPARAR HEADERS
                $headers = $this->ninjaHeaders($referer);

                if (!empty($customHeaders)) {
                    foreach ($customHeaders as $headerKey => $headerValue) {
                        if ($headerValue === null) {
                            unset($headers[$headerKey]);
                            continue;
                        }

                        $headers[$headerKey] = $headerValue;
                    }
                }

                // 8. EJECUTAR PETICIÓN SEGÚN MÉTODO
                $this->applyJitter();

                $this->debug('Ejecutando petición resiliente', [
                    'method' => $method,
                    'url' => $url,
                    'attempt' => $attempt,
                    'has_data' => !empty($data),
                    'has_query' => !empty($queryParams)
                ]);

                $httpClient = Http::withToken($token)
                    ->withHeaders($headers)
                    ->timeout(60);

                $response = match($method) {
                    'GET' => $httpClient->get($url, $queryParams),
                    'POST' => $httpClient->post($url, $data),
                    'PUT' => $httpClient->put($url, $data),
                    'DELETE' => $httpClient->delete($url, $data),
                    default => throw new Exception("Método HTTP no soportado: {$method}")
                };

                // 9. VERIFICAR SI ES ERROR DE TOKEN EXPIRADO
                if ($response->status() === 401 || $response->status() === 403) {
                    $body = $response->json();
                    $errorMessage = $body['message'] ?? $body['error'] ?? '';

                    // Detectar token expirado
                    if (str_contains($errorMessage, 'Token inválido') ||
                        str_contains($errorMessage, 'token') ||
                        str_contains($errorMessage, 'expirado')) {

                        Log::warning("SEACE: Token expirado detectado en respuesta, intento {$attempt}");

                        // Marcar token como expirado
                        if ($this->cuenta) {
                            $this->cuenta->update(['token_expires_at' => now()->subMinute()]);
                        }

                        // Permitir reintento
                        continue;
                    }
                }

                // 10. VERIFICAR SI LA RESPUESTA ES EXITOSA
                if ($response->successful()) {
                    $this->debug('Petición resiliente exitosa', [
                        'status' => $response->status(),
                        'attempt' => $attempt
                    ]);

                    return $response;
                }

                // 11. SI LA RESPUESTA NO ES EXITOSA, REGISTRAR Y REINTENTAR
                $bodyPreview = substr($response->body(), 0, 200);
                // Limpiar caracteres UTF-8 inválidos
                $bodyPreview = mb_convert_encoding($bodyPreview, 'UTF-8', 'UTF-8');

                Log::warning("SEACE: Respuesta no exitosa", [
                    'status' => $response->status(),
                    'attempt' => $attempt,
                    'body_preview' => $bodyPreview
                ]);

                // Si es el último intento, lanzar excepción con detalles
                if ($attempt >= $maxRetries) {
                    throw new Exception(
                        "Respuesta del SEACE no exitosa (HTTP {$response->status()}): " .
                        substr($response->body(), 0, 200)
                    );
                }

                // Esperar antes del siguiente intento
                sleep(2);
                continue;

            } catch (Exception $e) {
                $lastException = $e;

                // Limpiar mensaje de error para evitar problemas UTF-8
                $errorMessage = mb_convert_encoding($e->getMessage(), 'UTF-8', 'UTF-8');

                Log::error("SEACE: Error en intento {$attempt}/{$maxRetries}", [
                    'error' => $errorMessage,
                    'method' => $method,
                    'endpoint' => $endpoint
                ]);

                if ($attempt >= $maxRetries) {
                    break;
                }

                // Esperar antes del siguiente intento
                sleep(2);
            }
        }

        // Si llegamos aquí, todos los intentos fallaron
        // Limpiar mensaje final para evitar errores UTF-8
        $finalError = $lastException
            ? mb_convert_encoding($lastException->getMessage(), 'UTF-8', 'UTF-8')
            : 'Desconocido';

        throw new Exception(
            "Petición falló después de {$maxRetries} intentos. Último error: " . $finalError
        );
    }

    /**
     * Validar si el token actual está vigente (no expirado)
     */
    public function validarToken(): bool
    {
        if ($this->cuenta) {
            return $this->cuenta->token_valido;
        }

        // Fallback a cache (legacy)
        $token = Cache::get('seace_access_token');
        $expiresAt = Cache::get('seace_token_expires_at');

        if (!$token || !$expiresAt) {
            return false;
        }

        return now()->lessThan($expiresAt);
    }

    /**
     * Refrescar token usando el refresh token según API real
     * CRÍTICO: Usa el token EXPIRADO en el header Authorization
     */
    public function refreshToken(): bool
    {
        try {
            // Obtener token expirado de la cuenta o del cache
            $expiredToken = $this->cuenta
                ? $this->cuenta->access_token
                : Cache::get('seace_access_token');

            if (!$expiredToken) {
                Log::warning('SEACE: No hay token para refrescar, se requiere login completo');
                return false;
            }

            $this->debug('Intentando refrescar token');

            $this->applyJitter();

            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withToken($expiredToken)
                ->withHeaders($this->ninjaHeaders($this->authReferer))
                ->timeout(30)
                ->post("{$this->baseUrl}/seguridadproveedor/seguridad/tokens/refresh");

            if (!$response->successful()) {
                Log::warning('SEACE: Refresh token falló', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }

            $data = $response->json();

            if (!isset($data['token'])) {
                Log::error('SEACE: Respuesta de refresh sin nuevo token', ['data' => $data]);
                return false;
            }

            // ⚠️ CRÍTICO: El servidor devuelve un NUEVO refreshToken también
            if ($this->cuenta) {
                $this->cuenta->actualizarTokens(
                    $data['token'],
                    $data['refreshToken'] ?? null,
                    300 // 5 minutos según documentación
                );
                $this->cuenta->registrarConsulta();
            } else {
                // Fallback a cache (legacy)
                Cache::put('seace_access_token', $data['token'], $this->tokenCacheDuration);

                if (isset($data['refreshToken'])) {
                    Cache::put('seace_refresh_token', $data['refreshToken'], $this->tokenCacheDuration * 2);
                }
            }

            $this->debug('Token refrescado exitosamente', [
                'usando_bd' => $this->cuenta !== null
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('SEACE: Excepción en refresh token', [
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Asegurar que tenemos un token válido (con retry automático)
     */
    protected function ensureValidToken(): bool
    {
        // Si usamos cuenta de BD, verificar su token
        if ($this->cuenta) {
            if ($this->cuenta->token_valido) {
                return true;
            }

            $this->debug('Token de cuenta expirado o no disponible, intentando login');
            return $this->fullLogin();
        }

        // Fallback a cache (legacy)
        $token = Cache::get('seace_access_token');

        if ($token) {
            return true;
        }

        $this->debug('No hay token en cache, intentando login');
        return $this->fullLogin();
    }

    /**
     * Petición con retry automático en caso de TOKEN_EXPIRED
     */
    public function fetchWithRetry(string $url, array $params = [], int $maxRetries = 2)
    {
        if (!$this->ensureValidToken()) {
            throw new Exception('No se pudo obtener un token válido de SEACE');
        }

        $attempts = 0;

        while ($attempts < $maxRetries) {
            $this->applyJitter();

            // Obtener token de la cuenta o del cache
            $token = $this->cuenta
                ? $this->cuenta->access_token
                : Cache::get('seace_access_token');

            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withToken($token)
                ->withHeaders($this->ninjaHeaders($this->contratacionesReferer))
                ->timeout(30)
                ->get($url, $params);

            // Si es exitoso, registrar consulta y retornar
            if ($response->successful()) {
                if ($this->cuenta) {
                    $this->cuenta->registrarConsulta();
                }
                return $response;
            }

            // Detectar TOKEN_EXPIRED
            if ($response->status() === 401) {
                $errorCode = $response->json('errorCode');

                if ($errorCode === 'TOKEN_EXPIRED') {
                    Log::warning('SEACE: Token expirado, intentando refresh', [
                        'attempt' => $attempts + 1
                    ]);

                    // Intentar refresh primero
                    if ($this->refreshToken()) {
                        $attempts++;
                        continue; // Reintentar con el nuevo token
                    }

                    // Si el refresh falla, hacer login completo
                    Log::warning('SEACE: Refresh falló, haciendo login completo');
                    if ($this->fullLogin()) {
                        $attempts++;
                        continue;
                    }
                }
            }

            // Si llegamos aquí, hay un error que no podemos manejar
            Log::error('SEACE: Error irrecuperable en petición', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return $response;
        }

        throw new Exception('Se alcanzó el máximo de reintentos en SEACE');
    }

    /**
     * Obtener contratos del buscador con configuración "Deep Dive"
     */
    public function fetchLatestContracts(int $year = null): array
    {
        $year = $year ?? now()->year;
        $pageSize = config('services.seace.page_size', 100);

        $endpoint = $this->baseUrl . config('services.seace.endpoints.buscador');

        $params = [
            'anio' => $year,
            'ruc' => $this->rucProveedor,
            'cotizaciones_enviadas' => false,
            'invitaciones_por_cotizar' => false,
            'lista_estado_contrato' => 2, // Vigente
            'orden' => 2, // Descendente por fecha
            'page' => 1,
            'page_size' => $pageSize,
        ];

        $this->debug('Consultando buscador', [
            'year' => $year,
            'page_size' => $pageSize
        ]);

        /** @var \Illuminate\Http\Client\Response $response */
        $response = $this->fetchWithRetry($endpoint, $params);

        if (!$response->successful()) {
            Log::error('SEACE: Error al obtener contratos', [
                'status' => $response->status()
            ]);
            return [];
        }

        $data = $response->json();

        if (!isset($data['data']) || !is_array($data['data'])) {
            Log::warning('SEACE: Respuesta sin datos válidos', [
                'response_keys' => array_keys($data)
            ]);
            return [];
        }

        $this->debug('Contratos obtenidos exitosamente', [
            'count' => count($data['data'])
        ]);

        return $data['data'];
    }

    /**
     * Obtener maestras (para filtros y validación)
     */
    public function fetchMaestra(string $tipo): array
    {
        $endpoints = [
            'objetos' => config('services.seace.endpoints.objeto_contratacion'),
            'estados' => config('services.seace.endpoints.estado_contratacion'),
            'departamentos' => config('services.seace.endpoints.departamentos'),
        ];

        if (!isset($endpoints[$tipo])) {
            throw new Exception("Tipo de maestra no válido: {$tipo}");
        }

        $endpoint = $this->baseUrl . $endpoints[$tipo];

        $this->debug("Consultando maestra: {$tipo}");

        /** @var \Illuminate\Http\Client\Response $response */
        $response = $this->fetchWithRetry($endpoint);

        if (!$response->successful()) {
            Log::error("SEACE: Error al obtener maestra {$tipo}", [
                'status' => $response->status()
            ]);
            return [];
        }

        return $response->json();
    }
}
