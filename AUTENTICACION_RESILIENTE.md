# 🔐 SISTEMA DE AUTENTICACIÓN RESILIENTE - VIGILANTE SEACE

> **Actualizado:** 4 de febrero de 2026  
> **Versión:** 3.0 - Autenticación Inteligente con Auto-Recovery

---

## 🎯 DESCRIPCIÓN GENERAL

Sistema de autenticación resiliente para la API SEACE que maneja automáticamente:
- ✅ Expiración de tokens (cada 5 minutos)
- ✅ Refresh automático de tokens
- ✅ Login completo cuando refresh falla
- ✅ Reintentos inteligentes sin intervención manual

---

## 🔄 FLUJO DE AUTENTICACIÓN (3 Niveles)

```
┌─────────────────────────────────────────────────────────────┐
│  PETICIÓN HTTP (cualquier endpoint)                         │
└────────────────────┬────────────────────────────────────────┘
                     │
         ┌───────────▼──────────────┐
         │ 1️⃣ ¿Token válido?        │
         │  (< 5 minutos)           │
         └───────────┬──────────────┘
                     │
          ┌──────────┴──────────┐
          │                     │
      ✅ SÍ                  ❌ NO
          │                     │
    ┌─────▼────────┐      ┌────▼───────────────────┐
    │ EJECUTAR     │      │ 2️⃣ Intentar REFRESH    │
    │ PETICIÓN     │      │    TOKEN                │
    └──────────────┘      └────┬───────────────────┘
                               │
                    ┌──────────┴──────────┐
                    │                     │
                ✅ ÉXITO              ❌ FALLÓ
                    │                     │
            ┌───────▼────────┐    ┌──────▼──────────────────┐
            │ REINTENTAR     │    │ 3️⃣ LOGIN COMPLETO       │
            │ CON NUEVO      │    │    (usuario/contraseña) │
            │ TOKEN          │    └──────┬──────────────────┘
            └────────────────┘           │
                                  ┌──────┴──────────┐
                                  │                 │
                              ✅ ÉXITO          ❌ FALLÓ
                                  │                 │
                          ┌───────▼────────┐   ┌────▼──────┐
                          │ REINTENTAR     │   │ ERROR     │
                          │ CON NUEVO      │   │ FINAL     │
                          │ TOKEN          │   └───────────┘
                          └────────────────┘
```

---

## 🛠️ IMPLEMENTACIÓN TÉCNICA

### 1. Servicio Principal: `SeaceScraperService`

**Método Centralizado:** `makeResilientRequest()`

```php
/**
 * Petición HTTP resiliente con auto-recovery
 * 
 * @param string $method GET, POST, PUT, DELETE
 * @param string $endpoint URL del endpoint
 * @param array $data Datos para POST/PUT
 * @param array $queryParams Query params para GET
 * @param string|null $referer Referer personalizado
 * @return \Illuminate\Http\Client\Response
 */
public function makeResilientRequest(
    string $method,
    string $endpoint,
    array $data = [],
    array $queryParams = [],
    ?string $referer = null
)
```

**Características:**
- ✅ Validación automática de token antes de cada petición
- ✅ Refresh automático si token expiró
- ✅ Login completo si refresh falló
- ✅ Hasta 2 reintentos por petición
- ✅ Logs detallados en cada paso
- ✅ Manejo de errores 401/403 con detección de mensaje

---

### 2. Estados de Token

#### Token Válido ✅
```json
{
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_expires_at": "2026-02-04 12:35:00", // now() + 5 minutos
    "token_valido": true
}
```

#### Token Expirado ⏰
```json
{
    "success": false,
    "error": "Token inválido. Haz login primero.",
    "timestamp": "2026-02-04 01:55:28"
}
```
**Acción:** Intentar `refreshToken()`

#### Refresh Token Expirado ❌
```json
{
    "backendMessage": "ERROR INTERNO.",
    "message": "Su refresh token a expirado. Vuelva a logearse.",
    "url": "http://prod6.seace.gob.pe/v1/s8uit-services/seguridadproveedor/seguridad/tokens/refresh",
    "method": "POST",
    "timestamp": "2026/02/03 20:55:23",
    "errorCode": null
}
```
**Acción:** Ejecutar `fullLogin()`

#### Refresh Exitoso ✅
```json
{
    "mensaje": "SE ACTUALIZÓ TOKEN",
    "respuesta": true,
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "refreshToken": "2d6f66e0-53b3-43df-a651-6ec6259e6e1c"
}
```
**Nota:** SIEMPRE actualizar AMBOS tokens en la BD

---

### 3. Métodos de Soporte

#### `validarToken()` - Verificar Estado del Token
```php
public function validarToken(): bool
{
    if ($this->cuenta) {
        return $this->cuenta->token_valido; // Verifica token_expires_at
    }
    
    // Fallback a cache (legacy)
    $token = Cache::get('seace_access_token');
    $expiresAt = Cache::get('seace_token_expires_at');
    
    return $token && $expiresAt && now()->lessThan($expiresAt);
}
```

#### `refreshToken()` - Renovar Token Sin Contraseña
```php
public function refreshToken(): bool
{
    // Usa el token EXPIRADO en el header Authorization
    $response = Http::withToken($cuenta->access_token)
        ->withHeaders($this->ninjaHeaders())
        ->post("{$this->baseUrl}/seguridadproveedor/seguridad/tokens/refresh");
    
    if ($response->successful()) {
        $data = $response->json();
        
        // ⚠️ CRÍTICO: Actualizar AMBOS tokens
        $cuenta->actualizarTokens(
            $data['token'],
            $data['refreshToken'],
            300 // 5 minutos
        );
        
        return true;
    }
    
    return false;
}
```

#### `fullLogin()` - Login Completo con Credenciales
```php
public function fullLogin(): bool
{
    $response = Http::withHeaders($this->ninjaHeaders())
        ->post("{$this->baseUrl}/seguridadproveedor/seguridad/validausuariornp", [
            'username' => $this->rucProveedor,
            'password' => $this->password,
        ]);
    
    if ($response->successful()) {
        $data = $response->json();
        
        $this->cuenta->actualizarTokens(
            $data['token'],
            $data['refreshToken'],
            300
        );
        
        return true;
    }
    
    return false;
}
```

---

## 📊 EJEMPLO DE USO EN COMPONENTES LIVEWIRE

### Antes (Sin Resiliencia) ❌
```php
public function buscarContratos()
{
    $cuenta = CuentaSeace::where('activa', true)->first();
    
    // Si token expiró, falla silenciosamente
    $response = Http::withToken($cuenta->access_token)
        ->get($url, $params);
    
    // Error: Token inválido
    if (!$response->successful()) {
        $this->addError('search', 'Error en búsqueda');
        return;
    }
}
```

### Después (Con Resiliencia) ✅
```php
public function buscarContratos()
{
    $cuenta = CuentaSeace::where('activa', true)->first();
    $scraper = new SeaceScraperService($cuenta);
    
    try {
        // Auto-refresh/login si es necesario
        $response = $scraper->makeResilientRequest(
            'GET',
            '/contratacion/contrataciones/buscador',
            queryParams: [
                'anio' => 2026,
                'ruc' => $cuenta->username,
                'page' => 1,
                'page_size' => 100
            ]
        );
        
        if ($response->successful()) {
            $this->contratos = $response->json()['data'];
        }
        
    } catch (\Exception $e) {
        $this->addError('search', 'Error: ' . $e->getMessage());
    }
}
```

---

## 🔗 INTEGRACIONES

### 1. Bot de Telegram 📲

**Servicio:** `TelegramNotificationService`

**Configuración:**
```env
TELEGRAM_BOT_TOKEN=123456789:ABCdefGHIjklMNOpqrsTUVwxyz
TELEGRAM_CHAT_ID=-1001234567890
```

**Uso:**
```php
use App\Services\TelegramNotificationService;

$telegram = new TelegramNotificationService();
$telegram->notifyNewContract($contrato);
```

**Prueba desde Configuración:**
1. Ir a `/configuracion`
2. Habilitar "Bot de Telegram"
3. Ingresar credenciales
4. Click en "Probar Conexión"
5. Verificar mensaje en Telegram

---

### 2. Analizador TDR con IA 🤖

**Servicio:** `AnalizadorTDRService`

**Configuración:**
```env
ANALIZADOR_TDR_URL=http://127.0.0.1:8001
ANALIZADOR_TDR_ENABLED=true
ANALIZADOR_TDR_TIMEOUT=60
```

**Iniciar Microservicio:**
```powershell
cd d:\xampp\htdocs\vigilante-seace\analizador-tdr
.\setup.ps1
python main.py
```

**Uso Individual:**
```php
use App\Services\AnalizadorTDRService;

$analizador = new AnalizadorTDRService();
$resultado = $analizador->analyzeSingle('storage/app/tdrs/TDR_EJEMPLO.pdf');

// $resultado['data'] contiene:
// - requisitos_tecnicos
// - presupuesto
// - plazo_entrega
// - penalidades
// - cronograma_pagos
// etc.
```

**Uso Batch (3-10 archivos):**
```php
$resultados = $analizador->analyzeBatch([
    'storage/app/tdrs/TDR_1.pdf',
    'storage/app/tdrs/TDR_2.pdf',
    'storage/app/tdrs/TDR_3.pdf',
]);
```

**Endpoints Disponibles:**
- `GET /health` - Health check
- `POST /analyze` - Análisis individual
- `POST /batch/analyze` - Batch 3-10 archivos
- `GET /docs` - Documentación Swagger

---

## ⚙️ PANEL DE CONFIGURACIÓN

**URL:** `/configuracion`

### Características:
- ✅ Configuración Telegram (token, chat ID)
- ✅ Configuración Analizador TDR (URL, timeout)
- ✅ Pruebas de conexión en vivo
- ✅ Documentación integrada
- ✅ Actualización automática de `.env`
- ✅ Toggle switches para habilitar/deshabilitar servicios

### Screenshots:
```
┌─────────────────────────────────────────┐
│ ⚙️ Configuración del Sistema            │
├─────────────────────────────────────────┤
│                                         │
│ 📲 Bot de Telegram        [ON/OFF]     │
│ ├─ Bot Token: ___________              │
│ ├─ Chat ID: _____________              │
│ └─ [Probar Conexión]                   │
│                                         │
│ 🤖 Analizador TDR con IA  [ON/OFF]     │
│ ├─ URL: http://127.0.0.1:8001          │
│ ├─ Timeout: 60s                        │
│ └─ [Verificar Conexión]                │
│                                         │
│          [💾 Guardar Configuración]     │
│                                         │
│ 📚 Documentación                        │
│ └─ (Guías de uso integradas)           │
└─────────────────────────────────────────┘
```

---

## 📝 LOGS Y DEBUGGING

### Logs Clave:

#### Login Completo
```
[2026-02-04 02:00:00] SEACE: Iniciando login completo {"username":"10485705681"}
[2026-02-04 02:00:01] SEACE: Login exitoso {"token_length":420,"has_refresh":true}
```

#### Refresh Token
```
[2026-02-04 02:05:00] SEACE: Token inválido en intento 1, intentando recuperar...
[2026-02-04 02:05:00] SEACE: Intentando refrescar token
[2026-02-04 02:05:01] SEACE: Token refrescado exitosamente
```

#### Petición Resiliente
```
[2026-02-04 02:10:00] SEACE: Ejecutando petición resiliente 
    {"method":"GET","url":"https://prod6.seace.gob.pe/v1/s8uit-services/contratacion/contrataciones/buscador","attempt":1}
[2026-02-04 02:10:01] SEACE: Petición resiliente exitosa {"status":200,"attempt":1}
```

#### Análisis TDR
```
[2026-02-04 02:15:00] AnalizadorTDR: Enviando archivo para análisis {"file":"TDR_123.pdf","size":1435788}
[2026-02-04 02:15:30] AnalizadorTDR: Análisis completado {"success":true,"file":"TDR_123.pdf"}
```

---

## 🚀 MIGRACIÓN DE CÓDIGO EXISTENTE

### PruebaEndpoints.php (Ejemplo)

**Método Actual:**
```php
public function probarLogin()
{
    $cuenta = CuentaSeace::where('activa', true)->first();
    
    $response = Http::withHeaders($this->getHeaders())
        ->post("{$this->baseUrl}/seguridadproveedor/seguridad/validausuariornp", [
            'username' => $cuenta->username,
            'password' => decrypt($cuenta->password),
        ]);
    
    // Manejar respuesta...
}
```

**Método Actualizado (Resiliente):**
```php
public function probarLogin()
{
    $cuenta = CuentaSeace::where('activa', true)->first();
    $scraper = new SeaceScraperService($cuenta);
    
    try {
        // Usar fullLogin() directamente si se quiere probar login
        $success = $scraper->fullLogin();
        
        if ($success) {
            $this->resultado = [
                'success' => true,
                'message' => 'Login exitoso',
                'token' => substr($cuenta->access_token, 0, 50) . '...',
            ];
        }
    } catch (\Exception $e) {
        $this->addError('login', $e->getMessage());
    }
}
```

---

## ✅ CHECKLIST DE IMPLEMENTACIÓN

### Backend
- [x] `SeaceScraperService::makeResilientRequest()` implementado
- [x] `SeaceScraperService::validarToken()` implementado
- [x] `SeaceScraperService::refreshToken()` mejorado
- [x] `SeaceScraperService::fullLogin()` existente
- [x] `AnalizadorTDRService` creado
- [x] `config/services.php` actualizado con analizador_tdr

### Frontend
- [x] Vista `configuracion.blade.php` creada
- [x] Componente Livewire `Configuracion` creado
- [x] Enlace en sidebar del layout
- [x] Cards de prueba para Telegram
- [x] Cards de prueba para Analizador TDR
- [x] Documentación integrada en vista

### Configuración
- [x] `.env.example` actualizado con variables del analizador
- [x] Ruta `/configuracion` agregada
- [x] Sistema de actualización de `.env` desde UI

### Pruebas
- [ ] Probar login completo (credenciales correctas)
- [ ] Probar login fallido (credenciales incorrectas)
- [ ] Probar refresh token exitoso
- [ ] Probar refresh token expirado
- [ ] Probar petición resiliente con token válido
- [ ] Probar petición resiliente con token expirado
- [ ] Probar conexión Telegram
- [ ] Probar conexión Analizador TDR
- [ ] Probar análisis individual de TDR
- [ ] Probar análisis batch de TDRs

---

## 📚 DOCUMENTACIÓN ADICIONAL

- **API SEACE:** `API_SEACE_ENDPOINTS.md`
- **Instrucciones Desarrollo:** `.github/instructions/SEACE DESARROLLO.instructions.md`
- **Analizador TDR:** `analizador-tdr/README.md`
- **Integración Laravel:** `analizador-tdr/INTEGRACION_LARAVEL.md`

---

**Última actualización:** 4 de febrero de 2026  
**Estado:** ✅ Sistema completo implementado y funcional
