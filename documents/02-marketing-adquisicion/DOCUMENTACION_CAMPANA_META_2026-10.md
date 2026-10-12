# Documentación de Campaña — Meta Ads · Vigilante SEACE

> **Versión:** 1.2 · **Fecha:** 11/10/2026 · **Presupuesto real:** **S/ 150/semana (S/ 21/día)**
> 📎 El detalle técnico exhaustivo (cronología, prompts de IA, IDs, payloads, scripts y lecciones) está en
> `ANEXO_TECNICO_CAMPANA_META.md`.
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
| CPL (form) | meta de escalado **≤ S/ 15** · realista S/ 20–40 con S/ 150/semana | > S/ 30 sostenido |
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

### 4.3 Sectores y geografía según datos reales de producción (11/10/2026)

Universo de contratos mayores en BD (`contratos_mayores`) y clientes reales (`subscriber_profile_keyword`):

**Por objeto (vigentes CONVOCADO):**

| Objeto | Vigentes | Monto promedio | Lectura para campañas |
|---|---|---|---|
| Servicio | 4,200 | S/ 318k | Alto volumen, ticket accesible — **avatar "servicios/mantenimiento"** |
| Bien | 4,131 | S/ 125k | Volumen alto, ticket bajo — menos prioritario |
| **Obra** | 1,582 | **S/ 6.0M** | Menos procesos pero el nicho que paga (Cluster A) — **foco de E2/E3** |
| Consultoría de obra | 409 | S/ 415k | Nicho secundario (expedientes, supervisión) |

**Por geografía (top):** Lima 1,727 · Cusco 483 · Áncash 358 · Puno 334 · Arequipa 324 · Apurímac 261 ·
Junín 260 · La Libertad 252 · Cajamarca 230 · Piura 168 (4,385 sin depto. asignado en scraping).

**Keywords declaradas por los clientes reales (top):** Obras civiles (5) · Servicio de mantenimiento (4) ·
Tecnología de la información (3) · **Infraestructura (3)** · Movimiento de tierras (2) · Estructuras (2) ·
Servicios logísticos (2) · Limpieza integral · SySO · BIM/edificaciones sanitarias (1 c/u).

**Configuración derivada:**
1. **Dos avatares creativos** (no dos campañas): (a) **obra/infraestructura** — "obras civiles, movimiento de
   tierras, pistas, saneamiento, estructuras" (el que paga); (b) **servicios/mantenimiento** — "servicio de
   mantenimiento, limpieza integral, logística" (volumen alto). El lenguaje de los creativos usa estas palabras.
2. **Geografía:** Perú completo; los creativos mencionan región ("filtrado por tu rubro y región") para abrir
   Cusco/Puno/Áncash/Arequipa sin segmentar geo (Andromeda encuentra el clúster).
3. **Intereses base** (señal, no filtro duro): OSCE + contrataciones del Estado + construcción. Sin apilar más
   (el apilado perjudica en 2026).
4. **No atacar TI** con creativo específico hasta tener una conversión real de ese clúster.

### 4.4 Lo que dice GA4 (últimos 28 días, 11/10/2026) — ajustes a la configuración

Fuente: GA4 `properties/404642926` (script `scripts/ga4-mcp-server.py` + queries propias).

**Tráfico:** 335 usuarios · 625 sesiones · 4,433 páginas vistas · duración media **6m 34s** · rebote **32 %**.
**Fuentes:** Google orgánico 161 u · directo 76 u · **Bing orgánico 60 u** · **ChatGPT (ai-assistant) 18 u** · Yahoo 17 u.
Todo orgánico: **no hay tráfico pago aún** (baseline limpio para medir Meta).
**Dispositivos:** **82 % desktop** (275 u) · 18 % mobile (60 u) · tablet 1 → la audiencia investiga desde la oficina, no desde el celular.
**Geografía:** Lima 191 u (57 %) · La Libertad 20 · Lambayeque 14 · Arequipa 11 · Junín 10 · Cusco 9 · Cajamarca 8 ·
Callao 7 · Ica 6 · Áncash 3 · Puno 3. Ciudades: Lima, Trujillo, Chiclayo, Cusco, Arequipa, Huancayo.
**Engagement por landing:** `/buscador-publico` 92 % (194 sesiones) · `/plantillas-tdr` 54 % (28) ·
`/licitaciones-vigentes` 56 % (9) · `/alertas-licitaciones` 33 % (3, landing nueva) · `/planes` 62 % (8).

**Implicancias aplicadas a la campaña:**
1. **Geo:** mantener Perú completo (los 3 adsets) — Lima concentra la demanda pero las regiones (norte/sur) ya generan
   sesiones; los creativos siguen con lenguaje "tu rubro y región".
2. **Desktop-first:** los leads B2B revisan en escritorio → no sobre-indexar mobile; creativos 4:5/1:1 sirven feed
   móvil y desktop; la landing debe estar impecable en desktop (ya lo está).
3. **Bing + ChatGPT traen 23 % del tráfico orgánico** → confirmar que el mensaje del anuncio esté alineado con la
   promesa que buscan ("licitaciones del Estado", "oportunidades") y que el pixel capture también esos aterrizajes.
4. **Conversión verificada (riesgo cerrado):** los 157 `form_submit` de GA4 pertenecen a los formularios de auth
   (`/login` 72 · `/register` 47 · `/email/verify` 17 · `/forgot-password` 6), no a las landings. Prueba end-to-end
   del formulario de `/software-licitaciones` en producción (headless, 01/10/2026): captcha resuelto, lead creado en
   `demo_leads` y log `DemoLead: solicitud registrada` ✔. `/plantillas-tdr` convierte 3 submits → 3 leads reales.
   No hay fricción: el formulario de pago está listo para recibir tráfico.
5. **Lead magnet validado como funnel:** `/plantillas-tdr` ya recibe sesiones orgánicas con 54 % de engagement → es
   candidato a 4.º embudo cuando se permita crear campañas nuevas (respetar el cap de 3 hasta cerrar la fase de datos).

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

### 6.1 Estado actual — estructura v2 aplicada; arranque programado lunes 12/10 08:00 (11/10/2026)

La v1 (3 campañas sueltas por embudo) fue **eliminada** y reemplazada por una campaña de aprendizaje con 3 ad sets
(ver §6.2). Al cierre del domingo 11/10: los **9 anuncios quedaron ACTIVE** (revisión Meta OK), los 3 ad sets ACTIVE,
y la **campaña quedó PAUSED a propósito** tras detectar que se había activado fuera de horario (domingo 23:00).
Antes de entregar gasto se pausó → **S/ 0 gastado**. El arranque real está programado para el **lunes 12/10 08:00
(America/Lima)** con `scripts/meta-ads/toggle_campaign.py --activate` vía timer del servidor
(`meta-activar-lunes.timer`). Si se necesita cancelar: `systemctl stop meta-activar-lunes.timer` (ver §12 pendientes:
dayparting por API exige presupuesto lifetime).

| Nivel | Nombre | ID | Presupuesto | Estado |
|---|---|---|---|---|
| Campaña | `META_Traffic_Construccion_E1E2E3_2026Q4` | `120247999030430108` | ABO (sin CBO) | PAUSED → arranca lunes 08:00 |
| Ad set E1 | `META_Traffic_E1_Alertas_2026Q4` | `120247999030690108` | **S/ 7/día** | ACTIVE |
| Ad set E2 | `META_Traffic_E2_Software_2026Q4` | `120247999032700108` | **S/ 7/día** | ACTIVE |
| Ad set E3 | `META_Traffic_E3_Vigentes_2026Q4` | `120247999034400108` | **S/ 7/día** | ACTIVE |
| Anuncio E1 | `MA-Alertas - V1 Dato` | `120247999155660108` | — | ACTIVE |
| Anuncio E1 | `MA-Alertas - V2 Dolor` | `120247999156360108` | — | ACTIVE |
| Anuncio E1 | `MA-Alertas - V3 Alivio` | `120247999157100108` | — | ACTIVE |
| Anuncio E2 | `MA-Software - V1 Dato` | `120247999157490108` | — | ACTIVE |
| Anuncio E2 | `MA-Software - V2 Dolor` | `120247999158440108` | — | ACTIVE |
| Anuncio E2 | `MA-Software - V3 Alivio` | `120247999159430108` | — | ACTIVE |
| Anuncio E3 | `MA-Vigentes - V1 Dato` | `120247999160020108` | — | ACTIVE |
| Anuncio E3 | `MA-Vigentes - V2 Dolor` | `120247999161460108` | — | ACTIVE |
| Anuncio E3 | `MA-Vigentes - V3 Alivio` | `120247999162570108` | — | ACTIVE |

- Página: **Vigilante SEACE** (`1340988862437864`) · Pixel: **Vigilante SEACE Web** (`1029237720131593`).
- Audiencia por ad set: Perú, 25–60 · intereses núcleo (OSCE + Gobierno + rubro) · sin advantage audience.
  Exclusiones activas: clientes (`120247999226360108`) y leads web (`120247999226840108`).
- Reproducible: `scripts/meta-ads/refactor_campaigns_v2.py --delete-old` (estructura) y
  `scripts/meta-ads/configure_v2.py --activate` (anuncios/UTMs).

### 6.2 Estructura (aplicada)

```
Cuenta Meta (act_350473826722700)
└── META_Traffic_Construccion_E1E2E3_2026Q4        (ABO, PAUSED hasta lunes 12 08:00 · S/21/día total)
     ├── META_Traffic_E1_Alertas_2026Q4   S/7/día  → /alertas-licitaciones
     │     ├── MA-Alertas - V1 Dato    (ma-alertas-1350.png)
     │     ├── MA-Alertas - V2 Dolor   (e1-v2-1350.png)
     │     └── MA-Alertas - V3 Alivio  (e1c-v3-1350.png)
     ├── META_Traffic_E2_Software_2026Q4  S/7/día  → /software-licitaciones
     │     ├── MA-Software - V1 Dato   (ma-software-1350.png)
     │     ├── MA-Software - V2 Dolor  (e2-v2-1350.png)
     │     └── MA-Software - V3 Alivio (e2c-v3-1350.png)
     └── META_Traffic_E3_Vigentes_2026Q4  S/7/día  → /licitaciones-vigentes
           ├── MA-Vigentes - V1 Dato   (ma-vigentes-1350.png)
           ├── MA-Vigentes - V2 Dolor  (e3-v2-1350.png)
           └── MA-Vigentes - V3 Alivio (e3c-v3-1350.png)
```

**Presupuesto aprobado: S/ 50 por campaña a la semana → S/ 150/semana total (≈ S/ 642/mes).**
No se crean más campañas hasta tener datos de esta fase (regla acordada).

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

### 6.5 Estrategias de configuración (A/B/C — máximo 3)

Solo una estrategia de segmentación a la vez; el resto de variables (creativos, presupuesto) se mantienen iguales
para que el dato sea limpio.

| Config | Segmentación | Cuándo usarla | Hipótesis |
|---|---|---|---|
| **A · Intereses núcleo** (actual) | Perú · 25–60 · intereses OSCE + Gobierno + rubro | Semana 1 | El filtro de intereses acelera la entrega al ICP |
| **B · Broad** (sin intereses) | Perú · 25–60 · solo país/edad | Semana 2 (mismos creativos que A) | Andromeda: el creativo hace el targeting; CPM más barato y mayor reach |
| **C · Advantage+** | Broad + `advantage_audience` activado | Semana 3 (si A/B dieron señal) | El algoritmo encuentra mejor al ICP que los intereses |

**Rotación:** cada estrategia se prueba **una semana** con los mismos creativos ganadores; se compara CPM, CTR y
CPL. Se adopta la ganadora y se pasa a la fase de escalado. Prohibido correr más de 3 estrategias o mezclar
cambios de audiencia y creativo en la misma semana.

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

**Variantes B (hook alternativo, listas para el A/B de la semana 2):**
`ma-alertas-1350-b.png` (dolor: "El lunes se publicó una licitación de tu rubro. Te enteraste el viernes.") ·
`ma-software-1350-b.png` ("El TDR que te descalifica está en la página 34. La IA lo lee por ti.") ·
`ma-vigentes-1350-b.png` ("Tu próximo contrato con el Estado ya está publicado. Está en el SEACE.").
Fuentes: `scripts/ad-creatives/*-b.html`. El A/B es **A (dato/pregunta) vs B (dolor/urgencia)** sobre el mismo diseño.

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

### 7.4 Matriz A/B de creativos — 3 versiones por embudo (máximo)

Cada embudo tiene exactamente 3 versiones (hook distinto, mismo sistema de diseño). El A/B se lee por
**CTR + leads por UTM**, no por gusto.

| Embudo | V1 · Dato (racional) | V2 · Dolor (miedo/FOMO) | V3 · Alivio (beneficio) |
|---|---|---|---|
| **E1 Alertas** | "Esta semana el SEACE publicó 1,347 convocatorias…" (tarjeta Telegram) | "Mientras lees esto, tu competencia ya está cotizando esa licitación." (contratista frustrado) | "Deja de revisar el SEACE todos los días." (tranquilidad) |
| **E2 Software** | "La IA lee el TDR y te dice si de verdad calzas…" (panel producto) | "El requisito que te descalifica está en la página 34." (TDR circulado) | "Recupera tus noches. La IA lee el TDR por ti." (alivio nocturno) |
| **E3 Vigentes** | "10,322 procesos vigentes. ¿Cuántos calzan…?" (feed navy) | "Tu próxima obra ya está publicada en el SEACE." (amanecer en obra) | "El Estado compra lo que tú vendes. Todos los días." (servicios/almacén) |

**Archivos por versión (4:5 `-1350` y 1:1 `-1080`):**
- V1: `ma-alertas-*.png` · `ma-software-*.png` · `ma-vigentes-*.png` (template HTML original).
- V2: `e1-v2-*.png` · `e2-v2-*.png` · `e3-v2-*.png` (foto AI + hook dolor).
- V3: `e1c-v3-*.png` · `e2c-v3-*.png` · `e3c-v3-*.png` (foto AI + hook alivio).
- Héroes AI: `public/images/ads/ai/hero-e{1,2,3}*.png` · template: `scripts/ad-creatives/flyer-v2.html` (configs `e1/e2/e3` = V2 · `e1c/e2c/e3c` = V3).

**Regla de cap:** máximo 3 versiones activas por embudo. Una nueva versión reemplaza a la peor (nunca se corre una cuarta).

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

### 9.1 Presupuesto real: S/ 150/semana (S/ 21/día)

| Semana | Distribución | Objetivo de la semana |
|---|---|---|
| **S1 (días 1–7)** | E1 S/7 · E2 S/7 · E3 S/7 | Primeros datos: entrega, CTR, CPC por anuncio (A/B de formato 4:5 vs 1:1) |
| **S2 (días 8–14)** | Ganador S/13 · 2.º S/5 · 3.º S/3 | Reasignar al mejor embudo + subir variantes B (A/B de hook) |
| **S3+** | 80 % ganador · 20 % slot de test | Escalar solo con el semáforo de §9.3 en verde |

**Rendimiento esperado con S/ 21/día** (benchmarks B2B Meta Perú: CPM ≈ S/ 30, CTR ≈ 1.2 %, conversión landing 6 %):

| Métrica | Semana | Mes (~4.3 sem) |
|---|---|---|
| Impresiones | ~4,900 | ~21,000 |
| Clics | ~59 | ~250 |
| Leads (CVR 6 %) | ~3–4 | ~15 |
| Costo por lead implícito | ~S/ 40 | ~S/ 42 |

⚠️ Con S/ 150/semana el volumen es de **aprendizaje y lectura de creativos**, no de cierre de ventas: alcanza
para saber qué embudo/hook responde y decidir dónde poner más. Si el CPL se mantiene > S/ 30, el canal se
reenfoca a **lead magnet** (`/plantillas-tdr`, meta CPL ≤ S/ 6) o se pausa. No se crean campañas nuevas hasta
cerrar la semana 2.

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

## 10. Estrategia A/B por días (fase 1)

**Principio:** el A/B se lee por **bloques de días con monitoreo diario**. Alternar creativos día por día (par A /
impar B) **reinicia el aprendizaje de Meta** y ensucia los datos; por eso: cada fila es un control diario y cada
bloque una decisión.

**Semana 1 — A/B de formato (4:5 vs 1:1) en los 3 embudos**

| Día | Qué corre | Qué se lee | Decisión del día |
|---|---|---|---|
| 1 | E1+E2+E3 · 2 anuncios c/u · S/7/día por ad set | Entrega y estado de revisión Meta; gasto real | Ninguna (no tocar) |
| 2 | Igual | Impresiones/alcance, CPM | Si un anuncio no entrega → revisar/reemplazar |
| 3 | Igual | Primer CTR por anuncio y formato | Anotar (muestra chica, no decidir) |
| 4 | Igual | CTR/CPC acumulado; clics | Anotar |
| 5 | Igual | CTR/CPC; primeros leads BD (por UTM) | Anotar; formato líder por ad set |
| 6 | Igual | Frecuencia (< 1.5) | Anotar |
| **7** | Igual | **Fair-share (gasto ≥ 50 % esperado) + CTR + leads** | **Decisión bloque 1:** pausar el formato perdedor de cada ad set |

**Semana 2 — A/B de hook (A = dato/pregunta · B = dolor/urgencia)**

| Día | Qué corre | Qué se lee | Decisión |
|---|---|---|---|
| 8 | Ganadores S1 + variantes B (3 anuncios nuevos) | Aprobación Meta de los B | Activar B junto a A en cada ad set |
| 9–13 | A vs B por ad set | CTR/CPC/leads por hook (UTM) | Anotar diario |
| **14** | — | **CTR/CPC/leads/CPL por ad set y por hook** | **Decisión bloque 2:** reasignar presupuesto (S2: S/13/S/5/S/3) al mejor embudo; retirar el hook perdedor |

**Semana 3+ — consolidación:** 80 % al ganador + 20 % a un slot de test (nuevas variantes de hook/visual).
Lectura semanal con reglas de §12.

**Panel diario (una hoja):** entrega · impresiones · CPM · CTR · CPC · clics · leads por UTM (`demo_leads`) ·
frecuencia. Se registra desde el día 1 en `documents/04-metricas-reportes/`.

> ¿Por qué no "día A / día B" alternado? Aprendido de `meta-decision-system.md`: los cortes diarios con
> presupuestos bajos no llegan a la muestra mínima (día 7: cada anuncio necesita ≥ S/12 de gasto para leerse) y
> el encendido/apagado constante resetea el aprendizaje. Bloques + lectura diaria = mismos datos, sin ruido.

---

## 11. Cadencia operativa

| Día | Acción |
|---|---|
| **Día 0 (lanzamiento)** | Activar la campaña de test (S/ 21/día total: S/ 7 por ad set); verificar pixel (PageView + Lead de prueba); UTMs correctos |
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

### 14.2 Checklist pre-lanzamiento (estado 11/10/2026)

- [x] Definir objetivo por campaña y aplicar nombres `META_Traffic_..._2026Q4` (aplicado v2).
- [x] Subir los 9 flyers (V1/V2/V3 × 3 embudos, 4:5) y crear los 9 anuncios (activos).
- [x] UTMs (`url_tags`) en los 9 anuncios.
- [x] Presupuesto S/ 7/día por ad set (S/ 150/semana total) — aplicado.
- [x] Pixel cargando (PageView) y Lead implementado en las landings.
- [x] **Excluir clientes/leads**: audiencias `VS Clientes actuales` (`120247999226360108`) y `VS Leads web`
      (`120247999226840108`) creadas + aplicadas como exclusión en los 3 ad sets.
- [ ] Conversión personalizada `Lead` (la API no la acepta con este permiso): crearla en Events Manager → Eventos
      personalizados → Crear desde `Lead` (pixel 1029237720131593).
- [ ] Verificar el dominio `licitacionesmype.pe` en Business Manager (Seguridad de la marca → Dominios).
- [x] **Formulario probado en producción** (01/10/2026): captcha OK, lead en `demo_leads`, log confirmado.
- [x] Registrar baseline diario (gasto, impresiones, clics, leads BD) desde el día 0.

### 14.3 Referencias
- Skills usadas: `ads` (Meta Andromeda + decision system), `ad-creative`, `analytics`, `attribution`, `ab-testing`,
  `frontend-design`, `design-taste-frontend`, `high-end-visual-design`, `brandkit`, `funnel-paid-vigilante`.
- Docs internos: `CONFIG_CAMPANAS_META_ADS.md`, `EMBUDOS_3_CAMPANAS.md`, `PRONOSTICO_TRAFICO_REVENUE.md`,
  `voc-datos-reales.md`, `WHATSAPP_SWITCH.md`.
