"""
Cliente para Anthropic Claude API.
"""
from anthropic import AsyncAnthropic
from typing import Dict, List, Optional
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
Analiza el siguiente TDR del SEACE y entrega únicamente un JSON con las claves: resumen_ejecutivo, requisitos_tecnicos, reglas_de_negocio, politicas_y_penalidades, presupuesto_referencial. Usa arrays vacíos o null cuando falte información.

TDR:
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

    async def evaluate_compatibility(
        self,
        company_copy: str,
        analisis_tdr: Dict,
        contrato_contexto: Optional[Dict] = None,
        keywords: Optional[List[str]] = None
    ) -> Dict:
        try:
            prompt = self._build_compatibility_prompt(company_copy, analisis_tdr, contrato_contexto, keywords)
            response = await self.client.messages.create(
                model=self.model_name,
                max_tokens=1024,
                temperature=0.2,
                system="Asesor en compatibilidad de TDR para proveedores SEACE",
                messages=[{"role": "user", "content": prompt}]
            )

            response_text = response.content[0].text
            self.logger.debug(f"Compatibilidad Claude (primeros 400 chars): {response_text[:400]}")
            return self._parse_json_response(response_text)
        except Exception as e:
            self.logger.error(f"Error en compatibilidad Anthropic: {str(e)}")
            raise ValueError(f"Error al evaluar compatibilidad: {str(e)}")

    async def analyze_direccionamiento(self, context: str) -> Dict:
        """Análisis forense de direccionamiento/corrupción usando Claude."""
        try:
            self.logger.info(f"🔍 Analizando direccionamiento con Anthropic ({self.model_name})")

            user_prompt = f"""
Analiza este TDR/ET del SEACE (Perú) buscando indicios de direccionamiento y corrupción.
Responde ÚNICAMENTE con un JSON que siga este esquema:
{self.FORENSIC_JSON_TEMPLATE.strip()}

Reglas ESTRICTAS:
- score_riesgo_corrupcion: entero 0-100.
- veredicto_flash: "LIMPIO" si score<30, "SOSPECHOSO" si 30-69, "ALTAMENTE DIRECCIONADO" si >=70.
- Máximo 8 hallazgos.
- Cada hallazgo DEBE usar EXACTAMENTE estos valores:
  * categoria: "Técnica" | "Experiencia" | "Personal" | "Puntaje" | "Fraccionamiento" | "Otra" (NO inventar categorías)
  * nivel_de_gravedad: "Alto" | "Medio" | "Bajo" (NO usar "Muy Alto", "Crítico" ni variantes)

TDR:
{context}

Devuelve SOLO el objeto JSON sin texto adicional.
"""
            response = await self.client.messages.create(
                model=self.model_name,
                max_tokens=4096,
                temperature=0.15,
                system=self.FORENSIC_SYSTEM_PROMPT,
                messages=[{"role": "user", "content": user_prompt}],
            )

            response_text = response.content[0].text
            return self._parse_json_response(response_text)
        except Exception as e:
            self.logger.error(f"❌ Error en direccionamiento Anthropic: {str(e)}")
            raise ValueError(f"Error al analizar direccionamiento: {str(e)}")

    async def generate_proforma(
        self,
        context: str,
        company_name: str,
        company_copy: str,
        contrato_contexto=None,
    ) -> dict:
        """Genera proforma técnica de cotización para un proceso SEACE usando Claude."""
        try:
            self.logger.info(f"📋 Generando proforma técnica con Anthropic ({self.model_name})")
            nombre_empresa = company_name.strip() or "Mi Empresa"
            entidad = contrato_contexto.get("nomEntidad", "") if contrato_contexto else ""
            objeto = (
                contrato_contexto.get("desObjetoContrato") or
                contrato_contexto.get("nomObjetoContrato", "")
            ) if contrato_contexto else ""

            user_prompt = f"""Actúa como Director de Operaciones de "{nombre_empresa}".
Especialidad: "{company_copy}"
{('Entidad: ' + entidad) if entidad else ''}
{('Objeto: ' + objeto) if objeto else ''}

Genera una PROFORMA TÉCNICA de cotización basada en el siguiente TDR.
Devuelve ÚNICAMENTE este JSON:
{{
  "titulo_proceso": "...",
  "empresa_nombre": "{nombre_empresa}",
  "empresa_rubro": "...",
  "items": [{{"item": 1, "descripcion": "...", "unidad": "...", "cantidad": 1, "precio_unitario": 0.0, "subtotal": 0.0}}],
  "total_estimado": "S/ X,XXX.XX",
  "analisis_viabilidad": "...",
  "condiciones": ["..."]
}}

TDR:\n{context}\n\nDevuelve SOLO el JSON sin texto adicional."""

            response = await self.client.messages.create(
                model=self.model_name,
                max_tokens=4096,
                temperature=0.3,
                system=self.SYSTEM_PROMPT,
                messages=[{"role": "user", "content": user_prompt}],
            )
            return self._parse_json_response(response.content[0].text)
        except Exception as e:
            self.logger.error(f"❌ Error en proforma Anthropic: {str(e)}")
            raise ValueError(f"Error al generar proforma: {str(e)}")

    # ═══════════════════════════════════════════════════════════════
    # CONTRATOS MAYORES (> 8 UIT) — Ley N° 32069
    # ═══════════════════════════════════════════════════════════════

    async def analyze_tdr_mayores(self, context: str) -> Dict:
        """Analiza Bases Estándar para procedimientos > 8 UIT bajo Ley N° 32069."""
        try:
            self.logger.info(f"Analizando TDR MAYOR con Anthropic ({self.model_name})")

            user_prompt = f"""Analiza el documento adjunto proveniente de las Bases de un procedimiento de selección 
del Estado Peruano y extrae la información requerida mapeándola estrictamente en la siguiente estructura JSON.
Si una condición no existe en el texto, emplea "null" o arreglos vacíos [].

Presta especial atención a: la naturaleza de los requisitos exigidos al personal, los montos de experiencia, 
las condiciones financieras, y la separación entre Requisitos de Calificación (Pasa/No Pasa) y Factores de Evaluación (puntaje).

Estructura Esperada (JSON):
{{
  "metadatos_proceso": {{
    "objeto_principal": "Descripción del bien, servicio u obra",
    "sistema_de_contratacion": "Suma Alzada | Precios Unitarios | Esquema Mixto | Tarifas | Honorario Fijo",
    "valor_monetario_referencial": "Monto estimado si está disponible, o null",
    "modalidad_inferida": "Licitación Pública | Concurso Público | Adjudicación Simplificada | Comparación de Precios | Subasta Inversa"
  }},
  "requisitos_admisibilidad_y_calificacion": {{
    "habilitaciones_legales_obligatorias": ["Ej: Registro MTC, Licencia SUCAMEC, etc."],
    "equipamiento_infraestructura": ["Ej: Camionetas 4x4, servidores, plantas de asfalto, etc."],
    "experiencia_financiera_postor": "Monto requerido en facturación y horizonte temporal (Ej: 1.5x valor referencial, últimos 8 años)",
    "perfil_personal_clave": [
      {{
        "cargo": "Nombre del cargo requerido",
        "formacion_academica": "Título, colegiatura, especialidad requerida",
        "experiencia_especifica_obligatoria": "Años y tipo de experiencia exigida"
      }}
    ]
  }},
  "factores_puntaje_evaluacion": [
    {{
      "factor_nombre": "Ej: Precio, Experiencia Adicional, Sostenibilidad, ISO, Mejoras",
      "puntaje_maximo_asignado": 0,
      "criterio_evaluacion": "Cómo el proveedor obtiene los puntos"
    }}
  ],
  "parametros_consorcio": {{
    "permite_consorcio": true,
    "limite_maximo_integrantes": 0,
    "porcentaje_minimo_individual": "porcentaje o null",
    "porcentaje_minimo_mayor_experiencia": "porcentaje o null"
  }},
  "garantias_y_penalidades": {{
    "porcentaje_garantia_fiel_cumplimiento": "Generalmente 10% del contrato, o null",
    "permite_retencion_mype": true,
    "penalidad_mora_tope_maximo": "Porcentaje máximo por retraso, usualmente 10%",
    "otras_penalidades_tope": "Porcentaje máximo acumulado de otras penalidades, usualmente 10%",
    "plazo_estimado_ejecucion": "Días calendario o null"
  }}
}}

Reglas:
- NO fusiones requisitos de calificación con factores de evaluación. Son categorías jurídicas distintas.
- Si el valor referencial está oculto (reservado por ley), usa null.
- Si las bases no mencionan consorcio, asume que sí está permitido sin restricciones especiales.
- Identifica correctamente el sistema de contratación (Suma Alzada vs Precios Unitarios).

Documento a evaluar:
{context}

Devuelve SOLO el JSON, sin markdown ni texto adicional."""

            response = await self.client.messages.create(
                model=self.model_name,
                max_tokens=4096,
                temperature=0.15,
                system=self.SYSTEM_PROMPT_MAYORES,
                messages=[{"role": "user", "content": user_prompt}],
            )

            return self._parse_json_response(response.content[0].text)

        except Exception as e:
            self.logger.error(f"Error en analyze_tdr_mayores Anthropic: {str(e)}")
            raise ValueError(f"Error al analizar TDR mayor: {str(e)}")

    async def analyze_direccionamiento_mayores(self, context: str) -> Dict:
        """Auditoría forense avanzada para contratos > 8 UIT bajo Ley N° 32069."""
        try:
            self.logger.info(f"Auditando direccionamiento MAYOR con Anthropic ({self.model_name})")

            user_prompt = f"""Realiza un escrutinio forense exhaustivo del siguiente extracto de Bases Estándar 
en busca de indicios de sobrerregulación estratégica, direccionamiento o barreras arbitrarias 
a la competencia institucional, bajo la Ley N° 32069.

Genera ÚNICAMENTE un documento JSON válido de acuerdo a las siguientes reglas estrictas.

CRITERIOS DE PUNTUACIÓN ACUMULATIVA:
- Exigir marca/patente sin "o su equivalente": +40 pts
- Visita técnica obligatoria con sello/firma excluyente en fecha única: +50 pts
- Puntaje excesivo a certificaciones ISO irrelevantes para el objeto: +25 pts
- Restricción drástica de consorcios (prohibir o % mínimo > 50%): +20 pts
- Plazos de ejecución/entrega irrazonablemente cortos: +30 pts
- Experiencia en condiciones geográficas idénticas sin justificación técnica: +20 pts

CLASIFICACIONES DE RIESGO (usar EXACTAMENTE estas):
"Lock_Out_Procedimental" | "Brand_Directing_Ilegal" | "Sesgo_Evaluacion_Subjetivo" | "Experiencia_Irracional" | "Limitacion_Consorcial_Injustificada"

ESTRUCTURA REQUERIDA:
{{
  "score_probabilidad_direccionamiento": 0,
  "estado_proceso": "CONFORME Y LIMPIO",
  "fundamento_analitico_general": "Breve sinopsis de la sanidad del proceso bajo el principio de Valor por Dinero.",
  "anomalias_detectadas": [
    {{
      "clasificacion_riesgo": "Lock_Out_Procedimental",
      "nivel_impacto": "Critico",
      "extracto_base_sospechoso": "Cita textual del requisito cuestionable",
      "analisis_proporcionalidad": "Explicación técnica de por qué vulnera la competencia o la normativa.",
      "argumento_legal_observacion": "Redacción formal que el proveedor puede usar para observar este punto ante el Comité de Selección, citando el principio vulnerado (ej. ilegalidad de visitas obligatorias con sello único según el OECE)."
    }}
  ]
}}

REGLAS:
- score_probabilidad_direccionamiento: entero 0-100 calculado con los criterios de puntuación.
- estado_proceso: "CONFORME Y LIMPIO" si 0-25, "RIESGO MODERADO" si 26-65, "EVIDENCIA CLARA DE DIRECCIONAMIENTO" si 66-100.
- nivel_impacto: SOLO "Critico" | "Alto" | "Medio" | "Bajo".
- Cada anomalía debe incluir un argumento_legal_observacion redactado en formato formal listo para presentar.
- Si no hay anomalías (score ≤ 25), devuelve array vacío en anomalias_detectadas.

Documento a auditar:
{context}

Devuelve SOLO el JSON, sin markdown ni texto adicional."""

            response = await self.client.messages.create(
                model=self.model_name,
                max_tokens=4096,
                temperature=0.1,
                system=self.FORENSIC_SYSTEM_PROMPT_MAYORES,
                messages=[{"role": "user", "content": user_prompt}],
            )

            return self._parse_json_response(response.content[0].text)

        except Exception as e:
            self.logger.error(f"Error en direccionamiento_mayores Anthropic: {str(e)}")
            raise ValueError(f"Error al auditar direccionamiento mayor: {str(e)}")

    async def generate_proforma_mayores(
        self,
        context: str,
        company_name: str,
        company_copy: str,
        contrato_contexto: Optional[Dict] = None,
    ) -> Dict:
        """Genera proforma técnica para procedimientos competitivos (> 8 UIT) bajo Ley N° 32069."""
        try:
            self.logger.info(f"Generando proforma MAYOR con Anthropic ({self.model_name})")
            nombre_empresa = company_name.strip() or "Mi Empresa"
            entidad = contrato_contexto.get("nomEntidad", "") if contrato_contexto else ""
            objeto = (
                contrato_contexto.get("desObjetoContrato") or
                contrato_contexto.get("nomObjetoContrato", "")
            ) if contrato_contexto else ""

            user_prompt = f"""Actúa como Director de Operaciones de "{nombre_empresa}", especialista en contrataciones 
públicas peruanas bajo la Ley N° 32069.
Especialidad: "{company_copy}"
{('Entidad convocante: ' + entidad) if entidad else ''}
{('Objeto del proceso: ' + objeto) if objeto else ''}

Basándote en el siguiente documento de Bases Estándar, genera una PROFORMA TÉCNICA DE COTIZACIÓN 
para un procedimiento de selección competitivo. Debes reflejar la estructura de costos exigida 
por las Bases y la normativa de contrataciones.

REGLAS:
- Los precios deben ser realistas para el mercado peruano (incluye IGV donde aplique).
- Estructura la proforma con: costo directo, gastos generales, utilidad e IGV cuando sea posible.
- Si el TDR no especifica cantidades exactas, estímalas razonablemente.
- Advierte sobre las garantías requeridas y su impacto financiero.

ADVERTENCIAS OBLIGATORIAS EN EL ANÁLISIS DE VIABILIDAD:
- Si se exige Garantía de Fiel Cumplimiento (10% del contrato), calcula el monto.
- Si la entidad no menciona expresamente la retención MYPE, advierte que se necesitará carta fianza.
- Si hay penalidades por mora (> 0%), advierte el impacto en caso de retraso.
- Evalúa el plazo de ejecución vs. la capacidad operativa estándar del mercado.

Devuelve ÚNICAMENTE este JSON:
{{
  "titulo_proceso": "Descripción corta (máx 80 chars)",
  "empresa_nombre": "{nombre_empresa}",
  "empresa_rubro": "Rubro resumido en 1 línea",
  "items": [
    {{
      "item": 1,
      "descripcion": "Descripción del ítem",
      "unidad": "Und | Servicio | Mes | Global",
      "cantidad": 1,
      "precio_unitario": 0.0,
      "subtotal": 0.0
    }}
  ],
  "estructura_costos": {{
    "costo_directo": "S/ X,XXX.XX",
    "gastos_generales": "S/ X,XXX.XX",
    "utilidad": "S/ X,XXX.XX",
    "igv": "S/ X,XXX.XX"
  }},
  "total_estimado": "S/ X,XXX.XX",
  "analisis_viabilidad": "Análisis detallado de viabilidad operativa y financiera (3-4 párrafos) incluyendo advertencias sobre garantías y flujo de caja.",
  "advertencias_financieras": [
    "Garantía de Fiel Cumplimiento: X% del contrato = S/ X,XXX.XX (requiere carta fianza o retención MYPE)",
    "Penalidad por mora: X% diario hasta máximo X%"
  ],
  "condiciones": ["Supuesto 1", "Supuesto 2"],
  "recomendacion_consorcio": "Sugerencia sobre si conviene participar en consorcio dada la envergadura del contrato"
}}

TDR / Bases Estándar:
{context}

Devuelve SOLO el JSON sin markdown ni texto adicional."""

            response = await self.client.messages.create(
                model=self.model_name,
                max_tokens=4096,
                temperature=0.3,
                system=self.PROFORMA_SYSTEM_PROMPT_MAYORES,
                messages=[{"role": "user", "content": user_prompt}],
            )

            return self._parse_json_response(response.content[0].text)

        except Exception as e:
            self.logger.error(f"Error en proforma_mayores Anthropic: {str(e)}")
            raise ValueError(f"Error al generar proforma mayor: {str(e)}")
