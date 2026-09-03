# 🔧 CONFIGURACIÓN DEL SISTEMA VIGILANTE SEACE

## 📋 Variables de Entorno Requeridas

### 1. Configuración SEACE (OBLIGATORIO)

```env
# URL Base de la API del SEACE
SEACE_BASE_URL=https://prod6.seace.gob.pe/v1/s8uit-services

# RUC del proveedor (10 dígitos - RUC personal)
SEACE_RUC_PROVEEDOR=10XXXXXXXX

# Contraseña de acceso al portal SEACE
SEACE_PASSWORD=tu_password_aqui

# Duración del token en cache (segundos - default: 3600 = 1 hora)
SEACE_TOKEN_CACHE_DURATION=3600
```

### 2. Configuración Telegram (OBLIGATORIO para notificaciones)

```env
# Token del Bot de Telegram
# Obtenerlo desde @BotFather en Telegram
TELEGRAM_BOT_TOKEN=123456789:ABCdefGHIjklMNOpqrsTUVwxyz

# ID del chat donde se enviarán las notificaciones
# Obtenerlo enviando un mensaje al bot y consultando:
# https://api.telegram.org/bot<TOKEN>/getUpdates
TELEGRAM_CHAT_ID=123456789
```

### 3. Configuración de Scraping (OPCIONAL)

```env
# Cantidad de registros por página (máximo: 100)
SEACE_PAGE_SIZE=100

# Delay aleatorio entre ejecuciones (en minutos)
SEACE_MIN_DELAY_MINUTES=42
SEACE_MAX_DELAY_MINUTES=50
```

## 🚀 Comandos Disponibles

### Sincronización Manual

```bash
# Sincronizar contratos del año actual
php artisan seace:sync

# Sincronizar contratos de un año específico
php artisan seace:sync --year=2024

# Sincronizar sin delay (útil para pruebas)
php artisan seace:sync --force
```

### Verificar Estado del Sistema

```bash
# Limpiar cachés
php artisan cache:clear
php artisan config:clear

# Ver rutas disponibles
php artisan route:list

# Ver comandos disponibles
php artisan list
```

## 📊 Estructura de la Base de Datos

### Tabla: `contratos`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_contrato_seace` | BIGINT (PK) | ID único del contrato en SEACE |
| `nro_contratacion` | INTEGER | Número de contratación |
| `codigo_proceso` | VARCHAR | Código del proceso (ej: CM-19-2026-MDH/CM) |
| `entidad` | VARCHAR | Nombre de la entidad |
| `objeto` | VARCHAR | Tipo: Bien, Servicio, Obra, etc. |
| `descripcion` | TEXT | Descripción completa del contrato |
| `estado` | VARCHAR | Estado: Vigente, En Evaluación, etc. |
| `fecha_publicacion` | DATETIME | Fecha de publicación |
| `inicio_cotizacion` | DATETIME | Fecha de inicio de cotización |
| `fin_cotizacion` | DATETIME | Fecha límite para cotizar |
| `datos_raw` | JSON | Datos completos del JSON original |
| `created_at` | TIMESTAMP | Fecha de creación en BD local |
| `updated_at` | TIMESTAMP | Fecha de última actualización |

## 🔐 Obtener Credenciales de Telegram

### Paso 1: Crear el Bot

1. Abre Telegram y busca a **@BotFather**
2. Envía el comando: `/newbot`
3. Sigue las instrucciones (nombre y username del bot)
4. Copia el **Token** que te proporciona

### Paso 2: Obtener el Chat ID

1. Envía un mensaje a tu bot recién creado
2. Abre en el navegador:
   ```
   https://api.telegram.org/bot<TU_TOKEN>/getUpdates
   ```
3. Busca el campo `"chat":{"id":123456789}` en el JSON
4. Ese número es tu **Chat ID**

## 📝 Ejemplo de Notificación

Cuando se detecta un nuevo contrato, recibirás un mensaje como este:

```
📦 NUEVO CONTRATO DETECTADO

📋 Código: CM-19-2026-MDH/CM
🏢 Entidad: MUNICIPALIDAD DISTRITAL DE HUANCAVELICA
📦 Objeto: Bien
📝 Descripción:
Adquisición de útiles de escritorio para las diferentes áreas...

📅 Publicación: 30/01/2026 08:30
🟢 Inicio Cotización: 30/01/2026 09:00
🟡 Fin Cotización: 05/02/2026 18:00 (5 días restantes)

🔗 Estado: Vigente

🤖 Vigilante SEACE
```

## ⚙️ Programar Ejecución Automática

### Windows (Task Scheduler)

1. Abre el **Programador de Tareas**
2. Crear tarea básica
3. Configurar trigger: Diariamente cada hora
4. Acción: Iniciar programa
   - Programa: `C:\xampp\php\php.exe`
   - Argumentos: `artisan seace:sync`
   - Iniciar en: `d:\xampp\htdocs\vigilante-seace`

### Linux (Cron)

```bash
# Editar crontab
crontab -e

# Agregar línea (ejecutar cada hora)
0 * * * * cd /path/to/vigilante-seace && php artisan seace:sync >> /dev/null 2>&1
```

### Laravel Scheduler (Recomendado)

Edita `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('seace:sync')
             ->everyMinute()
             ->between('08:00', '20:00') // Solo en horario laboral
             ->withoutOverlapping() // Evitar ejecuciones simultáneas
             ->runInBackground();
}
```

Luego configura el cron:
```bash
* * * * * cd /path/to/vigilante-seace && php artisan schedule:run >> /dev/null 2>&1
```

## 🔍 Consultas Útiles en la Base de Datos

```sql
-- Ver contratos nuevos del día
SELECT * FROM contratos 
WHERE DATE(created_at) = CURDATE() 
ORDER BY fecha_publicacion DESC;

-- Ver contratos próximos a vencer (3 días)
SELECT codigo_proceso, entidad, fin_cotizacion 
FROM contratos 
WHERE fin_cotizacion BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY)
AND estado = 'Vigente'
ORDER BY fin_cotizacion ASC;

-- Estadísticas por entidad
SELECT entidad, COUNT(*) as total 
FROM contratos 
GROUP BY entidad 
ORDER BY total DESC 
LIMIT 10;
```

## 🐛 Solución de Problemas

### Error: "No se pudo obtener un token válido"

- Verifica que `SEACE_RUC_PROVEEDOR` y `SEACE_PASSWORD` sean correctos
- Intenta hacer login manual en el portal SEACE para verificar credenciales
- Revisa los logs: `storage/logs/laravel.log`

### Error: "Telegram credentials not configured"

- Verifica que `TELEGRAM_BOT_TOKEN` y `TELEGRAM_CHAT_ID` estén en `.env`
- Ejecuta: `php artisan config:clear`

### No se reciben notificaciones

- Verifica que el bot tenga permisos para enviar mensajes
- Envía un mensaje manualmente al bot antes de ejecutar el comando
- Revisa los logs para ver si hay errores de Telegram

### Base de datos vacía después de ejecutar

- Verifica la conexión a MySQL: `php artisan migrate:status`
- Revisa los logs para ver errores de autenticación con SEACE
- Ejecuta con `--force` para evitar el delay y ver errores inmediatos

## 📚 Logs y Auditoría

Todos los eventos importantes se registran en:

```
storage/logs/laravel.log
```

Eventos registrados:
- ✅ Login exitoso/fallido en SEACE
- 🔄 Refresh de tokens
- 📡 Consultas al buscador
- 💾 Contratos nuevos/actualizados
- 📱 Notificaciones enviadas
- ❌ Errores y excepciones

## 🔒 Seguridad

**IMPORTANTE:** Nunca subas el archivo `.env` a repositorios públicos.

Crea un `.env.example` con valores de ejemplo:

```env
SEACE_RUC_PROVEEDOR=10XXXXXXXX
SEACE_PASSWORD=tu_password
TELEGRAM_BOT_TOKEN=123456789:ABC...
TELEGRAM_CHAT_ID=123456789
```

## 📈 Próximas Mejoras

- [ ] Dashboard web con gráficos
- [ ] Filtros personalizados por entidad/monto
- [ ] Notificaciones por correo electrónico
- [ ] Export a Excel/PDF
- [ ] API REST para consultas externas
- [ ] Sistema de alertas por palabras clave

---

**Versión:** 1.0  
**Última actualización:** 30 de enero de 2026  
**Desarrollado con:** Laravel 12 + Livewire + Alpine.js
