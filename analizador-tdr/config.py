"""
Configuración centralizada del microservicio usando Pydantic Settings.
Lee automáticamente desde el archivo .env
"""
from pydantic_settings import BaseSettings, SettingsConfigDict
from typing import Literal


class Settings(BaseSettings):
    """Configuración de la aplicación"""

    # App
    app_name: str = "Analizador TDR SEACE"
    app_env: str = "development"
    debug: bool = True
    host: str = "0.0.0.0"
    port: int = 8001

    # LLM Provider
    default_llm_provider: Literal["gemini", "openai", "anthropic"] = "gemini"

    # Google Gemini (2026 - Optimizado para volumen)
    gemini_api_key: str = ""
    gemini_model: str = "gemini-2.5-flash"

    # OpenAI
    openai_api_key: str = ""
    openai_model: str = "gpt-4o-mini"

    # Anthropic
    anthropic_api_key: str = ""
    anthropic_model: str = "claude-3-5-haiku-20250122"

    # RAG Configuration
    chunk_size: int = 1000
    chunk_overlap: int = 200
    top_k_chunks: int = 5

    # Límites
    max_file_size_mb: int = 10
    request_timeout_seconds: int = 60

    # Procesamiento Asíncrono (para scraper: 3-10 docs/40min = 360 docs/día)
    max_concurrent_requests: int = 3
    enable_batch_processing: bool = True

    model_config = SettingsConfigDict(
        env_file=".env",
        env_file_encoding="utf-8",
        case_sensitive=False
    )


# Instancia global de configuración
settings = Settings()
