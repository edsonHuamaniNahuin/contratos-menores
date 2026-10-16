# 🔧 PLAN DE CAMBIOS Y REFACTORIZACIÓN DEL EMBUDO (15/10/2026)

> Basado en TODA la evidencia acumulada: 4 días de campañas (S/57), 500+ landings, heatmap first-party,
> Clarity (recién funcionando con grabaciones), 7 leads orgánicos, y el hallazgo clave:
> **el ÚNICO formulario que convierte tiene 2 campos + recompensa inmediata; los que no convierten tenían 4-5 campos + recompensa diferida.**

## ✅ EJECUTADO ESTA MADRUGADA (15/10 00:00–01:00)

| # | Cambio | Evidencia | Estado |
|---|---|---|---|
| 1 | **Formularios 5→3 campos** (fuera "Empresa" y "Rubro") en alertas/vigentes/software | Plantillas-tdr (2 campos) = 7 leads; los otros (4 campos + rubro) = 0 leads en ~500 visitas | ✅ deploy + audit 10/10 |
| 2 | **Botones → "Agendar mi demo gratis"** | Coherencia con oferta; "gratis" reduce fricción | ✅ |
| 3 | **CTA WhatsApp bajo cada formulario** ("¿Prefieres WhatsApp? Escríbenos aquí") | Únicos clics de intención del paid = WhatsApp (2), formularios = 0 | ✅ |
| 4 | **Clarity en TODO el embudo** (planes/register/login + landings) | Ceguera post-click había impedido diagnosticar | ✅ verificado |
| 5 | **Edad 40-60** en todos los brazos | Meta demografía: 45-64 = mejor CTR/CPC/landings | ✅ |

## 📅 PLAN POR FECHAS (evidencia → acción → criterio)

### Hoy 15/10
- 07:00: despiertan T4 + U1 (+J0 si Meta propaga la certificación)
- **Primer día con grabaciones completas de Clarity** → analizar: rage clicks, dónde
  abandonan, tiempo activo, grabaciones de sesiones del paid (referrer m.facebook)
- Medir: ¿el formulario de 3 campos convierte? (métrica: envíos form / landings)

### 16–17/10 — Refactor #2 guiado por GRABACIONES (no suposiciones)
- Revisar 10-20 grabaciones reales del tráfico pagado: identificar fricción exacta
  (velocidad de carga, sección que confunde, CTA invisible, formulario que no se encuentra)
- Fixes quirúrgicos correspondientes (con funnel-audit verde)

### Día 7 (18/10) — CHECKPOINT DURO
- Llamada por anuncio/brazo (fair-share ≥S/12): pausar el peor
- Rotar creativos (rot-alerta/rot-listado listos) + **creativo T4 v2 con persona**
  (el foto-documento rinde CTR 1.81% vs 2.0-2.5% de los de persona)
- Activar **campaña de retargeting** (audiencias VS-VisitantesWeb180d y VS-ClicsWA180d ya acumulan)
  con presupuesto reasignado del brazo pausado

### Semana 2 (19–25/10) — La apuesta grande (si CPL lo permite)
- **J1-refactor (recompensa inmediata para TODOS los clusters):** la landing de alertas
  regala algo instantáneo por correo — ej. *"Reporte semanal de convocatorias de tu rubro (gratis)"* —
  replicando el patrón del único formulario que convierte (plantillas). Requiere: backend de envío
  automático (1 plantilla email) + copy nuevo. **Propuesta formal para aprobación en el día 7.**
- Config B (geo-corredor Junín–Ucayali–Lima–LL–Arequipa) SOLO si T4/U1 muestran CPL viable
- Medir retargeting: ¿los recuperados convierten mejor? (ya nos conocen = más calientes)

### Backlog técnico paralelo
- **P-002**: eventos Contact/Lead del pixel no llegan a insights de Meta (impide optimizar a intención)
- Monitor 21:30: incluir T4/J0 en el resumen (hoy hardcodeado a la campaña original)

## 🚦 CRITERIOS DE CORTE (pre-comprometidos, sin autoengaño)
| Señal al 21/10 | Decisión |
|---|---|
| ≥1 lead de ads Y CPL ≤ S/12 | Mantener y escalar (con criterios de OBJETIVO_ROI) |
| 1+ leads pero CPL > S/12 | Reasignar 100% al brazo ganador + J1-refactor |
| **0 leads de ads con S/140+ gastados** | **Pausar paid**; todo al motor orgánico/plantillas; rediseño de oferta de entrada |

## 📌 Principio rector (grabado en bitácora)
**Cada cambio sale de un dato, no de una corazonada.** El refactor de formularios salió del único
formulario que convierte; el próximo sale de las grabaciones; ninguno sale de "creo que".
