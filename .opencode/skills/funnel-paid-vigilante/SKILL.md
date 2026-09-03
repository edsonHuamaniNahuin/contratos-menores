---
name: "funnel-paid-vigilante"
description: "Configurar y operar el embudo de tráfico PAGADO (Google Ads) + medición GA4/GTM para Vigilante SEACE (licitacionesmype.pe). Usar cuando se lancen campañas pagadas, se configure el tracking de conversiones del embudo (anuncio → landing → registro → trial → pago), se auditen métricas por etapa (CTR, conversión landing, trial→pago, CAC) o se conecte Google Ads con GA4. Requiere que las landings existan (skill landing-vigilante) y el message-match anuncio→landing."
license: MIT
metadata:
  version: 1.0.0
  category: marketing-vigilante
---

# S3 · Embudo con Google Ads + medición — Vigilante SEACE

## Principio rector
**Message match:** el anuncio es la introducción; la landing continúa la misma historia (keyword/headline/CTA casi textuales). Mandar tráfico pagado a la home o a páginas genéricas baja el Quality Score → menos impresiones y CPC más caro. Una landing dedicada por campaña/ad group (el skill landing-vigilante las produce).

## Estructura de cuenta Google Ads (presupuesto sugerido S/500–1,000/mes ≈ US$130–260)
### Campaña 1 — Search "Alta intención" (conversión directa, ~60% del presupuesto)
Ad groups por tema, 5-10 keywords cada uno, anuncios dedicados:
- "licitaciones por rubro": `[licitaciones obras públicas]`, "licitaciones de construcción en Perú", `[convocatorias seace]`, "licitaciones vigentes", "buena pro seace".
- "contratos mayores / ley 32069": "contratos mayores a 8 uit", "licitación pública peru", "adjudicación simplificada".
- Marca: "vigilante seace", "licitacionesmype" (protege marca, CPC bajo).
Landing por ad group (software-licitaciones / monitoreo-licitaciones / alertas-licitaciones), CTA: trial 15 días.

### Campaña 2 — Search "Lead magnet" (generación de leads, ~25%)
Keywords informativas: "cómo venderle al estado", "requisitos para ser proveedor del estado", "qué es la RNP", "cómo postular a licitaciones" → landing `/plantillas-tdr` (formulario email → guía/modelos) → nurture por email → prueba gratuita.

### Campaña 3 — Remarketing (Display/YouTube, ~15%)
A quienes visitaron sin convertir (audiencia no técnica necesita 2-3 contactos). Recién después de validar Search.

### Palabras negativas obligatorias
`seace empleos`, `modelo`, `plantilla` (salvo lead magnet), `curso`, `pdf`, `que es`, `ministerio`, `manual`, `código fuente`, `trabajo`. Revisar el informe de términos de búsqueda cada semana.

## Tracking (GA4 + GTM + Google Ads)
1. **GA4**: propiedad existente (G-4PRW1QCW48, property 404642926). Data stream web instalado. Activar *enhanced measurement* (scroll, outbound) para diagnóstico.
2. **GTM**: cargar el contenedor (ya existe GTM en layouts). Tags:
   - Config GA4 (Measurement ID).
   - Eventos clave con nombre estándar:
     - `form_lead` — submit del formulario de landing/lead magnet (GTM, form submit).
     - `registro` — creación de cuenta (mejor: evento SERVER-SIDE desde backend PHP al crear usuario — datos de primera parte, sin bloqueadores).
     - `suscripcion_activa` — pago confirmado (evento desde backend al activar suscripción; enviar con `value` en soles para el valor de conversión).
3. **Unir pago con orgánico/pagado**: pasar `gclid` (auto-tagging de Google Ads) — GA4 une la sesión "cpc / google".
4. **Importar conversiones a Google Ads**: vincular GA4↔Ads e importar `registro` y `suscripcion_activa` como conversiones primarias para optimización de bidding (o usar el tag de conversión de Ads directamente para `suscripcion_activa`, que es la conversión de mayor valor).

## Métricas por etapa (dashboard semanal) y benchmarks
| Etapa | Métrica | Benchmark |
|---|---|---|
| Anuncio | CTR | Search B2B: 2–5% (bueno >4%) |
| Anuncio | Quality Score | ≥7 (baja CPC real) |
| Click → Landing | Rebote / engagement (scroll) | Rebote <50% en tráfico pago |
| Landing | Conversión a registro/trial | 5–15% (mediana global 4.3%, Unbounce) |
| Trial | Activación (1ª alerta abierta o 1 análisis) | >60% |
| Trial → Pago | Conversión trial→suscripción | 20–35% |
| Económica | CAC por plan / payback | CAC recuperado en <12 meses; LTV:CAC >3 |

Cálculo rápido de viabilidad: a S/1,000/mes y CPC real ~S/1.5–2.5 → 400–650 clics/mes. Con conversión landing 8–12% → 35–75 trials/mes. Con trial→pago 25% → 9–18 pagos/mes ≈ **S/450–1,200 MRR nuevos/mes** si se sostiene (validar con datos reales y recalibrar el modelo del PRONOSTICO_TRAFICO_REVENUE.md en documents/02-marketing-adquisicion/).

## Herramientas de diagnóstico
- **Microsoft Clarity** (gratis): heatmaps + grabaciones de sesión en las landings; detecta fricción (popups que tapan el form, CTA fuera de vista móvil). Caso de referencia: detectaron popup que tapaba el form → +72% acciones.
- GA4 Explorations: embudo anuncio→landing→registro→pago con canal "cpc / google".

## Ciclo de optimización
1. Establecer baseline 2 semanas (no tocar nada).
2. Hipótesis priorizadas con las 6 palancas del Lift Model: claridad, relevancia, valor, ansiedad, distracción, urgencia (en ese orden).
3. A/B test con significancia (calculadora CXL); implementar ganador; repetir.
4. Registrar aprendizajes en `documents/04-metricas-reportes/` (qué ganó, por qué, dato).

## Reglas de calidad
- Prohibido inventar métricas: todo número del reporte debe venir de GA4/Ads/BD.
- Las conversiones de valor (`suscripcion_activa`) deben enviarse desde backend (no depender del navegador).
- No escalar presupuesto hasta tener: CTR >2%, conversión landing >5%, trial→pago >20% (3 señales verdes).
