# ⚡ QUICK START - Vigilante SEACE

## 🚀 Inicio Rápido en 5 Pasos

### 📋 Prerequisitos
- ✅ XAMPP instalado y corriendo (Apache + MySQL)
- ✅ Composer instalado
- ✅ Credenciales de SEACE (RUC + Password)

---

## Paso 1️⃣: Configurar Base de Datos

```bash
# Abrir MySQL (desde XAMPP Control Panel o terminal)
mysql -u root

# Crear la base de datos
CREATE DATABASE vigilante_seace CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

---

## Paso 2️⃣: Configurar Variables de Entorno

Edita el archivo `.env` en la raíz del proyecto:

```env
# === BASE DE DATOS ===
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vigilante_seace
DB_USERNAME=root
DB_PASSWORD=

# === SEACE (OBLIGATORIO) ===
SEACE_RUC_PROVEEDOR=10XXXXXXXX        # ⚠️ REEMPLAZAR con tu RUC
SEACE_PASSWORD=tu_password_aqui       # ⚠️ REEMPLAZAR con tu contraseña

# === TELEGRAM (Opcional - puedes dejarlo vacío por ahora) ===
TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=
```

💡 **Tip:** Las credenciales de Telegram son opcionales. El sistema funcionará sin notificaciones.

---

## Paso 3️⃣: Ejecutar Migraciones

```bash
cd d:\xampp\htdocs\vigilante-seace
php artisan migrate
```

**Salida esperada:**
```
✓ 0001_01_01_000000_create_users_table
✓ 0001_01_01_000001_create_cache_table
✓ 0001_01_01_000002_create_jobs_table
✓ 2026_01_29_210850_create_contratos_table
```

---

## Paso 4️⃣: Probar Conexión con SEACE

```bash
php artisan seace:test --auth
```

**Si todo está bien, verás:**
```
🔐 Probando autenticación con SEACE...
   📋 RUC: 10XXXXXXXX
   🌐 URL: https://prod6.seace.gob.pe/v1/s8uit-services
   🔑 Password: **********

   🔄 Intentando login...
   ✅ Login exitoso
   💡 El token se guardó en cache correctamente
```

**Si hay error:**
- ❌ Verifica que el RUC sea de 10 dígitos
- ❌ Verifica que la contraseña sea correcta
- ❌ Intenta hacer login manual en el portal SEACE para confirmar credenciales

---

## Paso 5️⃣: Primera Sincronización

```bash
# Sincronización rápida (sin delay)
php artisan seace:sync --force
```

**Salida esperada:**
```
🚀 Iniciando sincronización SEACE - 2026-01-30 10:30:00
📡 Consultando SEACE (año: 2026)...
✅ Se obtuvieron 100 contratos
💾 Procesando contratos...
 100/100 [████████████████████████████] 100%

📊 ESTADÍSTICAS DE SINCRONIZACIÓN
═══════════════════════════════════════
📋 Total procesados:    100
🆕 Nuevos contratos:    100
🔄 Actualizados:        0
❌ Errores:             0
📱 Notificados:         0 (sin Telegram configurado)
═══════════════════════════════════════
⏱️  Tiempo de ejecución: 15 segundos
✅ Sincronización completada
```

---

## 🎉 ¡Listo! Ahora Puedes...

### Ver los contratos en la base de datos:

```bash
mysql -u root vigilante_seace -e "SELECT codigo_proceso, entidad, estado, fecha_publicacion FROM contratos ORDER BY fecha_publicacion DESC LIMIT 5;"
```

### Ejecutar sincronizaciones periódicas:

```bash
# Con delay aleatorio (estrategia ninja - 42-50 minutos)
php artisan seace:sync

# Sin delay (para pruebas)
php artisan seace:sync --force

# Año específico
php artisan seace:sync --year=2024
```

### Ver estadísticas:

```sql
-- Abrir MySQL
mysql -u root vigilante_seace

-- Total de contratos
SELECT COUNT(*) as total FROM contratos;

-- Contratos por estado
SELECT estado, COUNT(*) as cantidad 
FROM contratos 
GROUP BY estado;

-- Contratos vigentes de hoy
SELECT codigo_proceso, entidad, fin_cotizacion 
FROM contratos 
WHERE estado = 'Vigente' 
  AND DATE(created_at) = CURDATE();

-- Contratos próximos a vencer (3 días)
SELECT codigo_proceso, entidad, 
       DATEDIFF(fin_cotizacion, NOW()) as dias_restantes
FROM contratos 
WHERE fin_cotizacion BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY)
  AND estado = 'Vigente'
ORDER BY fin_cotizacion ASC;
```

---

## 📱 (Opcional) Configurar Notificaciones de Telegram

### 1. Crear el Bot

1. Abre Telegram y busca **@BotFather**
2. Envía: `/newbot`
3. Nombre: `Vigilante SEACE Bot`
4. Username: `vigilante_seace_bot` (o el que prefieras)
5. **Copia el Token** que te da (ej: `123456789:ABCdefGHIjklMNOpqrs`)

### 2. Obtener el Chat ID

1. Envía un mensaje a tu bot recién creado (cualquier texto)
2. Abre en el navegador:
   ```
   https://api.telegram.org/bot<TU_TOKEN>/getUpdates
   ```
   (Reemplaza `<TU_TOKEN>` con el token que copiaste)
   
3. Busca el campo `"chat":{"id":123456789}`
4. **Copia ese número** (tu Chat ID)

### 3. Agregar a .env

```env
TELEGRAM_BOT_TOKEN=123456789:ABCdefGHIjklMNOpqrs
TELEGRAM_CHAT_ID=123456789
```

### 4. Limpiar cache y probar

```bash
php artisan config:clear
php artisan seace:test --telegram
```

**Deberías recibir un mensaje en Telegram:**
```
🧪 Mensaje de Prueba

✅ El sistema de notificaciones está funcionando correctamente.

📅 Fecha: 30/01/2026 10:45:00
🤖 Vigilante SEACE - Test
```

### 5. Sincronizar de nuevo

```bash
php artisan seace:sync --force
```

Ahora sí recibirás notificaciones de los contratos nuevos! 🎉

---

## 🐛 Solución de Problemas Comunes

### Error: "SQLSTATE[HY000] [1049] Unknown database"
```bash
# La base de datos no existe, créala:
mysql -u root -e "CREATE DATABASE vigilante_seace;"
php artisan migrate
```

### Error: "Class 'SeaceScraperService' not found"
```bash
# Regenerar autoload de Composer:
composer dump-autoload
php artisan config:clear
```

### Error: "No se pudo obtener un token válido"
```bash
# Verifica tus credenciales SEACE:
php artisan seace:test --auth

# Si el login manual en SEACE funciona pero el comando falla:
# - Verifica que no haya espacios en RUC o password
# - Verifica que el RUC sea de 10 dígitos
# - Intenta cambiar la contraseña en SEACE y actualizarla en .env
```

### No se reciben notificaciones de Telegram
```bash
# 1. Verifica la configuración:
php artisan seace:test --telegram

# 2. Envía un mensaje manual al bot antes de ejecutar el comando

# 3. Verifica los logs:
type storage\logs\laravel.log | findstr "Telegram"
```

### La tabla está vacía después de ejecutar seace:sync
```bash
# Verifica los logs:
type storage\logs\laravel.log | findstr "SEACE"

# Posibles causas:
# 1. Error de autenticación (verifica con seace:test --auth)
# 2. No hay contratos para el año actual (prueba con --year=2024)
# 3. Error en el parsing (revisa los logs)
```

---

## 📚 Próximos Pasos

1. ✅ **Configurar ejecución automática**
   - Ver: [SETUP_GUIDE.md - Sección "Programar Ejecución Automática"](./SETUP_GUIDE.md)

2. ✅ **Crear el Dashboard Web**
   - Pendiente de implementar (próxima versión)

3. ✅ **Configurar filtros personalizados**
   - Por entidad, monto, tipo de objeto, etc.

---

## 🆘 ¿Necesitas Ayuda?

1. 📖 **Documentación completa:** [SETUP_GUIDE.md](./SETUP_GUIDE.md)
2. 📋 **Resumen técnico:** [IMPLEMENTATION_SUMMARY.md](./IMPLEMENTATION_SUMMARY.md)
3. 🤖 **Contexto del proyecto:** [COPILOT_CONTEXT.md](./COPILOT_CONTEXT.md)
4. 📝 **Logs del sistema:** `storage/logs/laravel.log`

---

**¡Feliz Monitoreo! 🚀**

---

*Generado: 30 de enero de 2026*  
*Vigilante SEACE v2.0*
