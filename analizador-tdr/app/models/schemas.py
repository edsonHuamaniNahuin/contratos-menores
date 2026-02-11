"""
Modelos Pydantic para entrada/salida del microservicio.
Define el esquema estructurado del análisis de TDR.
"""
from pydantic import BaseModel, Field, field_validator
from typing import Any, Dict, List, Optional, Literal
from datetime import datetime


class TDRAnalysisResponse(BaseModel):
    """
    Esquema de salida del análisis de TDR (OBLIGATORIO).
    El LLM DEBE devolver un JSON que cumpla exactamente esta estructura.
    """
    resumen_ejecutivo: str = Field(
        ...,
        description="Resumen técnico ejecutivo del TDR en 2-3 párrafos",
        min_length=50,
        max_length=1000
    )

    requisitos_tecnicos: List[str] = Field(
        default_factory=list,
        description="Lista de requisitos técnicos específicos (tecnologías, experiencia, certificaciones). Array vacío si no hay información.",
        min_items=0
    )

    reglas_de_negocio: List[str] = Field(
        default_factory=list,
        description="Reglas de negocio, condiciones contractuales y obligaciones del proveedor. Array vacío si no hay información.",
        min_items=0
    )

    politicas_y_penalidades: List[str] = Field(
        ...,
        description="Políticas de incumplimiento, penalidades, multas y garantías",
        min_items=0
    )

    presupuesto_referencial: Optional[str] = Field(
        None,
        description="Presupuesto referencial o monto estimado (puede ser null si no se especifica)"
    )

    @field_validator('requisitos_tecnicos', 'reglas_de_negocio')
    @classmethod
    def validar_lista_no_vacia(cls, v):
        # Permitir arrays vacíos cuando no hay información en el TDR
        return v


class TDRAnalysisRequest(BaseModel):
    """Request para análisis de TDR"""
    llm_provider: Optional[Literal["gemini", "openai", "anthropic"]] = Field(
        None,
        description="Proveedor de LLM a usar (opcional, usa el configurado por defecto)"
    )


class CompatibilityScoreRequest(BaseModel):
    """Request para evaluación de compatibilidad por suscriptor"""
    company_copy: str = Field(
        ...,
        min_length=20,
        max_length=4000,
        description="Descripción del rubro/fortalezas del suscriptor"
    )
    analisis_tdr: Dict[str, Any] = Field(
        ...,
        description="Análisis estructurado previamente generado para el TDR"
    )
    contrato_contexto: Optional[Dict[str, Any]] = Field(
        default=None,
        description="Metadata del contrato (entidad, objeto, fechas)"
    )
    keywords: Optional[List[str]] = Field(
        default_factory=list,
        description="Lista de keywords suscritas para enriquecer el contexto"
    )


class CompatibilityScoreResponse(BaseModel):
    """Respuesta estructurada de la evaluación de compatibilidad"""
    score: float = Field(
        ...,
        ge=0,
        le=10,
        description="Puntaje decimal (0-10) que refleja la compatibilidad"
    )
    nivel: Literal['apto', 'revisar', 'descartar'] = Field(
        ...,
        description="Clasificación cualitativa basada en el score"
    )
    explicacion: str = Field(
        ...,
        min_length=20,
        max_length=1000,
        description="Motivo resumido del score asignado"
    )
    factores_clave: List[str] = Field(
        default_factory=list,
        description="Elementos del TDR que favorecen la compatibilidad"
    )
    riesgos: List[str] = Field(
        default_factory=list,
        description="Alertas o restricciones detectadas"
    )
    timestamp: datetime = Field(
        default_factory=datetime.utcnow,
        description="Momento de generación del score"
    )


class HealthCheckResponse(BaseModel):
    """Respuesta del endpoint de health check"""
    status: str
    app_name: str
    version: str
    timestamp: datetime
    llm_provider: str


class ErrorResponse(BaseModel):
    """Respuesta de error estándar"""
    error: str
    detail: str
    timestamp: datetime
