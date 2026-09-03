# 📡 DOCUMENTACIÓN COMPLETA - API SEACE

> **Base URL:** `https://prod6.seace.gob.pe/v1/s8uit-services`  
> **Versión:** 1.0  
> **Última actualización:** 30 de enero de 2026

---

## 🔐 1. AUTENTICACIÓN

### 1.1 Login Inicial (POST)

**Endpoint:** `/seguridadproveedor/seguridad/validausuariornp`

**URL Completa:**
```
https://prod6.seace.gob.pe/v1/s8uit-services/seguridadproveedor/seguridad/validausuariornp
```

**Método:** `POST`

**Headers Requeridos:**
```http
Content-Type: application/json
Accept: application/json, text/plain, */*
Accept-Language: es-419,es;q=0.9
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36
Origin: https://prod6.seace.gob.pe
Referer: https://prod6.seace.gob.pe/auth-proveedor/busqueda
```

**Request Body:**
```json
{
    "username": "10485705681",
    "password": "contraseña**"
}
```

**Parámetros:**
| Campo | Tipo | Descripción | Ejemplo |
|-------|------|-------------|---------|
| `username` | string | DNI o RUC del proveedor (10 dígitos para RUC personal) | `"10485705681"` |
| `password` | string | Contraseña de acceso al portal SEACE | `"miPassword123"` |

**Response (200 OK):**
```json
{
    "mensaje": "LA AUTENTICACIÓN DEL PROVEEDOR SE REALIZÓ CORRECTAMENTE",
    "respuesta": true,
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJqdGkiOiJTaXN0ZW1hOFVJVCIsInN1YiI6IjIwNjE0NzA2NDkxIiwiaWF0IjoxNzY5NzI5NTM2LCJleHAiOjE3Njk3Mjk4MzYsImFwcCI6IlBST1ZFRURPUiIsInRpcG9Eb2N1bWVudG8iOiJSVUMiLCJlc3RhZG8iOiJOTyBWSUdFTlRFIiwicm9sZXMiOlsiMTAxLVBST1ZFRURPUiBSTlAiXSwibm9tYnJlQ29tcGxldG8iOiJTVU5RVVBBQ0hBIFMuQS5DLiIsIm5yb0RvY3VtZW50byI6IjIwNjE0NzA2NDkxIiwiZW1haWwiOiJhZG1pbkBzdW5xdXBhY2hhLmNvbSIsInVzZXJuYW1lIjoiMjA2MTQ3MDY0OTEiLCJyZWZyZXNoVG9rZW4iOiJiM2FiOWFhMy05NTE3LTQwMzQtODVjYi1mMWI1MjRhNjliMDMifQ.tynQ5D0NxQCfpiU7blEFWK9S91siA-ilqTB1TAYy3SU",
    "refreshToken": "b3ab9aa3-9517-4034-85cb-f1b524a69b03"
}
```

**Campos de Respuesta:**
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `mensaje` | string | Mensaje descriptivo del resultado |
| `respuesta` | boolean | `true` si la autenticación fue exitosa |
| `token` | string | JWT (JSON Web Token) válido por **5 minutos** |
| `refreshToken` | string | UUID para refrescar el token sin re-autenticar |

**⚠️ IMPORTANTE:**
- El `token` expira en **5 minutos** (300 segundos)
- El `refreshToken` debe guardarse para renovar el token sin contraseña
- Ambos tokens deben almacenarse en la base de datos

**Código Laravel:**
```php
$response = Http::withHeaders([
    'Accept' => 'application/json, text/plain, */*',
    'Accept-Language' => 'es-419,es;q=0.9',
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    'Origin' => 'https://prod6.seace.gob.pe',
    'Referer' => 'https://prod6.seace.gob.pe/auth-proveedor/busqueda',
])
->post('https://prod6.seace.gob.pe/v1/s8uit-services/seguridadproveedor/seguridad/validausuariornp', [
    'username' => '10485705681',
    'password' => 'contraseña',
]);

$data = $response->json();
$accessToken = $data['token'];
$refreshToken = $data['refreshToken'];

// Guardar en BD
$cuenta->actualizarTokens($accessToken, $refreshToken, 300);
```

---

### 1.2 Refresh Token (POST)

**Endpoint:** `/seguridadproveedor/seguridad/tokens/refresh`

**URL Completa:**
```
https://prod6.seace.gob.pe/v1/s8uit-services/seguridadproveedor/seguridad/tokens/refresh
```

**Método:** `POST`

**Headers Requeridos:**
```http
Authorization: Bearer {TOKEN_EXPIRADO}
Content-Type: application/json
Accept: application/json, text/plain, */*
Accept-Language: es-419,es;q=0.9
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36
Origin: https://prod6.seace.gob.pe
Referer: https://prod6.seace.gob.pe/auth-proveedor/busqueda
```

**Request Body:** *(Vacío o no requerido)*

**⚠️ CRÍTICO:** El token expirado se envía en el header `Authorization: Bearer {TOKEN}`

**Response (200 OK):**
```json
{
    "mensaje": "SE ACTUALIZÓ TOKEN",
    "respuesta": true,
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJqdGkiOiJTaXN0ZW1hOFVJVCIsInN1YiI6IjEwNDg1NzA1NjgxIiwiaWF0IjoxNzY5NzQ4NDYyLCJleHAiOjE3Njk3NDg3NjIsImFwcCI6IlBST1ZFRURPUiIsInRpcG9Eb2N1bWVudG8iOiJSVUMiLCJlc3RhZG8iOiJWSUdFTlRFIiwicm9sZXMiOlsiMTAxLVBST1ZFRURPUiBSTlAiXSwibm9tYnJlQ29tcGxldG8iOiJIVUFNQU5JIMORQUhVSU4gRURTT04gSk9SREFOIiwibnJvRG9jdW1lbnRvIjoiMTA0ODU3MDU2ODEiLCJlbWFpbCI6ImVkc29uXzQ1NTVAaG90bWFpbC5jb20iLCJ1c2VybmFtZSI6IjEwNDg1NzA1NjgxIiwicmVmcmVzaFRva2VuIjoiOGIwOWY3ZmEtYmJhZi00MmFhLWIwZWYtYjI1NWM2YmNjNmFmIn0.dHr8VLlH0vLlomOf4Ib5POqovnPZQXkakTaU5NVFHB8",
    "refreshToken": "8b09f7fa-bbaf-42aa-b0ef-b255c6bcc6af"
}
```

**⚠️ IMPORTANTE:**
- El servidor devuelve un **NUEVO** `refreshToken` en cada refresh
- DEBES actualizar **AMBOS** tokens (token + refreshToken) en la base de datos
- El nuevo token también expira en 5 minutos

**Código Laravel:**
```php
$response = Http::withToken($tokenExpirado)
    ->withHeaders([
        'Accept' => 'application/json, text/plain, */*',
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Origin' => 'https://prod6.seace.gob.pe',
        'Referer' => 'https://prod6.seace.gob.pe/auth-proveedor/busqueda',
    ])
    ->post('https://prod6.seace.gob.pe/v1/s8uit-services/seguridadproveedor/seguridad/tokens/refresh');

$data = $response->json();

// ⚠️ ACTUALIZAR AMBOS TOKENS
$cuenta->actualizarTokens(
    $data['token'],
    $data['refreshToken'],
    300
);
```

---

### 1.3 Detección de Token Expirado (401 Unauthorized)

**Response de Error:**
```json
{
    "backendMessage": "ERROR INTERNO.",
    "message": "TOKEN_EXPIRED",
    "url": "http://prod6.seace.gob.pe/v1/s8uit-services/contratacion/error",
    "method": "GET",
    "timestamp": "2026/01/29 23:47:41",
    "errorCode": "TOKEN_EXPIRED"
}
```

**Status Code:** `401 Unauthorized`

**Condición de Detección:**
```php
if ($response->status() === 401 && $response->json('errorCode') === 'TOKEN_EXPIRED') {
    // Ejecutar refresh automático
    $this->refreshToken($cuenta);
    
    // Reintentar la petición original con el nuevo token
    return $this->retryRequest();
}
```

---

## 🔄 2. ESTRATEGIA DE PETICIONES RESILIENTES

### Flujo Automático de Retry on 401

```php
public function fetchWithRetry($url, $params = [])
{
    $cuenta = $this->getCuentaActiva();
    
    // 1️⃣ INTENTO INICIAL con token guardado
    $response = Http::withToken($cuenta->access_token)
        ->withHeaders($this->getHeaders())
        ->get($url, $params);

    // 2️⃣ DETECTAR EXPIRACIÓN
    if ($response->status() === 401 && $response->json('errorCode') === 'TOKEN_EXPIRED') {
        
        Log::info('Token expirado, intentando refresh...', ['cuenta_id' => $cuenta->id]);
        
        // 3️⃣ INTENTAR REFRESH
        if ($this->refreshToken($cuenta)) {
            
            // 4️⃣ REINTENTO con nuevo token
            $cuenta->refresh(); // Recargar desde BD
            return Http::withToken($cuenta->access_token)
                ->withHeaders($this->getHeaders())
                ->get($url, $params);
        }
        
        // 5️⃣ FALLBACK: Login completo si refresh falló
        Log::warning('Refresh falló, haciendo login completo...', ['cuenta_id' => $cuenta->id]);
        $this->fullLogin($cuenta);
        
        // 6️⃣ ÚLTIMO INTENTO después de login
        $cuenta->refresh();
        return Http::withToken($cuenta->access_token)
            ->withHeaders($this->getHeaders())
            ->get($url, $params);
    }

    return $response;
}
```

**Variables de Configuración (.env):**
```env
SEACE_BASE_URL=https://prod6.seace.gob.pe/v1/s8uit-services
SEACE_TOKEN_DURATION=300  # 5 minutos en segundos
```

---

## 📋 3. ENDPOINTS DE DATOS MAESTROS

### 3.1 Autocompletado de Entidades (GET)

**Endpoint:** `/servicio/servicios/obtener-entidades-cubso`

**URL Completa:**
```
https://prod6.seace.gob.pe/v1/s8uit-services/servicio/servicios/obtener-entidades-cubso
```

**Método:** `GET`

**Headers Requeridos:**
```http
Authorization: Bearer {TOKEN}
Accept: application/json, text/plain, */*
Accept-Language: es-419,es;q=0.9
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36
Origin: https://prod6.seace.gob.pe
Referer: https://prod6.seace.gob.pe/cotizacion/contrataciones
```

**Query Parameters:**

| Parámetro | Tipo | Requerido | Descripción | Ejemplo |
|-----------|------|-----------|-------------|---------|
| `descEntidad` | string | ✅ | Texto a buscar en nombre de entidad (mínimo 3 caracteres) | `"ministerio"` |

**⚠️ OPTIMIZACIÓN UX:**
- Implementar **debouncing** (500ms) para evitar peticiones en cada tecla
- Solo buscar cuando el texto tenga mínimo 3 caracteres
- Usar `wire:model.live.debounce.500ms` en Livewire

**Ejemplo URL:**
```
https://prod6.seace.gob.pe/v1/s8uit-services/servicio/servicios/obtener-entidades-cubso?descEntidad=ministerio%20de%20la%20mujer
```

**Response (200 OK):**
```json
{
    "codigo": "200",
    "mensaje": "Procesado correctamente",
    "lista": [
        {
            "razonSocial": "MINISTERIO DE LA MUJER Y POBLACIONES VULNERABLES- ADMINISTRACIÓN GENERAL",
            "codConsucode": "20",
            "idOrganismo": "20"
        }
    ]
}
```

**Campos de Respuesta:**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `codigo` | string | Código HTTP como string |
| `mensaje` | string | Mensaje descriptivo |
| `lista` | array | Array de entidades encontradas |
| `lista[].razonSocial` | string | Nombre completo de la entidad |
| `lista[].codConsucode` | string | Código único de la entidad |
| `lista[].idOrganismo` | string | ID del organismo |

**Código Laravel:**
```php
$entidades = Http::withToken($accessToken)
    ->withHeaders($this->getHeaders())
    ->get('https://prod6.seace.gob.pe/v1/s8uit-services/servicio/servicios/obtener-entidades-cubso', [
        'descEntidad' => 'ministerio',
    ])
    ->json();

// Usar en autocompletado
foreach ($entidades['lista'] as $entidad) {
    echo "<option value='{$entidad['codConsucode']}'>{$entidad['razonSocial']}</option>";
}
```

**Implementación con Livewire + Alpine.js:**
```blade
<div x-data="{ mostrarSugerencias: false }">
    <input
        wire:model.live.debounce.500ms="busqueda"
        @input="$wire.buscarEntidades()"
        @focus="mostrarSugerencias = true"
        placeholder="Buscar entidad (mínimo 3 caracteres)"
    >
    
    @if(!empty($entidadesSugeridas))
        <div x-show="mostrarSugerencias" @click.away="mostrarSugerencias = false">
            @foreach($entidadesSugeridas as $entidad)
                <button wire:click="seleccionarEntidad('{{ $entidad['razonSocial'] }}', '{{ $entidad['codConsucode'] }}')">
                    {{ $entidad['razonSocial'] }}
                </button>
            @endforeach
        </div>
    @endif
</div>
```

---

### 3.2 Listar Objetos de Contratación (GET)

**Endpoint:** `/maestra/maestras/listar-objeto-contratacion`

**URL Completa:**
```
https://prod6.seace.gob.pe/v1/s8uit-services/maestra/maestras/listar-objeto-contratacion
```

**Método:** `GET`

**Headers Requeridos:**
```http
Authorization: Bearer {TOKEN}
Accept: application/json, text/plain, */*
Accept-Language: es-419,es;q=0.9
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36
Origin: https://prod6.seace.gob.pe
Referer: https://prod6.seace.gob.pe/auth-proveedor/busqueda
```

**Response (200 OK):**
```json
[
    {
        "id": 1,
        "nom": "Bien",
        "abr": "."
    },
    {
        "id": 4,
        "nom": "Consultoría de Obra",
        "abr": "."
    },
    {
        "id": 3,
        "nom": "Obra",
        "abr": "."
    },
    {
        "id": 2,
        "nom": "Servicio",
        "abr": "."
    }
]
```

**Código Laravel:**
```php
$objetos = Http::withToken($accessToken)
    ->withHeaders($this->getHeaders())
    ->get('https://prod6.seace.gob.pe/v1/s8uit-services/maestra/maestras/listar-objeto-contratacion')
    ->json();

// Usar en select de filtros
foreach ($objetos as $objeto) {
    echo "<option value='{$objeto['id']}'>{$objeto['nom']}</option>";
}
```

---

### 3.3 Listar Estados de Contratación (GET)

**Endpoint:** `/maestra/maestras/listar-estado-contratacion`

**URL Completa:**
```
https://prod6.seace.gob.pe/v1/s8uit-services/maestra/maestras/listar-estado-contratacion
```

**Método:** `GET`

**Headers:** *(Mismo que 3.1)*

**Response (200 OK):**
```json
[
    {
        "id": 1,
        "nom": "Borrador",
        "abr": "."
    },
    {
        "id": 4,
        "nom": "Culminado",
        "abr": "."
    },
    {
        "id": 3,
        "nom": "En Evaluación",
        "abr": "."
    },
    {
        "id": 2,
        "nom": "Vigente",
        "abr": "."
    }
]
```

**Uso Común:**
```php
// Filtrar solo "Vigente" en el buscador
$params['lista_estado_contrato'] = 2;
```

---

### 3.4 Listar Departamentos (GET)

**Endpoint:** `/maestra/maestras/listar-departamento`

**URL Completa:**
```
https://prod6.seace.gob.pe/v1/s8uit-services/maestra/maestras/listar-departamento
```

**Método:** `GET`

**Headers:** *(Mismo que 3.1)*

**Response (200 OK):**
```json
[
    {
        "id": 1,
        "nom": "AMAZONAS",
        "abr": null,
        "ubigeoInei": "01",
        "ubigeoInei2": "01"
    },
    {
        "id": 2,
        "nom": "ANCASH",
        "abr": null,
        "ubigeoInei": "02",
        "ubigeoInei2": "02"
    },
    {
        "id": 15,
        "nom": "LIMA",
        "abr": null,
        "ubigeoInei": "15",
        "ubigeoInei2": "15"
    }
    // ... resto de departamentos (25 en total)
]
```

---

### 3.5 Listar Provincias por Departamento (GET)

**Endpoint:** `/maestra/maestras/listar-provincia/{id_departamento}`

**URL Completa:**
```
https://prod6.seace.gob.pe/v1/s8uit-services/maestra/maestras/listar-provincia/24
```

**Método:** `GET`

**Headers:** *(Mismo que 3.1)*

**Parámetros de URL:**

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| `id_departamento` | integer | ID del departamento (obtenido del endpoint listar-departamento) | `24` (Tumbes) |

**Response (200 OK):**
```json
[
    {
        "id": 190,
        "nom": "CONTRALMIRANTE VILLAR",
        "abr": null,
        "ubigeoInei": "2401",
        "ubigeoInei2": "2401"
    },
    {
        "id": 191,
        "nom": "TUMBES",
        "abr": null,
        "ubigeoInei": "2403",
        "ubigeoInei2": "2403"
    },
    {
        "id": 192,
        "nom": "ZARUMILLA",
        "abr": null,
        "ubigeoInei": "2402",
        "ubigeoInei2": "2402"
    }
]
```

**Uso Común:**
```php
// Cargar provincias cuando el usuario selecciona un departamento
$provincias = Http::withToken($accessToken)
    ->get("{$baseUrl}/maestra/maestras/listar-provincia/{$idDepartamento}")
    ->json();
```

---

### 3.6 Listar Distritos por Provincia (GET)

**Endpoint:** `/maestra/maestras/listar-distrito/{id_provincia}`

**URL Completa:**
```
https://prod6.seace.gob.pe/v1/s8uit-services/maestra/maestras/listar-distrito/190
```

**Método:** `GET`

**Headers:** *(Mismo que 3.1)*

**Parámetros de URL:**

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| `id_provincia` | integer | ID de la provincia (obtenido del endpoint listar-provincia) | `190` |

**Response (200 OK):**
```json
[
    {
        "id": 1867,
        "nom": "CANOAS DE PUNTA SAL",
        "abr": null,
        "ubigeoInei": "240102",
        "ubigeoInei2": "240102"
    },
    {
        "id": 1868,
        "nom": "CASITAS",
        "abr": null,
        "ubigeoInei": "240103",
        "ubigeoInei2": "240103"
    },
    {
        "id": 1869,
        "nom": "ZORRITOS",
        "abr": null,
        "ubigeoInei": "240101",
        "ubigeoInei2": "240101"
    }
]
```

**Uso Común:**
```php
// Cargar distritos cuando el usuario selecciona una provincia
$distritos = Http::withToken($accessToken)
    ->get("{$baseUrl}/maestra/maestras/listar-distrito/{$idProvincia}")
    ->json();
```

---

### 3.7 Notificaciones Pendientes (GET)

**Endpoint:** `/subsanacion/subsana-notificaciones/pendientes`

**URL Completa:**
```
https://prod6.seace.gob.pe/v1/s8uit-services/subsanacion/subsana-notificaciones/pendientes
```

**Método:** `GET`

**Headers:** *(Mismo que 3.1)*

**Response (200 OK):**
```json
[]
```

**Descripción:** Devuelve un array de notificaciones pendientes de subsanación (generalmente vacío si no hay pendientes).

---

## 🔍 4. ENDPOINT PRINCIPAL: BUSCADOR DE CONTRATOS

### 4.1 Búsqueda de Contratos (GET)

**Endpoint:** `/contratacion/contrataciones/buscador`

**URL Completa:**
```
https://prod6.seace.gob.pe/v1/s8uit-services/contratacion/contrataciones/buscador
```

**Método:** `GET`

**Headers Requeridos:**
```http
Authorization: Bearer {TOKEN}
Accept: application/json, text/plain, */*
Accept-Language: es-419,es;q=0.9
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36
Origin: https://prod6.seace.gob.pe
Referer: https://prod6.seace.gob.pe/auth-proveedor/busqueda
```

**Query Parameters:**

| Parámetro | Tipo | Requerido | Descripción | Ejemplo |
|-----------|------|-----------|-------------|---------|
| `anio` | integer | ✅ | Año de búsqueda | `2024` |
| `ruc` | string | ✅ | RUC del proveedor | `10485705681` |
| `codigo_entidad` | integer | ❌ | Código de la entidad (obtenido del endpoint obtener-entidades-cubso) | `20` |
| `codigo_departamento` | integer | ❌ | ID del departamento (obtenido de listar-departamento) | `24` |
| `codigo_provincia` | integer | ❌ | ID de la provincia (obtenido de listar-provincia) | `190` |
| `codigo_distrito` | integer | ❌ | ID del distrito (obtenido de listar-distrito) | `1867` |
| `cotizaciones_enviadas` | boolean | ❌ | Filtrar cotizaciones enviadas | `false` |
| `invitaciones_por_cotizar` | boolean | ❌ | Filtrar invitaciones pendientes | `false` |
| `lista_estado_contrato` | integer | ❌ | ID del estado (1=Borrador, 2=Vigente, 3=En Evaluación, 4=Culminado) | `2` |
| `lista_objeto_contrato` | string | ❌ | IDs de objetos separados por coma | `1,2,3,4` |
| `palabra_clave` | string | ❌ | Búsqueda en descripción | `"laptop"` |
| `orden` | integer | ❌ | Ordenamiento (1=Ascendente, 2=Descendente) | `2` |
| `page` | integer | ✅ | Número de página | `1` |
| `page_size` | integer | ✅ | Resultados por página (MAX: 100) | `100` |

**⚠️ ESTRATEGIA RECOMENDADA:**
- **SIEMPRE** usar `page_size=100` para minimizar peticiones
- Calcular total de páginas: `totalPages = ceil(totalElements / 100)`

**Ejemplo URL (Búsqueda Completa):**
```
https://prod6.seace.gob.pe/v1/s8uit-services/contratacion/contrataciones/buscador?anio=2024&ruc=10485705681&cotizaciones_enviadas=false&invitaciones_por_cotizar=false&lista_estado_contrato=2&orden=2&page=1&page_size=100
```

**Ejemplo URL (Con Código de Entidad):**
```
https://prod6.seace.gob.pe/v1/s8uit-services/contratacion/contrataciones/buscador?anio=2024&ruc=20614706491&codigo_entidad=20&cotizaciones_enviadas=false&invitaciones_por_cotizar=false&palabra_clave=lima&orden=2&page=1&page_size=100
```

**Ejemplo URL (Con Filtros Geográficos):**
```
https://prod6.seace.gob.pe/v1/s8uit-services/contratacion/contrataciones/buscador?anio=2024&ruc=20614706491&codigo_departamento=24&codigo_provincia=190&codigo_distrito=1867&cotizaciones_enviadas=false&invitaciones_por_cotizar=false&orden=2&page=1&page_size=5
```

**Ejemplo URL (Con Palabra Clave):**
```
https://prod6.seace.gob.pe/v1/s8uit-services/contratacion/contrataciones/buscador?anio=2024&ruc=10485705681&cotizaciones_enviadas=false&invitaciones_por_cotizar=false&palabra_clave=laptop&orden=2&page=1&page_size=5
```

**Response (200 OK):**
```json
{
    "data": [
        {
            "secuencia": 1,
            "idContrato": 40651,
            "nroContratacion": 19,
            "desContratacion": "CM-19-2026-MDH/CM",
            "idObjetoContrato": 2,
            "nomObjetoContrato": "Servicio",
            "desObjetoContrato": "SERVICIO DE AUXILIAR ADMINISTRATIVO PARA LA OFICINA DE ASESORIA LEGAL",
            "nomEtapaContratacion": "ETAPA DE COTIZACIÓN",
            "fecIniCotizacion": "02/02/2026 08:00:00",
            "fecFinCotizacion": "02/02/2026 17:30:00",
            "cotizar": false,
            "idEstadoContrato": 2,
            "nomEstadoContrato": "Vigente",
            "fecPublica": "29/01/2026 23:29:01",
            "idTipoCotizacion": 2,
            "idCotizacion": null,
            "idEstadoCotiza": null,
            "nomEstadoCotiza": null,
            "nomEntidad": "MUNICIPALIDAD DISTRITAL DE HUACHON",
            "numSubsanacionesTotal": 0,
            "numSubsanacionesPendientes": 0,
            "fecLimiteSubsanaMax": null
        },
        {
            "secuencia": 2,
            "idContrato": 40896,
            "nroContratacion": 122,
            "desContratacion": "CM-122-2026-GRU-SGA-ASA",
            "idObjetoContrato": 2,
            "nomObjetoContrato": "Servicio",
            "desObjetoContrato": "CONTRATACION DEL SERVICIO DE REFRIGERIOS Y ALMUERZO",
            "nomEtapaContratacion": "ETAPA DE COTIZACIÓN",
            "fecIniCotizacion": "30/01/2026 00:00:00",
            "fecFinCotizacion": "30/01/2026 12:00:00",
            "cotizar": false,
            "idEstadoContrato": 2,
            "nomEstadoContrato": "Vigente",
            "fecPublica": "29/01/2026 22:28:17",
            "idTipoCotizacion": 2,
            "idCotizacion": null,
            "idEstadoCotiza": null,
            "nomEstadoCotiza": null,
            "nomEntidad": "GOBIERNO REGIONAL DE UCAYALI SEDE CENTRAL",
            "numSubsanacionesTotal": 0,
            "numSubsanacionesPendientes": 0,
            "fecLimiteSubsanaMax": null
        }
    ],
    "pageable": {
        "pageNumber": 1,
        "pageSize": 5,
        "totalElements": 29552
    }
}
```

**Estructura de Respuesta:**

| Campo Raíz | Tipo | Descripción |
|------------|------|-------------|
| `data` | array | Array de contratos encontrados |
| `pageable` | object | Información de paginación |

**Campos del Array `data[]`:**

| Campo | Tipo | Descripción | Mapeo BD |
|-------|------|-------------|----------|
| `secuencia` | integer | Orden en la página | - |
| `idContrato` | integer | **ID único del contrato** | `id_contrato_seace` |
| `nroContratacion` | integer | Número de contratación | `nro_contratacion` |
| `desContratacion` | string | Código del proceso | `codigo_proceso` |
| `idObjetoContrato` | integer | ID del objeto (1=Bien, 2=Servicio, etc.) | `id_objeto_contrato` |
| `nomObjetoContrato` | string | Nombre del objeto | `objeto` |
| `desObjetoContrato` | string | Descripción completa | `descripcion` |
| `nomEtapaContratacion` | string | Etapa actual | `etapa_contratacion` |
| `fecIniCotizacion` | string | Fecha inicio cotización (dd/mm/yyyy HH:mm:ss) | `inicio_cotizacion` |
| `fecFinCotizacion` | string | Fecha fin cotización | `fin_cotizacion` |
| `cotizar` | boolean | Si puede cotizar | - |
| `idEstadoContrato` | integer | ID del estado | `id_estado_contrato` |
| `nomEstadoContrato` | string | Nombre del estado | `estado` |
| `fecPublica` | string | Fecha de publicación | `fecha_publicacion` |
| `idTipoCotizacion` | integer | Tipo de cotización | `id_tipo_cotizacion` |
| `idCotizacion` | integer/null | ID de cotización enviada | - |
| `idEstadoCotiza` | integer/null | ID estado cotización | - |
| `nomEstadoCotiza` | string/null | Nombre estado cotización | - |
| `nomEntidad` | string | Nombre de la entidad | `entidad` |
| `numSubsanacionesTotal` | integer | Total de subsanaciones | `num_subsanaciones_total` |
| `numSubsanacionesPendientes` | integer | Subsanaciones pendientes | `num_subsanaciones_pendientes` |
| `fecLimiteSubsanaMax` | string/null | Fecha límite subsanación | - |

**Objeto `pageable`:**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `pageNumber` | integer | Número de página actual |
| `pageSize` | integer | Tamaño de página (registros devueltos) |
| `totalElements` | integer | **Total de registros encontrados** |

**Response (Sin Resultados):**
```json
{
    "data": [],
    "pageable": {
        "pageNumber": 1,
        "pageSize": 5,
        "totalElements": 0
    }
}
```

---

### 4.2 Código Laravel para Buscador

```php
public function buscarContratos($anio = null, $page = 1, $pageSize = 100)
{
    $cuenta = CuentaSeace::where('activa', true)->first();
    
    if (!$cuenta || !$cuenta->access_token) {
        throw new \Exception('No hay cuenta activa con token válido');
    }
    
    $params = [
        'anio' => $anio ?? now()->year,
        'ruc' => $cuenta->username,
        'cotizaciones_enviadas' => false,
        'invitaciones_por_cotizar' => false,
        'lista_estado_contrato' => 2, // Solo "Vigente"
        'orden' => 2, // Descendente (más recientes primero)
        'page' => $page,
        'page_size' => $pageSize,
    ];
    
    // Agregar código de entidad si se especifica
    if ($codigoEntidad) {
        $params['codigo_entidad'] = $codigoEntidad;
    }
    
    // Agregar palabra clave si se especifica
    if ($palabraClave) {
        $params['palabra_clave'] = $palabraClave;
    }
    
    $response = $this->fetchWithRetry(
        config('services.seace.base_url') . '/contratacion/contrataciones/buscador',
        $params
    );
    
    if (!$response->successful()) {
        Log::error('Error en buscador SEACE', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
        throw new \Exception('Error al consultar el buscador SEACE');
    }
    
    $data = $response->json();
    
    return [
        'contratos' => $data['data'] ?? [],
        'total' => $data['pageable']['totalElements'] ?? 0,
        'page' => $data['pageable']['pageNumber'] ?? $page,
        'pageSize' => $data['pageable']['pageSize'] ?? $pageSize,
        'totalPages' => ceil(($data['pageable']['totalElements'] ?? 0) / $pageSize),
    ];
}
```

---

## � 5. ENDPOINTS DE ARCHIVOS Y TDR

### 5.1 Listar Archivos de un Contrato (GET)

**Endpoint:** `/archivo/archivos/listar-archivos-contrato/{idContrato}/{tipoArchivo}`

**URL Completa:**
```
https://prod6.seace.gob.pe/v1/s8uit-services/archivo/archivos/listar-archivos-contrato/41616/1
```

**Método:** `GET`

**Headers Requeridos:**
```http
Authorization: Bearer {TOKEN}
Accept: application/json, text/plain, */*
Accept-Language: es-419,es;q=0.9
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36
Origin: https://prod6.seace.gob.pe
Referer: https://prod6.seace.gob.pe/cotizacion/contrataciones
```

**Parámetros de URL:**

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------||
| `idContrato` | integer | ID del contrato (obtenido del buscador: `data[].idContrato`) | `41616` |
| `tipoArchivo` | integer | Tipo de archivo (1=Anexo de la contratación) | `1` |

**Ejemplo URL:**
```
https://prod6.seace.gob.pe/v1/s8uit-services/archivo/archivos/listar-archivos-contrato/41616/1
```

**Response (200 OK):**
```json
[
    {
        "idContratoArchivo": 160706,
        "idContrato": 41616,
        "idTipoArchivo": 1,
        "nombre": "TDR 70.pdf",
        "descripcionExtension": ".pdf",
        "descripcionMime": "application/pdf",
        "tamanio": "1435788",
        "nombreTipoArchivo": "Anexo de la contratación"
    }
]
```

**Campos de Respuesta:**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `idContratoArchivo` | integer | **ID único del archivo** (usar para descarga) |
| `idContrato` | integer | ID del contrato padre |
| `idTipoArchivo` | integer | Tipo de archivo (1=Anexo) |
| `nombre` | string | Nombre del archivo (TDR, bases, etc.) |
| `descripcionExtension` | string | Extensión del archivo (.pdf, .docx, etc.) |
| `descripcionMime` | string | Tipo MIME del archivo |
| `tamanio` | string | Tamaño del archivo en bytes |
| `nombreTipoArchivo` | string | Descripción del tipo de archivo |

**Response (Sin Archivos):**
```json
[]
```

**Código Laravel:**
```php
$archivos = Http::withToken($accessToken)
    ->withHeaders($this->getHeaders('https://prod6.seace.gob.pe/cotizacion/contrataciones'))
    ->timeout(10)
    ->get("{$baseUrl}/archivo/archivos/listar-archivos-contrato/{$idContrato}/1")
    ->json();

foreach ($archivos as $archivo) {
    echo "Archivo: {$archivo['nombre']} ({$archivo['tamanio']} bytes)";
}
```

---

### 5.2 Descargar Archivo de Contrato (GET - Binary Response)

**Endpoint:** `/archivo/archivos/descargar-archivo-contrato/{idContratoArchivo}`

**URL Completa:**
```
https://prod6.seace.gob.pe/v1/s8uit-services/archivo/archivos/descargar-archivo-contrato/160706
```

**Método:** `GET`

**Headers Requeridos:**
```http
Authorization: Bearer {TOKEN}
Accept: application/json, text/plain, */*
Accept-Language: es-419,es;q=0.9
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36
Origin: https://prod6.seace.gob.pe
Referer: https://prod6.seace.gob.pe/cotizacion/contrataciones
```

**Parámetros de URL:**

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------||
| `idContratoArchivo` | integer | ID del archivo (obtenido de listar-archivos-contrato) | `160706` |

**Response Headers (200 OK):**
```http
Content-Type: application/octet-stream
Content-Disposition: attachment; filename="TDR 70.pdf.pdf"
Content-Length: 1435788
```

**Response Body:** Flujo binario del archivo (PDF, DOCX, etc.)

**⚠️ IMPORTANTE:**
- La respuesta es un **archivo binario**, NO JSON
- El nombre del archivo está en el header `Content-Disposition`
- El tamaño está en el header `Content-Length` (bytes)

**Código Laravel (Descargar y Guardar Archivo):**
```php
public function descargarTDR($idContratoArchivo, $nombreArchivo)
{
    $cuenta = CuentaSeace::where('activa', true)->first();
    
    $response = Http::withToken($cuenta->access_token)
        ->withHeaders($this->getHeaders('https://prod6.seace.gob.pe/cotizacion/contrataciones'))
        ->timeout(30) // Mayor timeout para archivos grandes
        ->get("{$this->baseUrl}/archivo/archivos/descargar-archivo-contrato/{$idContratoArchivo}");
    
    if ($response->successful()) {
        // Guardar en storage/app/tdrs/
        $path = "tdrs/{$nombreArchivo}";
        Storage::put($path, $response->body());
        
        Log::info('TDR descargado', [
            'archivo' => $nombreArchivo,
            'tamaño' => strlen($response->body()),
            'path' => $path
        ]);
        
        return $path;
    }
    
    return false;
}
```

**Código Laravel (Descargar y Enviar al Navegador):**
```php
public function descargarArchivo($idContratoArchivo)
{
    $cuenta = CuentaSeace::where('activa', true)->first();
    
    $response = Http::withToken($cuenta->access_token)
        ->withHeaders($this->getHeaders('https://prod6.seace.gob.pe/cotizacion/contrataciones'))
        ->timeout(30)
        ->get("{$this->baseUrl}/archivo/archivos/descargar-archivo-contrato/{$idContratoArchivo}");
    
    if ($response->successful()) {
        // Extraer nombre del archivo del header Content-Disposition
        $contentDisposition = $response->header('Content-Disposition');
        preg_match('/filename="(.+?)"/', $contentDisposition, $matches);
        $filename = $matches[1] ?? 'archivo.pdf';
        
        return response($response->body(), 200, [
            'Content-Type' => $response->header('Content-Type'),
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
    
    abort(404, 'Archivo no encontrado');
}
```

---

### 5.3 Flujo Completo: Listar y Descargar TDR

**Paso 1: Buscar contratos**
```php
$contratos = Http::withToken($accessToken)
    ->get("{$baseUrl}/contratacion/contrataciones/buscador", [
        'anio' => 2026,
        'ruc' => '20614706491',
        'page' => 1,
        'page_size' => 10,
    ])
    ->json();
```

**Paso 2: Para cada contrato, listar archivos**
```php
foreach ($contratos['data'] as $contrato) {
    $idContrato = $contrato['idContrato'];
    
    $archivos = Http::withToken($accessToken)
        ->get("{$baseUrl}/archivo/archivos/listar-archivos-contrato/{$idContrato}/1")
        ->json();
    
    echo "Contrato {$contrato['desContratacion']}: " . count($archivos) . " archivos";
}
```

**Paso 3: Descargar un archivo específico**
```php
if (!empty($archivos)) {
    $archivo = $archivos[0];
    $idContratoArchivo = $archivo['idContratoArchivo'];
    
    $response = Http::withToken($accessToken)
        ->get("{$baseUrl}/archivo/archivos/descargar-archivo-contrato/{$idContratoArchivo}");
    
    Storage::put("tdrs/{$archivo['nombre']}", $response->body());
}
```

---

### 5.4 Implementación Completa con Livewire (✅ FUNCIONANDO)

**Descripción:** Sistema de descarga directa de archivos TDR sin abrir nueva pestaña, usando archivo temporal y enlace dinámico.

#### Componente Livewire (app/Livewire/PruebaEndpoints.php):
```php
public function descargarArchivo($idContratoArchivo, $nombreArchivo)
{
    $cuenta = CuentaSeace::where('activa', true)->first();
    
    // Validar token
    if (!$cuenta || !$cuenta->token_valido) {
        $this->addError('descarga', 'Token inválido o expirado. Por favor, vuelve a iniciar sesión.');
        return;
    }
    
    try {
        Log::info('SEACE: Descargando archivo', [
            'idContratoArchivo' => $idContratoArchivo,
            'nombreArchivo' => $nombreArchivo,
            'url' => "{$this->baseUrl}/archivo/archivos/descargar-archivo-contrato/{$idContratoArchivo}",
        ]);
        
        // Preparar headers (referer sin /auth-proveedor)
        $headers = $this->getHeaders('https://prod6.seace.gob.pe/cotizacion/contrataciones');
        $headers['Accept'] = 'application/json, text/plain, */*';
        
        // Realizar petición HTTP
        $response = Http::withToken($cuenta->access_token)
            ->withHeaders($headers)
            ->timeout(60)
            ->get("{$this->baseUrl}/archivo/archivos/descargar-archivo-contrato/{$idContratoArchivo}");
        
        Log::info('SEACE: Respuesta recibida', [
            'status' => $response->status(),
            'content_type' => $response->header('Content-Type'),
            'content_length' => $response->header('Content-Length'),
        ]);
        
        if ($response->successful()) {
            $contentType = $response->header('Content-Type');
            
            // Detectar si el servidor devolvió error JSON en lugar de archivo
            if (strpos($contentType, 'application/json') !== false) {
                $errorData = $response->json();
                Log::error('SEACE: API devolvió JSON en lugar de archivo', ['data' => $errorData]);
                $this->addError('descarga', 'El servidor devolvió un error: ' . ($errorData['message'] ?? json_encode($errorData)));
                return;
            }
            
            // Guardar archivo en storage temporal
            $tempPath = storage_path('app/temp');
            if (!file_exists($tempPath)) {
                mkdir($tempPath, 0755, true);
            }
            
            $tempFile = $tempPath . '/' . $nombreArchivo;
            $bytesWritten = file_put_contents($tempFile, $response->body());
            
            Log::info('SEACE: Archivo guardado temporalmente', [
                'path' => $tempFile,
                'bytes' => $bytesWritten,
            ]);
            
            // Disparar evento JavaScript para descargar
            $this->dispatch('descargar-archivo', url: route('seace.download.temp', ['filename' => $nombreArchivo]));
            
            session()->flash('descarga_exitosa', "✓ Archivo '{$nombreArchivo}' descargado correctamente");
            
        } else {
            // Manejar errores del servidor
            $errorData = null;
            $errorMessage = 'Error desconocido';
            
            try {
                $body = $response->body();
                $errorData = json_decode($body, true);
                
                if ($errorData && isset($errorData['message'])) {
                    $errorMessage = $errorData['message'];
                } elseif ($errorData && isset($errorData['backendMessage'])) {
                    $errorMessage = $errorData['backendMessage'];
                } else {
                    $errorMessage = $body;
                }
            } catch (\Exception $e) {
                $errorMessage = $response->body();
            }
            
            Log::error('SEACE: Error descargando archivo', [
                'status' => $response->status(),
                'error' => $errorData,
            ]);
            
            $this->addError('descarga', 'Error del servidor SEACE (HTTP ' . $response->status() . '): ' . $errorMessage);
        }
        
    } catch (\Exception $e) {
        Log::error('SEACE: Excepción al descargar archivo', [
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        
        $this->addError('descarga', 'Error al procesar la descarga: ' . $e->getMessage());
    }
}
```

#### Ruta de Descarga Temporal (routes/web.php):
```php
Route::get('/seace/download/{filename}', function ($filename) {
    $path = storage_path('app/temp/' . $filename);
    
    if (!file_exists($path)) {
        abort(404, 'Archivo no encontrado o ya fue descargado');
    }
    
    // Descargar y auto-eliminar archivo
    return response()->download($path, $filename)->deleteFileAfterSend(true);
})->name('seace.download.temp');
```

#### Vista Blade (resources/views/livewire/prueba-endpoints.blade.php):
```blade
{{-- Botón de descarga --}}
<button
    wire:click="descargarArchivo({{ $archivo['idContratoArchivo'] }}, '{{ $archivo['nombre'] }}')"
    wire:loading.attr="disabled"
    wire:target="descargarArchivo"
    class="px-4 py-2 bg-secondary-500 text-white rounded-full hover:bg-secondary-400 font-medium text-xs flex items-center gap-2 transition-colors shadow-sm disabled:opacity-50"
>
    <svg wire:loading.remove wire:target="descargarArchivo" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
    </svg>
    <span wire:loading.remove wire:target="descargarArchivo">Descargar</span>
    <span wire:loading wire:target="descargarArchivo">Descargando...</span>
</button>

{{-- Mensajes de éxito/error --}}
@if(session()->has('descarga_exitosa'))
    <div class="mt-4 bg-secondary-500/10 border-l-4 border-secondary-500 rounded-2xl p-4">
        <p class="text-sm text-neutral-900 font-medium">{{ session('descarga_exitosa') }}</p>
    </div>
@endif

@error('descarga')
    <div class="mt-4 bg-primary-500/10 border-l-4 border-primary-500 rounded-2xl p-4">
        <p class="text-sm text-neutral-900 font-medium">❌ {{ $message }}</p>
    </div>
@enderror

{{-- Script para descarga automática SIN abrir nueva pestaña --}}
@script
<script>
    $wire.on('descargar-archivo', (event) => {
        // Crear enlace temporal invisible
        const link = document.createElement('a');
        link.href = event.url;
        link.download = ''; // Forzar descarga
        link.style.display = 'none';
        
        // Agregar al DOM, hacer click y remover
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
</script>
@endscript
```

#### ⚠️ NOTAS CRÍTICAS DE IMPLEMENTACIÓN

1. **Referer Correcto:** El header `Referer` debe ser `https://prod6.seace.gob.pe/cotizacion/contrataciones` (SIN `/auth-proveedor`)

2. **Accept Header:** Debe incluir `application/json, text/plain, */*` para manejar tanto respuestas JSON (errores) como binarias (archivos)

3. **Validación de Respuesta:** SIEMPRE verificar si el servidor devuelve JSON (error) o binario (archivo exitoso) usando `Content-Type`

4. **Descarga Sin Redirección:** Usar `document.createElement('a')` + `link.click()` para descargar sin abrir nueva pestaña

5. **Timeout Extendido:** Usar `timeout(60)` para archivos grandes (algunos TDR pesan 4+ MB)

6. **Auto-limpieza:** El archivo temporal se elimina automáticamente después de enviarse al navegador (`deleteFileAfterSend(true)`)

7. **Directorio Temporal:** Crear `storage/app/temp/` si no existe antes de guardar archivos

---

## 📦 6. MAPEO COMPLETO: API → BASE DE DATOS

### Migración Laravel (Tabla `contratos`)

```php
Schema::create('contratos', function (Blueprint $table) {
    $table->id();
    
    // Identificador único del SEACE (CLAVE ÚNICA)
    $table->unsignedBigInteger('id_contrato_seace')->unique();
    
    // Datos básicos
    $table->integer('nro_contratacion');
    $table->string('codigo_proceso', 100); // desContratacion
    
    // Información de la entidad
    $table->string('entidad', 255); // nomEntidad
    
    // Objeto del contrato
    $table->unsignedTinyInteger('id_objeto_contrato'); // 1-4
    $table->string('objeto', 50); // nomObjetoContrato
    $table->text('descripcion'); // desObjetoContrato
    
    // Estado
    $table->unsignedTinyInteger('id_estado_contrato'); // 1-4
    $table->string('estado', 50); // nomEstadoContrato
    
    // Fechas importantes
    $table->dateTime('fecha_publicacion'); // fecPublica
    $table->dateTime('inicio_cotizacion'); // fecIniCotizacion
    $table->dateTime('fin_cotizacion'); // fecFinCotizacion
    
    // Etapa
    $table->string('etapa_contratacion', 100)->nullable(); // nomEtapaContratacion
    
    // Datos adicionales
    $table->unsignedTinyInteger('id_tipo_cotizacion')->nullable(); // idTipoCotizacion
    $table->unsignedInteger('num_subsanaciones_total')->default(0);
    $table->unsignedInteger('num_subsanaciones_pendientes')->default(0);
    
    // JSON completo (backup)
    $table->json('datos_raw')->nullable();
    
    // Auditoría
    $table->timestamps();
    
    // Índices para búsquedas rápidas
    $table->index('estado');
    $table->index('fecha_publicacion');
    $table->index('fin_cotizacion');
    $table->index(['entidad', 'estado']);
});
```

### 6.1 Lógica de Guardado con UPSERT

```php
foreach ($contratos as $item) {
    $contrato = Contrato::updateOrCreate(
        // CLAVE ÚNICA: idContrato del SEACE
        ['id_contrato_seace' => $item['idContrato']],
        
        // DATOS A ACTUALIZAR
        [
            'nro_contratacion' => $item['nroContratacion'],
            'codigo_proceso' => $item['desContratacion'],
            'entidad' => $item['nomEntidad'],
            'id_objeto_contrato' => $item['idObjetoContrato'],
            'objeto' => $item['nomObjetoContrato'],
            'descripcion' => $item['desObjetoContrato'],
            'id_estado_contrato' => $item['idEstadoContrato'],
            'estado' => $item['nomEstadoContrato'],
            'fecha_publicacion' => Carbon::createFromFormat('d/m/Y H:i:s', $item['fecPublica']),
            'inicio_cotizacion' => Carbon::createFromFormat('d/m/Y H:i:s', $item['fecIniCotizacion']),
            'fin_cotizacion' => Carbon::createFromFormat('d/m/Y H:i:s', $item['fecFinCotizacion']),
            'etapa_contratacion' => $item['nomEtapaContratacion'],
            'id_tipo_cotizacion' => $item['idTipoCotizacion'],
            'num_subsanaciones_total' => $item['numSubsanacionesTotal'],
            'num_subsanaciones_pendientes' => $item['numSubsanacionesPendientes'],
            'datos_raw' => $item, // Guardar JSON completo
        ]
    );
    
    // 🚨 DISPARAR ALERTA SOLO PARA CONTRATOS NUEVOS
    if ($contrato->wasRecentlyCreated) {
        // Enviar notificación Telegram
        TelegramNotificationService::enviarAlerta($contrato);
        
        Log::info("✅ NUEVO CONTRATO: {$contrato->codigo_proceso}", [
            'entidad' => $contrato->entidad,
            'id_seace' => $contrato->id_contrato_seace,
        ]);
    }
}
```

---

## 🎯 7. ESTRATEGIA DE EXTRACCIÓN DEEP DIVE

### 7.1 Objetivo: Extraer TODOS los contratos del año actual

**Paso 1: Consultar total de registros**
```php
$response = $this->buscarContratos(now()->year, 1, 100);
$total = $response['total'];
$totalPages = $response['totalPages'];

Log::info("Total de contratos encontrados: {$total} ({$totalPages} páginas)");
```

**Paso 2: Iterar sobre todas las páginas**
```php
for ($page = 1; $page <= $totalPages; $page++) {
    
    // Delay aleatorio entre 1-3 segundos para no saturar
    sleep(rand(1, 3));
    
    $response = $this->buscarContratos(now()->year, $page, 100);
    
    foreach ($response['contratos'] as $contratoData) {
        $this->guardarContrato($contratoData);
    }
    
    Log::info("Página {$page}/{$totalPages} procesada");
}
```

**Paso 3: Programar ejecuciones automáticas**

**Archivo: `app/Console/Kernel.php`**
```php
protected function schedule(Schedule $schedule)
{
    // Ejecutar cada 42-50 minutos aleatorios
    $schedule->command('seace:sync')
        ->everyMinute()
        ->when(function () {
            // Lógica para ejecutar solo cada 42-50 minutos
            $lastRun = Cache::get('seace_last_run', now()->subHour());
            $minutesSinceLastRun = now()->diffInMinutes($lastRun);
            
            if ($minutesSinceLastRun >= rand(42, 50)) {
                Cache::put('seace_last_run', now());
                return true;
            }
            
            return false;
        })
        ->withoutOverlapping()
        ->runInBackground();
}
```

**Configurar Cron (Linux/Mac):**
```bash
* * * * * cd /path/to/vigilante-seace && php artisan schedule:run >> /dev/null 2>&1
```

**Task Scheduler (Windows):**
- Programa: `C:\xampp\php\php.exe`
- Argumentos: `artisan schedule:run`
- Directorio: `d:\xampp\htdocs\vigilante-seace`
- Trigger: Cada 1 minuto

---

## 🛡️ 8. HEADERS OBLIGATORIOS (NINJA MODE)

### 8.1 Método Helper en `SeaceScraperService`

```php
private function getHeaders(): array
{
    return [
        'Accept' => 'application/json, text/plain, */*',
        'Accept-Language' => 'es-419,es;q=0.9',
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        'Origin' => 'https://prod6.seace.gob.pe',
        'Referer' => 'https://prod6.seace.gob.pe/auth-proveedor/busqueda',
        'Sec-Ch-Ua' => '"Google Chrome";v="131", "Chromium";v="131", "Not_A Brand";v="24"',
        'Sec-Ch-Ua-Mobile' => '?0',
        'Sec-Ch-Ua-Platform' => '"Windows"',
        'Sec-Fetch-Dest' => 'empty',
        'Sec-Fetch-Mode' => 'cors',
        'Sec-Fetch-Site' => 'same-origin',
    ];
}
```

**⚠️ IMPORTANTE:** Estos headers simulan un navegador real y son **OBLIGATORIOS** para evitar detección.

---

## 📝 9. RESUMEN DE VARIABLES DE ENTORNO

### 9.1 Archivo `.env`

```env
# URL Base de la API SEACE
SEACE_BASE_URL=https://prod6.seace.gob.pe/v1/s8uit-services

# Duración del token (5 minutos = 300 segundos)
SEACE_TOKEN_DURATION=300

# Tamaño de página máximo (recomendado: 100)
SEACE_PAGE_SIZE=100

# Delays para scraping "ninja" (en minutos)
SEACE_MIN_DELAY_MINUTES=42
SEACE_MAX_DELAY_MINUTES=50
```

### Archivo `config/services.php`

```php
return [
    'seace' => [
        'base_url' => env('SEACE_BASE_URL', 'https://prod6.seace.gob.pe/v1/s8uit-services'),
        'token_duration' => env('SEACE_TOKEN_DURATION', 300),
        'page_size' => env('SEACE_PAGE_SIZE', 100),
        'min_delay' => env('SEACE_MIN_DELAY_MINUTES', 42),
        'max_delay' => env('SEACE_MAX_DELAY_MINUTES', 50),
    ],
];
```

---

## 🎓 10. CASOS DE USO COMUNES

### 10.1 Buscar Contratos de una Entidad Específica (Con Autocompletado)

**Paso 1: Buscar código de entidad**
```php
$entidades = Http::withToken($accessToken)
    ->get($baseUrl . '/servicio/servicios/obtener-entidades-cubso', [
        'descEntidad' => 'ministerio de la mujer',
    ])
    ->json();

$codigoEntidad = $entidades['lista'][0]['codConsucode']; // "20"
```

**Paso 2: Buscar contratos con código de entidad**
```php
$params = [
    'anio' => 2024,
    'ruc' => $cuenta->username,
    'codigo_entidad' => $codigoEntidad, // 20
    'orden' => 2,
    'page' => 1,
    'page_size' => 100,
];
```

### 9.2 Buscar Solo Bienes

```php
$params = [
    'anio' => 2026,
    'ruc' => $cuenta->username,
    'lista_objeto_contrato' => '1', // 1 = Bien
    'orden' => 2,
    'page' => 1,
    'page_size' => 100,
];
```

### 9.3 Buscar por Palabra Clave

```php
$params = [
    'anio' => 2026,
    'ruc' => $cuenta->username,
    'palabra_clave' => 'laptop',
    'orden' => 2,
    'page' => 1,
    'page_size' => 100,
];
```

### 9.4 Obtener Todos los Contratos Vigentes

```php
$params = [
    'anio' => 2026,
    'ruc' => $cuenta->username,
    'lista_estado_contrato' => 2, // 2 = Vigente
    'orden' => 2,
    'page' => 1,
    'page_size' => 100,
];
```

### 9.5 Buscar Contratos por Ubicación Geográfica (Cascada)

**Paso 1: Cargar departamentos**
```php
$departamentos = Http::withToken($accessToken)
    ->get($baseUrl . '/maestra/maestras/listar-departamento')
    ->json();
```

**Paso 2: Usuario selecciona departamento → Cargar provincias**
```php
$idDepartamento = 24; // Tumbes
$provincias = Http::withToken($accessToken)
    ->get($baseUrl . "/maestra/maestras/listar-provincia/{$idDepartamento}")
    ->json();
```

**Paso 3: Usuario selecciona provincia → Cargar distritos**
```php
$idProvincia = 190; // Contralmirante Villar
$distritos = Http::withToken($accessToken)
    ->get($baseUrl . "/maestra/maestras/listar-distrito/{$idProvincia}")
    ->json();
```

**Paso 4: Buscar con filtros geográficos**
```php
$params = [
    'anio' => 2024,
    'ruc' => $cuenta->username,
    'codigo_departamento' => 24,
    'codigo_provincia' => 190,
    'codigo_distrito' => 1867,
    'orden' => 2,
    'page' => 1,
    'page_size' => 100,
];
```

### 10.6 Descargar TDR de Todos los Contratos Vigentes

**Flujo Completo:**
```php
// Paso 1: Buscar contratos vigentes del mes actual
$response = Http::withToken($accessToken)
    ->get("{$baseUrl}/contratacion/contrataciones/buscador", [
        'anio' => now()->year,
        'ruc' => '20614706491',
        'lista_estado_contrato' => 2, // Vigente
        'orden' => 2, // Más recientes primero
        'page' => 1,
        'page_size' => 100,
    ]);

$contratos = $response->json()['data'];

// Paso 2: Para cada contrato, listar y descargar archivos
foreach ($contratos as $contrato) {
    $idContrato = $contrato['idContrato'];
    $codigoProceso = $contrato['desContratacion'];
    
    // Listar archivos
    $archivos = Http::withToken($accessToken)
        ->get("{$baseUrl}/archivo/archivos/listar-archivos-contrato/{$idContrato}/1")
        ->json();
    
    if (empty($archivos)) {
        Log::warning("Contrato {$codigoProceso} sin archivos");
        continue;
    }
    
    // Descargar cada archivo
    foreach ($archivos as $archivo) {
        $idContratoArchivo = $archivo['idContratoArchivo'];
        $nombreArchivo = $archivo['nombre'];
        
        $fileResponse = Http::withToken($accessToken)
            ->timeout(30)
            ->get("{$baseUrl}/archivo/archivos/descargar-archivo-contrato/{$idContratoArchivo}");
        
        if ($fileResponse->successful()) {
            // Guardar con estructura de carpetas
            $path = "tdrs/{$codigoProceso}/{$nombreArchivo}";
            Storage::put($path, $fileResponse->body());
            
            Log::info("TDR descargado", [
                'contrato' => $codigoProceso,
                'archivo' => $nombreArchivo,
                'tamaño' => strlen($fileResponse->body()),
            ]);
        }
        
        // Delay entre descargas (evitar sobrecarga)
        usleep(500000); // 0.5 segundos
    }
}
```

---

## ⚠️ 11. ERRORES COMUNES Y SOLUCIONES

### Error 401: TOKEN_EXPIRED

**Causa:** El token JWT expiró (después de 5 minutos)

**Solución:**
1. Llamar automáticamente al endpoint `/tokens/refresh` con el token expirado en el header
2. Actualizar AMBOS tokens (token + refreshToken) en BD
3. Reintentar la petición original

### Error 401: Sin mensaje TOKEN_EXPIRED

**Causa:** Token inválido o refreshToken también expiró

**Solución:**
1. Realizar login completo con usuario y contraseña
2. Guardar nuevos tokens
3. Reintentar la petición

### Error 403: Forbidden

**Causa:** Headers faltantes o incorrectos

**Solución:**
- Verificar que TODOS los headers obligatorios estén presentes
- Usar el método `getHeaders()` que simula un navegador real

### Error de Formato de Fecha

**Causa:** Las fechas vienen en formato `dd/mm/yyyy HH:mm:ss`

**Solución:**
```php
$fecha = Carbon::createFromFormat('d/m/Y H:i:s', $item['fecPublica']);
```

---

## 📚 12. REFERENCIAS RÁPIDAS

### 12.1 URLs Importantes

| Recurso | URL |
|---------|-----|
| Portal SEACE Proveedor | https://prod6.seace.gob.pe/auth-proveedor/busqueda |
| Base API | https://prod6.seace.gob.pe/v1/s8uit-services |
| Términos y Condiciones | https://prod6.seace.gob.pe/auth-proveedor/terminos-condiciones |

### IDs de Objetos de Contratación

| ID | Nombre |
|----|--------|
| 1 | Bien |
| 2 | Servicio |
| 3 | Obra |
| 4 | Consultoría de Obra |

### IDs de Estados de Contratación

| ID | Nombre |
|----|--------|
| 1 | Borrador |
| 2 | Vigente |
| 3 | En Evaluación |
| 4 | Culminado |

---

## 🚀 13. COMANDOS ARTISAN DEL PROYECTO

```bash
# Sincronizar contratos del año actual
php artisan seace:sync

# Sincronizar año específico
php artisan seace:sync --year=2024

# Diagnóstico del sistema
php artisan seace:test

# Limpiar cachés
php artisan cache:clear && php artisan config:clear && php artisan view:clear

# Ver logs en tiempo real
Get-Content storage\logs\laravel.log -Wait -Tail 50
```

---

**Última actualización:** 30 de enero de 2026  
**Versión:** 1.0.0  
**Mantenedor:** Sistema Vigilante SEACE  
**Stack:** Laravel 12 + MySQL + Laravel HTTP Client (Guzzle)
