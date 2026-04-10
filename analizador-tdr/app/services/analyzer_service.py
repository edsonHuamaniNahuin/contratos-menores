"""
Orquestador principal del pipeline RAG de análisis de TDRs.
Coordina PDF Processor → RAG Extractor → LLM Client.
"""
from typing import Dict, Optional, Literal
from app.services.pdf_processor import PDFProcessorService
from app.services.rag_extractor import RAGExtractionService
from app.services.llm import LLMFactory
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

    async def analyze_tdr_document(
        self,
        pdf_bytes: bytes,
        llm_provider: Optional[Literal["gemini", "openai", "anthropic"]] = None
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

        # ESTRATEGIA: Extracción local inteligente → texto enriquecido → LLM
        # Evita enviar el PDF completo al LLM (ahorro de tokens a escala)
        self.logger.info("📄 Extrayendo contenido con SmartPDFReaderPipeline (texto + tablas + OCR)...")

        # Paso 1: Extraer texto con pipeline inteligente (método SÍNCRONO)
        self.logger.info("Paso 1/4: Extrayendo contenido del PDF...")
        full_text = self.pdf_processor.extract_text_from_pdf(pdf_bytes)

        if len(full_text) < 100:
            raise ValueError("El PDF contiene muy poco texto para analizar")

        self.logger.info(f"✓ Texto extraído: {len(full_text)} caracteres")

        # Detectar PDF escaneado: si chars/página es muy bajo, enviar PDF directo al LLM
        num_pages = self._get_page_count(pdf_bytes)
        chars_per_page = len(full_text) / max(num_pages, 1)

        if chars_per_page < MIN_CHARS_PER_PAGE and hasattr(llm_client, 'analyze_tdr_from_pdf'):
            self.logger.warning(
                f"⚠️ Texto insuficiente ({chars_per_page:.0f} chars/pág, {num_pages} págs) — "
                f"posible PDF escaneado. Enviando PDF directo a Gemini (multimodal)..."
            )
            analysis_dict = await llm_client.analyze_tdr_from_pdf(pdf_bytes, "scanned.pdf")
            analysis_dict = self._sanitize_llm_payload(analysis_dict)
            return self._validate_response(analysis_dict)

        # Paso 2 y 3: Construir contexto para el LLM
        # Si el documento es pequeño (<5000 caracteres), enviar todo directamente sin RAG
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
        self.logger.info(f"Paso 4/4: Analizando con LLM (provider: {llm_provider or 'default'})...")
        analysis_dict = await llm_client.analyze_tdr(context)

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
            # Fallback: si el LLM textual no extrajo nada, intentar con PDF directo
            if hasattr(llm_client, 'analyze_tdr_from_pdf'):
                self.logger.warning("⚠️ Respuesta vacía del LLM textual — reintentando con PDF directo (multimodal)...")
                analysis_dict = await llm_client.analyze_tdr_from_pdf(pdf_bytes, "fallback.pdf")
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
        llm_provider: Optional[Literal["gemini", "openai", "anthropic"]] = None
    ) -> DireccionamientoAnalysisResponse:
        """
        Pipeline de análisis forense de direccionamiento.
        Reutiliza la extracción de texto (SmartPDFReaderPipeline + RAG)
        pero usa el prompt forense para detectar corrupción.
        """
        self.logger.info("=== INICIANDO PIPELINE DE DIRECCIONAMIENTO ===")

        llm_client = LLMFactory.create_client(llm_provider)

        # Paso 1: Extraer texto (mismo pipeline que análisis general)
        self.logger.info("📄 Extrayendo contenido con SmartPDFReaderPipeline...")
        full_text = self.pdf_processor.extract_text_from_pdf(pdf_bytes)

        if len(full_text) < 100:
            raise ValueError("El PDF contiene muy poco texto para analizar")

        self.logger.info(f"✓ Texto extraído: {len(full_text)} caracteres")

        # Paso 2-3: Construir contexto (mismo que análisis general)
        if len(full_text) < 5000:
            context = f"DOCUMENTO COMPLETO DEL TDR:\n\n{full_text}\n\n===== FIN DEL DOCUMENTO ====="
        else:
            fragments = self.rag_extractor.extract_relevant_fragments(full_text)
            context = self.rag_extractor.build_context_for_llm(fragments)

        self.logger.info(f"✓ Contexto construido: {len(context)} caracteres")

        # Paso 4: Analizar con prompt forense
        self.logger.info("🔍 Analizando direccionamiento con LLM...")
        analysis_dict = await llm_client.analyze_direccionamiento(context)
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
    ) -> ProformaResponse:
        """
        Pipeline de generación de proforma técnica de cotización.
        Reutiliza la extracción de texto y construye el prompt con el perfil de empresa.
        """
        self.logger.info("=== INICIANDO PIPELINE DE PROFORMA TÉCNICA ===")

        llm_client = LLMFactory.create_client(llm_provider)

        # Paso 1: Extraer texto del PDF
        self.logger.info("📄 Extrayendo contenido del PDF para proforma...")
        full_text = self.pdf_processor.extract_text_from_pdf(pdf_bytes)

        if len(full_text) < 50:
            raise ValueError("El PDF contiene muy poco texto para generar la proforma")

        self.logger.info(f"✓ Texto extraído: {len(full_text)} caracteres")

        # Paso 2-3: Construir contexto (mismo pipeline que análisis general)
        if len(full_text) < 5000:
            context = f"DOCUMENTO COMPLETO DEL TDR:\n\n{full_text}\n\n===== FIN DEL DOCUMENTO ====="
        else:
            fragments = self.rag_extractor.extract_relevant_fragments(full_text)
            context = self.rag_extractor.build_context_for_llm(fragments)

        self.logger.info(f"✓ Contexto construido: {len(context)} caracteres")

        # Paso 4: Generar proforma con LLM
        self.logger.info("📋 Generando proforma técnica con LLM...")
        raw = await llm_client.generate_proforma(
            context,
            company_name,
            company_copy,
            contrato_contexto,
        )
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
