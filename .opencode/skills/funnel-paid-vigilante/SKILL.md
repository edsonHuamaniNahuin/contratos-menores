---
name: "funnel-paid-vigilante"
description: "Configurar y operar el embudo de trÃ¡fico PAGADO (Google Ads) + mediciÃ³n GA4/GTM para Vigilante SEACE (licitacionesmype.pe). Usar cuando se lancen campaÃ±as pagadas, se configure el tracking de conversiones del embudo (anuncio â†’ landing â†’ registro â†’ trial â†’ pago), se auditen mÃ©tricas por etapa (CTR, conversiÃ³n landing, trialâ†’pago, CAC) o se conecte Google Ads con GA4. Requiere que las landings existan (skill landing-vigilante) y el message-match anuncioâ†’landing."
license: MIT
metadata:
  version: 1.0.0
  category: marketing-vigilante
---

# S3 Â· Embudo con Google Ads + mediciÃ³n â€” Vigilante SEACE

## Principio rector
**Message match:** el anuncio es la introducciÃ³n; la landing continÃºa la misma historia (keyword/headline/CTA casi textuales). Mandar trÃ¡fico pagado a la home o a pÃ¡ginas genÃ©ricas baja el Quality Score â†’ menos impresiones y CPC mÃ¡s caro. Una landing dedicada por campaÃ±a/ad group (el skill landing-vigilante las produce).

## Estructura de cuenta Google Ads (presupuesto sugerido S/500â€“1,000/mes â‰ˆ US$130â€“260)
### CampaÃ±a 1 â€” Search "Alta intenciÃ³n" (conversiÃ³n directa, ~60% del presupuesto)
Ad groups por tema, 5-10 keywords cada uno, anuncios dedicados:
- "licitaciones por rubro": `[licitaciones obras pÃºblicas]`, "licitaciones de construcciÃ³n en PerÃº", `[convocatorias seace]`, "licitaciones vigentes", "buena pro seace".
- "contratos mayores / ley 32069": "contratos mayores a 8 uit", "licitaciÃ³n pÃºblica peru", "adjudicaciÃ³n simplificada".
- Marca: "vigilante seace", "licitacionesmype" (protege marca, CPC bajo).
Landing por ad group (software-licitaciones / monitoreo-licitaciones / alertas-licitaciones), CTA: trial 15 dÃ­as.

### CampaÃ±a 2 â€” Search "Lead magnet" (generaciÃ³n de leads, ~25%)
Keywords informativas: "cÃ³mo venderle al estado", "requisitos para ser proveedor del estado", "quÃ© es la RNP", "cÃ³mo postular a licitaciones" â†’ landing `/plantillas-tdr` (formulario email â†’ guÃ­a/modelos) â†’ nurture por email â†’ prueba gratuita.

### CampaÃ±a 3 â€” Remarketing (Display/YouTube, ~15%)
A quienes visitaron sin convertir (audiencia no tÃ©cnica necesita 2-3 contactos). ReciÃ©n despuÃ©s de validar Search.

### Palabras negativas obligatorias
`seace empleos`, `modelo`, `plantilla` (salvo lead magnet), `curso`, `pdf`, `que es`, `ministerio`, `manual`, `cÃ³digo fuente`, `trabajo`. Revisar el informe de tÃ©rminos de bÃºsqueda cada semana.

## Tracking (GA4 + GTM + Google Ads)
1. **GA4**: propiedad existente (G-4PRW1QCW48, property 404642926). Data stream web instalado. Activar *enhanced measurement* (scroll, outbound) para diagnÃ³stico.
2. **GTM**: cargar el contenedor (ya existe GTM en layouts). Tags:
   - Config GA4 (Measurement ID).
   - Eventos clave con nombre estÃ¡ndar:
     - `form_lead` â€” submit del formulario de landing/lead magnet (GTM, form submit).
     - `registro` â€” creaciÃ³n de cuenta (mejor: evento SERVER-SIDE desde backend PHP al crear usuario â€” datos de primera parte, sin bloqueadores).
     - `suscripcion_activa` â€” pago confirmado (evento desde backend al activar suscripciÃ³n; enviar con `value` en soles para el valor de conversiÃ³n).
3. **Unir pago con orgÃ¡nico/pagado**: pasar `gclid` (auto-tagging de Google Ads) â€” GA4 une la sesiÃ³n "cpc / google".
4. **Importar conversiones a Google Ads**: vincular GA4â†”Ads e importar `registro` y `suscripcion_activa` como conversiones primarias para optimizaciÃ³n de bidding (o usar el tag de conversiÃ³n de Ads directamente para `suscripcion_activa`, que es la conversiÃ³n de mayor valor).

## MÃ©tricas por etapa (dashboard semanal) y benchmarks
| Etapa | MÃ©trica | Benchmark |
|---|---|---|
| Anuncio | CTR | Search B2B: 2â€“5% (bueno >4%) |
| Anuncio | Quality Score | â‰¥7 (baja CPC real) |
| Click â†’ Landing | Rebote / engagement (scroll) | Rebote <50% en trÃ¡fico pago |
| Landing | ConversiÃ³n a registro/trial | 5â€“15% (mediana global 4.3%, Unbounce) |
| Trial | ActivaciÃ³n (1Âª alerta abierta o 1 anÃ¡lisis) | >60% |
| Trial â†’ Pago | ConversiÃ³n trialâ†’suscripciÃ³n | 20â€“35% |
| EconÃ³mica | CAC por plan / payback | CAC recuperado en <12 meses; LTV:CAC >3 |

CÃ¡lculo rÃ¡pido de viabilidad: a S/1,000/mes y CPC real ~S/1.5â€“2.5 â†’ 400â€“650 clics/mes. Con conversiÃ³n landing 8â€“12% â†’ 35â€“75 trials/mes. Con trialâ†’pago 25% â†’ 9â€“18 pagos/mes â‰ˆ **S/450â€“1,200 MRR nuevos/mes** si se sostiene (validar con datos reales y recalibrar el modelo del PRONOSTICO_TRAFICO_REVENUE.md).

## Herramientas de diagnÃ³stico
- **Microsoft Clarity** (gratis): heatmaps + grabaciones de sesiÃ³n en las landings; detecta fricciÃ³n (popups que tapan el form, CTA fuera de vista mÃ³vil). Caso de referencia: detectaron popup que tapaba el form â†’ +72% acciones.
- GA4 Explorations: embudo anuncioâ†’landingâ†’registroâ†’pago con canal "cpc / google".

## Ciclo de optimizaciÃ³n
1. Establecer baseline 2 semanas (no tocar nada).
2. HipÃ³tesis priorizadas con las 6 palancas del Lift Model: claridad, relevancia, valor, ansiedad, distracciÃ³n, urgencia (en ese orden).
3. A/B test con significancia (calculadora CXL); implementar ganador; repetir.
4. Registrar aprendizajes en `documents/marketing/` (quÃ© ganÃ³, por quÃ©, dato).

## Reglas de calidad
- Prohibido inventar mÃ©tricas: todo nÃºmero del reporte debe venir de GA4/Ads/BD.
- Las conversiones de valor (`suscripcion_activa`) deben enviarse desde backend (no depender del navegador).
- No escalar presupuesto hasta tener: CTR >2%, conversiÃ³n landing >5%, trialâ†’pago >20% (3 seÃ±ales verdes).
