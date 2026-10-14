# Registro de Herramientas y Medición (Fuentes de verdad)

> Cada herramienta con: qué responde, dónde vive el dato, cómo consultarlo y con qué frecuencia.
> Regla: toda métrica que se reporte debe poder rastrearse a UNA de estas fuentes.

---

## 1. Meta Ads (Marketing API)
- **Qué responde:** entrega, gasto, CTR/CPC/CPM, alcance/frecuencia, clics al enlace, landing views,
  breakdowns (plataforma, edad/género, región, hora, dispositivo), estados e issues, calidad de aprendiz.
- **Acceso:** token de sistema `licita` en `/root/.meta-token-pages`; cuenta `act_350473826722700`;
  campaña `120247999030430108` (E1/E2/E3) + campaña lead-ads (J0, al crearse).
- **Dónde vive:** dashboard de Meta + **copia propia** en `meta_insights_diarios` / `meta_insights_intradia`.
- **Cómo se consulta:** `php artisan meta:monitor` (estado+alertas), `php artisan meta:ingerir` (almacén),
  o Python directo con el token (curl/urllib a graph.facebook.com).
- **Frecuencia:** monitor 4x/día + Telegram 21:30 · ingesta cada hora · validación con `verify_campaign.py`.

## 2. GA4 (Google Analytics 4)
- **Qué responde:** sesiones, usuarios, fuentes (meta/paid, orgánico, IA), dispositivos, navegadores,
  eventos (`demo_lead_enviado`, `lead_whatsapp_click`, `form_start/submit`, `scroll`), tiempo por página.
- **Acceso:** service account en `.env` (`GA4_CREDENTIALS_JSON`), property `properties/404642926`.
- **Cómo se consulta:** `/var/www/vigilante-seace/analizador-tdr/venv/bin/python scripts/ga4-mcp-server.py <tool> [args]`
  (`ga4_traffic_sources`, `ga4_all_events`, `ga4_top_pages`, `ga4_devices`, `ga4_daily_trend`, `ga4_totals`)
  o scripts ad-hoc importando `run_report` (ver `/root/ga4_browser.py` de ejemplo).
- **Frecuencia:** revisión diaria/semanal.

## 3. Almacén propio (BD MySQL `vigilante_seace`)
| Tabla | Qué guarda | Consulta típica |
|---|---|---|
| `meta_insights_diarios` | Cierre diario por anuncio (Meta) | `ROUND(SUM(spend),2)`, CTR, leads por día |
| `meta_insights_intradia` | Acumulado por hora (ritmo del día) | gasto por hora de hoy |
| `demo_leads` | Leads de landings (utm_*, fbclid, landing) | leads del día por anuncio (`utm_content`) |
| `wa_clicks` | Clics a botones WhatsApp (utm, UA) | intención WA por anuncio |
| `page_events` | Clicks (x/y) + scroll por landing (heatmap) | dónde clickean / hasta dónde leen |
| `users` | Registros (+utm_source desde 12/10) | registros atribuidos a campaña |
| `pago_yapes` | Yapes (aprobados/pendientes) | **ventas y ROAS real** |
| `subscriptions` | Trials/pagos históricos | conversión y cohortes |
- **Cómo se consulta:** `mysql vigilante_seace -e 'SELECT ...'` en `root@161.132.4.111` (o vía ssh desde local).
- **Panel web:** `/admin/heatmaps`, `/admin/monitoreo`, `/admin/pagos-yape`, `/admin/respuesta-leads`.

## 4. Monitor del sistema (automatizaciones activas)
- `meta:monitor` — 10/13/16/19 (alertas) + 21:30 (resumen) → Telegram admin.
- `meta:ingerir` — hora a hora 08–23 (intradía) + 08:15 (cierre diario).
- `trial:onboarding` — 09:15 (secuencia de activación; detecta leads → trial).
- `roi:semanal` — lunes 09:00 (ROAS por cohortes: gasto→leads→registros→trials→Yape).
- **Alertas instantáneas:** nuevo lead (form), Yape reportado, Yape pendiente +2h, nuevo usuario, nueva suscripción.

## 5. Clarity (Microsoft) — grabaciones
- **Qué responde:** qué HACE el usuario (video de sesión), rage/dead clicks, scroll profundo.
- **Acceso:** dashboard del dueño (`clarity.microsoft.com`, ID `ywwnwgfd`) + **Data Export API**
  (token en `/root/.clarity-token`; endpoint `www.clarity.ms/export-data/api/v1/project-live-insights?numOfDays=3`).
- **Nota:** en modo opt-out (desde 13/10) — graba salvo que el visitante rechace cookies.
- **Uso:** diagnóstico cualitativo semanal; las métricas duras vienen de GA4/BD/Meta.

## 6. Embudo (las 3 landings) + QA
- Landings: `/alertas-licitaciones` (E1), `/software-licitaciones` (E2), `/licitaciones-vigentes` (E3), + `/plantillas-tdr`.
- **Pre-lanzamiento:** `scripts/qa/funnel-audit.mjs` (10 checks por landing) + `scripts/meta-ads/verify_campaign.py` (88 checks).
- CTAs: formulario (demo) + WhatsApp manual (+51 918 874 873) + **CTA fijo móvil** (desde 14/10).

## 7. J0 — Instant Forms (Lead Ads nativos) — CREADO, PENDIENTE ToS DE PÁGINA
- **Qué responde:** leads capturados NATIVAMENTE dentro de la app de Facebook (sin landing, sin deep-link) —
  se activa porque **100% del tráfico pagado navega en el navegador in-app de Meta** (UA `FB_IAB` verificado en BD).
- **IDs creados (14/10):**
  - Formulario: `1427421766170299` (preguntas: nombre + email prellenados + rubro)
  - Campaña: `120248044120960108` (OUTCOME_LEADS, PAUSED)
- **BLOQUEO ACTUAL:** el ad set exige que la PÁGINA acepte las **Condiciones de Lead Ads**
  (error 1815089). Paso manual del dueño: Business Suite → Configuración → **Acceso a clientes potenciales**
  (o crear 1 anuncio de leads desde la UI una vez → Meta muestra el diálogo de términos → aceptar).
- **Al aceptar:** crear ad set (`S/7/día`, targeting E3, Instant Form) + creativo (reusa copy/imagen de
  E3 V2 + `lead_gen_form_id`) + anuncio → programar swap 08:00 (`pausar E3 tráfico` + activar J0).
- **Dónde vive el lead:** Meta (Lead Center) + exportación CSV; integración a BD/Telegram pendiente de
  permiso `leads_retrieval` (si no está, se sincroniza manual o se solicita).
- **Cómo se mide el test:** CPL lead-nativo vs CPL formulario-web (Meta + `demo_leads` + alertas).
  Gasto J0 queda en `meta_insights_diarios` (la campaña nueva se suma a la ingesta: actualizar `meta:ingerir`
  con el array de campañas si se decide dejar J0 permanente).

---

## Reglas de medición
1. Toda afirmación de campaña debe citar fuente + número (una de las 6 fuentes de arriba).
2. El ROAS SIEMPRE se calcula contra `pago_yapes` (Yape), no contra "conversiones" de plataforma.
3. Gasto = `meta_insights_diarios` (cierre) o API (intradía); nunca estimar.
4. Los clics de WhatsApp son **clics**, no conversaciones (verificado 14/10 — lección en BITACORA).
