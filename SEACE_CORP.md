# Vigilante SEACE — Funcionalidades y Ventajas Corporativas

> **Plataforma de Monitoreo Inteligente de Contrataciones Públicas (Perú)**  
> **Stack:** Laravel 12 · Livewire · Alpine.js · MySQL · Python FastAPI (IA)  
> **Producción:** [licitacionesmype.pe](https://licitacionesmype.pe)  
> **Última actualización:** 13 de febrero de 2026

---

## Funcionalidades del Sistema

### 🔍 Búsqueda y Exploración de Contratos

- Buscador público de contratos menores sin necesidad de cuenta ni login
- Buscador autenticado con acceso completo a archivos y cotizaciones
- Filtros múltiples: palabra clave, objeto de contratación, estado, entidad
- Filtros geográficos en cascada: departamento → provincia → distrito
- Autocompletado inteligente de entidades públicas (debounce 500 ms)
- Paginación configurable (10, 20, 50, 100 resultados)
- Ordenamiento por fecha de publicación ascendente / descendente
- URLs compartibles con filtros persistentes (SEO-friendly para Google)
- Búsqueda automática al cargar la página
- Contador visual de filtros activos
- Filtros colapsables (avanzados ocultos por defecto)

### 📄 Gestión de Documentos TDR

- Descarga directa de archivos TDR (PDF, ZIP, RAR) sin abrir nueva pestaña
- Repositorio local de TDR con almacenamiento persistente en storage
- Cache de documentos descargados para evitar re-descargas innecesarias
- Validación de integridad de archivos por firma binaria (magic bytes)
- Filtro estricto de tipos permitidos (solo PDF / ZIP / RAR)
- Reporte de archivo incorrecto por usuarios ("Este no es el TDR")
- Re-descarga forzada por administradores (purge de cache + nueva descarga desde SEACE)
- Detección automática de MIME type y normalización de nombres de archivo

### 🤖 Análisis de TDR con Inteligencia Artificial

- Análisis automático de documentos TDR con **Gemini 2.5 Flash**
- Resumen ejecutivo del proceso en lenguaje natural
- Extracción de requisitos técnicos de calificación
- Extracción de reglas de negocio y ejecución
- Extracción de políticas y penalidades
- Detección de monto referencial
- Identificación de fechas clave (publicación, cierre, etapa)
- Cache de análisis para evitar re-procesamiento
- Indicador de cache vs análisis fresco
- Contexto enriquecido con datos de la API (fechas, estado, entidad)

### 📊 Score de Compatibilidad por Suscriptor

- Puntaje personalizado de compatibilidad TDR ↔ perfil del proveedor
- Evaluación por cada suscriptor registrado
- Comparativa visual de puntajes entre suscriptores
- Recomendación de "conviene postular" basada en score
- Cálculo bajo demanda (botón "Obtener score")

### 📅 Seguimiento de Procesos y Calendario

- Seguimiento de contratos con un clic (relación usuario-proceso)
- Calendario mensual con vista de procesos en seguimiento
- Navegación entre meses (anterior / siguiente)
- Semáforo visual de urgencia por colores:
  - 🔴 **Crítico** — ≤ 2 días para vencer
  - 🟠 **Alto** — 3–5 días
  - 🟡 **Medio** — 6–7 días
  - 🟢 **Estable** — > 7 días
- Panel lateral derecho con lista scrolleable de seguimientos
- Detalle de proceso al hacer clic en ítem del calendario
- Fechas de inicio y fin de cotización visibles por proceso

### 🔔 Notificaciones Telegram

- Bot de Telegram integrado para alertas en tiempo real
- Notificación automática de nuevos contratos detectados
- Suscriptores con perfiles personalizados (rubros, ubicación, montos)
- Filtrado de contratos relevantes por suscriptor
- Match automático contrato ↔ suscriptor
- Configuración de bot desde panel de administración
- Prueba de conexión en vivo desde la UI

### 🔐 Autenticación y Roles

- Sistema de roles y permisos granular (Spatie)
- Rol **Administrador** con acceso completo
- Rol **Proveedor** estándar
- Rol **Proveedor Premium** con funcionalidades exclusivas
- Gestión de roles y permisos desde UI
- Paginación de usuarios en panel de roles
- Login embebido en modal (sin redirigir fuera del buscador)
- Botones visibles para todos, funcionalidad restringida por rol
- Permisos configurables: `analyze-tdr`, `follow-contracts`
- Verificación de correo electrónico obligatoria
- Recuperación de contraseña

### 🔄 Sincronización Automática con SEACE

- Scraping automatizado vía Laravel HTTP Client (modo ninja)
- Autenticación resiliente de 3 niveles: token → refresh → login completo
- Retry automático en expiración de token (`401 TOKEN_EXPIRED`)
- Headers que simulan navegador real (anti-detección)
- Intervalo aleatorio 42–50 minutos (patrón no robótico)
- Upsert inteligente (`updateOrCreate`) sin duplicados
- Detección de contratos nuevos vs actualizados
- Scheduler de Laravel configurable
- Logs detallados de cada operación

### 👥 Gestión de Suscriptores

- CRUD completo de suscriptores de Telegram
- Perfiles con rubros de interés, ubicación geográfica, rangos de monto
- Match automático de contratos con perfil del suscriptor
- Historial de contratos notificados por suscriptor

### 🏢 Gestión de Cuentas SEACE

- CRUD de cuentas de acceso al portal SEACE
- Almacenamiento encriptado de contraseñas
- Gestión de tokens (access + refresh) en base de datos
- Activación / desactivación de cuentas
- Indicador de última autenticación exitosa
- Una sola cuenta activa a la vez

### 📈 Dashboard y Visualización

- Dashboard principal con métricas clave
- Modal de detalle rápido por contrato ("Ver")
- Información de entidad, objeto, descripción, estado
- Tarjetas de fechas con formato legible ("14 de febrero a las 10:00 a.m.")
- Tiempo relativo inteligente ("hace 2 horas" / "en 5 horas")
- Badges de estado con colores diferenciados
- Indicador de cotización abierta / cerrada
- Loading states con spinners en cada acción

### ⚙️ Panel de Configuración

- Configuración de Telegram desde UI (token, chat ID)
- Configuración del analizador TDR (URL, timeout)
- Toggles para habilitar / deshabilitar servicios
- Pruebas de conexión en vivo
- Documentación integrada en la vista
- Actualización de variables `.env` desde interfaz

### 🧪 Herramientas de Desarrollo

- Vista de prueba de endpoints SEACE completa
- Test de login, refresh, búsqueda, archivos, descarga
- Comando `seace:test` para diagnóstico del sistema
- Comando `seace:sync` para sincronización manual
- Logs en tiempo real (`laravel.log`)

### 🎨 Diseño y UX

- Diseño **"Sequence Dashboard"** con paleta personalizada (teal + mint)
- Sidebar fijo con navegación contextual por rol
- Fully responsive (desktop, tablet, mobile)
- Cards con `rounded-3xl` y sombras suaves
- Botones cápsula (`rounded-full`)
- Tipografía jerárquica (Inter / Helvetica)
- Dark header en modales con gradient
- Tooltips informativos en acciones

### 🏗️ Infraestructura y Arquitectura

- Monolito LAMP (Laravel + Apache + MySQL + PHP)
- Stack: Blade + Livewire + Alpine.js (sin React / Vue)
- CI/CD con GitHub Actions (tests + deploy)
- Microservicio Python FastAPI para análisis IA (independiente)
- Cache inteligente de catálogos maestros (departamentos, objetos, estados)
- Migraciones versionadas con rollback
- Seeders para datos iniciales

---

## Ventajas Corporativas

### ⏱️ Ahorro de Tiempo

- Elimina la necesidad de entrar manualmente al portal SEACE
- Búsqueda centralizada en una sola interfaz (vs navegar múltiples páginas del SEACE)
- Filtros persistentes en URL: comparte búsquedas exactas con tu equipo
- Descarga de TDR en un clic (sin redirecciones ni ventanas emergentes)
- Resúmenes completos de TDR en segundos (vs leer documentos de 20+ páginas)

### 🎯 Direccionamiento Inteligente

- Score de compatibilidad que indica qué procesos conviene postular
- Semáforo de urgencia para priorizar procesos por tiempo restante
- Seguimiento personalizado de procesos de interés
- Calendario visual con fechas críticas de cada proceso
- Notificaciones automáticas de procesos relevantes a tu perfil

### 💰 Ventaja Competitiva

- Detección temprana de oportunidades (alertas Telegram en tiempo real)
- Análisis IA de requisitos técnicos antes que la competencia
- Evaluación rápida de viabilidad con score de compatibilidad
- Historial de procesos seguidos para toma de decisiones
- Acceso público sin cuenta para evaluación inicial rápida

### 👥 Gestión de Equipo

- Roles diferenciados (Admin, Proveedor, Proveedor Premium)
- Múltiples suscriptores con perfiles independientes
- Permisos granulares configurables desde UI
- URLs compartibles para coordinación de equipo
- Reportes de archivos incorrectos para mejora continua

### 🔒 Seguridad y Confiabilidad

- Credenciales SEACE encriptadas en base de datos
- Autenticación resiliente sin caídas por expiración de tokens
- Verificación de correo electrónico obligatoria
- Validación de integridad de documentos descargados
- Logs completos para auditoría

### 📊 Escalabilidad

- Soporte para múltiples cuentas SEACE
- Paginación de usuarios preparada para crecimiento
- Cache de documentos y análisis para rendimiento
- Arquitectura modular (servicios independientes)
- CI/CD automatizado para despliegues sin downtime
