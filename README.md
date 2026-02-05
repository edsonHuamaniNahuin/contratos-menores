# 🔍 Vigilante SEACE

Sistema automatizado de monitoreo y extracción de contratos menores del portal SEACE (Perú) con notificaciones en tiempo real vía Telegram.

---

## 🎯 Descripción

**Vigilante SEACE** es un MVP que automatiza la extracción de datos de "contratos menores" del portal gubernamental SEACE (Sistema Electrónico de Contrataciones del Estado - Perú), los almacena en una base de datos propia y envía notificaciones vía Telegram cuando aparecen nuevas oportunidades relevantes.

### ✨ Características Principales

- 🤖 **Scraping Inteligente:** Extracción automatizada con manejo de tokens y refresh automático
- 📱 **Notificaciones Telegram:** Alertas instantáneas de nuevos contratos
- 💾 **Base de Datos MySQL:** Almacenamiento estructurado con prevención de duplicados
- ⏱️ **Estrategia Ninja:** Delays aleatorios para evitar detección como bot
- 📊 **Modelo Eloquent:** Queries optimizados con scopes personalizados
- 🔐 **Autenticación Segura:** Gestión de tokens JWT con refresh automático
- 📈 **Logging Completo:** Auditoría de todas las operaciones

---

## 🛠️ Stack Tecnológico

- **Backend:** Laravel 12.49.0 (PHP 8.2.12)
- **Base de Datos:** MySQL
- **Frontend:** Laravel Blade + Livewire 4.1.0 + Alpine.js 3.x
- **Servidor:** Apache (XAMPP)
- **Scraping:** Laravel HTTP Client (Guzzle 7.10.0)
- **Notificaciones:** Telegram Bot API
- **Arquitectura:** Monolito Majestuoso (Laravel Monolith)

---

## 📋 Requisitos Previos

- PHP >= 8.2.12
- Composer >= 2.0
- MySQL >= 5.7 / MariaDB >= 10.3
- Apache (vía XAMPP o similar)
- Cuenta en portal SEACE (RUC + contraseña)
- Bot de Telegram (opcional para notificaciones)

---

## 🚀 Instalación Rápida

### 1. Clonar el repositorio
```bash
cd d:\xampp\htdocs
git clone [URL_DEL_REPO] vigilante-seace
cd vigilante-seace
```

### 2. Instalar dependencias
```bash
composer install
```

### 3. Configurar variables de entorno
```bash
cp .env.example .env
```

Edita el archivo `.env` y configura:

```env
# Base de datos
DB_DATABASE=vigilante_seace
DB_USERNAME=root
DB_PASSWORD=

# Credenciales SEACE (OBLIGATORIO)
SEACE_RUC_PROVEEDOR=10XXXXXXXX
SEACE_PASSWORD=tu_password

# Telegram (opcional)
TELEGRAM_BOT_TOKEN=123456789:ABC...
TELEGRAM_CHAT_ID=123456789
```

### 4. Crear la base de datos
```bash
mysql -u root -e "CREATE DATABASE vigilante_seace CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 5. Generar application key
```bash
php artisan key:generate
```

### 6. Ejecutar migraciones
```bash
php artisan migrate
```

### 7. Probar la configuración
```bash
php artisan seace:test
```

Este comando verificará:
- ✅ Conexión a MySQL
- ✅ Autenticación con SEACE
- ✅ Notificaciones de Telegram

### 8. Iniciar servidor de desarrollo
```bash
php artisan serve
```

Visita: **http://127.0.0.1:8000**

---

## 🎮 Uso del Sistema

### Comandos Disponibles

#### 1. Sincronizar contratos (Manual)
```bash
# Sincronizar contratos del año actual
php artisan seace:sync

# Sincronizar un año específico
php artisan seace:sync --year=2024

# Sincronizar sin delay (para pruebas)
php artisan seace:sync --force
```

#### 2. Diagnóstico del sistema
```bash
# Verificar todas las configuraciones
php artisan seace:test

# Probar solo autenticación SEACE
php artisan seace:test --auth

# Probar solo Telegram
php artisan seace:test --telegram

# Probar solo base de datos
php artisan seace:test --db
```

#### 3. Ver contratos en la base de datos
```sql
-- Contratos vigentes
SELECT * FROM contratos WHERE estado = 'Vigente' ORDER BY fecha_publicacion DESC;

-- Contratos próximos a vencer (3 días)
SELECT codigo_proceso, entidad, fin_cotizacion 
FROM contratos 
WHERE fin_cotizacion BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY)
ORDER BY fin_cotizacion ASC;
```

---

## 📁 Estructura del Proyecto

```
vigilante-seace/
├── app/
│   ├── Console/Commands/
│   │   ├── SeaceSyncCommand.php      # Comando de sincronización
│   │   └── SeaceTestCommand.php      # Comando de diagnóstico
│   ├── Models/
│   │   └── Contrato.php              # Modelo de contratos
│   └── Services/
│       ├── SeaceScraperService.php   # Servicio de scraping
│       └── TelegramNotificationService.php  # Notificaciones
├── config/
│   └── services.php                   # Configuración SEACE/Telegram
├── database/
│   └── migrations/
│       └── 2026_01_29_210850_create_contratos_table.php
├── .env.example                       # Plantilla de configuración
├── SETUP_GUIDE.md                     # Guía completa de configuración
└── COPILOT_CONTEXT.md                 # Contexto del proyecto
```

---

## 📚 Documentación Adicional

Para información detallada sobre:
- 🔐 Configuración de credenciales
- 📱 Cómo obtener tokens de Telegram
- ⚙️ Programación automática (cron/scheduler)
- 🐛 Solución de problemas
- 📊 Consultas SQL útiles

👉 **Consulta:** [SETUP_GUIDE.md](./SETUP_GUIDE.md)

---

## 📁 Estructura del Proyecto---

## 🤖 Desarrollo con GitHub Copilot

Este proyecto incluye un archivo de contexto para maximizar la efectividad de GitHub Copilot:

📄 **[COPILOT_CONTEXT.md](COPILOT_CONTEXT.md)** - Contexto maestro del proyecto

### Antes de Cada Sesión:
1. Abre `COPILOT_CONTEXT.md` y revisa el estado actual
2. Carga el contexto en Copilot Chat:
   ```
   @workspace Lee COPILOT_CONTEXT.md
   ```

### Al Finalizar:
Actualiza `COPILOT_CONTEXT.md` con los cambios realizados para mantener el contexto sincronizado.

---

## 📝 Comandos Útiles de Laravel

### Desarrollo
```bash
php artisan serve                 # Iniciar servidor
php artisan route:list           # Listar rutas
php artisan list                 # Ver todos los comandos
```

### Base de Datos
```bash
php artisan migrate              # Ejecutar migraciones
php artisan migrate:fresh        # Resetear y re-migrar
php artisan db:seed              # Ejecutar seeders
```

### Caché
```bash
php artisan cache:clear          # Limpiar caché
php artisan config:clear         # Limpiar config
php artisan view:clear           # Limpiar vistas compiladas
```

### Comandos Personalizados (Cuando se implementen)
```bash
php artisan seace:extract        # Extraer contratos de SEACE
```

---

## 🏗️ Estado del Proyecto

### ✅ Completado
- [x] Inicialización del proyecto Laravel 12
- [x] Configuración de base de datos MySQL
- [x] Instalación de Livewire 4.1.0
- [x] Configuración de Alpine.js vía CDN
- [x] Layout base y vistas de ejemplo
- [x] Migración de tabla `contratos`
- [x] Sistema de documentación para Copilot

### 🔄 En Desarrollo
- [ ] Comando de extracción SEACE
- [ ] Servicio de scraping con manejo de sesiones
- [ ] Dashboard de contratos
- [ ] Sistema de notificaciones Telegram

### 📅 Próximos Pasos
1. Crear modelo `Contrato` con relaciones
2. Implementar `ExtractSeaceContracts` command
3. Desarrollar `SeaceScraperService`
4. Crear dashboard con Livewire
5. Integrar notificaciones Telegram

---

## 🎨 Principios de Arquitectura

### Monolito Majestuoso
- ✅ Todo en una sola aplicación Laravel
- ✅ Blade para renderizado server-side
- ✅ Livewire para interactividad dinámica
- ❌ No APIs REST separadas
- ❌ No frameworks JavaScript externos (React/Vue)

### Laravel Way
- Controladores delgados
- Modelos ricos con Eloquent
- Services para lógica compleja
- Form Requests para validación
- Eventos y Listeners para notificaciones

---

## 📊 Modelo de Datos

### Tabla: `contratos`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID autoincrementable |
| `numero_contrato` | string | Número único del contrato (unique) |
| `entidad` | string | Nombre de la entidad contratante |
| `objeto` | text | Descripción del objeto del contrato |
| `monto` | decimal(15,2) | Monto del contrato |
| `fecha_publicacion` | date | Fecha de publicación |
| `estado` | string | Estado del contrato (default: 'activo') |
| `datos_raw` | json | Datos completos del JSON |
| `created_at` | timestamp | Fecha de creación |
| `updated_at` | timestamp | Fecha de actualización |

---

## 🔐 Seguridad

- Validación de datos con Form Requests
- Sanitización de inputs
- Protección CSRF en formularios Blade
- Manejo seguro de credenciales en `.env`

---

## 🐛 Debugging

### Ver Logs
Los logs se encuentran en:
```
storage/logs/laravel.log
```

Ver logs en tiempo real (Linux/Git Bash):
```bash
tail -f storage/logs/laravel.log
```

### Logs en Código
```php
use Illuminate\Support\Facades\Log;

Log::info('Mensaje informativo', ['contexto' => $data]);
Log::error('Error detectado', ['exception' => $e->getMessage()]);
```

---

## 📖 Documentación

### Laravel
- [Documentación Oficial](https://laravel.com/docs)
- [Blade Templates](https://laravel.com/docs/11.x/blade)
- [Eloquent ORM](https://laravel.com/docs/11.x/eloquent)

### Livewire
- [Documentación Oficial](https://livewire.laravel.com)

### Alpine.js
- [Documentación Oficial](https://alpinejs.dev)

### Portal SEACE
- [SEACE Perú](https://prodapp2.seace.gob.pe/seacebus-uiwd-pub/buscadorPublico/buscadorPublico.xhtml)

---

## 🎓 Onboarding

### Para Nuevos Desarrolladores
1. Lee este **README.md** (5 min) - Resumen del proyecto
2. Lee **COPILOT_CONTEXT.md** (10 min) - Estado actual y convenciones
3. ¡Empieza a codear! 🚀

**Tiempo de onboarding:** ~15 minutos

---

## 📄 Licencia

Este proyecto es de código cerrado para uso interno.

---

## 👥 Equipo

Desarrollado para el monitoreo automatizado de contratos SEACE (Perú).

---

**Última actualización:** 29 de enero de 2026  
**Versión:** 1.0  
**Estado:** Inicialización completada ✅
