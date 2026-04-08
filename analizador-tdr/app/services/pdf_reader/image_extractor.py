"""
Extractor de imágenes de páginas PDF.
Detecta imágenes, clasifica si contienen texto, y aplica OCR cuando corresponde.
"""
from typing import List, Optional
import logging

from .contracts import (
    BlockExtractorContract,
    ContentBlock,
    BlockType,
    OCRProcessorContract,
)

logger = logging.getLogger(__name__)


class ImageBlockExtractor(BlockExtractorContract):
    """
    Detecta y procesa imágenes en páginas PDF (SRP).

    Flujo por imagen:
    1. Detectar imagen en la página
    2. Filtrar imágenes decorativas (íconos, bullets < min_size)
    3. Clasificar: ¿tiene texto extraíble?
       - Sí → OCR → extraer texto
       - No → reportar como imagen sin texto
    4. Retornar ContentBlock con el resultado

    La dependencia de OCR se inyecta (DIP) para poder
    intercambiar implementaciones o desactivarlo.
    """

    # Tamaño mínimo en bytes para considerar una imagen relevante
    DEFAULT_MIN_IMAGE_SIZE = 5_000         # ~5 KB
    # Tamaño máximo en bytes — imágenes más grandes se omiten (fotos, escaneos)
    DEFAULT_MAX_IMAGE_SIZE = 500_000       # ~500 KB
    # Mínimo de caracteres OCR para considerar que la imagen tiene texto útil
    MIN_OCR_CHARS = 20

    def __init__(
        self,
        ocr_processor: Optional[OCRProcessorContract] = None,
        min_image_size: int = DEFAULT_MIN_IMAGE_SIZE,
        max_image_size: int = DEFAULT_MAX_IMAGE_SIZE,
    ):
        """
        Args:
            ocr_processor: Procesador OCR inyectado (puede ser None/NullOCR).
            min_image_size: Bytes mínimos para considerar una imagen relevante.
            max_image_size: Bytes máximos — imágenes más grandes se omiten del OCR.
        """
        self._ocr = ocr_processor
        self._min_image_size = min_image_size
        self._max_image_size = max_image_size

    def extract(self, page, page_number: int) -> List[ContentBlock]:
        """Detecta y procesa imágenes de una página PDF."""
        blocks: List[ContentBlock] = []

        try:
            images = page.get_images(full=True)
        except Exception as e:
            logger.warning(f"Error obteniendo imágenes de página {page_number}: {e}")
            return blocks

        for img_index, img_info in enumerate(images):
            xref = img_info[0]
            block = self._process_single_image(page, xref, page_number, img_index)
            if block:
                blocks.append(block)

        return blocks

    def _process_single_image(
        self, page, xref: int, page_number: int, img_index: int
    ) -> Optional[ContentBlock]:
        """Procesa una imagen individual: extrae, clasifica y aplica OCR."""
        try:
            base_image = page.parent.extract_image(xref)
            if not base_image:
                return None

            image_bytes = base_image.get("image", b"")
            image_ext = base_image.get("ext", "unknown")
            image_size = len(image_bytes)

            # Filtrar imágenes decorativas (íconos, bullets, logos pequeños)
            if image_size < self._min_image_size:
                return None

            # Filtrar imágenes demasiado grandes (fotos, escaneos completos)
            if image_size > self._max_image_size:
                logger.debug(
                    f"Imagen omitida (muy grande): {image_size} bytes > "
                    f"{self._max_image_size} bytes en página {page_number}"
                )
                return None

            # Intentar OCR si el procesador está disponible
            if self._ocr and self._ocr.is_available():
                ocr_text = self._ocr.extract_text(image_bytes)

                if ocr_text and len(ocr_text.strip()) >= self.MIN_OCR_CHARS:
                    # Imagen CON texto extraíble → retornar texto OCR
                    return ContentBlock(
                        block_type=BlockType.IMAGE,
                        page_number=page_number,
                        content=f"[Texto extraído de imagen por OCR]:\n{ocr_text.strip()}",
                        metadata={
                            "source": "ocr",
                            "image_format": image_ext,
                            "image_size_bytes": image_size,
                            "image_index": img_index,
                        },
                    )

            # Imagen SIN texto extraíble (o OCR no disponible)
            return ContentBlock(
                block_type=BlockType.IMAGE,
                page_number=page_number,
                content=(
                    f"[Imagen detectada — {image_ext.upper()}, "
                    f"{image_size:,} bytes — sin texto extraíble]"
                ),
                metadata={
                    "source": "image_only",
                    "image_format": image_ext,
                    "image_size_bytes": image_size,
                    "image_index": img_index,
                },
            )

        except Exception as e:
            logger.warning(
                f"Error procesando imagen xref={xref} en página {page_number}: {e}"
            )
            return None
