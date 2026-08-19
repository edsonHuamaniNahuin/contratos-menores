"""
FastAPI Main Application - Microservicio de Análisis de TDRs SEACE (2026)
Pipeline RAG de Extracción (NO chatbot)
Optimizado para Gemini 2.5/3 Flash con procesamiento asíncrono.
"""
from fastapi import FastAPI, File, Form, UploadFile, HTTPException, Depends
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
from datetime import datetime
import io
import logging
from contextlib import asynccontextmanager

try:
    import fitz  # PyMuPDF
    _HAS_FITZ = True
except ImportError:
    _HAS_FITZ = False

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


def _comprimir_pdf(pdf_bytes: bytes, limite_mb: float) -> bytes | None:
    """
    Comprime un PDF que excede el límite permitido.

    Estrategia escalonada:
      1. Limpieza + recompresión de streams (garbage=4, deflate).
      2. Rasterización de páginas a JPEG (dpi escalonado 150→80) y reensamblado
         — reduce 5-10x el peso de PDFs escaneados (caso típico de TDR pesados).

    Devuelve los bytes comprimidos o None si no se pudo reducir al límite.
    """
    limite_bytes = limite_mb * 1024 * 1024
    if len(pdf_bytes) <= limite_bytes or not _HAS_FITZ:
        return pdf_bytes

    # ── Intento 1: limpieza y recompresión de streams ──
    try:
        doc = fitz.open(stream=pdf_bytes, filetype="pdf")
        out = io.BytesIO()
        doc.save(out, garbage=4, deflate=True)
        doc.close()
        comprimido = out.getvalue()
        if len(comprimido) < len(pdf_bytes) and len(comprimido) <= limite_bytes:
            logger.info(f"📦 PDF comprimido (streams): {len(pdf_bytes)/1048576:.1f}MB → {len(comprimido)/1048576:.1f}MB")
            return comprimido
    except Exception as e:
        logger.warning(f"Compresión por streams falló: {e}")

    # ── Intento 2: rasterizar páginas a JPEG con dpi escalonado ──
    for dpi in (150, 120, 100, 80):
        try:
            doc = fitz.open(stream=pdf_bytes, filetype="pdf")
            nuevo = fitz.open()
            for page in doc:
                pix = page.get_pixmap(dpi=dpi, colorspace=fitz.csRGB)
                img_bytes = pix.tobytes("jpeg", jpg_quality=70)
                rect = fitz.Rect(0, 0, pix.width / 72, pix.height / 72)
                npage = nuevo.new_page(width=rect.width, height=rect.height)
                npage.insert_image(rect, stream=img_bytes)
            out = io.BytesIO()
            nuevo.save(out, garbage=4, deflate=True)
            nuevo.close()
            doc.close()
            comprimido = out.getvalue()
            if len(comprimido) <= limite_bytes:
                logger.info(f"📦 PDF rasterizado a {dpi}dpi: {len(pdf_bytes)/1048576:.1f}MB → {len(comprimido)/1048576:.1f}MB")
                return comprimido
        except Exception as e:
            logger.warning(f"Rasterización a {dpi}dpi falló: {e}")

    return None


async def _leer_documento(file: UploadFile, limite_mb: float) -> bytes:
    """
    Lee el archivo subido y lo comprime si excede el límite del servicio.

    Lanza 413 si el archivo no puede reducirse al tamaño permitido.
    """
    pdf_bytes = await file.read()
    file_size_mb = len(pdf_bytes) / (1024 * 1024)

    if file_size_mb > limite_mb:
        comprimido = _comprimir_pdf(pdf_bytes, limite_mb)
        if comprimido is None:
            raise HTTPException(
                status_code=413,
                detail=f"El archivo excede el tamaño máximo permitido ({settings.max_file_size_mb}MB) y no pudo comprimirse"
            )
        pdf_bytes = comprimido

    return pdf_bytes


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
    file: UploadFile = File(..., description="Archivo del TDR (PDF, DOCX, DOC)"),
    llm_provider: str = Form(None),
    tipo_contrato: str = Form("menores"),
    auth: AuthContext = Depends(require_auth),
):
    """
    **Endpoint principal de análisis de TDR.**

    Recibe un archivo (PDF, DOCX, DOC) y devuelve análisis estructurado en JSON.

    **Proceso:**
    1. Extrae texto del documento
    2. Recupera fragmentos relevantes (RAG)
    3. Analiza con LLM (Gemini 2.5 Flash por defecto)
    4. Devuelve JSON validado con Pydantic

    **Parámetros:**
    - `file`: Archivo del TDR (max 50MB)
    - `llm_provider`: (Opcional) "gemini", "openai", "anthropic"
    - `tipo_contrato`: "menores" (≤8 UIT) o "mayores" (>8 UIT)

    **Respuesta:**
    - Objeto JSON con análisis estructurado envuelto en {success: true, data: {...}}
    """
    try:
        # Validar extensión
        ext = file.filename.rsplit('.', 1)[-1].lower() if '.' in file.filename else ''
        if ext not in ('pdf', 'docx', 'doc'):
            raise HTTPException(
                status_code=400,
                detail=f"Formato no soportado: .{ext}. Use PDF, DOCX o DOC"
            )

        # Leer contenido del archivo (comprime si excede el límite; 413 si no es posible)
        pdf_bytes = await _leer_documento(file, settings.max_file_size_mb)

        logger.info(f"📄 Recibido: {file.filename} — tipo: {tipo_contrato}")

        # Validar proveedor LLM si se especificó
        if llm_provider and llm_provider not in ["gemini", "openai", "anthropic"]:
            raise HTTPException(
                status_code=400,
                detail=f"Proveedor LLM no válido: {llm_provider}"
            )

        # Ejecutar análisis
        result = await analyzer_service.analyze_tdr_document(
            pdf_bytes=pdf_bytes,
            llm_provider=llm_provider,
            tipo_contrato=tipo_contrato or "menores",
            filename=file.filename,
        )

        logger.info(f"✅ Análisis completado exitosamente para: {file.filename}")

        # Envolver respuesta para Laravel
        return {
            "success": True,
            "data": result.model_dump() if hasattr(result, 'model_dump') else result,
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
    file: UploadFile = File(..., description="Archivo del TDR (PDF, DOCX, DOC)"),
    llm_provider: str = Form(None),
    tipo_contrato: str = Form("menores"),
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
        ext = file.filename.rsplit('.', 1)[-1].lower() if '.' in file.filename else ''
        if ext not in ('pdf', 'docx', 'doc'):
            raise HTTPException(status_code=400, detail=f"Formato no soportado: .{ext}")

        pdf_bytes = await _leer_documento(file, settings.max_file_size_mb)

        logger.info(f"🔍 Direccionamiento: {file.filename} — tipo: {tipo_contrato}")

        if llm_provider and llm_provider not in ["gemini", "openai", "anthropic"]:
            raise HTTPException(status_code=400, detail=f"Proveedor LLM no válido: {llm_provider}")

        result = await analyzer_service.analyze_direccionamiento_document(
            pdf_bytes=pdf_bytes,
            llm_provider=llm_provider,
            tipo_contrato=tipo_contrato or "menores",
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
    file: UploadFile = File(None, description="Archivo del TDR (PDF, DOCX, DOC)"),
    company_name: str = Form(""),
    company_copy: str = Form(""),
    tipo_contrato: str = Form("menores"),
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
            raise HTTPException(status_code=400, detail="Se requiere el archivo del TDR")

        ext = file.filename.rsplit('.', 1)[-1].lower() if '.' in file.filename else ''
        if ext not in ('pdf', 'docx', 'doc'):
            raise HTTPException(status_code=400, detail=f"Formato no soportado: .{ext}")

        pdf_bytes = await _leer_documento(file, settings.max_file_size_mb)

        logger.info(f"📋 Proforma: {file.filename} — empresa: {company_name or '(sin nombre)'} — tipo: {tipo_contrato}")

        result = await analyzer_service.generate_proforma_document(
            pdf_bytes=pdf_bytes,
            company_name=company_name.strip(),
            company_copy=company_copy.strip(),
            tipo_contrato=tipo_contrato or "menores",
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
