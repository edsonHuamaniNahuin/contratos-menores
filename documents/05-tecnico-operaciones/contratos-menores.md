# Contratos Menores

## Fuente de Datos
- **API:** REST pública del SEACE (`gob.pe/api/v1/buscadorpublico`)
- **Autenticación:** No requiere
- **Formato:** JSON
- **Service:** `SeaceBuscadorPublicoService`

## Flujo de Búsqueda (Usuario)

1. Usuario accede a `/buscador-publico`
2. `BuscadorPublico` (Livewire) renderiza filtros
3. Al escribir, hace llamadas AJAX a la API SEACE en tiempo real
4. Resultados se muestran en tabla con paginación
5. Acciones por contrato: Seguimiento, Ver, Descargar TDR, Analizar, Cotizar

### Filtros Soportados
- Palabra clave (texto)
- Entidad (autocompletado desde API SEACE)
- Objeto de contratación (select)
- Estado (select)
- Departamento → Provincia → Distrito (cascada)
- Página + registros por página
- Ordenamiento (código, entidad, estado, fecha)

## Escaneo Automático

### Pipeline A: Notificaciones (Telegram/WhatsApp)
- **Job:** `ImportarTdrNotificarJob`
- **Schedule:** Cada 2 horas (06:00-20:00 Lima)
- **Flujo:**
  1. Consulta `SeaceBuscadorPublicoService::buscarContratos()` con `page_size=150`
  2. Filtra por fecha (`fecPublica`)
  3. Para cada suscriptor: compara keywords → envía notificación
  4. **Dedup:** tabla `notification_sends` por (user, canal, proceso)
  5. A las 06:00 también escanea procesos de ayer

### Pipeline B: Analytics (Dashboard)
- **Job:** `ImportarContratosDiarioJob`
- **Schedule:** Diario a las 02:00 AM
- **Flujo:**
  1. Itera los 25 departamentos
  2. Consulta API con `page_size=150` por departamento
  3. Filtra por fecha de ayer
  4. `Contrato::updateOrCreate()` → tabla `contratos`
  5. Alimenta dashboard con stats, charts, heatmap

## Análisis de TDR (PDF → IA)

1. Usuario hace clic en "Analizar"
2. Descarga PDF desde API pública (`SeacePublicArchivoService`)
3. Guarda en `contrato_archivos` + storage local
4. Cache check: `TdrAnalisis` por `contrato_archivo_id`
5. PDFs < 500KB: síncrono. PDFs >= 500KB: `AnalizarTdrJob` (async)
6. Envía al Python µservice (`POST /analyze-tdr`)
7. Gemini IA analiza y devuelve JSON estructurado
8. Resultado guardado en `tdr_analisis`

## Seguimiento

- **Tabla:** `contrato_seguimientos`
- **Clave:** `user_id` + `contrato_seace_id` (unique)
- **Modelo:** `ContratoSeguimiento`
- **Ruta:** `/seguimientos` (requiere `follow-contracts`)

## Tablas Relacionadas

| Tabla | Uso |
|---|---|
| `contratos` | Contratos persistidos |
| `contrato_archivos` | PDFs descargados |
| `tdr_analisis` | Resultados de análisis IA |
| `contrato_seguimientos` | Seguimientos de usuario |
| `notified_processes` | Procesos notificados (dedup) |
| `notification_sends` | Historial de envíos por usuario |
