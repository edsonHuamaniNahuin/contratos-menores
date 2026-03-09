"""
Extractor de tablas de páginas PDF.
Utiliza PyMuPDF >= 1.23.0 (find_tables) para detección nativa de tablas.
"""
from typing import List
import logging

from .contracts import BlockExtractorContract, ContentBlock, BlockType

logger = logging.getLogger(__name__)


class TableBlockExtractor(BlockExtractorContract):
    """
    Detecta y extrae tablas de páginas PDF usando PyMuPDF (SRP).

    Utiliza page.find_tables() disponible desde PyMuPDF 1.23.0.
    Formatea cada tabla como texto estructurado con separadores.
    """

    def __init__(self, min_rows: int = 2, min_cols: int = 2):
        """
        Args:
            min_rows: Mínimo de filas para considerar una tabla válida.
            min_cols: Mínimo de columnas para considerar una tabla válida.
        """
        self._min_rows = min_rows
        self._min_cols = min_cols

    def extract(self, page, page_number: int) -> List[ContentBlock]:
        """Detecta y extrae tablas de una página PDF."""
        blocks: List[ContentBlock] = []

        try:
            tables = page.find_tables()
        except AttributeError:
            # PyMuPDF < 1.23.0 no tiene find_tables()
            logger.debug(f"find_tables() no disponible en página {page_number}")
            return blocks
        except Exception as e:
            logger.warning(f"Error detectando tablas en página {page_number}: {e}")
            return blocks

        for idx, table in enumerate(tables):
            block = self._process_single_table(table, page_number, idx)
            if block:
                blocks.append(block)

        return blocks

    def _process_single_table(
        self, table, page_number: int, table_index: int
    ) -> ContentBlock | None:
        """Procesa una tabla individual y la convierte en ContentBlock."""
        try:
            table_data = table.extract()

            if not table_data:
                return None

            # Filtrar tablas muy pequeñas (no son tablas reales)
            num_rows = len(table_data)
            num_cols = max((len(row) for row in table_data), default=0)

            if num_rows < self._min_rows or num_cols < self._min_cols:
                return None

            formatted = self._format_table(table_data)
            if not formatted.strip():
                return None

            return ContentBlock(
                block_type=BlockType.TABLE,
                page_number=page_number,
                content=formatted,
                bbox=tuple(table.bbox) if hasattr(table, "bbox") else None,
                metadata={
                    "rows": num_rows,
                    "cols": num_cols,
                    "table_index": table_index,
                },
            )

        except Exception as e:
            logger.warning(
                f"Error extrayendo tabla {table_index} en página {page_number}: {e}"
            )
            return None

    @staticmethod
    def _format_table(data: list) -> str:
        """
        Formatea datos de tabla como texto legible con separadores.

        Ejemplo de salida:
            [TABLA]
            Col1 | Col2 | Col3
            ─────┼──────┼─────
            A    | B    | C
        """
        if not data:
            return ""

        lines = ["[TABLA]"]

        for row_idx, row in enumerate(data):
            cells = [str(cell).strip() if cell else "" for cell in row]
            line = " | ".join(cells)
            lines.append(line)

            # Separador después de la primera fila (cabecera)
            if row_idx == 0:
                separator = "─" * len(line)
                lines.append(separator)

        lines.append("[/TABLA]")
        return "\n".join(lines)
