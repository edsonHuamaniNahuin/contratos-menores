"""
Servicio de procesamiento de PDF.
Delega la extracción al pipeline SmartPDFReader (texto + tablas + OCR de imágenes).
Mantiene la interfaz pública original para no romper el pipeline existente.
"""
import fitz  # PyMuPDF
import io
from typing import Optional
import logging

from config import settings
from app.services.pdf_reader import SmartPDFReaderPipeline

logger = logging.getLogger(__name__)


class PDFProcessorService:
    """
    Servicio para extraer texto de archivos PDF.

    Internamente usa SmartPDFReaderPipeline (SOLID) para:
    - Texto digital: extracción directa con PyMuPDF
    - Tablas: detección y formateo estructurado
    - Imágenes: OCR con Tesseract (si está disponible)

    La interfaz pública (extract_text_from_pdf, extract_metadata) NO cambia.
    """

    def __init__(self):
        self.logger = logger
        self._pipeline = SmartPDFReaderPipeline(
            ocr_enabled=getattr(settings, "ocr_enabled", True),
            table_extraction_enabled=getattr(settings, "table_extraction_enabled", True),
            min_image_size=getattr(settings, "min_image_size_bytes", 5_000),
        )

    def extract_text_from_pdf(self, pdf_bytes: bytes) -> str:
        """
        Extrae todo el contenido de un PDF usando el pipeline inteligente.
        NOTA: PyMuPDF es síncrono, por eso este método NO es async.

        Args:
            pdf_bytes: Contenido binario del archivo PDF

        Returns:
            str: Texto completo extraído del PDF (incluye tablas formateadas y OCR)

        Raises:
            ValueError: Si el PDF está corrupto o no se puede procesar
        """
        try:
            self.logger.info("Extrayendo contenido con SmartPDFReaderPipeline...")
            return self._pipeline.extract(pdf_bytes)

        except ValueError:
            # Re-raise errores de validación del pipeline
            raise

        except Exception as e:
            self.logger.error(f"Error inesperado al procesar PDF: {str(e)}")
            raise ValueError(f"Error al procesar PDF: {str(e)}")

    def extract_metadata(self, pdf_bytes: bytes) -> dict:
        """
        Extrae metadatos del PDF (opcional para futuros análisis).
        NOTA: PyMuPDF es síncrono, por eso este método NO es async.

        Args:
            pdf_bytes: Contenido binario del archivo PDF

        Returns:
            dict: Metadatos del PDF
        """
        try:
            # Crear stream en memoria
            pdf_stream = io.BytesIO(pdf_bytes)
            doc = fitz.open(stream=pdf_stream, filetype="pdf")

            metadata = {
                "num_pages": len(doc),
                "title": doc.metadata.get("title", ""),
                "author": doc.metadata.get("author", ""),
                "subject": doc.metadata.get("subject", ""),
                "creator": doc.metadata.get("creator", ""),
                "producer": doc.metadata.get("producer", ""),
            }

            doc.close()
            pdf_stream.close()
            return metadata

        except Exception as e:
            self.logger.warning(f"No se pudieron extraer metadatos: {str(e)}")
            return {}
