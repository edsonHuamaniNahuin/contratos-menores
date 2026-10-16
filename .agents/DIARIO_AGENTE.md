# 🤖 RUNBOOK — Ritual Diario del Agente (refactorización autónoma)

> Esta es tu instrucción de ejecución cuando corres en modo diario (`opencode run`).
> Sigue los pasos EN ORDEN. No pidas confirmación para lo que está en la matriz de autonomía.
> Al final: reporte por Telegram + resumen de 3 líneas.

## PASO 0 — Contexto obligatorio (leer antes de actuar)
1. **CARGA EL SKILL `optimizacion-diaria-vigilante`** (herramienta skill) — es tu manual operativo:
   fuentes de datos propias (heatmaps/BD/Meta/Clarity/GA4), umbrales, recetas y reglas de oro.
2. `documents/02-marketing-adquisicion/PROTOCOLO_OPERATIVO.md` (tu mandato y matriz)
3. `documents/02-marketing-adquisicion/PLAN_REFACTOR_EMBUDO_15OCT.md` (plan vigente)
4. `documents/04-metricas-reportes/PANEL_DIARIO_CAMPANA.md` (estado del A/B registry)

## PASO 1 — Dossier del día
```bash
ssh -i C:\Users\PC-01\.ssh\deploy_vigilante root@161.132.4.111 "cat /var/www/vigilante-seace/storage/agent/DAILY_BRIEF.md"
```
(lo compila `agente:brief` a las 08:35; si no existe, correrlo tú vía ssh + artisan)

## PASO 2 — Auditorías (obligatorio si tocarás algo)
- `python scripts/meta-ads/verify_campaign.py --token-file /root/.meta-token` (vía ssh)
- `funnel-audit` de las landings que vayas a tocar (harness: `node browser.mjs <url> --script scripts/qa/funnel-audit.mjs`)

## PASO 3 — Análisis (patrones, no corazonadas)
Con el brief + Clarity (API con `/root/.clarity-token`) + grabaciones (dashboard del dueño):
1. ¿Qué brazo rinde? Métrica primaria: **costo por clic WA y leads**. Secundarias: costo por landing.
2. ¿Qué patrón repite? (ángulo, destino, hora, edad, texto)
3. ¿Qué guardrail se activó? (gasto >S/21, sin entrega, frecuencia >2.5, CPL >S/12)
4. **Auditar CADA componente tocado contra `REGLAS_MEJORA_CAMPANAS.md`** (copy/imagen/segmentación/
   edad/destino/form/UX/horario) — cada pieza mejora con el aprendizaje de lo exitoso.
5. **Registro obligatorio del refactor:**
   `componente → dato que lo justifica → skill citado → herramientas usadas (mín. 4) → regla aplicada → resultado esperado`

## PASO 4 — Decisiones DENTRO de la matriz (ejecuta sin preguntar)
| Si pasa esto… | Haz esto (autónomo) |
|---|---|
| Un anuncio/brazo pierde (fair-share ≥S/12 y métrica <50% de la mediana al día 7+) | Pausar; rotar creativo; reasignar presupuesto interno |
| Dead clicks >5% en una página | Fix del elemento (hacerlo clickeable o quitar affordance) |
| Quickbacks >2% | Revisar/corregir los links con retorno inmediato |
| CTR cae >30% (3 días) | Rotar creativo (usar `scripts/ad-creatives/flyer-v2.html` + variantes) |
| Lead sin atender >2h | Recordar al dueño por Telegram |
| CPL ≤ S/8 con ≥3 leads | Mantener; preparar propuesta de escala (NO ejecutar) |

## PASO 5 — PROHIBIDO (fuera de la matriz — solo PROPONER en el reporte)
- Subir presupuesto total / campañas nuevas / precios
- Tocar la WhatsApp API (congelada)
- Cambiar placements (siempre FB+IG)

## PASO 6 — Cierre de cada cambio
1. QA verde (paso 2) → `git commit` con evidencia → push → **deploy con orchestrate si tocaste código/blade**:
   `ssh … "cd /var/www/vigilante-seace && export PATH=/usr/local/bin:/usr/bin:/bin:\$PATH && bash deploy/orchestrate.sh deploy"`
2. Actualizar `PANEL_DIARIO_CAMPANA.md` (fila del día) + `BITACORA.md` (síntoma→causa→solución→lección)
3. Telegram al dueño: semáforo + 1 dato clave + 1 acción tomada + 1 propuesta (si hay)
4. Termina tu respuesta con 3 líneas: estado / acción ejecutada / siguiente.

## Regla de oro
**Cada cambio sale de un dato.** Si no hay dato que lo justifique, el cambio se propone, no se ejecuta.
