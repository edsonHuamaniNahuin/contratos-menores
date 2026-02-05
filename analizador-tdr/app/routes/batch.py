"""
Endpoint optimizado para procesamiento por lotes (batch).
Diseñado para el scraper que envía 3-10 documentos cada 40 minutos.
"""
from fastapi import APIRouter, File, UploadFile, HTTPException
from typing import List
import asyncio
import logging
from datetime import datetime

from app.models.schemas import TDRAnalysisResponse, ErrorResponse
from app.services.analyzer_service import TDRAnalyzerService
from config import settings

router = APIRouter(prefix="/batch", tags=["Batch Processing"])
logger = logging.getLogger(__name__)

analyzer_service = TDRAnalyzerService()


@router.post(
    "/analyze-tdrs",
    response_model=List[dict],
    summary="Analiza múltiples TDRs en paralelo (optimizado para scraper)"
)
async def analyze_batch_tdrs(
    files: List[UploadFile] = File(..., description="Lista de archivos PDF")
):
    """
    **Endpoint optimizado para procesamiento por lotes.**
    
    Ideal para el scraper que envía 3-10 documentos cada 40 minutos.
    
    **Optimizaciones:**
    - Procesamiento asíncrono en paralelo
    - Límite de 3 requests concurrentes (configurable)
    - No bloquea si un PDF falla
    
    **Volumen soportado:**
    - 36 rondas/día × 10 docs = 360 docs/día
    - 24% del límite Free Tier de Gemini (1,500 req/día)
    
    **Respuesta:**
    - Array de resultados (algunos pueden ser errores)
    """
    
    if not settings.enable_batch_processing:
        raise HTTPException(
            status_code=403,
            detail="El procesamiento por lotes está deshabilitado"
        )
    
    # Validar número de archivos
    if len(files) > 20:
        raise HTTPException(
            status_code=400,
            detail=f"Máximo 20 archivos por lote. Recibidos: {len(files)}"
        )
    
    logger.info(f"📦 Procesando lote de {len(files)} TDRs")
    
    # Validar que todos sean PDFs
    for file in files:
        if not file.filename.endswith('.pdf'):
            raise HTTPException(
                status_code=400,
                detail=f"Archivo inválido: {file.filename} (solo PDFs)"
            )
    
    # Función para procesar un PDF
    async def process_single_pdf(file: UploadFile, index: int):
        try:
            logger.info(f"  [{index+1}/{len(files)}] Procesando: {file.filename}")
            
            # Leer PDF
            pdf_bytes = await file.read()
            
            # Validar tamaño
            file_size_mb = len(pdf_bytes) / (1024 * 1024)
            if file_size_mb > settings.max_file_size_mb:
                return {
                    "filename": file.filename,
                    "status": "error",
                    "error": f"Archivo muy grande ({file_size_mb:.2f}MB)"
                }
            
            # Analizar
            result = await analyzer_service.analyze_tdr_document(pdf_bytes)
            
            logger.info(f"  ✅ [{index+1}/{len(files)}] Completado: {file.filename} (Score: {result.score_compatibilidad}/10)")
            
            return {
                "filename": file.filename,
                "status": "success",
                "analysis": result.model_dump()
            }
        
        except Exception as e:
            logger.error(f"  ❌ [{index+1}/{len(files)}] Error en {file.filename}: {str(e)}")
            return {
                "filename": file.filename,
                "status": "error",
                "error": str(e)
            }
    
    # Procesar en paralelo con límite de concurrencia
    semaphore = asyncio.Semaphore(settings.max_concurrent_requests)
    
    async def process_with_limit(file: UploadFile, index: int):
        async with semaphore:
            return await process_single_pdf(file, index)
    
    # Ejecutar todos en paralelo (con límite)
    start_time = datetime.now()
    
    tasks = [
        process_with_limit(file, idx) 
        for idx, file in enumerate(files)
    ]
    
    results = await asyncio.gather(*tasks)
    
    elapsed = (datetime.now() - start_time).total_seconds()
    
    # Estadísticas
    success_count = sum(1 for r in results if r["status"] == "success")
    error_count = len(results) - success_count
    
    logger.info(f"📊 Lote completado: {success_count} exitosos, {error_count} errores en {elapsed:.2f}s")
    
    return results


@router.get("/stats", summary="Estadísticas de procesamiento por lotes")
async def get_batch_stats():
    """
    Devuelve estadísticas y límites del procesamiento por lotes.
    """
    return {
        "enabled": settings.enable_batch_processing,
        "max_concurrent_requests": settings.max_concurrent_requests,
        "max_file_size_mb": settings.max_file_size_mb,
        "llm_provider": settings.default_llm_provider,
        "limits": {
            "gemini_free_tier": {
                "requests_per_day": 1500,
                "requests_per_minute": 15,
                "context_tokens": 1_000_000
            },
            "estimated_daily_usage": {
                "rounds_per_day": 36,
                "docs_per_round_max": 10,
                "total_docs_per_day": 360,
                "percentage_of_free_tier": "24%"
            }
        }
    }
