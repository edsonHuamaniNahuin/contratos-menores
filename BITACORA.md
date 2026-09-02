# 📓 Bitácora — Errores y Soluciones

> Historial de incidencias, bugs y su resolución en Vigilante SEACE.
> Formato: fecha · categoría · commit · síntoma → causa → solución.

---

## 2026-09-01 · Contratos Mayores · `8bd6cd77`

### Error 500 al abrir el detalle de un contrato — `implode(): Argument #2 ($array) must be of type ?array, string given` (userId 149)

**Síntoma:** 3 errores seguidos (11:58-12:00) al abrir el detalle de un contrato en `/buscador-contratos-mayores`: `implode(): Argument #2 ($array) must be of type ?array, string given (View: buscador-mayores.blade.php)` línea del bloque "Proveedores" (`implode(', ', $detalleContrato['proveedores'])`).

**Causa raíz (doble codificación JSON):**
1. El scraper de procedimientos insertaba `'proveedores' => '[]'` (**string**).
2. El modelo `ContratoMayor` tiene cast `'proveedores' => 'array'` → al insertar un string con cast array, Eloquent lo serializa a `'"[]"'` (JSON de un string).
3. Al leer, el cast devuelve el **string** `'[]'` (no array) → `!empty('[]')` es true (string no vacía) → `implode` explota.

**Alcance:** **501 registros corruptos** en producción (JSON_TYPE(proveedores) = 'STRING') — no solo los 42 del scraper; el bug de doble codificación afectaba cualquier insert/update que pasara proveedores como string.

**Solución:**
1. **Reparación de datos**: `UPDATE contratos_mayores SET proveedores = CAST('[]' AS JSON) WHERE JSON_TYPE(proveedores)='STRING'` → 501 reparados, 0 restantes.
2. **Scraper**: inserta `'proveedores' => []` (array real) — comentado el porqué en el código.
3. **Defensa en la vista**: `@if(!empty(...) && is_array(...))`.

**Lección:** Con una columna JSON + cast `array` en Eloquent, SIEMPRE pasar arrays PHP (nunca strings JSON pre-codificados) — el cast re-serializa y produce doble codificación que solo explota al leer (tipo string inesperado).

**Archivos:** `app/Services/SeaceProcedimientosScraperService.php`, `resources/views/livewire/buscador-mayores.blade.php`; datos reparados vía SQL.

---

## 2026-08-31 · Scraper SEACE · `244894be` + `84a1c5c1` + `03211557`

### Gap de latencia del OECE: procedimientos con días/semanas de retraso en el buscador de mayores

**Síntoma (reporte de cliente):** El cliente reportó el 28/08 que el proceso `LP-ABR-1-2026-MDB-1` (MUNICIPALIDAD DISTRITAL DE BAMBAS, publicado en SEACE el 19/08 20:03) no aparecía en el buscador. Se confirmó que se importó recién el 31/08 12:09: **el ETL del OECE publicó el release OCDS con ~12 días de retraso**. Contratos del mismo rango de OCID entraron el 18/08 y otros (incluido Bambas) recién el 31/08 (lote de 192). No era bug del import: el dato NO existía en la API OCDS.

**Investigación de fuentes alternativas (concluida):**
- El buscador de Procedimientos de Selección del SEACE (prod2) es JSF + **reCAPTCHA v3** (invisible). No hay API REST limpia (el SPA nuevo de prod6 solo cubre contrataciones menores).
- **El botón "Exportar a Excel" del buscador funciona**: POST JSF + token reCAPTCHA v3. El token v3 se genera automáticamente en el navegador (headless) sin interacción.
- **Pruebas de viabilidad EXITOSAS** desde IP residencial y desde el servidor (datacenter): descarga `Lista-Procesos.xls` (Excel real, 10 columnas: entidad, fecha/hora, nomenclatura, objeto, descripción, VR, moneda, versión) y contiene el proceso de Bambas el mismo día de publicación.

**Implementación:**
1. `scripts/scrape-procesos-seace.cjs` — Puppeteer-core (chrome-headless-shell en `/opt/scraper-seace`) + SheetJS (`xlsx`): descarga el Excel del rango y escribe JSON.
   - ⚠️ `.cjs` obligatorio: el `package.json` del repo tiene `"type": "module"` → un `.js` se trataría como ESM y `require()` no existe.
   - `SCRAPE_CHROME_BIN` / `SCRAPE_NODE_MODULES` configurables (defaults para el servidor).
2. `SeaceProcedimientosScraperService` — ejecuta node, parsea JSON, **dedupe por nomenclatura**: existente → actualiza campos (sin tocar OCID); nuevo → inserta con OCID sintético `ocds-scraped-{md5}` y estado `CONVOCADO`.
3. `ScrapearProcedimientosSeaceJob` + schedule **12:00 y 21:00** (hora Lima).
4. Resuelve el binario de node por ruta común (`/usr/local/bin/node`) — el PATH de www-data no lo incluye.

**Verificación end-to-end en producción:** 51 procedimientos del día → **42 nuevos + 9 actualizados**. El pipeline OCDS sigue en paralelo (completa OCID real, estado, proveedores, URL, geo).

**Beneficio colateral:** los procedimientos entran a `contratos_mayores` el mismo día → el job de vigilancia ≥S/1M (corre cada 5h sobre esa tabla) los detecta antes.

**Riesgo asumido:** el reCAPTCHA v3 puede endurecerse; con 2 requests/día el riesgo es bajo. El job falla limpio y el pipeline OCDS es el respaldo.

**Archivos:** `scripts/scrape-procesos-seace.cjs`, `app/Services/SeaceProcedimientosScraperService.php`, `app/Jobs/ScrapearProcedimientosSeaceJob.php`, `routes/console.php`; entorno: `/opt/scraper-seace` (node_modules + chrome-headless-shell).

---

## 2026-08-30 · WhatsApp · `0184a433`

### Error #131056 "pair rate limit hit" en ráfaga (13+ fallos al mismo par)

**Síntoma:** El 28/08 12:05-12:06, 25+ errores `(#131056) (Business Account, Consumer Account) pair rate limit hit` TODOS al mismo destinatario (51944427266): primero un template fallido (12:05:01) y luego decenas de interactive del job `ImportarTdrNotificarJob` (12:05:14+). El usuario del número desactivó sus canales (toggles en 12:05:36-39) mientras el job seguía martillando.

**Causa (3 capas):**
1. **El throttle era por proceso** (`static array` en memoria): el worker de cola, el bot listener y el webhook tienen procesos PHP distintos → cada uno con su propio contador → podían superar la tasa del par entre todos.
2. **Intervalo insuficiente**: 1.5s por par — Meta, en ráfagas, bloquea el par temporalmente aunque los envíos estén espaciados (el 131056 dispara un enfriamiento del par).
3. **Doble golpe por contrato**: `enviarProcesoASuscriptor` con ventana cerrada intenta template + luego interactive para CADA coincidencia → duplica las llamadas al par por contrato.

**Solución (escalable):**
1. **Throttle compartido entre procesos** vía Cache (file store, `Cache::lock` atómico con flock en el mismo servidor): clave por destinatario + timestamp del último envío en cache.
2. **Intervalo 1.5s → 3s** por par.
3. **Backoff por par al detectar 131056**: `whatsapp:par_backoff:{número}` por 90s — durante el backoff NO se llama a la API (retorna fallo limpio, el dedup/cola lo reenvía después).
4. **Sin doble golpe**: si el template falla con 131056, `enviarProcesoASuscriptor` retorna sin intentar el interactive en ese ciclo.
5. Backoff aplicado a los 3 métodos (`enviarMensaje`, `enviarMensajeConBotones`, `enviarTemplate`).

**Lecciones:** (1) Los rate limits de Meta por par son por cuenta completa, no por proceso — cualquier throttle debe ser compartido (cache/redis), no estático en memoria. (2) Cuando un límite se dispara, Meta enfría el par: hay que esperar (backoff), no reintentar espaciado. (3) Evitar llamadas dobles al mismo par por unidad de trabajo.

**Archivos:** `app/Services/WhatsAppNotificationService.php`

---

## 2026-08-30 · Ops/Infra (transitorio, sin código)

### Telegram Bot Listener — `cURL error 28: Operation timed out after 10001ms` (30/08 14:31)

**Síntoma:** Un solo error de timeout en getUpdates con 0 bytes recibidos a los ~10s (el long-poll de Telegram).

**Análisis:** El listener ya usa `Http::timeout(30)` (fix `ee84ed2a`) — el corte a 10s proviene de la infraestructura de red (el long-poll mantiene la conexión 10s y el servidor/red no devolvió respuesta a tiempo). Evento transitorio: el listener se recupera solo en el siguiente ciclo. Sin pérdida de mensajes (getUpdates reprocesa).

**Acción:** Ninguna (monitorear; si se vuelve frecuente >3/día, revisar DNS/resolvers del servidor).

---

## 2026-08-28 · Externo SEACE (sin código)

### SEACE Público 500 (12:31 y 19:36)

**Análisis:** Errores 500 transitorios de la API pública del SEACE (externa). El retry 5xx con backoff 1.5s ya está activo (`e0cbb8ea`); el log aparece cuando el retry también falla. Sin acción.

---

## 2026-08-20 · RBAC · `e615794f`

### Permisos directos por usuario (grants individuales) — caso cliente facturado

**Contexto:** Un cliente facturado (bgmaxperu@gmail.com, plan S/68) necesitaba SOLO el permiso `alerta-adjudicaciones`, sin darlo a todo el rol premium+mayores. El RBAC solo permitía asignar permisos vía roles.

**Implementación (modelo Spatie, sin librería):**
1. Tabla `permission_user` (user_id, permission_id, unique) — migración `2026_08_20_000001`.
2. `User::directPermissions()` + `hasPermission()` ahora evalúa **roles ∪ directos** (aditivo: los permisos directos NUNCA quitan permisos del rol).
3. **Regla anti-deuda:** `SubscriptionService::revokePremiumRole()` limpia los permisos directos al expirar la suscripción (un moroso no conserva grants sin pagar).
4. **Gestor en `/roles-permisos`**: botón "⚙ Permisos" por usuario → panel con select-search de permisos (+Agregar), chips de permisos actuales diferenciando **del rol** (gris) vs **directo** (verde con X para quitar).
5. Auditoría: `Log::info` en cada grant/revoke (user, permission, admin_id).

**Semántica verificada localmente:** usuario sin permiso → `false`; grant directo → `true`; revoke → `false`. El resto de la lógica RBAC (roles, matriz, Gate::define) intacta.

**Archivos:** migración, `User.php`, `SubscriptionService.php`, `RolesPermisos.php` + blade

---

## 2026-08-20 · Analytics GA4 · `e891ac40`

### Error 500 en `/admin/analytics` — `max(): Argument #1 ($value) must contain at least one element`

**Síntoma:** Al entrar a `/admin/analytics` → Error 500. En laravel.log: `max(): Argument #1 ($value) must contain at least one element (View: resources/views/admin/analytics.blade.php)`. El script GA4 respondía bien (rowCount: 59).

**Causa:** `AdminAnalyticsController::queryGa4()` tenía `return $data['rows'][0] ?? ($data['rows'] ?? [])` — devolvía SOLO la **primera fila** como array asociativo en vez de todas las filas. Las secciones "Páginas más visitadas"/"Fuentes de tráfico" esperan listas; al recibir un array de escalares, `array_column($validPages, 'screenPageViews')` devolvía `[]` → `max([])` → ValueError en PHP 8. El error solo se veía al regenerarse la vista compilada (por eso "funcionaba antes").

**Solución:**
1. `queryGa4()` tool-aware: `ga4_totals` → fila única (`$rows[0]`); `ga4_top_pages`/`ga4_traffic_sources`/`ga4_all_events` → TODAS las filas.
2. Defensa en la vista: `array_values(array_filter($topPages))`.

**Verificación en producción (reflection):** top_pages → 3 filas; totals → keys correctas; traffic_sources → 10 filas.

**Lección:** Al parsear una API, distinguir "una fila" vs "todas las filas" por endpoint, no con un `??` genérico. Y proteger los `max()/min()` sobre arrays derivados (PHP 8 lanza ValueError, no devuelve false).

**Archivos:** `app/Http/Controllers/AdminAnalyticsController.php`, `resources/views/admin/analytics.blade.php`

---

## 2026-08-20 · Ops/Infra (sin código)

### Telegram Bot Listener — `cURL error 28: Resolving timed out` (transitorio, 3 apariciones)

**Síntoma:** `Telegram Bot Listener Error {"exception":"cURL error 28: Resolving timed out after 10000 milliseconds ... api.telegram.org/getUpdates"}` el 19/08 (21:37, 21:53) y 20/08 (07:20).

**Causa:** Problema transitorio de **resolución DNS** del servidor hacia api.telegram.org. No es bug del código (el listener ya tiene timeout 30s; los resolving timeouts son del resolvers). El listener se recupera solo en el siguiente ciclo (getUpdates vuelve a conectarse).

**Solución:** Ninguna requerida — evento transitorio (4 apariciones en 3 días, sin pérdida de funcionalidad: los mensajes entrantes se reprocesan en el siguiente poll). Si se vuelve frecuente (>5/día), revisar DNS (systemd-resolved/`/etc/resolv.conf`) o mover el listener a un entorno con DNS estable.

---

## 2026-08-20 · Externo SEACE (sin código)

### SEACE Público 403 "SQLi Filters Categories" + 500 con queries de terceros

**Síntoma:** `SEACE Público: Error en búsqueda {"status":403,"error":{"code":403,"message":"Forbidden - 9420000","details":"SQLi Filters Categories"}}` (19/08 22:36) con query en japonés; y 500s (18/08 18:08-18:09, 19/08 18:56, 20/08 08:22) con queries tipo "Which section of the Income Tax Act...".

**Causa:** El endpoint público del SEACE es consumido por **scrapers externos** (cualquier persona puede llamarlo). El WAF del SEACE bloquea (403) o el backend falla (500) ante queries de terceros. No es nuestro tráfico ni nuestro bug — nuestro buscador ya maneja el fallo con error amigable + retry 5xx (desde `e0cbb8ea`).

**Solución:** Ninguna en nuestro código (monitorear; los 403/500 externos no afectan usuarios de nuestra plataforma).

---

## 2026-08-20 · SMTP MailerSend (transitorio, recuperado)

### 421 en una corrida puntual — mecanismo de reintento funcionó (evidencia)

**Síntoma:** En el panel de monitoreo: `Expected response code "250/251/252" but got code "421", "421 Service not available, closing transmission channel."` (20/08 12:04:51).

**Análisis:** El formato (mensaje crudo + stack vendor-only) es el log estándar de Laravel para un **job fallido con retry pendiente** — no es un fallo definitivo. En la corrida de las 12:00-12:04: 3 correos recibieron 421 transitorio → el mecanismo de reintento con backoff 30s los recuperó → **total_envios: 17, total_errores: 0** (12:04:20). El reintento del job fallido fue exitoso (no aparece en failed_jobs). Los mismos 421 crudos aparecieron el 14/08, 17/08 y 18/08 (mismo patrón transitorio).

**Conclusión:** Evento transitorio de MailerSend, ya manejado por el diseño (throttle 2s + backoff 30s + tope 40/corrida). Sin acción.

---

## 2026-08-20 · WhatsApp (nota)

### Entrega rechazada por "healthy ecosystem engagement" (131049)

**Síntoma:** `WhatsApp Webhook: estado de mensaje {"status":"failed","recipient":"51974570774","errors":[{"code":131049,"title":"This message was not delivered to maintain healthy ecosystem engagement."}]}` (20/08 12:04:54).

**Causa:** Política de Meta: rechaza la entrega a números con bajo engagement del ecosistema (el número no interactúa con el bot). No es un error del sistema — es política de entrega de Meta.

**Solución:** Ninguna de código. Si se vuelve recurrente para un número: el usuario debe interactuar con el bot (enviar mensaje, responder alertas). El sistema ya reenvía pendientes cuando la ventana se reabre.

---

## 2026-08-19 · WhatsApp · `3ac12c6e`

### El template `nuevo_contrato` "no existía" — error 132001 "does not exist in the translation" (cada 2h)

**Síntoma:** `WhatsApp: Error al enviar template {"template":"nuevo_contrato","error":"(#132001) Template name does not exist in the translation","status":404}` en cada corrida de jobs (06:00, 10:00, 14:00, 16:00, 18:00). Se concluyó erróneamente que el template faltaba y se pidió al usuario crearlo de nuevo.

**Causa raíz (diagnóstico por fuerza bruta contra Graph API):** El template `nuevo_contrato` **SÍ existe** en la WABA, pero está registrado con idioma **`es_PE`** (Español – Perú). El código enviaba con idioma `es` → Meta busca la traducción `es` del template → no existe → 132001. Además se confirmó que el template espera EXACTAMENTE 4 parámetros (el payload del sistema ya envía 4: tipo, entidad, código, objeto). El fallback `hello_world` tampoco existe (solo disponible en números de prueba de Meta).

**Solución:**
1. `templateLanguage()` en `WhatsAppNotificationService` → `config('services.whatsapp.template_language', 'es_PE')` (configurable vía `WHATSAPP_TEMPLATE_LANGUAGE` en .env).
2. Usado en `enviarNotificacionComoTemplate()` y en `enviarConTemplateFallback()` (Opción A).
3. Cache `whatsapp:template_invalido` limpiado en producción (quedó marcado por tests).

**Verificación end-to-end:** envío real del template a 51974570774 → webhook: `sent` → `delivered` → `read` ✓ (el mensaje llegó y fue leído).

**Lección:** El error 132001 de Meta es "template name does not exist **in the translation**" — la palabra *translation* es la pista: el template puede existir pero no en el idioma solicitado. Antes de pedir crear un template, probar todas las variantes de idioma contra la API (diagnóstico: enviar con destinatario inexistente 51999999999 → 132001 = no existe / 132000 = parámetros incorrectos / 131049 = destinatario inválido pero template VÁLIDO).

**Archivos:** `app/Services/WhatsAppNotificationService.php`, `config/services.php`

---

## 2026-08-19 · Análisis IA · `e0cbb8ea`

### TDR >20MB → 413 "El archivo excede el tamaño máximo permitido" (contratos mayores con anexos)

**Síntoma:** `MayoresTdr: excepción {"error":"Error HTTP 500: {\"detail\":\"Error interno al procesar el TDR: 413: El archivo excede el tamaño máximo permitido (20MB)\"}"}` — el análisis de un contrato mayor (OCDS-1242145, PDF de 38.5MB) fallaba.

**Causa:** `analizador-tdr/.env` en producción tenía `MAX_FILE_SIZE_MB=20` (el `.env.example` sugiere 50). El microservicio rechazaba con 413 ANTES de analizar; Laravel lo traducía a 500 interno. Los TDRs de contratos mayores (planos, anexos escaneados) superan fácilmente 20MB.

**Solución (escalable, en el microservicio — transparente para Laravel):**
1. `MAX_FILE_SIZE_MB=50` en producción.
2. Pipeline de compresión en `main.py` (`_comprimir_pdf` + `_leer_documento`), aplicado a los 3 endpoints (`/analyze`, `/analyze-direccionamiento`, `/generate-proforma`):
   - Intento 1: `doc.save(garbage=4, deflate=True)` (limpieza de objetos + recompresión de streams).
   - Intento 2: **rasterización a JPEG** con dpi escalonado (150→120→100→80) y reensamblado (reduce 5-10x PDFs escaneados).
   - Si nada funciona: 413 con mensaje claro.
3. Mensaje de error en `TdrAnalysisService.php` actualizado (decía "10 MB", número fijo y desactualizado).

**Verificación con el archivo real del incidente (38.5MB):** con límite 50MB entra directo; forzando límite 10MB, la compresión lo redujo a **8.9MB (77% menos) a 120dpi**. ✓

**Lección:** El límite de tamaño de un microservicio no debe ser un muro: comprimir/degrada antes de rechazar. PyMuPDF ya estaba en el venv — sin dependencias nuevas.

**Archivos:** `analizador-tdr/main.py`, `app/Services/TdrAnalysisService.php`, `.env` del analizador (ops)

---

## 2026-08-19 · WhatsApp · `e0cbb8ea`

### Ruido: error 132001 repetido en cada corrida de job (flag estático por proceso worker)

**Síntoma:** El error de template se repetía 4+ veces al día, una por cada corrida programada (los workers se reinician entre corridas).

**Causa:** `protected static bool $templateInvalido` era un flag **por proceso worker**. Cada corrida arranca un worker nuevo → el flag se resetea → reintenta el template → vuelve a fallar y loguear.

**Solución:**
1. Flag estático → **Cache** `whatsapp:template_invalido` con TTL 12h: al primer fallo 132001 se marca; las corridas siguientes NO intentan el template (cero spam, cero llamadas inútiles a Meta). Al expirar, reintenta una vez (por si el template fue creado).
2. `enviarConTemplateFallback` también respeta el flag (si template inválido → texto plano directo).
3. **Banner en `/configuracion` (admin)**: si el cache está activo, aviso ámbar con instrucciones para crear el template en WhatsApp Manager (visible sin depender de logs).

**Archivos:** `app/Services/WhatsAppNotificationService.php`, `app/Livewire/Configuracion.php`, `resources/views/livewire/configuracion.blade.php`

---

## 2026-08-19 · Buscador público · `e0cbb8ea`

### SEACE Público 500 en búsquedas en vivo (queries de terceros)

**Síntoma:** `SEACE Público: Error en búsqueda {"status":500, ... "palabra_clave":"Which section of the Income Tax Act..."}` — 500 interno de la API pública del SEACE. Los params raros indican queries de **scrapers externos** (el endpoint es público).

**Causa:** Error externo del SEACE (no nuestro). El código ya manejaba el fallo correctamente (log + `success:false` + error amigable).

**Solución:** Retry único con backoff de 1.5s para errores 5xx transitorios en `SeaceBuscadorPublicoService::buscarContratos()` antes de devolver el error.

**Archivos:** `app/Services/SeaceBuscadorPublicoService.php`

---

## 2026-08-19 · Ops (sin código)

### Saturación del servidor (load 37) por procesos Python de diagnóstico — resuelto sin detener el servidor

**Síntoma:** SSH con timeouts de handshake, `uptime` load average 37. Los usuarios siguieron operativos (Apache respondía).

**Causa:** Tests de compresión PDF ejecutados vía heredoc (`python -`) en el servidor con generación de imágenes de alta resolución — quedaron consumiendo CPU.

**Solución (sin stop del servidor):**
1. Identificar los procesos: `ps aux | grep python` → solo los PIDs `python -` (heredocs de test).
2. `kill` de los 2 PIDs culpables (no `uvicorn`, no `fail2ban`, no `frappe`, no servicios).
3. Verificar: load 37 → 5.8 en minutos; `systemctl is-active` de los 8 servicios → todos active (solo `telegram-admin-bot.service` no existe como unit en el servidor — el bot admin de Telegram nunca se instaló; está solo en el repo `deploy/`).
4. `curl` web 200 (137ms) y `/health` del analizador 200.

**Lección:** Nunca ejecutar tests pesados de Python en el servidor de producción vía heredoc. Correrlos con `timeout` desde el primer intento, limitar resolución/tamaño, y matar el proceso si el comando MCP corta (el proceso hijo puede quedar vivo).

---

## 2026-08-19 · UI Direccionamiento · `cf4ecedc` + `19681048`

### Gateway timeout 504 de Cloudflare al detectar direccionamiento en Contratos Mayores

**Síntoma:** Al hacer clic en "Detectar Direccionamiento" en `/buscador-contratos-mayores`, el navegador recibía `504 Gateway Timeout` (Cloudflare corta a los 100s). El análisis en sí terminaba en ~3 min (score 85) pero el request ya había muerto. Los logs no mostraban error del analizador.

**Causa (2 bugs):**
1. El direccionamiento corría **síncrono dentro del request HTTP** del Livewire (`ejecutarDireccionamientoInterno` → `analyzeDireccionamiento`), a diferencia del análisis general que usa `AnalizarTdrMayorJob` async + polling.
2. Tras pasarlo a job async, el `finally` de `detectarDireccionamiento`/`ejecutarDireccionamiento` hacía `analizandoDireccOcid = null` INMEDIATAMENTE después del dispatch → el modal se abría y se cerraba a ~1s (el poll nunca llegaba a ver el estado "en progreso").

**Solución:**
1. Nuevo **`DireccionarTdrMayorJob`** (timeout 600s): crea/actualiza `TdrAnalisisMayor` (tipo direccionamiento, estado EXITOSO/FALLIDO) en background — mismo patrón que `AnalizarTdrMayorJob`.
2. `ejecutarDireccionamientoInterno` en producción: crea fila PENDIENTE (firstOrCreate) + `DireccionarTdrMayorJob::dispatch()`. En local/sync: comportamiento síncrono original.
3. `checkDireccionamientoMayor()`: polling cada 3s (mismo patrón que `checkAnalisisMayor`) — detecta EXITOSO/FALLIDO y muestra el resultado.
4. **Eliminados los `finally`** que reseteban `analizandoDireccOcid` — el estado persiste hasta que el poll detecta el resultado (o el job falla). Reset solo en: catch (error real), flujo síncrono, selector de documentos (ZIP/RAR), o cierre manual.
5. Anti-duplicado: si ya hay un direccionamiento PENDIENTE para el OCID, no lanza otro (retoma el polling).
6. Blade: `wire:poll.3s="checkDireccionamientoMayor"` en el modal de direccionamiento.

**Lección:** Un `finally` que resetea estado de UI es correcto en flujos síncronos pero MATA los flujos async con polling. Al migrar a jobs, revisar cada reset de estado.

**Archivos:** `app/Jobs/DireccionarTdrMayorJob.php` (nuevo), `app/Livewire/BuscadorMayores.php`, `resources/views/livewire/buscador-mayores.blade.php`

---

## 2026-08-19 · Ops Build

### `npm run build` fallaba silenciosamente en producción (Vite 7 vs Node 20.11)

**Síntoma:** El build no se ejecutaba en los deploys manuales; al ejecutarlo: `You are using Node.js 20.11.1. Vite requires Node.js version 20.19+` + `EACCES` en `node_modules/.vite-temp`. El manifest.json tenía días de antigüedad.

**Causa:**
1. Vite 7.3.1 (package-lock) exige Node ≥20.19 — el servidor tenía Node 20.11.1.
2. `deploy/orchestrate.sh` hace `npm run build 2>&1 | tail -3` — el pipe **enmascara el exit code** (tail devuelve 0 aunque npm falle) → el script logueaba "Vite build completado" con build roto.
3. `node_modules` es de root → el build como www-data daba EACCES.

**Solución (ops, sin tocar repo):**
1. Node 22.16.0 instalado en `/usr/local/node-v22` (tarball oficial) + symlinks en `/usr/local/bin` (node/npm/npx).
2. Build ejecutado como root (igual que orchestrate.sh) → 5.2s exitoso, manifest actualizado, assets 200.

**Pendiente (recomendado):** `orchestrate.sh` con `set -o pipefail` o `${PIPESTATUS[0]}` para que un build roto falle el deploy.

---

## 2026-08-18 · Vigilancia Adjudicaciones · `84821b29`

### La alerta de buena pro decía "por tus canales activos" pero debía ser SOLO WhatsApp

**Síntoma:** `/configuracion-alertas` → "Alertar cuando procesos se hayan adjudicado: Recibe aviso... **por tus canales activos**". El requisito del negocio: la alerta de adjudicación solo por WhatsApp.

**Causa:** `VigilarAdjudicacionesMayoresJob::notificarUsuariosOptIn` notificaba por Telegram + WhatsApp + Email (con dedup `adj-telegram`/`adj-whatsapp`/`adj-email`). Además los fallos SMTP 421 del 18/08 15:02-15:06 (user_id 149) eran justamente de ese flujo email.

**Solución:**
1. `notificarUsuariosOptIn`: eliminados los bloques Telegram y Email — queda SOLO WhatsApp.
2. Eliminados imports y método `buildMensajeTelegram` sin uso.
3. UI: texto → "Recibe aviso por WhatsApp cuando un proceso que vigiles pase a buena pro."
4. Los destinatarios del panel admin (`notificarDestinatarios`, configuración manual email/teléfono) se mantienen intactos.

**Archivos:** `app/Jobs/VigilarAdjudicacionesMayoresJob.php`, `resources/views/livewire/configuracion-alertas.blade.php`

---

## 2026-08-18 · Campañas de correo · `bc22da88`

### Botón "Usar en campaña" no hacía nada visible

**Síntoma:** Al hacer clic en "Usar en campaña" (lista de plantillas en `/admin/correos`) no pasaba nada — el modal de campaña nunca se abría.

**Causa:** `loadTemplate()` solo seteaba `subject`/`body` en silencio sin abrir el modal (`$editingId` seguía null).

**Solución:**
1. `loadTemplate(int $id, bool $abrirCampana = false)`: con `true` abre el modal de nueva campaña con la plantilla precargada (nombre + asunto + cuerpo). El select dentro del modal (rellenar sin abrir) mantiene su comportamiento.
2. Evento `trix-cargar` + listener JS para sincronizar el editor Trix con el body (Livewire actualiza el textarea oculto pero Trix no se refresca solo).

**Archivos:** `app/Livewire/EmailCampaigns.php`, `resources/views/livewire/email-campaigns.blade.php`

---

## 2026-08-18 · Campañas de correo · `2bcf2000`

### No existía filtro para "usuarios con ventana WhatsApp vencida" ni plantilla de aviso

**Síntoma:** Se necesitaba notificar por email a los usuarios cuya ventana de 24h de WhatsApp estaba vencida (no reciben alertas), pero el sistema de campañas solo filtraba por todos/premium/no-premium/específicos.

**Solución:**
1. Nuevo filtro `whatsapp-ventana` (`FILTRO_WSP_VENTANA`): usuarios con suscripción WhatsApp activa cuya ventana está cerrada — (A) Meta rechazó una entrega (131047) sin interacción posterior, o (B) última interacción >24h.
2. Relación `User::whatsappSubscriptions()`.
3. Plantilla seed "Ventana 24h WhatsApp vencida" (migración): explica la política de Meta y cómo reactivar (enviar "hola" o responder alertas).
4. UI: botón de filtro + label en la tabla.

**Verificación en producción:** el filtro resolvió 1 destinatario real (operaciones@corporacionfamod.com).

**Archivos:** `app/Models/EmailCampaign.php`, `app/Jobs/SendEmailCampaign.php`, `app/Models/User.php`, `app/Livewire/EmailCampaigns.php`, blade, migración seed

---

## 2026-08-18 · WhatsApp · `81206d71` + `bd6965c6`

### Notificaciones WhatsApp "enviadas" que nunca llegaban (ventana 24h cerrada)

**Síntoma:** La API de Meta respondía 200 (success + wamid) pero los usuarios no recibían nada. Los logs no mostraban errores.

**Causa (2 capas):**
1. Meta **acepta** los mensajes free-form fuera de la ventana de 24h (200) y los rechaza **en la entrega** con 131047 "Re-engagement message" — el fallo solo es visible en los webhooks de estado (`statuses`), que el app ignoraba (ack sin log).
2. El fallback a template existía pero (a) nunca se activaba (el error no llega en el request, llega en el webhook) y (b) el template `nuevo_contrato` fallaba por el idioma `es` (ver entrada del 19-08).

**Solución:**
1. **Tracking de entrega por wamid**: `notification_sends` gana `wamid` + `estado_entrega` (aceptado/delivered/read/failed) + `reenviado_at`. El webhook correlaciona cada estado con el envío.
2. **Detección de ventana cerrada**: fallos 131047/130472 marcan `whatsapp_subscriptions.ultima_entrega_fallida_at` (evidencia real para la UI).
3. **Cola de reenvío** (`ReenviarWhatsAppPendientesJob`): cuando el usuario interactúa (mensaje o botón → webhook), se reenvían los procesos fallidos (últimos 3 días, máx 30/corrida). Si un reenvío vuelve a fallar por ventana → vuelve a la cola.
4. **Template-first cuando la ventana está cerrada**: si `ultima_interaccion_at` es reciente (<23h) → interactive directo; si no → template primero (entrega garantizada) + follow-up interactivo.
5. `ultima_interaccion_at` en suscripciones: se actualiza con cada mensaje/botón entrante (webhook).
6. `wamid` capturado en el engine (menores) y en `NotificarContratosMayoresJob` (mayores) y persistido vía `ProcessNotificationTracker::recordNotification(..., ?string $wamid)`.

**Verificación:** ciclo completo probado en producción: envío → wamid → webhook failed 131047 → estado `failed` → interacción → reenvío → estado `aceptado`.

**Lecciones:** (1) 200 de Meta ≠ entregado: monitorear los webhooks de `statuses`. (2) La ventana de 24h se abre con mensajes del usuario O con entrega de template; responder alertas (botones) también la renueva.

**Archivos:** migración `2026_08_18_000003`, `WhatsAppNotificationService`, `WebhookWhatsAppController`, `ReenviarWhatsAppPendientesJob` (nuevo), `ProcessNotificationTracker`, `ImportadorTdrEngine`, `NotificarContratosMayoresJob`, modelos

---

## 2026-08-18 · UI WhatsApp · `77ba1871` + `22cca348`

### Aviso de ventana 24h aparecía en la sección Telegram + campana roja para todos

**Síntoma 1:** El mensaje "Si dejas de enviar mensajes al bot por mas de 24 horas..." se mostraba debajo de CADA suscriptor Telegram (estaba dentro del `@foreach` de Telegram en vez de la sección WhatsApp).

**Síntoma 2:** Con la nueva columna `ultima_interaccion_at` (null para todos hasta que interactúen), la campana roja se mostraba para TODOS los suscriptores — falsos positivos.

**Solución:**
1. Aviso movido a la sección WhatsApp (fuera del foreach de Telegram).
2. Lógica de estado: 🔴 roja SOLO con evidencia de ventana vencida — (A) `ultima_entrega_fallida_at` más reciente que la última interacción, o (B) interacción >24h. 🟢 verde en cualquier otro caso (incluido "sin evidencia").
3. Tooltips explicativos (rojo: política de Meta, ventana se abre escribiendo o respondiendo alertas; verde: ventana activa).

**Archivos:** `resources/views/livewire/configuracion-alertas.blade.php`

---

## 2026-08-18 · WhatsApp · `5a2270eb` + `922e9200`

### Notificaciones no llegaban y no se sabía por qué (sin visibilidad de entrega)

**Síntoma:** El usuario reportó que las notificaciones WhatsApp "ya no llegan". Los jobs logueaban envíos exitosos sin errores.

**Causa:** Sin correlación entre envíos y estados de entrega; los webhooks de estado de Meta se ignoraban (solo ack 200).

**Solución (paso previo al tracking completo):**
1. `WebhookWhatsAppController::logStatuses()`: registrar estados `delivered/read/failed` con wamid, recipient y errores.
2. Diagnosticar: test manual → API 200 + wamid → webhook `failed 131047` → causa raíz identificada (ventana 24h).
3. `enviarProcesoASuscriptor` con template-first (ver entrada anterior).

**Archivos:** `app/Http/Controllers/Api/WebhookWhatsAppController.php`, `app/Services/WhatsAppNotificationService.php`

---

## 2026-08-18 · Alertas Email · `db265ce3`

### "Recibir todos los procesos" en alertas por correo saturaba MailerSend

**Síntoma:** `operaciones@corporacionfamod.com` (con `notificar_todo=true`) recibía ~300 correos/día, consumiendo el límite free de MailerSend (~3,000/mes) y causando los 421.

**Solución:** La opción "Recibir todos los procesos" se ELIMINA de `/configuracion-alertas` (queda solo "Filtrar por palabras clave del perfil"):
1. Blade: quitado el radio "Recibir todos los procesos" del modal de email.
2. Componente: `notificar_todo` siempre `false` al guardar.
3. Backfill (migración `2026_08_18_000001`): todos los `email_subscriptions.notificar_todo=true` → `false`.
4. Verificado en producción: 0 suscriptores con `notificar_todo=1`.

**Archivos:** `resources/views/livewire/configuracion-alertas.blade.php`, `app/Livewire/ConfiguracionAlertas.php`, migración

---

## 2026-08-18 · Producción · (commit pendiente)

### Doble incidente: `fopen cache: Permission denied` + 421 SMTP MailerSend

**Síntomas (producción, 18-08):**
1. `ErrorException: fopen(/var/www/vigilante-seace/storage/framework/cache/data/68/b0/...): Failed to open stream: Permission denied` a las 06:00 y 08:00 (horas de los jobs de suscripciones).
2. `NotificarEmailSuscriptoresJob: fallo al enviar email ... code "421" Service not available, closing transmission channel` — 13 fallos el mismo día, TODOS para `operaciones@corporacionfamod.com` (suscriptor con `notificar_todo=true`, recibe todos los procesos nuevos).

**Causa 1 — Doble scheduler con permisos de root:**
- Existía un **crontab de root** (`* * * * * php artisan schedule:run`) REDUNDANTE junto al servicio systemd `vigilante-scheduler` (que ya corre como `www-data` con `artisan schedule:work`).
- Los jobs síncronos del schedule (`subscriptions:alerts`, `subscriptions:expire`) ejecutados vía el cron de root creaban directorios/archivos de cache (`storage/framework/cache/data/...`) como **root**.
- Luego php-fpm/queue (`www-data`) intentaban escribir la misma cache → `Permission denied`.
- Se agravó al migrar `CACHE_STORE=database` → `file` (antes los deadlocks de BD, ahora permisos de archivos).

**Solución 1 (ops, sin código):**
- Eliminar el crontab de root (`crontab -r` — solo contenía esa línea). El scheduler queda ÚNICAMENTE en systemd como www-data.
- `chown -R www-data:www-data storage/framework/cache`.

**Causa 2 — Rate limit de MailerSend (plan free):**
- El throttle de 750ms ya estaba activo, pero las ráfagas (~13 correos en 2 min) superan el límite por hora del plan free de MailerSend (~100/hora).
- Los correos fallidos NO se marcaban como enviados → reintentaban en cada corrida (2h) → se acumulaban fallos del mismo contrato.
- Volumen: ~68 correos/día ≈ 2,000/mes — cerca del límite free (3,000/mes).

**Solución 2 (código, `NotificarEmailSuscriptoresJob`):**
1. Throttle 750ms → 2s entre envíos.
2. Reintento con backoff: si el error SMTP es temporal (421/429/"Service not available"/"Too many"), espera 30s y reintenta UNA vez.
3. Tope `MAX_ENVIOS_POR_CORRIDA=40` correos por suscriptor por corrida; el resto queda para la siguiente (el dedup evita duplicados).
4. `$timeout` 300s → 900s (el backoff suma tiempo).

**Lecciones:**
- NUNCA tener dos schedulers (cron + systemd): además del doble gasto, si uno corre como root contamina los permisos de `storage/` del otro.
- Tras cambiar el driver de cache, verificar propiedad de los directorios de escritura.
- Con SMTP con rate limit, un "fallo" de envío no debe reintentarse en bucle eterno: backoff + tope por corrida + revisar el plan del proveedor (MailerSend free ≈ 3,000/mes).

**Archivos:** `app/Jobs/NotificarEmailSuscriptoresJob.php`, ops en servidor (crontab root, chown cache).

---

## 2026-08-15 · Geografía Contratos Mayores · `823f2e61`

### Tablas maestras de geografía + backfill + filtros cascada en buscador

**Objetivo:** Soportar la exportación con filtros geográficos normalizados (departamento/provincia/distrito) pedida por el cliente, y añadir filtros de ubicación al buscador de Contratos Mayores.

**Semántica confirmada de la API OCDS** (verificada con datos reales de producción — 11,386 contratos, 100% con `datos_raw`):
- `parties[rol=buyer].address.department` → **departamento** (25 únicos)
- `parties[rol=buyer].address.region` → **provincia** (194 únicos)
- `parties[rol=buyer].address.locality` → **distrito** (1049 únicos)
- Ejemplo real: `department=LORETO`, `region=UCAYALI` (Prov. de Ucayali-Loreto), `locality=CONTAMANA` ✓

**Implementación:**
1. **Migración** `2026_08_15_000001_create_geo_master_tables.php`: tablas `departamentos`/`provincias`/`distritos` (nombre único + FK jerárquico) y `contratos_mayores` gana `departamento_id`/`provincia_id`/`distrito_id` (nullable FK + índices). Tardó 1m30s en producción por los índices sobre 11K filas.
2. **`GeoResolverService`**: firstOrCreate normalizado (trim + mb_strtoupper) + cache estático por proceso + listas para filtros con cache 1h.
3. **`SeaceMayoresService::extraerGeografiaDeRelease()`** (público): extrae geografía del release; usado por `mapearRelease` y por el backfill.
4. **`ImportarContratosMayoresJob`**: resuelve geo en `mapearCampos`, compara/actualiza IDs en `camposCambiados`/`actualizarContrato`, y mantiene `dbMap` con IDs.
5. **`RefrescarEstadosContratosMayoresJob`**: `columnasCambiadas` compara/actualiza los 3 IDs geo (backfill gradual vía refresco).
6. **`BackfillGeoContratosMayores`** (`php artisan backfill:geo-mayores`): llena IDs desde `datos_raw` SIN llamar a la API. Usa `chunkById` (no `chunk`, que desfasa la paginación al hacer UPDATEs). Resultado en producción: **11,386/11,386 con geo, 0 fallos** en ~45s.
7. **`BuscadorMayores`**: filtros cascada (dep→prov→dist con reset) + rango de fechas de publicación, con `#[Url]` para deep-linking. Se añadieron a `contarFiltrosActivos` y a `limpiarFiltros`.

**Deploy verificado:** migración OK, backfill OK, cachés (`optimize:clear` + `route:cache` + `config:cache`) OK, página pública responde con los 25 departamentos en el dropdown.

**Lección:** Para backfill de una tabla con UPDATEs dentro del loop, usar `chunkById` — con `chunk()` (offset) los rows saltan. Y validar la semántica de campos de una API con datos reales antes de mapear (el orden dep/prov/dist NO es obvio: `department`/`region`/`locality`).

**Archivos:** migración geo, 3 modelos nuevos, `GeoResolverService`, `SeaceMayoresService`, ambos jobs, `BuscadorMayores` + blade, `BackfillGeoContratosMayores`

---

## 2026-08-15 · Validación IA · (pendiente de commit)

### Direccionamiento fallaba con "1 validation error for DireccionamientoAnalysisResponse hallazgos_criticos.0..."

**Síntoma:** Al pedir "Detectar Direccionamiento" desde Telegram para el contrato `CM-67-2026-MP-FN-UEDFSMAR`, el análisis fallaba:

```
❌ Error al analizar direccionamiento: No se pudo completar el análisis...
{"detail":"Respuesta del LLM no cumple esquema: 1 validation error for
DireccionamientoAnalysisResponse\nhallazgos_criticos.0.de..."}
```

**Causa:** El schema Pydantic `DireccionamientoHallazgo` exige `descripcion_hallazgo` (min 10, max 500) y `red_flag_detectada` (min 5, max 300). Gemini a veces devuelve hallazgos con nombres de campo alternativos (`descripcion`, `detalle`, `red_flag`) o campos vacíos. El sanitizer `_sanitize_direccionamiento_payload` solo normalizaba `categoria` y `nivel_de_gravedad`, así que el hallazgo llegaba a validación sin `descripcion_hallazgo` → todo el análisis fallaba.

**Solución:** Sanitizer robusto en `analyzer_service.py`:
1. Mapear nombres alternativos: `descripcion_hallazgo ← descripcion|detalle|hallazgo`, `red_flag_detectada ← red_flag|senal_alerta|alerta|redflag`
2. Si la descripción tiene < 10 chars → descartar ESE hallazgo (no romper todo el análisis)
3. Si falta red flag → derivarla de la descripción
4. Truncar a los máximos del schema (500/300)
5. Construir dict limpio con SOLO los 4 campos del schema (elimina campos basura)

**Lección:** Nunca confiar en que el LLM respete los nombres de campo exactos. El sanitizer debe ser defensivo: mapear alias, descartar lo insalvable y construir el payload limpio antes de validar con Pydantic.

**Archivos:** `analizador-tdr/app/services/analyzer_service.py`

---

## 2026-08-15 · Notificaciones Mayores · (pendiente de commit)

### Alertas duplicadas de "NUEVO CONTRATO MAYOR" — contratos de abril/junio notificados en agosto

**Síntoma:** El usuario recibía en WhatsApp el mismo contrato múltiples veces (`CP-ABR-6-2026-FONAFE-1` publicado 23/06, `CONV-PROC-2-2026-PEJENP-PJ` publicado 27/04). Además recibía alertas de contratos de meses atrás como si fueran nuevos.

**Causa raíz (2 bugs):**
1. **Sin dedup**: `NotificarContratosMayoresJob` tenía un comment afirmando que usaba `notified_processes` + `notification_sends` para dedup, pero el código NUNCA llamaba al tracker. Contratos Menores sí lo usa (`ProcessNotificationTracker` en `ImportadorTdrEngine`).
2. **Filtro de recencia equivocado**: usaba `created_at` (fecha de inserción en NUESTRA BD) en vez de `fecha_publicacion`. Cuando el escaneo global re-importó contratos viejos, `created_at` se actualizó a agosto → entraron en la ventana de 6h → notificados como "nuevos".

**Solución:**
1. **Dedup per-suscriptor**: inyectar `ProcessNotificationTracker` en el job. Antes de enviar: `wasAlreadyNotified(ocid, user_id, canal, recipientId)` → skip. Después de enviar: `recordNotification(...)`. La BD tiene constraint unique `uq_send_process_user_canal_recipient (notified_process_id, user_id, canal, recipient_id)` como red de seguridad final (imposible duplicar aunque 2 jobs corran en paralelo).
2. **Filtro correcto**: `where('fecha_publicacion', '>=', $desde)` en vez de `created_at`.
3. **Ventana 6h → 12h**: cubre el hueco nocturno (los runs son 06:00-20:00; con 12h el run de las 06:00 cubre desde las 18:00 del día anterior).
4. **Log** `total_omitidos_dup` para monitorear duplicados bloqueados.

**Verificación del dedup:** constraint unique verificado en producción: `uq_send_process_user_canal_recipient`.

**Lección:** Un comment en el código NO garantiza que el código haga lo que dice. Y `created_at` es "cuándo lo guardamos", no "cuándo pasó el evento" — para filtrar eventos por recencia usar la fecha del evento (`fecha_publicacion`).

**Archivos:** `app/Jobs/NotificarContratosMayoresJob.php`, `routes/console.php`

---

## 2026-08-15 · Telegram Bot · `ee84ed2a`

### cURL error 28 recurrente en getUpdates (3+ veces por día)

**Síntoma:** `Telegram Bot Listener Error {"exception":"cURL error 28: Operation timed out after 15001 milliseconds with 0 bytes received"}` cada pocas horas.

**Causa:** El listener usaba `Http::timeout(15)` pero el long-poll de Telegram mantiene la conexión ~10s + latencia de red. Cuando Telegram excedía 15s, el cURL expiraba. El Admin Bot listener ya usaba 35s correctamente; el listener principal no.

**Solución:** `Http::timeout(15)` → `Http::timeout(30)` — margen 3x sobre el long-poll de 10s, sin sacrificar respuesta a SIGTERM.

**Archivos:** `app/Console/Commands/TelegramBotListener.php`

---

## 2026-08-15 · Análisis IA WhatsApp · `907b65e8` + `ae414b79`

### TDR DOCX guardado con extensión .pdf rompía el análisis de direccionamiento ("The document has no pages")

**Síntoma:** Al presionar "🔍 Detectar Direccionamiento" en el bot de WhatsApp para el contrato `CP-ABR-6-2026-FONAFE-1`, devolvía:

```
❌ Error HTTP 400: {"detail":"Error en análisis direccionamiento PDF directo:
400 INVALID_ARGUMENT. {'error': {'code': 400, 'message':
'The document has no pages.', 'status': 'INVALID_ARGUMENT'}}"}
```

En los logs: `AnalizadorTDR: Error en direccionamiento {"file":"06be05fb....pdf", ...}`.

**Causa raíz (2 capas):**
1. El TDR descargado del SEACE era un **Microsoft Word 2007+ (DOCX)** pero el sistema lo guardaba SIEMPRE con extensión `.pdf` (hardcodeada en los bot listeners)
2. El `gemini_client.py` hardcodeaba `mime_type="application/pdf"` al enviar el documento a Gemini multimodal. Gemini NO soporta DOCX → "no pages"
3. Primer intento de fix (mandar MIME DOCX) falló también: `Unsupported MIME type: application/vnd.openxmlformats-officedocument.wordprocessingml.document` — Gemini directamente no acepta DOCX en modo multimodal

**Solución (3 capas):**
1. **Bots PHP** (`WhatsAppBotListener` + `TelegramBotListener`): helper `detectarExtensionDocumento()` que detecta el tipo real por **magic bytes** (`%PDF` → pdf, `PK` → docx, OLE2 → doc) y guarda el temp con la extensión real. También valida que la descarga no venga vacía.
2. **Pipeline IA** (`analyzer_service.py`): en `analyze_direccionamiento_document()` y `generate_proforma_document()`, si los magic bytes indican Word (PK/OLE2), se **extrae texto con `_extract_docx_text()`** y se analiza por la ruta textual (RAG + prompt forense), NUNCA por multimodal.
3. **Gemini client** (`gemini_client.py`): helper `_detect_document_mime()` por magic bytes como red de seguridad para PDFs con extensión incorrecta.

**Verificación:** El archivo que fallaba ahora devuelve `score: 95, veredicto: ALTAMENTE DIRECCIONADO`.

**Lección:** Nunca confiar en la extensión del archivo para determinar el formato. Los documentos del SEACE vienen en PDF y Word mezclados. Detectar por magic bytes (primeros 4-8 bytes) es la única forma confiable. Y recordar que Gemini multimodal solo soporta PDF/imágenes/audio/video — DOCX siempre debe ir por extracción de texto.

**Archivos:** `analizador-tdr/app/services/llm/gemini_client.py`, `analizador-tdr/app/services/analyzer_service.py`, `app/Console/Commands/WhatsAppBotListener.php`, `app/Console/Commands/TelegramBotListener.php`

---

## 2026-08-14 · Monitoreo · `ffa8ba3e`

### El item "Monitoreo del Sistema" no aparecía en el menú aunque el permiso estaba asignado

**Síntoma:** El permiso `view-monitoreo-sistema` se creó vía migración y se asignó al rol admin, pero el item del menú no aparecía en `/dashboard` ni en localhost.

**Causa:** El sistema de permisos usa `@can('slug')` en Blade, que consulta el **Gate de Laravel**. Cada permiso debe registrarse explícitamente con `Gate::define('slug', fn ($user) => $user->hasPermission('slug'))` en `AppServiceProvider::boot()`. Se creó el permiso en BD pero no se registró en el Gate → `@can` siempre devolvía false.

**Solución:** Agregar `Gate::define('view-monitoreo-sistema', ...)` en `AppServiceProvider.php`.

**Lección:** En este proyecto, crear un permiso nuevo requiere 4 pasos: (1) registro en BD, (2) `Gate::define` en AppServiceProvider, (3) item de menú con `@can`, (4) ruta con `middleware('can:...')`. Si falta el paso 2, todo lo demás falla silenciosamente.

**Archivos:** `app/Providers/AppServiceProvider.php`, `app/Livewire/RolesPermisos.php`

---

## 2026-08-14 · Contratos Mayores · `1d21e1b0`, `11cf3d80`, `d3dc8f26`, `3ae4df83`, `6a4f772f`

### Los estados de Contratos Mayores quedaban congelados para siempre (nunca se actualizaban)

**Síntoma:** SEACE mostraba procesos con estado ADJUDICADO pero el portal los mostraba con el estado viejo. El usuario detectó que "como almacenamos los procesos en BD, los estados no cambian".

**Causa raíz (investigación completa):**
1. `ImportarContratosMayoresJob` solo hacía INSERT de contratos NUEVOS; los existentes se omitían para siempre (`if isset($storedMap[$ocid]) continue`)
2. El endpoint `/releases` de la API OCDS solo expone los últimos ~2 días de eventos (10,000 releases, 500 páginas × 20). Un contrato que cambia de estado semanas después ya no está en la ventana
3. El job escaneaba solo 80 páginas (~horas), ni siquiera el día completo

**Descubrimientos clave de la API OCDS (contratacionesabiertas.oece.gob.pe):**
- `/releases`: eventos completos pero SOLO última ventana de ~2 días. 500 páginas máximo (page 501 = 404). Ignora `limit`, `fechaDesde`, `dateFrom`
- `/search`: 2,748,755 registros históricos, filtro `year` funciona (45,504 en 2026), pero el compiledRelease viene STRIPPED (sin items/status/awards) — inútil para estados
- `/records?ocid=X`: devuelve el **compiledRelease completo** (con items, status, awards) de CUALQUIER contrato sin importar antigüedad — LA FUENTE DE VERDAD para refrescar

**Solución (estrategia día/noche):**
1. **Import incremental** cada 3h con 15 páginas: descubre contratos nuevos
2. **`RefrescarEstadosContratosMayoresJob`** con filtro de antigüedad por `fecha_publicacion`:
   - Madrugada 03:00: refresca los últimos 30 días (~3,899 contratos, ~38 min)
   - Día 13:00 y 18:00: refresca los últimos 7 días, 300 c/u
   - `antiguedadDias = 0` = escaneo global manual (solo bajo demanda)
3. **Salvaguardas**: abort automático si la API cae (15 fallos seguidos), bulk UPDATE para sin-cambios, progreso cada 250, timeout 2h

**Bug introducido durante el proceso:** Se despacharon 4 jobs globales con el constructor viejo (sin `antiguedadDias`) y luego se desplegó el constructor nuevo. Al re-hidratar, la propiedad tipada `$antiguedadDias` no existía en el payload serializado → `Typed property ... must not be accessed before initialization` → 3 jobs murieron (5,821 de 11,332 refrescados).

**Fix:** `protected int $antiguedadDias = 30;` con valor por defecto en la propiedad.

**Lección:** Nunca desplegar un job con una propiedad nueva sin valor por defecto mientras hay jobs serializados del constructor viejo en la cola.

**Archivos:** `app/Jobs/ImportarContratosMayoresJob.php`, `app/Jobs/RefrescarEstadosContratosMayoresJob.php`, `app/Services/SeaceMayoresService.php`, `routes/console.php`

---

## 2026-08-06 · SEO Regional · `3cae390c`

### Las 26 URLs regionales (`/buscador-publico/{dep}`) no se indexaban como páginas separadas

**Síntoma:** Google trataba `/buscador-publico?dep=amazonas` como una sola página con variantes de query string, no como 26 páginas independientes.

**Causa:** Los parámetros de filtro estaban solo en query string (`?dep=`), no en la ruta limpia.

**Solución:**
1. Crear ruta limpia `/buscador-publico/{dep}/{prov?}/{dist?}` que renderiza una vista dedicada con meta tags únicos por región
2. El Livewire `BuscadorPublico` recibe `initialDep` en `mount()` para precargar el filtro
3. Meta title: `"🔍 Contratos Menores en {dep} 2026 — Buscador SEACE"`
4. Canonical: `/contratos-estado/{dep}` (evita duplicate content con la otra URL alias)
5. 26 URLs incluidas en sitemap.xml

**Archivos:** `buscador-publico-regional.blade.php`, `BuscadorPublico.php`, `routes/web.php`

---

### Las 88 URLs de Contratos Mayores (`/buscador-contratos-mayores/{entidad}`) no existían

**Síntoma:** Sin páginas dedicadas para "contratos mayores gobierno regional arequipa", 0 tráfico regional en mayores.

**Solución:**
1. Ruta `/buscador-contratos-mayores/{entidad}` que busca la entidad en DB (`entidades_mayores`) por nombre normalizado
2. `BuscadorMayores::mount()` recibe `initialEntidad`, busca en DB vía `LOWER(nombre)`, setea `entidadTexto` y `entidadFiltro`
3. Sidebar con 88 entidades desde DB (cache 24h), búsqueda Alpine.js, límite 20 items + "Ver todas"
4. Vista por defecto en cuadrícula (`localStorage.vistaMayores = 'grid'`)
5. 88 URLs incluidas en sitemap.xml

**Archivos:** `buscador-mayores-regional.blade.php`, `BuscadorMayores.php`, `routes/web.php`

---

## 2026-08-06 · H1 · `88cbb725`

### Duplicación masiva de `<h1>` en todas las páginas autenticadas

**Síntoma:** `/buscador-publico` mostraba 2 H1s: `"Vigilante SEACE"` + `"Buscador de Licitaciones del SEACE"`. El `<h1>Vigilante SEACE</h1>` del logo del sidebar contaminaba todas las páginas.

**Causa:** El sidebar logo en `layouts/app.blade.php:102` usaba `<h1>` en vez de `<div>`. Google y lectores de pantalla veían 2 títulos principales por página.

**Solución:** Cambiar `<h1>Vigilante SEACE</h1>` → `<div>Vigilante SEACE</div>` en:
- `layouts/app.blade.php:102` (sidebar logo — afectaba ~40 páginas)
- `layouts/guest.blade.php:38` (header login/register)

Cada página ahora tiene exactamente 1 `<h1>` semántico.

**Archivos:** `layouts/app.blade.php`, `layouts/guest.blade.php`

---

### H1 visible con mismo tamaño que H2 "Filtros de Búsqueda" → sin jerarquía visual

**Síntoma:** El título de página (`text-xl`) y el H2 de filtros (`text-lg lg:text-xl`) se veían del mismo tamaño en desktop.

**Solución:** Subir H1 de `text-xl` a `text-2xl` en ambas páginas. Jerarquía clara: H1 (24px) > H2 (18-20px).

**Archivos:** `buscador-publico.blade.php`, `buscador-mayores.blade.php`

---

### El H1 del buscador estaba oculto con `sr-only`

**Síntoma:** `/buscador-publico` no tenía título visible para usuarios. Solo screen readers y Google lo veían.

**Solución:** Agregar H1 visible con `text-2xl font-bold text-neutral-900` + subtítulo descriptivo. El `sr-only` original se bajó a `<h2>`.

**Archivo:** `buscador-publico.blade.php`

---

## 2026-08-06 · SEO General · `ecd05dce`

### CTR catastrófico de 0.62% (79K impresiones, solo 490 clics)

**Síntoma:** Google mostraba el sitio en primera página para 79,000 búsquedas/mes pero casi nadie hacía click. `/buscador-publico` con 72K impresiones tenía CTR de 0.42%.

**Causa raíz:** Marca muerta "Licitaciones MYPe" seguía apareciendo en títulos SEO, meta descriptions genéricas sin año/emoji/CTA, sin OG/Twitter tags, sin schema markup actualizado.

**Solución (Fase 1 completa):**
1. **Rebrand**: 17 referencias a "Licitaciones MYPe" → "Vigilante SEACE" en títulos, schemas, body text
2. **Meta titles**: Emojis, año 2026, keywords principales al inicio, CTA ("Gratis", "Paso a Paso")
3. **Meta descriptions**: 140-155 chars, beneficio concreto, CTA explícito
4. **OG + Twitter cards**: Añadidos al layout público (afecta 14 páginas)
5. **Schema JSON-LD**: WebApplication, SearchAction, BlogPosting, BreadcrumbList, Place (regional)
6. **4 blog posts críticos**: títulos reescritos con año + keywords (tenían CTR 0%-0.49%)

**Archivos:** 15+ archivos modificados. Ver `ANALISIS_COMPLETO_SEO_SEM.md` para detalle completo.

---

### `que-es-el-seace.md` tenía title/slug mismatch

**Síntoma:** 204 impresiones, CTR 0.49%. La URL `/blog/que-es-el-seace/` mostraba en Google el título "Ley N° 32069: La Nueva Ley de Contrataciones del Estado Explicada". El usuario buscaba "qué es el SEACE" y Google mostraba un título sobre una ley.

**Causa:** El frontmatter title no coincidía con el slug. El contenido original era sobre SEACE pero el título fue cambiado a Ley 32069 sin actualizar el slug.

**Solución:** Reescribir title a `"🔰 ¿Qué es el SEACE? Guía Completa del Sistema de Contrataciones 2026 — Ley 32069 Explicada"`.

**Archivo:** `blog/source/_posts/que-es-el-seace.md`

---

## 2026-08-06 · Bug Técnico

### Las vistas compiladas no se actualizaban después de `view:clear`

**Síntoma:** Modificaciones al blade del Livewire (ej: `@if(!$regionalMode)`) no se reflejaban en el HTML servido. El filtro geográfico seguía visible aunque la condición era `true`.

**Causa:** `php artisan view:cache` había pre-compilado las vistas en `storage/framework/views/`. El comando `view:clear` NO eliminaba algunos archivos cacheados grandes (>200KB). El servidor servía la versión compilada vieja.

**Solución:** Borrar manualmente `storage/framework/views/*.php` con `Remove-Item` antes de `view:clear`. Verificar que el conteo de archivos sea 0.

**Lección:** No confiar ciegamente en `artisan view:clear`. Verificar `ls storage/framework/views/ | wc -l`.

---

### `@@context` en JSON-LD colisionaba con directiva de Livewire

**Síntoma:** Error `ParseError: syntax error, unexpected end of file, expecting "elseif" or "else" or "endif"` al cargar `buscador-publico-regional.blade.php`. El error apuntaba a la línea del `@endsection`.

**Causa:** El JSON-LD contenía `"@@context": "https://schema.org"`. Livewire registra una directiva `@context` que interfería con el escape de Blade. `@@context` se compilaba como `<?php ... context()->has(...) ?>` en vez de `@context` literal.

**Solución:** Reemplazar el JSON-LD inline por `{!! json_encode([...], JSON_PRETTY_PRINT) !!}`. Esto evita que Blade interprete `@context` como directiva.

**Archivos:** `buscador-publico-regional.blade.php`, `buscador-mayores-regional.blade.php`

---

### `@hasSection` dentro de `@unless...@else` causaba error de parseo

**Síntoma:** `ParseError: syntax error, unexpected end of file, expecting "elseif" or "else" or "endif"` en `layouts/public.blade.php`.

**Causa:** La combinación `@unless(app()->environment('production')) ... @else @hasSection('noindex') ... @endif @endunless` creaba anidamiento confuso de directivas Blade. El `@endif` de `@hasSection` dentro del `@else` del `@unless` rompía el parser.

**Solución:** Reemplazar `@hasSection('noindex') ... @endif` por `@if(trim($__env->yieldContent('noindex'))) ... @endif`. Usar check directo en vez de directiva anidada.

**Archivo:** `layouts/public.blade.php`

---

### `<div class="mt-3">` duplicado rompía la maquetación de filtros

**Síntoma:** Al agregar `@if(!$this->regionalMode)` alrededor del filtro geográfico, la línea original `<div class="mt-3" x-data="...">` quedó duplicada (una dentro y otra antes del `@if`). Esto creaba un div vacío que rompía el espaciado visual entre filtros y resultados.

**Causa:** Error de edición al insertar el bloque `@if`. La línea original no fue removida.

**Solución:** Eliminar la línea duplicada. Verificar que solo haya una ocurrencia del `<div class="mt-3">` dentro del bloque condicional.

**Archivo:** `livewire/buscador-publico.blade.php`

---

## 2026-07-25 · WhatsApp · `f16625a0`

### Error `#131009` en WhatsApp Business API — título de lista excedía 24 caracteres

**Síntoma:** Error `#131009` al enviar notificaciones de Contratos Mayores por WhatsApp. El mensaje fallaba.

**Causa:** El texto `"🔍 Detectar Direccionamiento"` (25 caracteres) excedía el límite de 24 caracteres impuesto por WhatsApp para títulos de lista interactiva. Además, el `ocid` enviado contenía caracteres especiales que rompían el formato.

**Solución:**
1. Truncar título a ≤24 caracteres: `"🔍 Direccionamiento"` (18 chars)
2. Agregar método `sanitizeOcid()` para limpiar caracteres especiales
3. Remover el header del keyboard interactivo
4. Desplegado a prod + QA

**Archivos:** `app/Jobs/NotificarContratosMayoresJob.php`

---

## 2026-08-06 · UX

### El sidebar de entidades (88 items) era ilegible sin búsqueda

**Síntoma:** La lista vertical de 88 entidades en `/buscador-contratos-mayores/{entidad}` ocupaba toda la pantalla. Imposible navegar.

**Solución:**
1. Agregar buscador Alpine.js con ícono de lupa (luego movido a la derecha por colisión con texto)
2. Límite de 20 entidades visibles por defecto
3. Botón "Ver las 88 entidades ▼" para expandir
4. Al buscar, se filtran todas sin límite
5. Mobile: chips horizontales scrolleables (solo 15 primeras)

**Archivo:** `buscador-mayores-regional.blade.php`

---

### La tabla de Contratos Mayores desbordaba el contenedor en modo regional

**Síntoma:** Columnas con texto largo (ej: "Descripción" con 200+ caracteres) empujaban la tabla fuera del ancho del contenedor.

**Causa:** Múltiples elementos con `overflow: visible` (clase e inline style) en la cadena de contenedores del Livewire. La tabla sin `table-layout: fixed` expandía sus columnas al contenido.

**Solución:**
1. `#regional-mayores-content { overflow-x: auto }` con `max-width: 100%`
2. `[wire\:id]` y sus divs hijos con `max-width: 100% !important`
3. Elementos con `style="overflow: visible"` sobreescritos con `overflow-x: auto !important`
4. `<table>` con `min-width: 700px` para forzar scroll horizontal en vez de desbordar

**Archivo:** `buscador-mayores-regional.blade.php`

---

### El ícono de lupa en el filtro de entidades colisionaba con el texto

**Síntoma:** El input "Filtrar entidad..." tenía el ícono `left-2.5` que se superponía al texto ingresado.

**Causa:** Posicionamiento absoluto del SVG a la izquierda con poco espacio de padding (`pl-7`, 28px) para el texto. El ícono (14px) terminaba en 24px y el texto empezaba en 28px — margen insuficiente.

**Solución:** Mover el ícono a la derecha: `right-2.5` + `pr-8` en el input. El texto usa el lado izquierdo sin obstrucción.

**Archivo:** `buscador-mayores-regional.blade.php`

---

## 2026-08-06 · SEO Técnico

### El sitemap se regeneraba cada 1 hora on-demand

**Riesgo:** `Cache::remember('sitemap_xml', 3600)` — cada hora, el primer visitante de `/sitemap.xml` disparaba 3 llamadas API + 2 queries DB para regenerar ~672 URLs.

**Mitigación propuesta:** Subir TTL a 24h o implementar pre-warm vía cron. (Pendiente de implementación.)

---

### URLs departamentales duplicadas en 2 patrones

**Síntoma:** `/buscador-publico/ancash` y `/contratos-estado/ancash` apuntaban al mismo contenido con canonical `url()->current()`. Google veía duplicate content.

**Solución:** Canonical de `/buscador-publico/{dep}` apunta a `/contratos-estado/{dep}`. Solo `/contratos-estado/` está en el sitemap.

**Archivo:** `buscador-publico-regional.blade.php`

---

## 📋 Pendientes conocidos

| Issue | Prioridad | Notas |
|---|---|---|
| `deploy/orchestrate.sh`: `npm run build | tail -3` enmascara el exit code (build roto = deploy "exitoso") | Alta | Usar `set -o pipefail` o `${PIPESTATUS[0]}` |
| `telegram-admin-bot.service` no instalado en producción | Media | La unit existe en `deploy/` pero nunca se instaló; el bot admin de Telegram no corre |
| Sitemap: subir cache a 24h o pre-warm | Media | Riesgo bajo, 672 URLs es poco |
| FAQ schema automatizado por post | Baja | Requiere parsear H2 del markdown |
| Landing pages para keywords nuevas ("tdr que es", 258 imp) | Media | Fase 2 del plan SEO |
| Core Web Vitals no medidos | Media | PageSpeed Insights pendiente |
| OG image (`vigilante-seace-og.webp`) | Baja | Placeholder, no existe el archivo aún |
| Internal linking desde páginas principales al blog | Media | Fase 2 del plan SEO |
