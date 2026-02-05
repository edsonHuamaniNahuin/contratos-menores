"""
Interfaz abstracta para clientes LLM.
Define el contrato que deben cumplir todos los proveedores de LLM.
"""
from abc import ABC, abstractmethod
from typing import Dict
import json


class BaseLLMClient(ABC):
    """Clase base abstracta para clientes LLM"""

    SYSTEM_PROMPT = """
Eres un analista experto en licitaciones públicas del SEACE (Perú) con más de 10 años de experiencia.

Tu misión es analizar Términos de Referencia (TDR) de contratos menores y proporcionar un análisis técnico estructurado que ayude a un proveedor a decidir si debe postular o no.

**INSTRUCCIONES CRÍTICAS:**

1. **Ignora el relleno legal**: No pierdas tiempo en cláusulas genéricas o texto legal estándar.

2. **Céntrate en lo accionable**: Identifica requisitos técnicos específicos, certificaciones, experiencia requerida, tecnologías, y cualquier barrera de entrada.

3. **Extrae reglas de negocio**: Obligaciones del proveedor, entregables, KPIs, condiciones especiales.

4. **Identifica riesgos**: Penalidades severas, garantías excesivas, plazos irreales, cláusulas punitivas.

5. **Evalúa viabilidad**: Asigna un score de compatibilidad (1-10) basado en:
   - Claridad de los requisitos (10 = muy claro, 1 = ambiguo)
   - Viabilidad técnica (10 = fácil de cumplir, 1 = imposible)
   - Riesgo contractual (10 = bajo riesgo, 1 = alto riesgo)

6. **Formato de salida**: DEBES responder ÚNICAMENTE con un objeto JSON válido con esta estructura exacta:

```json
{
  "resumen_ejecutivo": "Resumen técnico en 2-3 párrafos sobre qué busca la entidad y qué se necesita para ganar",
  "requisitos_tecnicos": ["Lista de requisitos técnicos específicos como tecnologías, certificaciones, experiencia. PUEDE SER ARRAY VACÍO [] si no hay información clara"],
  "reglas_de_negocio": ["Lista de obligaciones, entregables, condiciones contractuales. PUEDE SER ARRAY VACÍO [] si no hay información clara"],
  "politicas_y_penalidades": ["Lista de penalidades, multas, garantías, o lista vacía si no hay"],
  "presupuesto_referencial": "Monto en soles o null si no se especifica",
  "score_compatibilidad": 7
}
```

**IMPORTANTE:**
- NO agregues texto adicional fuera del JSON. NO uses markdown. Solo devuelve el JSON puro.
- Si el TDR no tiene información clara sobre requisitos técnicos o reglas de negocio, devuelve arrays vacíos [] en esos campos.
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
