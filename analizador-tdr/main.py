"""
FastAPI Main Application - Microservicio de Análisis de TDRs SEACE (2026)
Pipeline RAG de Extracción (NO chatbot)
Optimizado para Gemini 2.5/3 Flash con procesamiento asíncrono.
"""
from fastapi import FastAPI, File, UploadFile, HTTPException, Depends
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
    ErrorResponse
)
from app.services.analyzer_service import TDRAnalyzerService

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

# Middleware CORS (para integraciones con frontend)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # En producción, especifica dominios permitidos
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
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
    llm_provider: str = None
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
