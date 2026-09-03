---
name: "landing-vigilante"
description: "Crear o reescribir landing pages de alta conversión para Vigilante SEACE (licitacionesmype.pe). Usar cuando se construyan landings (ej. /software-licitaciones, /monitoreo-licitaciones, /alertas-licitaciones, /plantillas-tdr, /licitaciones-vigentes), secciones de la home o páginas de planes. Aplica: estructura de 9 secciones validada, message-match con anuncios pagados, frameworks de copy (PAS/AIDA/4U) en español peruano, reglas anti-IA con voz del cliente, CTA y formularios cortos, mobile-first (83% del tráfico es móvil). Requiere ejecutar antes el skill mercado-vigilante (o leer sus entregables en documents/03-clientes-investigacion/)."
license: MIT
metadata:
  version: 1.0.0
  category: marketing-vigilante
---

# S2 · Landing pages de alta conversión — Vigilante SEACE

## Contexto
- Audiencia: dueño/gerente de MYPE proveedora del Estado y equipos de Compras/Administración. No técnicos. Móvil primero.
- Oferta principal: trial gratuito de 15 días de alertas de licitaciones (WhatsApp/Telegram/email) + análisis IA del TDR.
- Planes: S/49/mes Premium · S/68/mes Premium + Contratos Mayores.
- Regla de oro: la landing es la continuación del anuncio. Si viene de Google Ads con la keyword "licitaciones de construcción", el headline debe repetir esa frase casi textual (message match) — caso real de referencia: landing dedicada por campaña redujo 80% el drop-off.

## Estructura de 9 secciones (validada por CXL/HubSpot/Unbounce)
1. **Hero (sin scroll, móvil):** headline = beneficio + keyword del anuncio; subheadline 1 línea; 3-5 bullets de beneficio; CTA visible; visual del PRODUCTO REAL (screenshot del panel de alertas o de un email/WhatsApp de alerta, NO stock photos). Hero corto: un caso pasó de 850→420px y subió conversión +19%.
2. **Señal de confianza (1 línea):** clientes/logos reales o cifra real ("Más de X empresas proveedoras reciben sus alertas aquí").
3. **Problema → Solución** (2-3 párrafos cortos, espejo del anuncio y de la VoC): nombrar el dolor con precisión y cifras ("¿Te enteraste tarde de una buena pro y se la llevó otro? El 60% de las buenas pros se adjudica a los primeros en postular").
4. **Cómo funciona (3 pasos):** 1. Crea tu perfil con tus rubros y regiones → 2. Recibe la alerta el mismo día en tu WhatsApp o correo → 3. Postula a tiempo con el TDR analizado por IA.
5. **Beneficios en lenguaje de beneficio (no feature):** "Alertas el mismo día de publicación" (no "scraping cada hora"); "La IA te dice si el TDR exige algo que no tienes" (no "análisis con Gemini").
6. **Prueba social con número:** 1-2 testimonios reales con nombre, cargo/empresa y resultado con cifra ("Ahora postulamos a 5 procesos al mes; ganamos 2 licitaciones en 6 meses"). Prohibido testimonio anónimo o inventado (ver skill mercado-vigilante).
7. **FAQ (acordeón) con objeciones REALES** (de entrevistas/cancelaciones): precio, cobertura de regiones/entidades, qué es RNP, "¿sirve si nunca he vendido al Estado?", cancelación.
8. **CTA repetida + garantía/riesgo cero:** "Prueba gratis 15 días, sin tarjeta" + "cancela cuando quieras".
9. **Footer mínimo:** privacidad, términos, contacto. Nada más.

**Omitir:** navegación del sitio, sliders, popups agresivos, video autoplay arriba del fold, logos sin contexto, formularios largos.

## Frameworks de copy (elegir según intención de la página)
- **PAS** (alta intención de compra — landings de planes/software): Problema → Agitación → Solución.
  Ej.: "Cada día se publican cientos de convocatorias en el SEACE. ¿Cuántas que calzan con tu negocio se te escapan? Enterarte tarde significa no presentar oferta. Vigilante SEACE te avisa por WhatsApp el mismo día con las de tu rubro y región."
- **AIDA** (audiencia fría / informacional): Atención con dato gancho ("Licitación de S/ 2.4M en obras publicada hoy en Arequipa") → Interés → Deseo ("imagina recibir esto filtrado para tu empresa") → Acción.
- **4U en headlines:** al menos 3 de: Útil, Urgente, Único, Ultra-específico. "Las convocatorias de tu rubro, el mismo día, antes que tu competencia" (único+específico). Evitar "Alertas de licitaciones en Perú" (genérico).

## Reglas anti-IA (obligatorias, pasada final con content-humanizer)
1. **Inyectar VoC textual:** frases literales de entrevistas/reviews como columna vertebral; nada de "herramienta integral de monitoreo" si el cliente dice "me avisa apenas sale".
2. **Matar muletillas:** "en el dinámico mundo actual", "solución innovadora", "revolucionario", "potenciar", "empoderar", "no solo… sino también", "asimismo", "en definitiva", listas donde todo empieza con infinitivo. Eliminar y reescribir.
3. **Variar longitud de frases:** mezclar de 3 a 30 palabras; preguntas retóricas; fragmentos; comillas de clientes.
4. **Especificidad local:** RUC, RNP, 8 UIT (S/44,000 en 2026), "buena pro", "bases integradas", soles, WhatsApp, regiones reales. Cuanto más específico y local, menos suena a plantilla global.
5. **Fraseo natural peruano:** contracciones, orden coloquial, sin conectores formales de más. Leer en voz alta: si suena a LinkedIn corporativo, reescribir.
6. Proceso: IA para estructura → inyectar VoC → 2 pasadas a mano (sonido, muletillas) → testar.

## CTA y formulario
- Botón con verbo específico: "Probar gratis 15 días", "Quiero mis alertas", "Ver licitaciones de mi rubro". CTA personalizadas convierten +202% (HubSpot). Nunca "Enviar"/"Registrarse" genérico.
- Formulario corto: email (+ opcional RUC). Un solo campo de más ya fuga conversión.
- Thank-you page con los siguientes pasos (revisa tu correo, activa tu WhatsApp enviando 'hola' al +51 998 294 604, mira este ejemplo de alerta).

## Checklist de QA (antes de publicar)
- [ ] Headline repite la keyword/idea del anuncio (message match) y tiene 3/4 de las U.
- [ ] CTA visible en el primer viewport móvil (hero < ~500px de alto).
- [ ] Cero muletillas de IA (pasada con content-humanizer).
- [ ] Testimonio y cifras reales (BD/GA4/VoC) — sin placeholders sin fuente.
- [ ] Formulario: máx. 2 campos + botón con verbo.
- [ ] Página sin menú de navegación del sitio (excepto privacidad/contacto).
- [ ] Carga <3s en móvil (revisar build de assets y tamaño de imágenes).
- [ ] Evento GA4 `form_lead`/`registro` disparando (verificar con funnel-paid-vigilante).
- [ ] FAQ contiene las 3 objeciones principales del mercado.

## Variantes por página objetivo
- `/software-licitaciones`: keyword "software licitaciones peru" · ángulo PAS · CTA a planes.
- `/monitoreo-licitaciones`: "monitoreo de licitaciones" · ángulo pérdida de oportunidades · CTA trial.
- `/alertas-licitaciones`: "alertas seace automaticas" · demo de la alerta (screenshot WhatsApp) · CTA trial.
- `/plantillas-tdr` (lead magnet): "plantillas tdr" · formulario email para descargar modelo Word · luego nurture.
- `/licitaciones-vigentes`: "licitaciones vigentes hoy" · feed real de procesos activos · CTA registro.
