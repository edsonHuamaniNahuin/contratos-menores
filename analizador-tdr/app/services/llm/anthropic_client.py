"""
Cliente para Anthropic Claude API.
"""
from anthropic import AsyncAnthropic
from typing import Dict
import logging
from .base_client import BaseLLMClient

logger = logging.getLogger(__name__)


class AnthropicClient(BaseLLMClient):
    """Cliente para Anthropic Claude API"""

    def __init__(self, api_key: str, model_name: str = "claude-3-5-sonnet-20241022"):
        self.api_key = api_key
        self.model_name = model_name
        self.logger = logger
        self.client = AsyncAnthropic(api_key=api_key)

    async def analyze_tdr(self, context: str) -> Dict:
        """
        Analiza el TDR usando Claude.

        Args:
            context: Contexto del TDR recuperado por el RAG

        Returns:
            Dict con el análisis estructurado
        """
        try:
            self.logger.info(f"Analizando TDR con Anthropic ({self.model_name})")

            # Prompt del usuario
            user_prompt = f"""
Analiza el siguiente TDR del SEACE y devuelve el análisis estructurado en formato JSON:

{context}

Recuerda: Devuelve SOLO el objeto JSON sin texto adicional.
"""

            # Llamada a la API
            response = await self.client.messages.create(
                model=self.model_name,
                max_tokens=2048,
                temperature=0.2,
                system=self.SYSTEM_PROMPT,
                messages=[
                    {"role": "user", "content": user_prompt}
                ]
            )

            # Extraer contenido
            response_text = response.content[0].text

            self.logger.debug(f"Respuesta de Claude (primeros 500 chars): {response_text[:500]}")

            # Parsear JSON
            result = self._parse_json_response(response_text)

            self.logger.info("Análisis completado exitosamente con Claude")

            return result

        except Exception as e:
            self.logger.error(f"Error al analizar con Anthropic: {str(e)}")
            raise ValueError(f"Error en Anthropic API: {str(e)}")
