# 🔍 BUSCADOR PÚBLICO SEACE

## 📋 Descripción

Vista especializada para acceder a los **contratos públicos del SEACE** sin necesidad de autenticación.

## ✅ Endpoint Público Funcional

**El SEACE SÍ ofrece un endpoint público para búsqueda de contratos:**

```
https://prod6.seace.gob.pe/v1/s8uit-services/buscadorpublico/contrataciones/buscador
```

### ✅ Disponibles Públicamente (Sin Token)
- ✅ **Búsqueda de Contratos**: Por palabra clave, departamento, objeto, estado, entidad
- ✅ **Departamentos**: Lista completa de departamentos del Perú
- ✅ **Provincias**: Por departamento
- ✅ **Distritos**: Por provincia
- ✅ **Objetos de Contratación**: Bien, Servicio, Obra, Consultoría
- ✅ **Estados de Contratación**: Vigente, En Evaluación, Culminado, etc.
- ✅ **Autocompletado de Entidades**: Búsqueda de entidades públicas

### ⚠️ Diferencias con Endpoint Autenticado
- ❌ **Archivos TDR**: Requiere autenticación
- ❌ **Cotizaciones**: Requiere autenticación
- ❌ **Detalles completos**: Algunos campos requieren autenticación

---

## ✨ Características

### 🎯 Funcionalidad Principal
- ✅ **Sin autenticación**: No requiere tokens ni credenciales
- ✅ **Búsqueda por palabra clave**: Busca en descripción de procesos
- ✅ **Autocompletado de entidades**: Buscar entidades dinámicamente (mín. 3 caracteres)
- ✅ **Filtros básicos**: Objeto de contratación y estado
- ✅ **Filtros geográficos**: Departamento → Provincia → Distrito (cascada)
- ✅ **Paginación completa**: 10, 20, 50 o 100 resultados por página
- ✅ **Filtros persistentes en URL**: Comparte búsquedas con URL
- ✅ **Diseño "Sequence"**: Paleta de colores profesional

### 🎨 UX/UI Optimizado
- 🟢 **Filtros colapsables**: Muestra/oculta filtros avanzados
- 🟢 **Debouncing automático**: 500ms en campos de texto
- 🟢 **Loading states**: Indicadores visuales de carga
- 🟢 **Responsive design**: Adaptable a móviles
- 🟢 **Estados visuales**: Colores diferenciados por estado de contrato
- 🟢 **Badge de filtros activos**: Contador visual de filtros aplicados

---

## 🛠️ Arquitectura Técnica

### Componentes

```
📁 app/Services/
  └── SeaceBuscadorPublicoService.php       # Servicio sin autenticación

📁 app/Livewire/
  └── BuscadorPublico.php                   # Componente principal

📁 resources/views/
  ├── buscador-publico.blade.php            # Vista wrapper
  └── livewire/
      └── buscador-publico.blade.php        # Vista del componente
```

### Flujo de Datos

```
Usuario → Vista Livewire → BuscadorPublico (Component)
                              ↓
                    SeaceBuscadorPublicoService
                              ↓
                     API SEACE (Sin token)
                              ↓
                    Resultados → Vista → Usuario
```

---

## 📡 Endpoint Utilizado

### Base URL
```
https://prod6.seace.gob.pe/v1/s8uit-services/buscadorpublico/contrataciones/buscador
```

### Método
`GET`

### Headers (No requiere Authorization)
```http
Accept: application/json, text/plain, */*
Accept-Language: es-419,es;q=0.9
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36
Origin: https://prod6.seace.gob.pe
Referer: https://prod6.seace.gob.pe/busqueda/buscadorContrataciones
```

### Query Parameters (Parámetros Obligatorios y Opcionales)

#### **Obligatorios**
| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| `anio` | integer | **OBLIGATORIO** - Año de publicación | `2024` |
| `orden` | integer | Ordenamiento (1=Asc, 2=Desc) | `2` |
| `page` | integer | Número de página (default: 1) | `1` |
| `page_size` | integer | Resultados por página (5-100) | `20` |

#### **Filtros Opcionales**
| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| `palabra_clave` | string | Búsqueda en descripción | `laptop` |
| `codigo_entidad` | string | Código de entidad (CONSUCODE) | `0123456789` |
| `lista_objeto_contrato` | integer | ID del objeto (1=Bien, 2=Servicio, 3=Obra, 4=Consultoría) | `2` |
| `lista_estado_contrato` | integer | ID del estado (2=Vigente, etc.) | `2` |
| `codigo_departamento` | integer | ID del departamento | `15` (Lima) |
| `codigo_provincia` | integer | ID de la provincia | `128` |
| `codigo_distrito` | integer | ID del distrito | `1301` |

### Ejemplo de URL Completa

```
https://prod6.seace.gob.pe/v1/s8uit-services/buscadorpublico/contrataciones/buscador?anio=2024&lista_estado_contrato=2&codigo_departamento=1&palabra_clave=&orden=2&page=1&page_size=5
```

### Respuesta de Ejemplo

```json
{
    "data": [
        {
            "secuencia": 1,
            "idContrato": 41822,
            "nroContratacion": 3,
            "desContratacion": "CM-3-2026-EMUSAP SA.",
            "idObjetoContrato": 2,
            "nomObjetoContrato": "Servicio",
            "desObjetoContrato": "CONTRATACIÓN DEL SERVICIO DE APOYO TÉCNICO...",
            "fecIniCotizacion": "04/02/2026 13:00:00",
            "fecFinCotizacion": "09/02/2026 23:00:00",
            "cotizar": true,
            "idEstadoContrato": 2,
            "nomEstadoContrato": "Vigente",
            "fecPublica": "04/02/2026 12:09:19",
            "idTipoCotizacion": 2,
            "nomEntidad": "EMPRESA MUNICIPAL DE AGUA POTABLE..."
        }
    ],
    "pageable": {
        "pageNumber": 1,
        "pageSize": 5,
        "totalElements": 13
    }
}
```

---

## 🎨 Paleta de Colores (Sequence Design)

### Primary (Teal)
- `primary-500`: `#025964` - Botones principales, enlaces activos
- `primary-400`: `#2A737D` - Hover states
- `primary-100`: `#A4C3C6` - Fondos sutiles

### Secondary (Mint Green)
- `secondary-500`: `#00D47E` - Estados "Vigente", éxito
- `secondary-400`: `#29DA93` - Hover
- `secondary-100`: `#A4EFD1` - Fondos

### Neutral (Grises)
- `neutral-900`: `#111827` - Títulos
- `neutral-600`: `#4B5563` - Textos body
- `neutral-400`: `#9CA3AF` - Subtítulos
- `neutral-100`: `#F3F4F6` - Bordes
- `neutral-50`: `#F9FAFB` - Fondo general

### Estados de Contratos
- **Vigente**: `bg-secondary-500/10 text-secondary-500`
- **En Evaluación**: `bg-yellow-100 text-yellow-700`
- **Culminado**: `bg-neutral-100 text-neutral-600`

---

## 💻 Uso del Servicio (PHP)

### Ejemplo Básico

```php
use App\Services\SeaceBuscadorPublicoService;

$buscador = new SeaceBuscadorPublicoService();

// Búsqueda simple
$resultado = $buscador->buscarContratos([
    'palabra_clave' => 'laptop',
    'lista_estado_contrato' => 2, // Vigente
    'page' => 1,
    'page_size' => 20,
]);

if ($resultado['success']) {
    $contratos = $resultado['data'];
    $totalElementos = $resultado['pagination']['total_elements'];
    
    foreach ($contratos as $contrato) {
        echo $contrato['desContratacion'] . "\n";
        echo $contrato['nomEntidad'] . "\n";
    }
}
```

### Obtener Catálogos Maestros

```php
// Departamentos (cacheado 1h)
$deptos = $buscador->obtenerDepartamentos();

// Provincias por departamento
$provincias = $buscador->obtenerProvincias(15); // Lima

// Distritos por provincia
$distritos = $buscador->obtenerDistritos(128); // Lima - Lima

// Objetos de contratación (cacheado 1h)
$objetos = $buscador->obtenerObjetosContratacion();

// Estados de contratación (cacheado 1h)
$estados = $buscador->obtenerEstadosContratacion();
```

### Autocompletado de Entidades

```php
// Buscar entidades (mínimo 3 caracteres)
$entidades = $buscador->buscarEntidades('ministerio de la mujer');

if ($entidades['success']) {
    foreach ($entidades['data'] as $entidad) {
        echo $entidad['razonSocial'] . "\n";
        echo "Código: " . $entidad['codConsucode'] . "\n";
    }
}
```

---

## 🔧 Uso del Componente Livewire

### Propiedades Públicas

```php
// Filtros (sincronizados con URL)
public string $palabraClave = '';
public string $entidadTexto = '';
public string $codigoEntidad = '';
public int $objetoContrato = 0;
public int $estadoContrato = 0;
public int $departamento = 0;
public int $provincia = 0;
public int $distrito = 0;
public int $pagina = 1;
public int $registrosPorPagina = 20;

// Estados
public bool $buscando = false;
public bool $cargandoFiltros = false;
public bool $mostrarFiltrosAvanzados = false;
public bool $mostrarSugerenciasEntidades = false;

// Datos
public array $resultados = [];
public array $paginacion = [];
public array $objetos = [];
public array $estados = [];
public array $departamentos = [];
public array $provincias = [];
public array $distritos = [];
```

### Métodos Principales

```php
// Realizar búsqueda
public function buscar(): void

// Limpiar todos los filtros
public function limpiarFiltros(): void

// Cambiar de página
public function irAPagina(int $numeroPagina): void

// Cambiar registros por página
public function cambiarRegistrosPorPagina(int $cantidad): void

// Autocompletado de entidades (debounced)
public function buscarEntidades(): void

// Seleccionar entidad del dropdown
public function seleccionarEntidad(string $razonSocial, string $codigoConsucode): void

// Toggle filtros avanzados
public function toggleFiltrosAvanzados(): void

// Contar filtros activos (para badge)
public function contarFiltrosActivos(): int
```

### Ejemplo de Uso en Blade

```blade
@livewire('buscador-publico')
```

---

## 📊 Estructura de Respuesta

### Respuesta Exitosa

```json
{
    "success": true,
    "data": [
        {
            "idContrato": 40651,
            "desContratacion": "CM-19-2026-MDH/CM",
            "nomEntidad": "MUNICIPALIDAD DISTRITAL DE HUACHON",
            "nomObjetoContrato": "Servicio",
            "desObjetoContrato": "SERVICIO DE AUXILIAR ADMINISTRATIVO...",
            "nomEstadoContrato": "Vigente",
            "fecPublica": "29/01/2026 23:29:01",
            "fecIniCotizacion": "02/02/2026 08:00:00",
            "fecFinCotizacion": "02/02/2026 17:30:00"
        }
    ],
    "pagination": {
        "current_page": 1,
        "page_size": 20,
        "total_elements": 15432,
        "total_pages": 772
    }
}
```

### Respuesta con Error

```json
{
    "success": false,
    "error": "Error al consultar el SEACE",
    "status": 500
}
```

---

## 🚀 Instalación y Configuración

### 1. Variables de Entorno

Asegúrate de tener configurado en `.env`:

```env
SEACE_BASE_URL=https://prod6.seace.gob.pe/v1/s8uit-services
SEACE_FRONTEND_ORIGIN=https://prod6.seace.gob.pe
```

### 2. Cache

El sistema cachea automáticamente:
- ✅ Departamentos: 1 hora
- ✅ Objetos de contratación: 1 hora
- ✅ Estados de contratación: 1 hora

Para limpiar cache:

```bash
php artisan cache:clear
```

### 3. Acceso

Navega a: `http://tu-dominio/buscador-publico`

O usa el enlace en el sidebar: **Buscador Público**

---

## 📱 Características Responsive

### Desktop (> 1024px)
- Filtros en grid 2 columnas
- Tabla completa con todas las columnas
- Hero con icono SVG decorativo

### Tablet (768px - 1024px)
- Filtros en grid 2 columnas
- Tabla scrollable horizontal
- Sidebar colapsable

### Mobile (< 768px)
- Filtros en 1 columna
- Tabla scrollable horizontal
- Cards en lugar de tabla (opcional mejorar)

---

## 🔍 Ejemplo de URLs Compartibles

Gracias al binding con URL (`#[Url(keep: true)]`), puedes compartir búsquedas:

```
/buscador-publico?palabraClave=laptop&estadoContrato=2&departamento=15&pagina=1
```

Esto pre-cargará los filtros y ejecutará la búsqueda automáticamente.

---

## 🎯 Mejoras Futuras (Roadmap)

- [ ] Export a Excel/PDF
- [ ] Vista de tarjetas (cards) en lugar de tabla
- [ ] Filtro por rango de fechas
- [ ] Filtro por rango de montos
- [ ] Guardar búsquedas favoritas
- [ ] Notificaciones de nuevos procesos
- [ ] Gráficos y estadísticas
- [ ] Comparador de procesos

---

## 🧪 Testing

### Pruebas Manuales

1. **Búsqueda básica**: Ingresa palabra clave y presiona "Buscar"
2. **Autocompletado**: Escribe 3+ caracteres en "Entidad"
3. **Filtros geográficos**: Selecciona Departamento → se cargan Provincias
4. **Paginación**: Navega entre páginas
5. **URL persistente**: Actualiza página y verifica que filtros persistan
6. **Limpiar filtros**: Verifica que todo se resetee

### Casos Edge

- ✅ Búsqueda sin resultados → Mensaje amigable
- ✅ Error de servidor → Mensaje de error con detalles
- ✅ Timeout de petición → Muestra error
- ✅ Filtros vacíos → No hace búsqueda inicial

---

## 📚 Referencias

- **Endpoint público**: `/buscadorpublico/contrataciones`
- **Diferencia con endpoint autenticado**: `/contratacion/contrataciones/buscador`
- **Documentación API SEACE**: Ver `API_SEACE_ENDPOINTS.md`
- **Diseño Sequence**: Ver `SEACE DESARROLLO.instructions.md`

---

**Versión:** 1.0  
**Fecha:** 5 de febrero de 2026  
**Autor:** Sistema Vigilante SEACE  
**Estado:** ✅ Funcional y en producción
