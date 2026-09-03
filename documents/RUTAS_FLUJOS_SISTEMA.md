# Vigilante SEACE — Árbol de Rutas y Flujos para Demo

> **URL producción:** `https://licitacionesmype.pe`
> **URL desarrollo:** `http://localhost:8000`
> **Base:** Laravel 12 + Livewire 4 + Tailwind CSS
> **Autenticación:** Login tradicional (email/contraseña) + roles (admin, proveedor, proveedor-premium, proveedor-premium-total)

---

## 🌟 FLUJOS PRINCIPALES PARA VIDEOS (orden de importancia)

### 1. Buscador Público + Análisis de TDR con IA ⭐⭐⭐⭐⭐
**Ruta:** `/buscador-publico`
**Tiempo demo:** 60-90s
**Pasos:**
```
1. Login → /login
2. Dashboard → /home
3. Sidebar: "Contratos Menores" → /buscador-publico
4. Escribir palabra clave en filtro → autocompletado entidad
5. Seleccionar objeto (Bien/Servicio/Obra) + Estado
6. Ver resultados en tabla/cuadrícula (toggle vista)
7. Click "Ver detalle" → modal con datos completos
8. Click "Analizar con IA" → loading → modal con resumen ejecutivo, requisitos, penalidades
9. Click "Descargar TDR" → descarga PDF
10. Click "Seguimiento" → añade a calendario
11. Click "Cotizar" → redirige al portal SEACE
```
**Destacar:** Análisis IA en segundos, extracción de requisitos, compatibilidad con perfil de empresa

### 2. Contratos Mayores (>8 UIT) ⭐⭐⭐⭐⭐
**Ruta:** `/buscador-contratos-mayores`
**Tiempo demo:** 45-60s
**Pasos:**
```
1. Sidebar: "Contratos Mayores" → /buscador-contratos-mayores
2. Ver bandeja con 1500+ contratos (OCDS API)
3. Filtrar por palabra clave, entidad, objeto, estado
4. Click "..." → dropdown con 7 acciones:
   - Ver detalle (modal completo)
   - Descargar TDR
   - Seguimiento
   - Analizar con IA (formato Ley 32069)
   - Direccionamiento (score radial)
   - Crear Proforma (modal con items + costos)
   - Ver Partes (entidades involucradas)
5. Mostrar badge "Vigente" con pulso verde
```
**Destacar:** 7 acciones, análisis forense, proforma automática, detección de direccionamiento

### 3. Planes y Pago con Yape ⭐⭐⭐⭐
**Ruta:** `/planes`
**Tiempo demo:** 30-45s
**Pasos:**
```
1. Ir a /planes (página pública)
2. Ver 3 cards: Gratuito, Premium (S/49), Premium + Mayores (S/68)
3. Click "Comprar Mensual" → /pago-yape/monthly
4. Ver QR de Yape + escanear
5. Subir screenshot del pago
6. Confirmación: "Pago enviado, validación en 24h"
7. Admin: /admin/pagos-yape → ver comprobante → aprobar
```
**Destacar:** QR Yape, formulario de comprobante, validación manual

### 4. Alertas y Notificaciones en Tiempo Real ⭐⭐⭐⭐
**Ruta:** `/configuracion-alertas`
**Tiempo demo:** 20-30s
**Pasos:**
```
1. Ir a /configuracion-alertas
2. Configurar palabras clave (ej: "laptop", "consultoría")
3. Activar canales: Telegram, WhatsApp, Email
4. Activar notificaciones para Menores y Mayores
5. Mostrar cómo llega la alerta al Telegram/WhatsApp:
   - Formato: 🔔 NUEVO CONTRATO
   - Botones interactivos: Analizar, Descargar, Seguimiento
```
**Destacar:** Alertas en tiempo real, botones interactivos, multi-canal

### 5. Seguimientos y Calendario ⭐⭐⭐
**Ruta:** `/seguimientos`
**Tiempo demo:** 15-20s
**Pasos:**
```
1. Ir a /seguimientos
2. Ver calendario con procesos seguidos (Menores + Mayores)
3. Click en un proceso → modal con detalle y fechas
4. Mostrar badge "Siguiendo" activo
```
**Destacar:** Calendario unificado, colores por tipo, fechas de cotización

### 6. Perfil y Configuración ⭐⭐
**Ruta:** `/perfil`
**Tiempo demo:** 10-15s
**Pasos:**
```
1. Ir a /perfil
2. Editar nombre, RUC, razón social
3. Ver tipo de cuenta (personal/empresa)
4. Ver estado de suscripción premium
```
**Destacar:** Gestión de datos empresariales, verificación RUC

---

## 📋 ÁRBOL COMPLETO DE RUTAS

### 🏠 Páginas Públicas (sin login)
| Ruta | Nombre | Descripción |
|---|---|---|
| `/` | landing | Landing page institucional |
| `/planes` | planes | 3 planes de precios (Gratuito/Premium/Premium+Mayores) |
| `/buscador-publico` | buscador.publico | Buscador de Contratos Menores (<8 UIT) |
| `/buscador-contratos-mayores` | buscador.mayores | Buscador de Contratos Mayores (>8 UIT) |
| `/blog` | — | Blog estático (16 posts, 4 categorías) |
| `/manual` | manual | Manual de usuario |
| `/contacto` | contacto | Formulario de contacto |
| `/analisis/{token}` | analisis.compartido | Análisis TDR compartido (público) |
| `/analisis-mayores/{token}` | analisis.mayores.compartido | Análisis Mayores compartido (público) |
| `/politica-de-privacidad` | legal.politica-privacidad | Política de privacidad |
| `/condiciones-del-servicio` | legal.condiciones-servicio | Condiciones del servicio |
| `/eliminacion-de-datos` | legal.eliminacion-datos | Solicitud eliminación de datos |
| `/cotizar-guia` | cotizar.guia | Guía de cotización |
| `/proforma/{token}/print` | proforma.print | Vista de impresión de proforma |

### 🔐 Autenticación
| Ruta | Nombre | Descripción |
|---|---|---|
| `/login` | login | Inicio de sesión |
| `/register` | register | Registro (personal/empresa) |
| `/logout` | logout | Cerrar sesión |
| `/forgot-password` | password.request | Recuperar contraseña |
| `/reset-password/{token}` | password.reset | Reset password |
| `/email/verify` | verification.notice | Verificar email |
| `/perfil` | perfil | Perfil de usuario (editar datos, ver suscripción) |

### 📊 Dashboard y Módulos Principales
| Ruta | Nombre | Descripción |
|---|---|---|
| `/home` | home | Dashboard con KPIs y accesos rápidos |
| `/buscador-publico` | buscador.publico | Contratos Menores (tabla + grid + filtros avanzados) |
| `/buscador-contratos-mayores` | buscador.mayores | Contratos Mayores OCDS API (7 acciones por registro) |
| `/seguimientos` | seguimientos | Calendario unificado Menores + Mayores |
| `/mis-procesos` | mis.procesos | Procesos guardados/seguidos |
| `/configuracion-alertas` | configuracion-alertas | Alertas Telegram/WhatsApp/Email |
| `/direccionamiento` | direccionamiento | Reportes de direccionamiento |
| `/tdr-repository` | tdr.repository | Repositorio de TDRs descargados |

### 💳 Suscripciones y Pagos
| Ruta | Nombre | Descripción |
|---|---|---|
| `/planes` | planes | Página de planes |
| `/planes/checkout/{plan}` | planes.checkout | Checkout (MercadoPago legacy) |
| `/planes/charge` | planes.charge | Procesar pago |
| `/planes/callback` | planes.callback | Callback post-pago |
| `/pago-yape/{plan}` | pago.yape.show | QR Yape + formulario comprobante |
| `/mi-suscripcion` | mi.suscripcion | Estado de suscripción + historial |
| `/billing` | billing | Facturación + renovación Yape |

### 🛠 Admin
| Ruta | Nombre | Descripción |
|---|---|---|
| `/suscripciones-premium` | suscripciones.premium | Gestionar suscripciones |
| `/admin/pagos-yape` | admin.pagos-yape | Validar pagos Yape |
| `/admin/correos` | admin.correos | Gestión de campañas de email |
| `/admin/analytics` | admin.analytics | Dashboard GA4 |
| `/roles-permisos` | roles.permisos | Gestión de roles y permisos |
| `/configuracion` | configuracion | Configuración del sistema |
| `/cuentas` | cuentas.* | CRUD cuentas SEACE |
| `/consumo-ia` | consumo.ia | Consumo de IA |
| `/clientes-mercado-pago` | clientes.mercadopago | Clientes MercadoPago |
| `/prueba-endpoints` | prueba-endpoints | Prueba de endpoints |

### 🔗 API / Webhooks
| Ruta | Nombre | Descripción |
|---|---|---|
| `POST /api/webhooks/mercadopago` | webhooks.mercadopago | Webhook MP |
| `POST /api/webhooks/openpay` | webhooks.openpay | Webhook Openpay |
| `GET/POST /api/webhooks/whatsapp` | webhooks.whatsapp.* | Webhook WhatsApp Cloud API |
| `POST /api/auth/token` | — | API token |
| `GET /api/user` | — | API user info |

---

## 🎯 TRES FLUJOS DE DEMO (para grabar con Playwright)

### Demo 1: "Encuentra y Analiza una Licitación en 60 Segundos"
```
URL inicio: /login
Credenciales demo: demo@correo.com / password123

Flujo:
1. Login → Dashboard
2. Sidebar: "Contratos Menores" → /buscador-publico
3. Escribir "laptop" en palabra clave
4. Seleccionar Objeto: "Bien"
5. Seleccionar Estado: "Vigente"
6. Esperar resultados
7. Cambiar a vista "Cuadrícula" (toggle)
8. Volver a "Tabla"
9. Click en "Ver" (ícono ojo azul) → modal detalle
10. Cerrar modal
11. Click en "Analizar IA" (ícono estrella púrpura)
12. Esperar análisis (~10-30s)
13. Mostrar modal: resumen ejecutivo, requisitos, penalidades
14. Scroll por las secciones
15. Cerrar
```

### Demo 2: "Contratos Mayores — Análisis Forense Completo"
```
URL inicio: /buscador-contratos-mayores

Flujo:
1. Ir a /buscador-contratos-mayores
2. Ver tabla con contratos, badges "Vigente" con pulso
3. Escribir "consultoría" en palabra clave
4. Click en "..." de un contrato
5. Mostrar dropdown con 7 opciones organizadas:
   - Info: Ver detalle, Ver Partes
   - Documento: Descargar TDR, Seguimiento
   - Herramientas IA (fondo púrpura): Analizar, Direccionamiento, Proforma
6. Click en "Analizar con IA" → loading → modal
7. Mostrar secciones: Requisitos de Calificación, Factores de Evaluación, Consorcio, Garantías
8. Click en "Compartir" (botón verde) → abre página pública /analisis-mayores/{token}
9. Volver
10. Click en "Direccionamiento" → loading → modal con score radial 0-100%
11. Mostrar hallazgos y argumento legal
```

### Demo 3: "Compra Premium y Activa Alertas"
```
URL inicio: /planes

Flujo:
1. Ir a /planes
2. Scroll por los 3 planes (Gratuito, Premium, Premium+Mayores)
3. Hacer hover en "Premium (Recomendado)"
4. Click "Comprar Mensual — S/ 49/mes"
5. Redirige a /pago-yape/monthly
6. Ver QR de Yape (grande, ocupa todo el ancho)
7. Card superior: "Escanea con Yape — S/ 49.00"
8. Formulario: subir screenshot, referencia, teléfono
9. Click "Enviar comprobante"
10. Redirige a /mi-suscripcion → "Pago pendiente de validación"
11. Admin: /admin/pagos-yape → ver comprobante → click ✅ Aprobar
12. Usuario recibe correo: "Pago aprobado — factura"
13. Ir a /configuracion-alertas → activar Telegram
14. Mostrar cómo llega la alerta al Telegram con botones
```

---

## 🔧 CONFIGURACIÓN DE ENTORNO

### Para grabar en local:
```bash
# Iniciar servidor
cd D:\xampp\htdocs\vigilante-seace
php artisan serve

# URL: http://localhost:8000

# Credenciales demo (crear en DB):
# admin@correo.com / password
# proveedor@correo.com / password
```

### Para grabar en producción:
```
URL: https://licitacionesmype.pe
Usar cuenta de prueba real con plan premium activo
```

### Resoluciones recomendadas:
- **Desktop:** 1440x900 (óptimo para grabar sidebar + tabla)
- **Landing/Mobile:** 390x844 (iPhone 14)

### Selectores clave para Playwright:
```
Selector                               | Elemento
---------------------------------------|--------------------------
`#trix-ta`                             | Editor Trix (cuerpo email)
`[wire\\:click="analizarTdr"]`         | Botón Analizar IA
`[wire\\:click="verContrato"]`         | Botón Ver detalle
`input[wire\\:model*="palabraClave"]`  | Input búsqueda
`a[href="/buscador-publico"]`          | Link Contratos Menores
`a[href="/buscador-contratos-mayores"]`| Link Contratos Mayores
`aside nav`                            | Sidebar navegación
`button[wire\\:click="hacerSeguimiento"]`| Botón Seguimiento
```

---

## 📦 TECNOLOGÍAS DESTACABLES EN EL SISTEMA

| Tecnología | Uso |
|---|---|
| **Laravel 12** | Backend framework |
| **Livewire 4** | Componentes reactivos sin JS |
| **Tailwind CSS** | Diseño responsive |
| **Alpine.js** | Interactividad frontend |
| **Gemini AI** | Análisis de TDR con IA |
| **FastAPI (Python)** | Microservicio analizador TDR |
| **WhatsApp Cloud API** | Bot interactivo con botones |
| **Telegram Bot API** | Bot con inline keyboards |
| **MailerSend** | Envío de correos transaccionales |
| **MercadoPago** | Pasarela de pago (legacy) |
| **OCDS API** | Datos abiertos de contrataciones |
| **Trix Editor** | Editor WYSIWYG para emails |
| **Vite** | Build system frontend |
| **MySQL** | Base de datos |
| **Redis** | Cache y colas |

---

## ⚠️ NOTAS PARA EL AGENTE

1. **Tiempos de espera:** El análisis de TDR con IA puede tardar 10-30s. Usar `waitForSelector` o timeouts generosos.
2. **Livewire:** La mayoría de interacciones son AJAX. Esperar a que los spinners desaparezcan antes de continuar.
3. **Sidebar colapsable:** En pantallas de buscador, el menú lateral está oculto por defecto. Click en el ícono hamburguesa para mostrarlo.
4. **Modales:** Los detalles/análisis se muestran en modales overlay. Usar Escape o click fuera para cerrar.
5. **Autenticación:** Para rutas protegidas, primero hacer login. Las rutas públicas no requieren auth.
6. **Emails:** Los correos de prueba se envían vía MailerSend. Usar Mailtrap o similar en local.
7. **WhatsApp:** El bot interactivo solo funciona en producción con el webhook configurado.
