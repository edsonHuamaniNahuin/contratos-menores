# 🎯 CONFIGURACIÓN GOOGLE ADS — 3 CAMPAÑAS (E1/E2/E3)
## Vigilante SEACE · licitacionesmype.pe · Preparado 02/09/2026

> Documento operativo para crear las campañas en Google Ads.
> Fuentes: `KEYWORDS_TRANSACCIONALES.md` (Nivel 1), `EMBUDOS_3_CAMPANAS.md`, datos GSC/autocomplete reales.

---

## 0. Antes de crear (requisitos)
- [ ] Conversión GA4 por embudo creada y verificada (eventos ya enviados desde las landings):
  - `lead_whatsapp_click` (parámetro `embudo` = E1/E2/E3) → marcar como **conversión** en GA4
  - `demo_lead_enviado` (parámetro `embudo`) → marcar como **conversión**
  - Importar las 2 conversiones GA4 a Google Ads (Tools → Conversions → Google Analytics 4)
- [ ] Verificar que las landings carguen en móvil (<3 s) y con el build de producción actualizado.
- [ ] Conectar Google Ads ↔ GA4 (auto-tagging ON).

---

## 1. Estructura general (las 3 campañas)

| Parámetro | Valor |
|---|---|
| Tipo de campaña | **Search (Búsqueda)** |
| Estrategia de puja | **Conversiones** (tCPA si hay ≥15 convs/30d; si no, Maximizar conversiones) |
| Idioma | Español (todos) |
| Ubicación | Perú (excluir zonas sin servicio si aplica) |
| Presupuesto inicial | **S/ 250–350 / día? NO: S/ 250–350 / mes por campaña** (~S/ 900/mes total) |
| Red | Solo Google Search (sin Display inicial) |
| Horario | Sin restricción al inicio (ver datos de reuniones) |
| Programación | Fecha de inicio: al activar (verificar conversiones 48h antes de escalar) |

**Nota presupuesto:** si el dueño confirma más presupuesto (S/ 500–1000/mes), escalar a S/ 300+/campaña/mes.

---

## 2. Campaña E1 `VS-Alertas` → /alertas-licitaciones (objetivo VENTA)

### Grupos de anuncios y keywords (phrase/broad modificado)
| Grupo | Keywords (intención compra, Nivel 1) |
|---|---|
| GA-1 Alertas | "alertas licitaciones", "alertas licitaciones publicas", "alertas seace", "alertas seace automaticas" |
| GA-2 Monitoreo | "monitoreo de licitaciones", "seguimiento de licitaciones", "monitoreo licitaciones publicas" |
| GA-3 Avisos | "alertas de licitaciones", "recibir alertas de licitaciones", "avisos de licitaciones peru" |

### Keywords negativas (globales E1)
- seace.gob.pe, portal oficial, buscar en el seace, seace buscador (sin marca), ley 32069 pdf, cursos, empleo, trabajo, convocatoria cas, méxico, colombia, chile, españa, gratis sin (excepto si se usa trial)

### Anuncios (RSA)
- H1: "Alertas de Licitaciones Públicas en Perú" · "¿Se te escapan las buenas pros?" · "Alertas SEACE el mismo día"
- Desc: "Recibe en WhatsApp solo los procesos de tu rubro. Entérate el mismo día de publicación. Agenda una reunión sin compromiso."
- Página final: `/alertas-licitaciones`

---

## 3. Campaña E2 `VS-Software` → /software-licitaciones (objetivo REUNIÓN)

| Grupo | Keywords |
|---|---|
| GS-1 Software | "software licitaciones publicas", "software licitaciones peru", "software para licitaciones", "software de licitaciones" |
| GS-2 Software SEACE/OSCE | "software seace", "software osce", "software para hacer licitaciones" |
| GS-3 Sistema/plataforma | "sistema de licitaciones", "sistema de licitaciones publicas", "plataforma de licitaciones", "plataforma para ver licitaciones publicas" |

### Negativas: las de E1 + "gratis", "open source", "curso", "capacitación", "descargar"

### Anuncios (RSA)
- H1: "Software de Licitaciones Públicas Perú" · "Gestiona licitaciones con IA" · "Del SEACE a tu proforma"
- Desc: "Monitoreo 24/7, análisis de TDR con IA, score de compatibilidad y proformas en Word/Excel. Agenda una demo de 20 min."
- Página final: `/software-licitaciones`

---

## 4. Campaña E3 `VS-LicitacionesVigentes` → /licitaciones-vigentes (objetivo LEAD — volumen)

| Grupo | Keywords |
|---|---|
| GV-1 Vigentes | "licitaciones vigentes", "licitaciones vigentes hoy", "licitaciones publicas vigentes" |
| GV-2 Convocatorias | "convocatorias vigentes", "convocatorias publicas peru", "convocatorias seace" |
| GV-3 Oportunidades | "oportunidades de negocio con el estado", "licitaciones del estado peruano", "licitaciones en peru" |

### Negativas: las de E1 (portal oficial / empleo / otros países)

### Anuncios (RSA)
- H1: "Licitaciones Públicas Vigentes en Perú" · "Convocatorias del SEACE hoy" · "¿Qué licita el Estado hoy?"
- Desc: "Procesos reales en convocatoria, actualizados con el SEACE. Recibe aviso cuando publiquen algo de tu rubro. Sin costo por informarte."
- Página final: `/licitaciones-vigentes`

---

## 5. Extensiones y assets (las 3)
- **Sitelinks:** /licitaciones-vigentes · /plantillas-tdr · /planes · /buscador-contratos-mayores
- **Llamada:** +51 918 874 873
- **Mensaje (WhatsApp):** botón de mensaje → wa.me/51918874873 con texto precargado del embudo
- **Imagen de logo:** favicon/logo Vigilante SEACE (si se habilita visual assets)

---

## 6. Medición y reglas (resumen de EMBUDOS_3_CAMPANAS.md)
| Embudo | Macro-objetivo | Métrica principal | Regla de decisión |
|---|---|---|---|
| E1 | VENTA | ROAS directo | No cierra → revisar guion, no campaña |
| E2 | REUNIÓN | CPR | CPR > valor cliente → revisar landing/mensaje |
| E3 | LEAD | CPL | CPL alto o leads que no avanzan → ajustar KW/negativas |

- Los leads por correo quedan en BD `demo_leads` (columna `landing` E1/E2/E3/plantillas-tdr) → reporte mensual a `04-metricas-reportes/`.
- Los leads por WhatsApp se atribuyen por el texto precargado del mensaje (distinto por embudo).

---

## 7. Checklist de lanzamiento
- [ ] Importar conversiones GA4 a Google Ads
- [ ] Crear 3 campañas con keywords de las secciones 2-4 (usar importador masivo / editor)
- [ ] Verificar auto-tagging y parámetros `utm_campaign`
- [ ] Lanzar con presupuesto bajo 3-5 días (fase de aprendizaje)
- [ ] Revisar búsquedas reales a los 7 días → añadir negativas nuevas
- [ ] Primer reporte mensual (CPL/CPR/CAC/ROAS) → `documents/04-metricas-reportes/`
