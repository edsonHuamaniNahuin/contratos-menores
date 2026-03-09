# 📋 DOCUMENTO DE CERTIFICACIÓN QA — VIGILANTE SEACE

> **Proyecto:** Vigilante SEACE — Sistema de Monitoreo Automatizado de Contratos SEACE (Perú)  
> **Versión:** 1.0  
> **Fecha de generación:** 7 de marzo de 2026  
> **Stack:** Laravel 12 + Blade + Livewire 4.1 + Alpine.js 3.x + MySQL  
> **Entorno de desarrollo:** XAMPP (Apache + PHP 8.2.12 + MySQL)  
> **URL base desarrollo:** `http://127.0.0.1:8000`

---

## TABLA DE CONTENIDOS

1. [Resumen del Sistema](#1-resumen-del-sistema)
2. [Arquitectura y Stack Tecnológico](#2-arquitectura-y-stack-tecnológico)
3. [Roles, Permisos y Control de Acceso](#3-roles-permisos-y-control-de-acceso)
4. [Módulo: Autenticación y Registro](#4-módulo-autenticación-y-registro)
5. [Módulo: Landing Page y Páginas Públicas](#5-módulo-landing-page-y-páginas-públicas)
6. [Módulo: Buscador Público SEACE](#6-módulo-buscador-público-seace)
7. [Módulo: Dashboard de Contratos](#7-módulo-dashboard-de-contratos)
8. [Módulo: Gestión de Cuentas SEACE](#8-módulo-gestión-de-cuentas-seace)
9. [Módulo: Prueba de Endpoints SEACE](#9-módulo-prueba-de-endpoints-seace)
10. [Módulo: Repositorio TDR](#10-módulo-repositorio-tdr)
11. [Módulo: Análisis de TDR con IA](#11-módulo-análisis-de-tdr-con-ia)
12. [Módulo: Suscriptores y Notificaciones](#12-módulo-suscriptores-y-notificaciones)
13. [Módulo: Seguimientos de Contratos](#13-módulo-seguimientos-de-contratos)
14. [Módulo: Mis Procesos Notificados](#14-módulo-mis-procesos-notificados)
15. [Módulo: Suscripciones Premium y Pagos](#15-módulo-suscripciones-premium-y-pagos)
16. [Módulo: Roles y Permisos (RBAC)](#16-módulo-roles-y-permisos-rbac)
17. [Módulo: Configuración del Sistema](#17-módulo-configuración-del-sistema)
18. [Módulo: Perfil de Usuario](#18-módulo-perfil-de-usuario)
19. [Módulo: Formulario de Contacto](#19-módulo-formulario-de-contacto)
20. [Módulo: Bots Interactivos (Telegram y WhatsApp)](#20-módulo-bots-interactivos-telegram-y-whatsapp)
21. [Módulo: Bot Admin Telegram](#21-módulo-bot-admin-telegram)
22. [Módulo: Jobs Programados (Scheduler)](#22-módulo-jobs-programados-scheduler)
23. [Módulo: API REST (Sanctum)](#23-módulo-api-rest-sanctum)
24. [Módulo: Webhooks](#24-módulo-webhooks)
25. [Módulo: Emails Transaccionales](#25-módulo-emails-transaccionales)
26. [Módulo: Páginas de Error](#26-módulo-páginas-de-error)
27. [Modelo de Datos (Esquema BD)](#27-modelo-de-datos-esquema-bd)
28. [Matriz de Permisos por Ruta](#28-matriz-de-permisos-por-ruta)
29. [Checklist de Regresión](#29-checklist-de-regresión)

---

## 1. RESUMEN DEL SISTEMA

**Vigilante SEACE** es un sistema web que automatiza el monitoreo y extracción de contratos menores del portal gubernamental SEACE (Sistema Electrónico de Contrataciones del Estado — Perú). Sus capacidades principales son:

- **Scraping automatizado** de contratos desde la API del SEACE con manejo de tokens JWT
- **Análisis inteligente de TDR** (Términos de Referencia) mediante IA (Gemini 2.5 Flash)
- **Notificaciones multi-canal:** Telegram, WhatsApp Business y Email
- **Sistema de suscripciones Premium** con pasarelas de pago (Openpay y MercadoPago)
- **RBAC** (Control de acceso basado en roles) con 3 roles y 13 permisos
- **Dashboard analítico** con gráficos de distribución de contratos
- **Buscador público** accesible sin autenticación
- **Bots interactivos** en Telegram y WhatsApp con botones inline

---

## 2. ARQUITECTURA Y STACK TECNOLÓGICO

| Componente | Tecnología |
|---|---|
| Backend | Laravel 12.49.0 (PHP 8.2.12) |
| Frontend | Blade + Livewire 4.1.0 + Alpine.js 3.x (CDN) |
| Base de datos | MySQL (utf8mb4) |
| Servidor web | Apache (XAMPP) |
| Cliente HTTP | Laravel HTTP Client (Guzzle 7.10) |
| Auth API | Laravel Sanctum (tokens Bearer) |
| Auth Web | Sesiones (guard `web`) |
| Microservicio IA | Python FastAPI (`http://127.0.0.1:8001`) |
| Pagos | Openpay Perú + MercadoPago |
| Notificaciones | Telegram Bot API + WhatsApp Cloud API (Meta) + SMTP |
| Scheduler | Laravel Scheduler (cron) |
| Queues | Laravel Jobs (ShouldQueue) |

---

## 3. ROLES, PERMISOS Y CONTROL DE ACCESO

### 3.1 Roles del Sistema

| Rol | Slug | Descripción |
|---|---|---|
| Administrador | `admin` | Acceso total al sistema |
| Proveedor | `proveedor` | Usuario registrado con acceso básico |
| Proveedor Premium | `proveedor-premium` | Proveedor con funcionalidades avanzadas |

### 3.2 Permisos Registrados (13)

| Permiso | Slug | Asignado a |
|---|---|---|
| Ver Buscador Público | `view-buscador-publico` | admin, proveedor, proveedor-premium |
| Ver Suscriptores | `view-suscriptores` | admin, proveedor, proveedor-premium |
| Ver Mis Procesos | `view-mis-procesos` | admin, proveedor, proveedor-premium |
| Analizar TDR con IA | `analyze-tdr` | admin, proveedor-premium |
| Seguir Contratos | `follow-contracts` | admin, proveedor-premium |
| Cotizar en SEACE | `cotizar-seace` | admin, proveedor-premium |
| Ver Cuentas SEACE | `view-cuentas` | admin |
| Ver Prueba Endpoints | `view-prueba-endpoints` | admin |
| Ver Configuración | `view-configuracion` | admin |
| Ver Repositorio TDR | `view-tdr-repository` | admin |
| Gestionar Roles/Permisos | `manage-roles-permissions` | admin |
| Importar TDR | `import-tdr` | admin |
| Gestionar Suscripciones | `manage-subscriptions` | admin |

### 3.3 Gates Registrados (13)

Todos los gates se definen en `AppServiceProvider::boot()` y verifican si el usuario tiene el permiso correspondiente vía `$user->hasPermission($slug)`.

---

## 4. MÓDULO: AUTENTICACIÓN Y REGISTRO

### 4.1 Registro de Usuario

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| AUTH-REG-001 | Registro exitoso — cuenta personal | No autenticado | 1. Ir a `/register` 2. Seleccionar tipo "Personal" 3. Completar nombre, email, password (min 8), confirmar password 4. Enviar | Se crea usuario, se asigna rol `proveedor`, se envía email de verificación, se autologa y redirige a `/email/verify` |
| AUTH-REG-002 | Registro exitoso — cuenta empresa | No autenticado | 1. Ir a `/register` 2. Seleccionar tipo "Empresa" 3. Completar nombre, email, password, RUC (11 dígitos, inicia con 10 o 20), razón social, teléfono 4. Enviar | Se crea usuario con RUC y razón social, se asigna rol `proveedor` |
| AUTH-REG-003 | Validación de dominio de email (personal) | No autenticado | 1. Intentar registrarse con email de dominio no permitido (ej: usuario@xyz.com) | Error de validación: solo se permiten gmail, outlook, hotmail, yahoo, live, icloud, protonmail, y dominios .com.pe, .edu.pe, .gob.pe, .org.pe |
| AUTH-REG-004 | Validación de dominio de email (empresa) | No autenticado | 1. Intentar registrarse como empresa con cualquier dominio de email | Se permite cualquier dominio para cuentas de empresa |
| AUTH-REG-005 | Email duplicado | Usuario ya registrado con ese email | 1. Intentar registro con email existente | Error de validación: "El email ya está registrado" |
| AUTH-REG-006 | RUC inválido | No autenticado, tipo empresa | 1. Ingresar RUC con formato inválido (no 11 dígitos o no inicia con 10/20) | Error de validación por regex |
| AUTH-REG-007 | Password demasiado corta | No autenticado | 1. Ingresar password de menos de 8 caracteres | Error de validación: mínimo 8 caracteres |
| AUTH-REG-008 | Passwords no coinciden | No autenticado | 1. Ingresar password distinta en confirmación | Error de validación: las contraseñas no coinciden |
| AUTH-REG-009 | Se dispara evento Registered | Registro exitoso | Verificar logs/listeners | `SendNewUserNotifications` ejecuta: 1) Notificación admin por Telegram, 2) Email de bienvenida (`WelcomeMail`) |

### 4.2 Login

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| AUTH-LOG-001 | Login exitoso | Usuario registrado y verificado | 1. Ir a `/login` 2. Ingresar email y password 3. Enviar | Se regenera sesión, redirige a `/dashboard` |
| AUTH-LOG-002 | Login con remember me | Usuario registrado | 1. Login con checkbox "Recordarme" activado | Cookie `remember_token` persistente |
| AUTH-LOG-003 | Credenciales inválidas | No autenticado | 1. Ingresar credenciales erróneas | Error: "Las credenciales no coinciden" |
| AUTH-LOG-004 | Acceso a ruta protegida sin login | No autenticado | 1. Navegar directamente a `/dashboard` | Redirige a `/login` |

### 4.3 Logout

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| AUTH-OUT-001 | Logout exitoso | Autenticado | 1. POST `/logout` | Sesión invalidada, token CSRF regenerado, redirige a `/login` |

### 4.4 Verificación de Email

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| AUTH-VER-001 | Vista de verificación | Autenticado, email no verificado | 1. GET `/email/verify` | Muestra página solicitando verificación |
| AUTH-VER-002 | Verificar email con link firmado | Recibir email con link | 1. Click en link de verificación | Email marcado como verificado, redirige a `/dashboard` |
| AUTH-VER-003 | Reenviar email de verificación | Autenticado, no verificado | 1. POST `/email/verification-notification` | Reenvía email de verificación (throttle: 6 por minuto) |
| AUTH-VER-004 | Acceso a ruta con `verified` sin verificar | Autenticado, no verificado | 1. Intentar acceder a `/dashboard` | Redirige a `/email/verify` |

### 4.5 Recuperación de Contraseña

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| AUTH-PWD-001 | Solicitar reset | No autenticado | 1. Ir a `/forgot-password` 2. Ingresar email 3. Enviar | Se envía email con link de reset |
| AUTH-PWD-002 | Restablecer contraseña | Token de reset válido | 1. Click en link del email `/reset-password/{token}` 2. Ingresar nueva password (min 8) + confirmación 3. Enviar | Contraseña actualizada, redirige a `/login` con mensaje de éxito |
| AUTH-PWD-003 | Token expirado | Token antiguo | 1. Usar link de reset expirado | Error: "Este enlace de recuperación ha expirado" |

---

## 5. MÓDULO: LANDING PAGE Y PÁGINAS PÚBLICAS

### 5.1 Landing Page

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| PUB-LAN-001 | Carga de landing page | Ninguna | 1. Navegar a `/` | Se muestra la landing page sin autenticación |
| PUB-LAN-002 | Links de navegación | Ninguna | 1. Verificar links a Buscador, Planes, Contacto, Manual | Todos los links funcionan y navegan a la vista correcta |

### 5.2 Páginas Públicas

| ID | Caso de Prueba | Ruta | Resultado Esperado |
|---|---|---|---|
| PUB-PAG-001 | Página de Planes | `/planes` | Muestra planes disponibles (trial, monthly, yearly) |
| PUB-PAG-002 | Página de Contacto | `/contacto` | Muestra formulario de contacto (componente Livewire `Contacto`) |
| PUB-PAG-003 | Página de Manual | `/manual` | Muestra documentación de usuario |
| PUB-PAG-004 | Buscador Público | `/buscador-publico` | Muestra el buscador público de contratos SEACE |

---

## 6. MÓDULO: BUSCADOR PÚBLICO SEACE

**Componente Livewire:** `BuscadorPublico` (~1217 líneas)  
**Ruta:** `/buscador-publico` (pública, sin autenticación)  
**Servicio backend:** `SeaceBuscadorPublicoService` (endpoints públicos SEACE)

### 6.1 Búsqueda y Filtros

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| BUS-FIL-001 | Búsqueda por palabra clave | Ninguna | 1. Ingresar texto en campo de búsqueda 2. Click "Buscar" | Se muestran contratos que contienen la palabra clave |
| BUS-FIL-002 | Filtro por objeto de contrato | Ninguna | 1. Seleccionar objeto (ej: "Bienes", "Servicios", "Consultoría de Obras") | Resultados filtrados por objeto seleccionado |
| BUS-FIL-003 | Filtro por estado | Ninguna | 1. Seleccionar estado (ej: "Vigente", "En Evaluación") | Resultados filtrados por estado |
| BUS-FIL-004 | Filtro por departamento | Ninguna | 1. Seleccionar departamento del dropdown | Se cargan provincias del departamento seleccionado, resultados filtrados |
| BUS-FIL-005 | Filtro por provincia | Departamento seleccionado | 1. Seleccionar provincia | Se cargan distritos, resultados filtrados |
| BUS-FIL-006 | Filtro por distrito | Provincia seleccionada | 1. Seleccionar distrito | Resultados filtrados por distrito |
| BUS-FIL-007 | Autocompletado de entidad | Ninguna | 1. Escribir nombre parcial de entidad | Se muestran sugerencias desplegables de entidades |
| BUS-FIL-008 | Seleccionar entidad sugerida | Sugerencias visibles | 1. Click en una entidad sugerida | Se filtra por esa entidad, se ocultan sugerencias |
| BUS-FIL-009 | Limpiar filtros | Filtros aplicados | 1. Click "Limpiar filtros" | Todos los filtros reseteados, resultados sin filtrar |
| BUS-FIL-010 | Paginación | Resultados > 1 página | 1. Click en botones de paginación (siguiente/anterior/número) | Se muestra la página correspondiente |
| BUS-FIL-011 | Cambiar registros por página | Resultados disponibles | 1. Cambiar selector de registros por página | La tabla se actualiza con el nuevo tamaño de página |
| BUS-FIL-012 | Ordenamiento de resultados | Resultados disponibles | 1. Click en cabecera de columna | Resultados ordenados por esa columna (asc/desc) |
| BUS-FIL-013 | Filtros avanzados toggle | Ninguna | 1. Click "Filtros avanzados" | Panel de filtros avanzados se expande/colapsa |
| BUS-FIL-014 | URL con query string | Ninguna | 1. Navegar a `/buscador-publico?palabraClave=consultoria&departamento=15` | Los filtros se cargan desde la URL, búsqueda ejecutada automáticamente |

### 6.2 Detalle de Contrato

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| BUS-DET-001 | Ver detalle de contrato | Resultados visibles | 1. Click "Ver" en un contrato | Modal/panel con detalle completo del contrato |
| BUS-DET-002 | Cerrar detalle | Detalle abierto | 1. Click "Cerrar" o fuera del modal | Se cierra el panel de detalle |

### 6.3 Descarga de TDR

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| BUS-TDR-001 | Descargar archivo TDR | Contrato con archivos TDR | 1. Click "Descargar TDR" | Se descarga el archivo PDF del TDR |
| BUS-TDR-002 | Re-descargar TDR | Archivo previamente descargado | 1. Click "Re-descargar" | Se descarga nuevamente el archivo |
| BUS-TDR-003 | Reportar archivo no-TDR | Archivo descargado | 1. Click "Reportar como no-TDR" | Se marca el archivo como no relevante |

### 6.4 Análisis IA (requiere permiso)

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| BUS-IA-001 | Analizar TDR con IA (usuario premium) | Autenticado con permiso `analyze-tdr` | 1. Click "Analizar con IA" en un TDR | Se muestra resultado del análisis: requisitos, reglas de ejecución, penalidades, monto referencial |
| BUS-IA-002 | Analizar TDR sin permiso | Sin autenticación o sin permiso | 1. Click "Analizar con IA" | Se muestra modal de acceso restringido o login modal |
| BUS-IA-003 | Limpiar resultado de análisis | Análisis visible | 1. Click "Limpiar análisis" | Se oculta el resultado del análisis |

### 6.5 Seguimiento (requiere permiso)

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| BUS-SEG-001 | Hacer seguimiento a contrato | Autenticado con permiso `follow-contracts` | 1. Click "Seguir" en un contrato | Se crea registro en `contrato_seguimientos`, se muestra confirmación |
| BUS-SEG-002 | Seguimiento sin permiso | Sin permiso `follow-contracts` | 1. Click "Seguir" | Modal de acceso restringido |

### 6.6 Cotización en SEACE (requiere permiso)

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| BUS-COT-001 | Cotizar en SEACE | Autenticado con permiso `cotizar-seace` | 1. Click "Cotizar" en un contrato | Se genera enlace de cotización al portal SEACE |
| BUS-COT-002 | Cotizar sin permiso | Sin permiso `cotizar-seace` | 1. Click "Cotizar" | Modal de acceso restringido |

### 6.7 Login Inline Modal

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| BUS-LIN-001 | Login desde modal | No autenticado, modal abierto | 1. Ingresar email y password en modal 2. Click "Iniciar Sesión" | Se autologa y habilita la acción solicitada |
| BUS-LIN-002 | Login fallido desde modal | No autenticado | 1. Ingresar credenciales erróneas | Mensaje de error inline |

### 6.8 Compatibilidad por Suscriptor

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| BUS-COM-001 | Calcular compatibilidad | Autenticado con suscriptores activos | 1. Click "Calcular compatibilidad" en un contrato | Se muestran scores de compatibilidad por suscriptor |

---

## 7. MÓDULO: DASHBOARD DE CONTRATOS

**Componente Livewire:** `ContratosDashboard` (~381 líneas)  
**Ruta:** `/dashboard` (middleware: `auth`, `verified`)

### 7.1 Tabla de Contratos

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| DSH-TBL-001 | Listado paginado de contratos | Contratos en BD local | 1. Acceder al dashboard | Tabla paginada con contratos |
| DSH-TBL-002 | Búsqueda por texto | Contratos en BD | 1. Escribir en campo de búsqueda | Tabla filtrada por coincidencia en entidad, código u objeto |
| DSH-TBL-003 | Filtro por estado | Contratos en BD | 1. Seleccionar estado en dropdown | Solo contratos del estado seleccionado |
| DSH-TBL-004 | Filtro por objeto | Contratos en BD | 1. Seleccionar objeto de contrato | Filtrado por objeto |
| DSH-TBL-005 | Filtro por departamento | Contratos en BD | 1. Seleccionar departamento | Filtrado por departamento |
| DSH-TBL-006 | Ordenamiento por columna | Contratos en BD | 1. Click en cabecera de columna | Tabla reordenada (toggle asc/desc) |
| DSH-TBL-007 | Limpiar filtros | Filtros activos | 1. Click "Limpiar" | Todos los filtros reseteados |

### 7.2 Estadísticas (KPIs)

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| DSH-KPI-001 | Contadores generales | Contratos en BD | 1. Ver sección de KPIs | Se muestran: Total contratos, Vigentes, En Evaluación, Por Vencer |
| DSH-KPI-002 | Contadores se actualizan con filtros | Filtro de departamento aplicado | 1. Cambiar departamento | Los KPIs se recalculan para el departamento seleccionado |

### 7.3 Gráficos Analíticos

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| DSH-CHR-001 | Distribución por estado | Contratos en BD | 1. Ver gráfico de estados | Gráfico con conteo por estado |
| DSH-CHR-002 | Distribución por objeto | Contratos en BD | 1. Ver gráfico de objetos | Gráfico con conteo por objeto de contrato |
| DSH-CHR-003 | Publicaciones por mes | Contratos en BD | 1. Ver gráfico mensual | Gráfico de línea/barra con publicaciones por mes |
| DSH-CHR-004 | Top entidades | Contratos en BD | 1. Ver gráfico de entidades | Top 10 entidades con más contratos |
| DSH-CHR-005 | Distribución por departamento | Contratos en BD | 1. Ver gráfico de departamentos | Contratos agrupados por departamento |
| DSH-CHR-006 | Filtro de rango de fechas en gráficos | Contratos en BD | 1. Cambiar fecha desde/hasta | Gráficos se recalculan para el rango seleccionado |

### 7.4 Importación desde API SEACE

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| DSH-IMP-001 | Importar por departamento | Permiso `import-tdr`, departamento seleccionado | 1. Seleccionar departamento 2. Seleccionar fecha 3. Click "Importar" | Se consulta API SEACE y se almacenan contratos en BD local vía `updateOrCreate` |
| DSH-IMP-002 | Importar sin permiso | Sin permiso `import-tdr` | 1. Intentar importar | Acción bloqueada por Gate |

---

## 8. MÓDULO: GESTIÓN DE CUENTAS SEACE

**Controlador:** `CuentaSeaceController`  
**Ruta base:** `/cuentas` (middleware: `auth`, `verified`, `can:view-cuentas`)

### 8.1 CRUD de Cuentas

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| CUE-CRU-001 | Listar cuentas | Permiso `view-cuentas` | 1. GET `/cuentas` | Tabla con cuentas ordenadas por activa/último login |
| CUE-CRU-002 | Crear cuenta | Permiso `view-cuentas` | 1. GET `/cuentas/create` 2. Completar nombre, RUC (11 dígitos, unique), password (min 6), email (opcional) 3. POST `/cuentas` | Cuenta creada, password encriptada con `Crypt::encryptString` |
| CUE-CRU-003 | Ver detalle de cuenta | Permiso `view-cuentas` | 1. GET `/cuentas/{id}` | Muestra nombre, RUC, email, estado, último login |
| CUE-CRU-004 | Editar cuenta | Permiso `view-cuentas` | 1. GET `/cuentas/{id}/edit` 2. Modificar campos 3. PUT `/cuentas/{id}` | Cuenta actualizada (password opcional) |
| CUE-CRU-005 | Eliminar cuenta | Permiso `view-cuentas` | 1. DELETE `/cuentas/{id}` | Cuenta eliminada |
| CUE-CRU-006 | RUC duplicado | Intento de crear con RUC existente | 1. Ingresar RUC ya registrado | Error de validación: unique |

### 8.2 Estado de Cuenta

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| CUE-EST-001 | Activar/desactivar cuenta | Permiso `view-cuentas` | 1. POST `/cuentas/{id}/toggle-activa` | Estado `activa` se invierte (true↔false) |
| CUE-EST-002 | Verificar estados visuales | Cuenta con diferentes estados de token | 1. Ver estado en listado | Estado: "Conectada" (verde), "Token Expirado" (amarillo), "Sin Conectar" (gris), "Inactiva" (rojo) |

### 8.3 Seguridad

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| CUE-SEC-001 | Password se encripta | Cuenta creada | 1. Verificar BD | Campo `password` almacenado encriptado (no hash, sino `Crypt::encryptString`) |
| CUE-SEC-002 | Campos sensibles ocultos en JSON | Serialización del modelo | 1. Verificar respuesta API o `toArray()` | `password`, `access_token`, `refresh_token` no visibles |

---

## 9. MÓDULO: PRUEBA DE ENDPOINTS SEACE

**Componente Livewire:** `PruebaEndpoints` (~1320 líneas)  
**Ruta:** `/prueba-endpoints` (middleware: `auth`, `verified`, `can:view-prueba-endpoints`)

### 9.1 Autenticación SEACE

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| END-AUT-001 | Login en SEACE | Cuenta SEACE activa | 1. Seleccionar cuenta 2. Click "Probar Login" | Se obtiene access_token y refresh_token, se actualizan en BD |
| END-AUT-002 | Refresh Token | Token existente | 1. Click "Probar Refresh" | Nuevo access_token generado |
| END-AUT-003 | Login sin cuenta seleccionada | Ninguna cuenta seleccionada | 1. Click "Probar Login" | Mensaje de error informativo |

### 9.2 Buscador de Contratos

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| END-BUS-001 | Búsqueda con filtros | Token válido | 1. Configurar filtros (departamento, objeto, keyword) 2. Click "Buscar" | Lista de contratos del SEACE |
| END-BUS-002 | Filtros geográficos en cascada | Token válido | 1. Seleccionar departamento 2. Verificar que se cargan provincias 3. Seleccionar provincia 4. Verificar distritos | Cascada funcional |

### 9.3 Archivos y Análisis

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| END-ARC-001 | Listar archivos de contrato | Contrato seleccionado | 1. Click "Ver archivos" | Lista de archivos TDR del contrato |
| END-ARC-002 | Descargar archivo | Archivo listado | 1. Click "Descargar" | Archivo PDF descargado |
| END-ARC-003 | Analizar con IA | Archivo listado | 1. Click "Analizar" | Resultado del análisis IA mostrado (requisitos, reglas, penalidades, monto) |

### 9.4 Tablas Maestras

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| END-MAE-001 | Consultar maestras | Token válido | 1. Seleccionar tipo de maestra 2. Click "Consultar" | Datos de la tabla maestra del SEACE |

### 9.5 Importación Masiva con Notificación

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| END-IMP-001 | Importar procesos TDR | Token válido, suscriptores activos | 1. Seleccionar fecha 2. Configurar límite 3. Click "Importar" | Procesos importados y notificados a suscriptores Telegram + WhatsApp |
| END-IMP-002 | Limpiar caché TDR | N/A | 1. Click "Limpiar caché" | Caché de procesos limpiada |
| END-IMP-003 | Enviar al bot | Contrato seleccionado | 1. Click "Enviar al bot" | Mensaje enviado al bot de Telegram |

---

## 10. MÓDULO: REPOSITORIO TDR

**Componente Livewire:** `TdrRepositoryManager` (~142 líneas)  
**Ruta:** `/tdr-repository` (middleware: `auth`, `verified`, `can:view-tdr-repository`)

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| TDR-REP-001 | Listado paginado de archivos | Archivos en BD | 1. Acceder a `/tdr-repository` | Tabla paginada de `ContratoArchivo` con nombre, código, entidad, estado análisis |
| TDR-REP-002 | Buscar archivos | Archivos en BD | 1. Escribir en campo de búsqueda | Filtrado por nombre, código o entidad |
| TDR-REP-003 | Filtrar por estado de análisis | Archivos en BD | 1. Seleccionar estado (pendiente/exitoso/fallido) | Solo archivos con ese estado de análisis |
| TDR-REP-004 | Toggle "Solo con análisis" | N/A | 1. Activar toggle | Solo archivos que tienen al menos un análisis |
| TDR-REP-005 | Descargar archivo | Archivo almacenado localmente | 1. Click "Descargar" | PDF descargado directamente |
| TDR-REP-006 | Descargar archivo no local | Archivo sin copia local | 1. Click "Descargar" | Descarga desde SEACE como fallback |
| TDR-REP-007 | Re-analizar con IA | Archivo existente | 1. Click "Re-analizar" | Nuevo análisis IA ejecutado y guardado |
| TDR-REP-008 | KPIs del repositorio | Archivos en BD | 1. Ver contadores | Muestra: Total archivos, Total analizados exitosamente |

---

## 11. MÓDULO: ANÁLISIS DE TDR CON IA

**Servicio principal:** `TdrAnalysisService`  
**Microservicio:** Python FastAPI en `http://127.0.0.1:8001`  
**LLM:** Gemini 2.5 Flash (1M tokens context)

### 11.1 Flujo de Análisis

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| IA-ANA-001 | Análisis individual exitoso | Microservicio activo, PDF válido | 1. Solicitar análisis de TDR | JSON con: requisitos_calificacion, reglas_ejecucion, penalidades, monto_referencial |
| IA-ANA-002 | Análisis con caché | Análisis previo exitoso en BD | 1. Solicitar análisis del mismo archivo | Se retorna resultado cacheado sin llamar a IA |
| IA-ANA-003 | Análisis con lock concurrente | Otro análisis del mismo archivo en progreso | 1. Solicitar análisis simultáneo | Se espera o retorna mensaje de "análisis en progreso" (lock atómico 3 min) |
| IA-ANA-004 | Microservicio caído | Servicio Python no disponible | 1. Solicitar análisis | Error manejado graciosamente con mensaje informativo |
| IA-ANA-005 | Health check del analizador | N/A | 1. Probar conexión al microservicio | Respuesta con estado "OK" si activo |
| IA-ANA-006 | Persistencia del análisis | Análisis exitoso | 1. Verificar BD | Registro en `tdr_analisis` con estado `exitoso`, proveedor, modelo, duración, tokens, costo estimado |
| IA-ANA-007 | Análisis fallido | PDF corrupto o error de IA | 1. Enviar PDF inválido | Registro en `tdr_analisis` con estado `fallido` y campo `error` |

### 11.2 Compatibilidad Empresa-Contrato

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| IA-COM-001 | Score de compatibilidad | Suscriptor con company_copy, análisis exitoso | 1. Solicitar compatibilidad | Score numérico (0-100) con detalle de coincidencia |
| IA-COM-002 | Score cacheado | Score previo sin cambios en company_copy | 1. Solicitar compatibilidad | Reutiliza score existente sin llamar a IA |
| IA-COM-003 | Score con company_copy actualizado | Cambio en company_copy | 1. Solicitar compatibilidad | Nuevo score calculado (force refresh) |

---

## 12. MÓDULO: SUSCRIPTORES Y NOTIFICACIONES

**Componente Livewire:** `Suscriptores` (~830 líneas)  
**Ruta:** `/suscriptores` (middleware: `auth`, `verified`, `can:view-suscriptores`)

### 12.1 Suscriptores Telegram

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| SUS-TEL-001 | Agregar suscriptor Telegram | Max 2 por usuario no alcanzado | 1. Ingresar chat_id (único), nombre, username, company_copy (min 30 chars), keywords (max 5) 2. Guardar | Suscriptor creado en `telegram_subscriptions` |
| SUS-TEL-002 | Máximo suscriptores | 2 suscriptores Telegram activos | 1. Intentar agregar otro | Error: máximo 2 suscriptores por usuario |
| SUS-TEL-003 | Chat ID duplicado | Chat ID ya registrado | 1. Ingresar chat_id existente | Error de validación: unique |
| SUS-TEL-004 | Editar suscriptor | Suscriptor existente | 1. Click "Editar" 2. Modificar campos 3. Guardar | Suscriptor actualizado |
| SUS-TEL-005 | Activar/desactivar | Suscriptor existente | 1. Click toggle activo | Estado `activo` invertido |
| SUS-TEL-006 | Eliminar suscriptor | Suscriptor existente | 1. Click "Eliminar" 2. Confirmar | Suscriptor eliminado |
| SUS-TEL-007 | Prueba de notificación | Suscriptor activo, Telegram habilitado | 1. Click "Probar" | Mensaje de prueba enviado al chat_id |
| SUS-TEL-008 | Company copy requerido | Campo vacío o < 30 chars | 1. Intentar guardar sin company_copy | Error de validación |

### 12.2 Suscriptores Email

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| SUS-EMA-001 | Crear suscripción email | No tiene suscripción email | 1. Ingresar email, keywords, activar/desactivar "notificar todo" 2. Guardar | Suscripción email creada (1 por usuario) |
| SUS-EMA-002 | Editar suscripción | Suscripción existente | 1. Modificar email o keywords 2. Guardar | Suscripción actualizada |
| SUS-EMA-003 | Toggle activo | Email suscripción existente | 1. Click toggle | Estado invertido |
| SUS-EMA-004 | Eliminar suscripción | Suscripción existente | 1. Click "Eliminar" | Suscripción eliminada |
| SUS-EMA-005 | Prueba de notificación email | Suscripción activa | 1. Click "Probar" | Email de prueba enviado |
| SUS-EMA-006 | Modo "Notificar todo" | Suscripción con flag | 1. Activar "Notificar todo" | Recibe todos los contratos sin filtrar por keywords |

### 12.3 Suscriptores WhatsApp

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| SUS-WA-001 | Crear suscripción WhatsApp | WhatsApp habilitado | 1. Ingresar teléfono (10-15 dígitos), nombre, company_copy (min 30), keywords (max 5) 2. Guardar | Suscripción creada en `whatsapp_subscriptions` |
| SUS-WA-002 | Teléfono inválido | Formato incorrecto | 1. Ingresar teléfono con letras o longitud incorrecta | Error de validación por regex |
| SUS-WA-003 | CRUD completo WhatsApp | Suscripción existente | 1. Editar, togglear, eliminar | Operaciones exitosas |
| SUS-WA-004 | Prueba de notificación | Suscripción activa, WhatsApp habilitado | 1. Click "Probar" | Mensaje de prueba enviado por WhatsApp |

### 12.4 Keywords (Palabras Clave)

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| SUS-KEY-001 | Seleccionar keyword del catálogo | Catálogo de `NotificationKeyword` | 1. Buscar y seleccionar keyword | Keyword agregado a la suscripción |
| SUS-KEY-002 | Agregar keyword manual | N/A | 1. Escribir keyword (3-80 chars) 2. Click "Agregar" | Keyword creado en `notification_keywords` y asociado |
| SUS-KEY-003 | Máximo 5 keywords | 5 keywords asignados | 1. Intentar agregar otro | Error: máximo 5 palabras clave |
| SUS-KEY-004 | Quitar keyword | Keywords asignados | 1. Click "X" en keyword | Keyword removido de la suscripción |
| SUS-KEY-005 | Búsqueda de keywords | Catálogo existente | 1. Escribir texto en buscador | Keywords filtrados por coincidencia |

### 12.5 Admin: Vista de Todos los Suscriptores

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| SUS-ADM-001 | Admin ve todos los suscriptores | Rol admin | 1. Acceder a `/suscriptores` | Ve suscriptores de todos los usuarios |
| SUS-ADM-002 | Proveedor ve solo los suyos | Rol proveedor | 1. Acceder a `/suscriptores` | Solo ve sus propios suscriptores |

---

## 13. MÓDULO: SEGUIMIENTOS DE CONTRATOS

**Componente Livewire:** `SeguimientosCalendario` (~173 líneas)  
**Ruta:** `/seguimientos` (middleware: `auth`, `verified`, `can:follow-contracts`)

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| SEG-CAL-001 | Vista calendario mensual | Seguimientos existentes | 1. Acceder a `/seguimientos` | Grilla tipo calendario con el mes actual, mostrando procesos como eventos por día |
| SEG-CAL-002 | Navegación mes anterior | N/A | 1. Click "← Mes anterior" | Calendario retrocede un mes |
| SEG-CAL-003 | Navegación mes siguiente | N/A | 1. Click "Mes siguiente →" | Calendario avanza un mes |
| SEG-CAL-004 | Eventos con rango de fechas | Seguimiento con fecha_inicio y fecha_fin | 1. Ver calendario | El evento se muestra en todos los días del rango |
| SEG-CAL-005 | Indicador de urgencia | Seguimiento próximo a vencer | 1. Ver calendario | Color de urgencia: crítico (≤2 días, rojo), alto (≤5, naranja), medio (≤7, amarillo), estable (verde) |
| SEG-CAL-006 | Máximo eventos por día | Más de 2 eventos en un día | 1. Ver celda del día | Muestra 2 eventos visibles + indicador "+N más" |
| SEG-CAL-007 | Ver detalle de seguimiento | Seguimiento visible | 1. Click en evento del calendario | Modal con detalle completo: código, entidad, objeto, estado, fechas, snapshot |
| SEG-CAL-008 | Cerrar detalle | Modal de detalle abierto | 1. Click "Cerrar" | Modal cerrado |
| SEG-CAL-009 | Zona horaria Lima | N/A | 1. Verificar fechas mostradas | Todas las fechas en zona `America/Lima` |

---

## 14. MÓDULO: MIS PROCESOS NOTIFICADOS

**Componente Livewire:** `MisProcesosNotificados` (~314 líneas)  
**Ruta:** `/mis-procesos` (middleware: `auth`, `verified`, `can:view-mis-procesos`)

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| MIS-PRO-001 | Listado de procesos notificados | Notificaciones enviadas al usuario | 1. Acceder a `/mis-procesos` | Tabla paginada de procesos notificados con código, entidad, descripción, canal, fecha |
| MIS-PRO-002 | KPIs por canal | Notificaciones existentes | 1. Ver contadores | Total procesos, Total Telegram, Total WhatsApp, Total Email |
| MIS-PRO-003 | Filtrar por texto | Procesos existentes | 1. Escribir en búsqueda | Filtrado por código, entidad o descripción |
| MIS-PRO-004 | Filtrar por canal | Procesos existentes | 1. Seleccionar canal (telegram/whatsapp/email) | Solo procesos notificados por ese canal |
| MIS-PRO-005 | Filtrar por rango de fechas | Procesos existentes | 1. Seleccionar fecha desde y hasta | Procesos dentro del rango |
| MIS-PRO-006 | Re-notificar por Telegram | Proceso con suscripción Telegram | 1. Click "Re-notificar Telegram" | Re-envío al chat de Telegram |
| MIS-PRO-007 | Re-notificar por WhatsApp | Proceso existente | 1. Click "Re-notificar WhatsApp" | Re-envío por WhatsApp |
| MIS-PRO-008 | Re-notificar por Email | Proceso existente | 1. Click "Re-notificar Email" | Re-envío por email |
| MIS-PRO-009 | Limpiar filtros | Filtros activos | 1. Click "Limpiar" | Todos los filtros reseteados |

---

## 15. MÓDULO: SUSCRIPCIONES PREMIUM Y PAGOS

### 15.1 Planes Disponibles

| Plan | Slug | Precio | Duración | Trial |
|---|---|---|---|---|
| Trial | `trial` | Gratis | 15 días | Sí (solo una vez) |
| Mensual | `monthly` | S/ 49.00 | 30 días | Trial previo al pago |
| Anual | `yearly` | S/ 470.00 | 365 días | No aplica trial |

### 15.2 Vista del Usuario — `MiSuscripcion`

**Componente:** `MiSuscripcion` (~148 líneas)  
**Ruta implícita:** Sección dentro del perfil o ruta dedicada

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| PRE-USR-001 | Ver suscripción activa | Suscripción activa | 1. Acceder a Mi Suscripción | Muestra plan, estado, pasarela, método de pago, días restantes |
| PRE-USR-002 | Ver historial de auditoría | Historial existente | 1. Ver sección de historial | Eventos grant/revoke con fechas, fuente y detalles |
| PRE-USR-003 | Activar/desactivar auto-renovación | Suscripción pagada activa | 1. Toggle "Auto-renovar" | Campo `auto_renew` actualizado |
| PRE-USR-004 | Cancelar suscripción | Suscripción activa | 1. Click "Cancelar" 2. Confirmar en modal | Suscripción cancelada, rol premium revocado |
| PRE-USR-005 | Sin suscripción | Usuario sin suscripción | 1. Acceder | Mensaje invitando a ver planes |

### 15.3 Checkout y Pago

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| PRE-CHK-001 | Iniciar trial | Usuario sin trial previo | 1. GET `/planes/checkout/monthly` 2. Seleccionar "Probar gratis" | Suscripción trial creada (15 días), rol `proveedor-premium` asignado |
| PRE-CHK-002 | Trial ya usado | Usuario con trial previo | 1. Intentar activar trial | Error: "Ya has usado tu período de prueba" |
| PRE-CHK-003 | Checkout con Openpay | Openpay configurada como gateway | 1. GET `/planes/checkout/{plan}` | Formulario de pago in-page con Openpay.js |
| PRE-CHK-004 | Checkout con MercadoPago | MercadoPago configurada | 1. GET `/planes/checkout/{plan}` | Redirige a checkout externo de MercadoPago |
| PRE-CHK-005 | Pago exitoso (Openpay) | Token de tarjeta válido | 1. POST `/planes/charge` con token_id + device_session_id | Suscripción creada, cobro procesado, JSON con éxito |
| PRE-CHK-006 | Callback MercadoPago | Pago aprobado en MP | 1. GET `/planes/callback?payment_id=...` | Suscripción activada, vista de confirmación |
| PRE-CHK-007 | Pago fallido | Tarjeta rechazada | 1. Intentar pago | Error mostrado al usuario |

### 15.4 Admin — `SuscripcionesPremium`

**Componente:** `SuscripcionesPremium` (~208 líneas)  
**Ruta:** `/suscripciones-premium` (middleware: `auth`, `verified`, `can:manage-subscriptions`)

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| PRE-ADM-001 | Listar suscripciones | Suscripciones existentes | 1. Acceder | Tabla paginada con filtros de búsqueda, estado y plan |
| PRE-ADM-002 | KPIs de suscripciones | Datos existentes | 1. Ver contadores | Activas, Trial, Pagadas, Expiradas |
| PRE-ADM-003 | Otorgar premium manual | Usuarios sin suscripción | 1. Click "Otorgar Premium" 2. Seleccionar usuario, plan, días 3. Confirmar | Suscripción creada, rol premium asignado, auditoría registrada |
| PRE-ADM-004 | Extender suscripción | Suscripción existente | 1. Click "Extender" 2. Ingresar días 3. Confirmar | `ends_at` extendido por N días |
| PRE-ADM-005 | Cancelar suscripción (admin) | Suscripción activa | 1. Click "Cancelar" | Suscripción cancelada, rol revocado |
| PRE-ADM-006 | Activar trial para usuario | Usuario sin trial previo | 1. Click "Activar Trial" | Trial de 15 días activado |
| PRE-ADM-007 | Admin excluido | Administradores | 1. Verificar listado | Admins no aparecen como candidatos para suscripción |

### 15.5 Renovación y Expiración Automáticas

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| PRE-AUT-001 | Expiración automática | Suscripción vencida | Comando `subscriptions:expire` (cada hora) | Suscripción marcada como expirada, rol premium revocado |
| PRE-AUT-002 | Renovación con tarjeta | Suscripción a 24h de vencer, auto_renew=true | Comando `subscriptions:renew` (cada 6h) | Cobro recurrente ejecutado, nueva suscripción creada |
| PRE-AUT-003 | Renovación sin auto_renew | auto_renew=false | Comando `subscriptions:renew` | No se renueva, suscripción expira normalmente |

---

## 16. MÓDULO: ROLES Y PERMISOS (RBAC)

**Componente Livewire:** `RolesPermisos` (~113 líneas)  
**Ruta:** `/roles-permisos` (middleware: `auth`, `verified`, `can:manage-roles-permissions`)

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| ROL-PER-001 | Ver matriz roles-permisos | Admin | 1. Acceder a `/roles-permisos` | Tabla con roles como columnas y permisos como filas (checkboxes) |
| ROL-PER-002 | Modificar permisos de rol | Admin | 1. Cambiar checkboxes 2. Click "Guardar" | Permisos del rol actualizados en pivot `permission_role` |
| ROL-PER-003 | Asignar rol a usuario | Admin | 1. Buscar usuario en tabla 2. Seleccionar rol 3. Guardar | Rol asignado en pivot `role_user` |
| ROL-PER-004 | Protección último admin | Solo queda 1 admin | 1. Intentar cambiar rol del último admin | Error: "No se puede eliminar el último administrador" |
| ROL-PER-005 | Paginación de usuarios | Más de 1 página de usuarios | 1. Navegar páginas | Paginación funcional |

---

## 17. MÓDULO: CONFIGURACIÓN DEL SISTEMA

**Componente Livewire:** `Configuracion` (~420 líneas)  
**Ruta:** `/configuracion` (middleware: `auth`, `verified`, `can:view-configuracion`)

### 17.1 Telegram Bot

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| CFG-TEL-001 | Configurar bot Telegram | Admin | 1. Ingresar token y chat_id 2. Guardar | Variables escritas en `.env`, caché de config limpiada |
| CFG-TEL-002 | Probar conexión Telegram | Token configurado | 1. Click "Probar" | Mensaje de prueba enviado, resultado mostrado |
| CFG-TEL-003 | Habilitar/deshabilitar Telegram | Admin | 1. Toggle "Habilitado" 2. Guardar | `TELEGRAM_ENABLED` actualizado en `.env` |

### 17.2 Telegram Admin Bot

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| CFG-TAD-001 | Configurar bot admin | Admin | 1. Ingresar token admin y chat_id admin 2. Guardar | Variables escritas en `.env` |
| CFG-TAD-002 | Probar bot admin | Token admin configurado | 1. Click "Probar Admin" | Resultado de prueba mostrado |

### 17.3 Analizador TDR (IA)

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| CFG-IA-001 | Configurar URL analizador | Admin | 1. Ingresar URL del microservicio 2. Guardar | URL guardada en `.env` |
| CFG-IA-002 | Probar conexión analizador | URL configurada | 1. Click "Probar" | Health check ejecutado, resultado mostrado |
| CFG-IA-003 | Habilitar/deshabilitar IA | Admin | 1. Toggle "Habilitado" 2. Guardar | Estado actualizado |

### 17.4 WhatsApp Bot

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| CFG-WA-001 | Configurar WhatsApp | Admin | 1. Ingresar token y group_id 2. Guardar | Variables en `.env` actualizadas |

### 17.5 Pasarelas de Pago

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| CFG-PAG-001 | Configurar MercadoPago | Admin | 1. Ingresar access_token, public_key, webhook_secret 2. Guardar | Credenciales en `.env` |
| CFG-PAG-002 | Configurar Openpay | Admin | 1. Ingresar merchant_id, private_key, public_key, modo producción 2. Guardar | Credenciales en `.env` |
| CFG-PAG-003 | Probar gateway | Gateway configurado | 1. Click "Probar" | Conexión verificada, resultado mostrado |
| CFG-PAG-004 | Cambiar gateway activo | Admin | 1. Seleccionar gateway default (openpay/mercadopago) 2. Guardar | `PAYMENT_GATEWAY` actualizado |

---

## 18. MÓDULO: PERFIL DE USUARIO

**Componente Livewire:** `Perfil` (~119 líneas)  
**Ruta:** `/perfil` (middleware: `auth`, `verified`)

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| PER-DAT-001 | Actualizar nombre | Autenticado | 1. Modificar nombre 2. Click "Guardar" | Nombre actualizado |
| PER-DAT-002 | Actualizar email | Autenticado | 1. Modificar email 2. Guardar | Email actualizado, `email_verified_at` reseteado, se envía nuevo email de verificación |
| PER-DAT-003 | Email duplicado | Email ya registrado | 1. Ingresar email existente | Error de validación: unique |
| PER-DAT-004 | Actualizar teléfono, RUC, razón social | Autenticado | 1. Completar campos empresa 2. Guardar | Datos actualizados |
| PER-PWD-001 | Cambiar contraseña | Autenticado | 1. Expandir sección de password 2. Ingresar password actual, nueva (min 8), confirmación 3. Guardar | Contraseña actualizada |
| PER-PWD-002 | Password actual incorrecta | Autenticado | 1. Ingresar password actual errónea | Error: "La contraseña actual no es correcta" |
| PER-PWD-003 | Confirmación no coincide | Autenticado | 1. Passwords de confirmación diferentes | Error de validación: confirmed |

---

## 19. MÓDULO: FORMULARIO DE CONTACTO

**Componente Livewire:** `Contacto` (~83 líneas)  
**Ruta:** `/contacto` (pública)

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| CON-FOR-001 | Envío exitoso | Ninguna | 1. Completar nombre (min 2), email (válido), asunto (min 3), mensaje (min 10, max 2000) 2. Enviar | Email enviado a `services@sunqupacha.com`, campos reseteados, mensaje de éxito |
| CON-FOR-002 | Validación de campos | Campos vacíos | 1. Enviar formulario vacío | Errores de validación en todos los campos |
| CON-FOR-003 | Email inválido | N/A | 1. Ingresar email con formato incorrecto | Error de validación: email |
| CON-FOR-004 | Mensaje demasiado corto | N/A | 1. Ingresar mensaje < 10 caracteres | Error de validación: min:10 |
| CON-FOR-005 | Mensaje demasiado largo | N/A | 1. Ingresar mensaje > 2000 caracteres | Error de validación: max:2000 |

---

## 20. MÓDULO: BOTS INTERACTIVOS (TELEGRAM Y WHATSAPP)

### 20.1 Telegram Bot Listener

**Comando:** `php artisan telegram:listen`  
**Modo:** Long-polling (loop infinito, 10s timeout por petición)

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| BOT-TEL-001 | Callback: Analizar TDR | Bot activo, contrato en caché | 1. Click botón "🤖 Analizar con IA" en Telegram | Bot ejecuta análisis IA y envía resultado formateado en HTML |
| BOT-TEL-002 | Callback: Descargar TDR | Bot activo, archivo disponible | 1. Click botón "📄 Descargar TDR" | Bot envía PDF como documento al chat |
| BOT-TEL-003 | Callback: Ver Compatibilidad | Bot activo, suscriptor con company_copy | 1. Click botón "📊 Compatibilidad" | Bot calcula y envía score de compatibilidad |
| BOT-TEL-004 | Callback: Cotizar | Bot activo | 1. Click botón "💰 Cotizar" | Bot envía link de cotización al portal SEACE |
| BOT-TEL-005 | Dedup de callbacks | Mismo callback recibido 2 veces | 1. Doble-click rápido en botón | Solo se procesa una vez (dedup atómico por callback_id) |
| BOT-TEL-006 | Lock anti doble-click | Análisis en progreso | 1. Click en analizar mientras otro análisis corre | Mensaje: "Análisis en progreso, espere..." |
| BOT-TEL-007 | Señales SIGINT/SIGTERM | Bot corriendo | 1. Enviar señal de terminación | Bot se detiene limpiamente |
| BOT-TEL-008 | Instancia única (Isolatable) | Bot ya corriendo | 1. Ejecutar segundo `telegram:listen` | Se impide ejecución duplicada |

### 20.2 WhatsApp Bot Listener

**Comando:** `php artisan whatsapp:listen`  
**Modo:** Procesa payloads encolados por webhook en cache

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| BOT-WA-001 | Callback: Analizar TDR | Bot activo, payload en cache | 1. Responder "analizar_{id}" en WhatsApp | Bot ejecuta análisis IA y envía resultado |
| BOT-WA-002 | Callback: Descargar TDR | Bot activo | 1. Responder "descargar_{id}" | Bot envía PDF como documento |
| BOT-WA-003 | Callback: Compatibilidad | Bot activo | 1. Responder "compatibilidad_{id}" | Score de compatibilidad enviado |
| BOT-WA-004 | Instancia única | Bot ya corriendo | 1. Ejecutar segundo `whatsapp:listen` | Ejecución bloqueada (Isolatable) |

---

## 21. MÓDULO: BOT ADMIN TELEGRAM

**Comando:** `php artisan telegram:admin-listen`  
**Modo:** Long-polling exclusivo para admin

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| ADM-BOT-001 | Comando /start | Admin verificado | 1. Enviar `/start` | Mensaje de bienvenida con botones inline |
| ADM-BOT-002 | Comando /escanear | Admin | 1. Enviar `/escanear` | Estado de servicios systemd (vigilante-queue, vigilante-scheduler, telegram-bot, whatsapp-bot, analizador-tdr) |
| ADM-BOT-003 | Comando /usuarios | Admin | 1. Enviar `/usuarios` | Estadísticas de usuarios: total, verificados, premium, trial, nuevos hoy |
| ADM-BOT-004 | Comando /ingresos | Admin | 1. Enviar `/ingresos` | Resumen financiero: ingresos del mes, suscripciones activas, plan más popular |
| ADM-BOT-005 | Comando /help | Admin | 1. Enviar `/help` | Lista de comandos disponibles |
| ADM-BOT-006 | No-admin intenta comando | Chat_id no configurado como admin | 1. Enviar cualquier comando | No responde (solo responde al admin configurado) |

---

## 22. MÓDULO: JOBS PROGRAMADOS (SCHEDULER)

| ID | Job/Comando | Frecuencia | Horario | Zona | Descripción |
|---|---|---|---|---|---|
| SCH-001 | `ImportarTdrNotificarJob` | Cada 2 horas | L-D, 06:00–20:00 | America/Lima | Importa procesos SEACE y notifica a suscriptores Telegram + WhatsApp |
| SCH-002 | `ImportarContratosDiarioJob` | Diario | 02:00 | America/Lima | Importa contratos del día anterior por todos los departamentos para dashboard |
| SCH-003 | `subscriptions:expire` | Cada hora | 24h | America/Lima | Expira suscripciones/trials vencidos, revoca rol premium |
| SCH-004 | `subscriptions:renew` | Cada 6 horas | 24h | America/Lima | Renueva suscripciones próximas a vencer con cobro recurrente |
| SCH-005 | `NotificarEmailSuscriptoresJob` | Cada 2 horas | L-D, 06:00–20:00 | America/Lima | Envía emails de procesos nuevos a suscriptores email |

### Pruebas de Scheduler

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| SCH-TST-001 | ImportarTdrNotificar a las 06:00 | Suscriptores activos | Ejecución automática | Importa procesos de HOY + AYER (brecha nocturna) |
| SCH-TST-002 | ImportarTdrNotificar a las 08:00+ | Suscriptores activos | Ejecución automática | Importa solo procesos de HOY |
| SCH-TST-003 | Dedup per-subscriber | Proceso ya notificado al suscriptor | Segunda ejecución | No re-envía (dedup en `notification_sends`) |
| SCH-TST-004 | Importación diaria 02:00 | BD operativa | Ejecución automática | Contratos de todos los departamentos del día anterior almacenados |
| SCH-TST-005 | Expiración de trials | Trial con `trial_ends_at` pasado | Ejecución de `subscriptions:expire` | Trial marcado expired, rol revocado |
| SCH-TST-006 | Renovación exitosa | Suscripción a 24h de vencer, auto_renew=true, tarjeta válida | Ejecución de `subscriptions:renew` | Cobro procesado, nueva suscripción |
| SCH-TST-007 | Renovación fallida | Tarjeta inválida/rechazada | Ejecución de `subscriptions:renew` | Error logueado, suscripción no renovada |

---

## 23. MÓDULO: API REST (SANCTUM)

### 23.1 Autenticación API

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| API-AUT-001 | Obtener token | Usuario registrado | 1. POST `/api/auth/token` con email + password | JSON con `token` (Bearer), datos de usuario |
| API-AUT-002 | Credenciales inválidas | N/A | 1. POST `/api/auth/token` con datos erróneos | HTTP 422 o 401 |
| API-AUT-003 | Revocar token | Token válido | 1. DELETE `/api/auth/token` con header `Authorization: Bearer {token}` | `{ "revoked": true }` |
| API-AUT-004 | Obtener usuario actual | Token válido | 1. GET `/api/user` con Bearer token | JSON con datos del usuario |
| API-AUT-005 | Acceso sin token | N/A | 1. GET `/api/user` sin token | HTTP 401 Unauthenticated |
| API-AUT-006 | Rate limiting | N/A | 1. Enviar más de 60 requests/minuto | HTTP 429 Too Many Requests |

---

## 24. MÓDULO: WEBHOOKS

### 24.1 Webhooks de Pago

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| WH-PAY-001 | Webhook Openpay | Pago procesado en Openpay | 1. POST `/api/webhooks/openpay` | Firma verificada, suscripción activada si pago aprobado |
| WH-PAY-002 | Webhook MercadoPago | Pago procesado en MP | 1. POST `/api/webhooks/mercadopago` | Firma HMAC-SHA256 verificada, suscripción activada |
| WH-PAY-003 | Firma inválida | Webhook con firma manipulada | 1. POST con firma alterada | Request rechazado |

### 24.2 Webhook WhatsApp (Meta)

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| WH-WA-001 | Verificación GET | Meta requiere verificación | 1. GET `/api/webhooks/whatsapp?hub.verify_token=...&hub.challenge=...&hub.mode=subscribe` | Responde con `hub.challenge` si token coincide |
| WH-WA-002 | Token de verificación inválido | Token incorrecto | 1. GET con token erróneo | HTTP 403 Forbidden |
| WH-WA-003 | Recibir mensaje | Meta envía payload | 1. POST `/api/webhooks/whatsapp` con payload | Payload encolado en cache (max 500, TTL 24h), responde HTTP 200 |
| WH-WA-004 | Payload no WhatsApp | Tipo distinto a `whatsapp_business_account` | 1. POST con otro tipo | Ignorado, responde HTTP 200 |

---

## 25. MÓDULO: EMAILS TRANSACCIONALES

| ID | Email | Trigger | Destinatario | Contenido |
|---|---|---|---|---|
| EML-001 | Email de Bienvenida (`WelcomeMail`) | Registro exitoso (`Registered` event) | Usuario nuevo | Bienvenida + links a manual, login, planes, buscador |
| EML-002 | Verificación de Email (`VerifyEmailNotification`) | Registro o cambio de email | Usuario | Link firmado de verificación (expira 60 min), mensaje en español |
| EML-003 | Reset de Contraseña (`ResetPasswordNotification`) | Solicitud de reset | Usuario | Link de reset con token, expiración configurable, en español |
| EML-004 | Nuevo Proceso SEACE (`NuevoProcesoSeace`) | Job de email notificación | Suscriptor email | Datos del contrato, keywords coincidentes, link de seguimiento |
| EML-005 | Formulario de Contacto (`ContactoMail`) | Envío del formulario | `services@sunqupacha.com` | Nombre, email, asunto, mensaje del visitante |

### Pruebas de Email

| ID | Caso de Prueba | Precondición | Pasos | Resultado Esperado |
|---|---|---|---|---|
| EML-TST-001 | Email de bienvenida se encola | Registro exitoso | 1. Registrar usuario | Email encolado vía `Mail::to()->queue()` |
| EML-TST-002 | Verificación con link firmado | Email recibido | 1. Click en link | Email verificado correctamente |
| EML-TST-003 | Reset password completo | Email recibido | 1. Click link → 2. Nueva password → 3. Login | Flujo completo funcional |
| EML-TST-004 | Email de nuevo proceso | Suscripción activa con keywords | 1. Esperar ejecución del job | Email recibido con datos del contrato SEACE |

---

## 26. MÓDULO: PÁGINAS DE ERROR

| ID | Código | Ruta | Descripción | Resultado Esperado |
|---|---|---|---|---|
| ERR-001 | 403 | Acceso denegado | Forbidden | Página personalizada con diseño Sequence |
| ERR-002 | 404 | Página no encontrada | Not Found | Página personalizada con diseño Sequence |
| ERR-003 | 500 | Error del servidor | Internal Server Error | Página personalizada con diseño Sequence |
| ERR-004 | 503 | Mantenimiento | Service Unavailable | Página personalizada con diseño Sequence |

---

## 27. MODELO DE DATOS (ESQUEMA BD)

### 27.1 Tablas del Sistema (~30)

| Categoría | Tablas | Cantidad |
|---|---|---|
| **Laravel Core** | `users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `personal_access_tokens` | 9 |
| **Contratos SEACE** | `contratos`, `contrato_archivos`, `tdr_analisis`, `contrato_seguimientos` | 4 |
| **Cuentas SEACE** | `cuentas_seace` | 1 |
| **RBAC** | `roles`, `permissions`, `permission_role`, `role_user` | 4 |
| **Notificaciones Telegram** | `telegram_subscriptions`, `notification_keywords`, `notification_keyword_subscription`, `subscription_contract_matches` | 4 |
| **Notificaciones WhatsApp** | `whatsapp_subscriptions`, `whatsapp_subscription_keyword` | 2 |
| **Notificaciones Email** | `email_subscriptions`, `email_contract_sends`, `email_subscription_keyword` | 3 |
| **Tracking de Envíos** | `notified_processes`, `notification_sends` | 2 |
| **Suscripciones Premium** | `subscriptions`, `premium_audit_logs` | 2 |

### 27.2 Diagrama de Relaciones Principales

```
User ──HasMany──> TelegramSubscription ──BelongsToMany──> NotificationKeyword
 │                     └──HasMany──> SubscriptionContractMatch
 ├──HasMany──> WhatsAppSubscription ──BelongsToMany──> NotificationKeyword
 │                     └──HasMany──> SubscriptionContractMatch
 ├──HasMany──> EmailSubscription ──BelongsToMany──> NotificationKeyword
 │                  └──HasMany──> EmailContractSend
 ├──HasMany──> ContratoSeguimiento
 ├──HasMany──> Subscription
 ├──BelongsToMany──> Role ──BelongsToMany──> Permission

Contrato ──HasMany──> ContratoArchivo ──HasMany──> TdrAnalisis
 └──HasManyThrough──> TdrAnalisis (via ContratoArchivo)

NotifiedProcess ──HasMany──> NotificationSend ──BelongsTo──> User

PremiumAuditLog ──BelongsTo──> User, Subscription

CuentaSeace (standalone — credenciales para API SEACE)
```

### 27.3 Modelos Eloquent (18)

| Modelo | Tabla | Relaciones Clave |
|---|---|---|
| `User` | `users` | HasMany: TelegramSubscription, Subscription, ContratoSeguimiento, EmailSubscription. BelongsToMany: Role |
| `Contrato` | `contratos` | HasMany: ContratoArchivo. HasManyThrough: TdrAnalisis |
| `ContratoArchivo` | `contrato_archivos` | BelongsTo: Contrato. HasMany: TdrAnalisis |
| `TdrAnalisis` | `tdr_analisis` | BelongsTo: ContratoArchivo |
| `CuentaSeace` | `cuentas_seace` | Standalone. Encripta password con `Crypt::encryptString` |
| `TelegramSubscription` | `telegram_subscriptions` | BelongsTo: User. BelongsToMany: NotificationKeyword |
| `WhatsAppSubscription` | `whatsapp_subscriptions` | BelongsTo: User. BelongsToMany: NotificationKeyword |
| `EmailSubscription` | `email_subscriptions` | BelongsTo: User. BelongsToMany: NotificationKeyword. HasMany: EmailContractSend |
| `NotificationKeyword` | `notification_keywords` | BelongsToMany: TelegramSubscription |
| `SubscriptionContractMatch` | `subscription_contract_matches` | BelongsTo: TelegramSubscription o WhatsAppSubscription |
| `ContratoSeguimiento` | `contrato_seguimientos` | BelongsTo: User |
| `Subscription` | `subscriptions` | BelongsTo: User |
| `Role` | `roles` | BelongsToMany: Permission, User |
| `Permission` | `permissions` | BelongsToMany: Role |
| `NotifiedProcess` | `notified_processes` | HasMany: NotificationSend |
| `NotificationSend` | `notification_sends` | BelongsTo: NotifiedProcess, User |
| `EmailContractSend` | `email_contract_sends` | BelongsTo: EmailSubscription |
| `PremiumAuditLog` | `premium_audit_logs` | BelongsTo: User, Subscription |

---

## 28. MATRIZ DE PERMISOS POR RUTA

| Ruta | Método | Auth | Verified | Permiso/Gate | Roles con Acceso |
|---|---|---|---|---|---|
| `/` | GET | No | No | — | Todos |
| `/buscador-publico` | GET | No | No | — | Todos |
| `/planes` | GET | No | No | — | Todos |
| `/contacto` | GET | No | No | — | Todos |
| `/manual` | GET | No | No | — | Todos |
| `/login` | GET/POST | Guest | No | — | No autenticados |
| `/register` | GET/POST | Guest | No | — | No autenticados |
| `/forgot-password` | GET/POST | Guest | No | — | No autenticados |
| `/reset-password/{token}` | GET/POST | Guest | No | — | No autenticados |
| `/email/verify` | GET | Sí | No | — | Todos autenticados |
| `/email/verify/{id}/{hash}` | GET | Sí | No | `signed` | Link firmado |
| `/email/verification-notification` | POST | Sí | No | `throttle:6,1` | Todos autenticados |
| `/logout` | POST | Sí | No | — | Todos autenticados |
| `/dashboard` | GET | Sí | Sí | — | Todos verificados |
| `/perfil` | GET | Sí | Sí | — | Todos verificados |
| `/seguimientos` | GET | Sí | Sí | `follow-contracts` | admin, proveedor-premium |
| `/mis-procesos` | GET | Sí | Sí | `view-mis-procesos` | admin, proveedor, proveedor-premium |
| `/suscriptores` | GET | Sí | Sí | `view-suscriptores` | admin, proveedor, proveedor-premium |
| `/cuentas` | RESOURCE | Sí | Sí | `view-cuentas` | admin |
| `/prueba-endpoints` | GET | Sí | Sí | `view-prueba-endpoints` | admin |
| `/configuracion` | GET | Sí | Sí | `view-configuracion` | admin |
| `/tdr-repository` | GET | Sí | Sí | `view-tdr-repository` | admin |
| `/roles-permisos` | GET | Sí | Sí | `manage-roles-permissions` | admin |
| `/suscripciones-premium` | GET | Sí | Sí | `manage-subscriptions` | admin |
| `/planes/checkout/{plan}` | GET | Sí | Sí | — | Todos verificados |
| `/planes/charge` | POST | Sí | Sí | — | Todos verificados |
| `/planes/callback` | GET | Sí | Sí | — | Todos verificados |
| `/tdr/archivos/{archivo}/descargar` | GET | No | No | — | Todos |
| `/seace/download/{filename}` | GET | No | No | — | Todos |
| `/api/auth/token` | POST | No | No | — | Todos |
| `/api/auth/token` | DELETE | Sanctum | No | — | Token válido |
| `/api/user` | GET | Sanctum | No | — | Token válido |
| `/api/webhooks/openpay` | POST | No | No | — | Webhook firmado |
| `/api/webhooks/mercadopago` | POST | No | No | — | Webhook firmado |
| `/api/webhooks/whatsapp` | GET/POST | No | No | — | Meta verify_token |

---

## 29. CHECKLIST DE REGRESIÓN

### 29.1 Funcionalidades Críticas (P0 — Bloqueo total)

- [ ] Login y logout funcional
- [ ] Registro de usuario (personal y empresa)
- [ ] Verificación de email
- [ ] Buscador público carga y busca contratos
- [ ] Dashboard muestra contratos almacenados
- [ ] Notificaciones Telegram se envían a suscriptores
- [ ] Jobs programados se ejecutan en horario correcto
- [ ] Pasarelas de pago procesan cobros

### 29.2 Funcionalidades Importantes (P1 — Degradación)

- [ ] Análisis TDR con IA retorna resultado estructurado
- [ ] Descarga de archivos TDR funcional
- [ ] Seguimiento de contratos se persiste
- [ ] Calendario de seguimientos muestra eventos
- [ ] Suscripciones premium se activan/expiran correctamente
- [ ] Roles y permisos bloquean acceso correcto
- [ ] Mis Procesos muestra historial de notificaciones
- [ ] Re-notificación por canal funciona
- [ ] Renovación automática cobra y extiende

### 29.3 Funcionalidades Secundarias (P2 — Cosmético)

- [ ] Formulario de contacto envía email
- [ ] Perfil de usuario se actualiza
- [ ] Páginas de error personalizadas se muestran
- [ ] Filtros avanzados del buscador funcionan
- [ ] Paginación en todas las tablas
- [ ] Query string refleja estado de filtros en URL
- [ ] Cookie consent se muestra
- [ ] Admin bot responde a comandos

### 29.4 Pruebas de Seguridad

- [ ] CSRF token presente en formularios Blade
- [ ] Rutas protegidas redirigen a login sin sesión
- [ ] Gates/permisos bloquean acceso no autorizado
- [ ] Credenciales SEACE se almacenan encriptadas
- [ ] Campos sensibles no se exponen en JSON (`hidden`)
- [ ] Webhooks validan firma criptográfica
- [ ] Rate limiting funcional en API (60/min)
- [ ] Sanctum tokens revocables
- [ ] Dominios de email validados para cuentas personales
- [ ] RUC valida formato regex `^(10|20)\d{9}$`

### 29.5 Pruebas de Integración Externa

- [ ] API SEACE: login con RUC/password → obtiene tokens
- [ ] API SEACE: refresh token → renueva access_token
- [ ] API SEACE: búsqueda de contratos → retorna resultados
- [ ] API SEACE: listado de archivos por contrato
- [ ] API SEACE: descarga de PDF
- [ ] Telegram Bot API: envío de mensajes y documentos
- [ ] WhatsApp Cloud API: envío de mensajes y media
- [ ] Openpay API: creación de clientes, cobros, verificación webhooks
- [ ] MercadoPago API: checkout, callback, verificación webhooks
- [ ] Microservicio Python: health check, análisis de TDR, score de compatibilidad
- [ ] SMTP: envío de emails transaccionales

### 29.6 Pruebas de Rendimiento

- [ ] Buscador público responde en < 5s con filtros
- [ ] Dashboard con 1000+ contratos carga KPIs en < 3s
- [ ] Paginación no hace full table scan
- [ ] Análisis TDR con lock previene llamadas concurrentes
- [ ] Jobs no exceden timeout (300s/600s según job)
- [ ] Cache de catálogos SEACE (1h TTL) reduce llamadas
- [ ] Jitter anti-detección (0.5-2s) en scraping autenticado

---

> **Nota:** Este documento fue generado automáticamente a partir del análisis estático del código fuente del proyecto. Los casos de prueba están basados en la lógica implementada, validaciones definidas y flujos de datos observados. Se recomienda complementar con pruebas exploratorias y de aceptación del usuario.

---

**Fecha:** 7 de marzo de 2026  
**Total de casos de prueba:** 168+  
**Total de módulos documentados:** 26
