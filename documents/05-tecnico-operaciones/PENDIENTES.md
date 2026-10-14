# ⏳ PENDIENTES TÉCNICOS — Vigilante SEACE

> Lista canónica de pendientes técnicos. Cada entrada: evidencia → hipótesis → verificación → estado.
> Los incidentes resueltos viven en `BITACORA.md`. Los de marketing/embudo en `02-marketing-adquisicion/BACKLOG_OPTIMIZACION.md`.

---

## ✅ P-001 · Notificador "degradado" — RESUELTO: NO ERA BUG (explicado con evidencia)

**Detectado:** 14/10/2026 (investigación de target caliente). **Resuelto:** 14/10 09:00.

**Veredicto:** el sistema está SANO. La caída de volumen (oct: 53 registros vs sep: 1,653) se explica por **dos causas conocidas y deliberadas:**

1. **WhatsApp apagado el 01/10** (regla de costos): era el **97% del volumen de envíos** — evidencia: septiembre `notification_sends` = WhatsApp 1,695 + WhatsApp-adjuntos 107 + Telegram 53; octubre = **solo Telegram 70** (≈5/día ✓ consistente con la caída observada).
2. **Suscripciones activas: 8-10 (ago/sep) → 3 (oct)**: menos destinatarios = menos filas en `notified_processes` (que cuenta proceso×destinatario).

**Evidencia de salud del pipeline (verificado hoy):**
- Scraper corrió 13/10 21:05: `success=true`, **260 procesos** capturados (+15 docs TDR).
- Todos los jobs en `schedule:list` corriendo (TDR 2h, contratos 2h/3h, emails 2h, scraper 12/21h).
- `laravel.log`: SIN errores de estos jobs.
- Alertas de suscripción (correo) hoy 08:00: "✅ 1 alerta enviada" — funcionando.
- Las "239 filas con formato anómalo": son de rutas de ingesta distintas (IDs numéricos = pipeline contratos menores; `ocds:`/`tdr:` = mayores/TDR) — diferencia de formato, no corrupción.

**Lección (a la bitácora):** cuando el volumen de un sistema cae justo después de apagar un canal/fuente, **segmentar por canal y destinatario ANTES de asumir degradación** — 97% del "bug" era el canal apagado + churn.

**Palanca real para recuperar volumen:** adquisición (más suscriptores activos recibiendo Telegram/correo) — exactamente lo que las campañas están persiguiendo.

---

## 🟡 P-002 · Pixel: eventos Contact/Lead no aparecen en insights de Meta (MEDIA)

**Detectado:** 14/10 (reporte 12-14 oct). El pixel dispara PageView (el LPV optimiza bien ✓) pero en las `actions` de insights NO aparece `Contact` (clic WhatsApp) ni `Lead` (envío form), aunque nuestra BD registra los clics reales.

**Hipótesis:** (1) los eventos `fbq('track','Contact')`/`Lead` no se están disparando en producción (condición `session('ok')` / listener wa.me); (2) nombre de evento incorrecto; (3) deduplicación/naming en insights.

**Verificación:** abrir Events Manager → Test Events con un clic real de WhatsApp y un envío de form; comparar con el código de `partials/meta-pixel.blade.php`.

**Impacto:** sin `Contact`/`Lead` en Meta no se puede optimizar hacia esas señales (campañas futuras) ni medir intención del lado de Meta.

**Estado:** ⏳ PENDIENTE.
