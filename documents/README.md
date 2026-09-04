# 📁 Documents — Vigilante SEACE

> Índice maestro de la documentación, organizada **por contexto y objetivo**.
> Estructura: estrategia → marketing → clientes → métricas → operaciones.

---

## 🗂️ 01-estrategia-negocio
*Qué somos, qué vendemos y cómo fluye el sistema. Leer primero para contexto.*

| Documento | Objetivo |
|---|---|
| `FUNCIONALIDADES_SISTEMA.md` | Catálogo funcional completo de la plataforma (módulos, permisos, planes) |
| `SEACE_CORP.md` | Ficha comercial/corporativa del producto (stack y parámetros UX) |
| `RUTAS_FLUJOS_SISTEMA.md` | Mapa de rutas y flujos del sistema |

## 🚀 02-marketing-adquisicion
*Cómo atraer y convertir: SEO/SEM, demanda, contenido comercial y skills de marketing.*

| Documento | Objetivo |
|---|---|
| `ANALISIS_COMPLETO_SEO_SEM.md` | Plan SEO/SEM completo (fases, metas CTR/clics/registros) |
| `ANALISIS_CLUSTERS_DATOS_REALES.md` | CTR por cluster de keywords (GSC real) + acciones de ganancia |
| `BITACORA_SEO_SEM.md` | Bitácora de cambios SEO y sus efectos |
| `BRECHA_KEYWORDS_DATOS_REALES.md` | Brecha: keywords que no rankeamos (4,256) por cluster |
| `INVESTIGACION_KEYWORDS_NUEVOS_CLUSTERS.md` | Prioridades de clusters y landings a crear (plan 60 días) |
| `KEYWORDS_AUDIENCIA_REAL.md` | 5,355 keywords cosechadas por patrón de intención |
| `KEYWORDS_TRANSACCIONALES.md` | Inventario de keywords por nivel de intención (Nivel 1 = compra) |
| `VOLUMENES_ESTIMADOS_GSC.md` | Volumen estimado del nicho (220K búsquedas/mes) |
| `PRONOSTICO_TRAFICO_REVENUE.md` | Pronóstico de tráfico e ingresos (base → 6 meses) |
| `GUION_TIKTOK_LIVE.md` | Guion del live (20-25 min) + métricas post-live |
| `TIKTOK_INTEGRATION.md` | Integración técnica TikTok |
| `BROCHURE_CONTENIDO.md` | Contenido fuente del brochure comercial (Honda/corporativo) |
| `PROMPT_BROCHURE_IA.md` | Prompt para IA generadora de brochures (+14 screenshots) |
| `EMBUDOS_3_CAMPANAS.md` | **3 embudos + 3 campañas Google Ads** (E1 alertas, E2 software, E3 vigentes; CPR/CAC/ROAS por embudo) |
| `VOC-DATOS-REALES` (en 03) | Keywords y descripción de empresa de usuarios reales → cluster A construcción = el que paga |
| Landings E1/E2/E3 | `/alertas-licitaciones`, `/software-licitaciones`, `/licitaciones-vigentes` (rutas en web.php) |

> Skills de marketing: `.opencode/skills/` (S1 mercado, S2 landing, S3 funnel-paid).

## 👥 03-clientes-investigacion
*Quién es nuestro cliente y qué dice: inputs del S1 (mercado-vigilante).*

| Documento | Objetivo |
|---|---|
| `candidatos-entrevistas.md` | Lista priorizada para entrevistas VoC (pagando / trials / cancelados) |
| `voc-datos-reales.md` | **VoC desde BD**: keywords configuradas + descripción de empresa de usuarios reales (sustituto de entrevistas: cluster A construcción/ingeniería = el que paga) |
| *(futuros)* `swipe-file-voc.md` · `objecciones.md` · `personas.md` | Entregables del skill mercado-vigilante |

## 📈 04-metricas-reportes
*Resultados medidos y aprendizajes de tests.*

| Documento | Objetivo |
|---|---|
| `REPORTE_OBJETIVOS_SEP2026.md` | Objetivos planificados vs conseguidos/no conseguidos (corte 02/09) |
| *(futuros)* aprendizajes de A/B tests | Registro de qué ganó y por qué (skill funnel-paid-vigilante) |

## 🛠️ 05-tecnico-operaciones
*Documentación técnica de desarrollo, despliegue y operación.*

| Documento | Objetivo |
|---|---|
| `arquitectura.md` … `comandos.md` | Documentación técnica por módulo (arquitectura, menores, mayores, IA, notificaciones, backup, despliegue, suscripciones, comandos) |
| `BITACORA.md` | **Bitácora de bugs e incidencias** (revisar siempre ante errores) |
| `API_SEACE_ENDPOINTS.md` | Endpoints de la API SEACE |
| `AUTENTICACION_RESILIENTE.md` | Estrategia de autenticación resiliente |
| `BUSCADOR_PUBLICO.md` | Documentación del buscador público |
| `CI_CD_MANUAL.md` · `SETUP_GUIDE.md` · `SETUP_QA.md` · `QUICKSTART.md` | Instalación, CI/CD y entornos |
| `QA_CERTIFICACION.md` | Casos de certificación QA |
| `COPILOT_CONTEXT.md` | Contexto para asistentes de código |
| `INVESTIGACION_SEACE.md` | Investigación del SEACE (incl. flujo JSF/captcha) |
| `mejorar-promp.md` | Reglas del analizador IA (Ley 32069, scores, direccionamiento) |
| `CHANGELOG.md` | Changelog base (skeleton Laravel) |

---

## 📌 Convención de uso
- **Contexto comercial/marketing** → copiar de `02-marketing-adquisicion` y `03-clientes-investigacion`.
- **Bug o incidente** → leer `05-tecnico-operaciones/BITACORA.md` y agregar la entrada nueva allí.
- **Nuevo entregable del skill S1** → `03-clientes-investigacion/`; **resultados/tests** → `04-metricas-reportes/`.
- Los skills S1-S3 referencian estas carpetas; mantenerlas actualizadas.
