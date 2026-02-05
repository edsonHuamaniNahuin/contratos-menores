"""
Servicio de procesamiento de PDF.
Extrae texto completo usando PyMuPDF (fitz).
"""
import fitz  # PyMuPDF
import io
from typing import Optional
import logging

logger = logging.getLogger(__name__)


class PDFProcessorService:
    """Servicio para extraer texto de archivos PDF"""

    def __init__(self):
        self.logger = logger

    def extract_text_from_pdf(self, pdf_bytes: bytes) -> str:
        """
        Extrae todo el texto de un PDF usando PyMuPDF.
        NOTA: PyMuPDF es síncrono, por eso este método NO es async.

        Args:
            pdf_bytes: Contenido binario del archivo PDF

        Returns:
            str: Texto completo extraído del PDF

        Raises:
            ValueError: Si el PDF está corrupto o no se puede procesar
        """
        try:
            # Crear stream en memoria desde bytes
            pdf_stream = io.BytesIO(pdf_bytes)

            # Abrir PDF desde el stream
            doc = fitz.open(stream=pdf_stream, filetype="pdf")

            # Extraer texto de todas las páginas
            full_text = ""
            num_pages = len(doc)

            for page_num in range(num_pages):
                page = doc.load_page(page_num)

                # Intentar múltiples métodos de extracción
                # Método 1: text (más rápido)
                text = page.get_text("text")

                # Método 2: Si text no funciona bien, intentar blocks (preserva estructura)
                if len(text.strip()) < 50:
                    blocks = page.get_text("blocks")
                    text = "\n".join([block[4] for block in blocks if len(block) > 4])

                # Método 3: Si sigue vacío, intentar dict (más detallado)
                if len(text.strip()) < 50:
                    text_dict = page.get_text("dict")
                    text = "\n".join([block.get("text", "") for block in text_dict.get("blocks", [])])

                full_text += f"\n--- Página {page_num + 1} ---\n{text}\n"

            # Cerrar documento
            doc.close()
            pdf_stream.close()

            if not full_text.strip():
                raise ValueError("El PDF no contiene texto extraíble (puede ser un PDF escaneado)")

            self.logger.info(f"PDF procesado: {num_pages} páginas, {len(full_text)} caracteres")

            return full_text.strip()

        except fitz.FileDataError as e:
            self.logger.error(f"Error al abrir PDF: {str(e)}")
            raise ValueError("El archivo no es un PDF válido o está corrupto")

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
