# ⏳ PENDIENTES TÉCNICOS — Vigilante SEACE

> Lista canónica de pendientes técnicos. Cada entrada: evidencia → hipótesis → verificación → estado.
> Los incidentes resueltos viven en `BITACORA.md`. Los de marketing/embudo en `02-marketing-adquisicion/BACKLOG_OPTIMIZACION.md`.

---

## 🔴 P-001 · Notificador de convocatorias degradado (ALTA — afecta producto y usuarios)

**Detectado:** 14/10/2026 (investigación de target caliente).

**Evidencia (BD producción):**
- `notified_processes` por mes de publicación: Ago **1,225** · Sep **1,653** → Oct **53** (al 13/10).
- Inserciones por `created_at` (últimos 10 días): 13/10=7 · 12/10=19 · 10/10=2 · 09/10=1 · 08/10=2 · 07/10=3 · 06/10=2 · 05/10=4 → **1-19/día vs ~55/día en septiembre**.
- 239 filas con formato de `fecha_publicacion` anómalo (con segundos: `13/10/2026 11:43:24` vs estándar `12/10/2026 18:27`).

**Hipótesis (en orden de probabilidad):**
1. Scraper/notificador caído o degradado (limitación de fuente SEACE, captcha, timeout, scheduler detenido).
2. Caída en cascada de suscriptores activos (3 active) → menos keywords → menos matches (no explica del todo: -87%).
3. Cambio de formato/fuente en SEACE rompió el parser parcialmente (las 239 filas anómalas sugieren 2 rutas de ingesta).

**Verificación (pasos):**
1. Revisar logs del job de scraping/notificación (cron/schedule en servidor) — últimas ejecuciones y errores.
2. Ejecutar el capturador manualmente y ver cuántos procesos trae de SEACE hoy.
3. Comparar: procesos publicados hoy en SEACE vs capturados por el sistema.
4. Revisar las 239 filas anómalas (¿qué ruta las insertó?).

**Impacto si se confirma:** los usuarios reciben menos alertas que la promesa del producto (retención + churn: 15 expired) y las cifras internas (buscador, informes) subestiman el mercado.

**Estado:** ⏳ PENDIENTE (dueño pidió dejarlo documentado y continuar con investigación de target).

**Referencias:** `documents/03-clientes-investigacion/INVESTIGACION_TARGET_CALIENTE_2026-10.md` §2 (anomalía) · tablas `notified_processes`, `contratos`.
