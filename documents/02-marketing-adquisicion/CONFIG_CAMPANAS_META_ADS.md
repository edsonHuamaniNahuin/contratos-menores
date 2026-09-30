# 📱 CAMPAÑAS META ADS (Facebook/Instagram) — Vigilante SEACE
## Preparado 02/09/2026 · licitacionesmype.pe

> Estructura para crear en Meta Ads Manager (no requiere API). Requisito previo: **META_PIXEL_ID** en .env → los eventos `Contact` (clic WhatsApp) y `Lead` (formulario enviado) ya están implementados en las landings.

---

## 0. Requisitos antes de crear
- [ ] Crear el **Pixel** en Meta Business Suite → *Configuración de datos* → copiar el **Pixel ID** y pasarlo al dev para ponerlo en `META_PIXEL_ID` del .env y desplegar.
- [ ] Verificar el pixel en producción: cargar cada landing + extensión Meta Pixel Helper (debe verse `PageView`, y `Contact`/`Lead` al interactuar).
- [ ] Configurar el **dominio** en Meta Business Suite (licitacionesmype.pe) y el **evento de conversión** primario: `Lead` (o `Contact` como respaldo).

---

## 1. Las 3 campañas Meta (objetivos distintos — igual lógica que Google pero con creativo)

| Campaña | Objetivo en Meta | Landing destino | Macro-objetivo | Público |
|---|---|---|---|---|
| **MA-Alertas** | **Mensajes** (Click a WhatsApp) | `/alertas-licitaciones` | VENTA (E1) | Dueños/gerentes de MYPE que postulan al Estado |
| **MA-Software** | **Tráfico** → landing (o Leads) | `/software-licitaciones` | REUNIÓN (E2) | Empresas que buscan herramientas para licitar |
| **MA-Vigentes** | **Tráfico** → landing | `/licitaciones-vigentes` | LEAD (E3) | Audiencia amplia: "quiero saber qué licita el Estado" |

**Nota:** para E1 el formato **Click a WhatsApp (objetivo Mensajes)** suele convertir mejor en Perú; las otras dos usan tráfico a la landing con el formulario de correo como conversión `Lead`.

---

## 2. Audiencias (intereses sugeridos — Perú, español, 25-60)

**Núcleo (todas):** personas interesadas en *SEACE*, *OSCE*, *contrataciones del Estado*, *licitaciones*, *RNP - Registro Nacional de Proveedores*, *construcción en Perú*, *gobierno local*.

- **Campaña 1 (Alerta/WhatsApp):** intereses núcleo + cargos gerenciales (dueño, gerente, administrador) + industria construcción.
- **Campaña 2 (Software):** intereses núcleo + tecnología (software empresarial, gestión) + cargos de compras/administración.
- **Campaña 3 (Vigentes):** intereses núcleo + "oportunidades de negocio" + emprendimiento + regiones (todo Perú; luego segmentar por resultados).

Si ya hay seguidores en la página de Facebook o clientes: crear **audiencia similar (lookalike)** al 1-3% con los contactos existentes (correos de la app).

---

## 3. Presupuesto de arranque (sugerido)
- Total **S/ 900 - 1,500/mes** para 3 campañas en fase de test (S/ 300-500 c/u).
- Objetivo de aprendizaje: mínimo ~50 conversiones/semana por campaña para que Meta optimice.
- Revisión a los 7 días: CTR, CPM, costo por conversión → pausar creativos que no rindan, escalar ganadores.

---

## 4. Creativos (copy español peruano + formato)

### MA-Alertas → /alertas-licitaciones (objetivo: que escriban por WhatsApp)
**Imagen:** foto real de obra/maquinaria (banco, formato 1:1 o 4:5) o captura de la alerta WhatsApp real del sistema.

- **Texto principal (primary text):**
"¿Cuántas licitaciones de tu rubro se te escapan cada mes?
Cada día el Estado publica cientos de convocatorias en el SEACE. Las de tu empresa son pocas, aparecen de golpe y las ventanas para cotizar duran días.
Vigilante SEACE te avisa por WhatsApp el mismo día, filtrado por tu rubro y región. Sin revisar el portal a mano.
Escríbenos y te mostramos el sistema con procesos reales de tu sector."

- **Título (headline):** "Alertas de licitaciones por WhatsApp" · botón: **Enviar mensaje**

### MA-Software → /software-licitaciones (objetivo: demo/reunión)
**Imagen:** mockup del panel/proforma real del sistema.

- **Texto:**
"Deja de buscar licitaciones a mano.
Vigilante SEACE monitorea el SEACE 24/7, filtra por tu rubro, analiza el TDR con IA y te arma la proforma en Word o Excel.
Agenda una demo de 20 minutos y lo ves con procesos reales de tu empresa."

- **Título:** "Software de licitaciones con IA" · botón: **Más información**

### MA-Vigentes → /licitaciones-vigentes (objetivo: curiosidad → lead)
**Imagen:** captura del feed de procesos reales (código, entidad, monto).

- **Texto:**
"Hoy hay más de 4,700 procesos en convocatoria en el Perú.
Municipios, regiones, ministerios y hospitales publican sus compras en el SEACE. ¿Cuántos calzan con tu negocio?
Mira los procesos vigentes y recibe aviso cuando publiquen algo de tu rubro."

- **Título:** "Licitaciones vigentes hoy" · botón: **Más información**

---

## 5. Checklist de lanzamiento
- [ ] Pixel instalado y verificado (eventos Contact + Lead en producción)
- [ ] Dominio verificado en Meta Business Suite
- [ ] Crear 3 campañas con copy de la sección 4
- [ ] Configurar conversión: Mensajes (E1) / Lead o Tráfico (E2-E3)
- [ ] Lanzar 3-5 días y revisar: pausar lo que no rinde, duplicar ganadores
- [ ] Reporte mensual de CPL/CPM/ROAS → `documents/04-metricas-reportes/`
