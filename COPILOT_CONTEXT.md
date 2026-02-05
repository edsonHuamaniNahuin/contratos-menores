# 🤖 CONTEXTO DEL PROYECTO PARA GITHUB COPILOT

> **IMPORTANTE:** Lee este documento completo antes de sugerir código o arquitectura.
> Este archivo se actualiza continuamente para reflejar el estado actual del proyecto.

---

## 📋 INFORMACIÓN GENERAL DEL PROYECTO

### Nombre del Proyecto
**Vigilante SEACE** - Sistema de Monitoreo Automatizado de Contratos Menores SEACE (Perú)

### Objetivo del MVP
Automatizar la extracción de datos de "contratos menores" del portal gubernamental SEACE (Sistema Electrónico de Contrataciones del Estado - Perú), almacenarlos en una base de datos propia y notificar vía Telegram cuando aparezcan nuevas oportunidades relevantes.

### Fecha de Inicio
29 de enero de 2026

### Ubicación del Proyecto
```
d:\xampp\htdocs\vigilante-seace\
```

---

## 🎯 TU ROL COMO ASISTENTE

Eres un **Desarrollador Senior experto en Laravel** asignado al proyecto "Vigilante SEACE".

Tu responsabilidad es:
- ✅ Sugerir código siguiendo las **restricciones técnicas** definidas abajo
- ✅ Revisar el **estado actual** del proyecto antes de sugerir cambios
- ✅ Evitar duplicar funcionalidades ya existentes
- ✅ Mantener consistencia con la arquitectura establecida
- ✅ Seguir las mejores prácticas de Laravel y PSR

---

## 🚫 RESTRICCIONES TÉCNICAS ABSOLUTAS

### ❌ PROHIBIDO (No sugieras NUNCA estas tecnologías):

1. **Frontend Externo:**
   - React, Vue, Svelte, Angular, Next.js
   - APIs REST separadas para consumir desde SPA
   - TypeScript en el frontend (solo usar si es inevitable)

2. **Scraping Externo:**
   - Puppeteer, Playwright, Selenium
   - Scripts en Python (Beautiful Soup, Scrapy)
   - Scripts en Node.js
   - Scripts en Java
   - Herramientas headless browser externas

3. **Base de Datos:**
   - MongoDB, PostgreSQL, SQLite (solo MySQL)
   - ORMs externos (solo Eloquent)

4. **Microservicios:**
   - No separar en múltiples aplicaciones
   - No dockerizar microservicios independientes

---

## ✅ STACK TECNOLÓGICO APROBADO

### Backend Core
- **Framework:** Laravel 12.49.0
- **Lenguaje:** PHP 8.2.12
- **Servidor Web:** Apache (XAMPP)
- **Base de Datos:** MySQL

### Frontend (Renderizado en Servidor)
- **Motor de Plantillas:** Laravel Blade (obligatorio)
- **Interactividad Dinámica:** Laravel Livewire 4.1.0 (para tablas, filtros AJAX)
- **Micro-interacciones UI:** Alpine.js 3.x (CDN)
- **NO usar:** jQuery, React, Vue

### Extracción de Datos (Scraping)
- **Cliente HTTP:** Laravel HTTP Client (wrapper de Guzzle 7.10.0)
- **Automatización:** Artisan Commands + Laravel Scheduler
- **Ejecución:** Comandos PHP nativos de Laravel

### Notificaciones
- **Canal:** Telegram Bot API
- **Implementación:** Eventos y Listeners de Laravel

### Arquitectura
- **Tipo:** Monolito Majestuoso (Laravel Monolith)
- **Infraestructura:** Stack LAMP (Linux/Apache/MySQL/PHP)

---

## 📁 ESTADO ACTUAL DEL PROYECTO

### ✅ Completado

#### 1. Inicialización del Proyecto
- [x] Laravel 12 instalado vía Composer
- [x] Archivo `.env` configurado para MySQL
- [x] Base de datos `vigilante_seace` creada
- [x] Migraciones iniciales ejecutadas

#### 2. Base de Datos
- [x] Conexión MySQL establecida
- [x] Migración de contratos actualizada con estructura completa:
  - `id_contrato_seace` (PK), `nro_contratacion`, `codigo_proceso`, 
  - `entidad`, `objeto`, `descripcion`, `estado`,
  - `fecha_publicacion`, `inicio_cotizacion`, `fin_cotizacion`,
  - `datos_raw` (JSON), `timestamps`
  - Índices optimizados para consultas frecuentes

#### 3. Frontend Base
- [x] Layout principal: `resources/views/layouts/app.blade.php`
  - Incluye Alpine.js vía CDN
  - Incluye Livewire styles y scripts
  - Diseño responsivo básico
- [x] Vista home: `resources/views/home.blade.php`
  - Extiende layout principal
  - Incluye demo de Alpine.js funcional
  - Incluye componente Livewire funcional

#### 4. Livewire
- [x] Laravel Livewire 4.1.0 instalado
- [x] Configuración publicada: `config/livewire.php`
- [x] Componente de ejemplo: `⚡contratos-list`
  - Contador interactivo (incremento/decremento)
  - Actualización sin recarga de página (AJAX)

#### 5. Rutas
- [x] Ruta principal: `GET /` → `home.blade.php`

#### 6. Servidor
- [x] Servidor de desarrollo corriendo en `http://127.0.0.1:8000`

---

### 🔄 En Progreso

Ninguna tarea en progreso actualmente.

---

### 📝 Pendiente de Implementar

#### Alta Prioridad (Core Completado ✅)
1. [x] **Modelo Contrato** con relaciones y casts ✅
2. [x] **Comando Artisan:** `SeaceSyncCommand` para scraping ✅
3. [x] **Servicio HTTP:** `SeaceScraperService` para login/cookies/sesión SEACE ✅
4. [x] **Parser de Datos:** Transformar JSON de SEACE a formato DB ✅
5. [x] **Integración Telegram:** `TelegramNotificationService` para notificaciones ✅
6. [x] **Variables de entorno:** Configuración completa en .env ✅
7. [x] **Comando de prueba:** `SeaceTestCommand` para diagnóstico ✅

#### Media Prioridad
8. [ ] **Dashboard:** Vista con tabla Livewire de contratos
9. [ ] **Sistema de Filtros:** Livewire component para filtrar contratos
10. [ ] **Programación Automática:** Configurar Laravel Scheduler

#### Baja Prioridad
11. [ ] **Sistema de Logs Avanzado:** Panel visual de extracciones
12. [ ] **Panel de Configuración:** Parámetros de búsqueda en UI
13. [ ] **Tests:** Unitarios y de integración
14. [ ] **Deployment:** Scripts para producción

---

## 🗂️ ESTRUCTURA DE ARCHIVOS ACTUAL

```
vigilante-seace/
├── app/
│   ├── Console/
│   │   ├── Kernel.php                    # Aquí se registran comandos y schedule
│   │   └── Commands/
│   │       ├── SeaceSyncCommand.php      # ✅ Comando de sincronización SEACE
│   │       └── SeaceTestCommand.php      # ✅ Comando de diagnóstico
│   ├── Http/
│   │   ├── Controllers/                  # [VACÍO] Agregar controllers aquí
│   │   └── Livewire/                     # [VACÍO] Componentes Livewire aquí
│   ├── Models/
│   │   ├── User.php                      # Modelo por defecto
│   │   └── Contrato.php                  # ✅ Modelo de contratos con scopes
│   ├── Events/                           # [VACÍO] Agregar eventos aquí
│   ├── Listeners/                        # [VACÍO] Agregar listeners aquí
│   └── Services/
│       ├── SeaceScraperService.php       # ✅ Servicio de scraping y autenticación
│       └── TelegramNotificationService.php # ✅ Servicio de notificaciones
│
├── config/
│   ├── app.php
│   ├── database.php
│   ├── livewire.php                      # ✅ Configuración Livewire
│   └── services.php                      # ✅ Configuración SEACE y Telegram
│
├── database/
│   └── migrations/
│       ├── 0001_01_01_000000_create_users_table.php
│       ├── 0001_01_01_000001_create_cache_table.php
│       ├── 0001_01_01_000002_create_jobs_table.php
│       └── 2026_01_29_210850_create_contratos_table.php  # ✅ Tabla contratos actualizada
│
├── resources/
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php             # ✅ Layout principal con Alpine.js + Livewire
│       ├── components/
│       │   └── ⚡contratos-list.blade.php # ✅ Componente Livewire de ejemplo
│       ├── home.blade.php                # ✅ Vista principal
│       └── welcome.blade.php             # [NO SE USA] Vista por defecto Laravel
│
├── routes/
│   ├── web.php                           # ✅ Ruta GET / configurada
│   ├── api.php                           # [NO SE USA] No crear APIs REST
│   └── console.php                       # Para comandos Artisan
│
├── .env                                  # ✅ Configurado con variables SEACE y Telegram
├── .env.example                          # ✅ Plantilla de configuración
├── composer.json                         # ✅ Dependencias instaladas
├── COPILOT_CONTEXT.md                    # 📄 ESTE ARCHIVO
└── SETUP_GUIDE.md                        # 📘 Guía de configuración y uso
```

---

## 🎨 CONVENCIONES DE CÓDIGO

### Nombres de Clases y Archivos

```php
// ✅ CORRECTO
app/Models/Contrato.php                 // Singular, PascalCase
app/Services/SeaceScraperService.php    // Sufijo "Service"
app/Http/Controllers/ContratoController.php  // Sufijo "Controller"
app/Console/Commands/ExtractSeaceContracts.php  // PascalCase

// ❌ INCORRECTO
app/Models/contratos.php                // Minúsculas
app/Services/seace_scraper.php          // Snake_case
```

### Rutas

```php
// ✅ CORRECTO - Usar nombres descriptivos
Route::get('/contratos', [ContratoController::class, 'index'])->name('contratos.index');
Route::get('/contratos/{id}', [ContratoController::class, 'show'])->name('contratos.show');

// ❌ INCORRECTO - Rutas confusas o genéricas
Route::get('/list', [ContratoController::class, 'index']);
```

### Migraciones

```php
// ✅ CORRECTO - Nombres descriptivos, usar tipos correctos
Schema::create('contratos', function (Blueprint $table) {
    $table->id();
    $table->string('numero_contrato')->unique();
    $table->string('entidad');
    $table->text('objeto');
    $table->decimal('monto', 15, 2);
    $table->date('fecha_publicacion');
    $table->string('estado')->default('activo');
    $table->json('datos_raw')->nullable();
    $table->timestamps();
});

// ❌ INCORRECTO - Usar text para todo
$table->text('everything');
```

### Blade Templates

```blade
{{-- ✅ CORRECTO - Usar @extends y @section para layouts --}}
@extends('layouts.app')

@section('content')
    <div class="card">
        <h2>{{ $titulo }}</h2>
        @livewire('contratos-list')
    </div>
@endsection

{{-- ❌ INCORRECTO - Usar sintaxis de componentes para layouts --}}
<x-layouts.app>
    <div>Contenido</div>
</x-layouts.app>
```

### Livewire Components

```php
// ✅ CORRECTO - Sintaxis Laravel Livewire 3+
<?php

use Livewire\Component;

new class extends Component
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

# Servidor de desarrollo
php artisan serve                         # http://127.0.0.1:8000

# Listar rutas
php artisan route:list

# Ver comandos personalizados
php artisan list
```

### Composer

```bash
# Instalar dependencias
composer install

# Agregar paquetes
composer require nombre/paquete

# Actualizar autoload
composer dump-autoload
```

---

## 🔄 CÓMO USAR ESTE DOCUMENTO CON COPILOT

### Al Iniciar una Sesión de Desarrollo

```
@workspace Lee completamente el archivo COPILOT_CONTEXT.md 
antes de responder cualquier pregunta sobre este proyecto.
```

### Durante el Desarrollo

Cada vez que pidas código, recuérdale a Copilot:

```
Según COPILOT_CONTEXT.md, ¿cuál es la forma correcta de 
implementar [FUNCIONALIDAD]?
```

### Al Finalizar una Sesión

Actualiza este documento con lo completado:

```
Actualiza COPILOT_CONTEXT.md con lo siguiente:

COMPLETADO HOY:
- [Tarea 1]
- [Tarea 2]

ARCHIVOS CREADOS:
- app/Models/Contrato.php - [Descripción]
- app/Services/SeaceScraperService.php - [Descripción]

PRÓXIMOS PASOS:
- [ ] [Siguiente tarea]

Instrucciones:
1. Mueve las tareas de "Pendiente" a "Completado"
2. Agrega los archivos a "Estructura de Archivos"
3. Actualiza la versión del documento
4. Actualiza la fecha
```

---

## 📋 CHECKLIST DE SESIÓN

### Antes de Empezar
- [ ] Leí COPILOT_CONTEXT.md completo
- [ ] Cargué el contexto en Copilot Chat
- [ ] Revisé qué está completado y qué falta
- [ ] Sé en qué tarea voy a trabajar

### Durante Desarrollo
- [ ] Consulto COPILOT_CONTEXT.md antes de crear código
- [ ] Sigo las convenciones establecidas
- [ ] No duplico funcionalidades existentes
- [ ] Uso solo el stack aprobado

### Al Finalizar
- [ ] Actualicé COPILOT_CONTEXT.md
- [ ] Incrementé la versión del documento
- [ ] Documenté decisiones técnicas importantes

---

## 📝 HISTORIAL DE VERSIONES

### Versión 1.0 - 29 de enero de 2026
- ✅ Inicialización del proyecto
- ✅ Configuración de Laravel 12 + MySQL
- ✅ Instalación de Livewire + Alpine.js
- ✅ Creación de layout y vistas base
- ✅ Migración de tabla contratos

---

**Última actualización:** 29 de enero de 2026  
**Versión:** 1.0  
**Estado:** Inicialización completada ✅

## 📊 ARQUITECTURA DE DATOS

### Modelo Contrato (Propuesto)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contrato extends Model
{
    protected $fillable = [
        'numero_contrato',
        'entidad',
        'objeto',
        'monto',
        'fecha_publicacion',
        'estado',
        'datos_raw',
    ];

    protected $casts = [
        'fecha_publicacion' => 'date',
        'monto' => 'decimal:2',
        'datos_raw' => 'array',
    ];

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopeRecientes($query)
    {
        return $query->orderBy('fecha_publicacion', 'desc');
    }
}
```

---

## 🔄 FLUJO DE TRABAJO DE SCRAPING (Propuesto)

### 1. Comando Artisan
```
app/Console/Commands/ExtractSeaceContracts.php
```
- Se ejecuta vía Laravel Scheduler (cron)
- Maneja autenticación en SEACE
- Llama al servicio de scraping

### 2. Servicio de Scraping
```
app/Services/SeaceScraperService.php
```
- Usa Laravel HTTP Client
- Maneja cookies y sesiones
- Extrae JSON de microservicios SEACE
- Retorna datos estructurados

### 3. Parser de Datos
```
app/Services/ContratoParserService.php
```
- Transforma JSON a formato DB
- Valida datos
- Detecta duplicados

### 4. Almacenamiento
```
app/Models/Contrato.php
```
- Guarda en MySQL vía Eloquent
- Dispara evento `NuevoContratoDetectado`

### 5. Notificación
```
app/Events/NuevoContratoDetectado.php
app/Listeners/EnviarNotificacionTelegram.php
```
- Listener escucha el evento
- Envía mensaje a Telegram

---

## 🎯 MEJORES PRÁCTICAS A SEGUIR

### 1. Controllers Delgados
```php
// ✅ CORRECTO
public function index()
{
    $contratos = Contrato::activos()->recientes()->paginate(20);
    return view('contratos.index', compact('contratos'));
}

// ❌ INCORRECTO - Lógica compleja en controller
public function index()
{
    // 50 líneas de lógica de negocio aquí...
}
```

### 2. Usar Form Requests para Validación
```php
// ✅ CORRECTO
php artisan make:request StoreContratoRequest

// En el controller
public function store(StoreContratoRequest $request)
{
    Contrato::create($request->validated());
}

// ❌ INCORRECTO - Validar en controller
public function store(Request $request)
{
    $request->validate([...]);
}
```

### 3. Usar Services para Lógica Compleja
```php
// ✅ CORRECTO
app/Services/SeaceScraperService.php

// En el controller o command
$scraper = new SeaceScraperService();
$data = $scraper->extractContratos();

// ❌ INCORRECTO - Lógica en controller/command
public function handle()
{
    // 200 líneas de lógica de scraping...
}
```

### 4. Eloquent Query Scopes
```php
// ✅ CORRECTO
Contrato::activos()->recientes()->where('monto', '>', 10000)->get();

// ❌ INCORRECTO - Query builder crudo
DB::table('contratos')->where('estado', 'activo')->orderBy('fecha_publicacion', 'desc')->get();
```

---

## 🐛 DEBUGGING Y LOGS

### Logs de Laravel
```php
// Agregar logs en puntos críticos
use Illuminate\Support\Facades\Log;

Log::info('Iniciando extracción SEACE', ['fecha' => now()]);
Log::error('Error en scraping', ['exception' => $e->getMessage()]);
```

### Logs se guardan en:
```
storage/logs/laravel.log
```

### Ver logs en tiempo real (Linux):
```bash
tail -f storage/logs/laravel.log
```

---

## 📝 CHECKLIST ANTES DE SUGERIR CÓDIGO

Antes de generar código o sugerir una solución, verifica:

- [ ] ¿Ya existe esta funcionalidad en el proyecto?
- [ ] ¿Estoy usando el stack aprobado (Laravel/Blade/Livewire/Alpine.js)?
- [ ] ¿Estoy evitando React/Vue/APIs REST?
- [ ] ¿Estoy usando Laravel HTTP Client en lugar de herramientas externas?
- [ ] ¿Estoy siguiendo las convenciones de nombres?
- [ ] ¿Mi código sigue los principios de Laravel Way?
- [ ] ¿Estoy consultando COPILOT_CONTEXT.md actualizado?

---

## 🔄 INSTRUCCIONES DE ACTUALIZACIÓN

**IMPORTANTE:** Este archivo debe actualizarse después de cada sesión de desarrollo.

### Cuándo actualizar:
- ✅ Cuando se complete una tarea de la lista "Pendiente"
- ✅ Cuando se agreguen nuevos archivos o funcionalidades
- ✅ Cuando se modifique la arquitectura
- ✅ Cuando se instalen nuevas dependencias

### Quién actualiza:
- El desarrollador al finalizar cada sesión
- GitHub Copilot al completar tareas significativas

### Secciones a actualizar:
1. **Estado Actual del Proyecto** → Mover tareas de "Pendiente" a "Completado"
2. **Estructura de Archivos Actual** → Agregar nuevos archivos creados
3. **En Progreso** → Indicar tareas activas

---

## 📞 INFORMACIÓN DE CONTACTO DEL PROYECTO

- **Repositorio:** (pendiente)
- **Entorno Local:** `http://127.0.0.1:8000`
- **Base de Datos:** `vigilante_seace` en MySQL local
- **Stack:** LAMP (Apache + MySQL + PHP 8.2.12 + Laravel 12)

---

## 🎓 RECURSOS DE REFERENCIA

### Documentación Oficial
- [Laravel 11 Docs](https://laravel.com/docs/11.x) (aplicable a v12)
- [Livewire 3 Docs](https://livewire.laravel.com/docs)
- [Alpine.js Docs](https://alpinejs.dev/)
- [Blade Templates](https://laravel.com/docs/11.x/blade)

### SEACE (Portal Objetivo)
- URL: [https://prodapp2.seace.gob.pe/seacebus-uiwd-pub/buscadorPublico/buscadorPublico.xhtml](https://prodapp2.seace.gob.pe/seacebus-uiwd-pub/buscadorPublico/buscadorPublico.xhtml)
- Requiere login para acceso completo
- Usa microservicios JSON en backend

---

## ✅ VERSIÓN DEL DOCUMENTO

- **Versión:** 2.0
- **Última Actualización:** 30 de enero de 2026
- **Actualizado por:** Implementación Core de Scraping SEACE
- **Próxima Revisión:** Al completar Dashboard con Livewire

### 📝 Cambios en esta versión:
- ✅ Modelo Contrato implementado con scopes y accessors
- ✅ Migración de contratos actualizada con estructura optimizada
- ✅ SeaceScraperService con autenticación y refresh token
- ✅ TelegramNotificationService para notificaciones
- ✅ SeaceSyncCommand con estrategia "ninja" (delays aleatorios)
- ✅ SeaceTestCommand para diagnóstico del sistema
- ✅ Variables de entorno completas en .env
- ✅ Configuración en config/services.php
- ✅ Documentación completa en SETUP_GUIDE.md
- ✅ Archivo .env.example actualizado

---

> **Fin del Documento de Contexto**
> 
> 🤖 **Para Copilot:** Lee este documento completamente antes de cada respuesta.
> Consulta las secciones "Estado Actual" y "Pendiente" para evitar duplicar trabajo.
