# 🎙️ VOC DESDE BD — Keywords configuradas + descripción de empresa de usuarios reales
## Vigilante SEACE · licitacionesmype.pe · Extraído 02/09/2026

---

## 1. Objetivo
Sustituir las entrevistas del skill S1 (muestra premium demasiado pequeña) por **análisis de datos reales de configuración**: las keywords de vigilancia y la descripción de empresa (company_copy) que cada usuario escribe al registrarse. Sirve de insumo VoC (voice of customer) para landings, copy y segmentación.

## 2. Metodología y fuentes
- Fuente: BD de producción (tablas `subscriber_profiles`, `subscriber_profile_keyword`, `notification_keywords`, `users`, `subscriptions`).
- Alcance: **20 perfiles** con keywords/descripción; **19 con company_copy** no vacío.
- Anonimizado: sin nombres, RUC, correos ni razón social.

## 3. Cluster de rubros reales (quién usa el sistema)

### 🏗️ CLUSTER A — Construcción e ingeniería (el que PAGA)
**Pagadores activos (4 de 5):** constructora de pavimentos/carreteras, constructora vial urbana + saneamiento, ingeniería eléctrica/energía (media y alta tensión), obras civiles + alquiler de maquinaria pesada.
- Keywords típicas: `Obras civiles`, `MOVIMIENTO DE TIERRAS`, `Pistas y veredas`, `Vias urbanas`, `Carreteras`, `calzada`, `vehicuar y petonal`, `Estructuras metálicas`, `Maquinaria pesada`, `Demoliciones`, `Montajes`, `Mantenimiento`, `Infraestructura`.
- Cluster eléctrico (dentro del mismo rubro): `media tensión`, `eléctricas`, `Alta tensión`, `Eléctrico`, `Alumbrado`, `Electrificación`, `Redes de Distribución`, `Domiciliarias`, `tablero eléctrico`, `interruptores`, `bombas de agua`, `electronico`.
- Fraseo real en company_copy:
  - *"Empresa de servicios generales especializada en ejecución de construcción, ejecución y mantenimiento de pavimentos y carreteras."*
  - *"Empresa de construcción que brinda servicios y ejecuta obras de infraestructura vial urbana, saneamiento y carreteras."*
  - *"Empresa de Ingeniería con más de 10 años de experiencia brindando servicios especializados en las Áreas de Energía y Construcción. Ejecutamos Proyectos Electromecánicos, Civiles y Sanitarios..."*
  - *"Somos una empresa peruana constituida hace más de 8 años dedicada a crear y ejecutar proyectos civiles, industriales y alquiler de maquinaria pesada."*

### 💻 CLUSTER B — Tecnología / TI / telecom (trials fallidos, no convierte)
- Keywords: `Soporte tecnológico`, `Tecnologia de la informacion`, `Cableado estructurado`, `data center`, `diseño web`, `PLATAFORMA DE SOFTWARE`, `SERVICIO DE HOSTING`, `satelital`, `internet satelital`, `Redes LAN/WAN`, BIM (`Modelamiento BIM`, `Implementación BIM`, `Gestión BIM`, `Coordinación BIM`).
- Fraseo real:
  - *"Somos una empresa de tecnología especializada en implementar Redes LAN/WAN, Telecomunicaciones y Enlaces Inalámbricos/Satelitales para entornos críticos y remotos. Desarrollamos Infraestructura TI..."*
  - *"Empresa encargada de realizar la implementación y gestión integral de proyectos con la metodología BIM."*

### 🧪 CLUSTER C — Salud / laboratorio clínico
- Keywords: `PAPANICOLAOU`, `BIOPSIAS`, `LECTURA DE LAMINAS PAP`, `ANALISIS CLINICOS A EMPRESAS`, `TOMA DE MUESTRAS A DOMICILIO`, `TOMA DE MUESTRA IN HOUSE`.
- Fraseo real:
  - *"Laboratorio clínico y anatomía patológica, análisis clínicos, servicio de lectura de láminas de Papanicolaou, estudio de biopsias y piezas quirúrgicas."*

### 🧰 CLUSTER D — Servicios varios / HSE
- HSE: *"Empresa de consultoría, asesoría, monitoreos, auditorías en seguridad, salud ocupacional, medio ambiente, calidad, anticorrupción."* (`seguridad y salud en el trabajo`)
- Facilities: *"Empresa brinda servicios de facilities management, dotación de personal, proyectos y otros."* (`Servicios logísticos`, `Servicio de mantenimiento`)
- Insumos: *"Empresa de venta de insumos de limpieza."*
- Legal: estudio jurídico sin copy ni keywords (pagador activo, plan mayores-premium).

## 4. Insights para adquisición

1. **El rubro que paga es construcción/ingeniería hacia el Estado** (pavimentos, carreteras, vial urbana, energía eléctrica, maquinaria). El mensaje de las landings debe hablarles a ellos primero: alertas "de tu rubro y región", buenas pros de obras, contratos >8 UIT, TDR de obras.
2. **Las keywords libres que escriben = búsquedas transaccionales que usan en Google.** "Pistas y veredas", "movimiento de tierras", "alta tensión" son micro-rubros exactos. Las landings genéricas deberían incluir secciones/filtros por micro-rubro y el buscador público cubrirlas.
3. **Lenguaje real (no de IA):** frases cortas de obra: "ejecuta obras de", "especializada en", "proyectos civiles, industriales y alquiler de maquinaria", "más de X años de experiencia". El copy de landings debe usar estas estructuras ("para empresas que ejecutan obras de infraestructura vial").
4. **Cluster B (TI/telecom/BIM) prueba pero no convierte**: posible falta de fit (procesos pequeños) o que el precio/beneficio no calza; NO invertir en landings dedicadas TI por ahora — el mensaje constructora viene primero.
5. **Objeciones/FAQ:** solo 1 motivo de cancelación registrado ("Problemas técnicos"); trials vencidos no dejan razón → completar tabla de objeciones con datos de GSC/blog y con motivos futuros (añadir captura de motivo en expiración de trial).

## 5. Qué hacer con esto (acciones)
- [ ] Landings P0 con mensaje orientado al CLUSTER A (construcción/ingeniería) usando frases de su copy real.
- [ ] FAQ de landings con objeciones reales del rubro (precio S/49-S/68, RNP, regiones, "¿sirve si nunca vendí al Estado?").
- [ ] Evaluar mostrar en el buscador público los micro-rubros más configurados como chips de filtro rápido.
- [ ] Cuando un trial expire, capturar motivo de salida (hoy no se captura).
