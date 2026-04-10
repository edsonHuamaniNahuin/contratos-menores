"""
Procesador OCR con degradación elegante.
Si Tesseract no está instalado, el sistema sigue funcionando sin OCR.
"""
import io
import logging
from typing import Optional

from .contracts import OCRProcessorContract

logger = logging.getLogger(__name__)


class TesseractOCRProcessor(OCRProcessorContract):
    """
    Implementación OCR usando pytesseract + Tesseract (SRP).

    Dependencia opcional: si pytesseract o Tesseract no están instalados,
    is_available() retorna False y el sistema degrada elegantemente.
    """

    # Timeout en segundos por imagen para evitar que Tesseract se cuelgue
    DEFAULT_TIMEOUT = 10

    def __init__(self, lang: str = "spa", timeout: int = DEFAULT_TIMEOUT):
        """
        Args:
            lang: Idioma para OCR (default: español).
            timeout: Segundos máximos por imagen (default: 10).
        """
        self._lang = lang
        self._timeout = timeout
        self._available: Optional[bool] = None

    def is_available(self) -> bool:
        """Verifica si pytesseract y Tesseract están disponibles."""
        if self._available is not None:
            return self._available

        try:
            import pytesseract

            pytesseract.get_tesseract_version()
            self._available = True
            logger.info("✅ Tesseract OCR disponible")
        except Exception:
            self._available = False
            logger.info("ℹ️  Tesseract OCR no disponible — imágenes se reportarán sin OCR")

        return self._available

    def extract_text(self, image_bytes: bytes) -> Optional[str]:
        """Extrae texto de una imagen usando Tesseract OCR con timeout."""
        if not self.is_available():
            return None

        try:
            import pytesseract
            from PIL import Image

            img = Image.open(io.BytesIO(image_bytes))

            # Convertir a RGB si es necesario (CMYK, paleta, etc.)
            if img.mode not in ("RGB", "L"):
                img = img.convert("RGB")

            text = pytesseract.image_to_string(
                img, lang=self._lang, timeout=self._timeout
            )
            return text.strip() if text else None

        except RuntimeError:
            # pytesseract lanza RuntimeError cuando se excede el timeout
            logger.warning(f"OCR timeout ({self._timeout}s) — imagen omitida")
            return None

        except Exception as e:
            logger.warning(f"Error en OCR: {e}")
            return None


class NullOCRProcessor(OCRProcessorContract):
    """
    Implementación nula de OCR (Null Object Pattern).
    Útil cuando OCR está deshabilitado por configuración.
    """

    def is_available(self) -> bool:
        return False

    def extract_text(self, image_bytes: bytes) -> Optional[str]:
        return None
