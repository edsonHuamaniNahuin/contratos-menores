# 🗺️ FUNCIONALIDADES DEL SISTEMA — VIGILANTE SEACE

> **Proyecto:** Vigilante SEACE — Sistema de Monitoreo Automatizado de Contratos SEACE (Perú)  
> **Versión del documento:** 1.0  
> **Fecha:** 28 de marzo de 2026  
> **Stack:** Laravel 12 + Blade + Livewire 4.1 + Alpine.js + MySQL  
> **Propósito:** Descripción funcional completa del sistema para usuarios, stakeholders y QA.

---

## TABLA DE CONTENIDOS

1. [¿Qué es Vigilante SEACE?](#1-qué-es-vigilante-seace)
2. [Acceso al Sistema — Roles y Planes](#2-acceso-al-sistema--roles-y-planes)
3. [Funcionalidades Públicas (sin cuenta)](#3-funcionalidades-públicas-sin-cuenta)
4. [Funcionalidades para Usuarios Registrados](#4-funcionalidades-para-usuarios-registrados)
5. [Funcionalidades Exclusivas Premium](#5-funcionalidades-exclusivas-premium)
6. [Funcionalidades de Administración](#6-funcionalidades-de-administración)
7. [Notificaciones Automáticas Multi-Canal](#7-notificaciones-automáticas-multi-canal)
8. [Integraciones Externas](#8-integraciones-externas)
9. [Resumen por Módulo](#9-resumen-por-módulo)

---

## 1. ¿QUÉ ES VIGILANTE SEACE?

**Vigilante SEACE** es una plataforma web peruana que automatiza el monitoreo de contratos menores del Estado publicados en el portal gubernamental SEACE (*Sistema Electrónico de Contrataciones del Estado*). Su propósito es ayudar a **proveedores, consultores y empresas** a encontrar oportunidades de contratación pública, analizarlas con inteligencia artificial y participar de forma más competitiva.

### ¿Qué problema resuelve?

| Problema | Solución en Vigilante SEACE |
|----------|----------------------------|
| Revisar manualmente miles de contratos en SEACE a diario | Monitoreo automatizado con alertas en tiempo real |
| Leer y entender documentos TDR de decenas de páginas | IA analiza el TDR y extrae los requisitos clave en segundos |
| Detectar si un contrato está "direccionado" (sesgado) | Análisis de direccionamiento con score y hallazgos concretos |
| Preparar una proforma técnica para cotizar es tedioso | IA genera la proforma completa descargable en Word/Excel |
| Perderse de contratos de su rubro por falta de alertas | Suscripción por palabras clave + notificación Telegram/WhatsApp/Email |
| No saber si la empresa aplica para un contrato | Score de compatibilidad empresa-contrato calculado por IA |

---

## 2. ACCESO AL SISTEMA — ROLES Y PLANES

### Tipos de cuenta

| Tipo | Registro | Acceso base |
|------|----------|-------------|
| **Proveedor** (persona natural) | Gratuito | Buscador, alertas básicas, mis procesos |
| **Proveedor Empresa** | Gratuito, requiere RUC | Igual + datos de empresa |
| **Proveedor Premium** | Pago (S/ 49/mes o S/ 470/año) | Todo + análisis IA, seguimiento, proforma, direccionamiento |
| **Administrador** | Solo por asignación interna | Gestión total del sistema |

### Planes de suscripción

| Plan | Precio | Duración | Características |
|------|--------|----------|----------------|
| **Trial gratuito** | S/ 0 | 15 días | Todas las funciones Premium (una sola vez) |
| **Mensual** | S/ 49.00 | 30 días | Funciones Premium activas |
| **Anual** | S/ 470.00 | 365 días | Funciones Premium + ahorro del 20% |

### Email de registro

- Cuentas **personales**: solo se aceptan dominios conocidos (gmail, outlook, hotmail, yahoo, live, icloud, protonmail, dominios `.com.pe`, `.edu.pe`, `.gob.pe`, `.org.pe`)
- Cuentas **empresa**: cualquier dominio corporativo

---

## 3. FUNCIONALIDADES PÚBLICAS (SIN CUENTA)

> Accesibles sin registrarse, disponibles para cualquier visitante.

### 3.1 Buscador Público de Contratos SEACE

El **buscador público** permite explorar los contratos menores del Estado en tiempo real consultando directamente la API del SEACE.

**¿Qué puedes hacer?**
- Buscar contratos por **palabra clave** (ej: "consultoría de obras", "seguridad informática")
- Filtrar por **objeto de contratación**: Bienes, Servicios, Consultoría de Obras, Obras
- Filtrar por **estado**: Vigente, En Evaluación, Desierto, etc.
- Filtrar por **ubicación geográfica**: departamento → provincia → distrito (cascada)
- Filtrar por **entidad contratante** con autocompletado
- **Buscar por URL**: los filtros de departamento y objeto se reflejan en la URL para compartir búsquedas (SEO-friendly)
- Ver el **detalle completo** de cada contrato en un panel
- **Descargar el TDR** (Términos de Referencia) en PDF
- **Ordenar y paginar** los resultados

> 💡 El buscador está optimizado para PageSpeed mobile 90+, con accesibilidad mejorada.

### 3.2 Landing Page e Información

- **Landing page** con descripción del sistema, planes, testimonios y preguntas frecuentes
- **Página de Planes**: detalle de funcionalidades por plan
- **Página de Contacto**: formulario para consultas (llega al equipo por email)
- **Manual de usuario**: guía de uso de la plataforma con imágenes

### 3.3 Páginas Públicas de Análisis Compartido

Cuando un usuario Premium realiza un análisis IA, puede **compartir el resultado** mediante una URL pública única:

- `https://vigilanteseace.com/analisis/{token}` — accesible sin cuenta
- Muestra: veredicto, score, requisitos de calificación, reglas de ejecución, penalidades o hallazgos de direccionamiento

---

## 4. FUNCIONALIDADES PARA USUARIOS REGISTRADOS

> Requieren crear una cuenta gratuita y verificar el email.

### 4.1 Dashboard de Contratos Importados

Panel analítico con todos los contratos descargados localmente a la base de datos del sistema:

- **Tabla paginada** con búsqueda, filtros por estado/objeto/departamento y ordenamiento por cualquier columna
- **KPIs en tiempo real**: total contratos, vigentes, en evaluación, por vencer
- **Gráficos analíticos**:
  - Distribución por estado
  - Distribución por objeto de contratación
  - Publicaciones por mes (serie temporal)
  - Top 10 entidades contratantes
  - Distribución por departamento
  - Mini-gráfico de análisis de direccionamiento
- **Importación manual**: importar contratos desde la API SEACE por departamento y fecha (solo admin)

### 4.2 Mis Procesos Notificados

Historial personal de todos los contratos SEACE que el sistema te ha notificado:

- Lista paginada con código, entidad, descripción, canal de notificación y fecha
- **KPIs**: total procesos, por Telegram, por WhatsApp, por Email
- Filtros por texto, canal y rango de fechas
- **Re-notificar**: enviar nuevamente un proceso por Telegram, WhatsApp o Email con un click

### 4.3 Perfil de Usuario

- Actualizar nombre, email, teléfono, RUC y razón social
- Cambiar contraseña con verificación de la contraseña actual
- Ver plan de suscripción activo y días restantes

### 4.4 Mi Suscripción

- Ver estado actual del plan (trial, mensual, anual)
- Ver historial de auditoría de activaciones y cancelaciones
- Activar/desactivar renovación automática
- Cancelar suscripción
- Ver opciones de upgrade a plan superior

---

## 5. FUNCIONALIDADES EXCLUSIVAS PREMIUM

> Disponibles para usuarios con suscripción activa (trial, mensual o anual) y administradores.

### 5.1 Análisis de TDR con Inteligencia Artificial

La IA (Gemini 2.5 Flash, contexto de 1M tokens) analiza el documento TDR de un contrato y extrae:

- **Requisitos de calificación**: experiencia mínima, certificaciones, capacidad técnica
- **Reglas de ejecución**: plazos, entregables, condiciones especiales
- **Penalidades**: multas, condiciones de resolución de contrato
- **Monto referencial**: valor estimado del contrato

**¿Dónde se puede analizar?**
- Desde el **buscador público** (botón "Analizar con IA" en cada contrato)
- Desde el **repositorio TDR** (re-análisis de archivos)
- Desde los **bots de Telegram y WhatsApp**
- Resultado **cacheado** para no gastar tokens si ya fue analizado

### 5.2 Análisis de Direccionamiento

Detecta si un contrato tiene indicios de **estár direccionado** (favorece a un proveedor específico, impide la libre competencia):

- **Veredicto**: riesgo alto / medio / bajo / sin evidencia
- **Score de direccionamiento** (0–100, donde 100 = altamente direccionado)
- **Hallazgos categorizados**: requisitos restrictivos, especificaciones marcarias, plazos inviables, etc.
- **Gravedad por hallazgo**: crítica / alta / media / baja

**¿Dónde se puede usar?**
- Desde el **buscador público** (botón dedicado)
- Desde los **bots de Telegram y WhatsApp** (acción en el menú interactivo)
- **Dashboard de Direccionamiento** (`/direccionamiento`) con gráficos agregados

### 5.3 Dashboard de Direccionamiento

Página dedicada con estadísticas históricas de todos los análisis de direccionamiento realizados:

- Gráfico de distribución de **veredictos** (alto/medio/bajo riesgo)
- Gráfico de **rangos de score** (0-20, 21-40, 41-60, 61-80, 81-100)
- Gráfico de **hallazgos por categoría**
- Gráfico de **gravedad** de hallazgos
- **Tendencia mensual** de score promedio (últimos 6 meses)
- **Top 10 entidades** contratantes con mayor frecuencia de hallazgos

Filtros: por departamento, entidad contratante y rango de fechas.

### 5.4 Generación de Proforma Técnica IA

La IA genera automáticamente una **cotización técnica profesional** basada en el análisis del TDR:

- **Ítems con precios unitarios** estimados por la IA
- **Subtotales y total en S/**
- **Análisis de viabilidad** del contrato
- **Condiciones de pago** sugeridas
- **Datos del contrato**: entidad, código, objeto

**Formatos de descarga:**
- 📄 **Word** (`.doc`) — listo para editar
- 📊 **Excel** (`.xls`, SpreadsheetML) — con fórmulas de subtotales
- 🖨️ **Vista de impresión** — HTML para imprimir / PDF del navegador

**Integración con bots**: los bots de Telegram y WhatsApp pueden generar la proforma directamente y enviar los links de descarga al chat.

### 5.5 Score de Compatibilidad Empresa-Contrato

La IA compara el perfil de la empresa del suscriptor con los requisitos del TDR analizado:

- **Score 0–100** de compatibilidad
- Detalle de qué requisitos cumple y cuáles no
- Resultado cacheado mientras el perfil no cambie
- Visible desde el buscador público (botón "Calcular compatibilidad")

### 5.6 Seguimiento de Contratos (Calendario)

Permite hacer seguimiento visual de contratos de interés:

- **Calendario mensual** con los contratos como eventos por día
- Los contratos con rango de fechas aparecen en **todos los días del período**
- **Color por urgencia**: crítico (≤2 días, rojo), alto (≤5, naranja), medio (≤7, amarillo), estable (verde)
- Click en un evento → modal con detalle completo del contrato
- **Zona horaria Lima** en todas las fechas

### 5.7 Cotización en SEACE

Acceso directo al portal SEACE para cotizar en un contrato:

- Genera el **enlace de cotización** al sistema de SEACE del contrato elegido
- **Guía interactiva** para orientar al proveedor en el proceso de cotización
- Disponible desde buscador público y bots

### 5.8 Compartir Análisis (Share Tokens)

Después de analizar un TDR (análisis estándar o de direccionamiento):

- Se genera una **URL pública única** con UUID (`/analisis/{token}`)
- Cualquier persona puede ver el resultado sin necesidad de cuenta
- Los bots de Telegram y WhatsApp **envían el link automáticamente** tras el análisis
- Las URLs se incluyen en el **sitemap.xml** para indexación SEO

---

## 6. FUNCIONALIDADES DE ADMINISTRACIÓN

> Exclusivas para el rol Administrador.

### 6.1 Gestión de Cuentas SEACE

Administra las credenciales de acceso a la API del SEACE (RUC + contraseña):

- CRUD completo de cuentas (crear, ver, editar, eliminar)
- Las contraseñas se almacenan **encriptadas** (Laravel Crypt)
- **Activar/desactivar** cuentas
- Estado visual de cada cuenta: Conectada, Token Expirado, Sin Conectar, Inactiva

### 6.2 Prueba de Endpoints SEACE

Herramienta interna para probar la integración con la API del SEACE:

- **Login** con credenciales SEACE → obtiene access_token y refresh_token
- **Refresh token**: renovar token expirado
- **Búsqueda de contratos** con filtros avanzados (departamento, objeto, keyword, fechas)
- **Tablas maestras**: consultar catálogos del SEACE
- **Listar y descargar archivos** de un contrato específico
- **Importación masiva** con notificación a suscriptores
- **Enviar al bot** de Telegram directamente

### 6.3 Repositorio TDR

Gestor de todos los archivos TDR descargados:

- Tabla paginada con nombre de archivo, código de contrato, entidad y estado de análisis
- Filtros: por texto, estado de análisis (pendiente/exitoso/fallido), "solo con análisis"
- Descargar archivo localmente o desde SEACE como fallback
- **Re-analizar** cualquier archivo con la IA
- KPIs: total archivos, total analizados exitosamente

### 6.4 Gestión de Suscripciones Premium

Panel de administración de todas las suscripciones de la plataforma:

- Tabla paginada con filtros: búsqueda, estado, plan
- KPIs: activas, trial, pagadas, expiradas
- **Otorgar Premium manual**: asignar plan a cualquier usuario con duración en días
- **Extender suscripción**: agregar días a una suscripción existente
- **Cancelar suscripción**: revoca inmediatamente acceso premium
- **Activar trial**: para usuarios que no lo han usado
- Historial de auditoría completo (grant/revoke con fuente y fecha)

### 6.5 Roles y Permisos (RBAC)

Matriz gráfica de control de acceso:

- Tabla con **roles como columnas** y **permisos como filas**
- Modificar permisos de cada rol con checkboxes
- Asignar rol a cualquier usuario
- Protección del último administrador (no se puede degradar)
- 15 permisos en 3 categorías: Vistas del sistema, TDR y procesos, Gestión

### 6.6 Configuración del Sistema

Panel centralizado para configurar todas las integraciones:

- **Telegram Bot**: token, chat_id, activar/desactivar, prueba de conexión
- **Telegram Admin Bot**: token y chat_id dedicados para el bot admin
- **WhatsApp Bot**: token de Meta Cloud API, group_id
- **Analizador TDR (IA)**: URL del microservicio Python, activar/desactivar
- **MercadoPago**: access_token, public_key, webhook_secret
- **Openpay**: merchant_id, private_key, public_key, modo producción
- Selección del **gateway de pago por defecto**
- Cambios se escriben en `.env` con cache de config limpiada

### 6.7 Bot Admin Telegram

Bot exclusivo de administración accesible vía Telegram:

- `/start` — Bienvenida con botones inline
- `/escanear` — Estado de servicios systemd del servidor
- `/usuarios` — Estadísticas: total, verificados, premium, trial, nuevos hoy
- `/ingresos` — Resumen financiero: ingresos del mes, suscripciones activas
- `/help` — Lista de comandos

---

## 7. NOTIFICACIONES AUTOMÁTICAS MULTI-CANAL

### 7.1 ¿Cómo funcionan las alertas?

Los usuarios configuran su perfil de alertas en **Configuración de Alertas** (`/configuracion-alertas`):

1. Ingresan **hasta 5 palabras clave** de su rubro (ej: "seguridad", "consultoría vial", "limpieza")
2. Configuran su **suscripción Telegram** (chat_id) y/o **WhatsApp** (número) y/o **Email**
3. Opcionalmente definen un **perfil de empresa** (descripción, rubro) para el score de compatibilidad
4. El sistema **importa contratos** de SEACE cada 2 horas (lunes a domingo, 06:00–20:00 Lima)
5. Los contratos que coinciden con las keywords del suscriptor se notifican **automáticamente**

### 7.2 Canal Telegram

Cada notificación incluye:
- Código del contrato, entidad, descripción y monto
- Botones inline: 🤖 Analizar con IA | 📄 Descargar TDR | 📊 Compatibilidad | 🔍 Direccionamiento | 📋 Crear Proforma | 💰 Cotizar | 🔗 Compartir

Límite: hasta **2 suscriptores Telegram** por usuario (distintos chat_ids).

### 7.3 Canal WhatsApp

Cada notificación incluye:
- Datos del contrato en formato mensaje
- **Interactive List Message** con menú de 5 acciones:
  - 🤖 Analizar TDR
  - 📥 Descargar TDR
  - 🏅 Compatibilidad
  - 🔍 Direccionamiento
  - 📋 Crear Proforma

### 7.4 Canal Email

Notificación por email con datos del contrato, keywords coincidentes y link para hacer seguimiento. Opción "Notificar todo" para recibir todos los contratos sin filtro.

### 7.5 Deduplicación anti-spam

- El sistema registra cada notificación enviada en `notification_sends`
- **No se re-envía** un contrato que ya fue notificado al mismo suscriptor
- Cache de procesamiento aislado por **ambiente** (QA vs Producción) para evitar interferencias

### 7.6 Jobs Programados (Scheduler)

| Job | Frecuencia | Qué hace |
|-----|-----------|----------|
| Importar + Notificar TDR | Cada 2h (L-D 06:00–20:00) | Descarga contratos nuevos y notifica a suscriptores Telegram + WhatsApp |
| Notificar Email | Cada 2h (L-D 06:00–20:00) | Envía emails de contratos nuevos a suscriptores email |
| Importar contratos diario | Diario a las 02:00 | Importa todos los contratos del día anterior para el dashboard |
| Expirar suscripciones | Cada hora | Expira trials y suscripciones vencidas, revoca acceso premium |
| Renovar suscripciones | Cada 6 horas | Cobra y renueva suscripciones próximas a vencer (auto-renovación) |

---

## 8. INTEGRACIONES EXTERNAS

| Servicio | Propósito | Acceso |
|----------|-----------|--------|
| **API SEACE** (OSCE) | Fuente de datos de contratos — login con RUC/password, JWT tokens | `https://prod.seace.gob.pe` |
| **Telegram Bot API** | Notificaciones y bot interactivo para proveedores | Meta Bot API |
| **WhatsApp Cloud API** (Meta) | Notificaciones y bot interactivo para proveedores | Meta Cloud API |
| **Microservicio Python (FastAPI)** | Motor de análisis IA de TDR | `http://127.0.0.1:8001` (local) |
| **Gemini 2.5 Flash** (Google) | LLM para análisis de TDR, direccionamiento, proforma y compatibilidad | API Key |
| **Openpay Perú** | Pasarela de pago (cobro con tarjeta) | SDK in-page |
| **MercadoPago** | Pasarela de pago alternativa | Redirect checkout |
| **SMTP** | Emails transaccionales (verificación, bienvenida, reset, alertas) | Configurable |

---

## 9. RESUMEN POR MÓDULO

| # | Módulo | Ruta | Acceso | Descripción breve |
|---|--------|------|--------|-------------------|
| 1 | Landing Page | `/` | Público | Presentación del sistema, planes y CTA |
| 2 | Buscador Público | `/buscador-publico` | Público | Búsqueda y filtrado en tiempo real de contratos SEACE |
| 3 | Análisis Público Compartido | `/analisis/{token}` | Público | Ver resultado de análisis IA compartido sin cuenta |
| 4 | Proforma Descarga | `/proforma/{token}/...` | Público (token) | Descargar proforma técnica en Word/Excel/impresión |
| 5 | Planes y Precios | `/planes` | Público | Comparativa de planes y botones de checkout |
| 6 | Contacto | `/contacto` | Público | Formulario de consultas |
| 7 | Manual | `/manual` | Público | Guía de uso con imágenes |
| 8 | Login / Registro | `/login`, `/register` | Público (guest) | Autenticación y registro con verificación de email |
| 9 | Dashboard | `/dashboard` | Registrado | Contratos importados + KPIs + gráficos analíticos |
| 10 | Mis Procesos | `/mis-procesos` | Registrado | Historial de contratos notificados con re-notificación |
| 11 | Configuración de Alertas | `/configuracion-alertas` | Registrado | Gestionar suscripciones Telegram, WhatsApp y Email |
| 12 | Seguimientos (Calendario) | `/seguimientos` | Premium | Calendario de contratos en seguimiento personal |
| 13 | Mi Suscripción | Sección en perfil | Registrado | Estado de plan, historial de auditoría y cancelación |
| 14 | Perfil | `/perfil` | Registrado | Datos personales y cambio de contraseña |
| 15 | Checkout y Pago | `/planes/checkout/{plan}` | Registrado | Activar trial o pagar suscripción |
| 16 | Repositorio TDR | `/tdr-repository` | Admin | Gestión de archivos TDR descargados |
| 17 | Análisis de Direccionamiento | `/direccionamiento` | Admin | Dashboard de estadísticas de direccionamiento |
| 18 | Cuentas SEACE | `/cuentas` | Admin | CRUD de credenciales de acceso a la API SEACE |
| 19 | Prueba Endpoints | `/prueba-endpoints` | Admin | Herramienta de testing de la API SEACE |
| 20 | Roles y Permisos | `/roles-permisos` | Admin | Matriz RBAC con 15 permisos y 3 roles |
| 21 | Suscripciones Premium | `/suscripciones-premium` | Admin | Gestión de suscripciones de todos los usuarios |
| 22 | Configuración Sistema | `/configuracion` | Admin | Tokens Telegram/WhatsApp/Openpay/MercadoPago/IA |
| 23 | Bot Telegram (proveedor) | Artisan command | Sistema | Notificaciones + acciones IA por Telegram |
| 24 | Bot WhatsApp (proveedor) | Webhook + Artisan | Sistema | Notificaciones + Interactive List Messages |
| 25 | Bot Admin Telegram | Artisan command | Admin | Comandos de gestión del servidor vía Telegram |
| 26 | API REST (Sanctum) | `/api/...` | Token Bearer | Acceso programático con autenticación por tokens |
| 27 | Webhooks Pagos | `/api/webhooks/...` | Externo firmado | Procesamiento de pagos Openpay y MercadoPago |
| 28 | Webhook WhatsApp | `/api/webhooks/whatsapp` | Meta firmado | Recepción de mensajes WhatsApp de usuarios |
| 29 | Emails Transaccionales | (jobs/listeners) | Sistema | Verificación, bienvenida, reset, alertas de contratos |

---

> **Última actualización:** 28 de marzo de 2026  
> **Total de módulos:** 29  
> **Total de permisos:** 15  
> **Canales de notificación:** 3 (Telegram, WhatsApp, Email)  
> **Formatos de descarga de proforma:** 3 (Word, Excel, Impresión)  
> **LLM utilizado:** Gemini 2.5 Flash (Google) vía microservicio Python FastAPI
