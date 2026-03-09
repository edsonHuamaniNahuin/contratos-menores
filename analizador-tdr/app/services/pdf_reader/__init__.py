"""
Módulo PDF Reader — Extracción inteligente de contenido de PDFs.

Arquitectura SOLID:
- SRP: Cada extractor maneja un solo tipo de bloque.
- OCP: Nuevos extractores se agregan sin modificar los existentes.
- LSP: Todos los extractores son intercambiables vía BlockExtractorContract.
- ISP: Interfaces segregadas (BlockExtractorContract, OCRProcessorContract).
- DIP: El DocumentParser depende de abstracciones, no de implementaciones.

Uso:
    from app.services.pdf_reader import SmartPDFReaderPipeline

    pipeline = SmartPDFReaderPipeline()
    text = pipeline.extract(pdf_bytes)
"""
from typing import Optional
import logging

from .contracts import (
    BlockType,
    ContentBlock,
    PageContent,
    DocumentContent,
    BlockExtractorContract,
    OCRProcessorContract,
)
from .document_parser import DocumentParser
from .text_extractor import TextBlockExtractor
from .image_extractor import ImageBlockExtractor
from .table_extractor import TableBlockExtractor
from .ocr_processor import TesseractOCRProcessor, NullOCRProcessor
from .content_merger import ContentMerger

logger = logging.getLogger(__name__)


class SmartPDFReaderPipeline:
    """
    Fachada que ensambla y ejecuta el pipeline completo de lectura de PDF.

    Construye el pipeline con los extractores configurados y expone
    un único método público `extract()` que retorna texto unificado.
    """

    def __init__(
        self,
        ocr_enabled: bool = True,
        table_extraction_enabled: bool = True,
        min_image_size: int = 5_000,
    ):
        """
        Args:
            ocr_enabled: Si True, intenta usar Tesseract OCR para imágenes.
            table_extraction_enabled: Si True, detecta y extrae tablas.
            min_image_size: Bytes mínimos para considerar una imagen relevante.
        """
        ocr_processor: OCRProcessorContract = (
            TesseractOCRProcessor() if ocr_enabled else NullOCRProcessor()
        )

        extractors = [TextBlockExtractor()]

        if table_extraction_enabled:
            extractors.append(TableBlockExtractor())

        extractors.append(ImageBlockExtractor(
            ocr_processor=ocr_processor,
            min_image_size=min_image_size,
        ))

        self._parser = DocumentParser(extractors)
        self._merger = ContentMerger()

        logger.info(
            f"SmartPDFReaderPipeline inicializado: "
            f"{len(extractors)} extractores, "
            f"OCR={'ON' if ocr_enabled else 'OFF'}, "
            f"Tablas={'ON' if table_extraction_enabled else 'OFF'}"
        )

    def extract(self, pdf_bytes: bytes) -> str:
        """
        Ejecuta el pipeline completo de extracción de contenido.

        Args:
            pdf_bytes: Contenido binario del PDF.

        Returns:
            Texto unificado con todo el contenido extraído.

        Raises:
            ValueError: Si el PDF no contiene contenido extraíble.
        """
        document = self._parser.parse(pdf_bytes)
        merged_text = self._merger.merge(document)

        if not merged_text.strip():
            raise ValueError(
                "El PDF no contiene contenido extraíble "
                "(puede ser un PDF escaneado sin OCR disponible)"
            )

        return merged_text

    def extract_structured(self, pdf_bytes: bytes) -> DocumentContent:
        """
        Retorna el contenido estructurado (para uso avanzado).

        Args:
            pdf_bytes: Contenido binario del PDF.

        Returns:
            DocumentContent con bloques clasificados por tipo.
        """
        return self._parser.parse(pdf_bytes)


__all__ = [
    # Pipeline (fachada principal)
    "SmartPDFReaderPipeline",
    # Contratos
    "BlockType",
    "ContentBlock",
    "PageContent",
    "DocumentContent",
    "BlockExtractorContract",
    "OCRProcessorContract",
    # Extractores (por si se necesitan individualmente)
    "TextBlockExtractor",
    "ImageBlockExtractor",
    "TableBlockExtractor",
    "TesseractOCRProcessor",
    "NullOCRProcessor",
    # Parser y Merger
    "DocumentParser",
    "ContentMerger",
]
