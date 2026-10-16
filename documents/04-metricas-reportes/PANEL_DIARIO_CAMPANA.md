# PANEL DIARIO DE CAMPAÑA — Registro A/B (fase 1)

> Fuente única para las decisiones A/B. Datos de: Meta (almacén + API), GA4, heatmap propio, `demo_leads`, `wa_clicks`, `pago_yapes`.
> Regla: cada afirmación tiene fuente; las decisiones se toman por BLOQUES (ver §10 del doc maestro), nunca por un día suelto.

---

## Evaluación diaria de estrategias (protocolo)

**Principio del dueño:** cada estrategia se evalúa después de cada día. La lectura es DIARIA; las decisiones de
cambio fuerte son por BLOQUES (día 7 / día 14) para no ensuciar los tests con apagados/encendidos.

| Estrategia | KPI diario | Dónde se lee | Decisión |
|---|---|---|---|
| Cirugía de placements (FB+IG) | % de gasto en FB/IG vs otro | Breakdown Meta | Diaria (auto) |
| CTA fijo móvil | clics en el CTA + scroll + leads | Heatmap + Meta + BD | Lectura diaria; ajuste si no hay uso en 3 días |
| Dayparting (23:00–07:00) | gasto nocturno = S/0 · volumen del día | Meta (hora) + cron log | Semanal (¿roba volumen?) |
| Ángulos creativos (V1/V2/V3) | CTR/CPC/leads por anuncio | Almacén + UTM | Bloque día 7 |
| Destino (J0 Instant Form vs landing) | CPL por destino | Meta + BD | Bloque a los 5-7 días de activar |
| Clarity (grabaciones opt-out) | # sesiones grabadas / rage clicks | Dashboard dueño + Data Export API | Semanal |
| Nurture 24h (T7) | envíos + respuestas/registros post-email | `logs/leads-seguimiento.log` + `demo_leads.seguimiento_enviado_at` | Semanal |
| Recuperación manual (leads/WA) | contactos hechos → respuestas | WhatsApp del equipo | Diaria (humano) |

---

## Registro diario

| Día | Fecha | Gasto | Impr | Clics (link) | CTR | CPM | Alc. | Freq | Landings | Scroll≥25% | WA clicks | Leads | Notas |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| 1 | lun 12/10 | S/ 13.46 | 10,654 | 290 (214) | 2.76% | 1.26 | 7,510 | 1.33 | 79 | — | n/d¹ | **0** | AN basura eliminado mediodía · form E1 roto (fix) · build stale (fix) |
| 2 | mar 13/10 | S/ 27.68² | 23,995 | 554 (434) | 2.31% | 1.15 | 15,550 | 1.54 | 192 | 23 | 2 (BD) / 5 (GA4) | **0** | 100% in-app (FB_IAB) confirmado · mejor CTR: Alertas V1 3.23% · CTA fijo desplegado 00:38 del día 3 |
| 3 | mié 14/10 | S/ 16.07 | 9,657 | 163 | 2.06% | 1.66 | 8,226 | 1.17 | 94 | 39 | 3 | **0** | CTA fijo LIVE · dayparting (pausa 23:00→07:00) · **🔴 INCIDENTE resuelto: ads optimizaban LINK_CLICKS → migrados a LANDING_PAGE_VIEWS+pixel (§ bitácora 14/10)** · reloj de aprendizaje reiniciado · 2 clics WA de U1 (Urgencia) |
| 4 | jue 15/10 | S/ 11.33 | 7,918 | 125 | 2.06% | 1.43 | 6,701 | 1.18 | 77 | 19 | 1 (org.) | **0** | T4 estable (CTR 1.82%, el más bajo del set) · único clic WA = orgánico (software) · Clarity scroll 44.48% · **0 leads de ads tras S/69.6 / 447 LPV** |
| 5 | vie 16/10 | en curso (~S/0.2 a 08:00) | — | — | — | — | — | — | — | — | — | — | **🐛 fix nurture: `seguimiento_enviado_at` fuera de `DemoLead::$fillable` → T7 reenviaba el correo a diario y la métrica del panel quedaba NULL; backfill 14/15 verificado (dry-run=0)** · checkpoint día 7 en 2 días · **🔴 BLOQUEO: token git inválido → no se puede pushear/deploy normal (§ bitácora 16/10)** |

¹ El beacon de WA clicks estuvo roto hasta el fix del 13/10 (CSRF). GA4 sí registró (5 usuarios en 2 días).
² Compensa el subgasto del día 1 → promedio real: S/ 20.57/día ≤ S/ 21 ✓ (Meta balancea la flexibilidad diaria).

**⚡ Estructura comparativa desde 14/10 07:00 (3 brazos, presupuesto intacto S/21/día):**

| Brazo | Campaña | Presupuesto | Ángulo | Estado |
|---|---|---|---|---|
| **E1** | Traffic E1 (Alertas) | S/7/día | V1/V2/V3 (alertas genérico) | corre |
| **E3** | Traffic E3 (Vigentes) | S/7/día | V1/V2/V3 (vigentes) | corre |
| **NEW U1** | **META_Urgencia_TIER_A_2026Q4** | S/7/día | **Urgencia (perdiste la obra + canal Telegram/correo)** | arranca 07:00 (dayparting) |
| ~~E2~~ | ~~Traffic E2 (Software)~~ | — | cluster que no paga | **PAUSADA** (financia U1) |

**Criterio de comparación (día 7):** landings reales por S/ gastado · clics WA por 100 landings · leads reales · CPL. El brazo ganador define la dirección de la semana 2.

## Creativos (CTR acumulado 2 días — muestra chica, se decide día 7)

| Embudo | V1 Dato | V2 Dolor | V3 Alivio | Líder provisional |
|---|---|---|---|---|
| E1 Alertas | **3.23%** | 1.96% | 2.34% | V1 |
| E2 Software | **3.02%** | 2.69% | 2.12% | V1 |
| E3 Vigentes | 2.15% | **2.14%** | 2.53% | V3 (empate técnico) |
| Gasto recibido | poco | más | más | Meta prioriza V2/V3 (reacciones) |

## Registro de tests A/B (bloques)

| # | Test | Estado | Runs | Decisión |
|---|---|---|---|---|
| T1 | **Ángulos creativos** (Dato/Dolor/Alivio × E1/E2/E3) | RUNNING | día 1 → | **Día 7 (18/10)**: pausar el peor ángulo por embudo (gasto ≥50% fair-share + CTR + leads) |
| T2 | **Destino**: landing vs **Instant Form** (E3) | READY (J0; ToS pend.) | arranca 14/10 (swap 08:00) | +5-7 días: CPL nativo vs CPL web |
| T3 | **Captura**: "agenda reunión" vs "valor inmediato" (J1) | PROPUESTO | — | al implementar (E3 como primer test) |
| T4 | **Copy + creativos nuevos** (mockup, nativo) | READY | entran lun 19/10 | Día 14: reasignación por embudo (S/13/S/5/S/3 al mejor) |
| T5 | Segmentación: Config A (intereses) | LIVE | día 1 → | Semana 2: config B (broad) |
| T6 | Segmentación: Config C (Advantage+) | PLAN | Semana 3 | según señal T5 |
| T7 | **Nurture automático 24-72h** (email a leads sin registro) | LIVE | diario 10:30 | Semanal: % de respuestas/registros post-email |

## Reglas del panel
1. Se actualiza **a diario** (yo o el monitor): fila nueva + notas.
2. Las decisiones A/B **solo** en los hitos de bloque (día 7, día 14) — nunca por un día suelto.
3. Toda "victoria" de un test se valida contra `pago_yapes` (lo único que cuenta como retorno).
4. El fair-share: cada anuncio necesita ≥ S/12 de gasto acumulado para poder juzgarse (con S/7/día por ad set y 3 anuncios).
