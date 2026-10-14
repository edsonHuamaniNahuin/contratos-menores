# 🎯 INVESTIGACIÓN A PROFUNDIDAD — El Cliente Caliente (Target de Urgencia)

> **Pedido del dueño (14/10):** "no me interesan los likes… necesito que el target sea caliente, el cliente que NECESITA el servicio".
> **Fuentes:** BD de producción (compradores, usuarios, suscripciones, convocatorias del sistema, clicks WA), VoC existente (`voc-datos-reales.md`), resultados de campaña (almacén Meta + GA4), análisis de intención.

---

## 1. QUIÉN PAGA HOY (evidencia dura de BD)

**11 pagos aprobados en total:** 7 en Premium Mayores (S/68/mes) + 4 en Mensual (S/49/mes).
**Patrón de compra:** registran → **10–12 días de "lurking"** → pagan. La compra NO es impulsiva: la dispara un **evento de urgencia** (una convocatoria que calza, o el dolor de perder una).
**Perfil de los que pagan (VoC del 02/09 + pagos):**
- **Cluster A — Construcción/ingeniería (el que paga):** pavimentos, carreteras, vial urbana, obras civiles, saneamiento, electricidad (media/alta tensión), alquiler de maquinaria pesada. 4 de 5 activos de ese cluster.
- Estudio jurídico (pagó premium mayores). Cluster TI/telecom/BIM: prueba y **no convierte** (no invertir ahí).
- **Retención crítica:** suscripciones hoy = `expired 15 · cancelled 4 · active 3`. La fuga es el problema #2 (post-adquisición) — ya hay `trial:onboarding` + winback en marcha.

## 2. QUÉ DEMANDA EL ESTADO AHORA (lo que el sistema ve = el mercado real)

**4,529 convocatorias notificadas** (BD `notified_processes`).

| Corte | Hallazgo |
|---|---|
| **Tipo de objeto** | Servicio 75% (3,393) · Bien 664 · Obra 117 · Consultoría de obra 116 · Buena Pro 239 |
| **Categorías (LIKE, aprox.)** | construcción/mantenimiento 989 · tecnología 734 · **obras/carreteras 643** · salud 523 · **saneamiento/agua 333** · consultoría 229 · **maquinaria 196** · eléctrico 119 |
| **Entidades que más publican** | GORE Ucayali (515) · **Provías Nacional (299)** · Salud Áncash (281) · U. Tayacaja (135) · **SEDA Arequipa (126)** · INIA (101) · Hospital Cusco (97) · **Chavimochic (80)** |
| **Temporada** | Ago 1,225 · Sep 1,653 → **Q4 = temporada de ejecución presupuestal** (entidades publican a full antes del cierre de año). **Estamos en la temporada más caliente del año.** |
| ⚠️ **ALERTA PRODUCTO** | Octubre solo 53 registros (vs ~700 esperado) — **evidencia created_at: últimos 10 días solo 1-19 inserciones/día (sep: ~55/día)** → el notificador/scraper está degradado. Prioridad alta: afecta alertas reales de usuarios y la promesa del producto. Verificar HOY. |

**El cruce que importa:** los que PAGAN (constructoras/obras/saneamiento/electricidad) coinciden con las categorías de mayor publicación (obras/carreteras, saneamiento, maquinaria, eléctrico) y las entidades que más publican (Provías, SEDA, GOREs, Chavimochic). **Hay match oferta-demanda: el target correcto existe y publica en volumen.**

## 3. SEÑALES DE URGENCIA (evidencia de comportamiento)

1. **Los únicos 2 clicks WA reales** vinieron del ángulo **"V2 Dolor"** ("enterarte tarde te cuesta") en la landing de alertas → **el dolor mueve, el "dato curioso" no.**
2. **Lurk de 10-12 días:** compra en el momento del trigger, no al ver el anuncio → el anuncio debe **estar ahí cuando el trigger pasa** (presencia + retargeting), y el mensaje debe nombrar el trigger.
3. **Temporada Q4:** urgencia real y verificable ("las entidades están publicando a full antes del cierre de año").
4. **12 usuarios siguen contratos específicos** (22 seguimientos) → comportamiento de proveedor activo en el sistema.

## 4. PERFIL DEL CLIENTE CALIENTE (a quién hay que atraer)

**ICP CALIENTE (orden de prioridad):**

| # | Perfil | Dónde está (señales) | Disparador de compra |
|---|---|---|---|
| 1 | **Constructora/contratista de obras** (pavimentos, vial urbana, saneamiento, eléctrica, maquinaria) que ya vende o quiere vender al Estado | Departamentos con publicación activa: Áncash, Ucayali, Cusco, Arequipa, La Libertad, Lima | Ve una convocatoria de su rubro/región que calza y no la quiere perder |
| 2 | **Proveedor de SERVICIOS al Estado** (mantenimiento, consultoría, salud) — el 75% de las convocatorias son servicios | El objeto más publicado del SEACE | Necesita flujo constante de contratos públicos |
| 3 | **Proveedor "primera venta"** (RNP listo, aún sin contrato) | Busca "cómo postular", "requisitos" | Miedo/ansiedad de no saber por dónde empezar en temporada |
| 4 | **Proveedor que PERDIÓ o se enteró tarde** de un proceso | El ángulo Dolor que dio los únicos clicks WA | Rabia/arrepentimiento inmediato |

**Definición operativa de "caliente" (para filtrar leads):** responde ≥2 de: (a) rubro = obra/servicio con alta publicación, (b) ya vendió o postuló al Estado, (c) tiene urgencia declarada ("esta semana", "ahora"), (d) región con convocatorias activas de su rubro.

## 5. QUÉ CAMBIA EN LA CAPTACIÓN (plan de targeting)

1. **J0 Instant Form con preguntas calificadoras** (el filtro de "target caliente" MÁS directo): `¿A qué se dedica tu empresa?` · `¿Ya vende al Estado? (Sí/No/Estoy empezando)` · `¿Qué tipo de procesos buscas?` → el equipo de ventas prioriza por esas respuestas. *(Ajustar el form `1427421766170299` al desbloquear ToS.)*
2. **Creativos: el ángulo Dolor manda** (evidencia: únicos WA clicks). Los 3 ángulos siguen en test, pero el nuevo lote del día 8 debe ser: **dolor + rubro específico** ("¿Eres contratista? Así te enteraste tarde de la última obra…") y **urgencia de temporada** ("antes del cierre de año las entidades publican a full").
3. **Segmentación:** mantener intereses de construcción + gobierno, PERO el filtro real será (a) el mensaje (auto-selección por rubro), (b) la optimización por conversiones (J0/pixel), (c) retargeting: **construir YA las audiencias de sitio web** (visitantes 180d por página: `/licitaciones-vigentes`, `/alertas-licitaciones`) + WA clickers + usuarios registrados sin pago → campaña de retargeting de urgencia (~día 14-21). Nota: el pixel es nuevo (12/10) — las audiencias empiezan a llenarse ahora.
4. **Mensaje con rubro y región**: el copy debe nombrar obras/carreteras/saneamiento/electricidad y "tu región" (el cliente se reconoce; los curiosos se auto-excluyen).
5. **Lookalike:** con 11 compradores no se puede LAL todavía. Cuando la audiencia de compradores + leads llegue a ~100, crear LAL 1%.

## 6. MEDICIÓN DEL "CALOR" (reemplaza métricas vanidad)

| Métrica | Reemplaza a | Umbral |
|---|---|---|
| **% de leads calificados** (respuesta del form J0) | likes/clicks | ≥50% de leads con rubro obra/servicio |
| **Clics WA por 100 landings** | CTR del anuncio | ≥2% (hoy ~0.7% con tráfico frío) |
| **Leads con "ya vende al Estado"** | alcance | ≥30% |
| **CPL por segmento de campana** | CPM | ≤S/8 (10x) · ≤S/12 (alerta) |
| **Registros que pagan ≤15 días** | tiempo en página | ≥10% (hoy ~5%) |

## 7. PENDIENTES DE VERIFICACIÓN (honestidad)

- [ ] **Entrevistas (5-10)** con compradores y trials fríos — guion listo (`candidatos-entrevistas.md`); requiere al equipo del dueño (llamadas 20 min). Es la única fuente que supera a la BD para "por qué compra".
- [ ] **Captura de octubre (notified_processes 53)** — verificar scraper (afecta alertas reales de usuarios).
- [ ] Herramientas de demanda externa: falta `SERPAPI_KEY` (gratis) y verificar cuenta DataForSEO para volumen de búsquedas; Keywords Everywhere sin saldo.
- [ ] Verificar si el registro captura RNP/rubro estructurado (hoy es texto libre `company_copy`) — un campo "rubro" + "ya vende al Estado" en el registro mejoraría el scoring de leads.

*Documento vivo — se actualiza con cada hallazgo. Insumo directo de `landing-vigilante` y del protocolo operativo.*
