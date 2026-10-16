# 📐 REGLAS DE REFACTORIZACIÓN POR COMPONENTE (VINCULANTES)

> **Qué es:** la matriz de mejora continua de cada pieza de la configuración Meta + embudo.
> **Cómo se usa:** CADA refactor (diario o semanal) DEBE auditar los componentes tocados contra esta tabla.
> **Evidencia:** cada regla nace de un dato observado (4 días de campañas + 7 leads orgánicos + 208 sesiones Clarity + heatmap propio). Cuando un dato nuevo contradiga una regla, se actualiza AQUÍ y en el registro.
> **Vinculación:** AGENTS.md §9-10 · skill `optimizacion-diaria-vigilante` · runbook `.agents/DIARIO_AGENTE.md`.

## 1. COPY (texto del anuncio)
| Aprendizaje (evidencia) | REGLA |
|---|---|
| Los hooks "Dato" lideraron CTR (3.32%); el dolor generó los únicos clics WA | **Primera línea = DATO o DOLOR en ≤125 caracteres** (antes del "ver más") |
| Copies con iconos y URLs informan mejor y dirigen | Iconos que refuercen (3-6 máx) + **las 3 URLs visibles** (web + ambos buscadores) |
| El único formulario que convierte promete recompensa inmediata | **Toda pieza debe contener una recompensa inmediata explícita** ("gratis", "descarga", "el mismo día") |
| Vigilante = Telegram/correo (WhatsApp congelado) | **PROHIBIDO** prometer alertas por WhatsApp |
| — | Máx 800 caracteres; español peruano; "tú" directo; sin jerga IA |

## 2. IMAGEN / CREATIVO
| Aprendizaje | REGLA |
|---|---|
| Personas reales: CTR 2.0-2.5% + únicos clics WA · Documento: 1.81% | **Persona real en escena** (oficio/contexto TIER A) como opción principal |
| Fotos reutilizadas del lote viejo = percibidas como "lo mismo" | **PROHIBIDO reutilizar fotos de creativos retirados**; cada flyer nuevo usa material no publicado antes |
| Misma plantilla 3 veces = fatiga visual | **Estructuras alternadas**: full-bleed / split (foto+panel) / tarjeta-alerta / poster tipográfico |
| 83% móvil | Titular legible a 2 seg; contraste alto; CTA píldora con flecha |
| Alerta (correo/Telegram) = dispositivo con clics | La **tarjeta de alerta** se usa donde el mensaje sea "te avisamos" |

## 3. SEGMENTACIÓN
| Aprendizaje | REGLA |
|---|---|
| TIER A (obras/saneamiento/eléctrico) = pagadores; tech NO paga | Intereses SOLO TIER A validados; **nunca software/tech** como vertical |
| AN = clics basura (18/18) | **SIEMPRE FB+IG** (publisher_platforms) |
| Advantage expande fuera de rango | **advantage_audience=0** SIEMPRE |
| Clientes y leads no deben re-ver anuncios de captación | Exclusiones activas (clientes + leads) en TODO adset |
| Geografía de demanda: Junín–Ucayali–Lima–LL–Arequipa | Corredor como config B (test), no restricción permanente |

## 4. EDAD
| Aprendizaje | REGLA |
|---|---|
| 45-64: CTR 2.6-3.2%, CPC S/0.048 (mejor que 25-44) | **40-60 obligatorio**; revisar breakdown `age` semanalmente |
| 35-44 flojo en U1; comprador = dueño con 8-19 años de empresa | Si 40-44 sigue bajo al día 14 → **estrechar a 45-60** |

## 5. DESTINO Y FORMULARIO
| Aprendizaje | REGLA |
|---|---|
| Plantillas (recompensa inmediata, 2 campos) = 7 leads · Landings demo = 0 en ~500 | **Cada destino candidato debe tener patrón "plantillas"**: algo gratis que llega ya |
| Form 5 campos = 0 envíos · Form 2 campos = convierte | **Máx 3 campos** (nombre+email+captcha); empresa/rubro se preguntan DESPUÉS |
| Instant Form = cero fricción (pendiente validar) | J0 activo como vía principal de captura cuando Meta lo permita |
| WhatsApp obtuvo los únicos clics de intención | **WhatsApp visible siempre** (sticky + bajo cada formulario) |

## 6. UX (Clarity + heatmap propio)
| Aprendizaje | REGLA |
|---|---|
| Scroll promedio 43.6% (form al fondo no se alcanza) | Si scroll <50%: **CTA/form duplicado a mitad de página** |
| Dead clicks 7.7% de sesiones | Dead clicks >5% → **fix affordance** (hacer clickeable o quitar aspecto de botón) |
| Quickbacks 2.4% | >2% → auditar links con retorno inmediato |
| Script errors detectados (baja tasa, vigilar) | Cualquier error JS → fix prioritario (rompe todo el tracking) |

## 7. HORARIO Y PRESUPUESTO
| Aprendizaje | REGLA |
|---|---|
| Dayparting activo (23:00-07:00 off) | Mantener; revisar breakdown horario 1×/mes |
| Tope S/21/día congelado | Reasignación interna libre; **nunca subir total sin OK del dueño** |
| Fair-share ≥S/12 para juzgar un brazo | Ninguna pausa/rotación antes de ese gasto acumulado |

## 8. OBLIGATORIEDAD DE SKILLS Y HERRAMIENTAS (cada refactor)
**Skills (cargar y citar el principio aplicado):**
- Estructura/creativos → `ads` · Tests/validez → `ab-testing` · Landing/forms → `page-cro`/`form-cro` · Verificación → `analytics` · Ángulos → `marketing-psychology` · Target/VoC → `mercado-vigilante`.

**Herramientas (mínimo 4 por ritual, declarar cuáles se usaron):**
1. Dossier `agente:brief` 2. Heatmap propio (SQL page_events) 3. Clarity (API/dashboard) 4. Embudo BD (wa_clicks/leads/Yape) 5. Meta breakdowns (age/hora/anuncio) 6. verify_campaign.py + funnel-audit.

**Registro obligatorio por refactor (en PANEL_DIARIO + bitácora):**
`Componente tocado → dato que lo justifica → skill citado → herramienta usada → regla aplicada → resultado esperado (medible)`.

## 9. CÓMO EVOLUCIONAN ESTAS REGLAS
- Un dato nuevo que contradiga una regla → se edita ESTE documento (nunca se ignora).
- Cada regla nueva lleva: evidencia + umbral + herramienta de verificación.
- El agente diario las audita; el dueño solo aprueba lo que toque su matriz (presupuesto total/precios).
