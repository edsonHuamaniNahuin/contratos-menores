# Análisis de TDR con IA

## Arquitectura

```
Laravel (PHP)                          Python (FastAPI)
─────────────                          ────────────────
TdrAnalysisService                     main.py
MayoresTdrService                      app/services/analyzer_service.py
  │                                      │
  ├─ Descarga PDF (SEACE/HTTP)           ├─ Extrae texto (PyMuPDF)
  ├─ Cache check (DB)                    ├─ Recupera fragmentos relevantes
  ├─ Lock atómico (Cache)                ├─ Analiza con Gemini 2.5 Flash
  └─ POST /analyze-tdr ──────────────►   └─ Valida con Pydantic
     (multipart: file + JWT auth)            └─ Devuelve JSON estructurado
```

## Microservicio Python (`analizador-tdr/`)

- **Puerto:** 8001 (prod) / 8002 (QA)
- **Framework:** FastAPI + Uvicorn
- **LLM:** Gemini 2.5 Flash (configurable: openai, anthropic)
- **Endpoints:**
  - `POST /analyze-tdr` — Análisis general de TDR
  - `POST /analyze-direccionamiento` — Detección de corrupción
  - `POST /compatibility/score` — Score de compatibilidad
  - `POST /generate-proforma` — Proforma técnica
- **Auth:** JWT HMAC-SHA256 (InterServiceJwt en Laravel)

## Resultado del Análisis

```json
{
  "resumen_ejecutivo": "...",
  "requisitos_calificacion": [
    {"descripcion": "...", "evidencia": "..."}
  ],
  "reglas_ejecucion": [
    {"descripcion": "...", "plazo": "..."}
  ],
  "penalidades": [
    {"descripcion": "...", "monto": "..."}
  ],
  "presupuesto_referencial": "...",
  "compatibilidad": {"score": 85, "nivel": "ALTO"}
}
```

## Caché y Deduplicación

### Menores
- Cache key: `(contrato_archivo_id, tipo_analisis, proveedor, modelo)`
- Lock: `Cache::lock('tdr:analyze:general:{id}', 180s)`
- TTL de caché configurable (`TDR_ANALYSIS_CACHE_MINUTES`)

### Mayores
- Cache key: `ocid`
- Lock: `Cache::lock('tdr:mayor:analyze:{ocid}', 180s)`
- Mismo microservicio Python, diferente tabla de resultados

## Procesamiento Async

### Menores
- PDFs >= 500KB → `AnalizarTdrJob` (queue)
- PDFs < 500KB → síncrono (request HTTP)
- Polling: `wire:poll.3s="checkAnalisisJob"`
- Timeout: 5 minutos

### Mayores
- Siempre async (PDFs externos)
- Job: `AnalizarTdrMayorJob`
- Polling: `wire:poll.3s="checkAnalisisMayor"`
- Tracking: registro `pendiente` → actualizado por job

## Configuración

```env
ANALIZADOR_TDR_URL=http://127.0.0.1:8001
ANALIZADOR_TDR_ENABLED=true
ANALIZADOR_TDR_TIMEOUT=120
ANALIZADOR_TDR_MAX_FILE_SIZE=10485760
ANALIZADOR_TDR_PROVIDER=gemini
ANALIZADOR_TDR_MODEL=gemini-2.5-flash
ANALIZADOR_TDR_SECRET=<hmac-key>
```
