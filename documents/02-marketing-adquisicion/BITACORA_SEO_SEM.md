# 📈 Bitácora de SEO & SEM — Vigilante SEACE

> Historial de decisiones, errores, correcciones y lecciones aprendidas en posicionamiento orgánico (SEO) y pago (SEM).
> Solo SEO/SEM. Para bugs técnicos generales, ver `BITACORA.md`.

---

## 📊 Línea de tiempo

```
Jul 2026        GSC: 490 clics, 79K imp, CTR 0.62%, pos 7.3 — 26 páginas indexadas
Ago 05-06       Análisis completo + Fase 1 de correcciones
Ago 06          Deploy masivo (14 páginas + layouts + blog)
Ago 08          GSC 3 meses: 299 clics, 85K imp, CTR 0.35%, pos 8.1 — 46 páginas indexadas
Ago 06-08       Google dance: keywords principales caen temporalmente a página 2
Ago 08          Corrección H1: keyword "2026" restaurada en H1 visible
```

---

## 🔴 Error #1 — Marca muerta en títulos SEO (2026-08-05)

**Síntoma:** `/buscador-publico` con 72K impresiones y CTR de 0.42%. Google mostraba "Buscador de Licitaciones SEACE — Licitaciones MYPe".

**Causa:** 17 referencias a la marca antigua "Licitaciones MYPe" seguían vivas en meta titles, meta descriptions, schema JSON-LD y body text. La marca había cambiado a "Vigilante SEACE" pero el SEO no se actualizó.

**Solución:**
- Reemplazar "Licitaciones MYPe" → "Vigilante SEACE" en todos los meta tags y schemas
- Actualizar schema JSON-LD en landing page (6 referencias)
- Actualizar títulos en 9 páginas públicas + blog generator

**Lección:** Cuando se hace rebranding, el SEO debe actualizarse en el mismo deploy. Cada día con la marca vieja en Google = confusión del usuario + CTR bajo.

**Commits:** `ecd05dce`, `88cbb725`

---

## 🔴 Error #2 — CTR catastrófico por meta tags genéricos (2026-08-05)

**Síntoma:** CTR promedio 0.62%. /planes en posición 2.71 con 0% CTR. /contacto en posición 2.45 con 0% CTR.

**Causa:** Meta titles sin emoji, sin año, sin CTA, sin beneficio. Meta descriptions ausentes o genéricas.

**Solución:**
- Formato de title: `{Emoji} {Keyword Principal} {Año} — {Beneficio} | Vigilante SEACE`
- Meta descriptions: 140-155 chars, CTA explícito, beneficio concreto
- Ejemplo /planes: `"Planes"` → `"💎 Planes y Precios — Vigilante SEACE | Desde S/ 0/mes · Análisis IA + Alertas"`

**Regla de meta titles efectivos:**
1. Keyword principal al inicio
2. Año actual (señal de frescura → Google favorece)
3. Emoji (destaca visualmente en SERP → +CTR)
4. Beneficio o valor ("Gratis", "en Segundos", "Paso a Paso")
5. Marca al final con pipe separator
6. 50-60 caracteres máximo

**Commits:** `ecd05dce`

---

## 🔴 Error #3 — Sin OG/Twitter tags (2026-08-05)

**Síntoma:** Al compartir cualquier página en WhatsApp, Facebook o Twitter, no aparecía preview (imagen, título, descripción). 14 páginas públicas sin tarjetas sociales.

**Causa:** `layouts/public.blade.php` no tenía ningún meta tag Open Graph ni Twitter Card.

**Solución:** Agregar 6 OG tags + 3 Twitter Card tags al layout público, con `@yield()` para que páginas individuales puedan sobrescribir. Afecta a todas las páginas públicas.

**Commits:** `ecd05dce`

---

## 🔴 Error #4 — Title/slug mismatch en blog post (2026-08-06)

**Síntoma:** `/blog/que-es-el-seace/` con 204 impresiones y CTR 0.49%. El slug decía "que-es-el-seace" pero el title del frontmatter era "Ley N° 32069: La Nueva Ley de Contrataciones del Estado Explicada".

**Causa:** Se cambió el título del post sin actualizar el slug. El usuario buscaba "qué es el SEACE", Google mostraba el post, pero el título hablaba de una ley → el usuario no hacía click.

**Solución:** Reescribir title para que coincida con la intención de búsqueda: `"🔰 ¿Qué es el SEACE? Guía Completa 2026 — Ley 32069 Explicada"`. El title ahora responde la pregunta que el usuario hizo.

**Lección:** El title debe responder a la intención de búsqueda, no al contenido interno. Si el usuario busca "qué es X", el title debe decir "Qué es X".

**Commits:** `ecd05dce`

---

## 🔴 Error #5 — Blog posts con CTR 0% (2026-08-06)

**Síntoma:** 4 posts con impresiones significativas y 0% CTR:
- `guia-rnp-inscripcion.md`: 882 imp, CTR 0.11%
- `como-funciona-seace-peru.md`: 276 imp, CTR 0%
- `tipos-contratos-seace.md`: 216 imp, CTR 0%

**Causa:** Títulos sin año, sin emoji, sin gancho. Ejemplo: "Guía RNP: Cómo Inscribirte en el Registro Nacional de Proveedores".

**Solución:**
- `"📋 Cómo Inscribirte en el RNP en 2026 — Guía Completa Paso a Paso | Gratis"`
- `"🔰 ¿Cómo Funciona el SEACE en 2026? — Guía para Principiantes"`
- `"🏗️ Tipos de Contrataciones en SEACE 2026 — Licitaciones, Concursos, Adjudicaciones"`

**Commits:** `ecd05dce`

---

## 🔴 Error #6 — Doble H1 en todas las páginas (2026-08-06)

**Síntoma:** Cada página autenticada tenía 2 `<h1>`: el del logo del sidebar ("Vigilante SEACE") + el real de la página. Google veía duplicate H1.

**Causa:** `<h1>Vigilante SEACE</h1>` en `layouts/app.blade.php:102` (sidebar logo) y `layouts/guest.blade.php:38` (header login). El logo no debería ser un H1.

**Solución:** Cambiar `<h1>` → `<div>` en ambos layouts. Ahora cada página tiene exactamente 1 H1 semántico.

**Lección:** El `<h1>` es el título de la PÁGINA, no del sitio. El logo/nombre del sitio debe ser `<div>`, `<span>` o `<a>`, nunca `<h1>`.

**Commits:** `88cbb725`

---

## 🔴 Error #7 — H1 visible sin jerarquía vs H2 filtros (2026-08-06)

**Síntoma:** El H1 visible (`text-xl`, 20px) y el H2 "Filtros de Búsqueda" (`text-lg lg:text-xl`, 18-20px) se veían del mismo tamaño en desktop. Sin jerarquía visual.

**Causa:** Ambas clases resultaban en 20px en pantallas grandes.

**Solución:** Subir H1 a `text-2xl` (24px). Jerarquía: H1 (24px) > H2 (18-20px) > resto.

**Commits:** `88cbb725`

---

## 🔴 Error #8 — H1 oculto con sr-only (2026-08-06)

**Síntoma:** `/buscador-publico` no tenía título visible para usuarios. Solo screen readers y Google lo veían (vía `<header class="sr-only">`). Esto perjudica UX y potencialmente CTR (el usuario no ve confirmación visual de que está en la página correcta).

**Causa:** El H1 estaba dentro de un `<header class="sr-only">` por decisión de diseño original.

**Solución:** Agregar H1 visible (`text-2xl font-bold text-neutral-900`) + subtítulo descriptivo. El sr-only ahora usa `<h2>` para no duplicar H1.

**Commits:** `88cbb725`

---

## 🔴 Error #9 — Pérdida de posición por reducción de keywords en H1 (2026-08-07~08) ⚠️ CRÍTICO

**Síntoma:** Keywords principales ("seace buscador", "seace buscador publico", "buscador seace") cayeron de posición 7 a 11+ (página 1 → página 2) en 48 horas post-deploy.

**Causa:** Al mover el H1 de sr-only a visible, el texto pasó de:
```
Antes (sr-only): 🔍 Buscador SEACE 2026 — Licitaciones del Estado en Segundos | Vigilante SEACE
Ahora (visible): Buscador de Licitaciones del SEACE
```
**5 keywords → 2 keywords.** Google pesa el H1 como señal fuerte de relevancia. Al reducir la densidad de keywords en el H1, la página perdió señales para "seace buscador", "2026", "vigilante seace".

**Solución (aplicada 2026-08-08):**
- H1: `Buscador de Licitaciones del SEACE 2026` (recupera año + keyword)
- Subtítulo: `"Gratis y sin registro"` (señal de valor + CTA)
- Regional: `Contratos Menores en Arequipa 2026` (año)

**Lección crítica:** ✅ Cuando se cambia la estructura del H1, NUNCA reducir la densidad de keywords. Si el H1 viejo tenía 5 keywords relevantes, el nuevo debe tener al menos las mismas. El H1 es la segunda señal más importante después del title tag.

**Commits:** `296f49da`

---

## 🔴 Error #10 — URLs regionales no indexables por usar solo query strings (2026-08-06)

**Síntoma:** Las búsquedas por departamento usaban `?dep=amazonas`. Google trataba esto como una sola página (`/buscador-publico`) con 25 variantes de query string, no como 25 páginas independientes.

**Causa:** Los parámetros de filtro estaban solo en query string. Google indexa la URL base, no las variantes con parámetros.

**Solución:**
- Crear rutas limpias: `/buscador-publico/{dep}` y `/contratos-estado/{dep}`
- Cada URL con meta tags únicos (title, description, H1, JSON-LD Place)
- Canonical desde `/buscador-publico/{dep}` → `/contratos-estado/{dep}` (evitar duplicate content)
- 26 URLs añadidas al sitemap

**Commits:** `3cae390c`

---

## 🔴 Error #11 — Canonical duplicado en URLs regionales (2026-08-08)

**Síntoma:** `/buscador-publico/ancash` y `/contratos-estado/ancash` tenían canonical `url()->current()`, apuntando cada una a sí misma. Google veía 2 URLs con contenido idéntico → duplicate content.

**Causa:** Ambas rutas renderizan la misma vista con `$canonicalUrl = url()->current()`. Cada URL se declaraba canonical de sí misma.

**Solución:** Canonical de `/buscador-publico/{dep}` ahora apunta a `/contratos-estado/{dep}`. Solo `/contratos-estado/` está en el sitemap.

**Commits:** `484e7125`

---

## 🔴 Error #12 — Contenido indexable insuficiente en página principal (2026-08-08)

**Síntoma:** `/buscador-publico` es principalmente un componente Livewire con renderizado JavaScript. Google ve poco texto estático indexable → señal débil de relevancia para "seace buscador".

**Estado actual:** La página tiene H1 + subtítulo + sr-only SEO text. Pero el cuerpo principal es el componente Livewire que Google puede no renderizar completamente.

**Riesgo:** Si Google no ejecuta JavaScript (o lo hace parcialmente), ve una página casi vacía.

**Mitigación pendiente:** Agregar 150-300 palabras de texto indexable debajo del componente (tipos de contratos, cómo usar el buscador, preguntas frecuentes). Esto daría más señales semánticas sin afectar la UX.

---

## 🟡 Lección — Google dance post-deploy (2026-08-06~08)

**Qué pasó:** Deploy de ~15 archivos el 06-Ago. En 48h, las keywords principales fluctuaron de posición 7 a 11+ temporalmente debido a que Google re-evalúa la página cuando detecta cambios estructurales significativos.

**Timeline típico del Google dance:**
| Días | Fase |
|---|---|
| 0-2 | Caída temporal (Google recalcula) |
| 3-7 | Recuperación gradual |
| 7-14 | Estabilización en nueva posición |
| 14-30 | Resultado final visible |

**Lección:** No entrar en pánico en los primeros 7 días post-deploy SEO. Esperar 2 semanas antes de juzgar resultados. Si a los 14 días la posición no se recupera, investigar causas específicas.

---

## 🟢 Acierto — Schema JSON-LD en landing y blog (2026-08-06)

**Qué se hizo:**
- Landing: WebApplication + SearchAction + FAQ (5 Q&A)
- Blog posts: BlogPosting + BreadcrumbList (generado automáticamente por el generador)

**Resultado esperado:** Rich snippets en SERP (FAQ accordion, breadcrumb trail, sitelinks search box). Aumentan el espacio visual en resultados → +CTR.

---

## 🟢 Acierto — Blog generator con meta tags dinámicos (2026-08-06)

**Qué se hizo:** El `GenerateBlogCommand` ahora genera meta titles con emoji + año + keywords para páginas de índice y categorías. Las descripciones incluyen el año actual dinámicamente.

**Impacto:** 16 posts × mejor meta = potencial +100 clics/mes al blog.

---

## 📋 Plan de acción SEO pendiente

| Prioridad | Acción | Impacto estimado |
|---|---|---|
| **P1** | Agregar texto indexable debajo del buscador | +posiciones para "seace buscador" |
| **P1** | Monitorear GSC diariamente hasta Ago 20 | Confirmar recuperación post-dance |
| **P2** | Internal linking: blog → buscador con anchor text | +relevancia para keywords |
| **P2** | Core Web Vitals: medir y optimizar LCP/CLS | +señal de ranking |
| **P2** | FAQ schema en blog posts individuales | +rich snippets |
| **P3** | Google Ads Search para keywords de alta intención | Tráfico inmediato mientras SEO madura |
| **P3** | Backlinks: guest posts, directorios peruanos | +domain authority |

---

## 📊 Métricas — Antes vs Después (en seguimiento)

| Métrica | Antes (Jul-Ago 5) | Después (Ago 8) | Objetivo 30 días |
|---|---|---|---|
| CTR promedio | 0.62% | 0.35%* | 1.5% |
| Posición promedio | 7.3 | 8.1* | 6.0 |
| Páginas indexadas | 26 | 46 | 60+ |
| Keywords con 0 clics | 50+ | ~45 | 20 |
| Tráfico orgánico diario | ~18 clics | ~20 clics* | 40+ |

*\* Datos afectados por Google dance post-deploy. No representan el resultado final.*
