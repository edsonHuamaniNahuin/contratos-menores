"""
Factory para crear clientes LLM según el proveedor seleccionado.
"""
from typing import Literal
from .gemini_client import GeminiClient
from .openai_client import OpenAIClient
from .anthropic_client import AnthropicClient
from .base_client import BaseLLMClient
from config import settings
import logging

logger = logging.getLogger(__name__)


class LLMFactory:
    """Factory para instanciar el cliente LLM correcto"""

    @staticmethod
    def create_client(
        provider: Literal["gemini", "openai", "anthropic"] = None
    ) -> BaseLLMClient:
        """
        Crea una instancia del cliente LLM según el proveedor.

        Args:
            provider: Nombre del proveedor (gemini, openai, anthropic).
                     Si es None, usa el configurado por defecto.

        Returns:
            Instancia del cliente LLM

        Raises:
            ValueError: Si el proveedor no está soportado o falta la API key
        """
        if provider is None:
            provider = settings.default_llm_provider

        logger.info(f"Creando cliente LLM: {provider}")

        if provider == "gemini":
            if not settings.gemini_api_key:
                raise ValueError("GEMINI_API_KEY no configurada en .env")
            return GeminiClient(
                api_key=settings.gemini_api_key,
                model_name=settings.gemini_model
            )

        elif provider == "openai":
            if not settings.openai_api_key:
                raise ValueError("OPENAI_API_KEY no configurada en .env")
            return OpenAIClient(
                api_key=settings.openai_api_key,
                model_name=settings.openai_model
            )

        elif provider == "anthropic":
            if not settings.anthropic_api_key:
                raise ValueError("ANTHROPIC_API_KEY no configurada en .env")
            return AnthropicClient(
                api_key=settings.anthropic_api_key,
                model_name=settings.anthropic_model
            )

        else:
            raise ValueError(f"Proveedor LLM no soportado: {provider}")
