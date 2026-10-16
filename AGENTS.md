# AGENTS.md — Reglas del proyecto Vigilante SEACE

> Leer SIEMPRE antes de trabajar en este repo. Son decisiones del dueño, no sugerencias.

## Reglas no negociables

1. **WhatsApp API: CONGELADA por costo. NO TOCAR.**
   - Prohibido reactivar/integrar el bot, webhooks de atención o envíos por la API.
   - Tracking de intención WhatsApp = **solo el click del botón** (`wa_clicks` en BD + `lead_whatsapp_click` en GA4).
   - Los leads de WhatsApp los atiende el **equipo manualmente** desde su app.
   - **Las alertas del producto se comunican SOLO por Telegram y correo** — PROHIBIDO prometer
     "alertas por WhatsApp" en anuncios, landings, flyers o respuestas (WhatsApp = solo canal de contacto/venta manual).
   - Ver `documents/05-tecnico-operaciones/WHATSAPP_SWITCH.md` §1b.

2. **Presupuesto de campañas: CONGELADO hasta ROI demostrado** (meta: 10x mínimo, 40x óptimo).
   No crear campañas nuevas ni subir presupuesto sin aprobación explícita.

3. **Antes de CUALQUIER cambio en campaña o landing:** correr
   `scripts/qa/funnel-audit.mjs` (10 checks por landing) y `scripts/meta-ads/verify_campaign.py` (88 checks).
   Nada se considera "aplicado" sin validación en verde.

4. **Placements Meta: SOLO Facebook + Instagram.** Audience Network = clics basura (18/18 de los clics del día 1).

5. **Cierre de ventas = Yape** (`/admin/pagos-yape`). Alertas Telegram instantáneas de lead nuevo
   y de Yape reportado; responder lead ≤2h con `/admin/respuesta-leads`.

6. **Deploys:** usar `bash deploy/orchestrate.sh deploy` (el build del frontend corre SIEMPRE desde 13/10).
   Verificar que el hash del CSS cambió cuando se toquen vistas.

7. **Bitácora obligatoria:** todo incidente, cambio de proceso o lección → `documents/05-tecnico-operaciones/BITACORA.md`
   (formato: síntoma → causa → solución → lección → archivos). Campañas/embudos → también en
   `documents/02-marketing-adquisicion/`.

8. **Nunca asumir que un canal sigue en uso:** confirmar con el dueño antes de "revivir" integraciones
   (lección del incidente WhatsApp API del 14/10).

9. **Operación autónoma con protocolo (NO preguntar qué evaluar):** el agente es el operador responsable
   del ROI. **Manual operativo diario = skill `optimizacion-diaria-vigilante`** (`.opencode/skills/`) —
   cargarlo ANTES de cualquier ritual: define las fuentes de datos propias (heatmaps/BD/Meta/Clarity/GA4),
   los umbrales de acción y las recetas de ejecución. Doctrina completa: `PROTOCOLO_OPERATIVO.md` — checklist
   diario, umbrales por estrategia, checkpoints de decisión (día 3/7/8/14/21), matriz de autonomía y
   guardrails. **Decidir dentro de la matriz** (pausar/rotar/reasignar es autónomo; presupuesto total,
   precios y campañas nuevas requieren OK). Usar los skills (`ab-testing`, `ads`, `analytics`…) como marco
   de decisión. El dueño no debe repetir qué evaluar: si algo falta, se agrega al protocolo/skill.
