# 📓 Bitácora — Errores y Soluciones

> Historial de incidencias, bugs y su resolución en Vigilante SEACE.
> Formato: fecha · categoría · commit · síntoma → causa → solución.

---

## 2026-08-06 · SEO Regional · `3cae390c`

### Las 26 URLs regionales (`/buscador-publico/{dep}`) no se indexaban como páginas separadas

**Síntoma:** Google trataba `/buscador-publico?dep=amazonas` como una sola página con variantes de query string, no como 26 páginas independientes.

**Causa:** Los parámetros de filtro estaban solo en query string (`?dep=`), no en la ruta limpia.

**Solución:**
1. Crear ruta limpia `/buscador-publico/{dep}/{prov?}/{dist?}` que renderiza una vista dedicada con meta tags únicos por región
2. El Livewire `BuscadorPublico` recibe `initialDep` en `mount()` para precargar el filtro
3. Meta title: `"🔍 Contratos Menores en {dep} 2026 — Buscador SEACE"`
4. Canonical: `/contratos-estado/{dep}` (evita duplicate content con la otra URL alias)
5. 26 URLs incluidas en sitemap.xml

**Archivos:** `buscador-publico-regional.blade.php`, `BuscadorPublico.php`, `routes/web.php`

---

### Las 88 URLs de Contratos Mayores (`/buscador-contratos-mayores/{entidad}`) no existían

**Síntoma:** Sin páginas dedicadas para "contratos mayores gobierno regional arequipa", 0 tráfico regional en mayores.

**Solución:**
1. Ruta `/buscador-contratos-mayores/{entidad}` que busca la entidad en DB (`entidades_mayores`) por nombre normalizado
2. `BuscadorMayores::mount()` recibe `initialEntidad`, busca en DB vía `LOWER(nombre)`, setea `entidadTexto` y `entidadFiltro`
3. Sidebar con 88 entidades desde DB (cache 24h), búsqueda Alpine.js, límite 20 items + "Ver todas"
4. Vista por defecto en cuadrícula (`localStorage.vistaMayores = 'grid'`)
5. 88 URLs incluidas en sitemap.xml

**Archivos:** `buscador-mayores-regional.blade.php`, `BuscadorMayores.php`, `routes/web.php`

---

## 2026-08-06 · H1 · `88cbb725`

### Duplicación masiva de `<h1>` en todas las páginas autenticadas

**Síntoma:** `/buscador-publico` mostraba 2 H1s: `"Vigilante SEACE"` + `"Buscador de Licitaciones del SEACE"`. El `<h1>Vigilante SEACE</h1>` del logo del sidebar contaminaba todas las páginas.

**Causa:** El sidebar logo en `layouts/app.blade.php:102` usaba `<h1>` en vez de `<div>`. Google y lectores de pantalla veían 2 títulos principales por página.

**Solución:** Cambiar `<h1>Vigilante SEACE</h1>` → `<div>Vigilante SEACE</div>` en:
- `layouts/app.blade.php:102` (sidebar logo — afectaba ~40 páginas)
- `layouts/guest.blade.php:38` (header login/register)

Cada página ahora tiene exactamente 1 `<h1>` semántico.

**Archivos:** `layouts/app.blade.php`, `layouts/guest.blade.php`

---

### H1 visible con mismo tamaño que H2 "Filtros de Búsqueda" → sin jerarquía visual

**Síntoma:** El título de página (`text-xl`) y el H2 de filtros (`text-lg lg:text-xl`) se veían del mismo tamaño en desktop.

**Solución:** Subir H1 de `text-xl` a `text-2xl` en ambas páginas. Jerarquía clara: H1 (24px) > H2 (18-20px).

**Archivos:** `buscador-publico.blade.php`, `buscador-mayores.blade.php`

---

### El H1 del buscador estaba oculto con `sr-only`

**Síntoma:** `/buscador-publico` no tenía título visible para usuarios. Solo screen readers y Google lo veían.

**Solución:** Agregar H1 visible con `text-2xl font-bold text-neutral-900` + subtítulo descriptivo. El `sr-only` original se bajó a `<h2>`.

**Archivo:** `buscador-publico.blade.php`

---

## 2026-08-06 · SEO General · `ecd05dce`

### CTR catastrófico de 0.62% (79K impresiones, solo 490 clics)

**Síntoma:** Google mostraba el sitio en primera página para 79,000 búsquedas/mes pero casi nadie hacía click. `/buscador-publico` con 72K impresiones tenía CTR de 0.42%.

**Causa raíz:** Marca muerta "Licitaciones MYPe" seguía apareciendo en títulos SEO, meta descriptions genéricas sin año/emoji/CTA, sin OG/Twitter tags, sin schema markup actualizado.

**Solución (Fase 1 completa):**
1. **Rebrand**: 17 referencias a "Licitaciones MYPe" → "Vigilante SEACE" en títulos, schemas, body text
2. **Meta titles**: Emojis, año 2026, keywords principales al inicio, CTA ("Gratis", "Paso a Paso")
3. **Meta descriptions**: 140-155 chars, beneficio concreto, CTA explícito
4. **OG + Twitter cards**: Añadidos al layout público (afecta 14 páginas)
5. **Schema JSON-LD**: WebApplication, SearchAction, BlogPosting, BreadcrumbList, Place (regional)
6. **4 blog posts críticos**: títulos reescritos con año + keywords (tenían CTR 0%-0.49%)

**Archivos:** 15+ archivos modificados. Ver `ANALISIS_COMPLETO_SEO_SEM.md` para detalle completo.

---

### `que-es-el-seace.md` tenía title/slug mismatch

**Síntoma:** 204 impresiones, CTR 0.49%. La URL `/blog/que-es-el-seace/` mostraba en Google el título "Ley N° 32069: La Nueva Ley de Contrataciones del Estado Explicada". El usuario buscaba "qué es el SEACE" y Google mostraba un título sobre una ley.

**Causa:** El frontmatter title no coincidía con el slug. El contenido original era sobre SEACE pero el título fue cambiado a Ley 32069 sin actualizar el slug.

**Solución:** Reescribir title a `"🔰 ¿Qué es el SEACE? Guía Completa del Sistema de Contrataciones 2026 — Ley 32069 Explicada"`.

**Archivo:** `blog/source/_posts/que-es-el-seace.md`

---

## 2026-08-06 · Bug Técnico

### Las vistas compiladas no se actualizaban después de `view:clear`

**Síntoma:** Modificaciones al blade del Livewire (ej: `@if(!$regionalMode)`) no se reflejaban en el HTML servido. El filtro geográfico seguía visible aunque la condición era `true`.

**Causa:** `php artisan view:cache` había pre-compilado las vistas en `storage/framework/views/`. El comando `view:clear` NO eliminaba algunos archivos cacheados grandes (>200KB). El servidor servía la versión compilada vieja.

**Solución:** Borrar manualmente `storage/framework/views/*.php` con `Remove-Item` antes de `view:clear`. Verificar que el conteo de archivos sea 0.

**Lección:** No confiar ciegamente en `artisan view:clear`. Verificar `ls storage/framework/views/ | wc -l`.

---

### `@@context` en JSON-LD colisionaba con directiva de Livewire

**Síntoma:** Error `ParseError: syntax error, unexpected end of file, expecting "elseif" or "else" or "endif"` al cargar `buscador-publico-regional.blade.php`. El error apuntaba a la línea del `@endsection`.

**Causa:** El JSON-LD contenía `"@@context": "https://schema.org"`. Livewire registra una directiva `@context` que interfería con el escape de Blade. `@@context` se compilaba como `<?php ... context()->has(...) ?>` en vez de `@context` literal.

**Solución:** Reemplazar el JSON-LD inline por `{!! json_encode([...], JSON_PRETTY_PRINT) !!}`. Esto evita que Blade interprete `@context` como directiva.

**Archivos:** `buscador-publico-regional.blade.php`, `buscador-mayores-regional.blade.php`

---

### `@hasSection` dentro de `@unless...@else` causaba error de parseo

**Síntoma:** `ParseError: syntax error, unexpected end of file, expecting "elseif" or "else" or "endif"` en `layouts/public.blade.php`.

**Causa:** La combinación `@unless(app()->environment('production')) ... @else @hasSection('noindex') ... @endif @endunless` creaba anidamiento confuso de directivas Blade. El `@endif` de `@hasSection` dentro del `@else` del `@unless` rompía el parser.

**Solución:** Reemplazar `@hasSection('noindex') ... @endif` por `@if(trim($__env->yieldContent('noindex'))) ... @endif`. Usar check directo en vez de directiva anidada.

**Archivo:** `layouts/public.blade.php`

---

### `<div class="mt-3">` duplicado rompía la maquetación de filtros

**Síntoma:** Al agregar `@if(!$this->regionalMode)` alrededor del filtro geográfico, la línea original `<div class="mt-3" x-data="...">` quedó duplicada (una dentro y otra antes del `@if`). Esto creaba un div vacío que rompía el espaciado visual entre filtros y resultados.

**Causa:** Error de edición al insertar el bloque `@if`. La línea original no fue removida.

**Solución:** Eliminar la línea duplicada. Verificar que solo haya una ocurrencia del `<div class="mt-3">` dentro del bloque condicional.

**Archivo:** `livewire/buscador-publico.blade.php`

---

## 2026-07-25 · WhatsApp · `f16625a0`

### Error `#131009` en WhatsApp Business API — título de lista excedía 24 caracteres

**Síntoma:** Error `#131009` al enviar notificaciones de Contratos Mayores por WhatsApp. El mensaje fallaba.

**Causa:** El texto `"🔍 Detectar Direccionamiento"` (25 caracteres) excedía el límite de 24 caracteres impuesto por WhatsApp para títulos de lista interactiva. Además, el `ocid` enviado contenía caracteres especiales que rompían el formato.

**Solución:**
1. Truncar título a ≤24 caracteres: `"🔍 Direccionamiento"` (18 chars)
2. Agregar método `sanitizeOcid()` para limpiar caracteres especiales
3. Remover el header del keyboard interactivo
4. Desplegado a prod + QA

**Archivos:** `app/Jobs/NotificarContratosMayoresJob.php`

---

## 2026-08-06 · UX

### El sidebar de entidades (88 items) era ilegible sin búsqueda

**Síntoma:** La lista vertical de 88 entidades en `/buscador-contratos-mayores/{entidad}` ocupaba toda la pantalla. Imposible navegar.

**Solución:**
1. Agregar buscador Alpine.js con ícono de lupa (luego movido a la derecha por colisión con texto)
2. Límite de 20 entidades visibles por defecto
3. Botón "Ver las 88 entidades ▼" para expandir
4. Al buscar, se filtran todas sin límite
5. Mobile: chips horizontales scrolleables (solo 15 primeras)

**Archivo:** `buscador-mayores-regional.blade.php`

---

### La tabla de Contratos Mayores desbordaba el contenedor en modo regional

**Síntoma:** Columnas con texto largo (ej: "Descripción" con 200+ caracteres) empujaban la tabla fuera del ancho del contenedor.

**Causa:** Múltiples elementos con `overflow: visible` (clase e inline style) en la cadena de contenedores del Livewire. La tabla sin `table-layout: fixed` expandía sus columnas al contenido.

**Solución:**
1. `#regional-mayores-content { overflow-x: auto }` con `max-width: 100%`
2. `[wire\:id]` y sus divs hijos con `max-width: 100% !important`
3. Elementos con `style="overflow: visible"` sobreescritos con `overflow-x: auto !important`
4. `<table>` con `min-width: 700px` para forzar scroll horizontal en vez de desbordar

**Archivo:** `buscador-mayores-regional.blade.php`

---

### El ícono de lupa en el filtro de entidades colisionaba con el texto

**Síntoma:** El input "Filtrar entidad..." tenía el ícono `left-2.5` que se superponía al texto ingresado.

**Causa:** Posicionamiento absoluto del SVG a la izquierda con poco espacio de padding (`pl-7`, 28px) para el texto. El ícono (14px) terminaba en 24px y el texto empezaba en 28px — margen insuficiente.

**Solución:** Mover el ícono a la derecha: `right-2.5` + `pr-8` en el input. El texto usa el lado izquierdo sin obstrucción.

**Archivo:** `buscador-mayores-regional.blade.php`

---

## 2026-08-06 · SEO Técnico

### El sitemap se regeneraba cada 1 hora on-demand

**Riesgo:** `Cache::remember('sitemap_xml', 3600)` — cada hora, el primer visitante de `/sitemap.xml` disparaba 3 llamadas API + 2 queries DB para regenerar ~672 URLs.

**Mitigación propuesta:** Subir TTL a 24h o implementar pre-warm vía cron. (Pendiente de implementación.)

---

### URLs departamentales duplicadas en 2 patrones

**Síntoma:** `/buscador-publico/ancash` y `/contratos-estado/ancash` apuntaban al mismo contenido con canonical `url()->current()`. Google veía duplicate content.

**Solución:** Canonical de `/buscador-publico/{dep}` apunta a `/contratos-estado/{dep}`. Solo `/contratos-estado/` está en el sitemap.

**Archivo:** `buscador-publico-regional.blade.php`

---

## 📋 Pendientes conocidos

| Issue | Prioridad | Notas |
|---|---|---|
| Sitemap: subir cache a 24h o pre-warm | Media | Riesgo bajo, 672 URLs es poco |
| FAQ schema automatizado por post | Baja | Requiere parsear H2 del markdown |
| Landing pages para keywords nuevas ("tdr que es", 258 imp) | Media | Fase 2 del plan SEO |
| Core Web Vitals no medidos | Media | PageSpeed Insights pendiente |
| OG image (`vigilante-seace-og.webp`) | Baja | Placeholder, no existe el archivo aún |
| Internal linking desde páginas principales al blog | Media | Fase 2 del plan SEO |
