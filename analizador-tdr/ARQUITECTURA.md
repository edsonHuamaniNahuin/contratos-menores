# 📐 ARQUITECTURA DEL MICROSERVICIO

## 🎯 Visión General

El **Analizador TDR SEACE** es un microservicio Python especializado que implementa un pipeline RAG (Retrieval-Augmented Generation) de **extracción**, no de conversación.

### Diferencia clave: RAG de Extracción vs RAG de Chatbot

| Característica | RAG de Chatbot | RAG de Extracción (Este proyecto) |
|----------------|----------------|-----------------------------------|
| **Objetivo** | Responder preguntas iterativas | Extraer información estructurada |
| **Entrada** | Pregunta del usuario | Documento completo (PDF) |
| **Salida** | Respuesta en lenguaje natural | JSON estructurado validado |
| **Interacción** | Conversacional (múltiples turnos) | Una sola llamada (stateless) |
| **Prompt** | Dinámico según pregunta | Fijo, especializado en análisis |

## 🏗️ Arquitectura de Componentes

```
┌──────────────────────────────────────────────────────────────┐
│                      FASTAPI APPLICATION                      │
│                         (main.py)                             │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────────┐
│                   TDRAnalyzerService                          │
│                 (Orquestador Principal)                       │
└───┬──────────────────┬────────────────────┬──────────────────┘
    │                  │                    │
    ▼                  ▼                    ▼
┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐
│ PDFProcessor │  │ RAGExtractor │  │   LLMFactory         │
│              │  │              │  │   ├─ GeminiClient    │
│ PyMuPDF      │  │ Pattern      │  │   ├─ OpenAIClient   │
│ (fitz)       │  │ Matching     │  │   └─ AnthropicClient│
└──────────────┘  └──────────────┘  └──────────────────────┘
       │                  │                    │
       ▼                  ▼                    ▼
   [Texto]          [Fragmentos]          [Análisis]
                                               │
                                               ▼
                                   ┌──────────────────────┐
                                   │  Pydantic Validator  │
                                   │  (TDRAnalysisResponse)│
                                   └──────────────────────┘
                                               │
                                               ▼
                                          [JSON Final]
```

## 📦 Módulos y Responsabilidades

### 1. `main.py` - Aplicación FastAPI
**Responsabilidad:** Exponer endpoints HTTP y manejar requests/responses.

**Endpoints:**
- `GET /` - Root informativo
- `GET /health` - Health check
- `POST /analyze-tdr` - Endpoint principal de análisis

**Características:**
- CORS middleware para integraciones
- Validación de archivos (PDF, tamaño)
- Manejo centralizado de excepciones
- Documentación automática (Swagger/ReDoc)

### 2. `config.py` - Configuración
**Responsabilidad:** Centralizar toda la configuración de la aplicación.

**Usa:** Pydantic Settings para validación automática de variables de entorno.

**Ventajas:**
- Type hints y validación automática
- Autocomplete en IDE
- Valores por defecto seguros

### 3. `app/models/schemas.py` - Modelos Pydantic
**Responsabilidad:** Definir contratos de entrada/salida.

**Modelos:**
- `TDRAnalysisResponse` - Estructura de salida (CRÍTICO)
- `TDRAnalysisRequest` - Parámetros opcionales
- `HealthCheckResponse` - Estado del servicio
- `ErrorResponse` - Errores estructurados

### 4. `app/services/pdf_processor.py` - Procesador de PDF
**Responsabilidad:** Extraer texto de archivos PDF.

**Tecnología:** PyMuPDF (fitz)

**Métodos:**
- `extract_text_from_pdf()` - Extrae texto completo
- `extract_metadata()` - Extrae metadatos (opcional)

**Manejo de errores:**
- PDFs corruptos
- PDFs escaneados sin texto
- Archivos inválidos

### 5. `app/services/rag_extractor.py` - RAG de Extracción
**Responsabilidad:** Recuperar fragmentos relevantes del texto.

**Estrategia:**
1. Divide el texto en chunks con overlap
2. Aplica pattern matching por categoría:
   - Requisitos del postor
   - Penalidades
   - Forma de pago
   - Plazos de ejecución
   - Presupuesto referencial
3. Recupera top-K chunks por categoría
4. Construye contexto estructurado para el LLM

**Ventajas sobre búsqueda semántica:**
- No requiere embeddings
- Más rápido para documentos estructurados
- Menos dependencias (sin vector DB)
- Patrones específicos para TDRs peruanos

### 6. `app/services/analyzer_service.py` - Orquestador
**Responsabilidad:** Coordinar el pipeline completo.

**Flujo:**
```
PDF bytes → [Extract] → Full Text
           ↓
Full Text → [RAG] → Fragments
           ↓
Fragments → [Build Context] → Structured Context
           ↓
Context → [LLM] → Raw JSON
           ↓
Raw JSON → [Validate] → TDRAnalysisResponse
```

**Logging:** Registra cada paso para debugging.

### 7. `app/services/llm/` - Clientes LLM

#### `base_client.py` - Clase Base Abstracta
Define la interfaz común para todos los clientes LLM.

**Método abstracto:**
- `analyze_tdr(context: str) -> Dict`

**SYSTEM_PROMPT:** Prompt engineering especializado en análisis de licitaciones peruanas.

**Instrucciones del prompt:**
- Actuar como analista experto en SEACE
- Ignorar relleno legal
- Enfocarse en requisitos accionables
- Identificar riesgos y penalidades
- Asignar score de compatibilidad
- Devolver SOLO JSON válido

#### `gemini_client.py` - Cliente Gemini (PRINCIPAL)
**Modelo:** `gemini-2.0-flash-exp`

**Características:**
- `response_mime_type: "application/json"` - Fuerza salida JSON
- Temperature: 0.2 (respuestas consistentes)
- Safety settings: BLOCK_NONE (permitir análisis técnico)

**Ventajas:**
- Más rápido que GPT-4o
- Más económico
- Excelente con salidas estructuradas

#### `openai_client.py` - Cliente OpenAI (ALTERNATIVO)
**Modelo:** `gpt-4o`

**Características:**
- `response_format: {"type": "json_object"}` - Structured Outputs
- Cliente asíncrono (AsyncOpenAI)

**Ventajas:**
- Mejor comprensión de contexto largo
- Más confiable para análisis complejos

#### `anthropic_client.py` - Cliente Claude (ALTERNATIVO)
**Modelo:** `claude-3-5-sonnet-20241022`

**Características:**
- System prompt separado
- Cliente asíncrono (AsyncAnthropic)

**Ventajas:**
- Mejor análisis de riesgos
- Excelente seguimiento de instrucciones

#### `factory.py` - Factory Pattern
**Responsabilidad:** Instanciar el cliente LLM correcto.

**Patrón de diseño:** Factory Method

**Ventajas:**
- Fácil agregar nuevos proveedores
- Cambio de proveedor sin modificar código
- Validación centralizada de API keys

## 🔄 Flujo de Datos Completo

```
1. Cliente HTTP envía POST /analyze-tdr con PDF
   ↓
2. FastAPI valida archivo (extensión, tamaño)
   ↓
3. TDRAnalyzerService.analyze_tdr_document()
   ├─ PDFProcessorService.extract_text_from_pdf()
   │  └─ PyMuPDF (fitz) → texto completo
   ├─ RAGExtractionService.extract_relevant_fragments()
   │  ├─ Divide en chunks
   │  ├─ Aplica pattern matching
   │  └─ Recupera top-K por categoría
   ├─ RAGExtractionService.build_context_for_llm()
   │  └─ Combina fragmentos en contexto estructurado
   ├─ LLMFactory.create_client()
   │  └─ Instancia GeminiClient/OpenAIClient/AnthropicClient
   ├─ llm_client.analyze_tdr(context)
   │  ├─ Envía SYSTEM_PROMPT + contexto
   │  └─ Recibe JSON raw
   └─ TDRAnalysisResponse(**json_raw)
      └─ Pydantic valida estructura
   ↓
4. FastAPI devuelve JSON validado al cliente
```

## 🛡️ Validación en Capas

### Capa 1: FastAPI (Entrada)
- Validación de extensión de archivo
- Validación de tamaño (max 10MB)
- Validación de parámetros (llm_provider)

### Capa 2: Servicios (Procesamiento)
- PDFProcessor: Valida que el PDF tenga texto
- RAGExtractor: Valida que se recuperen fragmentos
- LLMClient: Valida respuesta JSON parseable

### Capa 3: Pydantic (Salida)
- TDRAnalysisResponse valida:
  - `resumen_ejecutivo`: min 50, max 1000 chars
  - `requisitos_tecnicos`: mínimo 1 item
  - `reglas_de_negocio`: mínimo 1 item
  - `score_compatibilidad`: rango 1-10

## 🚀 Escalabilidad

### Actual (v1.0)
- Procesamiento síncrono
- Una request a la vez por worker
- Sin persistencia

### Futuras mejoras
- Queue system (Celery/RQ) para procesamiento asíncrono
- Cache de análisis (Redis)
- Vector database para búsqueda semántica
- OCR para PDFs escaneados
- Multi-tenant con rate limiting

## 🔐 Seguridad

### Implementado
- Validación de tipo de archivo
- Límite de tamaño de archivo
- Variables de entorno para API keys
- Safety settings en Gemini

### Recomendado para producción
- Rate limiting por IP
- Autenticación con API keys
- HTTPS obligatorio
- CORS restrictivo
- Logging de auditoría

## 📊 Métricas y Observabilidad

### Logging actual
- Info: Pasos del pipeline
- Debug: Respuestas LLM (primeros 500 chars)
- Error: Excepciones con traceback

### Recomendado
- Prometheus metrics
- Distributed tracing (OpenTelemetry)
- Application Performance Monitoring (APM)

## 🔗 Integración con Vigilante SEACE (Laravel)

El microservicio está diseñado para ser consumido por el proyecto Laravel principal.

Ver: [INTEGRACION_LARAVEL.md](INTEGRACION_LARAVEL.md)

---

**Última actualización:** 3 de febrero de 2026  
**Versión de arquitectura:** 1.0
