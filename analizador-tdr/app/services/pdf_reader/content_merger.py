"""
Fusionador de contenido extraído.
Ensambla los bloques de texto, imágenes y tablas en un documento final unificado.
"""
from typing import List
import logging

from .contracts import DocumentContent, ContentBlock, BlockType

logger = logging.getLogger(__name__)


class ContentMerger:
    """
    Fusiona todos los bloques de contenido en un texto final unificado (SRP).

    Ordena los bloques por posición vertical dentro de cada página,
    manteniendo el orden natural de lectura del documento.
    """

    # Separadores por tipo de bloque
    _BLOCK_SEPARATORS = {
        BlockType.TEXT: "",
        BlockType.IMAGE: "\n",
        BlockType.TABLE: "\n",
    }

    def merge(self, document: DocumentContent) -> str:
        """
        Fusiona el contenido de todas las páginas en un solo texto.

        Args:
            document: DocumentContent con todas las páginas y bloques.

        Returns:
            Texto unificado del documento completo.
        """
        parts: List[str] = []
        total_chars = 0

        for page in document.pages:
            if not page.blocks:
                continue

            parts.append(f"\n--- Página {page.page_number} ---\n")

            # Ordenar bloques por posición vertical (y0) para lectura natural
            sorted_blocks = self._sort_blocks_by_position(page.blocks)

            for block in sorted_blocks:
                separator = self._BLOCK_SEPARATORS.get(block.block_type, "")
                if separator:
                    parts.append(separator)
                parts.append(block.content)
                total_chars += len(block.content)

        merged = "\n".join(parts).strip()

        # Estadísticas
        text_blocks = sum(
            1 for p in document.pages
            for b in p.blocks if b.block_type == BlockType.TEXT
        )
        image_blocks = sum(
            1 for p in document.pages
            for b in p.blocks if b.block_type == BlockType.IMAGE
        )
        table_blocks = sum(
            1 for p in document.pages
            for b in p.blocks if b.block_type == BlockType.TABLE
        )

        logger.info(
            f"Documento fusionado: {document.total_pages} págs, "
            f"{total_chars} chars — "
            f"texto: {text_blocks}, imágenes: {image_blocks}, tablas: {table_blocks}"
        )

        return merged

    @staticmethod
    def _sort_blocks_by_position(blocks: List[ContentBlock]) -> List[ContentBlock]:
        """Ordena bloques por posición vertical (y0) para mantener orden de lectura."""
        return sorted(
            blocks,
            key=lambda b: b.bbox[1] if b.bbox and len(b.bbox) >= 2 else 0,
        )
