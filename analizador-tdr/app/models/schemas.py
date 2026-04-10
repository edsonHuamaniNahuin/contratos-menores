"""
Modelos Pydantic para entrada/salida del microservicio.
Define el esquema estructurado del análisis de TDR.
"""
from pydantic import BaseModel, Field, field_validator
from typing import Any, Dict, List, Optional, Literal, Union
from datetime import datetime


class TDRAnalysisResponse(BaseModel):
    """
    Esquema de salida del análisis de TDR (OBLIGATORIO).
    El LLM DEBE devolver un JSON que cumpla exactamente esta estructura.
    """
    resumen_ejecutivo: Optional[str] = Field(
        None,
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
        default_factory=list,
        description="Políticas de incumplimiento, penalidades, multas y garantías",
        min_items=0
    )

    presupuesto_referencial: Optional[str] = Field(
        None,
        description="Presupuesto referencial o monto estimado (puede ser null si no se especifica)"
    )

    @field_validator('requisitos_tecnicos', 'reglas_de_negocio', 'politicas_y_penalidades', mode='before')
    @classmethod
    def normalizar_items_a_strings(cls, v):
        """
        Gemini a veces devuelve arrays de dicts {tipo, detalle} en lugar de strings.
        Normaliza ambos formatos a List[str] para que el schema sea resiliente.
        """
        if not isinstance(v, list):
            return v
        resultado = []
        for item in v:
            if isinstance(item, str):
                resultado.append(item)
            elif isinstance(item, dict):
                tipo = item.get('tipo', '')
                detalle = item.get('detalle', item.get('descripcion', ''))
                if tipo and detalle:
                    resultado.append(f"{tipo}: {detalle}")
                elif detalle:
                    resultado.append(str(detalle))
                elif tipo:
                    resultado.append(str(tipo))
                else:
                    resultado.append(str(item))
            else:
                resultado.append(str(item))
        return resultado


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


class DireccionamientoHallazgo(BaseModel):
    """Un hallazgo individual de direccionamiento/corrupción."""
    categoria: Literal[
        "Técnica", "Experiencia", "Personal", "Puntaje", "Fraccionamiento", "Otra"
    ] = Field(..., description="Categoría del hallazgo")
    descripcion_hallazgo: str = Field(
        ..., min_length=10, max_length=500,
        description="Descripción del hallazgo identificado"
    )
    red_flag_detectada: str = Field(
        ..., min_length=5, max_length=300,
        description="Red flag o señal de alerta concreta"
    )
    nivel_de_gravedad: Literal["Alto", "Medio", "Bajo"] = Field(
        ..., description="Gravedad del hallazgo"
    )


class DireccionamientoAnalysisResponse(BaseModel):
    """Respuesta de análisis forense de direccionamiento en TDR."""
    score_riesgo_corrupcion: int = Field(
        ..., ge=0, le=100,
        description="Score de riesgo de corrupción (0-100)"
    )
    veredicto_flash: Literal["LIMPIO", "SOSPECHOSO", "ALTAMENTE DIRECCIONADO"] = Field(
        ..., description="Veredicto rápido del análisis"
    )
    hallazgos_criticos: List[DireccionamientoHallazgo] = Field(
        default_factory=list,
        description="Lista de hallazgos de direccionamiento"
    )
    argumento_para_observacion: str = Field(
        ..., min_length=20, max_length=2000,
        description="Texto legal/técnico para presentar observación formal"
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


class ProformaRequest(BaseModel):
    """Request para generación de proforma técnica"""
    company_name: str = Field(
        default='',
        max_length=150,
        description="Nombre de la empresa proveedora"
    )
    company_copy: str = Field(
        ...,
        min_length=20,
        max_length=4000,
        description="Descripción del rubro y experiencia de la empresa"
    )
    analisis_tdr: Optional[Dict[str, Any]] = Field(
        default=None,
        description="Análisis TDR existente (si no se envía PDF)"
    )
    contrato_contexto: Optional[Dict[str, Any]] = Field(
        default=None,
        description="Metadata del contrato (entidad, objeto, fechas)"
    )


class ProformaItem(BaseModel):
    """Ítem de la tabla de proforma técnica"""
    item: int
    descripcion: str
    unidad: str
    cantidad: float
    precio_unitario: float
    subtotal: float


class ProformaResponse(BaseModel):
    """Respuesta de generación de proforma técnica"""
    titulo_proceso: str = Field(default='', description="Nombre/descripción del proceso licitatorio")
    empresa_nombre: str = Field(default='', description="Nombre de la empresa")
    empresa_rubro: str = Field(default='', description="Rubro resumido de la empresa")
    items: List[ProformaItem] = Field(default_factory=list, description="Ítems de la tabla de cotización")
    total_estimado: str = Field(default='', description="Total estimado en soles (texto)")
    analisis_viabilidad: str = Field(default='', description="Análisis de viabilidad operativa")
    condiciones: List[str] = Field(default_factory=list, description="Condiciones y supuestos del presupuesto")
