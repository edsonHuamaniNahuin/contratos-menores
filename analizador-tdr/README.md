# 🤖 Analizador TDR SEACE - Microservicio de Análisis con IA (2026)

Microservicio Python especializado en análisis automatizado de Términos de Referencia (TDR) del Sistema Electrónico de Contrataciones del Estado (SEACE) de Perú.

**Actualizado para 2026:** Gemini 2.5/3 Flash, procesamiento asíncrono y batch processing.

## 🎯 Objetivo

Este microservicio implementa un **pipeline RAG de extracción** (NO es un chatbot) que:
- Recibe uno o múltiples PDFs de TDR del SEACE
- Extrae y analiza información estructurada en paralelo
- Devuelve JSON con análisis técnico completo

## ✨ Características

- **Pipeline RAG de Extracción:** Recupera fragmentos específicos (requisitos, penalidades, pagos, plazos, presupuesto)
- **Salida Estructurada:** JSON validado con Pydantic
- **Multi-LLM:** Gemini 2.5 Flash (1M tokens), GPT-4o-mini, Claude 3.5 Haiku
- **Procesamiento por Lotes:** Analiza 3-10 documentos en paralelo (optimizado para scrapers)
- **Análisis Especializado:** Prompt engineering enfocado en licitaciones peruanas
- **API REST Async:** FastAPI con procesamiento verdaderamente asíncrono
- **Optimizado para Volumen:** 360 docs/día = 24% del Free Tier de Gemini

## 🏗️ Arquitectura

```
PDF (TDR) 
   ↓
[PDF Processor] → Extrae texto con PyMuPDF
   ↓
[RAG Extractor] → Recupera fragmentos relevantes
   ↓
[LLM Client] → Analiza con Gemini/GPT-4o/Claude
   ↓
[Pydantic Validator] → Valida estructura JSON
   ↓
JSON Estructurado
```

## � Inicio Rápido

### Instalación Automática (Windows)
```powershell
cd d:\xampp\htdocs\vigilante-seace\analizador-tdr
.\setup.ps1
# Edita .env y agrega tu GEMINI_API_KEY
python main.py
```

### Instalación Manual

**Prerrequisitos:** Python 3.10+

```bash
# 1. Navegar al proyecto
cd d:\xampp\htdocs\vigilante-seace\analizador-tdr

# 2. Crear entorno virtual
python -m venv venv
venv\Scripts\activate  # Windows
# source venv/bin/activate  # Linux/Mac

# 3. Instalar dependencias
pip install -r requirements.txt

# 4. Configurar API key
cp .env.example .env
# Edita .env y agrega:
# GEMINI_API_KEY=tu_api_key_aqui

# 5. Iniciar servidor
python main.py
```

**Obtener API Key de Gemini:** https://aistudio.google.com/app/apikey

## 🚀 Uso

### Iniciar el servidor

```bash
python main.py
```

El servidor estará disponible en: `http://localhost:8001`

### Documentación API

Una vez iniciado el servidor, accede a:
- **Swagger UI:** http://localhost:8001/docs
- **ReDoc:** http://localhost:8001/redoc

### Endpoints

#### 1. Health Check
```bash
curl http://localhost:8001/health
```

#### 2. Analizar TDR Individual
```bash
curl -X POST "http://localhost:8001/analyze-tdr" \
  -F "file=@tdr_seace.pdf"
```

#### 3. 🆕 Analizar Múltiples TDRs (Batch)
**Nuevo en v1.1.0** - Optimizado para scrapers que envían 3-10 documentos:

```bash
curl -X POST "http://localhost:8001/batch/analyze-tdrs" \
  -F "files=@tdr1.pdf" \
  -F "files=@tdr2.pdf" \
  -F "files=@tdr3.pdf"
```

**Respuesta del batch:**
```json
[
  {
    "filename": "tdr1.pdf",
    "status": "success",
    "analysis": {
      "resumen_ejecutivo": "...",
      "requisitos_tecnicos": [...],
      "score_compatibilidad": 8
    }
  },
  {
    "filename": "tdr2.pdf",
    "status": "success",
    "analysis": {...}
  },
  {
    "filename": "tdr3.pdf",
    "status": "error",
    "error": "El PDF no contiene texto extraíble"
  }
]
```

#### 4. 🆕 Estadísticas de Batch
```bash
curl http://localhost:8001/batch/stats
```

**Respuesta individual típica:**
```json
{
  "resumen_ejecutivo": "El TDR busca contratar servicios de desarrollo web...",
  "requisitos_tecnicos": [
    "Experiencia mínima de 3 años en desarrollo web",
    "Conocimiento en Laravel 10+ y Vue.js"
  ],
  "reglas_de_negocio": [
    "Entrega en 3 fases: análisis, desarrollo, despliegue",
    "Reuniones semanales obligatorias"
  ],
  "politicas_y_penalidades": [
    "Penalidad del 2% por día de retraso",
    "Garantía de fiel cumplimiento: 10% del monto"
  ],
  "presupuesto_referencial": "S/ 45,000.00",
  "score_compatibilidad": 8
}
```

### Cambiar proveedor LLM en tiempo de ejecución

```bash
curl -X POST "http://localhost:8001/analyze-tdr?llm_provider=openai" \
  -F "file=@tdr_seace.pdf"
```

Proveedores disponibles: `gemini`, `openai`, `anthropic`

## � Ejemplos de Código

### Python
```python
import requests

# Analizar un TDR
with open("tdr.pdf", "rb") as f:
    files = {"file": ("tdr.pdf", f, "application/pdf")}
    response = requests.post("http://localhost:8001/analyze-tdr", files=files)

if response.status_code == 200:
    analisis = response.json()
    print(f"Score: {analisis['score_compatibilidad']}/10")
```

### cURL con Batch
```bash
curl -X POST "http://localhost:8001/batch/analyze-tdrs" \
  -F "files=@tdr1.pdf" \
  -F "files=@tdr2.pdf" \
  -F "files=@tdr3.pdf"
```

### PHP (Laravel)
```php
use Illuminate\Support\Facades\Http;

$response = Http::timeout(120)
    ->attach('file', file_get_contents($pdfPath), 'tdr.pdf')
    ->post('http://localhost:8001/analyze-tdr');

$analisis = $response->json();
```

Ver [INTEGRACION_LARAVEL.md](INTEGRACION_LARAVEL.md) para integración completa.

## 📊 Esquema de Respuesta

El microservicio siempre devuelve un JSON con la siguiente estructura validada:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `resumen_ejecutivo` | `string` | Resumen técnico en 2-3 párrafos |
| `requisitos_tecnicos` | `array[string]` | Lista de requisitos técnicos |
| `reglas_de_negocio` | `array[string]` | Obligaciones del proveedor |
| `politicas_y_penalidades` | `array[string]` | Penalidades y garantías |
| `presupuesto_referencial` | `string \| null` | Monto estimado o null |
| `score_compatibilidad` | `int (1-10)` | Score de viabilidad |

## 🔧 Configuración Avanzada

### Variables de Entorno

Revisa [.env.example](.env.example) para todas las opciones de configuración:

- **APP_ENV:** `development` | `production`
- **DEBUG:** `True` | `False`
- **HOST:** `0.0.0.0` (default)
- **PORT:** `8001` (default)
- **DEFAULT_LLM_PROVIDER:** `gemini` | `openai` | `anthropic`
- **CHUNK_SIZE:** Tamaño de chunks para RAG (default: 1000)
- **TOP_K_CHUNKS:** Número de chunks a recuperar (default: 5)
- **MAX_FILE_SIZE_MB:** Tamaño máximo de PDF (default: 10)

### Obtener API Keys

- **Gemini:** https://aistudio.google.com/app/apikey
- **OpenAI:** https://platform.openai.com/api-keys
- **Anthropic:** https://console.anthropic.com/settings/keys

## 🧪 Testing

```bash
# Usar pytest para tests (próximamente)
pytest tests/
```

## 📁 Estructura del Proyecto

```
analizador-tdr/
├── main.py                     # Aplicación FastAPI
├── config.py                   # Configuración centralizada
├── requirements.txt            # Dependencias
├── .env.example               # Template de configuración
├── app/
│   ├── models/
│   │   └── schemas.py         # Modelos Pydantic
│   └── services/
│       ├── pdf_processor.py   # Extracción de PDF
│       ├── rag_extractor.py   # RAG de extracción
│       ├── analyzer_service.py # Orquestador principal
│       └── llm/
│           ├── base_client.py     # Clase base LLM
│           ├── gemini_client.py   # Cliente Gemini
│           ├── openai_client.py   # Cliente OpenAI
│           ├── anthropic_client.py # Cliente Claude
│           └── factory.py         # Factory de LLMs
└── temp/                      # Archivos temporales
```

## 🛠️ Stack Tecnológico

- **Framework:** FastAPI 0.110+ 
- **Python:** 3.10+
- **PDF Processing:** PyMuPDF
- **LLM:** Gemini 2.5 Flash (1M tokens) | GPT-4o-mini | Claude 3.5 Haiku
- **Validación:** Pydantic 2.8+
- **Servidor:** Uvicorn

## 📚 Documentación Adicional

- [ARQUITECTURA.md](ARQUITECTURA.md) - Diseño técnico detallado del sistema
- [INTEGRACION_LARAVEL.md](INTEGRACION_LARAVEL.md) - Guía completa de integración con Laravel

## 🔐 Notas

- **Free Tier Gemini:** 1,500 requests/día, 15 RPM, 1M tokens contexto
- **Volumen soportado:** 360 docs/día (24% del límite)
- **Privacidad:** Los TDRs son documentos públicos del SEACE
- En producción: configurar CORS restrictivo y rate limiting

---

**Versión:** 1.1.0 | **Fecha:** 3 de febrero de 2026
