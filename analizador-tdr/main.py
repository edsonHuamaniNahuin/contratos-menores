"""
FastAPI Main Application - Microservicio de Análisis de TDRs SEACE (2026)
Pipeline RAG de Extracción (NO chatbot)
Optimizado para Gemini 2.5/3 Flash con procesamiento asíncrono.
"""
from fastapi import FastAPI, File, Form, UploadFile, HTTPException, Depends
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
from datetime import datetime
import logging
from contextlib import asynccontextmanager

from config import settings
from app.models.schemas import (
    TDRAnalysisResponse,
    TDRAnalysisRequest,
    HealthCheckResponse,
    ErrorResponse,
    CompatibilityScoreRequest,
    DireccionamientoAnalysisResponse,
    ProformaRequest,
    ProformaResponse,
)
from app.services.analyzer_service import TDRAnalyzerService
from app.middleware import require_auth, AuthContext

# Importar router de batch processing
from app.routes.batch import router as batch_router

# Configuración de logging
logging.basicConfig(
    level=logging.INFO if not settings.debug else logging.DEBUG,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)

logger = logging.getLogger(__name__)


# Lifespan context manager para startup/shutdown
@asynccontextmanager
async def lifespan(app: FastAPI):
    """Maneja el ciclo de vida de la aplicación"""
    logger.info("🚀 Iniciando microservicio Analizador TDR SEACE (2026)")
    logger.info(f"Entorno: {settings.app_env}")
    logger.info(f"LLM Provider: {settings.default_llm_provider} ({settings.gemini_model})")
    logger.info(f"Batch processing: {'Habilitado' if settings.enable_batch_processing else 'Deshabilitado'}")
    logger.info(f"Concurrencia máxima: {settings.max_concurrent_requests}")
    yield
    logger.info("🛑 Deteniendo microservicio")


# Crear aplicación FastAPI
app = FastAPI(
    title=settings.app_name,
    description="Microservicio de análisis automatizado de TDRs del SEACE usando RAG + LLM (Gemini 2.5/3 Flash)",
    version="1.1.0",
    lifespan=lifespan,
    docs_url="/docs",
    redoc_url="/redoc"
)

# Middleware CORS — orígenes restringidos por configuración
_allowed_origins = [o.strip() for o in settings.allowed_origins.split(",") if o.strip()]
app.add_middleware(
    CORSMiddleware,
    allow_origins=_allowed_origins,
    allow_credentials=False,
    allow_methods=["GET", "POST"],
    allow_headers=["Authorization", "Content-Type"],
)

# Incluir router de batch processing
app.include_router(batch_router)

# Instancia del servicio de análisis (singleton)
analyzer_service = TDRAnalyzerService()


# ============================================================================
# ENDPOINTS
# ============================================================================

@app.get("/", tags=["Root"])
async def root():
    """Endpoint raíz"""
    return {
        "message": "Microservicio Analizador TDR SEACE (2026)",
        "version": "1.1.0",
        "llm": f"{settings.default_llm_provider} ({settings.gemini_model})",
        "batch_processing": settings.enable_batch_processing,
        "docs": "/docs"
    }


@app.get("/health", response_model=HealthCheckResponse, tags=["Health"])
async def health_check():
    """
    Health check del microservicio.
    Verifica que el servicio esté operativo.
    """
    return HealthCheckResponse(
        status="healthy",
        app_name=settings.app_name,
        version="1.0.0",
        timestamp=datetime.now(),
        llm_provider=settings.default_llm_provider
    )


@app.post(
    "/analyze-tdr",
    tags=["Analysis"],
    summary="Analiza un TDR del SEACE y devuelve análisis estructurado"
)
async def analyze_tdr(
    file: UploadFile = File(..., description="Archivo PDF del TDR"),
    llm_provider: str = None,
    auth: AuthContext = Depends(require_auth),
):
    """
    **Endpoint principal de análisis de TDR.**

    Recibe un archivo PDF (TDR del SEACE) y devuelve un análisis técnico estructurado en JSON.

    **Proceso:**
    1. Extrae texto del PDF
    2. Recupera fragmentos relevantes (requisitos, penalidades, plazos, presupuesto)
    3. Analiza con LLM (Gemini 2.5 Flash por defecto)
    4. Devuelve JSON validado con Pydantic

    **Parámetros:**
    - `file`: Archivo PDF del TDR (max 10MB)
    - `llm_provider`: (Opcional) Proveedor LLM: "gemini", "openai", "anthropic"

    **Respuesta:**
    - Objeto JSON con análisis estructurado envuelto en {success: true, data: {...}}
    """
    try:
        # Validar que sea un PDF
        if not file.filename.endswith('.pdf'):
            raise HTTPException(
                status_code=400,
                detail="El archivo debe ser un PDF"
            )

        # Leer contenido del archivo
        pdf_bytes = await file.read()

        # Validar tamaño del archivo
        file_size_mb = len(pdf_bytes) / (1024 * 1024)
        if file_size_mb > settings.max_file_size_mb:
            raise HTTPException(
                status_code=413,
                detail=f"El archivo excede el tamaño máximo permitido ({settings.max_file_size_mb}MB)"
            )

        logger.info(f"📄 Recibido: {file.filename} ({file_size_mb:.2f} MB)")

        # Validar proveedor LLM si se especificó
        if llm_provider and llm_provider not in ["gemini", "openai", "anthropic"]:
            raise HTTPException(
                status_code=400,
                detail=f"Proveedor LLM no válido: {llm_provider}"
            )

        # Ejecutar análisis
        result = await analyzer_service.analyze_tdr_document(
            pdf_bytes=pdf_bytes,
            llm_provider=llm_provider
        )

        logger.info(f"✅ Análisis completado exitosamente para: {file.filename}")

        # Envolver respuesta para Laravel
        return {
            "success": True,
            "data": result.model_dump(),  # Convertir Pydantic a dict
            "token_usage": analyzer_service.last_token_usage,
            "timestamp": datetime.now().isoformat(),
            "filename": file.filename
        }

    except ValueError as e:
        logger.error(f"Error de validación: {str(e)}")
        raise HTTPException(status_code=400, detail=str(e))

    except Exception as e:
        logger.error(f"Error inesperado: {str(e)}", exc_info=True)
        raise HTTPException(
            status_code=500,
            detail=f"Error interno al procesar el TDR: {str(e)}"
        )


@app.post(
    "/analyze-direccionamiento",
    tags=["Analysis"],
    summary="Detecta indicios de direccionamiento y corrupción en un TDR"
)
async def analyze_direccionamiento(
    file: UploadFile = File(..., description="Archivo PDF del TDR"),
    llm_provider: str = None,
    auth: AuthContext = Depends(require_auth),
):
    """
    **Análisis forense de direccionamiento en TDR.**

    Recibe un PDF y evalúa indicadores de corrupción y direccionamiento
    según la Ley N.º 32069 y normativa OSCE.

    Retorna score de riesgo (0-100), veredicto flash, hallazgos críticos
    y argumento legal para presentar observación formal.
    """
    try:
        if not file.filename.endswith('.pdf'):
            raise HTTPException(status_code=400, detail="El archivo debe ser un PDF")

        pdf_bytes = await file.read()
        file_size_mb = len(pdf_bytes) / (1024 * 1024)

        if file_size_mb > settings.max_file_size_mb:
            raise HTTPException(
                status_code=413,
                detail=f"Archivo excede tamaño máximo ({settings.max_file_size_mb}MB)"
            )

        logger.info(f"🔍 Direccionamiento: {file.filename} ({file_size_mb:.2f} MB)")

        if llm_provider and llm_provider not in ["gemini", "openai", "anthropic"]:
            raise HTTPException(status_code=400, detail=f"Proveedor LLM no válido: {llm_provider}")

        result = await analyzer_service.analyze_direccionamiento_document(
            pdf_bytes=pdf_bytes,
            llm_provider=llm_provider
        )

        logger.info(f"✅ Direccionamiento completado: {file.filename} — Score: {result.score_riesgo_corrupcion}")

        return {
            "success": True,
            "data": result.model_dump(),
            "token_usage": analyzer_service.last_token_usage,
            "timestamp": datetime.now().isoformat(),
            "filename": file.filename
        }

    except ValueError as e:
        logger.error(f"Error de validación en direccionamiento: {str(e)}")
        raise HTTPException(status_code=400, detail=str(e))
    except Exception as e:
        logger.error(f"Error inesperado en direccionamiento: {str(e)}", exc_info=True)
        raise HTTPException(status_code=500, detail=f"Error interno: {str(e)}")


@app.post(
    "/compatibility/score",
    tags=["Compatibility"],
    summary="Evalúa compatibilidad del TDR con el perfil de un suscriptor"
)
async def compatibility_score(
    request: CompatibilityScoreRequest,
    auth: AuthContext = Depends(require_auth),
):
    try:
        result = await analyzer_service.evaluate_compatibility(request)
        return {
            "success": True,
            "data": result.model_dump(),
            "token_usage": analyzer_service.last_token_usage,
            "timestamp": datetime.now().isoformat(),
        }
    except ValueError as e:
        logger.error(f"Error de compatibilidad: {str(e)}")
        raise HTTPException(status_code=400, detail=str(e))
    except Exception as e:
        logger.error(f"Error interno en compatibilidad: {str(e)}", exc_info=True)
        raise HTTPException(status_code=500, detail=str(e))


@app.post(
    "/generate-proforma",
    tags=["Proforma"],
    summary="Genera proforma técnica de cotización a partir de un TDR"
)
async def generate_proforma(
    file: UploadFile = File(None, description="Archivo PDF del TDR (opcional si se enviaron datos JSON)"),
    company_name: str = Form(""),
    company_copy: str = Form(""),
    auth: AuthContext = Depends(require_auth),
):
    """
    **Generación de Proforma Técnica de Cotización.**

    Recibe un PDF del TDR y el perfil de la empresa, y genera una proforma con:
    - Tabla de ítems (descripción, unidad, cantidad, precio unitario, subtotal)
    - Total estimado en soles
    - Análisis de viabilidad operativa
    - Condiciones y supuestos del presupuesto

    **Parámetros (form data):**
    - `file`: PDF del TDR
    - `company_name`: Nombre de la empresa proveedora
    - `company_copy`: Descripción del rubro/experiencia de la empresa
    """
    try:
        if not company_copy or len(company_copy.strip()) < 20:
            raise HTTPException(
                status_code=400,
                detail="El campo company_copy es obligatorio (mínimo 20 caracteres)"
            )

        if file is None:
            raise HTTPException(status_code=400, detail="Se requiere el archivo PDF del TDR")

        if not file.filename.endswith('.pdf'):
            raise HTTPException(status_code=400, detail="El archivo debe ser un PDF")

        pdf_bytes = await file.read()
        file_size_mb = len(pdf_bytes) / (1024 * 1024)

        if file_size_mb > settings.max_file_size_mb:
            raise HTTPException(
                status_code=413,
                detail=f"El archivo excede el tamaño máximo permitido ({settings.max_file_size_mb}MB)"
            )

        logger.info(f"📋 Proforma: {file.filename} ({file_size_mb:.2f} MB) — empresa: {company_name or '(sin nombre)'}")

        result = await analyzer_service.generate_proforma_document(
            pdf_bytes=pdf_bytes,
            company_name=company_name.strip(),
            company_copy=company_copy.strip(),
        )

        logger.info(f"✅ Proforma generada: {len(result.items)} ítems — {result.total_estimado}")

        return {
            "success": True,
            "data": result.model_dump(),
            "token_usage": analyzer_service.last_token_usage,
            "timestamp": datetime.now().isoformat(),
            "filename": file.filename,
        }

    except ValueError as e:
        logger.error(f"Error de validación en proforma: {str(e)}")
        raise HTTPException(status_code=400, detail=str(e))
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Error inesperado en proforma: {str(e)}", exc_info=True)
        raise HTTPException(status_code=500, detail=f"Error interno al generar proforma: {str(e)}")


@app.exception_handler(Exception)
async def global_exception_handler(request, exc):
    """Handler global de excepciones"""
    logger.error(f"Excepción no manejada: {str(exc)}", exc_info=True)
    return JSONResponse(
        status_code=500,
        content={
            "error": "Internal Server Error",
            "detail": str(exc),
            "timestamp": datetime.now().isoformat()
        }
    )


# ============================================================================
# ENTRY POINT
# ============================================================================

if __name__ == "__main__":
    import uvicorn

    uvicorn.run(
        "main:app",
        host=settings.host,
        port=settings.port,
        reload=settings.debug,
        log_level="info"
    )
