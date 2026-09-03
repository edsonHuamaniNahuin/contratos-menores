Prompts SEACE Mayores Ocho UIT
Arquitectura de Prompts y Auditoría Algorítmica para la Detección de Direccionamiento en Contratos Mayores a 8 UIT bajo la Ley N° 32069
Introducción al Nuevo Ecosistema de Contratación Pública
La implementación de inteligencia artificial generativa, específicamente a través de modelos fundacionales como Claude 3.5 Sonnet, para el análisis y auditoría de documentos de contratación pública representa un avance tecnológico sin precedentes en la mitigación de riesgos de cumplimiento y la optimización de procesos de licitación. Hasta la fecha, los sistemas de procesamiento de lenguaje natural (NLP) aplicados a los Términos de Referencia (TDR) y Especificaciones Técnicas (ET) han operado con una eficiencia notable en el segmento de las contrataciones menores, es decir, aquellas cuyo monto es inferior o igual a ocho Unidades Impositivas Tributarias (UIT). No obstante, la transición operativa hacia la auditoría de procedimientos de selección competitivos (aquellos que superan el umbral de las 8 UIT) requiere una reingeniería profunda de los esquemas de extracción de datos y de la lógica de inferencia forense.   

El marco regulatorio peruano ha experimentado una metamorfosis estructural con la promulgación y posterior entrada en vigencia de la Ley N° 32069, Ley General de Contrataciones Públicas, reglamentada mediante el Decreto Supremo N° 009-2025-EF y sus modificatorias posteriores. Este nuevo cuerpo normativo abandona el enfoque estrictamente formalista de su predecesora (Ley N° 30225) para adoptar un modelo de gestión por resultados fundamentado en el principio rector de "Valor por Dinero", el cual prioriza la eficiencia, la eficacia, la economía y la sostenibilidad a lo largo de todo el ciclo de vida de la contratación. Paralelamente, el ecosistema institucional ha evolucionado con la transformación del Organismo Supervisor de las Contrataciones del Estado (OSCE) hacia el Organismo Especializado para las Contrataciones Públicas Eficientes (OECE), y la consolidación de la Plataforma Digital para las Contrataciones Públicas (Pladicop) como el eje central de trazabilidad, reemplazando progresivamente al Sistema Electrónico de Contrataciones del Estado (SEACE).   

En este contexto de alta complejidad regulatoria, intentar evaluar un contrato mayor a 8 UIT utilizando un prompt diseñado para compras directas constituye un error de diseño arquitectónico. Los contratos menores se caracterizan por su agilidad, donde la entidad contratante solicita cotizaciones simples y selecciona la oferta basándose casi exclusivamente en el precio y el plazo de entrega, con requisitos mínimos de admisibilidad. Por el contrario, los procedimientos competitivos mayores a 8 UIT (Licitaciones Públicas, Concursos Públicos, Adjudicaciones Simplificadas) están estrictamente sujetos a Bases Estándar aprobadas por el ente rector, las cuales contienen una estructura legal bifurcada y altamente técnica.   

El direccionamiento y la corrupción en estos grandes contratos no se manifiestan de manera burda (como el fraccionamiento simple), sino a través de una "sobrerregulación estratégica". Los malos funcionarios estructuran barreras técnicas mediante la manipulación de factores de evaluación, la inclusión de requisitos de calificación desproporcionados, o la limitación indebida de la participación en consorcio. Por consiguiente, los modelos de lenguaje deben ser recalibrados mediante una inyección de contexto legal específico (few-shot prompting) y esquemas JSON granulares que reflejen la ontología jurídica de la Ley N° 32069 y la jurisprudencia del Tribunal de Contrataciones Públicas.   

Diagnóstico de la Arquitectura de Prompts Actual y sus Limitaciones
El análisis detallado del código fuente proporcionado revela que la clase AnthropicClient gestiona la interacción con la API de Anthropic utilizando prompts estáticos y genéricos. Si bien esta configuración ha demostrado utilidad para el procesamiento de contratos de baja cuantía, presenta vulnerabilidades críticas que impedirán su funcionamiento correcto en escenarios de alta complejidad.   

El método analyze_tdr instruye al modelo a extraer un JSON con claves superficiales tales como resumen_ejecutivo, requisitos_tecnicos, reglas_de_negocio, politicas_y_penalidades y presupuesto_referencial. En el entorno de un procedimiento de selección superior a 8 UIT, el término "requisitos técnicos" carece de validez normativa. Las Bases Estándar dividen las exigencias al proveedor en dos categorías inmiscibles: los Requisitos de Calificación (que determinan la capacidad legal, técnica y profesional mínima para ejecutar el contrato) y los Factores de Evaluación (que otorgan un puntaje para definir al ganador de la Buena Pro). Al fusionar ambos conceptos en un solo campo, el modelo de lenguaje perderá la capacidad de distinguir entre una condición excluyente (Pasa/No Pasa) y una ventaja competitiva opcional, lo que derivará en una evaluación de compatibilidad defectuosa para la empresa usuaria del sistema.   

Asimismo, el método analyze_direccionamiento implementa una plantilla forense que restringe los hallazgos a categorías como "Técnica", "Experiencia", "Personal", "Puntaje", y "Fraccionamiento". Esta taxonomía es anacrónica para el nuevo régimen. El "Fraccionamiento", por ejemplo, es una tipología de fraude que busca precisamente evadir la aplicación de la ley para mantener la compra dentro del umbral de las 8 UIT. En una Licitación Pública de diez millones de soles, el fraccionamiento no es la técnica empleada; el direccionamiento se ejecuta a través de tácticas de exclusión (lock-out tactics) como la exigencia de visitas técnicas obligatorias con sellos específicos de la entidad, certificaciones ISO no vinculadas al objeto de la contratación, o la definición de marcas específicas sin admitir equivalencias.   

Adicionalmente, el método de generación de proformas (generate_proforma) asume una estructura de cotización lineal. En los procedimientos competitivos, la oferta económica debe presentarse siguiendo formatos estrictos (Anexos de las Bases Estándar) que incluyen el análisis de precios unitarios, gastos generales, utilidades y la contemplación de costos asociados a las garantías exigidas, tales como la Garantía de Fiel Cumplimiento equivalente al 10% del contrato. Omitir estas variables en la generación algorítmica de la proforma expondría al proveedor a riesgos financieros severos y a la posible descalificación de su oferta.   

Determinación de Umbrales y Procedimientos Competitivos
Para que el modelo de lenguaje pueda aplicar la heurística correcta, el sistema debe ser capaz de identificar el umbral económico del proceso auditado. La Ley N° 32069 establece una correlación directa entre el monto del Valor Estimado (para bienes y servicios) o Valor Referencial (para obras) y la rigurosidad del procedimiento de selección.   

El análisis algorítmico debe sustentarse en la identificación precisa del procedimiento, dado que los márgenes se actualizan anualmente mediante la Ley de Presupuesto del Sector Público. Considerando la proyección de la Unidad Impositiva Tributaria (UIT) para el año fiscal 2026 fijada en S/ 5,500, la estratificación de los procedimientos competitivos se estructura de la siguiente manera, marcando el fin de la compra directa y el inicio del régimen general:   

Tipo de Procedimiento de Selección	Objeto Contractual	Rango Económico (S/ para el año 2026)
Contrato Menor (No Competitivo)	Bienes, Servicios y Obras	Inferior o igual a 44,000 (≤ 8 UIT)
Comparación de Precios	Bienes y Servicios (de disponibilidad inmediata)	Mayor a 44,000 y menor o igual a 100,000
Subasta Inversa Electrónica	Bienes y Servicios comunes (con Ficha Técnica)	Mayor a 44,000
Adjudicación Simplificada	Bienes y Servicios	Mayor a 44,000 y menor a 485,000
Licitación Pública Abreviada	Bienes	Mayor a 44,000 y menor a 485,000
Licitación Pública Abreviada	Obras	Mayor a 44,000 y menor a 5'000,000
Concurso Público Abreviado	Servicios y Consultorías	Mayor a 44,000 y menor a 485,000
Licitación Pública	Bienes	Igual o superior a 485,000
Licitación Pública	Obras	Entre 5'000,000 y 79'000,000
Concurso Público	Servicios y Consultorías	Igual o superior a 485,000
La identificación del procedimiento permite al modelo inferir los plazos legales aplicables, un dato crucial para el análisis de viabilidad. Por ejemplo, en una Licitación Pública general, la norma exige un plazo no menor de siete días hábiles desde la integración de bases hasta la presentación de ofertas. En modalidades abreviadas (Licitación Pública Abreviada o Concurso Público Abreviado), este plazo puede reducirse a tres días hábiles, mientras que en procesos sin etapa de consultas y observaciones el límite mínimo es de seis días desde la convocatoria. Si el modelo de lenguaje no extrae esta temporalidad, la herramienta fallará en advertir al usuario sobre la ventana crítica de oportunidad.   

Auditoría Forense de los Requisitos de Calificación
El componente más crítico para la evaluación algorítmica en contratos superiores a 8 UIT reside en la disección de los Requisitos de Calificación. Estos requisitos constituyen el filtro primario de admisibilidad; su incumplimiento genera el rechazo automático de la oferta. Los prompts de extracción deben obligar al modelo a mapear cada una de las subcategorías estandarizadas por la normativa vigente, evaluando simultáneamente su proporcionalidad.   

Capacidad Legal y Representación
La evaluación de la capacidad legal exige la verificación de las habilitaciones necesarias para llevar a cabo la actividad económica materia de la contratación. El modelo debe identificar si el TDR requiere inscripciones en registros especiales (por ejemplo, autorizaciones del Ministerio de Transportes y Comunicaciones para servicios de telecomunicaciones, o certificaciones de la Superintendencia Nacional de Control de Servicios de Seguridad, Armas, Municiones y Explosivos de Uso Civil - SUCAMEC para vigilancia). La exigencia de autorizaciones no vinculadas directamente al núcleo del objeto contractual es una señal inequívoca de direccionamiento institucional.   

Capacidad Técnica, Profesional y Personal Clave
La capacidad técnica se centra en el equipamiento estratégico y la infraestructura física que el proveedor debe garantizar. Por su parte, la evaluación del personal clave (residentes de obra, gerentes de proyecto, especialistas técnicos) es frecuentemente manipulada para favorecer a postores predeterminados.   

La inteligencia artificial debe auditar la exigencia de calificaciones, formación académica y experiencia del personal clave. La jurisprudencia del tribunal supervisor ha determinado reiteradamente que establecer años de experiencia desproporcionados (por ejemplo, exigir quince años de experiencia específica para el mantenimiento de un software común) o requerir capacitaciones en tecnologías propietarias que solo posee un distribuidor (fenómeno de Brand Directing) vulnera el principio de competencia. El nuevo prompt debe capturar estas métricas de tiempo y especialidad, contrastándolas algorítmicamente contra la complejidad real del proyecto para emitir una alerta de riesgo.   

Experiencia del Postor en la Especialidad
La Ley N° 32069 establece topes máximos para la acreditación de experiencia previa, buscando evitar barreras de entrada excesivas. Para la contratación de bienes y servicios, la experiencia facturada exigible puede llegar hasta tres veces el valor estimado de la contratación en un horizonte retrospectivo de hasta diez años. En el caso de obras, los umbrales varían, permitiendo en algunos casos requerir facturación equivalente a una o dos veces el valor referencial para obras similares. El modelo de lenguaje debe extraer el monto referencial, calcular el tope legal permitido y verificar si las bases publicadas exceden dicho límite. Todo requerimiento que sobrepase estos márgenes reglamentarios debe ser clasificado automáticamente como un acto de sobrerregulación o direccionamiento severo.   

Condiciones de Participación en Consorcio
La figura del consorcio permite a pequeñas y medianas empresas (MYPE) complementar capacidades técnicas y financieras para acceder a contratos estatales complejos. No obstante, las entidades tienen la potestad de establecer condiciones limitantes "en función a la naturaleza de la prestación", lo que incluye determinar un número máximo de integrantes, un porcentaje mínimo de participación global por cada consorciado (comúnmente fijado entre el 5% y el 10%), y un porcentaje mínimo obligatorio de participación en la ejecución del contrato para aquel miembro que aporte la mayor experiencia (usualmente estipulado en un 20% o 50%).   

El direccionamiento a través de la restricción consorcial ocurre cuando se prohíbe arbitrariamente la asociación o se imponen porcentajes tan altos que anulan la viabilidad comercial de la colaboración (por ejemplo, exigir que el miembro con mayor experiencia asuma el 80% de la participación en un consorcio de tres empresas). Los prompts actualizados deben obligar al modelo a extraer específicamente las variables: numero_maximo_consorciados, porcentaje_minimo_por_miembro y porcentaje_minimo_mayor_experiencia, para que la lógica de la aplicación determine si la empresa del usuario requiere formar alianzas para ser competitiva y si las reglas impuestas son jurídicamente cuestionables.   

Estructura y Valoración de los Factores de Evaluación
A diferencia de los requisitos de calificación, los Factores de Evaluación no determinan la aptitud del proveedor, sino el grado de excelencia de su oferta técnica y económica frente a la competencia. En este escenario, la Ley N° 32069 ha consolidado el principio de "Valor por Dinero", desterrando el antiguo paradigma donde la adjudicación recaía inevitablemente en la propuesta de menor costo, independientemente de su calidad integral a largo plazo.   

El puntaje máximo total siempre se fijará en cien (100) puntos, distribuidos entre una evaluación técnica y una evaluación económica. La evaluación de la oferta económica, salvo excepciones específicas en modalidades de consultoría donde se realiza de manera posterior a la evaluación técnica, opera de forma simultánea. Resulta crítico instruir al LLM sobre la ponderación legal de los factores económicos; por ejemplo, en la Licitación Pública Abreviada para bienes homologados, el factor precio puede ponderar hasta setenta puntos, pero en otros procedimientos competitivos la normativa exige asignar un peso significativo a la calidad y la eficiencia, estableciendo un límite máximo de cuarenta puntos para el factor precio a fin de dar cabida a los componentes cualitativos.   

El ecosistema normativo actual incorpora una matriz de factores facultativos y obligatorios que el modelo fundacional debe evaluar rigurosamente:

Categoría del Factor de Evaluación	Criterios Específicos Evaluados	Implicancias y Riesgos de Direccionamiento
Sostenibilidad Ambiental y Social	
Políticas de reducción de emisiones, ISO 14001, uso de materiales reciclados, inclusión de personal vulnerable.

Riesgo alto si se otorgan puntajes desproporcionados (ej. 30 puntos) en contratos donde el impacto ambiental es marginal.
Integridad en la Contratación	
Sistema de Gestión Antisoborno (ISO 37001), políticas de compliance y ética corporativa certificada.

Otorgar puntajes excesivos beneficia injustamente a grandes corporaciones frente a las MYPE que no pueden costear certificaciones internacionales complejas.

Mejoras Operativas	
Extensiones de garantía comercial, disponibilidad de repuestos garantizada, mantenimiento preventivo adicional.

El direccionamiento ocurre cuando la "mejora" solicitada coincide exactamente con un servicio de valor añadido exclusivo del proveedor actual.
Capacitación	
Transferencia tecnológica y capacitación al personal de la entidad contratante tras la entrega del bien u obra.

Exigir capacitadores con certificaciones exclusivas o en idiomas extranjeros sin justificación técnica debidamente fundamentada en el expediente.
  
El modelo analítico debe examinar la distribución de los 100 puntos. Si un factor de "Integridad" (ISO 37001) o de "Sostenibilidad" acapara una porción sustancial del puntaje técnico (por encima del 15% al 20%), el sistema debe levantar una alerta de "Puntaje Subjetivo Desproporcionado". La inteligencia artificial debe reconocer que, si bien la promoción de prácticas responsables es un mandato legal de la nueva norma, la ponderación debe estar directamente vinculada y ser estrictamente proporcional al objeto contractual y a la magnitud de los recursos públicos comprometidos.   

Penalidades, Garantías y Ejecución Contractual
El análisis previo a la cotización no puede limitarse a la fase de selección; la viabilidad económica de un contrato público mayor a 8 UIT está indisolublemente ligada al esquema de garantías y penalidades que rige la etapa de ejecución contractual.   

El reglamento de la ley prescribe que la penalidad máxima por mora en la ejecución de la prestación asciende al diez por ciento (10%) del monto del contrato vigente, calculada de forma diaria mediante una fórmula reglamentaria. Asimismo, las entidades pueden tipificar "otras penalidades" por incumplimientos cualitativos distintos al retraso (por ejemplo, ausencia del personal clave, falta de implementos de seguridad, presentación defectuosa de informes), las cuales, de manera acumulativa, pueden alcanzar otro tope máximo del diez por ciento (10%). El LLM debe examinar el capítulo correspondiente de las Bases para garantizar que la entidad no haya diseñado penalidades abusivas o ambiguas que superen estos umbrales legales.   

En el rubro de las garantías, la normativa exige la presentación de una Garantía de Fiel Cumplimiento, equivalente al diez por ciento (10%) del monto original del contrato, como requisito indispensable para su perfeccionamiento. Esta garantía, que tradicionalmente adopta la forma de una carta fianza bancaria o póliza de caución, representa un costo financiero considerable. Sin embargo, la legislación vigente contempla medidas promotoras: las micro y pequeñas empresas (MYPE) tienen el derecho de solicitar que, en lugar de presentar un instrumento financiero externo, la entidad aplique una retención del diez por ciento (10%) sobre los pagos a realizar durante la primera mitad de la ejecución del contrato.   

La arquitectura de los prompts debe asegurar que la IA evalúe si las bases respetan esta prerrogativa legal para las MYPE. Adicionalmente, se debe instruir al modelo para que advierta sobre las condiciones de los "Adelantos" (directos o por materiales), ya que solicitar liquidez inicial requiere la presentación de garantías idénticas por el monto adelantado, impactando severamente el flujo de caja operativo del proveedor.   

Finalmente, los contratos mayores deben incluir obligatoriamente cláusulas relacionadas con la solución de controversias. A diferencia de las contrataciones directas, donde la conciliación es el mecanismo principal, los grandes proyectos exigen pactar arbitraje (frecuentemente institucional, administrado por centros inscritos en el REGAJU bajo la supervisión del OECE) o someterse a una Junta de Prevención y Resolución de Disputas (JPRD), la cual es obligatoria para la ejecución de obras de alta cuantía. Identificar estos parámetros permite a la aplicación medir el riesgo legal latente de la operación.   

Taxonomía Avanzada del Direccionamiento Forense
La detección algorítmica de favoritismos en contratos estatales complejos exige dotar al modelo fundacional de una lógica forense que replique el razonamiento de un auditor gubernamental y de la Comisión de Defensa de la Libre Competencia. Las entidades corrompidas no evaden abiertamente la ley; diseñan el requerimiento de tal forma que solo un postor específico pueda superarlo, excluyendo de facto a sus competidores mediante barreras técnicas y procedimentales inexpugnables.   

La nueva estructura de prompts debe incorporar y evaluar las siguientes tipologías de fraude y colusión:

Visita Técnica Obligatoria Condicionante: El requerimiento de realizar una visita técnica a las instalaciones de la entidad o a la zona del proyecto no es, per se, ilícito. Sin embargo, condicionar la admisión de la oferta técnica a la presentación de una constancia o certificado de visita con sello oficial y firma de un funcionario específico en una fecha única, constituye una práctica que el OECE y la jurisprudencia administrativa han declarado consistentemente ilegal. Esta imposición es una barrera flagrante de acceso que facilita el conocimiento anticipado de los postores y desincentiva la pluralidad. La presencia de esta cláusula en el TDR debe disparar un veredicto de "Altamente Direccionado".   

Referencia Ciega a Marcas, Patentes o Tecnologías Propietarias (Brand Directing): La normativa prohíbe la inclusión de marcas registradas en las especificaciones, salvo en aquellos casos donde la entidad haya seguido previamente el riguroso y formal proceso de "estandarización" de bienes y servicios. Requerir software, maquinaria, vehículos o insumos químicos haciendo alusión a tecnologías patentadas (por ejemplo, exigir técnicos con experiencia exclusiva en "Elevonic 411") sin incorporar la salvedad normativa "o su equivalente", restringe ilegalmente la libre concurrencia. El LLM debe ser capaz de identificar nomenclaturas comerciales restrictivas en contraposición a las características funcionales genéricas.   

Sobrerregulación Subjetiva en Evaluación (Scoring Bias): Consiste en la asignación de puntajes decisivos a condiciones que no guardan relación de razonabilidad ni proporcionalidad con la naturaleza de la prestación. Si la evaluación algorítmica detecta que la balanza de evaluación se inclina significativamente hacia factores blandos (como certificaciones ISO menores o promesas de capacitación redundantes) en desmedro de la calidad técnica demostrable o la eficiencia económica, debe generarse una alerta por manipulación en la metodología de asignación de puntaje.   

Imposición Irracional de Tiempos de Respuesta y Plazos de Entrega: Exigir que un suministro nacional complejo sea entregado en 48 horas tras el perfeccionamiento del contrato, o que el tiempo de respuesta ante fallas técnicas críticas sea menor a dos horas independientemente de la ubicación geográfica, es una estrategia común para garantizar que solo el proveedor que ya se encuentra operando en la entidad (incumbente) o aquel que posee un almacén periférico a la institución pueda participar en el proceso sin incurrir en penalidades masivas.   

Requisitos de Facturación Redundantes: El fraccionamiento o hiper-especificación del requisito de experiencia, demandando que la facturación de obras o servicios similares se haya dado en condiciones ambientales, geográficas o de altitud idénticas a las de la entidad contratante, cuando la técnica de ingeniería subyacente es universal.

La ventaja de procesar estas anomalías mediante un modelo avanzado radica en la posibilidad de proveer, junto con la detección del riesgo, los argumentos jurídicos de impugnación basados en los pronunciamientos vigentes del OECE, facultando al proveedor a formular observaciones formales eficaces en la plataforma Pladicop.

Reingeniería de la Arquitectura de Prompts para la API de Anthropic
A fin de operacionalizar las directrices normativas de la Ley N° 32069 y resolver la incapacidad del sistema actual para procesar contratos mayores a 8 UIT, es indispensable estructurar prompts especializados que obliguen a Claude 3.5 Sonnet a realizar un procesamiento por fases (Chain of Thought implícito en el esquema de salida).

La estrategia de despliegue requiere instanciar una nueva clase, por ejemplo, ContratosMayoresAnthropicClient, que encapsule esta lógica avanzada, reservando la lógica anterior estrictamente para el análisis de compras directas de baja cuantía.

Prompt de Extracción Analítica (Sustituto de analyze_tdr)
El objetivo de este prompt es deconstruir el extenso documento técnico (Bases Estándar) en un modelo de datos tabular, segregando drásticamente las condiciones de admisibilidad de las métricas de evaluación competitiva.

Instrucción de Contexto (System Prompt):

Actúas como un auditor especialista senior del Organismo Especializado para las Contrataciones Públicas Eficientes (OECE) de Perú, con dominio absoluto sobre la Ley N° 32069, su Reglamento, y la estructura de Bases Estándar para procedimientos superiores a 8 UIT. Tu objetivo es procesar expedientes técnicos y Términos de Referencia, abstrayendo la información con extremo rigor jurídico. Debes mantener una separación tajante entre los "Requisitos de Calificación" (condiciones obligatorias de Pasa/No Pasa) y los "Factores de Evaluación" (criterios que otorgan puntaje de 0 a 100). Tu salida debe ser analítica, objetiva y estructurada exclusivamente en formato JSON válido, sin preámbulos ni conclusiones en texto libre.

Estructura del User Prompt:
Analiza el documento adjunto proveniente de las Bases de un procedimiento de selección del Estado Peruano y extrae la información requerida mapeándola estrictamente en la siguiente estructura JSON.
Si una condición no existe en el texto, emplea "null" o arreglos vacíos []. Presta especial atención a la naturaleza de los requisitos exigidos al personal, los montos de experiencia y las condiciones financieras.

Estructura Esperada (JSON):
{
"metadatos_proceso": {
"objeto_principal": "Descripción del bien, servicio u obra",
"sistema_de_contratacion": "Determina si es Suma Alzada, Precios Unitarios, Esquema Mixto, Tarifas o Honorario Fijo basado en el texto",
"valor_monetario_referencial": "Extrae el monto económico estimado, si está disponible",
"modalidad_inferida": "Infiere si es Licitación Pública, Concurso Público o Adjudicación Simplificada basándote en la cuantía y el texto"
},
"requisitos_admisibilidad_y_calificacion": {
"habilitaciones_legales_obligatorias": ["Ej: Registro MTC, Licencias, SUCAMEC, etc."],
"equipamiento_infraestructura": ["Ej: Camionetas, servidores, plantas de asfalto"],
"experiencia_financiera_postor": "Monto acumulado requerido en facturación (Ej. 1.5 veces el valor referencial) y horizonte temporal (Ej. últimos 8 años)",
"perfil_personal_clave": [
{
"cargo": "...",
"formacion_academica": "...",
"experiencia_especifica_obligatoria": "..."
}
]
},
"factores_puntaje_evaluacion": [
{
"factor_nombre": "Ej: Precio, Experiencia Adicional, Sostenibilidad, ISO, Mejoras",
"puntaje_maximo_asignado": "Entero representing points",
"criterio_evaluacion": "Breve descripción de cómo el proveedor obtiene los puntos"
}
],
"parametros_consorcio": {
"permite_consorcio": "boolean",
"limite_maximo_integrantes": "entero o null",
"porcentaje_minimo_individual": "porcentaje o null",
"porcentaje_minimo_mayor_experiencia": "porcentaje o null"
},
"garantias_y_penalidades": {
"porcentaje_garantia_fiel_cumplimiento": "Generalmente 10% o null",
"permite_retencion_mype": "boolean basado en la mención explícita a retención para MYPEs",
"penalidad_mora_tope_maximo": "Porcentaje, usualmente 10%",
"otras_penalidades_tope": "Porcentaje, usualmente 10%"
}
}

Documento a evaluar:
{context}

La implementación de este esquema asegura que los datos extraídos habiliten a la plataforma del usuario a realizar cálculos posteriores exactos sobre el costo de fianzas y la composición de asociaciones empresariales.

Prompt de Auditoría Forense (Sustituto de analyze_direccionamiento)
El análisis de corrupción debe transitar de una evaluación genérica a un sistema de diagnóstico predictivo y prescriptivo, capaz de detectar las barreras artificiales mencionadas anteriormente (barreras de marca, evaluación y procedimentales) e instruir al usuario sobre su viabilidad legal de impugnación.   

Instrucción de Contexto (System Prompt):

Eres un perito informático forense y especialista en control gubernamental peruano adscrito al OECE. Tu función es auditar Bases Estándar de contrataciones mayores a 8 UIT bajo la Ley N° 32069. Evalúas la razonabilidad, la proporcionalidad de las exigencias técnicas y defiendes los principios de Libertad de Concurrencia, Igualdad de Trato y Valor por Dinero. Tienes un profundo conocimiento de los Pronunciamientos del Tribunal de Contrataciones que invalidan exigencias como marcas específicas sin alternativas, visitas técnicas condicionantes con sellos institucionales, y puntajes desproporcionados en certificaciones blandas.

Estructura del User Prompt:
Realiza un escrutinio forense exhaustivo del siguiente extracto de Bases Estándar en busca de indicios de sobrerregulación estratégica, direccionamiento o barreras arbitrarias a la competencia institucional.
Genera ÚNICAMENTE un documento JSON válido de acuerdo a las siguientes reglas estrictas.

Reglas del Análisis:

"score_probabilidad_direccionamiento": Entero de 0 a 100.

Criterios de puntuación penalizada: Exigir marca patente sin "o equivalente" suma 40 pts. Exigir visita técnica de carácter obligatorio con sello excluyente suma 50 pts. Distribuir excesivo puntaje técnico a certificaciones ISO irrelevantes suma 25 pts. Restringir drásticamente la formación de consorcios suma 20 pts. Plazos de ejecución física irrazonablemente cortos suma 30 pts.

"estado_proceso": "CONFORME Y LIMPIO" (0-25), "RIESGO MODERADO" (26-65), "EVIDENCIA CLARA DE DIRECCIONAMIENTO" (66-100).

El array de hallazgos debe categorizar cada problema detectado utilizando EXACTAMENTE una de estas clasificaciones: "Lock_Out_Procedimental", "Brand_Directing_Ilegal", "Sesgo_Evaluacion_Subjetivo", "Experiencia_Irracional", "Limitacion_Consorcial_Injustificada".

Estructura Esperada (JSON):
{
"score_probabilidad_direccionamiento": 0,
"estado_proceso": "...",
"fundamento_analitico_general": "Breve sinopsis de la sanidad del proceso en base al principio de Valor por Dinero.",
"anomalias_detectadas": [
{
"clasificacion_riesgo": "...",
"nivel_impacto": "Critico | Alto | Medio | Bajo",
"extracto_base_sospechoso": "Cita textual del requisito cuestionable",
"analisis_proporcionalidad": "Explicación técnica de por qué este requisito vulnera la competencia o la normativa.",
"argumento_legal_observacion": "Redacción estructurada y formal que el proveedor puede utilizar para observar/impugnar este punto ante el comité o el OECE (citando el principio vulnerado o la jurisprudencia aplicable, ej. la ilegalidad de visitas obligatorias o la estandarización de marcas)."
}
]
}

Documento a auditar:
{context}

Esta modificación transforma el resultado de la aplicación de una métrica pasiva a una herramienta activa de defensa comercial, generando los borradores argumentativos (argumento_legal_observacion) necesarios para que el proveedor obligue al comité de selección a corregir las bases viciadas durante la etapa legal obligatoria de Consultas y Observaciones.   

Reconfiguración de la Generación de Proforma y Viabilidad
El prompt destinado a generar la proforma económica (generate_proforma) debe abandonar el concepto de cotización minorista simple e incorporar los requerimientos de la oferta técnica en procedimientos competitivos.

El modelo debe instruirse para consolidar un análisis de viabilidad que advierta al proveedor sobre las implicancias de flujo de caja. Si la entidad no contempla la retención de garantías para MYPE, el proveedor deberá inmovilizar capital a través de una carta fianza; si los plazos de conformidad (hasta 10 días para emisión) más los plazos de pago (que pueden sumar semanas dependiendo del entregable) extienden el retorno financiero, la proforma algorítmica debe calcular una provisión de riesgos operativos. La generación del documento final (en la salida del JSON) debe reflejar los rubros típicos de un presupuesto de obras o servicios (costo directo, gastos generales, utilidades e IGV), garantizando el cumplimiento de las normativas de la SUNAT e instituciones fiscales.   

Conclusión y Orquestación del Sistema Híbrido
La arquitectura legal introducida por la Ley N° 32069 dictamina un quiebre tecnológico en la forma en que los sistemas algorítmicos deben aproximarse a las compras públicas en el Perú. El modelo de contratación estatal ha sido escindido en dos esferas con requerimientos documentales, procesales y económicos diametralmente opuestos: un universo transaccional simplificado (Contratos Menores ≤ 8 UIT) y un entorno altamente estructurado, regulado y basado en incentivos cualitativos, sostenibilidad e integridad (Procedimientos Competitivos > 8 UIT).   

La pretensión de sostener una única cadena de prompts estáticos en el código fuente para procesar ambos universos inevitablemente resultará en un fallo sistémico. El LLM, al carecer de instrucciones explícitas sobre la dicotomía entre Requisitos de Calificación y Factores de Evaluación, o sobre los topes legales de las penalidades y la estructura de los consorcios, emitirá juicios sesgados o imprecisos. Evaluará como "fraccionamiento" escenarios inexistentes y pasará por alto manipulaciones severas como la asignación desproporcionada de puntaje técnico a certificaciones irrelevantes.   

La solución arquitectónica óptima requiere la implementación de un enrutador inteligente (Router) en la capa de negocio de la aplicación. Este enrutador debe extraer preliminarmente el valor estimado del proceso mediante un modelo ligero o un algoritmo determinista y, en función del cruce de este monto con el límite de las 8 UIT (S/ 44,000 proyectado para 2026), derivar el procesamiento contextual al AnthropicClient original o a la nueva variante de prompts extendidos presentados en este análisis.

Adoptar este ecosistema de prompts avanzados no solo alinea la tecnología con el nuevo rigor del Organismo Especializado para las Contrataciones Públicas Eficientes (OECE), sino que dota a las empresas usuarias de una capacidad analítica profunda, transformando la aplicación de un mero lector de textos en un auditor legal preventivo y prescriptivo que asegura la competitividad en un entorno público altamente riguroso.