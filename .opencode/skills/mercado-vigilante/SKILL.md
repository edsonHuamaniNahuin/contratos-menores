---
name: "mercado-vigilante"
description: "Investigación de mercado y audiencia para Vigilante SEACE (licitacionesmype.pe) ANTES de escribir cualquier landing, embudo o campaña. Usar cuando se necesite definir el mensaje que resuena con el proveedor peruano del Estado: entrevistas con voz del cliente (VoC), minería de reviews/foros/grupos, análisis de competidores, JTBD y objeciones reales. El objetivo es que el público objetivo se sienta identificado y que el copy use SU lenguaje, no el de una plantilla global. Activa este skill antes de crear landing pages, embudos o campañas pagadas."
license: MIT
metadata:
  version: 1.0.0
  category: marketing-vigilante
---

# S1 · Investigación de mercado y audiencia — Vigilante SEACE

## Por qué este skill existe
El copy convierte cuando usa la **voz del cliente (VoC)**: frases textuales reales ("me avisa apenas sale, antes me enteraba por un amigo o por casualidad"). La IA produce "claro y conciso"; la credibilidad sale de VoC: *real, único y detallado*. Este skill define el proceso para recolectar esa materia prima y entregar un brief de mensaje validado.

## Contexto del producto (para contextualizar las entrevistas)
- Producto: monitoreo automático de licitaciones SEACE (menores ≤8 UIT y mayores >8 UIT, Ley 32069) con alertas por WhatsApp/Telegram/email, análisis de TDR con IA, detección de direccionamiento, proforma en Word/Excel y vigilancia de buena pro.
- Planes: Gratis S/0 · Premium S/49/mes (S/470/año) · Premium + Contratos Mayores S/68/mes · Trial gratuito de 15 días.
- Audiencia primaria: dueños/gerentes de pequeñas empresas proveedoras del Estado (MYPE), administradores, compras; NO técnicos. Perú. Mayoría en móvil.
- Datos disponibles para citar: ~178 usuarios registrados, 7 suscriptores activos, tráfico +84% ago vs jul (GA4), registros +71%, `/buscador-publico` concentra 57% del tráfico con CTR bajo (~0.2% en GSC), `/planes` recibe ~100 views/mes (poco tráfico de compra).

## Paso 1 — Entrevistas (5-10, prioridad: clientes facturados > usuarios de trial > registrados)
Reclutar entre: los 7 suscriptores activos, los que compraron y cancelaron (entender el "por qué no"), trials que no convirtieron, y 2-3 prospects (ej. contactos de Honda del Perú S.A. — División Compras — para el caso corporativo). 20-30 min por llamada o WhatsApp. Preguntas obligatorias:

1. "Cuéntame cómo te enteras hoy de las licitaciones" → proceso actual, verbatim.
2. "¿Qué fue lo peor de perderte un proceso? ¿Cuánto te costó en plata o tiempo?" → dolor + cifras concretas.
3. "¿Qué pasó la primera vez que intentaste postular al Estado?" → fricción: RNP, garantías, documentos.
4. "¿Qué te haría pagar por esto cada mes?" → motivador de compra.
5. "¿Cómo se lo explicarías a un colega proveedor?" → lenguaje natural = material para headlines.
6. "¿Qué casi te impide contratar o te hizo dudar?" → objeciones → FAQ.
7. (Si es cliente activo) "¿Qué resultado concreto te dio el servicio? ¿Qué proceso ganaste o no perdiste?" → testimonio con número.

## Paso 2 — VoC de segunda mano (si faltan entrevistas)
- Reviews de apps/plataformas de licitaciones (Google Play, Capterra), grupos de Facebook de "proveedores del estado / licitaciones Perú", comentarios en publicaciones del OSCE/SEACE (LinkedIn, Facebook), foros.
- El propio soporte del sistema: tickets, chats, correos de soporte y motivos de cancelación de suscripciones (columna `cancellation_reason`).
- Busca frases que nombren: el dolor de enterarse tarde, la desconfianza, el "no sé por dónde empezar", el idioma RNP/RUC/UIT, y el momento de urgencia ("salió hoy, cierra en 3 días").

## Paso 3 — Análisis de competidores (qué prometen y qué omiten)
Lista: SEACE oficial (no compite en comodidad), apps/portales de alertas de licitaciones peruanos, consultoras. Para cada uno anotar: promesa principal (rapidez, filtros, WhatsApp, buena pro), a quién se dirigen, precio publicado (ancla), y lo que NO dicen (la brecha = nuestro ángulo).

## Paso 4 — JTBD (Jobs To Be Done)
Completar la plantilla con hallazgos reales:
- **Cuando** (contexto): "…estoy buscando crecer mi empresa y venderle al Estado…"
- **quiero** (motivación): "…encontrar convocatorias que calzan con mi rubro sin dedicarle horas…"
- **para** (resultado): "…para no depender de 2-3 clientes privados y tener flujo constante".

## Entregables (escribir en `docs/marketing/` del repo)
1. **Swipe file** de 20-50 frases verbatim (con la fuente) — será la materia prima del copy.
2. **Lista de objeciones** reales (mín. 8) con su respuesta — alimentará la sección FAQ de las landings.
3. **Promesa única en 1 frase** construida con palabras del cliente (ej. formato: "[Resultado] para [audiencia] sin [dolor]").
4. **Perfil de audiencia** (2: MYPE proveedora y empresa de Compras/Administración corporativa) con lenguaje, canales y objeciones.
5. **3 ángulos de mensaje** candidatos (dolor/ganancia/identidad) para testear en landings y anuncios.

## Reglas de calidad
- Prohibido inventar testimonios: si no hay VoC real, marcar la sección como "placeholder — requiere entrevista".
- Las cifras citadas deben venir de BD/GA4 o de la boca del cliente; nunca inventar métricas.
- Todo el proceso debe quedar documentado para que `landing-vigilante` lo consuma.
