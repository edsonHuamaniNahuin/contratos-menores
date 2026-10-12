# 📘 Documentación de Campaña — Meta Ads · Vigilante SEACE

> **Versión:** 1.0 · **Fecha:** 01/10/2026 · **Autor:** equipo Vigilante SEACE
> **Cuenta publicitaria:** `act_350473826722700` (FocusSunquPacha · PEN · America/Lima)
> **Documentos relacionados:** `CONFIG_CAMPANAS_META_ADS.md` (config inicial), `EMBUDOS_3_CAMPANAS.md` (E1/E2/E3),
> `CONFIG_CAMPANAS_GOOGLE_ADS.md`, `PRONOSTICO_TRAFICO_REVENUE.md`, `../03-clientes-investigacion/voc-datos-reales.md`.

---

## 1. Resumen ejecutivo

Lanzamos las 3 campañas Meta (MA-Alertas, MA-Software, MA-Vigentes) ya parametrizadas en la cuenta, con pixel
midiendo en las landings y 6 creativos nuevos (1:1 y 4:5). Esta documentación fija: **objetivos, KPIs, público,
presupuesto, medición, reglas de decisión y material gráfico**.

Tres realidades que condicionan el plan (datos de producción al 01/10/2026):

1. **El negocio paga, pero es chico y con churn:** 3 suscripciones activas de pago (2× S/68 + 1× S/49 = **MRR S/185**),
   10 trials expirados sin convertir y 2 cancelaciones. El MRR bajó vs. septiembre por vencimientos.
2. **La captación de leads recién arranca:** `demo_leads` tiene **3 leads** (todos de `/plantillas-tdr`).
   No hay baseline de conversión landing→lead ni lead→pago.
3. **WhatsApp quedó fuera para alertas (01/10/2026):** el canal automatizado está apagado; las alertas van por
   **Telegram + correo**. Los creativos y copies ya fueron corregidos a este contexto.

**Consecuencia estratégica:** las campañas de octubre deben optimizar **leads calificados** (no ROAS directo),
con presupuesto de aprendizaje (S/ 500–900/mes), midiendo todo y corrigiendo cada semana. El escalado vendrá
cuando exista CPL estable y lead→pago medido.

---

## 2. Contexto de negocio (datos reales de producción)

Fuente: BD de producción `vigilante_seace` (consulta 01/10/2026).

| Métrica | Valor | Nota |
|---|---|---|
| Usuarios registrados | **230** (43 últimos 30 días) | Captación orgánica activa |
| Suscripciones activas de pago | **3** | 2× `mayores-premium` (S/68) + 1× `monthly` (S/49) |
| MRR activo | **S/ 185** | Solo planes de pago activos |
| Trials activos / expirados | 0 / **10** | Ningún trial activo hoy; 10 expiraron sin pagar |
| Cancelaciones | 3 (2 trial + 2 pago / solapadas) | Motivo registrado: 1 ("problemas técnicos") |
| Perfiles de empresa | 21 (20 con `company_copy`) | Base para lookalike cuando haya >100 |
| Suscripciones Telegram activas | **2** | Canal de alertas vigente |
| Suscripciones WhatsApp | 10 (canal apagado) | 1,098 envíos WSP últimos 30d → migración a Telegram/correo |
| Notificaciones 30d | Telegram 60 · WhatsApp 1,098+107 | El grueso era WhatsApp (ahora pausado) |
| Contratos mayores (DB) | **20,933** (10,322 CONVOCADO) | 5,599 publicados últimos 30d |
| Leads de landing (`demo_leads`) | **3** (última semana, `plantillas-tdr`) | Sin datos aún de E1/E2/E3 |

**Lecturas clave**
- El ICP que paga son **empresas de construcción/ingeniería** (4 de 5 pagos históricos: pavimentos, viales,
  saneamiento, eléctricas, obras civiles + maquinaria). Ver `voc-datos-reales.md` (Cluster A).
- El segmento TI/telecom **no convierte** (trials sin pago) → no crear audiencias específicas para TI todavía.
- La fuga ocurre en **trial → pago** (10 expirados). El anuncio puede traer el lead, pero el funnel debe
  arreglar la activación (primera alerta útil, onboarding). **Las campañas no escalan un funnel que gotea.**

---

## 3. Objetivos y KPIs

### 3.1 Objetivo de negocio (Q4 2026)
1. Validar el canal Meta como fuente de **leads calificados** para el ICP construcción (CPL sostenible).
2. Generar los primeros **20–40 leads** de E1/E2/E3 en 30 días (baseline de conversión).
3. Cerrar **≥3 ventas nuevas** atribuibles a Meta (directas o asistidas por email/WhatsApp de venta).
4. Bajar el lead→pago demostrando mejora de activación (acompañado de fixes de producto/onboarding).

### 3.2 KPIs por embudo (definidos en `EMBUDOS_3_CAMPANAS.md`)

| Embudo | Landing | Objetivo del click | KPI principal | KPIs secundarios |
|---|---|---|---|---|
| **E1 Alertas** | `/alertas-licitaciones` | Lead (form) + WhatsApp venta | **CPL** y % que agenda reunión | costo por reunión, lead→pago |
| **E2 Software** | `/software-licitaciones` | Reunión/demo 20 min | **CPR (costo por reunión)** | reunión→pago, CAC |
| **E3 Vigentes** | `/licitaciones-vigentes` | Lead (form) | **CPL** y progresión del lead | % leads→reunión, calidad |
| Global | — | — | **CAC**, **payback**, blended ROAS | frecuencia, reach mensual |

### 3.3 Metas numéricas al cierre de 30 días (fase de aprendizaje)

| KPI | Meta | Umbral de alarma |
|---|---|---|
| CTR (Meta, cold B2B PE) | ≥ 1.0 % (bueno 1.5 %) | < 0.8 % |
| CPM | S/ 25–45 (US$ 7–12) | > S/ 70 |
| CPC | S/ 0.8–1.8 | > S/ 2.5 |
| CPL (form) | **≤ S/ 15** | > S/ 30 |
| Tasa lead → reunión | ≥ 25 % | < 15 % |
| Tasa reunión → pago | ≥ 20 % | < 10 % |
| CAC (blended, 30d) | **≤ S/ 120** | > S/ 200 |
| Frecuencia (cold) | < 2.5 | > 3.5 |

### 3.4 TCPL (Target Cost Per Qualified Lead) — el ancla de decisiones

Cálculo desde el deal (método 1 de la skill `ads`):

- Ticket promedio ponderado: **S/ 56.3** (2×68 + 1×49 ÷ 3).
- Valor de un cliente (LTV conservador, 6 meses de permanencia): **≈ S/ 338**.
- CAC máximo saludable (33 % del LTV): **≈ S/ 110** → redondeamos a **S/ 100**.
- Si lead→pago ≈ 10 % (supuesto fase 1), **CAC = CPL / 0.10** → para CAC S/ 100 ⇒ **CPL ≤ S/ 10**.
- Con lead→pago 20 % (si mejoramos activación): CPL ≤ S/ 20.

**TCPL de trabajo = S/ 12 por lead calificado** (documentado; se recalibra a los 30 días con datos reales).

---

## 4. Público objetivo (ICP) y segmentación Meta

### 4.1 Segmentos (de `voc-datos-reales.md`)

| Cluster | Descripción | ¿Invertir? |
|---|---|---|
| **A · Construcción/ingeniería** | Pavimentos, viales urbanas, saneamiento, eléctricas (media/alta tensión), obras civiles, maquinaria pesada | **SÍ — foco principal (80 % del presupuesto)** |
| B · TI/telecom | Cableado, data center, hosting, BIM | No por ahora (trials no pagan) |
| C · Salud/laboratorio | PAP, biopsias, análisis clínicos | Secundario (creativo específico después) |
| D · Servicios varios/HSE | Consultoría HSE, facilities, limpieza | Secundario |

### 4.2 Enfoque Meta 2026 (Andromeda): **creativo = segmentación**

La audiencia se define en el **creativo** (rubro, obra, regiones), no apilando intereses. Para esta cuenta:

- **Targeting base:** Perú, 25–60 años, español. Intereses núcleo mínimos (OSCE, contrataciones del Estado,
  construcción) como **señal**, no como filtro duro.
- **Excluir:** clientes actuales (lista de emails de `users` con suscripción activa) y quienes ya enviaron lead
  (exclusión de `demo_leads` por email en los próximos 30 días).
- **Retargeting futuro** (cuando haya volumen de pixel): visitantes de landings 30–90 días con oferta distinta
  (p. ej. plantilla TDR → demo), frecuencia 3–5/semana.
- **Lookalike 1 %:** cuando `demo_leads` + pagos sumen ≥ 100 contactos (hoy 3 — no alcanza).

---

## 5. Oferta y message-match por campaña

| Campaña | Landing | Promesa central (headline) | Oferta | CTA |
|---|---|---|---|---|
| **MA-Alertas** | `/alertas-licitaciones` | "Esta semana el SEACE publicó 1,347 convocatorias. ¿Cuántas eran de tu rubro?" | Alertas por **Telegram/correo** el mismo día + demo con procesos reales | Enviar formulario / escribir por WhatsApp de venta |
| **MA-Software** | `/software-licitaciones` | "La IA lee el TDR y te dice si de verdad calzas antes de postular." | Demo 20 min con procesos reales del sector | Agendar demo (form) |
| **MA-Vigentes** | `/licitaciones-vigentes` | "10,322 procesos vigentes. ¿Cuántos calzan con tu empresa?" | Ver el feed gratis + aviso por Telegram/correo | Ver licitaciones / dejar contacto |

**Regla de message-match:** el headline del anuncio se repite textual en el H1 de la landing (prueba de
headline mirroring). Referencia: landing `alertas-licitaciones` ya usa el número real de la semana
(`$stats['semana']`); los flyers usan los mismos números que la landing.

**Nota WhatsApp:** el canal de alertas por WhatsApp está **temporalmente deshabilitado** (01/10/2026). Las
landings ya muestran la nota declarativa + modal; el soporte humano por WhatsApp (+51 918 874 873) sigue como
canal de venta.

---

## 6. Estructura de campañas en Meta

### 6.1 Estado actual — estructura v2 aplicada (01/10/2026, todo PAUSED)

La v1 (3 campañas sueltas por embudo) fue **eliminada** el 01/10/2026 y reemplazada por una campaña de
aprendizaje con 3 ad sets (ver §6.2).

| Nivel | Nombre | ID | Presupuesto | Estado |
|---|---|---|---|---|
| Campaña | `META_Traffic_Construccion_E1E2E3_2026Q4` | `120247999030430108` | ABO (sin CBO) | PAUSED |
| Ad set E1 | `META_Traffic_E1_Alertas_2026Q4` | `120247999030690108` | S/ 10/día | PAUSED |
| Ad set E2 | `META_Traffic_E2_Software_2026Q4` | `120247999032700108` | S/ 10/día | PAUSED |
| Ad set E3 | `META_Traffic_E3_Vigentes_2026Q4` | `120247999034400108` | S/ 10/día | PAUSED |
| Anuncio E1 | `MA-Alertas - 4x5` | `120247999031740108` | — | PAUSED |
| Anuncio E1 | `MA-Alertas - 1x1` | `120247999032370108` | — | PAUSED |
| Anuncio E2 | `MA-Software - 4x5` | `120247999033910108` | — | PAUSED |
| Anuncio E2 | `MA-Software - 1x1` | `120247999034210108` | — | PAUSED |
| Anuncio E3 | `MA-Vigentes - 4x5` | `120247999035080108` | — | PAUSED |
| Anuncio E3 | `MA-Vigentes - 1x1` | `120247999035650108` | — | PAUSED |

- Página: **Vigilante SEACE** (`1340988862437864`) · Pixel: **Vigilante SEACE Web** (`1029237720131593`).
- Audiencia por ad set: Perú, 25–60 · intereses núcleo (OSCE + Gobierno + rubro) · sin advantage audience.
- Reproducible: `scripts/meta-ads/refactor_campaigns_v2.py --delete-old`.

### 6.2 Estructura (aplicada)

```
Cuenta Meta (act_350473826722700)
└── META_Traffic_Construccion_E1E2E3_2026Q4        (ABO, PAUSED)
     ├── META_Traffic_E1_Alertas_2026Q4   S/10/día  → /alertas-licitaciones
     │     ├── MA-Alertas - 4x5  (ma-alertas-1350.png)
     │     └── MA-Alertas - 1x1  (ma-alertas-1080.png)
     ├── META_Traffic_E2_Software_2026Q4  S/10/día  → /software-licitaciones
     │     ├── MA-Software - 4x5
     │     └── MA-Software - 1x1
     └── META_Traffic_E3_Vigentes_2026Q4  S/10/día  → /licitaciones-vigentes
           ├── MA-Vigentes - 4x5
           └── MA-Vigentes - 1x1
```

**Naming convention** (a aplicar): `META_[Objetivo]_[Audiencia]_[Oferta]_[Fecha]` → ej.
`META_Lead_Construccion_Alertas_2026Q4`.

### 6.3 Objetivo de campaña recomendado

| Opción | Cuándo | Riesgo |
|---|---|---|
| **OUTCOME_LEADS + formulario en landing (actual, mejorado)** | Recomendado fase 1: los landings ya capturan con antibot y guardan `demo_leads` | Requiere que la landing convierta |
| OUTCOME_TRAFFIC + LINK_CLICKS (lo que está hoy) | Solo como test control de creativos | Meta no optimiza a lead |
| OUTCOME_LEADS + Lead Form nativo (Higher Intent, email corporativo, 2 preguntas) | Test en el 2.º ad set de E2/E3 | Menos control, preguntas de calidad |
| OUTCOME_SALES + conversión `Lead` del pixel | Cuando haya ≥50 leads/semana | Precipitado con el volumen actual |

**Decisión (aplicada en v2):** fase 1 = **OUTCOME_TRAFFIC + LINK_CLICKS** (los ad sets actuales) para leer
rápido creativos/landings mientras el pixel acumula sus primeros `Lead`; al alcanzar **≥ 15–20 leads** se crea
la versión `OUTCOME_LEADS + OFFSITE_CONVERSIONS (pixel Lead)` con la misma estructura (los ad sets actuales
quedan como control). A las 2 semanas evaluar formulario nativo como ad set paralelo.

### 6.4 Pujas y learning phase
- Estrategia: **LOWEST_COST_WITHOUT_CAP** (sin tope) hasta tener costo estable; luego *cost cap* ≈ 1.2× TCPL.
- No editar anuncios activos (resetea learning). Cambios = anuncios nuevos.
- Learning phase: 7 días / ~50 eventos por ad set. Con S/ 16.5/día, esperar 10–14 días antes de leer resultados.

---

## 7. Creativos (material gráfico)

### 7.1 Inventario actual (producción 01/10/2026)

| Campaña | Archivo 4:5 (1080×1350) | Archivo 1:1 (1080×1080) | Concepto | Fuente HTML |
|---|---|---|---|---|
| MA-Alertas | `public/images/ads/ma-alertas-1350.png` | `ma-alertas-1080.png` | Editorial crema + Fraunces · tarjeta de alerta Telegram con proceso real (La Esperanza, S/ 9.7M) + dato "1,347 convocatorias esta semana" | `scripts/ad-creatives/e1-alertas.html` |
| MA-Software | `ma-software-1350.png` | `ma-software-1080.png` | Panel de producto (Space Grotesk) · análisis IA + score 8.4/10 + proforma | `scripts/ad-creatives/e2-software.html` |
| MA-Vigentes | `ma-vigentes-1350.png` | `ma-vigentes-1080.png` | Feed navy/ámbar (Sora) con 3 procesos vigentes reales | `scripts/ad-creatives/e3-vigentes.html` |

Regenerar cualquiera: `node <skill browser-automation>/browser.mjs <file-url> --script scripts/ad-creatives/render.mjs` con
`RENDER_URL/RENDER_OUT/RENDER_W/RENDER_H` en el entorno. Estilos y colores están alineados a cada landing
(brand tokens E1/E2/E3) para message-match visual.

### 7.2 Reglas de producción (Andromeda: volumen > pulido)
- **1 hora semanal** de producción de creativos: 3–5 estáticos nuevos por semana.
- Jerarquía de test: **concepto → hook/headline → visual → body → CTA**.
- Estáticos primero; video/UGC solo cuando un concepto ya validó.
- Formatos: 4:5 principal (feed/Reels), 1:1 secundario; después 9:16 para Stories.
- **No editar** un anuncio con rendimiento: lanzar variantes nuevas.
- Iteración de ganador: 1) hook 2) tratamiento visual 3) formato 4) copy/CTA.
- Prohibido: texto ilegible, stock genérico, estética "ads" pulida (nativo > publicitario).

### 7.3 Copies aprobados (primary text / headline)

**E1 — MA-Alertas**
> ¿Cuántas licitaciones de tu rubro se te escapan cada mes?
> Cada día el Estado publica cientos de convocatorias en el SEACE. Las de tu empresa son pocas, aparecen de
> golpe y las ventanas para cotizar duran días.
> Vigilante SEACE te avisa por Telegram o correo el mismo día, filtrado por tu rubro y región. Sin revisar el
> portal a mano.
> Escríbenos y te mostramos el sistema con procesos reales de tu sector.

Headline: **Alertas de licitaciones por Telegram y correo** · CTA: Más información

**E2 — MA-Software**
> Deja de buscar licitaciones a mano.
> Vigilante SEACE monitorea el SEACE 24/7, filtra por tu rubro, analiza el TDR con IA y te arma la proforma en
> Word o Excel.
> Agenda una demo de 20 minutos y lo ves con procesos reales de tu empresa.

Headline: **Software de licitaciones con IA** · CTA: Más información

**E3 — MA-Vigentes**
> Hoy hay más de 10,000 procesos en convocatoria en el Perú.
> Municipios, regiones, ministerios y hospitales publican sus compras en el SEACE. ¿Cuántos calzan con tu negocio?
> Mira los procesos vigentes y recibe aviso cuando publiquen algo de tu rubro.

Headline: **Licitaciones vigentes hoy** · CTA: Más información

---

## 8. Medición y atribución

### 8.1 Pixel y eventos

- **Pixel/dataset:** `1029237720131593` ("Vigilante SEACE Web"), activo en las 4 landings (PageView) desde 01/10.
- Eventos actuales:
  - `PageView` — automático.
  - `Contact` — clic en enlaces `wa.me` (soporte/venta; perdió valor como señal de alerta al apagarse WhatsApp).
  - `Lead` — al enviar formulario (`session('ok')` en las landings; confirmado en `DemoLeadController`/`PlantillaTdrController`).
- **Pendiente de implementar (refactor):**
  1. Evento **`Lead` con `value` por embudo** (`content_category = E1|E2|E3`) — ya se envía `EMBUDO`; validar en Events Manager.
  2. Evento server-side **`SuscripcionActiva`** desde backend al confirmar pago (Conversions API ligera o endpoint
     interno) → permite optimizar a pago cuando haya volumen.
  3. **Conversión personalizada** "Lead E1/E2/E3" en Events Manager para leer por campaña.
  4. **Marcar `Lead` como conversión primaria** y asociarla a las 3 campañas.

### 8.2 UTMs (obligatorio en todos los anuncios)

```
utm_source=meta&utm_medium=paid&utm_campaign={{campaign.name}}&utm_content={{ad.name}}&utm_term={{adset.name}}
```
Con `utm_campaign` = `MA-Alertas|MA-Software|MA-Vigentes` (coincide con `demo_leads.origen`).

### 8.3 Fuentes de verdad
- **`demo_leads` (BD):** leads con `landing`, `origen` (UTM) e IP → fuente primaria para CPL y calidad.
- **GA4** (G-4PRW1QCW48, property 404642926): sesiones `cpc / meta`, embudo landing→formulario; comparar con Meta.
- **Meta Ads Manager:** CTR/CPM/CPC/frecuencia (atribución 7d click / 1d view — no sumar con GA4).
- **`subscriptions` + `pago_yapes`:** pagos; cruzar por email del lead → cliente.
- Comparar siempre **plataforma vs. BD** (la atribución de Meta se infla ~20–40 %).

### 8.4 Privacidad/consentimiento
El sitio ya implementa Consent Mode v2 y solo carga GA4/GTM en producción con consentimiento. El pixel carga
en producción; revisar que no se dispare en páginas de checkout/pago (evitar señales basura) y excluir
`/configuracion*`, `/admin*` de eventos personalizados.

---

## 9. Presupuesto, economía y proyección

### 9.1 Escenarios de inversión (fase de aprendizaje)

| Escenario | Presupuesto/mes | Por campaña/día | Leads esperados (CPL S/ 15) | Reuniones (25 %) | Pagos (20 %) | CAC estimado* |
|---|---|---|---|---|---|---|
| **Mínimo (recomendado inicio)** | S/ 500 | S/ 5.5 | 33 | 8 | 1.7 | S/ 294 |
| **Base** | S/ 900 | S/ 10 | 60 | 15 | 3 | S/ 300 |
| **Agresivo (solo si CPL ≤ S/ 12)** | S/ 1,500 | S/ 16.5 | 125 | 31 | 6 | S/ 250 |

\* CAC = presupuesto ÷ pagos. **Con el LTV actual (≈ S/ 338 a 6 meses) y CAC S/ 250–300, el payback es 4–5 meses:
lo hace viable pero exige mejorar retención y/o subir ticket.** Si el CPL sale > S/ 30, el canal no es viable
para venta directa y debe reenfocarse a **lead magnet (plantillas-TDR) → email nurturing** con costo por lead
mucho menor (meta ≤ S/ 6).

### 9.2 Break-even por plan

| Plan | Precio/mes | Meses para pagar CAC S/ 250 | Margen bruto/mes (est. 80 %) |
|---|---|---|---|
| Monthly | S/ 49 | ~6.4 meses | S/ 39.2 |
| Mayores-premium | S/ 68 | ~4.6 meses | S/ 54.4 |

**Implicancia:** priorizar campañas/creativos que atraigan **Contratos Mayores** (ticket S/ 68 y casos con más
volumen de alertas) y ofrecer *upgrade* a los leads de E3.

### 9.3 Mínimo para escalar (semáforo)
No subir presupuesto hasta tener 3 señales verdes simultáneas:
1. CTR ≥ 1.5 % y CPM estable 7 días.
2. CPL ≤ S/ 15 con ≥ 15 leads acumulados.
3. Al menos 1 pago atribuido (lead→pago medido).

Escalado: **+20 % cada 5 días** (nunca +30 %); rollback si el costo supera 1.5× TCPL tras el aumento.

---

## 10. Plan de experimentos (A/B) — 3 tests simultáneos

| # | Hipótesis | Variable | Métrica | Duración |
|---|---|---|---|---|
| T1 | El flyer 4:5 supera al 1:1 en feed | Formato del creativo | CTR + CPL | 10–14 días |
| T2 | El hook con dato duro ("1,347 convocatorias") supera al hook con pregunta | Headline/hook | CTR + lead→reunión | 10–14 días |
| T3 | El formulario de landing convierte mejor que un Lead Form nativo (Higher Intent) | Destino | CPL + tasa de calificación | 14 días |

Al ganar un test: registrar aprendizaje en `documents/04-metricas-reportes/` (qué ganó, por qué, dato) y
**mirror** del headline ganador en el H1 de la landing (esperado +15–20 % de conversión).

---

## 11. Cadencia operativa

| Día | Acción |
|---|---|
| **Día 0 (lanzamiento)** | Activar 3 campañas a S/ 10–16.5/día; verificar pixel (PageView + Lead de prueba); UTMs correctos |
| **Día 1–7 (learning)** | No tocar nada. Monitorear entrega, errores de revisión Meta, gasto |
| **Día 7 (delivery check)** | Por anuncio: gasto ≥ (presupuesto/ads)×7×0.5 → los que no, kill (cambiando hook/visual) |
| **Semanal (lunes)** | Leer CTR/CPM/CPL por anuncio (rolling 14d) + calidad de leads en BD; aplicar swap si > 1.5× TCPL |
| **Semanal (miércoles)** | Lanzar tests nuevos en slots liberados; subir creativos frescos (1 h de producción) |
| **Semanal (viernes)** | Escalado +20 % / rollback; revisar frecuencia (< 2.5 cold) |
| **Mensual** | Auditar librería de creativos, recalibrar TCPL, revisar reach neto (MoM) y MRR de la cohorte |

---

## 12. Reglas de decisión (adaptadas con números reales)

Basado en `meta-decision-system.md`, con TCPL = S/ 12:

- **Data gate:** no juzgar un anuncio con < S/ 36 de gasto (3× TCPL).
- **Delivery kill (día 7):** gasto < 50 % del fair share → kill + hook nuevo.
- **Quality kill:** ≥ S/ 36 de gasto y 0 leads → abandonar el **concepto** (no iterar).
- **Cost kill:** costo por lead > S/ 18 (1.5× TCPL) → swap (ángulo/audiencia/oferta).
- **Graduación a Scaling:** ≥ 5 leads calificados · tasa calificación ≥ 60 % · CPL ≤ S/ 12 · ≥ 14 días activo ·
  ≥ 1 lead en los últimos 7 días.
- **Fatiga (cold):** warning frecuencia 2.5; critical 4.0 · CTR −20 % en 7 días · CPM +30 % en 14 días →
  reemplazo en ≤ 7 días (pipeline de 2–3 variantes ya listas).
- **Nunca pausar sin reemplazo.**

---

## 13. Riesgos y supuestos

| Riesgo | Prob. | Impacto | Mitigación |
|---|---|---|---|
| CPL real > S/ 30 (canal no viable para venta directa) | Media | Alto | Reenfocar a lead magnet (plantillas) y nurturing; CPL objetivo ≤ 6 |
| Bajo volumen de leads para leer datos (semana 1–2) | Alta | Medio | No tocar durante learning; leer recién al día 10–14 |
| Fuga trial→pago / churn alto (10 trials expirados) | Alta | Alto | El equipo de producto/soporte debe activar el lead el día 1; el anuncio no arregla el funnel |
| Estacionalidad Q4 (+60–80 % CPM B2B) | Media | Medio | Presupuesto concentrado en las 3 semanas de test; retomar fuerte en enero |
| WhatsApp apagado resta "facilidad percibida" | Alta | Bajo | Los copies/landings ya explican Telegram/correo y la nota del cambio |
| Revisión de Meta (errores, políticas) | Baja | Medio | Los 6 anuncios ya pasaron la primera revisión (IN_PROCESS); monitorear |
| Lead de baja calidad (solo curiosos) | Media | Medio | Preguntas de calificación (RNP/experiencia), email corporativo en Lead Forms; filtro en el primer contacto |

**Supuestos documentados:** LTV 6 meses; lead→pago 10–20 %; CPL S/ 12–15; margen bruto 80 %. Todos se
recalibran con datos reales a los 30 días.

---

## 14. Anexos

### 14.1 Activos e IDs
- Ad account: `act_350473826722700` · Página: `1340988862437864` · Pixel: `1029237720131593`.
- Campañas/conjuntos/anuncios: ver §6.1.
- Script de creación: `scripts/meta-ads/create_campaigns.py` (+ `campaigns_config.json`).
- Flyers: `public/images/ads/*.png` · fuentes: `scripts/ad-creatives/*.html` · render: `render.mjs`.

### 14.2 Checklist pre-lanzamiento (refactor)
- [ ] Definir objetivo por campaña (LEADS/traffic) y aplicar nombres `META_Lead_..._2026Q4`.
- [ ] Subir los 6 flyers nuevos como creativos (4:5 + 1:1 por campaña) y actualizar los anuncios.
- [ ] Excluir clientes actuales y leads existentes de las audiencias.
- [ ] Marcar `Lead` como conversión primaria y crear conversiones personalizadas por embudo.
- [ ] Verificar UTMs en los 3 anuncios.
- [ ] Probar `Lead` real (enviar el formulario de una landing) en Events Manager.
- [ ] Registrar baseline diario (gasto, impresiones, clics, leads BD) desde el día 0.

### 14.3 Referencias
- Skills usadas: `ads` (Meta Andromeda + decision system), `ad-creative`, `analytics`, `attribution`, `ab-testing`,
  `frontend-design`, `design-taste-frontend`, `high-end-visual-design`, `brandkit`, `funnel-paid-vigilante`.
- Docs internos: `CONFIG_CAMPANAS_META_ADS.md`, `EMBUDOS_3_CAMPANAS.md`, `PRONOSTICO_TRAFICO_REVENUE.md`,
  `voc-datos-reales.md`, `WHATSAPP_SWITCH.md`.
