# BACKLOG DE OPTIMIZACIÓN — Justificación por centavo

> Cada palanca: estado actual → evidencia → acción propuesta → ganancia esperada → prioridad.
> Regla: ninguna acción entra sin evidencia de las 6 fuentes (ver `MEDICION_HERRAMIENTAS.md`).

---

## 1. SEGMENTACIÓN

| Sub-palanca | Evidencia | Acción | Ganancia | Prioridad |
|---|---|---|---|---|
| **Horarios (dayparting)** | Desglose por hora (13/10): 00–06h reciben ~950 impr (~4% del gasto) con CTR de madrugada bajo; pico 17–20h | ✅ **IMPLEMENTADO 14/10**: cron `23:00 pause / 07:00 activate` (`/root/dayparting.py`) con **alerta Telegram si falla** (una reactivación jamás queda en silencio). Probado: pausa manual OK | ~S/26-32/mes dejan de quemarse en horas muertas (o se reinvierten en pico) | **HECHO** |
| Edad/género | Día 1: 25–34M (CTR 10.2%) y 35–44F (6.8%) destacan; muestra chica | Esperar día 7 y cortar con datos de leads (no de clics) | Evita sobreajuste | Media |
| Geo | Pendiente desglose regional día 2 (solo 1 sorpresa conocida: AN basura, ya eliminada) | Query regional al día 7; excluir regiones sin proveedores si concentran gasto sin conversión | Protege presupuesto de zonas sin oferta | Media |
| Intereses | Config A validada (PyMEs + prácticas gubernamentales; bugs de fuzzy corregidos) | Config B (broad sin intereses) en semana 2 según plan A/B — la era Andromeda premia broad + creativo específico | Potencial de escala de entrega | Planificada |
| Advantage audience | Off (config A) | Config C en semana 3 si la señal A/B lo respalda | — | Planificada |
| Exclusiones | Clientes + leads aplicadas ✓ | Mantener; sumar "visitantes que ya llenaron form" cuando exista volumen | — | OK |

## 2. ETIQUETAS / UTMs — estado: SANO, sin brecha crítica
- `utm_source=meta&medium=paid&campaign={{campaign.name}}&content={{ad.name}}&term={{adset.name}}` verificado en leads reales (`utm_content="MA-Alertas - V2 Dolor"`).
- Mejora menor: ninguna crítica. J0 (Instant Form) se etiquetará por nombre de formulario + `landing='meta-instant-form'` al sincronizar leads.

## 3. IMÁGENES / CREATIVOS — brecha REAL y accionable

**Evidencia:** V1 informativos tienen mejor CTR (3.0–4.2%) que emocionales (2.1–2.7%), pero Meta asigna ~70% de la
entrega a V2/V3 (reacciones/peso). Con 0 leads aún, el veredicto de CALIDAD es del día 7.

**Gaps identificados (3 creativos nuevos a construir):**
1. **Mockup de producto** ✅ **CONSTRUIDO 14/10** — `public/images/ads/rot-alerta-1350.png` (la alerta llegando + análisis IA del TDR).
2. **Estilo nativo** ✅ **CONSTRUIDO 14/10** — `public/images/ads/rot-listado-1350.png` (feed de licitaciones tipo app).
3. **Prueba social** — pendiente de los testimonios (pedido listo en `/admin/respuesta-leads`; al recibir 2-3 → construir con nombre/empresa).

## 4. COPY — reescritura propuesta (V1 mejorado / value-first)

**Diagnóstico del copy actual:** hooks correctos (PAS en E1, especificidad en E3), pero:
- CTAs de alta fricción ("agenda demo", "escríbenos") en tráfico 100% frío/móvil/in-app.
- Sin prueba social (230+ proveedores registrados sin usarse).
- Sin mención de la oferta nueva (5 días de garantía).

**Versiones propuestas (listas para el refresh):**

- **E1 Alertas** — HEADLINE: `Te avisamos HOY si publican una licitación de tu rubro` · PRIMARY:
  *"Mientras estás en obra, el SEACE publica. Las convocatorias de tu rubro duran pocos días y las que no ves, se van.\n\nVigilante SEACE te avisa el mismo día por Telegram o correo, filtrado por tu rubro y región. 200+ proveedores ya lo usan.\n\nIngresa tu rubro y mira las convocatorias de tu sector ahora."*
- **E2 Software** — HEADLINE: `Analiza el TDR con IA en segundos` · PRIMARY:
  *"Deja de pasar noches leyendo bases.\n\nVigilante SEACE vigila el SEACE por ti: te avisa el mismo día, analiza el TDR con IA (requisitos, penalidades, plazos) y arma tu proforma.\n\nIngresa tu rubro y pruébalo con un proceso real de tu sector."*
- **E3 Vigentes** — HEADLINE: `Miles de licitaciones vigentes — filtra las de TU rubro` · PRIMARY:
  *"Hoy hay más de 4,700 procesos en convocatoria en el Perú. Municipios, regiones y ministerios compran todos los días.\n\n¿Cuántos calzan con tu negocio? Ingresa tu rubro y míralos ahora — y te avisamos cuando publiquen los nuevos.\n\n200+ proveedores ya monitorean con Vigilante SEACE."*

## 5. LANDING / CAPTURA
- CTA fijo móvil: LIVE (medir heatmap 24h — dónde clickean ahora).
- **J1 (valor inmediato)**: propuesta lista (rubro+email → licitaciones ahora) — es la siguiente iteración de mayor impacto.
- **Fallback número WA**: añadir "si el botón no abre → +51 918 874 873" tras el test de deep-link de 30s.
- **J0 Instant Forms**: form + campaña creados; bloqueado por ToS de página (único tap del dueño).

## 6. PRESUPUESTO — disciplina
Congelado en S/21/día (S/642/mes). Reasignaciones internas permitidas (E3→J0). Escala solo con ROAS ≥3x sostenido.
**Cada sol rastreado:** gasto (`meta_insights_diarios`) · clics/landings (Meta+GA4) · intención (heatmap+wa_clicks) ·
leads (`demo_leads`) · ventas (`pago_yapes`) · ROAS (siempre contra Yape).

## Orden de ejecución recomendado (aprobaciones del dueño)
1. **Dayparting cron** (alto impacto en centavos, bajo riesgo con alerta).
2. **J0**: aceptar ToS → completar ad set/ad → swap 08:00.
3. **Copy refresh + 3 creativos nuevos** (listo para el ciclo del día 7; los V1 actuales siguen).
4. **J1 valor inmediato** en landing (E3 primero como test).
5. **Retargeting (J2)** cuando existan ≥15 leads acumulados (evitar audiencias vacías).
