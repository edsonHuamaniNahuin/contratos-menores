---
name: optimizacion-diaria-vigilante
description: Manual operativo del agente para la optimización diaria de campañas y embudo de Vigilante SEACE. Usar SIEMPRE que se ejecute el ritual diario/semanal, se analicen resultados de campaña, se busquen patrones de conversión, se tomen decisiones de pausar/rotar/reasignar, se analice UX con heatmaps/Clarity, o se planifique el siguiente refactor. Es el puente entre los datos reales (Meta, BD propia, heatmaps, Clarity, GA4, Yape) y los skills de decisión (ads, ab-testing, page-cro, analytics, mercado-vigilante).
---

# 🎯 SKILL: Optimización Diaria — Operador de Campañas Vigilante SEACE

> Eres el operador responsable del ROI. Este skill define CON QUÉ datos decides, CON QUÉ reglas
> y DÓNDE están las herramientas. La doctrina completa está en `PROTOCOLO_OPERATIVO.md`;
> este skill es el "cómo hacerlo" con nuestras herramientas reales.

## 1. FUENTES DE DATOS PROPIAS (usarlas todas, en este orden)

### 1a. Dossier diario (lo primero)
```bash
ssh -i C:\Users\PC-01\.ssh\deploy_vigilante root@161.132.4.111 "cat /var/www/vigilante-seace/storage/agent/DAILY_BRIEF.md"
```
Compilado por `agente:brief` (08:35). Si no existe: correrlo vía ssh + artisan.

### 1b. Heatmaps first-party (NUESTRO mapa de clics real)
Panel humano: `https://licitacionesmype.pe/admin/heatmaps` (para el dueño).
Agente: misma data vía SQL en el servidor (recipes obligatorias):
```sql
-- Qué clickean HOY (target + altura en página)
SELECT tipo, COALESCE(NULLIF(target,''),'-') t, ROUND(AVG(y_pct)) y, COUNT(*) n
FROM page_events WHERE DATE(created_at)=CURDATE() GROUP BY 1,2 ORDER BY n DESC LIMIT 20;
-- Scroll: hasta dónde llegan
SELECT ROUND(AVG(scroll_pct)) prom, MAX(scroll_pct) maximo, COUNT(*) n
FROM page_events WHERE tipo='scroll' AND DATE(created_at)=CURDATE();
-- Por landing (qué página funciona y cuál no)
SELECT landing, SUM(tipo='click') clics, SUM(tipo='scroll') scrolls, COUNT(*) total
FROM page_events WHERE DATE(created_at)=CURDATE() GROUP BY 1 ORDER BY total DESC;
```
Conexión: ssh al servidor → `set -a; . /var/www/vigilante-seace/.env; set +a` → `mysql -u$DB_USERNAME -p$DB_PASSWORD $DB_DATABASE -e "..."`.
**Lecturas clave:** clics que NO son en botones/CTAs = dead clicks UX; scroll <50% = el formulario del fondo no se alcanza; target repetido sin conversión = affordance roto.

### 1c. Embudo de conversión (la cadena completa)
```sql
-- Clics WhatsApp (intención real) con atribución
SELECT DATE_FORMAT(created_at,'%d/%m %H:%i'), COALESCE(NULLIF(utm_content,''),'(organico)'), landing
FROM wa_clicks WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND landing != 'audit' ORDER BY id DESC;
-- Leads de formulario (con rubro/fuente)
SELECT id, nombre, LEFT(email,30), COALESCE(NULLIF(rubro,''),'-'), DATE_FORMAT(created_at,'%d/%m %H:%i'), COALESCE(NULLIF(utm_content,''),'(organico)')
FROM demo_leads WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY id DESC;
-- Registros y VENTAS (esto es el ROI de verdad)
SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY);
SELECT estado, COUNT(*), SUM(monto) FROM pago_yapes WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY estado;
```
**Cadena del embudo:** Impresiones → Clics (Meta) → Eventos en landing (page_events) → Clics WA / envíos form → Leads → Registros → **Yape aprobado (única métrica que paga la luz)**.

### 1d. Meta (métricas de campañas)
- Dossier (`agente:brief`) ya las trae. Para detalle por anuncio:
  ```bash
  ssh … "python3 - <<'EOF' … act_350473826722700/insights level=ad … EOF"   # patrón usado en este repo
  ```
- Scripts: `scripts/meta-ads/verify_campaign.py` (88+ checks), `toggle_campaign.py`, `daily_report.py`.
- Demografía/hora: breakdowns `age`, `hourly_stats_aggregated_by_advertiser_time_zone`.

### 1e. Clarity (UX profundo)
```bash
ssh … "curl -s 'https://www.clarity.ms/export-data/api/v1/project-live-insights?numOfDays=1' -H 'Authorization: Bearer '\$(cat /root/.clarity-token)"
```
Métricas accionables: `DeadClickCount` (>5% sesiones → affordance roto), `RageClickCount` (>1% → frustración), `QuickbackClick` (>2% → link engañoso/lento), `ScriptErrorCount` (>0 → bug JS), `ScrollDepth` (<50% → contenido/CTA mal ubicado). Dashboard para el humano: `clarity.microsoft.com/projects/view/ywwntw8g1d`.

### 1f. Investigación de mercado (el QUÉ decir)
- `documents/03-clientes-investigacion/INVESTIGACION_TARGET_CALIENTE_2026-10.md` (ICP TIER A, triggers de urgencia)
- `documents/03-clientes-investigacion/COMPETIDORES_2026-10.md` (brechas: canal correo, combo TDR+proforma)
- `documents/02-marketing-adquisicion/OBJETIVO_ROI_CAMPANAS.md` (§7: CAC≤82, CPL≤8, 2.6 leads/día)
- `documents/02-marketing-adquisicion/PANEL_DIARIO_CAMPANA.md` (registry A/B T1-T7)

## 2. SKILLS DE DECISIÓN (OBLIGATORIO cargar y citar el principio en cada llamada)
| Decisión | Skill que la gobierna |
|---|---|
| Pausar/rotar anuncios, fatiga, placements | `ads` |
| Validez de tests, cuándo llamar ganador, anti-peeking | `ab-testing` |
| Cambios de landing/form | `page-cro` / `form-cro` |
| Verificar tracking antes de concluir | `analytics` |
| Ángulos/mensajes psicológicos | `marketing-psychology` / `copywriting` |
| Perfil del target, rubros, VoC | `mercado-vigilante` |
| Estructura del embudo pagado | `funnel-paid-vigilante` / `landing-vigilante` |

**Y OBLIGATORIO:** cada refactor audita los componentes contra
`documents/02-marketing-adquisicion/REGLAS_MEJORA_CAMPANAS.md` (copy/imagen/segmentación/edad/
destino/formulario/UX/horario) — cada pieza MEJORA con el aprendizaje de lo exitoso; prohibido
reutilizar material de campañas retiradas. Consultar mínimo **4 herramientas** de §1 por ritual y
registrar: `componente → dato → skill citado → herramientas → regla aplicada → resultado esperado`.

## 3. RITUAL DIARIO (orden exacto)
1. Leer dossier (1a) — ¿gasto, entrega, conteos cuadran?
2. Heatmap del día (1b) — ¿qué falta clickear? ¿dónde se detienen?
3. Embudo (1c) — clics WA, leads, registros, Yape: comparar contra ayer
4. Meta por brazo (1d) — costo por clic WA / por landing por BRAZO (T4 vs T5 vs U1...)
5. Clarity (1e) — dead clicks / rage / quickbacks / errores JS
6. **Patrones**: cruzar 2-5. Ej.: "brazo X tiene mejor costo/landing PERO scroll 40% → el problema es la ubicación del CTA, no el anuncio"
7. Decidir (tabla §4) — DENTRO de la matriz. Fuera de matriz → PROPUESTA.
8. Aplicar → QA (verify_campaign + funnel-audit) → commit → deploy → registro (panel + bitácora)
9. Reporte al dueño (semáforo + 1 dato + 1 acción + 1 propuesta)

## 4. UMBRALES DE ACCIÓN (dato → acción, sin ambigüedad)
| Dato | Acción autónoma |
|---|---|
| Brazo con fair-share ≥S/12 y métrica <50% mediana (día 7+) | Pausar / rotar creativo / reasignar entre brazos |
| Dead clicks >5% en una página | Fix del elemento (clickeable o quitar affordance) |
| Quickbacks >2% | Auditar links con retorno inmediato |
| Scroll <50% + form al fondo | Subir CTA/duplicar form a mitad de página |
| Clic WA de un brazo cuesta >2x la mediana de brazos | Reasignar presupuesto al mejor |
| CTR cae >30% (3 días) | Rotar creativo (flyer-v2.html + render + API) |
| Lead sin atender >2h | Escalar al dueño (Telegram) |
| CPL ≤ S/8 con ≥3 leads | Mantener + preparar propuesta de escala (NO ejecutar) |
| CPL > S/12 sostenido 7 días | Cortar la vía y reasignar (autónomo) |

## 5. RECETAS DE EJECUCIÓN (comandos/IDs reales)
- **Pausar/activar adset:** `curl -X POST https://graph.facebook.com/v23.0/<ADSET_ID> -d status=PAUSED -d access_token=$(cat /root/.meta-token)`
- **IDs clave:** campañas: Tráfico `120247999030430108` · Urgencia `120248045023470108` · J0 `120248044120960108`. Adsets activos: T4 `120248057690490108` · T5 `120248082495750108` · U1 `120248045023660108`. (E1/E2/E3 = pausados, evidencia.)
- **Creativo nuevo:** editar `scripts/ad-creatives/flyer-v2.html` (variante con foto AI de `public/images/ads/ai/`) → render (harness + `render.mjs`) → subir imagen + `adcreatives` + `ads` (patrón usado hoy, ver bitácora 14-15/10).
- **Landing fix:** editar blade → `funnel-audit` (harness) en la página → commit → `bash deploy/orchestrate.sh deploy` (build SIEMPRE).
- **Dayparting:** `/root/dayparting.py` controla las 3 campañas (07:00 activa / 23:00 pausa).
- **Robot pendientes:** `/root/pendientes_ad.py` (crea J0/T5 al propagar Meta + rebalancea U1).

## 6. REGLAS DE ORO
1. **Cada cambio sale de un dato** — si no hay dato, es PROPUESTA, no acción.
2. QA verde antes de considerar "aplicado" (`verify_campaign` + `funnel-audit`).
3. PROHIBIDO sin OK del dueño: presupuesto total, precios, campañas nuevas, WhatsApp API.
4. Placements SIEMPRE FB+IG. Edad 40-60 (validada). Intereses TIER A.
5. Todo incidente/lección → `BITACORA.md`. Todo cambio → `PANEL_DIARIO_CAMPANA.md`.
6. El correo es el canal de alertas promocionado (WhatsApp solo contacto manual).
