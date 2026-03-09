"""
Parser principal de documentos PDF.
Abre el PDF y delega la extracción a los extractores registrados (OCP).
"""
import fitz  # PyMuPDF
import io
import logging
from typing import List

from .contracts import (
    BlockExtractorContract,
    DocumentContent,
    PageContent,
)

logger = logging.getLogger(__name__)


class DocumentParser:
    """
    Abre un PDF y delega la extracción de bloques a los extractores registrados (SRP + OCP).

    Sigue el principio Open/Closed: se pueden agregar nuevos extractores
    sin modificar esta clase.

    Args:
        extractors: Lista de extractores de bloques (inyectados por DIP).
    """

    def __init__(self, extractors: List[BlockExtractorContract]):
        if not extractors:
            raise ValueError("Se requiere al menos un extractor de bloques")
        self._extractors = extractors

    def parse(self, pdf_bytes: bytes) -> DocumentContent:
        """
        Parsea un PDF completo y retorna el contenido estructurado.

        Args:
            pdf_bytes: Contenido binario del archivo PDF.

        Returns:
            DocumentContent con bloques extraídos de todas las páginas.

        Raises:
            ValueError: Si el PDF está corrupto o no se puede abrir.
        """
        try:
            pdf_stream = io.BytesIO(pdf_bytes)
            doc = fitz.open(stream=pdf_stream, filetype="pdf")
        except fitz.FileDataError as e:
            raise ValueError(f"El archivo no es un PDF válido o está corrupto: {e}")
        except Exception as e:
            raise ValueError(f"Error al abrir el PDF: {e}")

        num_pages = len(doc)
        document = DocumentContent(total_pages=num_pages)

        logger.info(f"Parseando PDF: {num_pages} páginas, {len(self._extractors)} extractores")

        for page_num in range(num_pages):
            page = doc.load_page(page_num)
            page_content = PageContent(page_number=page_num + 1)

            for extractor in self._extractors:
                try:
                    blocks = extractor.extract(page, page_num + 1)
                    page_content.blocks.extend(blocks)
                except Exception as e:
                    extractor_name = type(extractor).__name__
                    logger.warning(
                        f"Error en {extractor_name} para página {page_num + 1}: {e}"
                    )

            document.pages.append(page_content)

        # Metadatos del documento
        try:
            document.metadata = {
                "title": doc.metadata.get("title", ""),
                "author": doc.metadata.get("author", ""),
                "creator": doc.metadata.get("creator", ""),
                "producer": doc.metadata.get("producer", ""),
            }
        except Exception:
            pass

        doc.close()
        pdf_stream.close()

        total_blocks = sum(len(p.blocks) for p in document.pages)
        logger.info(f"PDF parseado: {num_pages} páginas, {total_blocks} bloques totales")

        return document
