# 📊 REPORTE META ADS — Días 1-3 (12-14 Oct 2026)

> Extraído en vivo de la API de Meta (14/10 08:30) + primera parte (BD propia).
> Contexto: **hoy (14/10) desde 07:00 corren 3 brazos iguales de S/7/día** (E1 + E3 + Urgencia); E2 pausada.

## 1. Campaña Tráfico E1E2E3 — diario

| Día | Gasto | Impr | Alcance | Freq | Clics (link) | CTR | CPC | CPM | Landings (Meta) |
|---|---|---|---|---|---|---|---|---|---|
| lun 12 | S/ 13.72 | 10,878 | 8,137 | 1.34 | 294 (232) | 2.70% | S/0.047 | S/1.26 | 84 |
| mar 13 | S/ 28.11 | 24,364 | 15,796 | 1.54 | 569 (449) | 2.34% | S/0.049 | S/1.15 | 192 |
| mié 14 (parcial ~8:30) | S/ 3.41 | 1,686 | 1,554 | 1.08 | 26 (20) | 1.54% | S/0.131 | S/2.02 | 15 |
| **Total** | **S/ 45.24** | **36,928** | — | — | **889 (701)** | **2.41%** | S/0.051 | S/1.23 | **291** |

Promedio real: **S/ 15.08/día** (≤ S/21 ✓; el día 2 compensó con el día 1 y el dayparting ya está activo).

## 2. Por anuncio (acumulado 12-14, ordenado por gasto)

| Anuncio | Gasto | Impr | Clics | CTR | CPC | Landings |
|---|---|---|---|---|---|---|
| Software - V3 Alivio | S/ 11.26 | 9,667 | 218 | 2.26% | S/0.052 | 71 |
| Vigentes - V2 Dolor | S/ 10.83 | 10,305 | 236 | 2.29% | S/0.046 | 75 |
| Alertas - V2 Dolor | S/ 7.74 | 5,673 | 129 | 2.27% | S/0.060 | 45 |
| Alertas - V3 Alivio | S/ 4.87 | 3,615 | 94 | 2.60% | S/0.052 | 25 |
| Vigentes - V3 Alivio | S/ 4.04 | 3,079 | 77 | 2.50% | S/0.052 | 27 |
| **Alertas - V1 Dato** | S/ 3.00 | 1,806 | 60 | **3.32%** ⭐ | S/0.050 | 24 |
| Software - V2 Dolor | S/ 2.03 | 1,638 | 44 | 2.69% | S/0.046 | 17 |
| Software - V1 Dato | S/ 0.94 | 720 | 22 | 3.06% | S/0.043 | 4 |
| Vigentes - V1 Dato | S/ 0.53 | 425 | 9 | 2.12% | S/0.059 | 3 |

**Lectura:** CTR parejo entre ángulos (V1 Dato lidera por muestra pequeña — el gasto lo dominó V3/V2 por reparto de Meta). Decisión formal por anuncio: **día 7 (18/10)** con fair-share ≥ S/12.

## 3. 🚀 Campaña URGENCIA — primeras 1.5 horas (07:00→08:30)

- Gasto S/ 0.91 · 391 impr · 9 clics link · CTR 2.05% · CPC S/0.114 · CPM S/2.33 · LPV 6.
- **2 CLICS WhatsApp a las 08:29** (`utm_content = MA-Urgencia - V1 Dolor`, landing /alertas-licitaciones) — **el primer contacto de la campaña Urgencia llegó en su primera hora y media** (E1 tardó ~2 días). Nota de rigor: ambos eventos del mismo minuto/dispositivo similar → podría ser 1 persona (2 eventos); aun así la señal inicial es la más rápida del sistema.

## 4. Embudo propio (primera parte)

| Métrica | 12/10 | 13/10 | 14/10 (parcial) |
|---|---|---|---|
| Clics WhatsApp reales (BD) | 0 | **2** (E1-V2 Dolor) | **3** (2× Urgencia 08:29 + 1 orgánico 02:16) |
| Eventos en landing (scroll/clic) | 1 / 0 | 42 / 161 | 19 / 17 |
| Leads formulario | 0 | 0 | **1** — EDITH MENDOZA (06:03, SIN UTM → **orgánico, no atribuible a campaña**) |

⚠️ Edith registró a las 06:03 (antes del arranque 07:00): es un lead orgánico — responderle ≤2h (regla) con `/admin/respuesta-leads`.

## 5. Hallazgos de medición

1. **El pixel NO reporta eventos Contact/Lead en los insights de Meta** (las `actions` solo muestran clic, LPV y engagement). PageView sí llega (LPV funciona ✓). → **P-002 abierto**: revisar por qué los eventos `Contact`/`Lead` no llegan (impide optimizar hacia ellos y medir intención en Meta).
2. Meta vs nuestros beacons: usar SIEMPRE la primera parte (wa_clicks/BD) como fuente de verdad de intención.

## 6. Próximos hitos

- **Hoy en la noche:** comparación de los 3 brazos (E1 vs E3 vs U1) con el día completo.
- **Día 7 (18/10):** llamada por anuncio (fair-share) → rotación creativos + relanzamiento E2 con ángulo saneamiento.
- **Día 14 (25/10):** llamada por ángulo + config B geo + retargeting.
