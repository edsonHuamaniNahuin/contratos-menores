# Objetivo de ROI — Campañas Meta (Yape como cierre)

> Definido por dirección (12/10/2026). Toda inversión publicitaria debe devolver **10x como mínimo y 40x como óptimo**.
> El cierre comercial es **Yape** → se aprueba en `/admin/pagos-yape`.

---

## 1. Presupuesto y metas en soles

| Concepto | Valor |
|---|---|
| Presupuesto aprobado | S/ 50 por campaña/semana → **S/ 150/semana** |
| Equivalente diario | S/ 21.43 (S/ 7 por ad set) |
| Equivalente mensual | **S/ 642** (4.28 semanas) |
| **Meta mínima (10x)** | **S/ 6,420 de ingreso/mes** |
| **Meta óptima (40x)** | **S/ 25,680 de ingreso/mes** |

## 2. La matemática del embudo (por qué 10x/40x)

ROAS = ingreso total ÷ gasto = **ingreso por lead ÷ CPL** (los leads se cancelan).

Con **CPL objetivo S/ 7.50** (alcanzable con CPC ~S/0.60 y 8% de conversión de landing):

| Plan | Ticket | LTV estimado | Conversión lead→pago necesaria para 10x | para 40x |
|---|---|---|---|---|
| Mensual | S/ 49/mes | S/ 294 (6 meses) | **25.5%** | 102% (imposible) |
| Anual | S/ 470 | S/ 470 | **16.0%** | 64% |
| Premium (mayores) | S/ 68/mes | S/ 816 (12 meses) | **9.2%** | 37% |

**Conclusión fría:** el 10x solo se alcanza si **(a)** el CPL se mantiene ≤ S/ 8, **(b)** el cierre empuja el **plan anual (S/ 470) o premium mayorista**, y **(c)** la conversión lead→pago sube a 10–16%. El 40x exige 37% de conversión sobre premium (retención 12m) — se persigue, pero primero aseguremos 10x sostenido.

> Hoy la conversión lead→pago es ≈0–2% (funnel gotea). **El ROI no se arregla en el anuncio; se arregla en el cierre y el onboarding.**

## 3. Palancas (en orden de impacto)

1. **Lead → pago (la palanca #1)**
   - Seguimiento a cada lead en ≤ 2 horas (plantilla + flyer ya listos en `/admin/respuesta-leads`).
   - Onboarding día 1: la primera alerta útil debe llegar el mismo día (activa el valor).
   - Empujar la **demo de 20 min** como paso previo al Yape.
2. **Ticket**
   - En el cierre ofrecer **anual S/ 470** (mejor cash y mejor LTV) y **premium S/ 68** como plan natural.
3. **CPL**
   - Semana 2–3: pasar a configuración broad/Advantage+ si los intereses limitan (era Andromeda).
   - Rotar creativos por la regla de día 7 (swap de la peor versión por embudo).
4. **Velocidad del Yape**
   - Aprobar en `/admin/pagos-yape` en minutos, no horas. El monitor ya **alerta si un pago lleva +2h pendiente**.

## 4. Instrumentación (ya activa)

- `meta:monitor` (10/13/16/19 + resumen 21:30 Telegram): incluye **Yape hoy, Yape del mes (y atribuibles a campaña), gasto del mes, ROAS del mes** y alerta si ROAS mensual < 10x (desde el día 7) o Yapes pendientes +2h.
- `meta:ingerir` cada hora guarda el gasto por anuncio; cierre diario 08:15 → base para el ROAS exacto.
- Atribución de ventas a campaña: usuario del pago (`pago_yapes.user_id`) cuyo email coincide con un lead de `demo_leads` con `utm_source=meta`.

## 5. Reglas de decisión semanal (lunes)

| Señal | Acción |
|---|---|
| ROAS < 1x | Pausar incrementos de presupuesto; arreglar funnel (onboarding/cierre) antes de gastar más |
| ROAS 1–5x | Mantener presupuesto; optimizar conversión y ticket |
| ROAS 5–10x | Escalar +20% de presupuesto (sin tocar segmentación) |
| ROAS ≥ 10x sostenido 2 semanas | Escalar agresivo y evaluar replicar a Google Ads |
| ROAS ≥ 40x | Éxito: congelar configuración ganadora y escalar hasta el techo del mercado |

## 6. Cálculo de referencia rápida

```
ROAS = (ventas Yape del mes) ÷ (gasto Meta del mes)
CPL  = gasto ÷ leads
Costo por venta = gasto ÷ ventas Yape
Ingreso por lead = ventas Yape ÷ leads   → si ≥ S/75 = camino a 10x
```
