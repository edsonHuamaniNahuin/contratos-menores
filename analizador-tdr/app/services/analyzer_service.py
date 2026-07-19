"""
Orquestador principal del pipeline RAG de análisis de TDRs.
Coordina PDF Processor → RAG Extractor → LLM Client.
"""
from typing import Dict, Optional, Literal
from app.services.pdf_processor import PDFProcessorService
from app.services.rag_extractor import RAGExtractionService
from app.services.llm import LLMFactory, BaseLLMClient
from app.models.schemas import (
    TDRAnalysisResponse,
    CompatibilityScoreRequest,
    CompatibilityScoreResponse,
    DireccionamientoAnalysisResponse,
    ProformaResponse,
    ProformaItem,
)
from datetime import datetime
import fitz
import io
import logging

logger = logging.getLogger(__name__)
MAX_RESUMEN_LENGTH = 1000
MIN_CHARS_PER_PAGE = 200


class TDRAnalyzerService:
    """
    Servicio principal que orquesta el pipeline completo de análisis de TDR.
    """

    def __init__(self):
        self.pdf_processor = PDFProcessorService()
        self.rag_extractor = RAGExtractionService()
        self.logger = logger
        self.last_token_usage: Dict = {}

    async def analyze_tdr_document(
        self,
        pdf_bytes: bytes,
        llm_provider: Optional[Literal["gemini", "openai", "anthropic"]] = None,
        tipo_contrato: str = "menores",
        filename: str = "document.pdf",
    ) -> TDRAnalysisResponse:
        """
        Pipeline completo de análisis de TDR.

        Flujo:
        1. Extrae texto del PDF
        2. Recupera fragmentos relevantes (RAG)
        3. Construye contexto para el LLM
        4. Envía al LLM para análisis estructurado
        5. Valida la respuesta con Pydantic

        Args:
            pdf_bytes: Contenido binario del PDF
            llm_provider: Proveedor LLM a usar (opcional)

        Returns:
            TDRAnalysisResponse: Análisis estructurado validado

        Raises:
            ValueError: Si hay errores en el procesamiento o validación
        """
        self.logger.info("=== INICIANDO PIPELINE DE ANÁLISIS DE TDR (EXTRACCIÓN INTELIGENTE) ===")

        llm_client = LLMFactory.create_client(llm_provider)

        # ── Reconocimiento de formato por extensión ──────────────────────────
        ext = filename.rsplit('.', 1)[-1].lower() if '.' in filename else ''
        
        # DOCX/DOC: extraer texto y analizar como texto (Gemini multimodal no soporta DOCX)
        if ext in ('docx', 'doc'):
            self.logger.info(f"📝 Word (.{ext}) — extrayendo texto...")
            docx_text = self._extract_docx_text(pdf_bytes)
            if not docx_text:
                raise ValueError("No se pudo extraer texto del documento Word. El archivo podría estar corrupto o protegido.")
            
            self.logger.info(f"✓ DOCX: {len(docx_text)} chars extraídos")
            es_mayor = (tipo_contrato == "mayores")
            
            if len(docx_text) < 5000:
                context = f"DOCUMENTO COMPLETO DEL TDR:\n\n{docx_text}\n\n===== FIN DEL DOCUMENTO ====="
            else:
                fragments = self.rag_extractor.extract_relevant_fragments(docx_text)
                context = self.rag_extractor.build_context_for_llm(fragments)
                self.logger.info(f"✓ RAG: {len(context)} chars")
            
            self.logger.info(f"Paso 4/4: Analizando con LLM (tipo: {tipo_contrato})...")
            if es_mayor:
                if hasattr(llm_client, 'analyze_tdr_mayores') and type(llm_client).analyze_tdr_mayores != BaseLLMClient.analyze_tdr_mayores:
                    analysis_dict = await llm_client.analyze_tdr_mayores(context)
                else:
                    mayores_prefix = """[INSTRUCCION: Contrato Mayor (>8 UIT) bajo Ley N 32069. DEBES devolver UNICAMENTE un JSON con metadatos_proceso, requisitos_admisibilidad_y_calificacion, factores_puntaje_evaluacion, parametros_consorcio, garantias_y_penalidades.]\n\n"""
                    analysis_dict = await llm_client.analyze_tdr(mayores_prefix + context)
            else:
                analysis_dict = await llm_client.analyze_tdr(context)
            self.last_token_usage = analysis_dict.pop('_token_usage', {})
            return analysis_dict if es_mayor else self._validate_response(self._sanitize_llm_payload(analysis_dict))

        # Paso 1: Detección ultrarrápida — PyMuPDF directo, cero OCR (~0.3s)
        self.logger.info("Paso 1/4: Detectando tipo de PDF (nativo vs escaneado)...")
        detect_text = self._extract_native_text(pdf_bytes)
        num_pages = self._get_page_count(pdf_bytes)

        # PyMuPDF no pudo leer el archivo (DOCX, etc.) → extraer texto de DOCX o markdown
        if num_pages == 0 and len(detect_text) < 50:
            docx_text = self._extract_docx_text(pdf_bytes)
            if docx_text:
                self.logger.info(f"📝 Documento no-PDF detectado (DOCX/Word) — {len(docx_text)} chars extraídos")
                context = f"DOCUMENTO COMPLETO DEL TDR:\n\n{docx_text}\n\n===== FIN DEL DOCUMENTO ====="
                self.logger.info(f"✓ Contexto preparado: {len(context)} caracteres")
                self.logger.info(f"Paso 4/4: Analizando con LLM (provider: {llm_provider or 'default'}, tipo: {tipo_contrato})...")
                # Para DOCX, usar el prompt textual de Mayores o Menores según corresponda
                if es_mayor:
                    if hasattr(llm_client, 'analyze_tdr_mayores') and type(llm_client).analyze_tdr_mayores != BaseLLMClient.analyze_tdr_mayores:
                        analysis_dict = await llm_client.analyze_tdr_mayores(context)
                    else:
                        # Inyectar instrucción Mayores
                        mayores_prefix = """[INSTRUCCIÓN: Contrato Mayor (>8 UIT) bajo Ley N° 32069. DEBES devolver ÚNICAMENTE un JSON con metadatos_proceso, requisitos_admisibilidad_y_calificacion, factores_puntaje_evaluacion, parametros_consorcio, garantias_y_penalidades.]\n\n"""
                        analysis_dict = await llm_client.analyze_tdr(mayores_prefix + context)
                else:
                    analysis_dict = await llm_client.analyze_tdr(context)
                self.last_token_usage = analysis_dict.pop('_token_usage', {})
                return analysis_dict if es_mayor else self._validate_response(self._sanitize_llm_payload(analysis_dict))
            else:
                # Ni PDF ni DOCX — intentar multimodal de todas formas (podría ser PDF corrupto)
                pass

        chars_per_page = len(detect_text) / max(num_pages, 1)

        self.logger.info(
            f"✓ Detección: {len(detect_text)} chars, "
            f"{num_pages} págs, {chars_per_page:.0f} chars/pág"
        )

        # ── Router: Contratos Mayores vs Menores ──────────────────────────────
        es_mayor = (tipo_contrato == "mayores")

        # ── Estrategia híbrida ─────────────────────────────────────────────
        # PDF escaneado (<200 chars/pág) O PDF grande (>50 págs): multimodal directo
        #   Salta extract_text_from_pdf, RAG y Tesseract por completo.
        #   Gemini procesa el PDF visualmente en ~30s constante.
        pdf_muy_grande = num_pages > 20
        use_multimodal = (
            (chars_per_page < MIN_CHARS_PER_PAGE or pdf_muy_grande)
            and hasattr(llm_client, 'analyze_tdr_from_pdf')
        )

        if use_multimodal:
            self.logger.info(
                f"📸 PDF escaneado ({chars_per_page:.0f} chars/pág) "
                f"— multimodal directo, sin OCR, sin Tesseract..."
            )
            analysis_dict = await llm_client.analyze_tdr_from_pdf(pdf_bytes, "document.pdf")
            self.last_token_usage = analysis_dict.pop('_token_usage', {})
            # Para Mayores, el resultado multimodal viene en formato Menores — es aceptable
            if es_mayor:
                return analysis_dict
            analysis_dict = self._sanitize_llm_payload(analysis_dict)
            return self._validate_response(analysis_dict)

        # ── Camino texto nativo ────────────────────────────────────────────
        # Solo llegamos aquí si el PDF tiene texto seleccionable.
        # Ejecutar pipeline completo (texto + tablas). OCR desactivado via env.
        self.logger.info("📄 PDF nativo — extrayendo texto + tablas (sin OCR)...")
        full_text = self.pdf_processor.extract_text_from_pdf(pdf_bytes)

        if len(full_text) < 100:
            raise ValueError("El PDF contiene muy poco texto para analizar")

        self.logger.info(f"✓ Texto completo: {len(full_text)} chars")

        # Paso 2 y 3: Construir contexto para el LLM
        if len(full_text) < 5000:
            self.logger.info("⚡ Documento pequeño detectado, enviando texto completo al LLM (sin RAG)...")
            context = f"""DOCUMENTO COMPLETO DEL TDR:

{full_text}

===== FIN DEL DOCUMENTO ====="""
            self.logger.info(f"✓ Contexto completo preparado: {len(context)} caracteres")
        else:
            # Paso 2: Recuperar fragmentos relevantes (RAG) - método SÍNCRONO
            self.logger.info("Paso 2/4: Recuperando fragmentos relevantes (RAG)...")
            fragments = self.rag_extractor.extract_relevant_fragments(full_text)

            # Verificar que se recuperaron fragmentos
            total_fragments = sum(len(chunks) for chunks in fragments.values())
            self.logger.info(f"✓ Fragmentos recuperados: {total_fragments} chunks")

            # Paso 3: Construir contexto para el LLM - método SÍNCRONO
            self.logger.info("Paso 3/4: Construyendo contexto para el LLM...")
            context = self.rag_extractor.build_context_for_llm(fragments)
            self.logger.info(f"✓ Contexto construido: {len(context)} caracteres")

        # Paso 4: Analizar con el LLM usando texto enriquecido
        self.logger.info(f"Paso 4/4: Analizando con LLM (provider: {llm_provider or 'default'}, tipo: {tipo_contrato})...")
        if es_mayor:
            # Para Mayores, si el cliente tiene método especializado, usarlo.
            # Si no (Gemini, OpenAI), inyectar instrucción Mayores en el contexto.
            if hasattr(llm_client, 'analyze_tdr_mayores') and type(llm_client).analyze_tdr_mayores != BaseLLMClient.analyze_tdr_mayores:
                analysis_dict = await llm_client.analyze_tdr_mayores(context)
            else:
                # Gemini/OpenAI: envolver contexto con instrucciones Mayores
                mayores_prefix = """[INSTRUCCIÓN: Contrato Mayor (>8 UIT) bajo Ley N° 32069.
DEBES devolver ÚNICAMENTE un JSON con esta estructura (no uses el formato de contratos menores):
{
  "metadatos_proceso": {"objeto_principal": "...", "sistema_de_contratacion": "...", "valor_monetario_referencial": "...", "modalidad_inferida": "..."},
  "requisitos_admisibilidad_y_calificacion": {"habilitaciones_legales_obligatorias": [], "equipamiento_infraestructura": [], "experiencia_financiera_postor": "...", "perfil_personal_clave": [{"cargo": "...", "formacion_academica": "...", "experiencia_especifica_obligatoria": "..."}]},
  "factores_puntaje_evaluacion": [{"factor_nombre": "...", "puntaje_maximo_asignado": 0, "criterio_evaluacion": "..."}],
  "parametros_consorcio": {"permite_consorcio": true, "limite_maximo_integrantes": 0, "porcentaje_minimo_individual": "..."},
  "garantias_y_penalidades": {"porcentaje_garantia_fiel_cumplimiento": "...", "permite_retencion_mype": true, "penalidad_mora_tope_maximo": "...", "otras_penalidades_tope": "..."}
}
Separa estrictamente Requisitos de Calificación (Pasa/No Pasa) de Factores de Evaluación (puntaje 0-100).
]\n\n"""
                context = mayores_prefix + context
                analysis_dict = await llm_client.analyze_tdr(context)
        else:
            analysis_dict = await llm_client.analyze_tdr(context)

        # Extraer token usage antes de validar con Pydantic
        self.last_token_usage = analysis_dict.pop('_token_usage', {})

        # Contratos Mayores: devolver raw dict (esquema diferente a Menores)
        if es_mayor:
            return analysis_dict

        # Asegurar que el payload cumpla con límites antes de validar
        analysis_dict = self._sanitize_llm_payload(analysis_dict)

        # Detectar respuesta vacía: el LLM no encontró contenido analizable
        # (PDF de solo imágenes sin OCR, documento corrupto, etc.)
        resumen = analysis_dict.get('resumen_ejecutivo')
        requisitos = analysis_dict.get('requisitos_tecnicos', [])
        reglas = analysis_dict.get('reglas_de_negocio', [])
        penalidades = analysis_dict.get('politicas_y_penalidades', [])
        is_empty_response = (
            not resumen
            and not requisitos
            and not reglas
            and not penalidades
        )
        if is_empty_response:
            # Fallback: si el LLM textual no extrajo nada, reintentar con multimodal
            if hasattr(llm_client, 'analyze_tdr_from_pdf'):
                self.logger.warning(
                    "⚠️ Respuesta vacía del LLM textual — reintentando con PDF directo (multimodal)..."
                )
                analysis_dict = await llm_client.analyze_tdr_from_pdf(pdf_bytes, "fallback.pdf")
                self.last_token_usage = analysis_dict.pop('_token_usage', {})
                analysis_dict = self._sanitize_llm_payload(analysis_dict)
                return self._validate_response(analysis_dict)

            self.logger.error("El LLM no pudo extraer contenido — PDF posiblemente escaneado sin OCR disponible")
            raise ValueError(
                "poco texto extraíble: el documento parece ser un PDF de imágenes escaneadas. "
                "No se pudo extraer contenido analizable. Intente con un PDF que contenga texto digital."
            )

        return self._validate_response(analysis_dict)

    async def evaluate_compatibility(
        self,
        request: CompatibilityScoreRequest,
        llm_provider: Optional[Literal["gemini", "openai", "anthropic"]] = None
    ) -> CompatibilityScoreResponse:
        """Evalúa la compatibilidad usando el análisis existente y el copy del suscriptor."""
        if not request.company_copy.strip():
            raise ValueError("El copy del suscriptor es obligatorio para evaluar compatibilidad")

        llm_client = LLMFactory.create_client(llm_provider)
        raw_response = await llm_client.evaluate_compatibility(
            request.company_copy,
            request.analisis_tdr,
            request.contrato_contexto,
            request.keywords,
        )

        sanitized = self._sanitize_compatibility_payload(raw_response)
        return CompatibilityScoreResponse(**sanitized)

    def _sanitize_llm_payload(self, analysis: Dict) -> Dict:
        """Ajusta el payload devuelto por el LLM para cumplir con los límites del esquema."""
        resumen = analysis.get("resumen_ejecutivo")

        if isinstance(resumen, str):
            resumen_limpio = resumen.strip()

            if len(resumen_limpio) > MAX_RESUMEN_LENGTH:
                self.logger.warning(
                    "Resumen excede %s caracteres; se truncará antes de validar.",
                    MAX_RESUMEN_LENGTH
                )
                resumen_limpio = resumen_limpio[:MAX_RESUMEN_LENGTH].rstrip()

            analysis["resumen_ejecutivo"] = resumen_limpio

        return analysis

    def _validate_response(self, analysis_dict: Dict) -> TDRAnalysisResponse:
        """Valida la respuesta del LLM con Pydantic."""
        try:
            validated = TDRAnalysisResponse(**analysis_dict)
            self.logger.info("✓ Análisis completado y validado exitosamente")
            self.logger.info(f"  - Requisitos técnicos: {len(validated.requisitos_tecnicos)}")
            self.logger.info(f"  - Reglas de negocio: {len(validated.reglas_de_negocio)}")
            return validated
        except Exception as e:
            self.logger.error(f"Error al validar respuesta del LLM: {str(e)}")
            self.logger.error(f"Respuesta recibida: {analysis_dict}")
            raise ValueError(f"La respuesta del LLM no cumple con el esquema esperado: {str(e)}")

    @staticmethod
    def _extract_native_text(pdf_bytes: bytes) -> str:
        """Extrae solo texto nativo del PDF via PyMuPDF (cero OCR, ~0.3s).
        Usado para detectar si el PDF es escaneado sin invocar Tesseract.
        """
        try:
            doc = fitz.open(stream=io.BytesIO(pdf_bytes), filetype="pdf")
            text = "\n".join(page.get_text() for page in doc)
            doc.close()
            return text
        except Exception:
            return ""

    @staticmethod
    def _get_page_count(pdf_bytes: bytes) -> int:
        """Obtiene el número de páginas del PDF usando PyMuPDF."""
        try:
            doc = fitz.open(stream=io.BytesIO(pdf_bytes), filetype="pdf")
            count = len(doc)
            doc.close()
            return count
        except Exception:
            return 1

    def _sanitize_compatibility_payload(self, payload: Dict) -> Dict:
        score = payload.get("score")
        try:
            payload["score"] = max(0.0, min(10.0, float(score)))
        except (TypeError, ValueError):
            payload["score"] = 0.0

        nivel = (payload.get("nivel") or "").lower()
        if nivel not in {"apto", "revisar", "descartar"}:
            if payload["score"] >= 8:
                nivel = "apto"
            elif payload["score"] >= 5:
                nivel = "revisar"
            else:
                nivel = "descartar"
        payload["nivel"] = nivel

        for key in ("factores_clave", "riesgos"):
            value = payload.get(key)
            if not isinstance(value, list):
                payload[key] = []

        if not payload.get("explicacion"):
            payload["explicacion"] = "Sin explicación proporcionada por el modelo."

        if not payload.get("timestamp"):
            payload["timestamp"] = datetime.utcnow()

        return payload

    async def analyze_direccionamiento_document(
        self,
        pdf_bytes: bytes,
        llm_provider: Optional[Literal["gemini", "openai", "anthropic"]] = None,
        tipo_contrato: str = "menores",
    ) -> DireccionamientoAnalysisResponse:
        """
        Pipeline de análisis forense de direccionamiento.
        Estrategia híbrida: multimodal para escaneados, texto+RAG para nativos.
        """
        self.logger.info("=== INICIANDO PIPELINE DE DIRECCIONAMIENTO ===")

        llm_client = LLMFactory.create_client(llm_provider)

        # Paso 1: Detección ultrarrápida — PyMuPDF directo (~0.3s)
        self.logger.info("Paso 1: Detectando tipo de PDF (nativo vs escaneado)...")
        detect_text = self._extract_native_text(pdf_bytes)
        num_pages = self._get_page_count(pdf_bytes)
        chars_per_page = len(detect_text) / max(num_pages, 1)

        self.logger.info(
            f"✓ Detección: {len(detect_text)} chars, "
            f"{num_pages} págs, {chars_per_page:.0f} chars/pág"
        )

        # ── Router: Contratos Mayores vs Menores ──────────────────────────────
        es_mayor = (tipo_contrato == "mayores")

        # ── Estrategia híbrida ─────────────────────────────────────────────
        use_multimodal = (
            (chars_per_page < MIN_CHARS_PER_PAGE or num_pages > 20)
            and hasattr(llm_client, 'analyze_direccionamiento_from_pdf')
        )

        if use_multimodal:
            self.logger.info(
                f"📸 PDF escaneado ({chars_per_page:.0f} chars/pág) "
                f"— direccionamiento multimodal directo, sin OCR..."
            )
            analysis_dict = await llm_client.analyze_direccionamiento_from_pdf(pdf_bytes, "document.pdf")
            self.last_token_usage = analysis_dict.pop('_token_usage', {})
            analysis_dict = self._sanitize_direccionamiento_payload(analysis_dict)
            try:
                validated = DireccionamientoAnalysisResponse(**analysis_dict)
                self.logger.info(f"✅ Direccionamiento multimodal — Score: {validated.score_riesgo_corrupcion}, Veredicto: {validated.veredicto_flash}")
                return validated
            except Exception as e:
                self.logger.error(f"Error al validar respuesta de direccionamiento: {str(e)}")
                raise ValueError(f"Respuesta del LLM no cumple esquema: {str(e)}")

        # ── Camino texto nativo ────────────────────────────────────────────
        self.logger.info("📄 PDF nativo — extrayendo texto + tablas (sin OCR)...")
        full_text = self.pdf_processor.extract_text_from_pdf(pdf_bytes)

        if len(full_text) < 100:
            raise ValueError("El PDF contiene muy poco texto para analizar")

        self.logger.info(f"✓ Texto completo: {len(full_text)} caracteres")

        # Paso 2-3: Construir contexto (mismo que análisis general)
        if len(full_text) < 5000:
            context = f"DOCUMENTO COMPLETO DEL TDR:\n\n{full_text}\n\n===== FIN DEL DOCUMENTO ====="
        else:
            fragments = self.rag_extractor.extract_relevant_fragments(full_text)
            context = self.rag_extractor.build_context_for_llm(fragments)

        self.logger.info(f"✓ Contexto construido: {len(context)} caracteres")

        # Paso 4: Analizar con prompt forense
        self.logger.info(f"🔍 Analizando direccionamiento con LLM (tipo: {tipo_contrato})...")
        if es_mayor:
            analysis_dict = await llm_client.analyze_direccionamiento_mayores(context)
            self.last_token_usage = analysis_dict.pop('_token_usage', {})
            return analysis_dict
        else:
            analysis_dict = await llm_client.analyze_direccionamiento(context)
        self.last_token_usage = analysis_dict.pop('_token_usage', {})
        analysis_dict = self._sanitize_direccionamiento_payload(analysis_dict)

        # Fallback: si el análisis textual falla, reintentar con multimodal
        score = analysis_dict.get("score_riesgo_corrupcion", 0)
        hallazgos = analysis_dict.get("hallazgos_criticos", [])
        argumento = analysis_dict.get("argumento_para_observacion", "")
        is_empty = score == 0 and not hallazgos and len(argumento) < 30
        if is_empty and hasattr(llm_client, 'analyze_direccionamiento_from_pdf'):
            self.logger.warning("⚠️ Respuesta vacía del LLM textual — reintentando con multimodal...")
            analysis_dict = await llm_client.analyze_direccionamiento_from_pdf(pdf_bytes, "fallback.pdf")
            self.last_token_usage = analysis_dict.pop('_token_usage', {})
            analysis_dict = self._sanitize_direccionamiento_payload(analysis_dict)

        # Paso 5: Validar con Pydantic
        try:
            validated = DireccionamientoAnalysisResponse(**analysis_dict)
            self.logger.info(f"✅ Direccionamiento completado — Score: {validated.score_riesgo_corrupcion}, Veredicto: {validated.veredicto_flash}")
            return validated
        except Exception as e:
            self.logger.error(f"Error al validar respuesta de direccionamiento: {str(e)}")
            raise ValueError(f"Respuesta del LLM no cumple esquema: {str(e)}")

    # Mapeos para normalizar valores inventados por el LLM
    _GRAVEDAD_MAP = {
        "muy alto": "Alto", "critico": "Alto", "crítico": "Alto",
        "muy alta": "Alto", "extremo": "Alto", "grave": "Alto",
        "alto": "Alto", "alta": "Alto",
        "medio-alto": "Alto", "medio alto": "Alto",
        "medio": "Medio", "media": "Medio", "moderado": "Medio", "moderada": "Medio",
        "medio-bajo": "Bajo", "medio bajo": "Bajo",
        "bajo": "Bajo", "baja": "Bajo", "leve": "Bajo", "menor": "Bajo", "minimo": "Bajo",
    }
    _CATEGORIAS_VALIDAS = {"Técnica", "Experiencia", "Personal", "Puntaje", "Fraccionamiento", "Otra"}
    _CATEGORIA_MAP = {
        "tecnica": "Técnica", "técnica": "Técnica", "tecnologia": "Técnica",
        "especificacion": "Técnica", "especificaciones": "Técnica",
        "logística": "Técnica", "logistica": "Técnica", "geográfica": "Técnica",
        "geografica": "Técnica", "logística/geográfica": "Técnica",
        "administrativa": "Técnica", "procedimental": "Técnica",
        "experiencia": "Experiencia", "calificacion": "Experiencia", "calificación": "Experiencia",
        "personal": "Personal", "personal clave": "Personal", "equipo": "Personal",
        "puntaje": "Puntaje", "evaluacion": "Puntaje", "evaluación": "Puntaje", "puntuacion": "Puntaje",
        "fraccionamiento": "Fraccionamiento",
        "otra": "Otra", "otro": "Otra", "general": "Otra",
    }

    def _normalize_gravedad(self, valor: str) -> str:
        """Normaliza nivel_de_gravedad al valor exacto del schema."""
        return self._GRAVEDAD_MAP.get(valor.strip().lower(), "Medio")

    def _normalize_categoria(self, valor: str) -> str:
        """Normaliza categoria al valor exacto del schema."""
        cleaned = valor.strip()
        if cleaned in self._CATEGORIAS_VALIDAS:
            return cleaned
        key = cleaned.lower()
        if key in self._CATEGORIA_MAP:
            return self._CATEGORIA_MAP[key]
        # Si contiene alguna categoría válida como substring, usar esa
        for cat_key, cat_val in self._CATEGORIA_MAP.items():
            if cat_key in key:
                return cat_val
        return "Otra"

    def _sanitize_direccionamiento_payload(self, payload: Dict) -> Dict:
        """Ajusta el payload de direccionamiento para cumplir con el esquema."""
        score = payload.get("score_riesgo_corrupcion")
        try:
            payload["score_riesgo_corrupcion"] = max(0, min(100, int(score)))
        except (TypeError, ValueError):
            payload["score_riesgo_corrupcion"] = 0

        veredicto = (payload.get("veredicto_flash") or "").upper()
        if veredicto not in {"LIMPIO", "SOSPECHOSO", "ALTAMENTE DIRECCIONADO"}:
            s = payload["score_riesgo_corrupcion"]
            veredicto = "ALTAMENTE DIRECCIONADO" if s >= 70 else ("SOSPECHOSO" if s >= 30 else "LIMPIO")
        payload["veredicto_flash"] = veredicto

        if not isinstance(payload.get("hallazgos_criticos"), list):
            payload["hallazgos_criticos"] = []

        # Normalizar campos de cada hallazgo para que cumplan con el schema
        sanitized_hallazgos = []
        for h in payload["hallazgos_criticos"][:8]:
            if not isinstance(h, dict):
                continue
            h["nivel_de_gravedad"] = self._normalize_gravedad(
                str(h.get("nivel_de_gravedad", "Medio"))
            )
            h["categoria"] = self._normalize_categoria(
                str(h.get("categoria", "Otra"))
            )
            sanitized_hallazgos.append(h)
        payload["hallazgos_criticos"] = sanitized_hallazgos

        if not payload.get("argumento_para_observacion"):
            payload["argumento_para_observacion"] = "Sin argumento generado por el modelo."

        # Truncar argumento si excede max_length del schema (2000 chars)
        arg = payload.get("argumento_para_observacion", "")
        if len(arg) > 2000:
            payload["argumento_para_observacion"] = arg[:1997] + "..."

        return payload

    async def generate_proforma_document(
        self,
        pdf_bytes: bytes,
        company_name: str,
        company_copy: str,
        contrato_contexto: Optional[Dict] = None,
        llm_provider: Optional[Literal["gemini", "openai", "anthropic"]] = None,
        tipo_contrato: str = "menores",
    ) -> ProformaResponse:
        """
        Pipeline de generación de proforma técnica de cotización.
        Estrategia híbrida: multimodal para escaneados, texto+RAG para nativos.
        """
        self.logger.info("=== INICIANDO PIPELINE DE PROFORMA TÉCNICA ===")

        llm_client = LLMFactory.create_client(llm_provider)

        # Paso 1: Detección ultrarrápida — PyMuPDF directo (~0.3s)
        self.logger.info("Paso 1: Detectando tipo de PDF (nativo vs escaneado)...")
        detect_text = self._extract_native_text(pdf_bytes)
        num_pages = self._get_page_count(pdf_bytes)
        chars_per_page = len(detect_text) / max(num_pages, 1)

        self.logger.info(
            f"✓ Detección: {len(detect_text)} chars, "
            f"{num_pages} págs, {chars_per_page:.0f} chars/pág"
        )

        # ── Router: Contratos Mayores vs Menores ──────────────────────────────
        es_mayor = (tipo_contrato == "mayores")

        # ── Estrategia híbrida ─────────────────────────────────────────────
        use_multimodal = (
            (chars_per_page < MIN_CHARS_PER_PAGE or num_pages > 20)
            and hasattr(llm_client, 'generate_proforma_from_pdf')
        )

        if use_multimodal:
            self.logger.info(
                f"📸 PDF escaneado ({chars_per_page:.0f} chars/pág) "
                f"— proforma multimodal directo, sin OCR..."
            )
            raw = await llm_client.generate_proforma_from_pdf(
                pdf_bytes, "document.pdf", company_name, company_copy, contrato_contexto
            )
            self.last_token_usage = raw.pop('_token_usage', {})
            sanitized = self._sanitize_proforma_payload(raw)
            try:
                validated = ProformaResponse(**sanitized)
                self.logger.info(f"✅ Proforma multimodal — {len(validated.items)} ítems")
                return validated
            except Exception as e:
                self.logger.error(f"Error al validar proforma multimodal: {str(e)}")
                raise ValueError(f"Respuesta del LLM no cumple esquema de proforma: {str(e)}")

        # ── Camino texto nativo ────────────────────────────────────────────
        self.logger.info("📄 PDF nativo — extrayendo texto + tablas (sin OCR)...")
        full_text = self.pdf_processor.extract_text_from_pdf(pdf_bytes)

        if len(full_text) < 50:
            raise ValueError("El PDF contiene muy poco texto para generar la proforma")

        self.logger.info(f"✓ Texto completo: {len(full_text)} caracteres")

        # Paso 2-3: Construir contexto (mismo pipeline que análisis general)
        if len(full_text) < 5000:
            context = f"DOCUMENTO COMPLETO DEL TDR:\n\n{full_text}\n\n===== FIN DEL DOCUMENTO ====="
        else:
            fragments = self.rag_extractor.extract_relevant_fragments(full_text)
            context = self.rag_extractor.build_context_for_llm(fragments)

        self.logger.info(f"✓ Contexto construido: {len(context)} caracteres")

        # Paso 4: Generar proforma con LLM
        self.logger.info(f"📋 Generando proforma con LLM (tipo: {tipo_contrato})...")
        if es_mayor:
            raw = await llm_client.generate_proforma_mayores(
                context, company_name, company_copy, contrato_contexto,
            )
            self.last_token_usage = raw.pop('_token_usage', {})
            return raw
        else:
            raw = await llm_client.generate_proforma(
                context, company_name, company_copy, contrato_contexto,
            )
        self.last_token_usage = raw.pop('_token_usage', {})
        sanitized = self._sanitize_proforma_payload(raw)

        # Fallback: si la proforma textual no tiene ítems, reintentar con multimodal
        if not sanitized.get("items") and hasattr(llm_client, 'generate_proforma_from_pdf'):
            self.logger.warning("⚠️ Proforma vacía del LLM textual — reintentando con multimodal...")
            raw = await llm_client.generate_proforma_from_pdf(
                pdf_bytes, "fallback.pdf", company_name, company_copy, contrato_contexto
            )
            self.last_token_usage = raw.pop('_token_usage', {})
            sanitized = self._sanitize_proforma_payload(raw)

        try:
            validated = ProformaResponse(**sanitized)
            self.logger.info(f"✅ Proforma generada — {len(validated.items)} ítems")
            return validated
        except Exception as e:
            self.logger.error(f"Error al validar respuesta de proforma: {str(e)}")
            raise ValueError(f"Respuesta del LLM no cumple esquema de proforma: {str(e)}")

    async def generate_proforma_from_analysis(
        self,
        analisis_tdr: Dict,
        company_name: str,
        company_copy: str,
        contrato_contexto: Optional[Dict] = None,
        llm_provider: Optional[Literal["gemini", "openai", "anthropic"]] = None,
    ) -> ProformaResponse:
        """
        Genera proforma a partir de un análisis TDR ya existente (sin re-procesar PDF).
        Usa el resumen ejecutivo + requisitos como contexto.
        """
        self.logger.info("=== PROFORMA DESDE ANÁLISIS EXISTENTE ===")

        llm_client = LLMFactory.create_client(llm_provider)

        # Construir contexto resumido desde el análisis
        partes = []
        if analisis_tdr.get("resumen_ejecutivo"):
            partes.append(f"RESUMEN EJECUTIVO:\n{analisis_tdr['resumen_ejecutivo']}")
        if analisis_tdr.get("requisitos_tecnicos"):
            partes.append("REQUISITOS TÉCNICOS:\n- " + "\n- ".join(analisis_tdr["requisitos_tecnicos"]))
        if analisis_tdr.get("reglas_de_negocio"):
            partes.append("REGLAS DE NEGOCIO:\n- " + "\n- ".join(analisis_tdr["reglas_de_negocio"]))
        if analisis_tdr.get("presupuesto_referencial"):
            partes.append(f"PRESUPUESTO REFERENCIAL: {analisis_tdr['presupuesto_referencial']}")

        context = "\n\n".join(partes) if partes else "Sin información del TDR disponible"

        raw = await llm_client.generate_proforma(
            context,
            company_name,
            company_copy,
            contrato_contexto,
        )
        sanitized = self._sanitize_proforma_payload(raw)

        try:
            validated = ProformaResponse(**sanitized)
            self.logger.info(f"✅ Proforma desde análisis generada — {len(validated.items)} ítems")
            return validated
        except Exception as e:
            raise ValueError(f"Respuesta del LLM no cumple esquema de proforma: {str(e)}")

    def _sanitize_proforma_payload(self, payload: Dict) -> Dict:
        """Normaliza el payload de proforma para cumplir con el esquema."""
        for key in ("titulo_proceso", "empresa_nombre", "empresa_rubro", "total_estimado", "analisis_viabilidad"):
            if not isinstance(payload.get(key), str):
                payload[key] = ""

        if not isinstance(payload.get("items"), list):
            payload["items"] = []

        if not isinstance(payload.get("condiciones"), list):
            payload["condiciones"] = []

        sanitized_items = []
        for idx, item in enumerate(payload["items"][:20], start=1):
            if not isinstance(item, dict):
                continue
            try:
                precio = float(item.get("precio_unitario", 0) or 0)
                cantidad = float(item.get("cantidad", 1) or 1)
                subtotal = float(item.get("subtotal", precio * cantidad) or precio * cantidad)
                sanitized_items.append({
                    "item": int(item.get("item", idx)),
                    "descripcion": str(item.get("descripcion", ""))[:300],
                    "unidad": str(item.get("unidad", "Und"))[:50],
                    "cantidad": round(cantidad, 2),
                    "precio_unitario": round(precio, 2),
                    "subtotal": round(subtotal, 2),
                })
            except (TypeError, ValueError):
                continue

        payload["items"] = sanitized_items

        # Truncar analisis_viabilidad
        viab = payload.get("analisis_viabilidad", "")
        if len(viab) > 3000:
            payload["analisis_viabilidad"] = viab[:2997] + "..."

        return payload

    def _extract_docx_text(self, pdf_bytes: bytes) -> str:
        """Extrae texto de un archivo DOCX (Office Open XML) sin dependencias externas."""
        import zipfile
        import xml.etree.ElementTree as ET
        import io

        try:
            with zipfile.ZipFile(io.BytesIO(pdf_bytes)) as zf:
                if 'word/document.xml' not in zf.namelist():
                    return ""

                xml_content = zf.read('word/document.xml')
                root = ET.fromstring(xml_content)

                paragraphs = []
                for p in root.iter('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}p'):
                    texts = []
                    for t in p.iter('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}t'):
                        if t.text:
                            texts.append(t.text)
                    if texts:
                        paragraphs.append(''.join(texts))

                text = '\n\n'.join(paragraphs)
                self.logger.info(f"✓ DOCX: {len(text)} chars extraídos de {len(paragraphs)} párrafos")
                return text

        except Exception as e:
            self.logger.warning(f"No se pudo extraer texto del DOCX: {e}")
            return ""
