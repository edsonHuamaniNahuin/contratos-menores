# Respuesta a leads (correo + WhatsApp)

> Material listo para responder cuando un cliente pide información.
> Aplica a leads de: landings de campaña (E1/E2/E3), form de demo y plantilla TDR.

---

## 1. Flujo y tiempos

1. El lead completa el formulario → llega **aviso interno a `services@sunqupacha.com`** (nombre, correo, WhatsApp si lo dejó, landing y anuncio de origen vía UTM).
2. Responder en **≤ 2 horas hábiles** (L–V 8:00–18:00):
   - **Por WhatsApp** (rápido, adjuntando el flyer) si dejó teléfono o si llegó por campaña.
   - **Por correo** con la plantilla si solo dejó email.
3. Objetivo del primer contacto: **agendar demo de 20 min** y cerrar **activación directa con Yape** (5 días de garantía).

---

## 2. Correo — plantilla oficial

### Opción A (recomendada): enviar la plantilla desde el servidor

```bash
ssh root@161.132.4.111
cd /var/www/vigilante-seace
sudo -u www-data env HOME=/tmp XDG_CONFIG_HOME=/tmp php artisan lead:responder CORREO_DEL_LEAD --nombre="Juan Pérez"
```

- Llega **branded** (misma línea visual que el correo de bienvenida), con botones a registro y WhatsApp.
- Sale con `replyTo: services@sunqupacha.com` — si el lead responde, cae en la bandeja del equipo.

### Opción B: copiar/pegar (texto plano)

**Asunto:** Vigilante SEACE — así te avisamos de las licitaciones de tu rubro

```
Hola {nombre} 👋

Gracias por tu interés en Vigilante SEACE. Te resumo cómo ayudamos a proveedores del Estado:

🔔 Alertas el mismo día: convocatorias de tu rubro directo a WhatsApp, Telegram o correo, apenas el Estado las publica.
🤖 Análisis de TDR con IA: la IA lee las bases y te dice si calificas, antes de invertir en postular.
🔍 Contratos menores y mayores: buscador completo con entidades, montos y estados.
📊 Seguimiento de tus procesos: plazos y documentos siempre a la vista.

Actívalo hoy y tienes 5 días de garantía: si no te convence, te devolvemos tu dinero: https://licitacionesmype.pe/register

¿Prefieres una demo de 20 minutos? Responde a este correo o escríbenos al WhatsApp +51 918 874 873.

Saludos,
Equipo Vigilante SEACE
Sunqupacha S.A.C. · licitacionesmype.pe
Servicio privado e independiente, no afiliado al OSCE ni al Estado peruano.
```

---

## 3. WhatsApp — mensaje + flyer

**Flyer (adjuntar):** `public/images/wsp/vigilante-info-1350-v2.png`
→ URL pública: `https://licitacionesmype.pe/images/wsp/vigilante-info-1350-v2.png`

**Mensaje plantilla:**

```
Hola {nombre} 👋 Gracias por escribir a Vigilante SEACE.

Te comparto cómo funciona:
• Te avisamos el mismo día cuando el Estado publica una licitación de tu rubro (WhatsApp, Telegram o correo).
• La IA analiza el TDR y te dice si calificas antes de postular.
• Incluye buscador de contratos menores y mayores + seguimiento de procesos.

Actívalo hoy y tienes 5 días de garantía: si no te convence, te devolvemos tu dinero. Crea tu cuenta aquí: https://licitacionesmype.pe/register

¿Te muestro el sistema en una demo rápida de 20 minutos? ¿Qué día te queda bien?
```

**Variantes por caso:**

| El lead pregunta… | Ajuste del mensaje |
|---|---|
| "¿Cuánto cuesta?" | Añadir: "Los planes van desde S/ 49 al mes, y al activar tienes 5 días de garantía: si no te convence, te devolvemos tu dinero." |
| "¿Cómo me llegan las alertas?" | Añadir: "Eliges canal (WhatsApp, Telegram o correo) y las palabras clave de tu rubro en Configuración de Alertas." |
| "¿Sirve para mi rubro/región?" | Añadir: "Sí — filtra por rubro y por departamento/provincia/distrito. Dime tu rubro y te muestro licitaciones de muestra." |

---

## 4. Reglas de respuesta

- Personalizar siempre con el **nombre** del lead (no copiar-pegar genérico).
- Nombrar lo que pidió (rubro, zona) → señal de que leímos su mensaje.
- 1 solo CTA por mensaje: **demo 20 min** o **registro gratis**.
- Nunca prometer adjudicaciones ni "contactos" en entidades (compliance).
- Si no responde en 48h: seguimiento corto (1 recordatorio) y luego pausar.
