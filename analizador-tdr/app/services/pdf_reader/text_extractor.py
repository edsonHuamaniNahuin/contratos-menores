"""
Extractor de bloques de texto digital de páginas PDF.
Responsabilidad única: extraer texto nativo (no escaneado) de un PDF.
"""
from typing import List
import logging

from .contracts import BlockExtractorContract, ContentBlock, BlockType

logger = logging.getLogger(__name__)


class TextBlockExtractor(BlockExtractorContract):
    """
    Extrae texto digital embebido en páginas PDF usando PyMuPDF (SRP).

    Utiliza el método 'dict' de PyMuPDF para obtener información estructurada
    de bloques, líneas y spans, preservando la jerarquía del layout.
    """

    def __init__(self, min_block_chars: int = 3):
        """
        Args:
            min_block_chars: Mínimo de caracteres para considerar un bloque válido.
        """
        self._min_block_chars = min_block_chars

    def extract(self, page, page_number: int) -> List[ContentBlock]:
        """Extrae bloques de texto digital de una página PDF."""
        blocks: List[ContentBlock] = []

        try:
            text_dict = page.get_text("dict", flags=11)  # flags=11: preserve whitespace + images info

            for block in text_dict.get("blocks", []):
                # type 0 = texto, type 1 = imagen (los ignoramos aquí)
                if block.get("type") != 0:
                    continue

                text = self._extract_block_text(block)
                if len(text.strip()) < self._min_block_chars:
                    continue

                blocks.append(ContentBlock(
                    block_type=BlockType.TEXT,
                    page_number=page_number,
                    content=text.strip(),
                    bbox=tuple(block.get("bbox", ())),
                    metadata={
                        "lines_count": len(block.get("lines", [])),
                    }
                ))

        except Exception as e:
            logger.warning(f"Error extrayendo texto de página {page_number}: {e}")
            # Fallback: extracción simple
            fallback_text = page.get_text("text")
            if fallback_text and len(fallback_text.strip()) >= self._min_block_chars:
                blocks.append(ContentBlock(
                    block_type=BlockType.TEXT,
                    page_number=page_number,
                    content=fallback_text.strip(),
                    metadata={"source": "fallback_simple"},
                ))

        return blocks

    @staticmethod
    def _extract_block_text(block: dict) -> str:
        """Reconstruye texto de un bloque a partir de sus líneas y spans."""
        lines: List[str] = []
        for line in block.get("lines", []):
            spans_text = " ".join(
                span.get("text", "")
                for span in line.get("spans", [])
            )
            if spans_text.strip():
                lines.append(spans_text.strip())
        return "\n".join(lines)
