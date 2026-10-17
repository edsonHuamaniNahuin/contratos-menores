# 🔬 AUDITORÍA "CANTO A CANTO" — 16/10 (GA4 + Clarity + Heatmap propio)

> Mandato del dueño: afinar campañas Y embudo Y web con TODA la data. Primer corte completo desde
> que Clarity mide de verdad + GA4 + beacons propios.

## 1. Mapa de sesiones (GA4, 7 días)
| Página | Sesiones | Usuarios | Vistas | Lectura |
|---|---|---|---|---|
| /alertas-licitaciones | 196 | 193 | 224 | landing tráfico pago |
| /plantillas-tdr | 173 | 169 | 188 | **la que convierte (leads)** |
| **/buscador-publico** | 143 | 100 | **991** | **LA SUPERFICIE MÁS USADA (6.9 vistas/sesión) y no tenía captura** |
| /licitaciones-vigentes | 140 | 134 | 153 | pago |
| /software-licitaciones | 108 | 105 | 111 | pago |
| /planes | 12 | 10 | 12 | **casi nadie llega al precio** |
| /register | 17 | 15 | — | |

**Eventos GA4:** page_view 2,105 · session_start 832 · scroll 311 · form_submit 63 (genérico: incluye login/registro/búsqueda) · **demo_lead_enviado 5** · lead_whatsapp_click 25 eventos (vs 7 reales en BD → ruido: revisar duplicados de gtag).

## 2. UX con Clarity (24h, 204 sesiones / 166 humanas)
- **Dead clicks: 11.27% de sesiones** (104 clics) — problema #1
- Scroll promedio: 28.5% · Tiempo activo: 24s
- Quickbacks 1.96% · Rage 1.47% · **Script errors: 7 en 7 pv** (pendiente cazar; no reproduce en desktop)
- Páginas top hoy: plantillas 148 · buscador 40

## 3. Heatmap propio → los clics muertos EXACTOS (7d)
| Landing | Target | Clics | Diagnóstico |
|---|---|---|---|
| vigentes | **H1.font-display.font-extrabold** | **113 (68% de sus clics)** | El titular se toca esperando acción — no hace nada |
| alertas | **SECTION.relative.max-w-6xl (hero)** | **52** | Taps en mockup/hero sin acción |
| alertas | H1.font-display.font-medium | 9 | Igual |
| varias | A.btn-wa | 4 | ✓ clics reales a WhatsApp |

## 4. ACCIONES EJECUTADAS HOY (deploy 7128c9c5)
1. **Buscador-publico: CTA sticky de conversión** (Agendar demo + WhatsApp) — la página más usada ahora captura.
2. **Fix dead-clicks en heroes de vigentes y alertas:** tap en zona hero no-interactiva → scroll suave al formulario (los 165 clics muertos ahora empujan al CTA en lugar de morir).
3. (previo hoy) Bonos/renombrado/setup asistido + beacons de heatmap en plantillas y planes.

## 5. Lo que queda por afinar (cola priorizada)
| # | Acción | Herramienta | Por qué |
|---|---|---|---|
| 1 | Cazar los 7 errores JS (interacción en buscador/filtros) | harness + Clarity dashboard | Rompen tracking/UX |
| 2 | Higiene de eventos GA4: `form_submit` genérico vs `demo_lead_enviado` real; duplicados de `lead_whatsapp_click` (25 vs 7 BD) | GA4 + código | Medir cierre con precisión |
| 3 | Scroll 28.5% global: subir CTA/form a mitad en alertas/vigentes (medir tras fix dead-clicks) | Clarity + heatmap | Menos scroll = menos exposición al form |
| 4 | /planes con solo 12 sesiones: enlazarla desde el buscador/banner y desde el CTA del hero | GA4 + funnel | El precio casi no se ve |
| 5 | Revisar duración de carga en FB in-app (impacto en 28% scroll) | PageSpeed/Clarity | Tráfico 100% in-app |

## 6. Regla nueva institucionalizada
El afinado NO termina en el anuncio: cada decisión de campaña se cruza con GA4 + Clarity + heatmap propio
(capturados en el skill `optimizacion-diaria-vigilante` §1 y en el ritual diario).
