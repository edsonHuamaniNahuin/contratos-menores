"""
Interfaz abstracta para clientes LLM.
Define el contrato que deben cumplir todos los proveedores de LLM.
"""
from abc import ABC, abstractmethod
from typing import Dict, List, Optional
import json


class BaseLLMClient(ABC):
    """Clase base abstracta para clientes LLM"""

    SYSTEM_PROMPT = """
Eres un analista experto en licitaciones públicas del SEACE (Perú) con más de 10 años de experiencia.

Tu misión es analizar Términos de Referencia (TDR) y entregar un resumen técnico accionable que describa qué busca la entidad, qué requisitos impone y cuáles son los riesgos clave.

INSTRUCCIONES CLAVE:
1. Ignora texto legal repetitivo o genérico.
2. Prioriza requisitos técnicos, certificaciones, experiencia mínima y capacidades logísticas.
3. Identifica reglas de negocio y condiciones contractuales relevantes (plazos, lugar, modalidad de pago, garantías).
4. Enumera penalidades, multas o restricciones relevantes para la ejecución.
5. Cuando no haya información suficiente sobre algún bloque, devuelve arrays vacíos o null según corresponda.
6. Devuelve únicamente un JSON válido con la estructura indicada (sin markdown, sin comentarios).
"""

    COMPATIBILITY_JSON_TEMPLATE = """
{
    "score": 8.5,
    "nivel": "apto",
    "explicacion": "Texto breve con la razón principal del puntaje",
    "factores_clave": ["Elemento que favorece la compatibilidad"],
    "riesgos": ["Riesgo o bloqueo detectado"]
}
"""

    @abstractmethod
    async def analyze_tdr(self, context: str) -> Dict:
        """
        Analiza el contexto del TDR y devuelve un JSON estructurado.

        Args:
            context: Contexto completo del TDR (fragmentos recuperados por el RAG)

        Returns:
            Dict con el análisis estructurado según el esquema TDRAnalysisResponse
        """
        pass

    @abstractmethod
    async def evaluate_compatibility(
        self,
        company_copy: str,
        analisis_tdr: Dict,
        contrato_contexto: Optional[Dict] = None,
        keywords: Optional[List[str]] = None
    ) -> Dict:
        """Evalúa compatibilidad entre el perfil del suscriptor y el análisis del TDR."""
        pass

    def _parse_json_response(self, response_text: str) -> Dict:
        """
        Parsea la respuesta del LLM asegurando que sea JSON válido.
        Intenta limpiar la respuesta si viene con markdown o texto adicional.

        Args:
            response_text: Texto de respuesta del LLM

        Returns:
            Dict parseado

        Raises:
            ValueError: Si no se puede parsear como JSON válido
        """
        import re

        # Limpiar espacios y saltos de línea problemáticos
        cleaned = response_text.strip()

        # Remover bloques de código markdown si existen
        if cleaned.startswith("```"):
            lines = cleaned.split("\n")
            cleaned = "\n".join(lines[1:-1]) if len(lines) > 2 else cleaned
            cleaned = cleaned.replace("```json", "").replace("```", "").strip()

        # Intentar parsear directamente primero
        try:
            return json.loads(cleaned)
        except json.JSONDecodeError as e:
            # Estrategia 2: Buscar objeto JSON usando regex
            json_pattern = r'\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}'
            matches = re.finditer(json_pattern, cleaned, re.DOTALL)

            for match in matches:
                try:
                    potential_json = match.group(0)
                    return json.loads(potential_json)
                except json.JSONDecodeError:
                    continue

            # Si todo falla, lanzar error con contexto
            raise ValueError(f"La respuesta del LLM no es un JSON válido: {str(e)}\n\nRespuesta recibida (primeros 500 chars): {cleaned[:500]}")

    def _build_compatibility_prompt(
        self,
        company_copy: str,
        analisis_tdr: Dict,
        contrato_contexto: Optional[Dict] = None,
        keywords: Optional[List[str]] = None
    ) -> str:
        import json

        contexto = contrato_contexto or {}
        keywords = keywords or []

        return f"""
Actúa como asesor de nuevos negocios para proveedores del SEACE en Perú.

PERFIL DEL PROVEEDOR:
{company_copy}

PALABRAS CLAVE DEL PERFIL: {', '.join(keywords) if keywords else 'N/A'}

CONTRATO:
{json.dumps(contexto, ensure_ascii=False, indent=2)}

ANÁLISIS BASE DEL TDR:
{json.dumps(analisis_tdr, ensure_ascii=False, indent=2)}

Evalúa qué tan alineado está este TDR con las capacidades descritas. Devuelve ÚNICAMENTE un JSON siguiendo este esquema:
{self.COMPATIBILITY_JSON_TEMPLATE.strip()}

Reglas:
- Usa score decimal entre 0 y 10 (ej. 7.5).
- "nivel": "apto" si score >=8, "revisar" si 5-7.9, "descartar" si <5.
- "explicacion" máximo 3 frases.
- Lista factores y riesgos específicos; si no hay, devuelve arrays vacíos.
- No inventes requisitos que no estén en el análisis.
"""
