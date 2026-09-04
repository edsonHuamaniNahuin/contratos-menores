# 🎯 3 EMBUDOS DE CAPTACIÓN + 3 CAMPAÑAS GOOGLE ADS — Vigilante SEACE

> **Fecha:** 02/09/2026 · **Estado:** declarado · **Autor:** estrategia de adquisición (S1-S3)
> **Propósito:** definir 3 embudos distintos, con objetivos distintos y ROI medible por embudo, porque se crearán **3 campañas de Google Ads** (una por embudo).

---

## 1. Decisión de modelo (contexto obligatorio)

- **NO se ofrece trial gratis de 15 días en las landings** (decisión del dueño). El trial existe solo dentro del registro orgánico de la app.
- La conversión principal de los 3 embudos es: **agendar una reunión/demo de 20 min por WhatsApp (+51 918 874 873)** para mostrar el sistema Premium en vivo, con procesos reales del rubro del prospecto.
- El CTA usa mensaje precargado de WhatsApp con **texto distinto por embudo** (para saber la fuente al recibir el lead).
- Fuente de keywords: `02-marketing-adquisicion/KEYWORDS_TRANSACCIONALES.md` (Nivel 1 = intención de compra) e `INVESTIGACION_KEYWORDS_NUEVOS_CLUSTERS.md`.

---

## 2. Los 3 embudos — modelo **Lead → Reunión → Venta**

> Cada embudo tiene un **macro-objetivo DISTINTO**, según la madurez del prospecto que llega por su keyword. Cada campaña se mide con su propia métrica: no se mezclan.

| # | Embudo | Landing | Keywords comerciales objetivo | **Macro-objetivo DISTINTO** | Audiencia |
|---|---|---|---|---|---|
| **E1** | **Alertas SEACE** (caliente) | `/alertas-licitaciones` ✅ construida | `alertas licitaciones`, `alertas licitaciones publicas`, `alertas seace`, `monitoreo de licitaciones` | 🟢 **VENTA**: reunión exprés con cierre de pago el mismo día (o compra directa) | Contratista que YA pierde procesos y lo sabe; intención máxima |
| **E2** | **Software/plataforma** (tibio) | `/software-licitaciones` ✅ construida | `software licitaciones publicas`, `software seace`, `software para licitaciones`, `sistema de licitaciones`, `plataforma licitaciones` | 🟡 **REUNIÓN**: agendar demo de 20 min (venta posterior en pipeline) | Empresa que busca comprar/adoptar una herramienta; compara opciones |
| **E3** | **Licitaciones vigentes hoy** (frío) | `/licitaciones-vigentes` ✅ construida | `licitaciones vigentes hoy`, `licitaciones publicas peru`, `convocatorias vigentes`, `licitaciones del estado peruano` | 🔵 **LEAD**: iniciar WhatsApp sin compromiso (prospecto crudo) | Audiencia amplia fría que busca oportunidades de negocio con el Estado |

### Por qué objetivos distintos (no canibalizan)
- **E1 = VENTA** (el que llega aquí ya decidió que necesita alertas). Mensaje: "te enteras tarde y pierdes" → la reunión es para confirmar y **cobrar ese día**.
- **E2 = REUNIÓN** (está evaluando herramienta). Mensaje: "la herramienta que monitorea, analiza TDR con IA y arma tu proforma" → la reunión es para **convertir la evaluación en venta** con follow-up.
- **E3 = LEAD** (descubrimiento: qué hay HOY). Mensaje: data real del SEACE ("esto se publicó esta semana") → captura **prospectos crudos** que se nutren después hacia E2/E1.

---

## 3. Las 3 campañas Google Ads (Search)

| Campaña | Embudo | Landing destino | Estructura de anuncios |
|---|---|---|---|
| **`VS-Alertas`** | E1 | `/alertas-licitaciones` | 2-3 grupos por micro-cluster (alertas licitaciones / alertas seace / monitoreo) |
| **`VS-Software`** | E2 | `/software-licitaciones` | Grupos: software licitaciones / software seace / sistema+plataforma |
| **`VS-LicitacionesVigentes`** | E3 | `/licitaciones-vigentes` | Grupos: licitaciones vigentes / convocatorias / oportunidades negocio |

- Regla de oro: **message match** — el headline del anuncio repite la keyword exacta y la landing repite esa misma frase en el H1 (skill S2).
- Presupuesto de arranque sugerido: **S/ 500-1000/mes total** (S/ 250-350 por campaña), ajustable por ROAS.

---

## 4. Medición de ROI por embudo

### 4.1 Rastreo de la conversión
Cada embudo tiene **su propia acción de conversión y su propio mensaje de WhatsApp** (para atribuir el lead a la campaña):

```
E1 (VENTA): "Hola, vi las alertas de licitaciones. Quiero agendar una reunión para contratar el plan."
E2 (REUNIÓN): "Hola, vi el software de licitaciones y quiero verlo en una demo de 20 minutos."
E3 (LEAD): "Hola, vi las licitaciones vigentes. Me interesa saber más."
```

Parámetros UTM estándar (Google Ads los agrega automáticamente):
`utm_source=google&utm_medium=cpc&utm_campaign=VS-Alertas|VS-Software|VS-LicitacionesVigentes`

### 4.2 Métricas por embudo (cada campaña se mide con SU métrica principal)

| Embudo | Macro-objetivo | Métrica PRINCIPAL | Métricas secundarias |
|---|---|---|---|
| **E1 Alertas** | VENTA | **Ventas cerradas + ROAS** (gasto ÷ ventas) | CAC, costo por reunión, % de reuniones que pagan el mismo día |
| **E2 Software** | REUNIÓN | **CPR — costo por reunión agendada** | Tasa reunión→venta, CAC, tiempo a cierre |
| **E3 Vigentes** | LEAD | **CPL — costo por lead (WhatsApp iniciado)** | % de leads que avanzan a reunión, calidad del lead |

| Métrica | Definición |
|---|---|
| CPC / CTR | Costo por clic y % de clics de cada campaña |
| **CPL (costo por lead)** | Gasto campaña ÷ leads iniciados (macro E3) |
| **CPR (costo por reunión)** | Gasto campaña ÷ reuniones agendadas (macro E2; secundario en E1/E3) |
| Tasa de cierre | Reuniones que terminan en pago Premium (seguimiento manual en pipeline) |
| **CAC final** | Gasto campaña ÷ clientes nuevos cerrados desde esa campaña |
| **LTV medio** | Mensualidad promedio (S/ 49 / S/ 68) × meses de permanencia |
| **ROAS** | (Clientes × LTV) ÷ inversión de la campaña — el número que decide escalar o cortar |

Pipeline manual: cada reunión se registra con su campaña de origen (hoja/CRM) → demo → propuesta → pago → se atribuye el cliente a su embudo.

### 4.3 Qué decide qué (reglas de decisión)
- **E1**: se juzga por ventas y ROAS directo. Si no cierra el mismo día → revisar guion de la reunión, no la campaña.
- **E2**: se juzga por CPR < valor de 1 cliente probable. Si las reuniones no convierten → revisar mensaje/landing.
- **E3**: se juzga por CPL bajo y por cuántos leads avanzan a reunión (nurture). Es el embudo de volumen (top of funnel) que alimenta E2/E1.

---

## 5. Roadmap de ejecución

- [x] E1 `/alertas-licitaciones` — **construida** (02/09). CTA y landing listos para objetivo VENTA
- [x] E2 `/software-licitaciones` — **construida** (02/09, identidad B2B con entregables reales Word/Excel/IA). Objetivo REUNIÓN
- [x] E3 `/licitaciones-vigentes` — **construida** (02/09, feed de procesos reales CONVOCADO + banda fotográfica). Objetivo LEAD
- [x] Mensajes precargados de WhatsApp por embudo (E1 venta / E2 reunión / E3 lead) + **formulario de correo** en las 3 landings
- [x] **Captura de leads por correo con antibot/spam**: honeypot + tiempo mínimo de llenado + rate-limit por IP (3/10 min) + blacklist de correos desechables + **captcha dinámico por sesión** (se regenera en cada carga). Guardado en BD `demo_leads` (landing de origen, UTM/referrer, IP) + email a services@sunqupacha.com
- [ ] Configurar las 3 campañas en Google Ads con keywords Nivel 1 y negativos (SEACE oficial, empleo, México/otros países)
- [ ] Primera medición mensual → `04-metricas-reportes/` con CPR, CAC y ROAS reales (los leads ya quedan registrados en `demo_leads` con su landing)

---

## 6. Fuentes y coherencia

- Keywords: `KEYWORDS_TRANSACCIONALES.md`, `INVESTIGACION_KEYWORDS_NUEVOS_CLUSTERS.md`, `BRECHA_KEYWORDS_DATOS_REALES.md`
- Diseño de landings: skill `.opencode/skills/landing-vigilante/SKILL.md` (estructura 9 secciones, message match, sin testimonios inventados)
- Lógica de funnel pagado: skill `.opencode/skills/funnel-paid-vigilante/SKILL.md`
- Auditoría CRO por página: skill `page-cro` (score objetivo ≥ 80/100 antes de activar campaña)
