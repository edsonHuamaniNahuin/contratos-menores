"""
Contratos e interfaces para el módulo PDF Reader.
Define las abstracciones que cumplan SRP, ISP y DIP (SOLID).
"""
from abc import ABC, abstractmethod
from dataclasses import dataclass, field
from typing import List, Optional
from enum import Enum


class BlockType(Enum):
    """Tipos de bloques detectables en un documento PDF."""
    TEXT = "text"
    IMAGE = "image"
    TABLE = "table"


@dataclass
class ContentBlock:
    """Bloque individual de contenido extraído de una página PDF."""
    block_type: BlockType
    page_number: int
    content: str
    bbox: Optional[tuple] = None          # (x0, y0, x1, y1)
    metadata: dict = field(default_factory=dict)


@dataclass
class PageContent:
    """Todo el contenido extraído de una sola página PDF."""
    page_number: int
    blocks: List[ContentBlock] = field(default_factory=list)


@dataclass
class DocumentContent:
    """Contenido completo extraído de un documento PDF."""
    pages: List[PageContent] = field(default_factory=list)
    metadata: dict = field(default_factory=dict)
    total_pages: int = 0


class BlockExtractorContract(ABC):
    """
    Interfaz para extractores de bloques de contenido (ISP).
    Cada implementación es responsable de un solo tipo de bloque (SRP).
    """

    @abstractmethod
    def extract(self, page, page_number: int) -> List[ContentBlock]:
        """
        Extrae bloques de contenido de una página PDF.

        Args:
            page: Objeto fitz.Page de PyMuPDF.
            page_number: Número de página (1-indexed).

        Returns:
            Lista de ContentBlock extraídos.
        """
        pass


class OCRProcessorContract(ABC):
    """
    Interfaz para procesadores OCR (ISP).
    Permite inyectar diferentes implementaciones de OCR (DIP).
    """

    @abstractmethod
    def is_available(self) -> bool:
        """Verifica si el motor OCR está disponible en el sistema."""
        pass

    @abstractmethod
    def extract_text(self, image_bytes: bytes) -> Optional[str]:
        """
        Extrae texto de una imagen mediante OCR.

        Args:
            image_bytes: Bytes de la imagen.

        Returns:
            Texto extraído o None si falla.
        """
        pass
