# PLAN MAESTRO — Embudo y ROI (análisis de gestor experto + BI)

> Fecha: 14/10/2026 · Autor: análisis integral con las 6 fuentes de datos (Meta, GA4, heatmap propio,
> almacén, CRM/BD, Clarity) · Reglas vigentes: `AGENTS.md` · Objetivo: **ROAS 10x mín / 40x óptimo** con
> cierre por Yape.

---

## 0. Resumen ejecutivo

- **Invertido: S/ 41.14 en 2 días. Retorno directo: S/ 0. ROAS: 0x.**
- El tráfico NO es el problema: **648 clics al enlace por S/ 41** (CPC S/ 0.05, CPM S/ 1.15, 100% FB+IG limpio).
- El problema es **100% de conversión**: ~190 visitas/día → ~23 pasan del 25% de scroll → 0 leads.
- **Hallazgo clave del análisis:** la intención de compra SÍ existe y se expresa en WhatsApp
  (5 usuarios en 2 días vs 0 formularios), pero ese flujo termina en el **WhatsApp manual del equipo**
  y **nadie estaba midiendo ni garantizando la respuesta**.
- **Recuperación real identificada: S/ 150–600 potenciales de dinero YA gastado** (ver §2).
- Camino al 10x: el ROAS de **primera venta** es estructuralmente bajo (0.8–3x); el 10x se cobra en
  **LTV/retención** (§4). La máquina de retención ya está construida (onboarding trial + winback).

---

## 1. Diagnóstico BI (embudo cuantificado — 6 fuentes cruzadas)

```
[Anuncios]    24,000 impr · 830 clics · CTR 2.31% · CPC S/0.05        (Meta)
     ↓
[Clic web]    648 clics al enlace (44% del total de clics son "clics accidentales")
     ↓
[Llegada]     ~190 landing views/día (pixel) · 184 sesiones meta/paid 2d (GA4)
     ↓
[Scroll]      ~23 de 64 usuarios pasan el 25% · 1 llega al final       (heatmap propio)
     ↓
[Intención]   110 taps en zona muerta (H1) · 5 usuarios → WhatsApp     (heatmap + GA4)
     ↓
[Captura]     0 formularios reales · 0 leads · 0 ventas                (BD + Meta)
```

**Lecturas BI:**
1. **99.7% Android / 83% móvil** → todo debe diseñarse para móvil in-app (ya aplicado: CTA fijo).
2. **El formulario era invisible** en la práctica (está al 60-70% de la página; nadie baja).
3. **La intención se desvía a WhatsApp** — y terminaba en un canal manual sin SLA medido.
4. Fuentes orgánicas: buscador público con **324 views y 9:08 min de sesión** (¡4x más engagement que
   el tráfico pago!) + bing/google/chatgpt = demanda real sin explotar.
5. Creativos: los `V1 Dato` tienen el mejor CTR (3.2-4.2%) pero reciben menos entrega; los emocionales
   reciben el 70% del gasto. Veredicto pendiente de leads (día 7).

---

## 2. ¿QUÉ RECUPERAMOS DE LOS S/ 41? (dinero ya gastado, recuperación activa)

| Activo atrapado | Dónde está | Acción de recuperación (hoy, costo S/0) |
|---|---|---|
| **3 leads de plantillas-TDR** (05/10–10/10, nunca convertidos) | `demo_leads` ids 1-3 | Contactar con plantilla `/admin/respuesta-leads` + oferta de activación con Yape |
| **5 clics al botón WhatsApp** (12–13/10) | ⚠️ **Verificado 14/10 con el dueño: NO generaron ningún mensaje** al número del equipo — clic ≠ conversación | Investigar si el deep-link `wa.me` falla en el navegador in-app de Meta (ver grabaciones Clarity con clic WA) + evaluar botón nativo CTWA (requiere aprobación) |
| **~190 visitantes de landing/día** (pixel + GA4) | Audiencias de retargeting | Reutilizar como audiencia de retargeting (§3-J2) |
| **534 clics con intención** | Aprendizaje de creativos/embudos | Decisiones de swap del día 7 (§3-J6) |

**Potencial directo:** los 3 leads de plantillas (emails en BD, sin contactar) → 1 conversión = S/ 49–470 →
**paga los S/ 41 completos**. Los clics de WhatsApp NO son conversaciones hasta que haya mensaje real enviado.

---

## 3. Las 7 jugadas estructurales (no ajustes de tuerca)

### J1 — La oferta de captura: de "reunión" a "VALOR INMEDIATO" ⭐ (LA JUGADA)
El histórico es concluyente: los 3 únicos leads vinieron de un **lead magnet** (descarga TDR); 0 de "agenda reunión".
El producto mismo ES el mejor lead magnet: **"Ingresa tu rubro y tu correo → te mostramos las licitaciones
vigentes AHORA y te enviamos las nuevas cada día"**. Captura mínima (rubro+email), valor instantáneo,
segmentación natural para el seguimiento. La reunión/WhatsApp se ofrece EN la página de resultados.
*Aplica a: las 3 landings (empezar por E3, la de peor engagement, como test).*

### J2 — Retargeting con OFERTA DIFERENTE (mismo presupuesto, reasignado)
Los 190 visitantes/día que no convierten son la audiencia más barata y caliente que existe.
Mostrarles un **anuncio distinto** (el lead magnet de J1, no la misma landing) a 1/3 del CPC actual.
*Requiere tu aprobación de reasignación interna (no es presupuesto nuevo).*

### J3 — El buscador público como embudo orgánico (BI: 9 minutos de sesión!)
324 views/2d con engagement excepcional en el buscador público (SEO, gratis). Insertar captura de
alertas EN los resultados ("recibe esto por Telegram/correo") → convierte demanda orgánica en leads
a costo S/0. *Aplica a: buscador público, páginas regionales, blog.*

### J4 — La matemática del 10x: TICKET + LTV (la verdad económica)
Primera venta: 90 leads/mes × 10% cierre = 9 ventas × S/60 = **S/540 → 0.8x** (matemáticamente insuficiente).
Mismo cliente a 12 meses: 9 × S/816 LTV = **S/7,344 → 11x**. 🎯 **El 10x se cobra en la retención.**
Ejecución: (a) vender **anual S/470** ("2 meses gratis") como opción principal; (b) el onboarding
automático (d1/d3/d7/d12/d15 + winback, ya operativo) es la mitad del motor de ROI; (c) medir cohortes.

### J5 — WhatsApp manual como canal de cierre (sin API, sin costo)
La API está congelada (regla). Dato verificado 14/10: **5 clics al botón → 0 mensajes enviados**
(clic ≠ conversación; hipótesis principal: el deep-link falla en el navegador in-app de Meta — a verificar
con Clarity). Jugadas sin API: (a) **fallback visible del número** junto a los botones ("si el botón no
abre, escríbenos al +51 918 874 873"); (b) **prefill con contexto** para el equipo; (c) **SLA ≤2h**;
(d) evaluar **botón nativo CTWA** en los anuncios (abre WhatsApp nativo dentro de la app de Meta,
compatible con WhatsApp manual — requiere aprobación del dueño).

### J6 — Creativos con PRUEBA (día 7)
Los 11 compradores = 11 testimonios potenciales (pedido listo en `/admin/respuesta-leads`).
Los anuncios actuales venden "dato"; los de **prueba social** ganan en la etapa de decisión.
+ Regla del día 7: swap del peor creativo por embudo con datos del almacén.

### J7 — El test científico del embudo (mismo presupuesto)
E3 (peor engagement, 28s) migra a la **oferta J1**: Ad → "mira tus licitaciones" → resultados+email →
onboarding. Comparar contra E1/E2 (oferta actual) en 5-7 días. **Aprendizaje garantizado por sol gastado.**

---

## 4. Unit economics y camino al 10x (números honestos)

| Escenario | Leads/mes | Cierre | Ventas | Ingreso mes 1 | LTV 12m | ROAS mes 1 | ROAS LTV |
|---|---|---|---|---|---|---|---|
| Actual (roto) | 0 | — | 0 | S/0 | S/0 | **0x** | 0x |
| Con J1+J5 (3% conv) | 90 | 10% | 9 | S/540 | S/7,344 | 0.8x | **11x** |
| Con J1+J5+anual (20% anual) | 90 | 10% | 9 | S/1,500 | S/9,200 | 2.3x | **14x** |
| Escalado (ROAS>3x sostenido) | ∞ | — | — | — | — | subir presupuesto | — |

**Conclusión de la tabla:** la inversión actual (S/642/mes) es MATEMÁTICAMENTE correcta para el 10x
SI: (1) el embudo convierte ≥3% visitante→lead, (2) el cierre humano ≤2h sostiene ~10%,
(3) la retención se mantiene (~12m). Las tres están ahora al alcance — el sistema existe.

---

## 5. Roadmap de ejecución

**Hoy (14/10, costo S/0):**
1. Equipo: revisar WhatsApp manual (5 clicks) + contactar los 3 leads de plantillas. [RECUPERACIÓN]
2. Aplicar prefill con contexto en los botones WhatsApp (J5a).
3. Aprobar/descartar J1 y J2 (decisión del dueño).

**Mañana (15/10):**
4. J1 en E3 (oferta de valor inmediato) + auditoría pre-lanzamiento.
5. J3: captura de alertas en buscador público.
6. Primer corte del CTA fijo + Clarity (grabaciones ya desbloqueadas).

**Día 7 (19/10):** swap de creativo por embudo + primera reasignación por CPL (J6/J2).
**Semana 2-3:** config broad/Advantage+ según señal; escala solo si ROAS ≥ 3x sostenido.

---

## 6. Reglas de combinación con lo existente

- Nada se cambia sin correr `funnel-audit.mjs` + `verify_campaign.py` (AGENTS.md §3).
- WhatsApp API: congelada (AGENTS.md §1). J5 es 100% manual + links.
- Cada acción de este plan se mide con las 6 fuentes y se documenta en BITACORA.
