"""
Cliente para OpenAI API (GPT-4o).
"""
from openai import AsyncOpenAI
from typing import Dict, List, Optional
import logging
from .base_client import BaseLLMClient

logger = logging.getLogger(__name__)


class OpenAIClient(BaseLLMClient):
    """Cliente para OpenAI API (GPT-4o con Structured Outputs)"""

    def __init__(self, api_key: str, model_name: str = "gpt-4o"):
        self.api_key = api_key
        self.model_name = model_name
        self.logger = logger
        self.client = AsyncOpenAI(api_key=api_key)

    async def analyze_tdr(self, context: str) -> Dict:
        """
        Analiza el TDR usando GPT-4o.

        Args:
            context: Contexto del TDR recuperado por el RAG

        Returns:
            Dict con el análisis estructurado
        """
        try:
            self.logger.info(f"Analizando TDR con OpenAI ({self.model_name})")

            # Prompt del usuario
            user_prompt = f"""
Analiza el siguiente TDR del SEACE y responde solo con JSON siguiendo la estructura requerida (resumen_ejecutivo, requisitos_tecnicos, reglas_de_negocio, politicas_y_penalidades, presupuesto_referencial). Si algún bloque no tiene datos, utiliza [] o null.

TDR:
{context}
"""

            # Llamada a la API con Structured Outputs (response_format)
            response = await self.client.chat.completions.create(
                model=self.model_name,
                messages=[
                    {"role": "system", "content": self.SYSTEM_PROMPT},
                    {"role": "user", "content": user_prompt}
                ],
                temperature=0.2,
                max_tokens=2048,
                response_format={"type": "json_object"}  # Fuerza JSON
            )

            # Extraer contenido
            response_text = response.choices[0].message.content

            self.logger.debug(f"Respuesta de OpenAI (primeros 500 chars): {response_text[:500]}")

            # Parsear JSON
            result = self._parse_json_response(response_text)

            self.logger.info("Análisis completado exitosamente con OpenAI")

            return result

        except Exception as e:
            self.logger.error(f"Error al analizar con OpenAI: {str(e)}")
            raise ValueError(f"Error en OpenAI API: {str(e)}")

    async def evaluate_compatibility(
        self,
        company_copy: str,
        analisis_tdr: Dict,
        contrato_contexto: Optional[Dict] = None,
        keywords: Optional[List[str]] = None
    ) -> Dict:
        try:
            prompt = self._build_compatibility_prompt(company_copy, analisis_tdr, contrato_contexto, keywords)
            response = await self.client.chat.completions.create(
                model=self.model_name,
                messages=[
                    {"role": "system", "content": "Asesor especializado en compatibilidad de contratos SEACE."},
                    {"role": "user", "content": prompt},
                ],
                temperature=0.2,
                max_tokens=1024,
                response_format={"type": "json_object"}
            )

            response_text = response.choices[0].message.content
            self.logger.debug(f"Compatibilidad OpenAI (primeros 400 chars): {response_text[:400]}")
            return self._parse_json_response(response_text)
        except Exception as e:
            self.logger.error(f"Error en compatibilidad OpenAI: {str(e)}")
            raise ValueError(f"Error al evaluar compatibilidad: {str(e)}")
