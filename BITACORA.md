# 📓 Bitácora — Errores y Soluciones

> Historial de incidencias, bugs y su resolución en Vigilante SEACE.
> Formato: fecha · categoría · commit · síntoma → causa → solución.

---

## 2026-08-15 · Análisis IA WhatsApp · `907b65e8` + `ae414b79`

### TDR DOCX guardado con extensión .pdf rompía el análisis de direccionamiento ("The document has no pages")

**Síntoma:** Al presionar "🔍 Detectar Direccionamiento" en el bot de WhatsApp para el contrato `CP-ABR-6-2026-FONAFE-1`, devolvía:

```
❌ Error HTTP 400: {"detail":"Error en análisis direccionamiento PDF directo:
400 INVALID_ARGUMENT. {'error': {'code': 400, 'message':
'The document has no pages.', 'status': 'INVALID_ARGUMENT'}}"}
```

En los logs: `AnalizadorTDR: Error en direccionamiento {"file":"06be05fb....pdf", ...}`.

**Causa raíz (2 capas):**
1. El TDR descargado del SEACE era un **Microsoft Word 2007+ (DOCX)** pero el sistema lo guardaba SIEMPRE con extensión `.pdf` (hardcodeada en los bot listeners)
2. El `gemini_client.py` hardcodeaba `mime_type="application/pdf"` al enviar el documento a Gemini multimodal. Gemini NO soporta DOCX → "no pages"
3. Primer intento de fix (mandar MIME DOCX) falló también: `Unsupported MIME type: application/vnd.openxmlformats-officedocument.wordprocessingml.document` — Gemini directamente no acepta DOCX en modo multimodal

**Solución (3 capas):**
1. **Bots PHP** (`WhatsAppBotListener` + `TelegramBotListener`): helper `detectarExtensionDocumento()` que detecta el tipo real por **magic bytes** (`%PDF` → pdf, `PK` → docx, OLE2 → doc) y guarda el temp con la extensión real. También valida que la descarga no venga vacía.
2. **Pipeline IA** (`analyzer_service.py`): en `analyze_direccionamiento_document()` y `generate_proforma_document()`, si los magic bytes indican Word (PK/OLE2), se **extrae texto con `_extract_docx_text()`** y se analiza por la ruta textual (RAG + prompt forense), NUNCA por multimodal.
3. **Gemini client** (`gemini_client.py`): helper `_detect_document_mime()` por magic bytes como red de seguridad para PDFs con extensión incorrecta.

**Verificación:** El archivo que fallaba ahora devuelve `score: 95, veredicto: ALTAMENTE DIRECCIONADO`.

**Lección:** Nunca confiar en la extensión del archivo para determinar el formato. Los documentos del SEACE vienen en PDF y Word mezclados. Detectar por magic bytes (primeros 4-8 bytes) es la única forma confiable. Y recordar que Gemini multimodal solo soporta PDF/imágenes/audio/video — DOCX siempre debe ir por extracción de texto.

**Archivos:** `analizador-tdr/app/services/llm/gemini_client.py`, `analizador-tdr/app/services/analyzer_service.py`, `app/Console/Commands/WhatsAppBotListener.php`, `app/Console/Commands/TelegramBotListener.php`

---

## 2026-08-14 · Monitoreo · `ffa8ba3e`

### El item "Monitoreo del Sistema" no aparecía en el menú aunque el permiso estaba asignado

**Síntoma:** El permiso `view-monitoreo-sistema` se creó vía migración y se asignó al rol admin, pero el item del menú no aparecía en `/dashboard` ni en localhost.

**Causa:** El sistema de permisos usa `@can('slug')` en Blade, que consulta el **Gate de Laravel**. Cada permiso debe registrarse explícitamente con `Gate::define('slug', fn ($user) => $user->hasPermission('slug'))` en `AppServiceProvider::boot()`. Se creó el permiso en BD pero no se registró en el Gate → `@can` siempre devolvía false.

**Solución:** Agregar `Gate::define('view-monitoreo-sistema', ...)` en `AppServiceProvider.php`.

**Lección:** En este proyecto, crear un permiso nuevo requiere 4 pasos: (1) registro en BD, (2) `Gate::define` en AppServiceProvider, (3) item de menú con `@can`, (4) ruta con `middleware('can:...')`. Si falta el paso 2, todo lo demás falla silenciosamente.

**Archivos:** `app/Providers/AppServiceProvider.php`, `app/Livewire/RolesPermisos.php`

---

## 2026-08-14 · Contratos Mayores · `1d21e1b0`, `11cf3d80`, `d3dc8f26`, `3ae4df83`, `6a4f772f`

### Los estados de Contratos Mayores quedaban congelados para siempre (nunca se actualizaban)

**Síntoma:** SEACE mostraba procesos con estado ADJUDICADO pero el portal los mostraba con el estado viejo. El usuario detectó que "como almacenamos los procesos en BD, los estados no cambian".

**Causa raíz (investigación completa):**
1. `ImportarContratosMayoresJob` solo hacía INSERT de contratos NUEVOS; los existentes se omitían para siempre (`if isset($storedMap[$ocid]) continue`)
2. El endpoint `/releases` de la API OCDS solo expone los últimos ~2 días de eventos (10,000 releases, 500 páginas × 20). Un contrato que cambia de estado semanas después ya no está en la ventana
3. El job escaneaba solo 80 páginas (~horas), ni siquiera el día completo

**Descubrimientos clave de la API OCDS (contratacionesabiertas.oece.gob.pe):**
- `/releases`: eventos completos pero SOLO última ventana de ~2 días. 500 páginas máximo (page 501 = 404). Ignora `limit`, `fechaDesde`, `dateFrom`
- `/search`: 2,748,755 registros históricos, filtro `year` funciona (45,504 en 2026), pero el compiledRelease viene STRIPPED (sin items/status/awards) — inútil para estados
- `/records?ocid=X`: devuelve el **compiledRelease completo** (con items, status, awards) de CUALQUIER contrato sin importar antigüedad — LA FUENTE DE VERDAD para refrescar

**Solución (estrategia día/noche):**
1. **Import incremental** cada 3h con 15 páginas: descubre contratos nuevos
2. **`RefrescarEstadosContratosMayoresJob`** con filtro de antigüedad por `fecha_publicacion`:
   - Madrugada 03:00: refresca los últimos 30 días (~3,899 contratos, ~38 min)
   - Día 13:00 y 18:00: refresca los últimos 7 días, 300 c/u
   - `antiguedadDias = 0` = escaneo global manual (solo bajo demanda)
3. **Salvaguardas**: abort automático si la API cae (15 fallos seguidos), bulk UPDATE para sin-cambios, progreso cada 250, timeout 2h

**Bug introducido durante el proceso:** Se despacharon 4 jobs globales con el constructor viejo (sin `antiguedadDias`) y luego se desplegó el constructor nuevo. Al re-hidratar, la propiedad tipada `$antiguedadDias` no existía en el payload serializado → `Typed property ... must not be accessed before initialization` → 3 jobs murieron (5,821 de 11,332 refrescados).

**Fix:** `protected int $antiguedadDias = 30;` con valor por defecto en la propiedad.

**Lección:** Nunca desplegar un job con una propiedad nueva sin valor por defecto mientras hay jobs serializados del constructor viejo en la cola.

**Archivos:** `app/Jobs/ImportarContratosMayoresJob.php`, `app/Jobs/RefrescarEstadosContratosMayoresJob.php`, `app/Services/SeaceMayoresService.php`, `routes/console.php`

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
