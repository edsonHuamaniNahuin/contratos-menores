# 🤖 INSTRUCCIONES PARA GITHUB COPILOT - VIGILANTE SEACE

> **Proyecto:** Sistema de Monitoreo Automatizado de Contratos SEACE (Perú)  
> **Stack:** Laravel 12 + Blade + Livewire + Alpine.js + MySQL  
> **Arquitectura:** Monolito LAMP  
> **Inicio:** 29 de enero de 2026  
> **Path:** `d:\xampp\htdocs\vigilante-seace\`

---

## 🎨 ADN DE DISEÑO "SEQUENCE DASHBOARD"

### 1. REGLAS CROMÁTICAS ESTRICTAS

**PALETAS PERSONALIZADAS (tailwind.config.js)**

```javascript
colors: {
  primary: {
    900: '#012D32',
    800: '#025964',  // BASE
    600: '#2A737D',
    400: '#7BA8AD',
  },
  secondary: {
    600: '#00B368',
    500: '#00D47E',  // BASE
    400: '#29DA93',
    200: '#79E9BC',
  },
  neutral: {
    50: '#F9FAFB',   // bg-app
    100: '#FFFFFF',  // bg-card
    400: '#9CA3AF',  // text-subtitle
    600: '#4B5563',  // text-body
    900: '#111827',  // text-title
  }
}
```

**⛔ PROHIBICIONES CROMÁTICAS**
- NUNCA uses `blue`, `indigo`, `sky`, `red`, `yellow`, `green` de Tailwind estándar
- `secondary-*` SOLO para backgrounds/badges, NUNCA para texto sobre blanco
- Contraste: `text-neutral-900` en fondos claros, `text-white` en fondos oscuros

### 2. ADN ESTRUCTURAL Y VISUAL

**LAYOUT**
- Contenedor: `h-screen bg-neutral-50 flex overflow-hidden`
- Sidebar: `w-64 bg-white border-r border-neutral-100` (fixed left)
- Content: `flex-1 overflow-y-auto bg-neutral-50 p-6`
- Navbar: Fondo transparente, buscador `rounded-full`

**UI SHAPES & DEPTH**
- Botones/Inputs/Badges: `rounded-full` (cápsula)
- Tarjetas/Cards: `rounded-3xl` o `rounded-[2rem]`
- Sombras: `shadow-soft` (suave y difusa: `0 4px 20px -2px rgba(0,0,0,0.04)`)
- Tipografía: Sans-serif (Inter/Helvetica), jerarquía: `text-3xl font-bold text-neutral-900` (títulos) vs `text-sm text-neutral-400` (labels)

---

## 🚫 RESTRICCIONES TÉCNICAS ABSOLUTAS

**❌ PROHIBIDO:**
- Frontend: React, Vue, Next.js, TypeScript (frontend)
- Scraping: Puppeteer, Selenium, scripts Python/Node.js
- DB: PostgreSQL, MongoDB (solo MySQL + Eloquent)
- Arquitectura: Microservicios, APIs REST separadas

**✅ STACK APROBADO:**
- Backend: Laravel 12.49.0 + PHP 8.2.12 + MySQL
- Frontend: Blade + Livewire 4.1.0 + Alpine.js 3.x (CDN)
- Scraping: Laravel HTTP Client (Guzzle) + Artisan Commands
- Notificaciones: Telegram Bot API
- Servidor: Apache (XAMPP)


---

## 📊 ESTADO ACTUAL (ÚLTIMAS 48H)

### ✅ Core Completado
- Modelo `Contrato` con scopes/accessors
- `SeaceSyncCommand` - scraping automatizado
- `SeaceScraperService` - autenticación/sesión SEACE
- `TelegramNotificationService` - notificaciones
- `SeaceTestCommand` - diagnóstico
- Migración `contratos` con índices optimizados
- Variables `.env` completas (SEACE + Telegram)
- Layout `app.blade.php` - Sidebar + Navbar Sequence design
- Vista `home.blade.php` - Dashboard con diseño Sequence
- Vistas `cuentas/*` - CRUD completo con diseño Sequence aplicado
- **PruebaEndpoints.php** - Testing completo de endpoints API SEACE:
  * ✅ Login y autenticación
  * ✅ Refresh Token automático
  * ✅ Buscador de contratos con filtros (departamento, objeto, palabra clave)
  * ✅ Listado de archivos TDR por contrato
  * ✅ **Descarga directa de archivos PDF/TDR** (sin redirección a nueva pestaña)
  * ✅ **Análisis de TDR con IA** (Gemini 2.5 Flash / GPT-4o)
- **AnalizadorTDRService** - Integración con microservicio Python FastAPI:
  * ✅ Análisis individual de TDR
  * ✅ Extracción automática: Requisitos, Reglas de Ejecución, Penalidades, Monto
  * ✅ Prompt especializado en licitaciones peruanas

### ♻️ Ajustes recientes (feb-2026)
- `PruebaEndpoints` (Livewire) ahora arma cada URL mediante `buildSeaceUrl()` y rehidrata la config SEACE dentro de `bootstrapSeaceConfig()`/`hydrate()`, evitando que las acciones fallen cuando Livewire pierde `SEACE_BASE_URL` o `SEACE_FRONTEND_ORIGIN`.
- El bot de Telegram depende explícitamente de `TELEGRAM_API_BASE` en `.env`; si falta, el servicio se desactiva y deja una advertencia en logs para que no haya envíos silenciosos.

### 📝 Pendiente
- Dashboard contratos (tabla Livewire)
- Filtros avanzados (Livewire)
- Laravel Scheduler configurado
- Tests unitarios

---

## 🤖 ANÁLISIS TDR CON IA

### Prompt de Análisis (Template)
```
Analiza el siguiente fragmento de TDR de una entidad pública peruana. 
Extrae los Requisitos de Calificación (experiencia del postor), 
las Reglas de Ejecución (dónde y cómo se entrega) y cualquier 
Política de Penalidad. Si el documento menciona un Monto Referencial, 
extráelo. Responde estrictamente en formato JSON.
```

### Estructura de Respuesta JSON
```json
{
    "success": true,
    "data": {
        "requisitos_calificacion": "Experiencia mínima de 2 años...",
        "reglas_ejecucion": "Entrega en sede de la entidad...",
        "penalidades": "0.1% del monto por día de retraso...",
        "monto_referencial": "S/ 45,000.00"
    },
    "timestamp": "2026-02-04 12:30:00"
}
```

### Uso en Livewire
```php
public function analizarArchivo($idContratoArchivo, $nombreArchivo)
{
    $analizador = new AnalizadorTDRService();
    
    // 1. Descarga archivo si no existe
    $tempPath = storage_path("app/temp/{$nombreArchivo}");
    
    // 2. Analiza con IA
    $resultado = $analizador->analyzeSingle($tempPath);
    
    // 3. Muestra resultado estructurado
    $this->resultadoAnalisis = $resultado;
}
```

### Endpoint API (Microservicio Python)
- **URL:** `http://127.0.0.1:8001/analyze`
- **Método:** `POST`
- **Body:** Multipart form-data con archivo PDF
- **Timeout:** 60 segundos
- **LLM:** Gemini 2.5 Flash (1M tokens context)

---

## 🗂️ ESTRUCTURA CLAVE

```
app/
├── Console/Commands/
│   ├── SeaceSyncCommand.php      ✅
│   └── SeaceTestCommand.php      ✅
├── Models/Contrato.php            ✅
├── Services/
│   ├── SeaceScraperService.php   ✅
│   ├── TelegramNotificationService.php ✅
│   └── AnalizadorTDRService.php  ✅ NUEVO
resources/views/
├── layouts/app.blade.php          ✅ Sequence design
├── home.blade.php                 ✅ Sequence design
├── cuentas/                       ✅ CRUD completo
└── prueba-endpoints.blade.php     ✅
config/
├── livewire.php                   ✅
└── services.php                   ✅ (SEACE + Telegram)
```


---

## 🎨 CONVENCIONES DE CÓDIGO

### Laravel Way
```php
// ✅ Nombres
app/Models/Contrato.php                      // Singular PascalCase
app/Services/SeaceScraperService.php         // Sufijo "Service"
app/Http/Controllers/ContratoController.php  // Sufijo "Controller"

// ✅ Rutas
Route::get('/contratos', [ContratoController::class, 'index'])->name('contratos.index');

// ✅ Migraciones
Schema::create('contratos', function (Blueprint $table) {
    $table->id();
    $table->string('numero')->unique();
    $table->json('datos_raw')->nullable();
    $table->timestamps();
});

// ✅ Blade
@extends('layouts.app')
@section('content')
    @livewire('contratos-list')
@endsection

// ✅ Livewire (v3+)
use Livewire\Component;
new class extends Component {
    public $contratos = [];
    public function mount() { $this->contratos = Contrato::all(); }
};
```

---

## 📊 ARQUITECTURA DE DATOS

### Modelo Contrato
```php
protected $fillable = [
    'numero_contrato', 'entidad', 'objeto', 'monto',
    'fecha_publicacion', 'estado', 'datos_raw',
];

protected $casts = [
    'fecha_publicacion' => 'date',
    'monto' => 'decimal:2',
    'datos_raw' => 'array',
];

// Scopes
public function scopeActivos($query) { return $query->where('estado', 'activo'); }
public function scopeRecientes($query) { return $query->orderBy('fecha_publicacion', 'desc'); }
```

---

## 🔄 FLUJO DE SCRAPING

1. **Comando Artisan** (`SeaceSyncCommand`) → Ejecuta via Laravel Scheduler
2. **Servicio de Scraping** (`SeaceScraperService`) → Laravel HTTP Client + cookies/sesión
3. **Parser de Datos** → Transforma JSON a formato DB
4. **Almacenamiento** → Eloquent + Evento `NuevoContratoDetectado`
5. **Notificación** → Listener escucha evento → Envía Telegram

---

## 🔧 COMANDOS FRECUENTES

```bash
# Desarrollo
php artisan serve                      # http://127.0.0.1:8000
php artisan migrate:fresh              # Resetear DB
php artisan make:model Contrato -m     # Modelo + Migración
php artisan make:livewire ContratosList
php artisan make:command ExtractContracts

# Scraping SEACE
php artisan seace:sync                 # Sincronización manual
php artisan seace:test                 # Diagnóstico sistema

# Cachés
php artisan cache:clear && php artisan config:clear && php artisan view:clear

# Composer
composer dump-autoload
```

---

## 🐛 DEBUGGING

```php
// Logs Laravel (storage/logs/laravel.log)
use Illuminate\Support\Facades\Log;
Log::info('Iniciando extracción', ['fecha' => now()]);
Log::error('Error en scraping', ['exception' => $e->getMessage()]);

// Ver logs en tiempo real (PowerShell)
Get-Content storage\logs\laravel.log -Wait -Tail 50
```

---

## ✅ CHECKLIST PRE-CÓDIGO

Antes de sugerir código, verifica:
- [ ] ¿Funcionalidad ya existe?
- [ ] ¿Usas stack aprobado (Laravel/Blade/Livewire/Alpine.js)?
- [ ] ¿Evitas React/Vue/APIs REST?
- [ ] ¿Usas Laravel HTTP Client en lugar de Puppeteer?
- [ ] ¿Sigues convenciones de nombres?
- [ ] ¿Cumples diseño Sequence (colores aprobados + rounded-3xl)?
- [ ] ¿Consultas estado actual del proyecto?

---

## � DOCUMENTACIÓN COMPLETA DE LA API SEACE

### BASE URL
```
https://prod6.seace.gob.pe/v1/s8uit-services
```

### 1. FLUJO DE AUTENTICACIÓN RESILIENTE

#### 🔑 LOGIN INICIAL (Solo primera vez o cuando refresh falla)

**Endpoint:** `POST /seguridadproveedor/seguridad/validausuariornp`

**Request Body:**
```json
{
    "username": "10485705681",
    "password": "tu_contraseña"
}
```

**Response (200 OK):**
```json
{
    "mensaje": "LA AUTENTICACIÓN DEL PROVEEDOR SE REALIZÓ CORRECTAMENTE",
    "respuesta": true,
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "refreshToken": "b3ab9aa3-9517-4034-85cb-f1b524a69b03"
}
```

**⚠️ IMPORTANTE:**
- El `token` (JWT) expira en **5 minutos**
- El `refreshToken` (UUID) se usa para obtener un nuevo token sin contraseña
- **GUARDAR AMBOS EN LA BASE DE DATOS** (tabla `cuentas_seace`)

---

#### 🔄 REFRESH TOKEN (Usar automáticamente cuando token expira)

**Endpoint:** `POST /seguridadproveedor/seguridad/tokens/refresh`

**Headers:**
```
Authorization: Bearer {TOKEN_EXPIRADO}
```

**Request Body:** (Vacío o el header es suficiente)

**Response (200 OK):**
```json
{
    "mensaje": "SE ACTUALIZÓ TOKEN",
    "respuesta": true,
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "refreshToken": "8b09f7fa-bbaf-42aa-b0ef-b255c6bcc6af"
}
```

**⚠️ CRÍTICO:**
- ACTUALIZAR **AMBOS** tokens en la BD inmediatamente
- El servidor devuelve un **NUEVO** `refreshToken` en cada refresh

---

#### 🚨 DETECCIÓN DE TOKEN EXPIRADO

Cuando cualquier petición devuelve **401 Unauthorized**, verificar el JSON:

```json
{
    "backendMessage": "ERROR INTERNO.",
    "message": "TOKEN_EXPIRED",
    "url": "http://prod6.seace.gob.pe/v1/s8uit-services/contratacion/error",
    "method": "GET",
    "timestamp": "2026/01/29 23:47:41",
    "errorCode": "TOKEN_EXPIRED"
}
```

**Condición de Refresh:** `status === 401 && errorCode === "TOKEN_EXPIRED"`

---

### 2. ALGORITMO DE PETICIONES RESILIENTES

**PATRÓN "RETRY ON 401"** - Implementar en `SeaceScraperService`:

```php
/**
 * Realiza una petición HTTP con retry automático en caso de token expirado
 */
public function fetchWithRetry($url, $params = [])
{
    $cuenta = $this->getCuentaActiva(); // Obtener cuenta de BD
    
    // 1️⃣ INTENTO INICIAL con token guardado
    $response = Http::withToken($cuenta->access_token)
        ->withHeaders($this->getHeaders())
        ->get($url, $params);

    // 2️⃣ DETECTAR EXPIRACIÓN
    if ($response->status() === 401 && $response->json('errorCode') === 'TOKEN_EXPIRED') {
        
        Log::info('Token expirado, intentando refresh...', ['cuenta_id' => $cuenta->id]);
        
        // 3️⃣ INTENTAR REFRESH
        if ($this->refreshToken($cuenta)) {
            
            // 4️⃣ REINTENTO con nuevo token
            $cuenta->refresh(); // Recargar desde BD
            return Http::withToken($cuenta->access_token)
                ->withHeaders($this->getHeaders())
                ->get($url, $params);
        }
        
        // 5️⃣ FALLBACK: Login completo si refresh falló
        Log::warning('Refresh falló, haciendo login completo...', ['cuenta_id' => $cuenta->id]);
        $this->fullLogin($cuenta);
        
        // 6️⃣ ÚLTIMO INTENTO después de login
        $cuenta->refresh();
        return Http::withToken($cuenta->access_token)
            ->withHeaders($this->getHeaders())
            ->get($url, $params);
    }

    return $response;
}

/**
 * Refresca el token usando el refreshToken
 */
private function refreshToken($cuenta)
{
    try {
        $response = Http::withToken($cuenta->access_token)
            ->withHeaders($this->getHeaders())
            ->post($this->baseUrl . '/seguridadproveedor/seguridad/tokens/refresh');

        if ($response->successful()) {
            $data = $response->json();
            
            // ⚠️ ACTUALIZAR AMBOS TOKENS en BD
            $cuenta->update([
                'access_token' => $data['token'],
                'refresh_token' => $data['refreshToken'],
                'token_expires_at' => now()->addMinutes(5),
            ]);
            
            Log::info('Token refrescado exitosamente', ['cuenta_id' => $cuenta->id]);
            return true;
        }
        
        return false;
    } catch (\Exception $e) {
        Log::error('Error al refrescar token', [
            'cuenta_id' => $cuenta->id,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

/**
 * Login completo con usuario y contraseña
 */
private function fullLogin($cuenta)
{
    $response = Http::withHeaders($this->getHeaders())
        ->post($this->baseUrl . '/seguridadproveedor/seguridad/validausuariornp', [
            'username' => $cuenta->username,
            'password' => decrypt($cuenta->password), // Desencriptar de BD
        ]);

    if ($response->successful()) {
        $data = $response->json();
        
        $cuenta->update([
            'access_token' => $data['token'],
            'refresh_token' => $data['refreshToken'],
            'token_expires_at' => now()->addMinutes(5),
            'last_login_at' => now(),
        ]);
        
        Log::info('Login completo exitoso', ['cuenta_id' => $cuenta->id]);
    }
}
```

---

### 3. ENDPOINTS DE DATOS MAESTROS

#### 📋 Listar Objetos de Contratación

**Endpoint:** `GET /maestra/maestras/listar-objeto-contratacion`

**Headers:** `Authorization: Bearer {TOKEN}`

**Response:**
```json
[
    { "id": 1, "nom": "Bien", "abr": "." },
    { "id": 4, "nom": "Consultoría de Obra", "abr": "." },
    { "id": 3, "nom": "Obra", "abr": "." },
    { "id": 2, "nom": "Servicio", "abr": "." }
]
```

---

#### 📊 Listar Estados de Contratación

**Endpoint:** `GET /maestra/maestras/listar-estado-contratacion`

**Headers:** `Authorization: Bearer {TOKEN}`

**Response:**
```json
[
    { "id": 1, "nom": "Borrador", "abr": "." },
    { "id": 4, "nom": "Culminado", "abr": "." },
    { "id": 3, "nom": "En Evaluación", "abr": "." },
    { "id": 2, "nom": "Vigente", "abr": "." }
]
```

---

#### 🗺️ Listar Departamentos

**Endpoint:** `GET /maestra/maestras/listar-departamento`

**Headers:** `Authorization: Bearer {TOKEN}`

**Response:**
```json
[
    {
        "id": 1,
        "nom": "AMAZONAS",
        "abr": null,
        "ubigeoInei": "01",
        "ubigeoInei2": "01"
    },
    {
        "id": 15,
        "nom": "LIMA",
        "abr": null,
        "ubigeoInei": "15",
        "ubigeoInei2": "15"
    }
    // ... resto de departamentos
]
```

---

#### 🔔 Notificaciones Pendientes

**Endpoint:** `GET /subsanacion/subsana-notificaciones/pendientes`

**Headers:** `Authorization: Bearer {TOKEN}`

**Response:** (Array de notificaciones)

---

### 4. ENDPOINT PRINCIPAL DE BÚSQUEDA (BUSCADOR)

**Endpoint:** `GET /contratacion/contrataciones/buscador`

**Headers:** `Authorization: Bearer {TOKEN}`

**Query Parameters:**

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| `anio` | integer | Año de búsqueda | `2024` |
| `ruc` | string | RUC del proveedor | `10485705681` |
| `cotizaciones_enviadas` | boolean | Filtrar cotizaciones enviadas | `false` |
| `invitaciones_por_cotizar` | boolean | Filtrar invitaciones pendientes | `false` |
| `lista_estado_contrato` | integer | ID del estado (ver maestro) | `2` (Vigente) |
| `lista_objeto_contrato` | integer | ID del objeto (ver maestro) | `1,2,3,4` (separados por coma) |
| `palabra_clave` | string | Búsqueda en descripción | `laptop` |
| `orden` | integer | Ordenamiento (1=Asc, 2=Desc) | `2` |
| `page` | integer | Número de página | `1` |
| `page_size` | integer | Resultados por página (MAX: 100) | `100` |

**⚠️ ESTRATEGIA CRÍTICA:**
- **SIEMPRE** usar `page_size=100` para minimizar peticiones
- Calcular páginas: `totalPages = ceil(totalElements / 100)`

**URL Ejemplo (Extracción Deep Dive):**
```
/contratacion/contrataciones/buscador?anio=2024&ruc=10485705681&cotizaciones_enviadas=false&invitaciones_por_cotizar=false&lista_estado_contrato=2&orden=2&page=1&page_size=100
```

**Response Ejemplo:**
```json
{
    "data": [
        {
            "secuencia": 1,
            "idContrato": 40651,
            "nroContratacion": 19,
            "desContratacion": "CM-19-2026-MDH/CM",
            "idObjetoContrato": 2,
            "nomObjetoContrato": "Servicio",
            "desObjetoContrato": "SERVICIO DE AUXILIAR ADMINISTRATIVO PARA LA OFICINA DE ASESORIA LEGAL",
            "nomEtapaContratacion": "ETAPA DE COTIZACIÓN",
            "fecIniCotizacion": "02/02/2026 08:00:00",
            "fecFinCotizacion": "02/02/2026 17:30:00",
            "cotizar": false,
            "idEstadoContrato": 2,
            "nomEstadoContrato": "Vigente",
            "fecPublica": "29/01/2026 23:29:01",
            "idTipoCotizacion": 2,
            "idCotizacion": null,
            "idEstadoCotiza": null,
            "nomEstadoCotiza": null,
            "nomEntidad": "MUNICIPALIDAD DISTRITAL DE HUACHON",
            "numSubsanacionesTotal": 0,
            "numSubsanacionesPendientes": 0,
            "fecLimiteSubsanaMax": null
        }
    ],
    "pageable": {
        "pageNumber": 1,
        "pageSize": 100,
        "totalElements": 29552
    }
}
```

---

### 5. MAPEO DE CAMPOS SEACE → BASE DE DATOS

**Tabla `contratos` (Migración Laravel):**

```php
Schema::create('contratos', function (Blueprint $table) {
    $table->id();
    
    // Identificador único del SEACE (CLAVE PRIMARIA FUNCIONAL)
    $table->unsignedBigInteger('id_contrato_seace')->unique();
    
    // Datos básicos
    $table->integer('nro_contratacion');
    $table->string('codigo_proceso'); // desContratacion
    
    // Información de la entidad
    $table->string('entidad'); // nomEntidad
    
    // Objeto del contrato
    $table->unsignedTinyInteger('id_objeto_contrato'); // idObjetoContrato
    $table->string('objeto'); // nomObjetoContrato
    $table->text('descripcion'); // desObjetoContrato
    
    // Estado
    $table->unsignedTinyInteger('id_estado_contrato'); // idEstadoContrato
    $table->string('estado'); // nomEstadoContrato
    
    // Fechas importantes
    $table->dateTime('fecha_publicacion'); // fecPublica
    $table->dateTime('inicio_cotizacion'); // fecIniCotizacion
    $table->dateTime('fin_cotizacion'); // fecFinCotizacion
    
    // Etapa
    $table->string('etapa_contratacion')->nullable(); // nomEtapaContratacion
    
    // Datos adicionales
    $table->unsignedTinyInteger('id_tipo_cotizacion')->nullable(); // idTipoCotizacion
    $table->unsignedInteger('num_subsanaciones_total')->default(0);
    $table->unsignedInteger('num_subsanaciones_pendientes')->default(0);
    
    // JSON completo por si acaso
    $table->json('datos_raw')->nullable();
    
    // Auditoría
    $table->timestamps();
    
    // Índices para búsquedas rápidas
    $table->index('estado');
    $table->index('fecha_publicacion');
    $table->index('fin_cotizacion');
    $table->index(['entidad', 'estado']);
});
```

---

### 6. LÓGICA DE GUARDADO INTELIGENTE (UPSERT)

**Comando de Sincronización (`SeaceSyncCommand`):**

```php
public function handle()
{
    $cuenta = CuentaSeace::where('activa', true)->first();
    
    if (!$cuenta) {
        $this->error('No hay cuenta SEACE activa configurada');
        return Command::FAILURE;
    }
    
    $scraper = new SeaceScraperService();
    
    // Obtener año actual
    $anio = now()->year;
    
    $this->info("Sincronizando contratos del año {$anio}...");
    
    // Petición con page_size máximo
    $response = $scraper->fetchWithRetry(
        '/contratacion/contrataciones/buscador',
        [
            'anio' => $anio,
            'ruc' => $cuenta->username,
            'cotizaciones_enviadas' => false,
            'invitaciones_por_cotizar' => false,
            'lista_estado_contrato' => 2, // Solo "Vigente"
            'orden' => 2, // Descendente (más recientes primero)
            'page' => 1,
            'page_size' => 100,
        ]
    );
    
    if (!$response->successful()) {
        $this->error('Error al obtener contratos: ' . $response->body());
        return Command::FAILURE;
    }
    
    $data = $response->json();
    $contratos = $data['data'];
    $totalElements = $data['pageable']['totalElements'];
    
    $this->info("Total de contratos encontrados: {$totalElements}");
    
    $nuevos = 0;
    $actualizados = 0;
    
    foreach ($contratos as $item) {
        $contrato = Contrato::updateOrCreate(
            // CLAVE ÚNICA: idContrato del SEACE
            ['id_contrato_seace' => $item['idContrato']],
            
            // DATOS A ACTUALIZAR
            [
                'nro_contratacion' => $item['nroContratacion'],
                'codigo_proceso' => $item['desContratacion'],
                'entidad' => $item['nomEntidad'],
                'id_objeto_contrato' => $item['idObjetoContrato'],
                'objeto' => $item['nomObjetoContrato'],
                'descripcion' => $item['desObjetoContrato'],
                'id_estado_contrato' => $item['idEstadoContrato'],
                'estado' => $item['nomEstadoContrato'],
                'fecha_publicacion' => Carbon::createFromFormat('d/m/Y H:i:s', $item['fecPublica']),
                'inicio_cotizacion' => Carbon::createFromFormat('d/m/Y H:i:s', $item['fecIniCotizacion']),
                'fin_cotizacion' => Carbon::createFromFormat('d/m/Y H:i:s', $item['fecFinCotizacion']),
                'etapa_contratacion' => $item['nomEtapaContratacion'],
                'id_tipo_cotizacion' => $item['idTipoCotizacion'],
                'num_subsanaciones_total' => $item['numSubsanacionesTotal'],
                'num_subsanaciones_pendientes' => $item['numSubsanacionesPendientes'],
                'datos_raw' => $item, // Guardar JSON completo
            ]
        );
        
        // 🚨 DISPARAR ALERTA SOLO PARA CONTRATOS NUEVOS
        if ($contrato->wasRecentlyCreated) {
            $nuevos++;
            
            // Enviar notificación Telegram
            TelegramNotificationService::enviarAlerta($contrato);
            
            $this->info("✅ NUEVO: {$contrato->codigo_proceso} - {$contrato->entidad}");
        } else {
            $actualizados++;
        }
    }
    
    $this->info("✅ Sincronización completada:");
    $this->info("   - Nuevos: {$nuevos}");
    $this->info("   - Actualizados: {$actualizados}");
    
    return Command::SUCCESS;
}
```

---

### 7. ESTRUCTURA DE LA TABLA `cuentas_seace`

**Migración:**

```php
Schema::create('cuentas_seace', function (Blueprint $table) {
    $table->id();
    $table->string('nombre')->comment('Nombre descriptivo de la cuenta');
    $table->string('username')->comment('DNI o RUC del proveedor');
    $table->text('password')->comment('Contraseña encriptada');
    
    // Tokens
    $table->text('access_token')->nullable();
    $table->text('refresh_token')->nullable();
    $table->timestamp('token_expires_at')->nullable();
    
    // Estado
    $table->boolean('activa')->default(false);
    $table->timestamp('last_login_at')->nullable();
    
    $table->timestamps();
    
    // Solo una cuenta activa a la vez
    $table->unique(['username']);
    $table->index('activa');
});
```

**⚠️ SEGURIDAD:**
- La contraseña DEBE guardarse encriptada: `encrypt($password)`
- Al usar: `decrypt($cuenta->password)`

---

### 8. HEADERS OBLIGATORIOS (Ninja Mode)

**Método en `SeaceScraperService`:**

```php
private function getHeaders()
{
    return [
        'Accept' => 'application/json, text/plain, */*',
        'Accept-Language' => 'es-419,es;q=0.9',
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        'Origin' => 'https://prod6.seace.gob.pe',
        'Referer' => 'https://prod6.seace.gob.pe/auth-proveedor/busqueda',
        'Sec-Ch-Ua' => '"Google Chrome";v="131", "Chromium";v="131", "Not_A Brand";v="24"',
        'Sec-Ch-Ua-Mobile' => '?0',
        'Sec-Ch-Ua-Platform' => '"Windows"',
        'Sec-Fetch-Dest' => 'empty',
        'Sec-Fetch-Mode' => 'cors',
        'Sec-Fetch-Site' => 'same-origin',
    ];
}
```

---

### 9. NOTIFICACIONES TELEGRAM (Solo para NUEVOS contratos)

**Servicio `TelegramNotificationService`:**

```php
public static function enviarAlerta($contrato)
{
    $token = config('services.telegram.bot_token');
    $chatId = config('services.telegram.chat_id');

    $mensaje = "🔔 *NUEVA CONVOCATORIA SEACE*\n\n"
             . "🏢 *Entidad:* {$contrato->entidad}\n"
             . "📝 *Código:* {$contrato->codigo_proceso}\n"
             . "🎯 *Objeto:* {$contrato->objeto}\n"
             . "📋 *Descripción:* " . Str::limit($contrato->descripcion, 200) . "\n"
             . "💼 *Estado:* {$contrato->estado}\n"
             . "📅 *Publicado:* {$contrato->fecha_publicacion->format('d/m/Y H:i')}\n"
             . "⏰ *Fin Cotización:* {$contrato->fin_cotizacion->format('d/m/Y H:i')}\n\n"
             . "🔗 [Ver en el SEACE](https://prod6.seace.gob.pe/auth-proveedor/busqueda)";

    return Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
        'chat_id' => $chatId,
        'text' => $mensaje,
        'parse_mode' => 'Markdown',
    ]);
}
```

---

### 10. SCHEDULER DE LARAVEL (Ejecución Automática)

**Archivo `app/Console/Kernel.php`:**

```php
protected function schedule(Schedule $schedule)
{
    // Ejecutar cada 42-50 minutos aleatorios
    $schedule->command('seace:sync')
        ->everyMinute()
        ->when(function () {
            // Lógica para ejecutar solo cada 42-50 minutos
            $lastRun = Cache::get('seace_last_run', now()->subHour());
            $minutesSinceLastRun = now()->diffInMinutes($lastRun);
            
            if ($minutesSinceLastRun >= rand(42, 50)) {
                Cache::put('seace_last_run', now());
                return true;
            }
            
            return false;
        })
        ->withoutOverlapping()
        ->runInBackground();
}
```

**Activar Scheduler (Cron en producción):**
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

---

### 11. VARIABLES DE ENTORNO (.env)

```env
# SEACE API
SEACE_BASE_URL=https://prod6.seace.gob.pe/v1/s8uit-services

# Telegram Bot
TELEGRAM_BOT_TOKEN=123456789:ABCdefGHIjklMNOpqrsTUVwxyz
TELEGRAM_CHAT_ID=-1001234567890
```

**Archivo `config/services.php`:**
```php
return [
    'seace' => [
        'base_url' => env('SEACE_BASE_URL'),
    ],
    
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
    ],
];
```

---

### 12. RESUMEN DE ESTRATEGIA "NINJA" 🥷

1. **Autenticación Inteligente:**
   - Login inicial → Guardar token + refreshToken en BD
   - Usar refresh automático cada 5 minutos (antes de que expire)
   - Solo re-login con contraseña si refresh falla

2. **Scraping Eficiente:**
   - `page_size=100` para minimizar peticiones
   - Headers de navegador real para pasar desapercibido
   - Intervalo aleatorio 42-50 minutos (no robótico)

3. **Persistencia Inteligente:**
   - `updateOrCreate` con `id_contrato_seace` como clave única
   - Evita duplicados y permite tracking de cambios de estado
   - Solo notifica contratos NUEVOS (`wasRecentlyCreated`)

4. **Resiliencia:**
   - Retry automático en expiración de token
   - Logs detallados para debugging
   - Fallback a login completo si refresh falla

---

## 📞 RECURSOS

- **Laravel 11 Docs:** https://laravel.com/docs/11.x
- **Livewire 3 Docs:** https://livewire.laravel.com/docs
- **Alpine.js:** https://alpinejs.dev/
- **SEACE Portal:** https://prod6.seace.gob.pe/auth-proveedor/busqueda
- **Telegram Bot API:** https://core.telegram.org/bots/api

---

**Última actualización:** 30 de enero de 2026  
**Versión:** 3.0 🚀  
**Estado:** Core funcional ✅ | Diseño Sequence aplicado ✅ | API SEACE documentada ✅
{
    public $contratos = [];
    
    public function mount()
    {
        $this->contratos = Contrato::all();
    }
};
?>

<div>
    @foreach($contratos as $contrato)
        <div wire:key="contrato-{{ $contrato->id }}">
            {{ $contrato->numero_contrato }}
        </div>
    @endforeach
</div>
```

---

## 🔧 COMANDOS ÚTILES DEL PROYECTO

### Comandos Artisan Frecuentes

```bash
# Limpiar cachés
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Crear elementos del proyecto
php artisan make:model Contrato -m        # Modelo + Migración
php artisan make:controller ContratoController
php artisan make:command ExtractSeaceContracts
php artisan make:livewire ContratosList
php artisan make:event NuevoContratoDetectado
php artisan make:listener EnviarNotificacionTelegram

# Base de datos
php artisan migrate                       # Ejecutar migraciones
php artisan migrate:fresh                 # Resetear DB y re-migrar
php artisan migrate:rollback              # Revertir última migración

**Última actualización:** 30 de enero de 2026  
**Versión:** 2.1  
**Estado:** Core funcional ✅ | Diseño Sequence aplicado ✅


