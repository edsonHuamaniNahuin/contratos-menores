# 🔧 Anexo técnico — Campaña Meta Ads · Vigilante SEACE

> Documento complementario de `DOCUMENTACION_CAMPANA_META_2026-10.md`. Aquí está **todo el detalle técnico**:
> cronología, por qué de cada decisión y cada flyer, prompts de IA, IDs, configuraciones exactas, scripts,
> costos, errores encontrados y lecciones. Fecha de esta versión: **11/10/2026** (America/Lima).

---

## 1. Cronología de construcción (bitácora)

| Fecha/hora | Acción | Por qué / resultado |
|---|---|---|
| 30/09 23:51 | **WhatsApp apagado** (`whatsapp:alertas off`) | Política de Meta del 01/10 (cobra servicio/utilidad; plantillas marketing caras). Runbook: `WHATSAPP_SWITCH.md` |
| 11/10 · inicio | **Pixel/dataset** "Vigilante SEACE Web" creado en Business Suite | Único requisito para medir; el sitio ya tenía el código (`partials/meta-pixel.blade.php`) esperando `META_PIXEL_ID` |
| 11/10 | `META_PIXEL_ID=1029237720131593` en `.env` de producción + `optimize:clear` | Deploy; verificado `fbq('init')` + `PageView` en las 4 landings |
| 11/10 | **App "Vigilante SEACE Ads"** existente → activada "En vivo" | Meta exige app pública para crear creatividades (error 1885183 con app en desarrollo) |
| 11/10 | **System user `licita`** + token de larga duración; activos asignados (cuenta, página, 2 apps, WABA) | Acceso API sin depender de sesiones personales; la página se asignó después (sin ella, `me/accounts` vacío) |
| 11/10 | Página **"Vigilante SEACE"** (`1340988862437864`) creada | Marca propia para los anuncios (en vez de SunquPacha) |
| 11/10 | **v1 de campañas** (3 campañas separadas OUTCOME_TRAFFIC, 6 anuncios con fotos de las landings) | Primera iteración; se descartó por presupuesto (S/30/día) y por creativos "fríos" |
| 11/10 | **Feedback del dueño:** flyers informativos, presupuesto excedido; pide emociones, A/B y usar modelo de imágenes | Punto de pivote: se instalaron skills de campañas/diseño y se habilitó generación de imágenes IA |
| 11/10 | **Skills instaladas** (`npx skills add`, proyecto en `.agents/skills/`): `ads`, `ad-creative`, `analytics`, `attribution`, `ab-testing` + `frontend-design`, `design-taste-frontend`, `high-end-visual-design`, `brandkit` | Aplicar marcos: hook system, decision system, playbooks Meta, A/B con rigor |
| 11/10 | **Prueba de generación de imágenes** con `gemini-2.5-flash-image` (Nano Banana) usando la key del server | Funciona; se definió el pipeline: foto IA documental + overlay HTML (texto nítido) |
| 11/10 | **Refactor v2**: 1 campaña ABO de test (`META_Traffic_Construccion_E1E2E3_2026Q4`), 3 ad sets (E1/E2/E3) a S/7/día | Consolidar presupuesto en una campaña para leer mejor con S/150/semana; eliminar las 3 v1 |
| 11/10 | **Matriz creativa**: V2 (dolor) y V3 (alivio) generadas con IA; V1 (dato) reutilizada | 3 versiones por embudo (cap 3): cubrir etapas de consciencia distintas |
| 11/10 | **Configuración total**: 9 anuncios con UTMs, audiencias de exclusión, activación, prueba e2e del formulario | Checklist de la skill `ads` aplicado |
| 11/10 · cierre | Fix de activación: 8/9 anuncios habían quedado PAUSED tras la activación en lote → **re-activados y verificados** (9 ACTIVE; 8 `IN_PROCESS` = revisión Meta) | Lección: verificar estado post-activación |

### Correcciones de rumbo (para el registro)
1. **Presupuesto**: de S/30/día (v1) → **S/7/día por ad set = S/21/día = S/147/semana** (tope real: S/50/campaña/semana).
2. **Creativos**: de informativos (tarjetas/datos) → **emocionales** (dolor/fomo, miedo, alivio) con fotos IA documentales.
3. **A/B**: de "formato 4:5 vs 1:1" → **3 versiones de hook** por embudo + **3 estrategias de configuración** (intereses → broad → Advantage+), una por semana.
4. **Fecha de la doc**: la campaña se construyó el **11/10/2026** (la política de Meta y el apagado WhatsApp sí son del 01/10 y 30/09).

---

## 2. Activos y referencias (sin secretos)

| Activo | Valor | Nota |
|---|---|---|
| Business Portfolio | Sunqupacha `1032654987593059` | Owner de todo |
| Cuenta publicitaria | `act_350473826722700` (FocusSunquPacha) | PEN, America/Lima, VISA *9464, estado activo |
| Página de Facebook | Vigilante SEACE `1340988862437864` | Los anuncios salen con esta identidad |
| Pixel / Conjunto de datos | Vigilante SEACE Web `1029237720131593` | `META_PIXEL_ID` en `.env` producción |
| App Meta | Vigilante SEACE Ads `4423561881198631` | **En vivo** (requisito para creatividades) |
| System user | `licita` `6158861140448` | Token `EAAZ…` de larga duración (no versionar) |
| WABA WhatsApp | `28137155649235530` | Alertas apagadas; ver `WHATSAPP_SWITCH.md` |

---

## 3. Estructura de campaña — IDs y configuración exacta

**Campaña:** `META_Traffic_Construccion_E1E2E3_2026Q4` → `120247999030430108`
- Objetivo `OUTCOME_TRAFFIC` · ABO (presupuesto por ad set) · **ACTIVE**.
- Fase: aprendizaje de datos. El pixel mide `Lead` en paralelo; al llegar a **≥15–20 leads** se crea la versión
  `OUTCOME_LEADS + OFFSITE_CONVERSIONS (Lead)` con la misma estructura (los actuales quedan de control).

| Ad set | ID | Landing | S/ día | Anuncios (ACTIVE) |
|---|---|---|---|---|
| `META_Traffic_E1_Alertas_2026Q4` | `120247999030690108` | `/alertas-licitaciones` | 7.00 | V1 `120247999155660108` · V2 `120247999156360108` · V3 `120247999157100108` |
| `META_Traffic_E2_Software_2026Q4` | `120247999032700108` | `/software-licitaciones` | 7.00 | V1 `120247999157490108` · V2 `120247999158440108` · V3 `120247999159430108` |
| `META_Traffic_E3_Vigentes_2026Q4` | `120247999034400108` | `/licitaciones-vigentes` | 7.00 | V1 `120247999160020108` · V2 `120247999161460108` · V3 `120247999162570108` |

**Targeting (por ad set):** `geo_locations: {countries:["PE"]}` · edad 25–60 · `targeting_automation.advantage_audience = 0`
· intereses como señal (`flexible_spec`):
- E1: *Government concepts and practices* + *OSCE* + *Construction engineering*
- E2: idem + *Software business*
- E3: idem + *Small business*

**Pujas/entrega:** `billing_event=IMPRESSIONS` · `optimization_goal=LINK_CLICKS` · `bid_strategy=LOWEST_COST_WITHOUT_CAP`.

**UTMs (`url_tags` en cada creatividad):**
```
utm_source=meta&utm_medium=paid&utm_campaign={{campaign.name}}&utm_content={{ad.name}}&utm_term={{adset.name}}
```

**Texto del anuncio (por embudo, igual en las 3 versiones; CTA `LEARN_MORE`):**

| Embudo | Headline (40c) | Primary text |
|---|---|---|
| MA-Alertas | "Alertas de licitaciones por Telegram y correo" | "¿Cuántas licitaciones de tu rubro se te escapan cada mes? … te avisa por Telegram o correo el mismo día…" |
| MA-Software | "Software de licitaciones con IA" | "Deja de buscar licitaciones a mano… la IA analiza el TDR y te arma la proforma…" |
| MA-Vigentes | "Licitaciones vigentes hoy" | "Hoy hay más de 10,000 procesos en convocatoria… Mira los procesos vigentes y recibe aviso…" |

---

## 4. Audiencias y exclusiones

| Audiencia | ID | Contenido | Uso |
|---|---|---|---|
| VS Clientes actuales (excluir) | `120247999226360108` | 10 emails SHA-256 (pagos históricos: monthly y mayores-premium) | Exclusión en los 3 ad sets |
| VS Leads web (excluir) | `120247999226840108` | 3 emails SHA-256 (`demo_leads`) | Exclusión en los 3 ad sets |

- La creación estaba bloqueada hasta **aceptar las Condiciones de Públicos Personalizados** (ToS por cuenta, en UI:
  `business.facebook.com/ads/manage/customaudiences/tos/?act=350473826722700`).
- Script reproducible: `scripts/meta-ads/configure_audiences.py` (hashea `EMAIL_SHA256` automáticamente).
- **Pendientes de audiencias:** lookalike 1 % cuando haya ≥100 contactos; audiencia web (visitantes 30/90 días) para
  retargeting cuando el pixel acumule volumen.

---

## 5. Sistema creativo — por qué cada flyer

### 5.1 Marco aplicado (skill `ad-creative` → hook system)
- Pipeline: **Segmento → Motivación (verbatim) → Formato → Hook**; el A/B mide por CTR/CPC/leads, no por gusto.
- **Cap de 3 versiones por embudo** para no fragmentar el presupuesto (S/7/día por embudo).
- Cada versión cubre una etapa distinta de consciencia:

| Versión | Etapa / emoción | E1 Alertas | E2 Software | E3 Vigentes |
|---|---|---|---|---|
| **V1 Dato** | Solución (racional, prueba) | "Esta semana el SEACE publicó 1,347 convocatorias. ¿Cuántas eran de tu rubro?" (tarjeta Telegram con proceso real) | "La IA lee el TDR y te dice si de verdad calzas antes de postular." (panel de producto con score 8.4) | "10,322 procesos vigentes. ¿Cuántos calzan con tu empresa?" (feed con 3 procesos reales) |
| **V2 Dolor** | Problema (FOMO/miedo) | "Mientras lees esto, tu competencia ya está cotizando esa licitación." (contratista frustrado al atardecer) | "El requisito que te descalifica está en la página 34." (mano señalando requisito circulado) | "Tu próxima obra ya está publicada en el SEACE." (equipo al amanecer frente a la obra) |
| **V3 Alivio** | Beneficio/deseo | "Deja de revisar el SEACE todos los días." (contratista tranquilo con café) | "Recupera tus noches. La IA lee el TDR por ti." (ingeniero descansando de noche) | "El Estado compra lo que tú vende. Todos los días." (trabajador de servicios con tablet en almacén) |

### 5.2 Diseño (tokens por embudo, extraídos de las landings)
- **E1:** papel crema `#f6f3ec`, teal `#0e5a54`, acento menta `#9fdfc9`, display **Fraunces** (serif).
- **E2:** fondo menta `#f4f8f6`, teal `#0b6e63`, acento `#17b885`, display **Space Grotesk**.
- **E3:** navy/ámbar `#1d3f8f` / `#f0a028`, display **Sora**.
- Layout común: foto full-bleed con scrim oscuro, marca arriba, kicker + titular grande + sub + CTA pill.
- El **texto va en HTML** (crisp) y la foto es **generada por IA** (no textos dentro de la imagen).

### 5.3 Fotos IA — modelo, prompts y costo
- **Modelo:** `gemini-2.5-flash-image` (Nano Banana) vía Gemini API — key existente del server (analizador TDR).
- **Costo:** ≈ US$ 0.04/imagen · total generadas 9 (1 prueba + 3 V2 + 3 V3 + 2 test) ≈ **US$ 0.40**.
- **Prompts usados** (resumen; en español, sin texto en la imagen):
  1. *E1-V2:* "Fotografía documental: contratista peruano con casco amarillo mirando su celular con gesto de frustración,
     planos y laptop sobre mesa de obra, luz cálida de tarde."
  2. *E2-V2:* "Close-up de un documento grueso de licitación con un círculo rojo hecho a lapicero sobre un párrafo,
     mano señalando, taza de café, laptop desenfocada, luz de lámpara, atmósfera tensa."
  3. *E3-V2:* "Fotografía cinematográfica al amanecer: equipo de ingeniería con cascos revisando planos frente a una
     obra pública en construcción, grúa al fondo, luz dorada, actitud de oportunidad."
  4. *E1-V3:* "Contratista tomando café tranquilo en oficina de obra por la mañana, sonrisa leve, planos ordenados,
     luz suave dorada, sensación de control."
  5. *E2-V3:* "Ingeniero de noche recostado relajado en su silla, laptop con documento resumido, pila de papeles a un
     lado, lámpara de escritorio, alivio tras terminar el trabajo."
  6. *E3-V3:* "Trabajador de servicios con tablet revisando inventario y pedidos en un almacén ordenado de insumos,
     luz industrial cálida, actitud profesional."

### 5.4 Pipeline de render y archivos
- Render HTML→PNG con Playwright (skill `browser-automation`): `scripts/ad-creatives/render.mjs`
  (`RENDER_URL/RENDER_OUT/RENDER_W/RENDER_H`), a **1080×1350 (4:5)** y **1080×1080 (1:1)**.
- Templates: `flyer-v2.html` (configs `e1/e2/e3` = V2 y `e1c/e2c/e3c` = V3) + los templates V1 originales.
- PNGs en `public/images/ads/`: V1 `ma-*`, V2 `e{1,2,3}-v2-*`, V3 `e{1,2,3}c-v3-*`; fotos IA en `ads/ai/`.

### 5.5 Reglas de producción (skill `ad-creative` + `meta-andromeda-playbook`)
- Volumen > pulido: 1 h/semana y 3–5 estáticos nuevos; estáticos primero, video solo si un concepto valida.
- Jerarquía de iteración: **hook → visual → formato → copy/CTA** (una variable por test).
- **No editar** un anuncio con rendimiento (resetea aprendizaje): se lanza uno nuevo.
- Cap: 3 versiones activas por embudo; la nueva reemplaza a la peor.

---

## 6. Medición, datos y verificación

- **Pixel:** `PageView` en todas las landings; `Contact` (clic `wa.me`); `Lead` (formulario, `session('ok')`).
- **Prueba e2e del formulario (11/10/2026):** submit headless en `/software-licitaciones` → captcha 8+5 resuelto →
  lead creado en `demo_leads` (id 4) → log `DemoLead: solicitud registrada` → lead de prueba eliminado.
  Conclusión: **sin fricción**; los 157 `form_submit` de GA4 eran de `/login` (72), `/register` (47), `/email/verify` (17).
- **GA4 (28 días, base para configuración):** 335 usuarios · 625 sesiones · 4,433 vistas · 6m34s · rebote 32 %;
  fuentes Google 161u · directo 76u · **Bing 60u** · **ChatGPT 18u**; **82 % desktop**; Lima 57 % + La Libertad,
  Lambayeque, Arequipa, Junín, Cusco, Cajamarca; landings: buscador 92 % engagement, plantillas-TDR 54 %.
- **BD producción (contexto):** 230 usuarios, 3 pagos activos (MRR S/ 185), 10 trials expirados, 20,933 contratos
  mayores (10,322 convocados), 5,599 publicados en 30 días.
- **Deuda de medición:** conversión personalizada `Lead` (crear en Events Manager; la API la rechaza con el permiso
  actual), verificación de dominio `licitacionesmype.pe`, y CAPI server-side (futuro, para `SuscripcionActiva`).

---

## 7. Scripts (inventario y uso)

| Script | Qué hace | Uso |
|---|---|---|
| `scripts/meta-ads/create_campaigns.py` | Creador v1 (1 campaña por entrada del JSON) | Referencia histórica |
| `scripts/meta-ads/refactor_campaigns_v2.py` | Crea la campaña v2 (1 campaña + 3 ad sets) y borra las v1 | `--delete-old` |
| `scripts/meta-ads/configure_v2.py` | 9 anuncios V1/V2/V3 con UTMs, intenta audiencias/conversión, activa | `--activate` |
| `scripts/meta-ads/configure_audiences.py` | Audiencias de exclusión + aplicación a los ad sets | Post-ToS |
| `scripts/ad-creatives/render.mjs` | Render HTML→PNG con Playwright | `RENDER_URL=… RENDER_OUT=…` |
| `scripts/ga4-mcp-server.py` | Reportes GA4 (totals, pages, sources, events, devices) | `analizador-tdr/venv/bin/python` |

**Ejemplo (sin token real):**
```bash
python scripts/meta-ads/configure_v2.py --token $TOKEN --ad-account act_350473826722700 \
  --page-id 1340988862437864 --pixel-id 1029237720131593 --campaign-id 120247999030430108 \
  --emails-file emails.json --activate
```

---

## 8. Reglas de operación (día a día, semana 1–3)

- **Semana 1 (hooks):** los 3 ad sets con 3 versiones; lectura diaria (entrega, CPM, CTR, CPC, leads por UTM).
  Día 7: pausar la peor versión de cada embudo.
- **Semana 2 (config B · broad):** mismos creativos ganadores, sin intereses (solo país/edad); comparar CPM/CTR/CPL.
- **Semana 3 (config C · Advantage+):** activar `advantage_audience`; adoptar la mejor estrategia y escalar.
- **Kill/keep/scale:** no juzgar con < S/ 36 de gasto por anuncio (3× TCPL S/ 12); kill si > 1.5× TCPL (S/ 18) sostenido;
  graduar con ≥5 leads calificados, ≥60 % de calificación, CPL ≤ S/ 12, ≥14 días. Escalar +20 % cada 5 días.
- **Fatiga (cold):** warning frecuencia 2.5, crítico 4.0; CTR −20 % o CPM +30 % → reemplazo en ≤7 días.

---

## 9. Economía de la campaña

- Inversión: **S/ 150/semana (S/ 642/mes)**; costo de producción creativa: **US$ 0.40** (9 imágenes IA) + tiempo.
- Rendimiento esperado (benchmarks PE: CPM ≈ S/30, CTR 1.2 %, CVR landing 6 %): ~59 clics/semana, ~3–4 leads/semana
  (CPL implícito ≈ S/ 40 en fase de aprendizaje). Se recalibra al día 14.
- TCPL de trabajo **S/ 12**; CAC máximo saludable **S/ 100–110** (LTV ≈ S/ 338 a 6 meses); break-even: Mayores (S/68)
  paga un CAC de S/ 250 en ~4.6 meses → **priorizar el embudo de Contratos Mayores**.

---

## 10. Lecciones aprendidas (para futuras campañas)

1. **App en modo desarrollo** no permite crear creatividades (error 1885183): publicarla en "En vivo" primero.
2. La **activación en lote por API** no persistió para 8/9 anuncios: **siempre re-verificar** el estado y reactivar.
3. **Rate limit de la cuenta** (code 17) con muchas llamadas: espaciar y verificar en lotes.
4. **Públicos personalizados** requieren aceptar el ToS en la UI (la API devuelve error 1870090).
5. El **system user necesita la página asignada** o `me/accounts` viene vacío (no se puede publicar el anuncio).
6. **Nunca validar flyers por gusto**: A/B por CTR/CPC/leads con una variable por bloque y cap de 3 versiones.
7. **La foto IA es el fondo, el mensaje va en HTML**: texto nítido, branding consistente y regenerable en minutos.
8. GA4 antes de configurar: **82 % desktop**, Bing+ChatGPT 23 % del orgánico y Lima 57 % — datos que cambian decisiones.
9. **Timing de activación (error detectado y corregido):** la campaña se activó un **domingo 23:00**; con audiencia B2B
   eso quema presupuesto en horas muertas. Se **pausó con S/ 0 gastado** (antes de que Meta entregara) y se programó
   el arranque para el **lunes 08:00 (America/Lima)** vía timer del servidor + `scripts/meta-ads/toggle_campaign.py`.
   *Nunca activar campañas B2B fuera de horario laboral; el arranque es una decisión de media, no un detalle.*
10. **Dayparting (franjas L–V 8:00–20:00) por API requiere presupuesto lifetime**, no diario
    (errores 1487675/1487682: "la frecuencia requiere una campaña sin franjas del día" / "no se admite presupuesto
    diario para la especificación actual"). Alternativa documentada: si al día 7 el desglose por hora muestra noches
    de baja calidad, migrar los ad sets a presupuesto semanal (lifetime) + `adset_schedule` para activar franjas.
11. **Conversión personalizada `Lead`:** la API rechaza su creación (1760020/1760021: formato de regla/categoría);
    el evento `Lead` ya sirve directo como conversión en Ads Manager, así que queda como paso manual opcional.

---

## 11. Pendientes (al cierre de esta versión)

- [ ] Conversión personalizada `Lead` en Events Manager (manual).
- [ ] Verificación de dominio `licitacionesmype.pe` en Business Manager.
- [ ] Baseline diario (gasto/impresiones/clics/leads) y lectura al día 7.
- [ ] Semana 2: rotación a config **broad** con los ganadores.
- [ ] Evaluar 4.º embudo (`/plantillas-tdr`, lead magnet) cuando se cierre la fase de datos (respetar cap de 3 campañas).

### Archivos relacionados
`DOCUMENTACION_CAMPANA_META_2026-10.md` (plan maestro) · `CONFIG_CAMPANAS_META_ADS.md` (config inicial) ·
`EMBUDOS_3_CAMPANAS.md` · `05-tecnico-operaciones/WHATSAPP_SWITCH.md` · `scripts/meta-ads/*` · `scripts/ad-creatives/*` ·
`public/images/ads/*`.
