"""
Cliente para Google Gemini API.
"""
from google import genai
from google.genai import types
from typing import Dict, List, Optional
import asyncio
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

        self.client = genai.Client(api_key=api_key)

        # Configuración de generación (JSON mode sin schema - salida estricta JSON)
        self.generation_config = types.GenerateContentConfig(
            temperature=0.2,
            top_p=0.95,
            top_k=40,
            max_output_tokens=8192,
            response_mime_type="application/json",
            system_instruction=self.SYSTEM_PROMPT,
            safety_settings=[
                types.SafetySetting(
                    category=types.HarmCategory.HARM_CATEGORY_HARASSMENT,
                    threshold=types.HarmBlockThreshold.BLOCK_NONE,
                ),
                types.SafetySetting(
                    category=types.HarmCategory.HARM_CATEGORY_HATE_SPEECH,
                    threshold=types.HarmBlockThreshold.BLOCK_NONE,
                ),
                types.SafetySetting(
                    category=types.HarmCategory.HARM_CATEGORY_SEXUALLY_EXPLICIT,
                    threshold=types.HarmBlockThreshold.BLOCK_NONE,
                ),
                types.SafetySetting(
                    category=types.HarmCategory.HARM_CATEGORY_DANGEROUS_CONTENT,
                    threshold=types.HarmBlockThreshold.BLOCK_NONE,
                ),
            ],
        )

    async def _generate_content(self, contents):
        if hasattr(self.client, "aio"):
            return await self.client.aio.models.generate_content(
                model=self.model_name,
                contents=contents,
                config=self.generation_config,
            )

        return await asyncio.to_thread(
            self.client.models.generate_content,
            model=self.model_name,
            contents=contents,
            config=self.generation_config,
        )

    @staticmethod
    def _extract_text(response) -> str:
        text = getattr(response, "text", None)
        if isinstance(text, str) and text.strip():
            return text

        try:
            candidates = response.candidates or []
            for candidate in candidates:
                parts = candidate.content.parts if candidate.content else []
                for part in parts:
                    if getattr(part, "text", None):
                        return part.text
        except Exception:
            pass

        return ""

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
Analiza este Término de Referencia del SEACE y responde únicamente con un JSON que contenga:

1. "resumen_ejecutivo": 1-2 párrafos concretos sobre alcance, objetivos y entregables.
2. "requisitos_tecnicos": Lista de requisitos accionables (certificaciones, experiencia, equipamiento). Usa [] si no hay.
3. "reglas_de_negocio": Lista de condiciones operativas (plazos, lugar, modalidad de pago, garantías). Usa [] si no hay.
4. "politicas_y_penalidades": Lista de sanciones, multas, retenciones o políticas relevantes.
5. "presupuesto_referencial": Monto textual ("S/ 120,000.00") o null.

TDR:
{context}
"""

            # Generar respuesta con JSON Schema enforced
            response = await self._generate_content(user_prompt)
            response_text = self._extract_text(response).strip()

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

            # Crear parte inline con el PDF
            pdf_part = types.Part.from_bytes(data=pdf_bytes, mime_type="application/pdf")
            self.logger.info(f"✅ PDF preparado ({len(pdf_bytes)} bytes)")

            # Prompt para análisis
            prompt = """
Analiza este TDR del SEACE (Perú) y devuelve ÚNICAMENTE un JSON con las siguientes claves:
{
    "resumen_ejecutivo": "100-200 palabras sobre objetivos y alcance",
    "requisitos_tecnicos": ["certificaciones, experiencia o equipamiento requerido"],
    "reglas_de_negocio": ["plazos, lugar de entrega, modalidad de pago, garantías"],
    "politicas_y_penalidades": ["multas, sanciones, porcentajes"],
    "presupuesto_referencial": "S/ X,XXX.XX" o null
}

Reglas:
- Si algún bloque no aparece en el PDF, devuelve [] o null.
- Máximo 10 items por lista.
- No incluyas texto fuera del JSON ni bloques ```json.
"""

            # Analizar con el PDF inline
            self.logger.info("🤖 Enviando PDF inline a Gemini...")
            response = await self._generate_content([prompt, pdf_part])

            # Parsear respuesta
            response_text = self._extract_text(response).strip()
            self.logger.debug(f"Respuesta (primeros 500 chars): {response_text[:500]}")

            result = self._parse_json_response(response_text)
            self.logger.info("✅ Análisis PDF inline completado exitosamente")

            return result

        except Exception as e:
            self.logger.error(f"❌ Error al analizar PDF con Gemini: {str(e)}")
            raise ValueError(f"Error en análisis PDF directo: {str(e)}")

    async def evaluate_compatibility(
        self,
        company_copy: str,
        analisis_tdr: Dict,
        contrato_contexto: Optional[Dict] = None,
        keywords: Optional[List[str]] = None
    ) -> Dict:
        try:
            prompt = self._build_compatibility_prompt(company_copy, analisis_tdr, contrato_contexto, keywords)
            response = await self._generate_content(prompt)
            response_text = self._extract_text(response).strip()
            self.logger.debug(f"Compatibilidad Gemini (primeros 400 chars): {response_text[:400]}")
            return self._parse_json_response(response_text)
        except Exception as e:
            self.logger.error(f"❌ Error en compatibilidad Gemini: {str(e)}")
            raise ValueError(f"Error al evaluar compatibilidad: {str(e)}")
