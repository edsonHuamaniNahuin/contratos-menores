"""
Servicio RAG de Extracción para TDRs.
Recupera fragmentos específicos del documento relacionados con secciones clave.
"""
import re
from typing import List, Dict
import logging

logger = logging.getLogger(__name__)


class RAGExtractionService:
    """
    Servicio RAG especializado en extracción de información estructurada de TDRs.
    NO es un chatbot, sino un sistema de recuperación de fragmentos relevantes.
    """

    # Patrones de búsqueda para secciones clave del TDR
    SECTION_PATTERNS = {
        "requisitos": [
            r"requisitos?\s+(?:del\s+)?(?:postor|proveedor|contratista)",
            r"condiciones?\s+(?:técnicas?|del\s+servicio)",
            r"especificaciones?\s+técnicas?",
            r"perfil\s+(?:del\s+)?(?:postor|proveedor)",
            r"experiencia\s+(?:requerida|mínima)",
            r"certificaciones?",
            r"calificaciones?"
        ],
        "penalidades": [
            r"penalidad(?:es)?",
            r"multas?",
            r"sanciones?",
            r"incumplimiento",
            r"garantías?",
            r"responsabilidad\s+contractual"
        ],
        "forma_pago": [
            r"forma\s+de\s+pago",
            r"modalidad\s+de\s+pago",
            r"cronograma\s+de\s+pago",
            r"desembolsos?",
            r"facturación",
            r"pagos?\s+parciales?"
        ],
        "plazos": [
            r"plazos?\s+(?:de\s+)?(?:ejecución|entrega|cumplimiento)",
            r"cronograma\s+(?:de\s+)?(?:ejecución|actividades)",
            r"duración\s+del\s+(?:contrato|servicio)",
            r"vigencia\s+(?:del\s+)?contrato",
            r"fecha\s+de\s+(?:inicio|término)"
        ],
        "presupuesto": [
            r"(?:presupuesto|monto|valor)\s+(?:referencial|estimado|total)",
            r"s/\.?\s*\d+(?:,\d{3})*(?:\.\d{2})?",  # Busca montos en soles
            r"valor\s+(?:referencial|estimado)",
            r"costo\s+(?:total|estimado)"
        ]
    }

    def __init__(self, chunk_size: int = 1000, chunk_overlap: int = 200):
        self.chunk_size = chunk_size
        self.chunk_overlap = chunk_overlap
        self.logger = logger

    def extract_relevant_fragments(self, full_text: str) -> Dict[str, List[str]]:
        """
        Recupera fragmentos del texto relacionados con las secciones clave del TDR.
        NOTA: Método síncrono - no hay operaciones async necesarias.

        Args:
            full_text: Texto completo extraído del PDF

        Returns:
            Dict con fragmentos recuperados por categoría
        """
        fragments = {
            "requisitos": [],
            "penalidades": [],
            "forma_pago": [],
            "plazos": [],
            "presupuesto": []
        }

        # Crear chunks del documento
        chunks = self._create_chunks(full_text)
        self.logger.info(f"Documento dividido en {len(chunks)} chunks")

        # Para cada categoría, buscar chunks relevantes
        for category, patterns in self.SECTION_PATTERNS.items():
            relevant_chunks = []

            for chunk in chunks:
                chunk_lower = chunk.lower()

                # Verificar si algún patrón coincide
                for pattern in patterns:
                    if re.search(pattern, chunk_lower, re.IGNORECASE):
                        relevant_chunks.append(chunk)
                        break  # Una coincidencia por chunk es suficiente

            # Limitar a los top K chunks más relevantes por categoría
            fragments[category] = relevant_chunks[:5]  # Top 5 por categoría

        total_found = sum(len(chunks) for chunks in fragments.values())

        self.logger.info(f"Fragmentos extraídos - Requisitos: {len(fragments['requisitos'])}, "
                        f"Penalidades: {len(fragments['penalidades'])}, "
                        f"Pagos: {len(fragments['forma_pago'])}, "
                        f"Plazos: {len(fragments['plazos'])}, "
                        f"Presupuesto: {len(fragments['presupuesto'])}, TOTAL: {total_found}")

        # FALLBACK: Si no se encontraron fragmentos específicos, usar los primeros chunks del documento
        if total_found == 0:
            self.logger.warning("⚠️ No se encontraron patrones específicos, usando primeros 10 chunks del documento")
            # Distribuir chunks entre categorías principales
            chunks_per_category = min(10, len(chunks)) // 2
            fragments["requisitos"] = chunks[:chunks_per_category]
            fragments["plazos"] = chunks[chunks_per_category:chunks_per_category*2]

        return fragments

    def _create_chunks(self, text: str) -> List[str]:
        """
        Divide el texto en chunks con overlap.

        Args:
            text: Texto completo

        Returns:
            Lista de chunks
        """
        words = text.split()
        chunks = []

        for i in range(0, len(words), self.chunk_size - self.chunk_overlap):
            chunk = ' '.join(words[i:i + self.chunk_size])
            chunks.append(chunk)

        return chunks

    def build_context_for_llm(self, fragments: Dict[str, List[str]]) -> str:
        """
        Construye el contexto completo para enviar al LLM.
        Combina todos los fragmentos recuperados en un solo texto estructurado.
        NOTA: Método síncrono - solo formatea strings.

        Args:
            fragments: Diccionario de fragmentos por categoría

        Returns:
            Contexto formateado para el LLM
        """
        context_parts = []

        context_parts.append("=== CONTEXTO EXTRAÍDO DEL TDR ===\n")

        for category, chunks in fragments.items():
            if chunks:
                category_label = category.upper().replace("_", " ")
                context_parts.append(f"\n## {category_label}:")
                for idx, chunk in enumerate(chunks, 1):
                    context_parts.append(f"\n[Fragmento {idx}]")
                    context_parts.append(chunk)

        context_parts.append("\n\n=== FIN DEL CONTEXTO ===")

        return "\n".join(context_parts)
