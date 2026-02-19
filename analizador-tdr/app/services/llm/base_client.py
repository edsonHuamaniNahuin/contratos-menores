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

    def _repair_truncated_json(self, text: str) -> str:
        """
        Intenta reparar un JSON truncado cerrando strings, arrays y objetos abiertos.
        Útil cuando el LLM alcanza max_output_tokens y corta la respuesta.
        """
        import re

        repaired = text.rstrip()

        # Si termina a mitad de un string (comilla abierta sin cerrar),
        # cerrar la comilla y truncar el valor.
        in_string = False
        escape_next = False
        for ch in repaired:
            if escape_next:
                escape_next = False
                continue
            if ch == '\\':
                escape_next = True
                continue
            if ch == '"':
                in_string = not in_string

        if in_string:
            # Cortar hasta la última comilla válida + cerrar string
            repaired = repaired.rstrip()
            # Remover caracteres parciales al final del string truncado
            repaired += '"'

        # Remover trailing commas antes de cerrar
        repaired = re.sub(r',\s*$', '', repaired)

        # Contar llaves y corchetes abiertos y cerrarlos
        open_braces = repaired.count('{') - repaired.count('}')
        open_brackets = repaired.count('[') - repaired.count(']')

        # Cerrar corchetes primero (están dentro de objetos normalmente)
        for _ in range(max(0, open_brackets)):
            repaired += ']'
        for _ in range(max(0, open_braces)):
            repaired += '}'

        return repaired

    def _parse_json_response(self, response_text: str) -> Dict:
        """
        Parsea la respuesta del LLM asegurando que sea JSON válido.
        Intenta limpiar la respuesta si viene con markdown o texto adicional.
        Si el JSON está truncado (Unterminated string), intenta repararlo.

        Args:
            response_text: Texto de respuesta del LLM

        Returns:
            Dict parseado

        Raises:
            ValueError: Si no se puede parsear como JSON válido
        """
        import re
        import logging
        logger = logging.getLogger(__name__)

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
        except json.JSONDecodeError as first_error:
            # Estrategia 2: Reparar JSON truncado (Unterminated string, etc.)
            if 'Unterminated' in str(first_error) or 'Expecting' in str(first_error):
                try:
                    repaired = self._repair_truncated_json(cleaned)
                    result = json.loads(repaired)
                    logger.warning(f"JSON reparado exitosamente (truncado por max_output_tokens). Original: {len(cleaned)} chars, reparado: {len(repaired)} chars")
                    return result
                except json.JSONDecodeError:
                    pass  # Continuar con otras estrategias

            # Estrategia 3: Buscar objeto JSON completo usando regex
            json_pattern = r'\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}'
            matches = re.finditer(json_pattern, cleaned, re.DOTALL)

            for match in matches:
                try:
                    potential_json = match.group(0)
                    return json.loads(potential_json)
                except json.JSONDecodeError:
                    continue

            # Estrategia 4: Reparar + regex (para JSONs grandes truncados)
            try:
                repaired = self._repair_truncated_json(cleaned)
                return json.loads(repaired)
            except json.JSONDecodeError:
                pass

            # Si todo falla, lanzar error con contexto
            raise ValueError(f"La respuesta del LLM no es un JSON válido: {str(first_error)}\n\nRespuesta recibida (primeros 500 chars): {cleaned[:500]}")

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
