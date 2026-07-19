# Investigación Completa: Portal SEACE 3.0 + API OCDS

---

## PARTE 1: API Oficial OCDS (Contrataciones Abiertas)

**El OECE (Organismo Especializado para las Contrataciones Públicas Eficientes)
tiene una API REST pública oficial** que expone los datos del SEACE en formato
OCDS (Open Contracting Data Standard).

### Características Clave

- **No requiere autenticación** (API pública)
- **No tiene reCAPTCHA ni mecanismos anti-bot**
- **No usa JSF ni ViewState**
- **Formato JSON estructurado bajo estándar internacional OCDS**
- **Datos enriquecidos** (RUC, dirección, teléfono, códigos de producto UNSPSC/CUBSO, etc.)
- **URLs directas a documentos PDF**
- **Paginación REST estándar** con links `next`/`prev`

### URL Base de la API

```
https://contratacionesabiertas.oece.gob.pe/api/v1/
```

### Portal Web (Frontend)

```
https://contratacionesabiertas.oece.gob.pe/
```

### Stack Tecnológico del Portal

- **Frontend:** Angular (SPA) con Angular Material
- **Documentación API:** Swagger UI embebido
- **CSS:** Bootstrap Grid + Angular Material + Shepherd (tours guiados)
- **Navegación:** Entidades | Proveedores | Búsqueda | API | Descargas

### Endpoints de la API

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET` | `/api/v1/releases` | Lista de releases OCDS (procedimientos de selección) |
| `GET` | `/api/v1/records` | Registros completos OCDS |
| `GET` | `/api/v1/releases/{ocid}` | Release específico por OCID |
| `GET` | `/api/v1/parties` | Entidades (buyers, suppliers, tenderers) |
| `GET` | `/api/v1/awards` | Adjudicaciones |
| `GET` | `/api/v1/contracts` | Contratos |

### Parámetros de Filtro (Query String)

| Parámetro | Tipo | Ejemplo | Descripción |
|-----------|------|---------|-------------|
| `page` | int | `page=1` | Número de página |
| `paginateBy` | int | `paginateBy=10` | Resultados por página |
| `source` | string | `source=seace_v3` | Fuente de datos |
| `year` | string | `year=2026` | Año de convocatoria |

#### Fuentes disponibles (`source`)

| Valor | Descripción |
|-------|-------------|
| `seace_v3` | SEACE versión 3 (actual) |
| `seace_v2` | SEACE versión 2 (legado) |
| `seace_v1` | SEACE versión 1 (legado) |

#### URL de Descargas (Portal Web)

```
https://contratacionesabiertas.oece.gob.pe/descargas?page=1&paginateBy=10&source=seace_v3&year=2026
```

### Ejemplo de Request

```bash
curl -s "https://contratacionesabiertas.oece.gob.pe/api/v1/releases?page=1&paginateBy=2" \
  -H "Accept: application/json"
```

### Respuesta HTTP

```
HTTP 200 OK
Allow: GET, HEAD, OPTIONS
Content-Type: application/json
Vary: Accept
```

---

### Estructura Completa de la Respuesta JSON

#### Nivel Raíz

```json
{
    "version": "1.1",
    "extensions": [...],
    "publishedDate": "2026-07-17T21:02:00.972054-05:00",
    "publisher": {
        "name": "Organismo Supervisor de las Contrataciones del Estado"
    },
    "license": "https://creativecommons.org/licenses/by/4.0/",
    "publicationPolicy": "https://contratacionesabiertas.oece.gob.pe/downloads/politica_publicacion.pdf",
    "releases": [...],
    "links": {
        "next": "https://contratacionesabiertas.oece.gob.pe/api/v1/releases?page=2&paginateBy=2",
        "prev": null
    },
    "uri": "https://contratacionesabiertas.oece.gob.pe/api/v1/releases?page=1&paginateBy=2"
}
```

#### Extensiones OCDS usadas

La API usa las siguientes extensiones del estándar OCDS:

| Extensión | Descripción |
|-----------|-------------|
| `ocds_currencyname_extension` | Nombre de moneda (Soles, Dólar) |
| `ocds_department_extension` | Departamento/región |
| `ocds_datasegmentation_extension` | Segmentación de datos por año/mes |
| `ocds_releasesource_extension` | Fuente de los datos (seace_v3, etc.) |
| `ocds_exchangeRate_extension` | Tipo de cambio |
| `ocds_contract_completion_extension` | Estado de finalización del contrato |
| `ocds_tenderinformation_extension` | Información protegida por ley |
| `ocds_itemstatus_extension` | Estado del ítem (CONVOCADO, etc.) |
| `ocds_releasepublisheddate_extension` | Fecha de publicación del release |
| `ocds_itemtotalvalue_extension` | Valor total del ítem |
| `ocds_statusdetails_extension` | Detalle del estado |
| `ocds_pagination_extension` | Paginación |

#### Estructura de un Release

```json
{
    "ocid": "ocds-dgv273-seacev3-2026-28-87",
    "id": "ocds-dgv273-seacev3-2026-28-87-2026-07-17T10:27:18.615982-05:00",
    "date": "2026-07-17T10:27:18.615970-05:00",
    "publishedDate": "2026-07-17T10:27:18.615988-05:00",
    "tag": ["planning", "tender"],
    "initiationType": "tender",

    "buyer": {
        "id": "PE-CONSUCODE-28",
        "name": "PODER JUDICIAL"
    },

    "planning": {
        "budget": {
            "description": "Fondos Públicos",
            "project": "Nombre del proyecto (opcional)",
            "projectID": "CUI o código de proyecto (opcional)"
        }
    },

    "tender": {
        "id": "1234784",
        "title": "CP-ABR-2-2026-C-CSJTU-PJ-3",
        "description": "Descripción completa del objeto de contratación",
        "procuringEntity": {
            "id": "PE-CONSUCODE-28",
            "name": "PODER JUDICIAL"
        },
        "datePublished": "2026-07-16T23:14:00-05:00",
        "procurementMethod": "open",
        "procurementMethodDetails": "Concurso Público Abreviado",
        "mainProcurementCategory": "services",
        "additionalProcurementCategories": ["services"],
        "value": {
            "amount": 0.0,
            "currency": "PEN",
            "currencyName": "Soles",
            "amount_PEN": 0.0
        },
        "tenderPeriod": {
            "startDate": "2026-07-16T00:00:00-05:00",
            "endDate": "2026-07-16T00:00:00-05:00"
        },
        "enquiryPeriod": {
            "startDate": "2026-07-17T00:01:00-05:00",
            "endDate": "2026-07-21T23:59:00-05:00",
            "durationInDays": 4
        },
        "tenderers": [
            {
                "id": "PE-RUC-10421145801",
                "name": "CENTENO GUEVARA RICARDO"
            }
        ],
        "numberOfTenderers": 3,
        "items": [
            {
                "id": "21332257",
                "position": "1",
                "description": "Descripción del ítem",
                "statusDetails": "CONVOCADO",
                "status": "active",
                "classification": {
                    "id": "7818150100231998",
                    "description": "SERVICIO DE MANTENIMIENTO PREVENTIVO DE MOTOCICLETA",
                    "scheme": "CUBSO"
                },
                "additionalClassifications": [
                    {
                        "id": "78181501",
                        "description": "Servicio de pintura y reparación de vehículo",
                        "scheme": "UNSPSC"
                    }
                ],
                "quantity": 1.0,
                "totalValue": {
                    "amount": 0.0,
                    "currency": "PEN",
                    "currencyName": "Soles"
                },
                "unit": {
                    "id": "36",
                    "name": "Servicio",
                    "scheme": "PE-SEACE3-UnidadMedida"
                }
            }
        ],
        "documents": [
            {
                "id": "24831474986031627",
                "url": "https://prod1.seace.gob.pe/SeaceWeb-PRO/SdescargarArchivoAlfresco?fileCode=...",
                "datePublished": "2026-07-16T23:14:00-05:00",
                "format": "pdf",
                "documentType": "biddingDocuments",
                "title": "Bases Administrativas",
                "language": "es"
            }
        ],
        "hasTenderInformationProtectedByLaw": false
    },

    "awards": [
        {
            "id": "1234645-20568024878",
            "value": { "amount": 760050.0, "currency": "PEN" },
            "items": [...],
            "date": "2026-07-16T00:00:00-05:00",
            "suppliers": [
                {
                    "id": "PE-RUC-20568024878",
                    "name": "A Y A CORPORACION REAL S.A.C."
                }
            ]
        }
    ],

    "parties": [
        {
            "id": "PE-CONSUCODE-28",
            "name": "PODER JUDICIAL",
            "identifier": {
                "id": "28",
                "scheme": "PE-CONSUCODE",
                "legalName": "PODER JUDICIAL"
            },
            "additionalIdentifiers": [
                {
                    "id": "20159981216",
                    "scheme": "PE-RUC",
                    "legalName": "PODER JUDICIAL"
                }
            ],
            "address": {
                "streetAddress": "Av. Colmena (Nicolás de Piérola) 745 LIMA",
                "locality": "LIMA",
                "region": "LIMA",
                "department": "LIMA",
                "countryName": "PERU"
            },
            "contactPoint": {
                "telephone": "4265010",
                "email": "Ver datos en Ficha del Proveedor"
            },
            "roles": ["buyer", "procuringEntity"]
        }
    ],

    "sources": [
        {
            "id": "seace_v3",
            "name": "Sistema Electrónico de Contrataciones del Estado - Versión 3",
            "url": "https://prodapp2.seace.gob.pe/seacebus-uiwd-pub/buscadorPublico/buscadorPublico.xhtml"
        }
    ],

    "dataSegmentation": {
        "id": "2026-07",
        "criteria": ["añoInicioConvocatoria", "mesInicioConvocatoria"]
    }
}
```

### Mapeo de Campos: API OCDS → Portal SEACE

| Campo API OCDS | Equivalente en Portal SEACE |
|----------------|---------------------------|
| `buyer.name` | Nombre o Sigla de la Entidad |
| `tender.datePublished` | Fecha y Hora de Publicación |
| `tender.title` | Nomenclatura |
| `tender.mainProcurementCategory` | Objeto de Contratación (`services`=Servicio, `goods`=Bien, `works`=Obra) |
| `tender.description` | Descripción de Objeto (completa, sin truncar) |
| `planning.budget.projectID` | Código Único de Inversión (CUI) |
| `tender.value.amount` | VR / VE / Cuantía |
| `tender.value.currencyName` | Moneda (Soles / Dólar Norteamericano) |
| `tender.procurementMethodDetails` | Tipo de Selección |
| `tender.items[].statusDetails` | Estado (CONVOCADO, etc.) |
| `awards[].suppliers[].name` | Proveedor adjudicado |
| `parties[].additionalIdentifiers[scheme=PE-RUC].id` | RUC de la entidad |
| `parties[].address` | Dirección completa |
| `documents[].url` | URL directa al PDF (bases, informes, etc.) |

### Esquemas de Identificación usados

| Esquema | Descripción | Ejemplo |
|---------|-------------|---------|
| `PE-CONSUCODE` | Código de entidad del OSCE | `28` = PODER JUDICIAL |
| `PE-RUC` | RUC de la entidad/proveedor | `20159981216` |
| `CUBSO` | Catálogo Único de Bienes, Servicios y Obras | `7818150100231998` |
| `UNSPSC` | United Nations Standard Products and Services Code | `78181501` |
| `PE-SEACE3-UnidadMedida` | Unidad de medida SEACE3 | `36`=Servicio, `40`=Unidad |

### Tipos de Documento (`documentType`)

| Valor | Descripción |
|-------|-------------|
| `biddingDocuments` | Bases (Administrativas o Integradas) |
| `evaluationReports` | Informes de evaluación |
| `clarifications` | Actas de consultas y observaciones |
| `contractSigned` | Contrato firmado |

### Categorías de Contratación (`mainProcurementCategory`)

| Valor | Objeto |
|-------|--------|
| `goods` | Bien |
| `services` | Servicio |
| `works` | Obra |

### Roles en `parties`

| Rol | Descripción |
|-----|-------------|
| `buyer` | Entidad compradora |
| `procuringEntity` | Entidad encargada de la contratación |
| `tenderer` | Postor |
| `supplier` | Proveedor adjudicado |

### Tags (Estado del Release)

| Tag | Significado |
|-----|-------------|
| `planning` | En planificación |
| `tender` | En convocatoria |
| `award` | Adjudicado |
| `contract` | Contratado |
| `implementation` | En ejecución |

### Valor `amount` = 0.0

Cuando el valor es `0.0` y `hasTenderInformationProtectedByLaw = false`,
significa que el valor referencial no fue publicado por la entidad o
está pendiente de registrarse. Si es `true`, el valor está reservado
por ley (ej: FFAA).

### Ventajas API OCDS vs Scraping SEACE Directo

| Característica | API OCDS | Scraping SEACE |
|---------------|----------|----------------|
| Autenticación | Ninguna | Session + ViewState |
| Anti-bot | Ninguno | reCAPTCHA v3 |
| Formato | JSON estructurado | XML/HTML (parse complejo) |
| Descripción del objeto | Completa | Truncada a ~50 caracteres |
| RUC de la entidad | Incluido | No visible directamente |
| Dirección de la entidad | Incluida | No visible directamente |
| Clasificación UNSPSC/CUBSO | Incluida | No disponible |
| URLs de documentos | Directas al PDF | Enlaces JSF encriptados |
| Proveedores/Postores | Identificados con RUC | No visibles en buscador público |
| Adjudicaciones | Incluidas (monto, fecha, proveedor) | No disponibles en esta vista |
| Paginación | REST estándar (`links.next`) | JSF DataTable AJAX |
| Histórico | Todos los años | Limitado por año en formulario |
| Descarga masiva | Posible vía API | Manual por página |

---

## PARTE 2: Portal SEACE Directo — Buscador Público (Scraping)

> **Nota:** Esta sección documenta el análisis detallado del portal directo
> del SEACE. SOLO USAR si la API OCDS no cubre una necesidad específica.
> El scraping directo requiere sortear reCAPTCHA v3, sesiones JSF/PrimeFaces
> y ViewState. Es significativamente más complejo.

### URL Base del Buscador Público

```
https://prod2.seace.gob.pe/seacebus-uiwd-pub/buscadorPublico/buscadorPublico.xhtml
```

### Arquitectura Técnica del Portal

- **Backend:** Java Server Faces (JSF) sobre PrimeFaces
- **Servidor de aplicaciones:** WildFly/JBoss con balanceo de carga (slave3)
- **Comunicación:** AJAX parcial (`javax.faces.partial.ajax=true`)
- **Formato payload (request):** `application/x-www-form-urlencoded`
- **Formato respuesta:** `text/xml` (`<partial-response>` con CDATA HTML)
- **Sesiones:** `JSESSIONID` con tracking de URL (`;jsessionid=...`)
- **Anti-bot:** Google reCAPTCHA v3
- **Captura de IP:** `https://api.ipify.org` (se envía en campo `ipClienteIpify`)

### Pestañas (Tabs) del Buscador

El portal es un `TabView` de PrimeFaces con 6 pestañas. Cada pestaña
es un formulario JSF independiente dentro del mismo `TabView`.

| Tab Index | ID del Tab | Form ID | Nombre |
|-----------|-----------|---------|--------|
| 0 | `tbBuscador:tab0` | `tbBuscador:idFormbuscarACF` | Anuncio de Contratación Futura |
| 1 | `tbBuscador:tab1` | `tbBuscador:idFormBuscarProceso` | **Buscador de Procedimientos de Selección** |
| 2 | `tbBuscador:tab2` | `tbBuscador:idFormbuscarexpresionInteres` | Buscador de Expresiones de Interés |
| 3 | `tbBuscador:tab3` | `tbBuscador:idFormbuscarDifusionRequerimientos` | Buscador de Difusión de Requerimientos |
| 4 | `tbBuscador:tab4` | `tbBuscador:idFormbuscarOCOS` | Buscador Público de Órdenes de Compra y Servicio |
| 5 | `tbBuscador:tab5` | `tbBuscador:idFormbuscarCCO` | Buscador de Condiciones de Contratación |

---

### Flujo Completo de una Consulta

#### Paso 1: GET Inicial

```
GET https://prod2.seace.gob.pe/seacebus-uiwd-pub/buscadorPublico/buscadorPublico.xhtml
```

**Lo que se obtiene:**
- Cookie `JSESSIONID` (con balanceo de carga, ej: `-ncC-pMa_S6Q6G4UPoBr8jzBwvAWzRW8gvFDc4uE.slave3:seace-main`)
- `ViewState` inicial (valor numérico compuesto: `-2085843740595564679:1896819167252217703`)
- HTML de la página con el TabView y los formularios

#### Paso 2: Cambio de Pestaña (Tab Change)

Cuando el usuario hace clic en la pestaña 1 ("Buscador de Procedimientos de
Selección"), se dispara un evento `tabChange` del TabView.

**POST** a `buscadorPublico.xhtml`:

```
javax.faces.partial.ajax=true
javax.faces.source=tbBuscador
javax.faces.partial.execute=tbBuscador
javax.faces.partial.render=tbBuscador:idFormbuscarACF:pnlBuscarProceso+tbBuscador:idFormbuscarACF:pnlGrdResultadosAnuncioContratacionFutura+tbBuscador:idFormBuscarProceso:pnlBuscarProceso+tbBuscador:idFormBuscarProceso:pnlGrdResultadosProcesos+tbBuscador:idFormbuscarexpresionInteres:pnlBuscarProceso+tbBuscador:idFormbuscarexpresionInteres:pnlGrdResultadosExpReconstruccion+tbBuscador:idFormbuscarDifusionRequerimientos:pnlBuscarProceso+tbBuscador:idFormbuscarDifusionRequerimientos:pnlGrdResultadosExpReconstruccion+tbBuscador:idFormbuscarOCOS:pnlBuscarProceso+tbBuscador:idFormbuscarOCOS:pnlGrdResultadosOCOS+tbBuscador:idFormbuscarCCO:pnlBuscarProceso+tbBuscador:idFormbuscarCCO:pnlGrdResultadosExpReconstruccion
javax.faces.behavior.event=tabChange
javax.faces.partial.event=tabChange
tbBuscador_newTab=tbBuscador:tab1
tbBuscador_tabindex=1
tbBuscador:idFormbuscarACF=tbBuscador:idFormbuscarACF
tbBuscador:idFormbuscarACF:hddNumeroRuc=
tbBuscador:idFormbuscarACF:nombreEntidad=
tbBuscador:idFormbuscarACF:cbxTipoSeleccion_input=
tbBuscador:idFormbuscarACF:cbxTipoSeleccion_focus=
tbBuscador:idFormbuscarACF:cbxObjContratacion_input=
tbBuscador:idFormbuscarACF:cbxObjContratacion_focus=
tbBuscador:idFormbuscarACF:dfechaInicioPubACF_input=
tbBuscador:idFormbuscarACF:dfechaFinPubACF_input=
tbBuscador:idFormbuscarACF:descripcionObjeto=
tbBuscador:idFormbuscarACF:dfechaInicioAproxConvACF_input=
tbBuscador:idFormbuscarACF:dfechaFinAproxConvACF_input=
tbBuscador:idFormbuscarACF:tokenBusACF=
tbBuscador:idFormbuscarACF:txtNombreEntidad=
tbBuscador:idFormbuscarACF:txtRucEntidad=
tbBuscador:idFormbuscarACF:txtsigla=
javax.faces.ViewState=<VIEWSTATE>
```

**La respuesta XML incluye:**
- `pnlBuscarProceso` actualizado con el formulario de búsqueda de la pestaña 1
- `pnlGrdResultadosProcesos` con la tabla vacía inicial
- Nuevo `ViewState`

#### Paso 3: Búsqueda (Click en "Buscar")

El botón "Buscar" **visible** es `btnBuscarSelToken`. No es un submit button real
sino un `type="button"`. Su `onclick` llama a `cargaTokenBuscadorProSel()`.

El botón "Buscar" **oculto** (`btnBuscarSel`) tiene `style="display:none"` y es
el verdadero `type="submit"` que dispara el POST JSF.

**Flujo real al hacer clic:**

1. Usuario hace clic en `btnBuscarSelToken` (el botón visible)
2. Se ejecuta `cargaTokenBuscadorProSel()`
3. La función obtiene un token reCAPTCHA v3 de Google
4. Asigna el token al campo oculto `tokenBusProSel`
5. Hace `boton.click()` sobre `btnBuscarSel` (el botón oculto)
6. El botón oculto dispara `PrimeFaces.ab(...)` que envía el POST AJAX

**POST enviado por `btnBuscarSel`:**

```
javax.faces.partial.ajax=true
javax.faces.source=tbBuscador:idFormBuscarProceso:btnBuscarSel
javax.faces.partial.execute=@all
javax.faces.partial.render=tbBuscador:idFormBuscarProceso:pnlGrdResultadosProcesos+tbBuscador:idFormBuscarProceso:footerBuscador+frmMesajes:gPrincipal+tbBuscador:idFormBuscarProceso:btnBuscarSel+tbBuscador:idFormBuscarProceso:pnlBuscarProceso
tbBuscador:idFormBuscarProceso:btnBuscarSel=tbBuscador:idFormBuscarProceso:btnBuscarSel
submit=S
tbBuscador:idFormBuscarProceso=tbBuscador:idFormBuscarProceso
tbBuscador:idFormBuscarProceso:numPositionTabView=1
tbBuscador:idFormBuscarProceso:hddNumeroRuc=
tbBuscador:idFormBuscarProceso:nombreEntidad=
tbBuscador:idFormBuscarProceso:j_idt211_input=
tbBuscador:idFormBuscarProceso:j_idt211_focus=
tbBuscador:idFormBuscarProceso:j_idt220_input=
tbBuscador:idFormBuscarProceso:j_idt220_focus=
tbBuscador:idFormBuscarProceso:numeroSeleccion=
tbBuscador:idFormBuscarProceso:descripcionObjeto=
tbBuscador:idFormBuscarProceso:anioConvocatoria_input=2026
tbBuscador:idFormBuscarProceso:anioConvocatoria_focus=
tbBuscador:idFormBuscarProceso:j_idt247_input=3
tbBuscador:idFormBuscarProceso:j_idt247_focus=
tbBuscador:idFormBuscarProceso:codigoSnip=
tbBuscador:idFormBuscarProceso:CUI=
tbBuscador:idFormBuscarProceso:siglasEntidad=
tbBuscador:idFormBuscarProceso:j_idt276_input=
tbBuscador:idFormBuscarProceso:j_idt276_focus=
tbBuscador:idFormBuscarProceso:departamento_input=
tbBuscador:idFormBuscarProceso:departamento_focus=
tbBuscador:idFormBuscarProceso:provincia_input=
tbBuscador:idFormBuscarProceso:provincia_focus=
tbBuscador:idFormBuscarProceso:distrito_input=
tbBuscador:idFormBuscarProceso:distrito_focus=
tbBuscador:idFormBuscarProceso:numeroConvocatoria=
tbBuscador:idFormBuscarProceso:j_idt309_input=
tbBuscador:idFormBuscarProceso:j_idt309_focus=
tbBuscador:idFormBuscarProceso:dfechaInicio_input=
tbBuscador:idFormBuscarProceso:dfechaFin_input=
tbBuscador:idFormBuscarProceso:j_idt266_collapsed=true
tbBuscador:idFormBuscarProceso:tokenBusProSel=<TOKEN_RECAPTCHA>
tbBuscador:idFormBuscarProceso:ipClienteIpify=<IP_PUBLICA>
tbBuscador:idFormBuscarProceso:txtNombreEntidad=
tbBuscador:idFormBuscarProceso:txtRucEntidad=
tbBuscador:idFormBuscarProceso:txtsigla=
javax.faces.ViewState=<VIEWSTATE>
```

---

### Parámetros del Formulario de Búsqueda (Filtros)

#### Filtros Básicos (visibles siempre)

| Parámetro | Tipo | Descripción | Valores Conocidos |
|-----------|------|-------------|-------------------|
| `nombreEntidad` | text (readonly) | Nombre o Sigla de Entidad | Rellenado vía popup buscador |
| `hddNumeroRuc` | hidden | RUC de la entidad seleccionada | |
| `j_idt211_input` | select | Tipo de Selección | Ver tabla abajo |
| `j_idt220_input` | select | Objeto de Contratación | `""`, `62`=Bien, `63`=Consultoría de Obra, `64`=Obra, `65`=Servicio |
| `numeroSeleccion` | text | Nro. Selección | Solo dígitos, max 20 |
| `descripcionObjeto` | text | Descripción del Objeto | Texto libre, max 250 chars |
| `anioConvocatoria_input` | select | Año de Convocatoria (*requerido) | `2026` a `2004` |
| `j_idt247_input` | select | Versión SEACE | `2`=Seace 2, `3`=Seace 3 |
| `codigoSnip` | text | Código SNIP | max 20 chars, formato específico |
| `CUI` | text | Código Único de Inversión | max 7 dígitos |

#### Tipos de Selección (`j_idt211_input`) — Valores completos

| Valor | Etiqueta |
|-------|----------|
| `""` | [Seleccione] (Todos) |
| `790` | Adjudicación Abreviada |
| `66` | Adjudicación de Menor Cuantía |
| `171` | Adjudicación de Menor Cuantía - Centésima DCF Ley Nº 30114 |
| `270` | Adjudicación de Menor Cuantía - DU Nº 004-2014 |
| `67` | Adjudicación de Menor Cuantía - DU Nº 130-2001 |
| `68` | Adjudicación de Menor Cuantía - DL Nº 1023 |
| `69` | Adjudicación de Menor Cuantía - DL Nº 1017 (Experto independiente) |
| `70` | Adjudicación de Menor Cuantía - Ley Nº 26859 |
| `71` | Adjudicación de Menor Cuantía - Ley Nº 27638 |
| `230` | Adjudicación de Menor Cuantía - Ley Nº 30191 |
| `390` | Adjudicación de Menor Cuantía - Ley Nº 30306 |
| `72` | Adjudicación de Menor Cuantía - Octava DCF del DL Nº 1017 |
| `73` | Adjudicación Directa Pública |
| `74` | Adjudicación Directa Selectiva |
| `1018` | Adjudicación para Contrato Marco |
| `835` | Adjudicación Selectiva |
| `271` | Adjudicación Simplificada |
| `530` | Adjudicación Simplificada - Décima DCF Reg. Ley 30225 |
| `1636` | Adjudicación Simplificada - DU 012-2023 |
| `1657` | Adjudicación Simplificada - DU 032-2023 |
| `1696` | Adjudicación Simplificada - DU 034-2023 |
| `550` | Adjudicación Simplificada - Ley Nº 26859 |
| `770` | Adjudicación Simplificada - Ley Nº 30556 |
| `1376` | Adjudicación Simplificada - Ley N° 31125 |
| `1536` | Adjudicación Simplificada - Ley N° 31579 |
| `1576` | Adjudicación Simplificada - Ley N° 31589 |
| `1616` | Adjudicación Simplificada - Ley N° 31728 |
| `1115` | Adjudicación Simplificada - Séptima DCF Reg. Ley 30225 |
| `1480` | Adjudicación Simplificada - DU 102-2021 |
| `1296` | Adjudicación Simplificada - DU 114-2020 |
| `670` | Adjudicación Simplificada - DL N° 1325 |
| `975` | Adjudicación Simplificada - DL N° 1355 |
| `1676` | Adjudicación Simplificada - DL N° 1577 |
| `730` | Adjudicación Simplificada - Homologación |
| `1736` | Asistencia Técnica Especializada |
| `1887` | Asociación para la Innovación |
| `386` | Comparación de Precios |
| `690` | Comparación de Precios - DL N° 1314 |
| `1886` | Compra Pública Precomercial |
| `104` | Compras por catálogo (Convenio Marco) |
| `1055` | Concurso de Proyectos Arquitectónicos |
| `1864` | Concurso Proyecto Arquitectónico y Urbanístico |
| `75` | Concurso Público |
| `1883` | Concurso Público Abreviado |
| `1916` | Concurso Público Abreviado - Ley N°26859 |
| `1917` | Concurso Público Abreviado - Ley N°31589 |
| `1861` | Concurso Público Abreviado Emergencia |
| `1860` | Concurso Público Abreviado Homologación |
| `2038` | Concurso Público Abreviado Séptima DCF Ley N°32069 |
| `1863` | Concurso Público con Diálogo Competitivo |
| `1862` | Concurso Público con Precalificación |
| `1881` | Concurso Público de Servicios |
| `1882` | Concurso Público para Consultoría |
| `1884` | Concurso Público para Evaluadores Expertos |
| `1885` | Concurso Público para Gerentes de Proyectos |
| `293` | Contratación Directa |
| `77` | Contratación Directa (Petroperú) |
| `76` | Contratación Internacional |
| `78` | Contratación por Competencia Mayor |
| `79` | Contratación por Competencia Menor |
| `1737` | Contratos Estandarizados |
| `80` | Convenio |
| `81` | Exoneración |
| `935` | Extensión de Catálogo Electrónico |
| `915` | Implementación de Catálogo Electrónico |
| `916` | Incorporación de Proveedores |
| `82` | Licitación Pública |
| `1877` | Licitación Pública Abreviada |
| `1918` | Licitación Pública Abreviada - Ley N°26859 |
| `1936` | Licitación Pública Abreviada - Ley N°31589 |
| `1857` | Licitación Pública Abreviada Emergencia |
| `1878` | Licitación Pública Abreviada Homologación |
| `2037` | Licitación Pública Abreviada Séptima DCF Ley N°32069 |
| `1879` | Licitación Pública con Diálogo Competitivo |
| `1880` | Licitación Pública con MDA |
| `1859` | Licitación Pública con Negociación |
| `1858` | Licitación Pública con Precalificación |
| `1876` | Licitación Pública Especializada |
| `1237` | Procedimiento de Contratación para Proceso Abierto |
| `1238` | Procedimiento de Contratación para Proceso Lista Corta |
| `511` | Procedimiento Especial de Contratación |
| `1156` | Procedimiento Especial de Contratación - Nueva Convocatoria por Desierto |
| `83` | Procedimiento Especial de Selección |
| `855` | Proceso por Competencia |
| `84` | Regímen Especial |
| `385` | Selección de Consultores Individuales |
| `384` | Subasta Inversa Electrónica |
| `570` | Subasta Inversa Electrónica Corporativa |

#### Años disponibles en `anioConvocatoria_input`

```
2026, 2025, 2024, 2023, 2022, 2021, 2020, 2019, 2018, 2017, 2016,
2015, 2014, 2013, 2012, 2011, 2010, 2009, 2008, 2007, 2006, 2005, 2004
```

#### Filtros de Búsqueda Avanzada (Fieldset colapsable `j_idt266`)

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `siglasEntidad` | text | Sigla Nomenclatura |
| `j_idt276_input` | select | Modalidad de Selección (Abreviada, Clásico, Acuerdo Marco, etc.) |
| `departamento_input` | select | Departamento (carga dinámica provincias al cambiar) |
| `provincia_input` | select | Provincia (carga dinámica distritos al cambiar) |
| `distrito_input` | select | Distrito |
| `numeroConvocatoria` | text | Identificador Convocatoria (max 7 dígitos) |
| `j_idt309_input` | select | Tipo de Compra (Corporativa, Por encargo, Por la Entidad) |
| `dfechaInicio_input` | text | Fecha Inicio Publicación (formato `dd/mm/yyyy`) |
| `dfechaFin_input` | text | Fecha Fin Publicación (formato `dd/mm/yyyy`) |
| `j_idt266_collapsed` | hidden | `true` = colapsado, `false` = expandido |

#### Departamentos (`departamento_input`)

| Valor | Nombre |
|-------|--------|
| `2` | AMAZONAS |
| `3` | ANCASH |
| `4` | APURIMAC |
| `5` | AREQUIPA |
| `6` | AYACUCHO |
| `7` | CAJAMARCA |
| `8` | CALLAO |
| `9` | CUSCO |
| `9997` | EXTERIOR |
| `10` | HUANCAVELICA |
| `11` | HUANUCO |
| `12` | ICA |
| `13` | JUNIN |
| `14` | LA LIBERTAD |
| `15` | LAMBAYEQUE |
| `16` | LIMA |
| `17` | LORETO |
| `18` | MADRE DE DIOS |
| `19` | MOQUEGUA |
| `9994` | MULTIDEPARTAMENTAL |
| `20` | PASCO |
| `21` | PIURA |
| `22` | PUNO |
| `23` | SAN MARTIN |
| `24` | TACNA |
| `25` | TUMBES |
| `26` | UCAYALI |

#### Modalidades de Selección (`j_idt276_input`)

| Valor | Nombre |
|-------|--------|
| `1816` | Abreviada |
| `23` | Acuerdo Marco |
| `22` | Clásico |
| `1817` | Comparación de Precios |
| `1818` | Concurso Proyecto Arquitectónico y Urbanístico |
| `1836` | Contratación Directa |
| `370` | Diálogo Competitivo |
| `2016` | Diferenciada |
| `371` | Precalificación |
| `24` | Procedimiento |
| `25` | Procedimiento de Selección Abreviado |
| `381` | Sin Modalidad |
| `26` | Subasta Inversa Electrónica |
| `27` | Subasta Inversa Presencial |

#### Tipos de Compra (`j_idt309_input`)

| Valor | Nombre |
|-------|--------|
| `51` | Compra Corporativa Facultativa |
| `52` | Compra Corporativa Obligatoria |
| `53` | Por encargo a Entidad Internacional |
| `54` | Por encargo a Entidad Privada Nacional |
| `55` | Por encargo a Entidad Pública |
| `56` | Por encargo a Organismo Internacional |
| `57` | Por la Entidad |

---

### Token `tokenBusProSel` — Google reCAPTCHA v3

#### NO es un token anti-CSRF tradicional

El campo `tokenBusProSel` contiene un **token de Google reCAPTCHA v3**,
un servicio de detección de bots que asigna un score de "humanidad"
sin mostrar captchas al usuario.

#### Site Key

```
6Lfhnb0pAAAAAB3RxPrOlihIByQUBjpZCAjX-cY2
```

#### Script cargado

```
https://www.google.com/recaptcha/api.js?render=6Lfhnb0pAAAAAB3RxPrOlihIByQUBjpZCAjX-cY2
```

#### Función JavaScript que genera el token

Ubicación: `/javax.faces.resource/js/utils.js.xhtml?ln=default&v=1_0`

```javascript
// La SITE_KEY se lee de un campo hidden en la página:
var SITE_KEY = document.getElementById('claveRecaptchav3SitioWeb').value;

function cargaTokenBuscadorProSel() {
    var boton = document.getElementById('tbBuscador:idFormBuscarProceso:btnBuscarSel');
    grecaptcha.ready(function() {
        grecaptcha.execute(
            SITE_KEY,
            {action: 'frmBuscadorProcedimientosSeleccion'}
        ).then(function(respuesta_token) {
            const itoken = document.getElementById('tbBuscador:idFormBuscarProceso:tokenBusProSel');
            itoken.value = respuesta_token;
            boton.click(); // Dispara el POST real en el botón oculto
        });
    });
}
```

#### Características del token reCAPTCHA v3

- Es un string largo (500+ caracteres) en Base64/URL-safe
- Tiene una validez de **~2 minutos** desde su generación
- Es validado por Google en el backend del SEACE
- No se puede generar sin ejecutar JavaScript en un navegador real
- Está ligado a la Site Key y al dominio `seace.gob.pe`

#### Implicaciones para automatización

| Método | ¿Funciona? | Razón |
|--------|------------|-------|
| `curl` / `requests` con token copiado | **No** | El token expira en ~2 minutos |
| Generar token sin navegador | **Imposible** | Requiere ejecutar JS de Google con la Site Key correcta |
| Navegador headless (Playwright/Selenium) | **Sí** | reCAPTCHA v3 se ejecuta normalmente en navegadores automatizados |
| Servicio de resolución (2captcha, etc.) | **Sí** | Ofrecen resolución de reCAPTCHA v3 Enterprise |

---

### Todos los Tokens reCAPTCHA del Portal

Cada pestaña del buscador tiene su propio token y función JS:

| Pestaña | Función JS | Action reCAPTCHA | Campo del token |
|---------|------------|------------------|-----------------|
| Procedimientos de Selección | `cargaTokenBuscadorProSel()` | `frmBuscadorProcedimientosSeleccion` | `tokenBusProSel` |
| Expresiones de Interés | `cargaTokenBuscadorExpInt()` | `frmBuscadorExpresionesInteres` | `tokenBusExpInt` |
| Difusión de Requerimientos | `cargaTokenBuscadorDifReq()` | `frmBuscadorDifusionRequerimientos` | `tokenBusDifReq` |
| Órdenes de Compra/Servicio | `cargaTokenBuscadorOrdComSer()` | `frmBuscadorOrdenesCompraServicios` | `tokenBusOrdComSer` |
| Condiciones de Contratación | `cargaTokenBuscadorCCO()` | `frmBuscarEntidadCCO` | `tokenBusCCO` |
| Anuncio Contratación Futura | `cargaTokenBuscadorACF()` | `frmBuscadorAnuncioContratacionFutura` | `tokenBusACF` |
| Expediente Tribunal | `cargaTokenBuscadorExpTri()` | `frmBuscadorExpedienteTribunal` | `tokenBusExpTri` |

---

### Tres Requisitos para una Consulta Exitosa (vía scraping)

1. **Cookie de sesión (`JSESSIONID`)** — Se obtiene con un GET inicial a la página.
   El servidor usa balanceo de carga con `slave3:seace-main`.

2. **`ViewState`** — Valor generado por JSF que codifica el estado del formulario.
   Viene en el HTML de la página y debe corresponder a la sesión activa.
   Formato: `-2085843740595564679:1896819167252217703` (dos números separados por `:`).

3. **`tokenBusProSel` (reCAPTCHA v3)** — Token generado por Google en tiempo real
   en el navegador.

Los tres deben ser **consistentes entre sí** (misma sesión HTTP).

---

### Respuesta del Servidor (Formato XML)

La respuesta es `text/xml` con estructura JSF `partial-response`:

```xml
<?xml version='1.0' encoding='UTF-8'?>
<partial-response>
  <changes>
    <update id="tbBuscador:idFormBuscarProceso:pnlBuscarProceso">
      <![CDATA[
        <!-- HTML del formulario de búsqueda actualizado -->
      ]]>
    </update>
    <update id="tbBuscador:idFormBuscarProceso:pnlGrdResultadosProcesos">
      <![CDATA[
        <!-- HTML de la tabla "Códigos SNIP" con los resultados -->
      ]]>
    </update>
    <update id="tbBuscador:idFormBuscarProceso:footerBuscador">
      <![CDATA[
        <!-- Footer: "Información Actualizada al: DD de mes del AAAA" -->
      ]]>
    </update>
    <update id="frmMesajes:gPrincipal">
      <![CDATA[
        <!-- Componente Growl de PrimeFaces para mensajes (errores, etc.) -->
      ]]>
    </update>
    <update id="j_id1:javax.faces.ViewState:0">
      <![CDATA[
        <!-- Nuevo ViewState para la siguiente petición -->
      ]]>
    </update>
  </changes>
</partial-response>
```

### Tabla de Resultados — "Códigos SNIP"

La tabla se renderiza como un `DataTable` de PrimeFaces dentro del componente
`pnlGrdResultadosProcesos`. Columnas:

| # | Columna | Ancho | Contenido |
|---|---------|-------|-----------|
| 1 | N° | auto | Número correlativo (1-15 por página) |
| 2 | Nombre o Sigla de la Entidad | 25% | Nombre completo de la entidad |
| 3 | Fecha y Hora de Publicacion | auto | Formato `DD/MM/YYYY HH:MM` |
| 4 | Nomenclatura | 15% | Código del proceso (ej: `CP-ABR-4-2026-C/MDSA-1`) |
| 5 | Reiniciado Desde | auto | Texto descriptivo si fue reiniciado |
| 6 | Objeto de Contratación | auto | Bien / Servicio / Obra / Consultoría de Obra |
| 7 | Descripción de Objeto | 50% | Descripción (truncada en la UI a ~50 chars) |
| 8 | Código SNIP | 15% | Icono lupa que abre popup con detalle |
| 9 | Código Unico de Inversion | 15% | Icono lupa que abre popup con detalle |
| 10 | VR / VE / Cuantía | 20% | Valor referencial (`---` si no publicado) |
| 11 | Moneda | auto | Soles / Dólar Norteamericano |
| 12 | Versión SEACE | auto | `2` o `3` |
| 13 | Acciones | auto | Iconos: Historial de Contrataciones + Ficha de Selección |

### Paginación de la Tabla

El DataTable de PrimeFaces incluye un paginador con:

- **Información:** `[ Mostrando de 1 a 15 del total 499 - Página: 1/34 ]`
- **Navegación:** First, Prev, números de página (1-10 visibles), Next, Last
- **Selector de filas:** 10, 15, 20 por página
- **Ejemplo real:** 499 registros totales, 34 páginas, 15 por página

La paginación se dispara con evento `page` del DataTable:

```javascript
PrimeFaces.ab({
    source: "tbBuscador:idFormBuscarProceso:dtProcesos",
    event: "page",
    process: "tbBuscador:idFormBuscarProceso:dtProcesos"
});
```

### Enlaces de Acciones en la Tabla

Cada fila tiene enlaces con parámetros encriptados/enconded:

| Acción | Parámetros | Destino |
|--------|-----------|---------|
| Código SNIP (lupa) | `nidConvocatoria`, `nidSistema`, `nidProceso`, `ntipo` | Popup modal `frmListaCodigoSnip` |
| Código Único de Inversión (lupa) | `nidConvocatoria`, `nidSistema`, `nidProceso`, `ntipo` | Popup modal `frmListaCodigoCUI` |
| Historial de Contrataciones | `nidConvocatoria`, `nidProceso`, `nidSistema` | Navega el formulario con `PrimeFaces.addSubmitParam` |
| Ficha de Selección | `ntipo`, `nidConvocatoria`, `nidProceso`, `nidSistema`, `ptoRetorno=LOCAL` | Navega el formulario |

Los valores de `nidConvocatoria` están codificados (ej: `+tx/lkFSP59YsQVQTkJbWSRBlTtOtcSEAr6cmHJd+nRWDwO17dTN`).

---

### Archivos JavaScript del Portal

| Archivo | Ruta | Contenido |
|---------|------|-----------|
| jQuery | `/javax.faces.resource/jquery/jquery.js.xhtml?ln=primefaces` | jQuery (PrimeFaces) |
| PrimeFaces | `/javax.faces.resource/primefaces.js.xhtml?ln=primefaces` | Framework PrimeFaces |
| jQuery Plugins | `/javax.faces.resource/jquery/jquery-plugins.js.xhtml?ln=primefaces` | Plugins jQuery |
| Utilidades SEACE | `/javax.faces.resource/js/utils.js.xhtml?ln=default&v=1_0` | Funciones `cargaTokenBuscador*()`, captura IP vía ipify |
| Calendario ES | `/javax.faces.resource/js/calendar_es.js.xhtml?ln=default&v=1_0` | Configuración de calendario en español |
| Descargas CMS | `/seacebus-uiwd-pub/recurso?nombre=cmsDescarga.js` | Funciones de descarga |
| reCAPTCHA | `https://www.google.com/recaptcha/api.js?render=6Lfhnb0pAAAA...` | Google reCAPTCHA v3 |

### Datos Adicionales Enviados

- **IP del cliente:** Se captura vía `https://api.ipify.org?format=jsonp` y se envía
  en el campo oculto `ipClienteIpify`
- **Clave reCAPTCHA:** Se lee del campo oculto `claveRecaptchav3SitioWeb`

---

### Popup Buscador de Entidades

Cada pestaña tiene un popup modal para buscar entidades:

1. Click en lupa abre `frmBuscarEntidad` (Dialog de PrimeFaces)
2. Campos de búsqueda: Nombre Entidad, N° Ruc, Sigla
3. El popup carga resultados vía AJAX en un DataTable con paginación
4. Muestra las primeras 300 coincidencias
5. Al seleccionar una entidad, se cierra el popup y se rellena `nombreEntidad` + `hddNumeroRuc`

---

### Estrategia de Automatización (si se requiriera scraping)

#### Opción A: API OCDS (RECOMENDADA)

```python
import requests

url = "https://contratacionesabiertas.oece.gob.pe/api/v1/releases"
params = {
    "page": 1,
    "paginateBy": 15,
    "source": "seace_v3",
    "year": 2026
}
response = requests.get(url, params=params)
data = response.json()

for release in data["releases"]:
    print(release["buyer"]["name"])
    print(release["tender"]["title"])
    print(release["tender"]["description"])
    print(release["tender"]["value"]["currencyName"],
          release["tender"]["value"]["amount"])

# Paginación
if data["links"]["next"]:
    next_page = requests.get(data["links"]["next"]).json()
```

#### Opción B: Playwright (solo si la API no cubre algo)

```python
# Flujo conceptual:
# 1. browser = playwright.chromium.launch()
# 2. page = browser.new_page()
# 3. page.goto("https://prod2.seace.gob.pe/seacebus-uiwd-pub/buscadorPublico/buscadorPublico.xhtml")
# 4. Esperar que cargue reCAPTCHA + PrimeFaces
# 5. Hacer clic en la pestaña "Buscador de Procedimientos de Selección" (tab1)
# 6. Seleccionar año en dropdown "anioConvocatoria"
# 7. Opcional: llenar otros filtros
# 8. Hacer clic en botón "Buscar" (btnBuscarSelToken)
#    → El JS de la página obtiene token reCAPTCHA automáticamente
#    → Luego hace clic en btnBuscarSel que envía el POST
# 9. Esperar respuesta AJAX (tabla de resultados en pnlGrdResultadosProcesos)
# 10. Extraer datos de la tabla HTML (filas <tr data-ri="...">)
# 11. Para paginar: hacer clic en números de página del paginador
```

**Ventajas de Playwright:**
- Ejecuta JavaScript real (reCAPTCHA funciona nativamente)
- Anti-detección de headless (usa Chromium real)
- Manejo de esperas automáticas (`waitForSelector`, `waitForResponse`)
- Puede interceptar respuestas de red para capturar datos JSON/XML

---

## Conclusión Final

**Usar la API OCDS** (`contratacionesabiertas.oece.gob.pe/api/v1/`) es la opción
correcta para cualquier proyecto. Es una API REST pública, sin autenticación,
sin mecanismos anti-bot, con datos enriquecidos en formato estándar internacional
OCDS. Incluye información que el portal público no muestra (RUC, dirección,
proveedores, adjudicaciones, URLs directas a documentos).

El scraping directo del portal SEACE (`prod2.seace.gob.pe`) solo se justifica si:
- Se necesita información en **tiempo real** con menos de 1 día de retraso
- Se requieren campos muy específicos de la ficha de selección no mapeados a OCDS
- Se necesita acceder a los popups de detalle (Código SNIP, CUI)

En cualquier otro caso, **la API es superior en todos los aspectos**.
