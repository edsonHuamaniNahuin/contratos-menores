# 🧠 DECISIONES DE NEGOCIO DEL NICHO — Aplicando skills.sh (15/10)

> **Skills usados:** `offers` (Value Equation + anatomía de oferta) · `lead-magnets` (principios 1-5) · `pricing` (referencia) · `churn-prevention` (fondo).
> **Instalados desde:** skills.sh → pack `coreyhaines31/marketingskills` (56 skills en `.agents/skills/`).

## 1. DIAGNÓSTICO con la Value Equation (skill `offers`)
```
              Resultado soñado × Probabilidad percibida
   Valor =  ─────────────────────────────────────────────
                Tiempo de espera × Esfuerzo/sacrificio
```
**Oferta actual (cold traffic):** "Dinos tu rubro y te avisamos" → humano contacta → suscripción S/49-68.

| Lever | Score /10 | Evidencia |
|---|---|---|
| Resultado soñado | 7 | Ganar contratos del Estado — el deseo existe (ellos ya buscan) |
| **Probabilidad percibida** | **3** ❌ | Cero prueba social visible, cero muestra del producto, "te avisamos" sin demostrar |
| **Tiempo de espera** | **3** ❌ | El valor NO llega al instante: llega días después (si el humano contacta) |
| Esfuerzo/sacrificio | 5 | Formulario corto ✓, pero luego register+pago S/49-68 |

**Conclusión del skill:** el cuello no es el precio — son **TIEMPO** y **PRUEBA**. Por eso plantillas convierte (entrega un artefacto INMEDIATO y demostrable) y las landings no.

## 2. DECISIÓN #1 — El lead magnet correcto (skill `lead-magnets`)
**Principio #3: valor alto percibido + consumo <10 min + beneficio inmediato.** Hoy el "magnet" es una promesa diferida. **Nuevo magnet:**

> **📄 "Reporte de convocatorias de TU rubro — esta semana"** — PDF automático al instante por correo, con: convocatorias reales de su rubro (entidad, monto, cierre), y 3 tips de calificación.

- Específico ✓ (su rubro, su semana) · Inmediato ✓ (se envía solo al enviar el form) · Camino natural al producto ✓ (ES el producto en miniatura) · Un solo formato ✓ (PDF móvil) · Reusa NUESTROS datos ✓ (notified_processes/contratos — no requiere contenido nuevo).
- **Impacto esperado (offers):** sube Probabilidad 3→7 y baja Tiempo 3→8. Lift honesto: +10-40% por componente (no prometo 5x).

**Build (siguiente sesión de agente):** job `leads:reporte-rubro` — al submit del form: query convocatorias del rubro → mailable HTML/PDF → enviar. (No toca los anuncios en learning.)

**✅ CONSTRUIDO Y EN VIVO (16/10):** `EnviarReporteConvocatoriasJob` + `ReporteConvocatoriasMail` (top-10 de contratos de los últimos 7 días, rubro auto-detectado, CTA "responde con tu rubro") + dispatch automático en los DOS flujos de lead (demo + plantillas) + comando `leads:reporte`. Enviado a los 6 leads de los últimos 14 días (incluye el nuevo del 16/10). Commit `f3921511`.

## 3. DECISIÓN #2 — Anatomía de la OFERTA de venta (skill `offers`)
| Componente | Estado actual | Acción |
|---|---|---|
| Core | Alertas + IA TDR + buscador | ✓ claro |
| **Bonos** | ❌ NINGUNO | **Agregar (reales, ya existentes):** pack de plantillas completo, alertas de buena pro, calculadora de márgenes. Cero costo, sube valor percibido |
| Garantía | ✓ 5 días | Mantener (bien posicionada) |
| Urgencia | ✓ temporada Q4 real | Mantener (nunca countdown falsos) |
| Nombre | Genérico "Premium" | Renombrar por resultado: **"Vigilante Proveedor"** (S/49) y **"Vigilante Proveedor + Contratos Mayores"** (S/68) |
| **Esfuerzo post-compra** | ❌ alto (configurar solo) | **Setup asistido GRATIS**: el equipo configura las alertas del cliente (usar el RUBRO que dio en el form) → reduce esfuerzo a ~0 |

## 4. DECISIÓN #3 — Precio de entrada (requiere TU OK — no ejecuto)
El agua fría entra por puerta cara: S/49-68. Opciones para decidir:
- **A)** Mantener precios + bonos + setup asistido (sin riesgo de margen)
- **B)** Plan de entrada **"Contratos Menores" S/29/mes** (compite en la puerta de LicitaHub, reduce barrera del tráfico frío)
- **C)** Pago único de prueba "30 días con garantía" ya existe como garantía — no sumar más trials

*Mi recomendación (skill `pricing`):* empezar con **A durante la ventana de 72h** y decidir B/C con los primeros datos de J0 (si CPL bajo pero cierre bajo → el problema es precio → B).

**✅ DECIDIDO POR EL DUEÑO (16/10): OPCIÓN A** — se mantienen S/49 (Vigilante Proveedor) y S/68 (Vigilante Proveedor + Contratos Mayores) + bonos + setup asistido. Revisión de precio solo con datos del día 18/10+.

## 5. DECISIÓN #4 — Churn (skill `churn-prevention`)
10 suscripciones vencidas sin winback efectivo → **secuencia de win-back con el NUEVO magnet**: "Perdiste convocatorias este mes. Aquí está el reporte de tu rubro que te perdiste — reactiva con garantía". Usar el email-sequence existente.

## 6. Lo que NO se toca (regla anti-tibieza pero con disciplina)
- **J0 en learning (72h)**: no cambiarlo hasta el 18/10. El nuevo magnet se construye AHORA en backend; el copy nuevo entra en la siguiente rotación.
- Nada de descuentos a ciegas, countdowns falsos ni promesas de WhatsApp.
