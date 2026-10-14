# Protocolo Operativo de Optimización — Mandato y Rutinas del Agente

> **Este documento existe para que el dueño NUNCA tenga que repetir qué evaluar.**
> El agente es el operador responsable del ROI: decide dentro de su matriz de autonomía,
> usa los skills como marco de decisión y solo escala lo que requiere mano humana.
>
> Se lee SIEMPRE junto con: `PANEL_DIARIO_CAMPANA.md` (registro), `OBJETIVO_ROI_CAMPANAS.md` (metas),
> `BACKLOG_OPTIMIZACION.md` (cola de mejoras) y `documents/05-tecnico-operaciones/BITACORA.md` (incidentes).

---

## 1. Mandato

- El agente **decide** — no pregunta lo que ya está documentado aquí.
- Los skills (`ab-testing`, `ads`, `analytics`, `page-cro`, `form-cro`, `marketing-psychology`) son el
  **marco de decisión obligatorio**: cada llamada (pausar, rotar, escalar, reescribir) se justifica con
  un principio del skill correspondiente y se cita en el panel.
- Solo se escala al dueño: ToS/bloqueos de plataforma, presupuesto total, precios/oferta, acciones humanas
  (llamadas, WhatsApp manual, testimonios).
- Presupuesto congelado: **S/21/día · S/147/semana · S/642/mes**. Reasignación interna (entre campañas/adsets)
  es decisión autónoma; subir el total NO (requiere aprobación).

## 2. Ritual diario de evaluación (cada mañana, sin que nadie lo pida)

Checklist fijo, en orden:

1. **Auditorías técnicas** — `scripts/qa/funnel-audit.mjs` (10 checks) + `scripts/meta-ads/verify_campaign.py`
   (88 checks). Si algo está rojo → arreglar o incidente en BITACORA. Nada se reporta "OK" sin esto.
2. **Almacén y cierre de ayer** — leer `meta_insights_diarios` (cierre 08:15) + confirmar que llegó el
   resumen Telegram de 21:30. Cada centavo del día anterior debe estar en el almacén.
3. **Guardrails (esto SÍ se vigila a diario)** — ver §6. Si un guardrail truena: acción inmediata, sin esperar checkpoints.
4. **Embudo del día por anuncio** — impresiones → clics → landings → scroll≥25% → envíos form → clics WA → leads,
   todo con UTM/UTM-content. Comparar contra la mediana del set (no contra cero).
5. **Capa humana** — leads nuevos sin respuesta (>2h = alertar equipo), Yapes pendientes (>2h = alertar al dueño),
   contactos manuales de WhatsApp (preguntar al equipo si respondieron los recuperados).
6. **Actualizar `PANEL_DIARIO_CAMPANA.md`** — fila del día: números + lectura (1 párrafo) + bandera (🔴🟡🟢).
7. **Incidentes → BITACORA** (síntoma → causa → solución → lección → archivos).

**Regla anti-peeking (fundamento: skill `ab-testing`):** la lectura diaria NO cambia anuncios por "va perdiendo".
Con volúmenes B2B pequeños, parar tests temprano por mirar el marcador genera falsos positivos. Lo diario
mueve SOLO guardrails/bugs (§6); lo estratégico se decide en checkpoints pre-comprometidos (§4).

## 3. Qué evaluar por estrategia (tabla fija — no se pregunta)

| Estrategia | KPI primario | Sano | Si falla |
|---|---|---|---|
| Placements FB+IG | % gasto fuera de FB/IG | 0% | Pausar placement ya (bug/config) |
| CTA fijo móvil | clics CTA + scroll + leads | ≥1 clic CTA/día | Ajuste visual si 0 uso en 3 días |
| Dayparting 23–07 | gasto nocturno | S/0 | Revisar cron + reactivar manual |
| Ángulo creativo (V1/V2/V3) | CTR + scroll + envíos por anuncio | CTR ≥ mediana | Checkpoint día 7: pausa del peor |
| Destino (J0 vs landing) | CPL por destino | CPL ≤ S/10 | Checkpoint 5-7 días del test |
| Precio/oferta garantía | conversión registro→pago | ≥1 pago/semana | Revisar objeción en grabaciones |
| Clarity | sesiones grabadas + rage clicks | sesiones >0 y rage =0 | Fix de la sección con rage |
| Nurture 24h | envíos + registros post-email | 1 registro/10 envíos | Reescribir asunto/CTA |
| Recuperación manual (leads/WA/Yape) | contactos hechos → respuestas | ≥50% respuesta | Cambiar guion (RESPUESTA_LEADS) |
| Anuncio individual | gasto acumulado, CTR, CPC | fair-share ≥S/12 | Ver §4 |
| Landing (funnel interno) | visitas → scroll → envío | scroll ≥15% | Fix sección (heatmap + Clarity) |
| Facturación/facturación | gasto diario registrado | % conciliación | Revisar con API |

## 4. Checkpoints de decisión (pre-comprometidos — no se decide "cuando pinte")

| Cuándo | Qué se decide | Regla de llamada |
|---|---|---|
| **Día 3 de campaña** | Triage de desastres únicamente | Pausar solo si gasto ≥S/12 Y CTR <1% Y 0 scrolls (desastre probado) |
| **Día 7 (bloque 1)** — 18/10 | Ganador/empate/pausa por anuncio + ángulo | Candidato a pausa: métrica primaria <50% de la mediana del set Y gasto ≥S/12 Y ≥25 clics relativos. Empate → mantener hasta día 14 |
| **Día 8** — 19/10 | Rotación de creativos/copy nuevos (ya construidos) | Aplica si hay fatiga (CTR cae >30% vs semana 1) o pausas del día 7 |
| **Día 14 (bloque 2)** — 25/10 | Llamada por ángulo + reasignación interna + T5→Config B | Misma regla; mover presupuesto al ganador |
| **Día 21** — 01/11 | J0 vs landing (CPL) + Config C | Menor CPL con n≥30 clics gana |
| **Checkpoint propio de J0** | 5-7 días tras activarse | Igual que día 7 |
| **Lunes (ROI semanal)** | Keep/kill de jugadas + matemática ROI | ROAS <1x → arreglar funnel antes de escalar; 5-10x → +20% semanal (proponer); ≥10x 2 semanas → proponer escalado |

**Formato obligatorio de cada llamada (skill ab-testing):**
`Porque [observación] creemos [cambio] causará [efecto] para [audiencia]. Lo sabremos con [métricas].`
Registrar en el panel: hipótesis, muestra, resultado, decisión, patrón aprendido.

## 5. Matriz de autonomía

**Decido solo (sin preguntar):**
- Pausar/rotar anuncios y activar/pausar adsets en checkpoints.
- Dayparting, creativos nuevos, copy de anuncios, tests A/B con presupuesto pequeño (T1-T7).
- Fixes de landing/formularios con `funnel-audit` + `verify_campaign` en verde.
- Reasignar el presupuesto DENTRO del sobre congelado (ej. E3→J0).
- Nurture, emails automáticos, alertas, dashboards, heatmaps.

**Requiere OK explícito del dueño:**
- Subir el presupuesto total o crear campañas nuevas.
- Cambiar precios, oferta o garantía.
- Cualquier gasto extra (herramientas, APIs).

**Prohibido (reglas de AGENTS.md):**
- Reactivar la WhatsApp API (congelada).
- Placements distintos a FB+IG.
- Lanzar algo sin auditorías verdes.
- Afirmar "conversaciones" desde clics (lección 14/10).

## 6. Disparadores de acción inmediata (guardrails)

| Condición | Acción | Urgencia |
|---|---|---|
| Campaña activa sin entrega ≥11h (horario activo) | Revisar estado/distribución; activar manual si el cron falló | 1h |
| Gasto diario >S/24 o semanal >S/147 | Pausar hasta conciliar | inmediata |
| Frecuencia >2.5 (7 días) | Rotar creativo | mismo día |
| Gasto nocturno >S/0.5 (23:00-07:00) | Revisar dayparting | al detectar |
| Lead sin respuesta >2h | Alertar al equipo (Telegram ya lo hace) + registrar | inmediata |
| Yape pendiente >2h | Alertar al dueño (Telegram ya lo hace) | inmediata |
| Pixel/landing caída o error de facturación | Pausa de emergencia + fix + BITACORA | inmediata |
| Rage clicks / abandono form (heatmap/Clarity) | Fix de la sección esa semana | 48h |
| CTR global cae >40% (3 días) | Diagnóstico fatiga/competencia → rotar creativos | 48h |

## 7. Registro y aprendizaje

- Cada checkpoint/llamada → fila del panel + entrada de patrón reusable (formato "Playbook" del skill).
- Incidentes → BITACORA (5 campos).
- Al final de cada mes: velocidad de experimentos (meta 4-8/mes), win rate (20-30% sano), backlog re-priorizado por ICE.

## 8. Cómo se reporta al dueño (para no ahogarlo)

- **Diario (mañana):** 3 líneas — semáforo + 1 dato clave + 1 acción/necesidad humana (si no hay necesidad, no se le solicita nada).
- **Semanal (lunes):** resumen ROI con **decisiones tomadas**, no consultas. Solo se pide OK en lo de la matriz §5.
- **Regla de oro:** si el dueño tiene que recordar qué evaluar, es un fallo del protocolo →
  se actualiza este documento (y nunca más se pregunta).
