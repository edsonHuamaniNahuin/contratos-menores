# Contratos Mayores (Ley N° 32069)

## Fuente de Datos
- **API:** OCDS REST pública del OECE (`https://contratacionesabiertas.oece.gob.pe/api/v1/releases`)
- **Autenticación:** No requiere
- **Limitación:** 20 ítems por página, sin búsqueda por texto ni filtros
- **Storage local:** Tabla `contratos_mayores`

## Bandeja de Búsqueda (`/buscador-contratos-mayores`)

### Componentes
- `BuscadorMayores` (Livewire) — orquesta búsqueda, filtros, análisis
- `SeaceMayoresService` — lógica de búsqueda en BD local + fetching desde API
- `ContratoMayor` (modelo) — `ocid` como unique key

### Filtros
- **Palabra Clave:** búsqueda LIKE en `entidad_nombre`, `nomenclatura`, `descripcion_objeto`, `entidad_ruc`
- **Entidad:** autocomplete con Alpine.js + Livewire debounce 400ms. Sugerencias desde `EntidadesMayoresService` (cache 7 días, 88 entidades)
- **Objeto:** Bien, Servicio, Obra (`goods`, `services`, `works`)
- **Estado:** dinámico desde `contratos_mayores.estado` (`CONVOCADO`, `ADJUDICADO`, `CONTRATADO`)
- **Vigencia:** 4 criterios (OCDS tag, items.status, statusDetails, tenderPeriod.endDate)

### UI
- **Vista tabla** (desktop): Entidad, Nomenclatura, Objeto, Descripción, Monto, Fecha, Vigencia, Acciones (⋮)
- **Vista cards** (mobile): mismo contenido en formato compacto
- **Dropdown acciones:** Alpine.js con posicionamiento dinámico (abre arriba si cerca del borde), tooltips con descripción de cada acción
- **Loading indicators:** badge "Buscando..." con spinner en cada filtro al escribir/seleccionar
- **Paginación:** 5/10/15/20 registros por página, selector responsive

### Acciones Disponibles
1. **Ver detalle** — modal con todos los campos + documentos del proceso + acciones
2. **Descargar TDR** — link directo al documento
3. **Seguimiento** — toggle con badge "Siguiendo" ✓, notificación toast
4. **Analizar con IA** — análisis completo del TDR con Gemini
5. **Direccionamiento** — auditoría forense anticorrupción
6. **Crear Proforma** — proforma técnica con estructura de costos
7. **Ver Partes** — entidades involucradas, postores, adjudicatarios desde OCDS raw data

### Modal de Análisis TDR
- Resumen ejecutivo + formato Mayores (metadatos_proceso, requisitos_admisibilidad_y_calificacion, factores_puntaje_evaluacion, parametros_consorcio, garantias_y_penalidades)
- Contexto del proceso (Entidad, Objeto, Estado, Método)
- Datos del proceso (Publicación, Moneda, RUC, Monto referencial)
- **Documentos del proceso:** lista de archivos extraídos del ZIP/RAR con links directos
- Score de compatibilidad con suscriptores

### Modal de Direccionamiento
- Score radial SVG con color dinámico (verde ≤25%, ámbar 26-65%, rojo 66-100%)
- Badge de estado (CONFORME Y LIMPIO / RIESGO MODERADO / EVIDENCIA CLARA DE DIRECCIONAMIENTO)
- Fundamento analítico
- Anomalías detectadas como cards expandibles (extracto sospechoso, análisis de proporcionalidad, argumento legal)
- Badges de impacto (Crítico/Alto/Medio/Bajo)

### Modal de Proforma
- Header con empresa y rubro
- Tabla de ítems de cotización (descripción, unidad, cantidad, precio unitario, subtotal)
- Estructura de costos con dots de colores (costo directo, gastos generales, utilidad, IGV)
- Total estimado en card gradiente
- Advertencias financieras con ícono de warning
- Análisis de viabilidad, recomendación de consorcio, condiciones
- Botones Imprimir + Descargar Word

### Modal de Partes Involucradas
- Lista de entidades con RUC linkeado a SUNAT (`e-consultaruc.sunat.gob.pe`)
- Roles: Comprador, Entidad Convocante, Ganador, Postor con badges de colores
- Dirección, ubicación, teléfono (datos basura ocultos)

## Extracción de Archivos (ZIP/RAR)
- `ArchiveExtractorService` detecta formato por magic bytes (`%PDF`, `PK\x03\x04`, `Rar!`)
- Soporta ZIP (ZipArchive PHP) y RAR (7z.exe bundled en `bin/`)
- Extrae a `storage/app/tdr-extracted/mayores/{ocid}/`
- `selectBestPdf()`: prioriza PDF con "TDR" en el nombre, si no → overlay para elegir
- `documentos_extraidos` table para tracking + `documento_extraido_id` FK en análisis
- DOCX detectado por `[Content_Types].xml` + `word/document.xml` dentro del ZIP

## Integración con Python Analizador
- `fetchFromApi()` en `SeaceMayoresService` llama a la API OCDS
- `mapearCampos()` parsea fechas ISO 8601 con Carbon
- Arrays (`proveedores`, `datos_raw`) se json_encode antes del insert
- Análisis vía `MayoresTdrService` → `AnalizadorTDRService` → API Python (`/analyze-tdr`, `/analyze-direccionamiento`, `/analyze-proforma`)
- DOCX se extrae texto vía `zipfile` + `xml.etree` (Gemini multimodal no soporta DOCX)

## Importación Automática
- `ImportarContratosMayoresJob`: 80 páginas × 20 registros cada 90 min (2 cron entries interleaved)
- 140 contratos en BD local (importados exitosamente luego del fix de fechas y API)
- Deduplicación por cache diario de OCIDs + unique constraint
- Cache cleanup diario 03:30 AM (OCIDs 2+ días)

## Sistema de Permisos Granulares
8 nuevos permisos en `2026_07_19_000001_add_contratos_mayores_permissions`:
- `view-buscador-mayores` — Ver bandeja contratos mayores
- `view-detalle-mayores` — Ver detalle de contrato mayor
- `download-tdr-mayores` — Descargar TDR de contrato mayor
- `follow-mayores` — Seguimiento de contrato mayor
- `analyze-tdr-mayores` — Analizar TDR con IA (contrato mayor)
- `detect-direccionamiento-mayores` — Detectar direccionamiento (contrato mayor)
- `create-proforma-mayores` — Crear proforma técnica (contrato mayor)
- `view-partes-mayores` — Ver partes involucradas (contrato mayor)

Asignados a roles `admin` y `proveedor-premium`. Los botones siempre visibles — si no tiene permiso, muestra modal "Funcionalidad Premium" con link a `/planes`.

## Planes y Precios
- **Gratuito** (S/ 0): buscador, descarga TDR, dashboard
- **Premium** (S/ 49/mes): análisis IA, alertas, score, direccionamiento, proformas
- **Premium + Contratos Mayores** (S/ 68/mes): todo Premium + bandeja Mayores, análisis IA para >8UIT, postores

Soporte en MercadoPago/Openpay con plan `Subscription::PLAN_MAYORES_PREMIUM`. Backdoor `?backdoor=1` para pruebas (todos los planes a S/ 1).

## Seguimientos
- `ContratoSeguimientoMayor` guarda snapshot completo del contrato
- Panel `/seguimientos` muestra calendario con ambos tipos (Menores + Mayores mergeados)
- IDs prefijados `mayor_` para distinguir en eliminación
- Detalle con toda la info del snapshot: badges, grid, fechas, descripción, acciones

## Configuración de Alertas
- `recibir_menores` / `recibir_mayores` en Telegram, WhatsApp y Email subscriptions
- Checkboxes toggle individuales en `/configuracion-alertas` (fix: compartían `nuevo_recibir_mayores`)
- "Contratos Mayores" oculto si usuario no tiene permiso `analyze-tdr-mayores`
- Admin ve todas las suscripciones pero agrega a su propia cuenta

## Notificaciones (Telegram + WhatsApp)
- `NotificarContratosMayoresJob` cada 2h, 6h-20h
- Filtra por `recibir_mayores = true` y keywords
- Envía TODOS los contratos que coinciden (fix: removido `break`)
- WhatsApp: Interactive List Message con header "Acciones del Proceso", body, footer, 6 acciones
- Callbacks `mayor_{accion}_{ocid}` procesados por `WhatsAppBotListener`
- Telegram: inline keyboard con botones URL
- `registrarNotificacion()` llamado en cada envío exitoso

## Rebranding
- "LICITACIONES MYPE" → "Vigilante SEACE" en login, register, dashboard, landing
- Menú: "Contratos < 8 UIT" / "Contratos > 8 UIT" (SEO-friendly)
- Landing actualizada con 3 tiers de precio, features de Mayores, FAQ actualizado
- Schema.org actualizado con 3 offers (0, 49, 68)
- Badge "NUEVO" flotante en sidebar dashboard
