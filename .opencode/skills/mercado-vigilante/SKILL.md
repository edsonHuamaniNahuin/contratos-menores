---
name: "mercado-vigilante"
description: "InvestigaciÃ³n de mercado y audiencia para Vigilante SEACE (licitacionesmype.pe) ANTES de escribir cualquier landing, embudo o campaÃ±a. Usar cuando se necesite definir el mensaje que resuena con el proveedor peruano del Estado: entrevistas con voz del cliente (VoC), minerÃ­a de reviews/foros/grupos, anÃ¡lisis de competidores, JTBD y objeciones reales. El objetivo es que el pÃºblico objetivo se sienta identificado y que el copy use SU lenguaje, no el de una plantilla global. Activa este skill antes de crear landing pages, embudos o campaÃ±as pagadas."
license: MIT
metadata:
  version: 1.0.0
  category: marketing-vigilante
---

# S1 Â· InvestigaciÃ³n de mercado y audiencia â€” Vigilante SEACE

## Por quÃ© este skill existe
El copy convierte cuando usa la **voz del cliente (VoC)**: frases textuales reales ("me avisa apenas sale, antes me enteraba por un amigo o por casualidad"). La IA produce "claro y conciso"; la credibilidad sale de VoC: *real, Ãºnico y detallado*. Este skill define el proceso para recolectar esa materia prima y entregar un brief de mensaje validado.

## Contexto del producto (para contextualizar las entrevistas)
- Producto: monitoreo automÃ¡tico de licitaciones SEACE (menores â‰¤8 UIT y mayores >8 UIT, Ley 32069) con alertas por WhatsApp/Telegram/email, anÃ¡lisis de TDR con IA, detecciÃ³n de direccionamiento, proforma en Word/Excel y vigilancia de buena pro.
- Planes: Gratis S/0 Â· Premium S/49/mes (S/470/aÃ±o) Â· Premium + Contratos Mayores S/68/mes Â· Trial gratuito de 15 dÃ­as.
- Audiencia primaria: dueÃ±os/gerentes de pequeÃ±as empresas proveedoras del Estado (MYPE), administradores, compras; NO tÃ©cnicos. PerÃº. MayorÃ­a en mÃ³vil.
- Datos disponibles para citar: ~178 usuarios registrados, 7 suscriptores activos, trÃ¡fico +84% ago vs jul (GA4), registros +71%, `/buscador-publico` concentra 57% del trÃ¡fico con CTR bajo (~0.2% en GSC), `/planes` recibe ~100 views/mes (poco trÃ¡fico de compra).

## Paso 1 â€” Entrevistas (5-10, prioridad: clientes facturados > usuarios de trial > registrados)
Reclutar entre: los 7 suscriptores activos, los que compraron y cancelaron (entender el "por quÃ© no"), trials que no convirtieron, y 2-3 prospects (ej. contactos de Honda del PerÃº S.A. â€” DivisiÃ³n Compras â€” para el caso corporativo). 20-30 min por llamada o WhatsApp. Preguntas obligatorias:

1. "CuÃ©ntame cÃ³mo te enteras hoy de las licitaciones" â†’ proceso actual, verbatim.
2. "Â¿QuÃ© fue lo peor de perderte un proceso? Â¿CuÃ¡nto te costÃ³ en plata o tiempo?" â†’ dolor + cifras concretas.
3. "Â¿QuÃ© pasÃ³ la primera vez que intentaste postular al Estado?" â†’ fricciÃ³n: RNP, garantÃ­as, documentos.
4. "Â¿QuÃ© te harÃ­a pagar por esto cada mes?" â†’ motivador de compra.
5. "Â¿CÃ³mo se lo explicarÃ­as a un colega proveedor?" â†’ lenguaje natural = material para headlines.
6. "Â¿QuÃ© casi te impide contratar o te hizo dudar?" â†’ objeciones â†’ FAQ.
7. (Si es cliente activo) "Â¿QuÃ© resultado concreto te dio el servicio? Â¿QuÃ© proceso ganaste o no perdiste?" â†’ testimonio con nÃºmero.

## Paso 2 â€” VoC de segunda mano (si faltan entrevistas)
- Reviews de apps/plataformas de licitaciones (Google Play, Capterra), grupos de Facebook de "proveedores del estado / licitaciones PerÃº", comentarios en publicaciones del OSCE/SEACE (LinkedIn, Facebook), foros.
- El propio soporte del sistema: tickets, chats, correos de soporte y motivos de cancelaciÃ³n de suscripciones (columna `cancellation_reason`).
- Busca frases que nombren: el dolor de enterarse tarde, la desconfianza, el "no sÃ© por dÃ³nde empezar", el idioma RNP/RUC/UIT, y el momento de urgencia ("saliÃ³ hoy, cierra en 3 dÃ­as").

## Paso 3 â€” AnÃ¡lisis de competidores (quÃ© prometen y quÃ© omiten)
Lista: SEACE oficial (no compite en comodidad), apps/portales de alertas de licitaciones peruanos, consultoras. Para cada uno anotar: promesa principal (rapidez, filtros, WhatsApp, buena pro), a quiÃ©n se dirigen, precio publicado (ancla), y lo que NO dicen (la brecha = nuestro Ã¡ngulo).

## Paso 4 â€” JTBD (Jobs To Be Done)
Completar la plantilla con hallazgos reales:
- **Cuando** (contexto): "â€¦estoy buscando crecer mi empresa y venderle al Estadoâ€¦"
- **quiero** (motivaciÃ³n): "â€¦encontrar convocatorias que calzan con mi rubro sin dedicarle horasâ€¦"
- **para** (resultado): "â€¦para no depender de 2-3 clientes privados y tener flujo constante".

## Entregables (escribir en `documents/marketing/` del repo)
1. **Swipe file** de 20-50 frases verbatim (con la fuente) â€” serÃ¡ la materia prima del copy.
2. **Lista de objeciones** reales (mÃ­n. 8) con su respuesta â€” alimentarÃ¡ la secciÃ³n FAQ de las landings.
3. **Promesa Ãºnica en 1 frase** construida con palabras del cliente (ej. formato: "[Resultado] para [audiencia] sin [dolor]").
4. **Perfil de audiencia** (2: MYPE proveedora y empresa de Compras/AdministraciÃ³n corporativa) con lenguaje, canales y objeciones.
5. **3 Ã¡ngulos de mensaje** candidatos (dolor/ganancia/identidad) para testear en landings y anuncios.

## Reglas de calidad
- Prohibido inventar testimonios: si no hay VoC real, marcar la secciÃ³n como "placeholder â€” requiere entrevista".
- Las cifras citadas deben venir de BD/GA4 o de la boca del cliente; nunca inventar mÃ©tricas.
- Todo el proceso debe quedar documentado para que `landing-vigilante` lo consuma.
