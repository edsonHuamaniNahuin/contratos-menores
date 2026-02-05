"""
Orquestador principal del pipeline RAG de análisis de TDRs.
Coordina PDF Processor → RAG Extractor → LLM Client.
"""
from typing import Dict, Optional, Literal
from app.services.pdf_processor import PDFProcessorService
from app.services.rag_extractor import RAGExtractionService
from app.services.llm import LLMFactory
from app.models.schemas import TDRAnalysisResponse
import logging

logger = logging.getLogger(__name__)


class TDRAnalyzerService:
    """
    Servicio principal que orquesta el pipeline completo de análisis de TDR.
    """

    def __init__(self):
        self.pdf_processor = PDFProcessorService()
        self.rag_extractor = RAGExtractionService()
        self.logger = logger

    async def analyze_tdr_document(
        self,
        pdf_bytes: bytes,
        llm_provider: Optional[Literal["gemini", "openai", "anthropic"]] = None
    ) -> TDRAnalysisResponse:
        """
        Pipeline completo de análisis de TDR.

        Flujo:
        1. Extrae texto del PDF
        2. Recupera fragmentos relevantes (RAG)
        3. Construye contexto para el LLM
        4. Envía al LLM para análisis estructurado
        5. Valida la respuesta con Pydantic

        Args:
            pdf_bytes: Contenido binario del PDF
            llm_provider: Proveedor LLM a usar (opcional)

        Returns:
            TDRAnalysisResponse: Análisis estructurado validado

        Raises:
            ValueError: Si hay errores en el procesamiento o validación
        """
        self.logger.info("=== INICIANDO PIPELINE DE ANÁLISIS DE TDR (PDF DIRECTO) ===")

        # ESTRATEGIA NUEVA: Enviar PDF directamente a Gemini (soporta PDFs nativos)
        self.logger.info("📄 Usando estrategia de PDF directo (sin extracción de texto)")

        llm_client = LLMFactory.create_client(llm_provider)

        # Verificar si el cliente soporta análisis de PDF directo
        if hasattr(llm_client, 'analyze_tdr_from_pdf'):
            self.logger.info("✅ Cliente LLM soporta PDF directo")
            analysis_dict = await llm_client.analyze_tdr_from_pdf(pdf_bytes, "tdr.pdf")
        else:
            # FALLBACK: Extraer texto y usar método tradicional
            self.logger.warning("⚠️  Cliente LLM no soporta PDF directo, usando extracción de texto...")

            # Paso 1: Extraer texto del PDF (método SÍNCRONO - PyMuPDF no es async)
            self.logger.info("Paso 1/4: Extrayendo texto del PDF...")
            full_text = self.pdf_processor.extract_text_from_pdf(pdf_bytes)

            if len(full_text) < 100:
                raise ValueError("El PDF contiene muy poco texto para analizar")

            self.logger.info(f"✓ Texto extraído: {len(full_text)} caracteres")

            # Paso 2 y 3: Construir contexto para el LLM
            # Si el documento es pequeño (<5000 caracteres), enviar todo directamente sin RAG
            if len(full_text) < 5000:
                self.logger.info("⚡ Documento pequeño detectado, enviando texto completo al LLM (sin RAG)...")
                context = f"""DOCUMENTO COMPLETO DEL TDR:

{full_text}

===== FIN DEL DOCUMENTO ====="""
                self.logger.info(f"✓ Contexto completo preparado: {len(context)} caracteres")
            else:
                # Paso 2: Recuperar fragmentos relevantes (RAG) - método SÍNCRONO
                self.logger.info("Paso 2/4: Recuperando fragmentos relevantes (RAG)...")
                fragments = self.rag_extractor.extract_relevant_fragments(full_text)

                # Verificar que se recuperaron fragmentos
                total_fragments = sum(len(chunks) for chunks in fragments.values())
                self.logger.info(f"✓ Fragmentos recuperados: {total_fragments} chunks")

                # Paso 3: Construir contexto para el LLM - método SÍNCRONO
                self.logger.info("Paso 3/4: Construyendo contexto para el LLM...")
                context = self.rag_extractor.build_context_for_llm(fragments)
                self.logger.info(f"✓ Contexto construido: {len(context)} caracteres")

            # Paso 4: Analizar con el LLM usando texto
            self.logger.info(f"Paso 4/4: Analizando con LLM (provider: {llm_provider or 'default'})...")
            analysis_dict = await llm_client.analyze_tdr(context)

        # Paso 5: Validar con Pydantic
        try:
            validated_response = TDRAnalysisResponse(**analysis_dict)
            self.logger.info("✓ Análisis completado y validado exitosamente")
            self.logger.info(f"  - Score de compatibilidad: {validated_response.score_compatibilidad}/10")
            self.logger.info(f"  - Requisitos técnicos: {len(validated_response.requisitos_tecnicos)}")
            self.logger.info(f"  - Reglas de negocio: {len(validated_response.reglas_de_negocio)}")

            return validated_response

        except Exception as e:
            self.logger.error(f"Error al validar respuesta del LLM: {str(e)}")
            self.logger.error(f"Respuesta recibida: {analysis_dict}")
            raise ValueError(f"La respuesta del LLM no cumple con el esquema esperado: {str(e)}")
