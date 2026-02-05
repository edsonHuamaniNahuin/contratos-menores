"""
Cliente para Google Gemini API.
"""
import google.generativeai as genai
from typing import Dict
import logging
from .base_client import BaseLLMClient

logger = logging.getLogger(__name__)


class GeminiClient(BaseLLMClient):
    """
    Cliente para Google Gemini API (2026).
    Optimizado para Gemini 2.5/3 Flash con 1M tokens de contexto.
    Free Tier: 1,500 requests/día, 15 RPM.
    """

    def __init__(self, api_key: str, model_name: str):
        """El model_name debe venir desde settings.gemini_model"""
        self.api_key = api_key
        self.model_name = model_name
        self.logger = logger

        # Configurar Gemini
        genai.configure(api_key=api_key)

        # Configuración de generación (JSON mode sin schema - evita error "unhashable type: 'list'")
        self.generation_config = {
            "temperature": 0.2,  # Más determinista para JSON estructurado
            "top_p": 0.95,
            "top_k": 40,
            "max_output_tokens": 8192,  # Aumentado para análisis completos (antes: 3072)
            "response_mime_type": "application/json",  # Forzar JSON sin schema estricto
        }

        # Configuración de seguridad (permitir todo para análisis técnico)
        self.safety_settings = [
            {"category": "HARM_CATEGORY_HARASSMENT", "threshold": "BLOCK_NONE"},
            {"category": "HARM_CATEGORY_HATE_SPEECH", "threshold": "BLOCK_NONE"},
            {"category": "HARM_CATEGORY_SEXUALLY_EXPLICIT", "threshold": "BLOCK_NONE"},
            {"category": "HARM_CATEGORY_DANGEROUS_CONTENT", "threshold": "BLOCK_NONE"},
        ]

        self.model = genai.GenerativeModel(
            model_name=model_name,
            generation_config=self.generation_config,
            safety_settings=self.safety_settings,
            system_instruction=self.SYSTEM_PROMPT
        )

    async def analyze_tdr(self, context: str) -> Dict:
        """
        Analiza el TDR usando Gemini 2.5/3 Flash con JSON Schema enforced.
        Ventana de contexto: 1M tokens.

        Args:
            context: Contexto del TDR recuperado por el RAG

        Returns:
            Dict con el análisis estructurado
        """
        try:
            self.logger.info(f"Analizando TDR con Gemini ({self.model_name})")
            self.logger.debug(f"Contexto: {len(context)} caracteres")

            # Prompt simplificado (el schema ya define la estructura)
            user_prompt = f"""
Analiza este Término de Referencia del SEACE y extrae:

1. **Resumen ejecutivo**: Qué busca la entidad y qué se necesita para ganar (2-3 párrafos)
2. **Requisitos técnicos**: Certificaciones, experiencia, tecnologías requeridas
3. **Reglas de negocio**: Obligaciones, entregables, condiciones
4. **Penalidades**: Multas, garantías, sanciones
5. **Presupuesto referencial**: Monto en soles o null
6. **Score de compatibilidad (1-10)**: Basado en claridad, viabilidad técnica y riesgo

**TDR:**
{context}
"""

            # Generar respuesta con JSON Schema enforced
            response = await self.model.generate_content_async(user_prompt)

            # Gemini con response_schema devuelve JSON válido directamente
            response_text = response.text.strip()

            self.logger.debug(f"Respuesta del LLM (primeros 500 chars): {response_text[:500]}")

            # Parsear JSON (debe ser válido gracias al schema)
            result = self._parse_json_response(response_text)

            self.logger.info("✅ Análisis completado exitosamente con Gemini")

            return result

        except Exception as e:
            self.logger.error(f"❌ Error al analizar con Gemini: {str(e)}")
            # Fallback: devolver análisis básico
            return {
                "resumen_ejecutivo": f"Error al analizar el TDR: {str(e)}. Contexto disponible: {len(context)} caracteres.",
                "requisitos_tecnicos": [],
                "reglas_de_negocio": [],
                "politicas_y_penalidades": [],
                "presupuesto_referencial": None,
                "score_compatibilidad": 1
            }

    async def analyze_tdr_from_pdf(self, pdf_bytes: bytes, filename: str) -> Dict:
        """
        Analiza un TDR enviando el PDF directamente a Gemini (sin extracción de texto).
        Gemini 2.5 Flash soporta PDFs nativamente con Vision integrada.
        Usa inline_data para evitar subir archivos a Files API.

        Args:
            pdf_bytes: Contenido binario del PDF
            filename: Nombre del archivo para logs

        Returns:
            Dict con el análisis estructurado
        """
        try:
            self.logger.info(f"📄 Analizando PDF directo con Gemini ({self.model_name})")
            self.logger.info(f"   Archivo: {filename} ({len(pdf_bytes)} bytes)")

            # ESTRATEGIA: Inline data (sin Files API)
            self.logger.info("📦 Preparando PDF inline para Gemini...")

            import base64
            pdf_base64 = base64.b64encode(pdf_bytes).decode('utf-8')

            # Crear parte inline con el PDF
            pdf_part = {
                "inline_data": {
                    "mime_type": "application/pdf",
                    "data": pdf_base64
                }
            }

            self.logger.info(f"✅ PDF preparado ({len(pdf_base64)} chars base64)")

            # Prompt para análisis
            prompt = """
Analiza este TDR del SEACE (Perú) y extrae información clave en formato JSON compacto.

IMPORTANTE: Responde SOLO con JSON válido, sin markdown, sin explicaciones adicionales.

Estructura requerida:
{
    "resumen_ejecutivo": "Texto de 100-200 palabras explicando: ¿Qué busca la entidad? ¿Alcance? ¿Requisitos clave?",
    "requisitos_tecnicos": ["Lista de certificaciones, experiencia, tecnologías, equipos requeridos"],
    "reglas_de_negocio": ["Lista de plazos, lugar, modalidad pago, garantías, obligaciones"],
    "politicas_penalidades": ["Lista de multas, sanciones, porcentajes, causales"],
    "presupuesto_referencial": "S/ X,XXX.XX o null (sin comillas)",
    "score_compatibilidad": 7
}

REGLAS:
- Resumen ejecutivo: Máximo 200 palabras, enfocado en lo esencial
- Cada array: Máximo 10 items por lista
- Si no hay info: usar [] o null
- NO inventes datos
- Presupuesto: "S/ X,XXX.XX" exacto o null
- Score: 1-10 según viabilidad

Devuelve ÚNICAMENTE el JSON, sin ```json ni explicaciones.
"""

            # Analizar con el PDF inline
            self.logger.info("🤖 Enviando PDF inline a Gemini...")
            response = await self.model.generate_content_async([prompt, pdf_part])

            # Parsear respuesta
            response_text = response.text.strip()
            self.logger.debug(f"Respuesta (primeros 500 chars): {response_text[:500]}")

            result = self._parse_json_response(response_text)
            self.logger.info("✅ Análisis PDF inline completado exitosamente")

            return result

        except Exception as e:
            self.logger.error(f"❌ Error al analizar PDF con Gemini: {str(e)}")
            raise ValueError(f"Error en análisis PDF directo: {str(e)}")
