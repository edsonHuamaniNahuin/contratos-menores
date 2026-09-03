# Integración TikTok — Vigilante SEACE / licitacionesmype.pe

> **Objetivo:** Crear y publicar videos promocionales automáticamente en TikTok.
> **App ID:** `7667600976140109832`
> **Client Key:** `awiopme8n86ov2a9`
> **Client Secret:** `uLG4rXx5SlgbNKBIDWAfyYu0kPbLe2O1`
> **Proyecto de videos:** `D:\proyectos\Sunqupacha\markegin IA\`

---

## 1. Estado actual

- [x] App creada en TikTok Developers
- [x] Scopes configurados: `user.info.basic`, `video.publish`, `video.upload`, `user.info.profile`, `user.info.stats`, `video.list`
- [x] Sistema de generación de videos funcionando (Python + moviepy + gTTS + ffmpeg)
- [x] Script de upload a TikTok v2 listo (`tiktok_uploader.py`)
- [x] Script para obtener tokens OAuth listo (`get_tiktok_token.py`)
- [x] Política de privacidad existente en `https://licitacionesmype.pe/politica-de-privacidad`
- [ ] **Falta: Verificación de dominio DNS (TXT record)**
- [ ] Falta: Obtener Access Token + Refresh Token vía OAuth
- [ ] Falta: Llenar formulario de revisión de la app
- [ ] Falta: Aprobación de TikTok (puede tomar días)

---

## 2. Verificación DNS (PENDIENTE — Hacer ahora)

TikTok exige verificar que somos dueños de `licitacionesmype.pe` agregando un registro TXT.

### 2.1 Obtener el valor TXT

En el panel de TikTok Developers, en la sección de verificación de dominio, TikTok muestra
un valor único. Debe verse similar a:

```
tik-tok-developers-verification=xxxxxxxxxxxxxxxxxxxxxxxx
```

> **IMPORTANTE:** Al agente que ejecute esto — preguntar al usuario el valor exacto que
> muestra TikTok. No se puede inventar. Cada app tiene un valor único.

### 2.2 Agregar el registro DNS

Dependiendo de dónde esté alojado el DNS de `licitacionesmype.pe`:

**Opción A — Cloudflare:**
1. Entrar a dashboard de Cloudflare
2. Seleccionar dominio `licitacionesmype.pe`
3. Ir a DNS → Records → Add record
4. Tipo: `TXT`
5. Name: `@`
6. Value: `<valor que te da TikTok>`
7. TTL: Auto
8. Save

**Opción B — cPanel / WHM:**
1. Entrar a cPanel del hosting
2. Zone Editor → Manage DNS
3. Add Record → Tipo TXT
4. Name: `licitacionesmype.pe.`
5. Value: `<valor que te da TikTok>`
6. Save

**Opción C — Desde el servidor vía comando (si aplica):**
```bash
# Solo si el DNS se gestiona desde el mismo servidor
# Averiguar primero dónde se gestiona el DNS
```

### 2.3 Verificar

Después de agregar el registro, esperar 2-5 minutos y hacer clic en "Verify" en TikTok.

Para verificar que el TXT se propagó:
```bash
nslookup -type=TXT licitacionesmype.pe
# o
dig TXT licitacionesmype.pe
```

---

## 3. Obtener tokens OAuth

Una vez verificado el dominio y completado el formulario de revisión:

### 3.1 Redirect URI

Agregar en la app de TikTok (App settings → Redirect domain):
```
licitacionesmype.pe
```

### 3.2 Ejecutar script para obtener tokens

```bash
cd "D:\proyectos\Sunqupacha\markegin IA"
python get_tiktok_token.py
```

El script:
1. Abre navegador con URL de autorización de TikTok
2. El usuario inicia sesión y autoriza
3. Copiar el `?code=XXXX` de la URL de redirección
4. Pegarlo en la terminal
5. El script intercambia el código por tokens y los guarda en `.env`

### 3.3 Verificar `.env`

Debe quedar así:
```
TIKTOK_CLIENT_KEY=awiopme8n86ov2a9
TIKTOK_CLIENT_SECRET=uLG4rXx5SlgbNKBIDWAfyYu0kPbLe2O1
TIKTOK_ACCESS_TOKEN=xxxxx...
TIKTOK_REFRESH_TOKEN=xxxxx...
```

---

## 4. Formulario de revisión de la app

Mientras se espera la propagación DNS, llenar en TikTok Developers:

| Campo | Valor |
|---|---|
| Description | Vigilante SEACE es una plataforma web peruana que automatiza el monitoreo de contrataciones publicas del Estado. Ayuda a empresas y MYPEs a encontrar, analizar y hacer seguimiento de licitaciones publicas mediante inteligencia artificial y notificaciones en tiempo real. |
| Privacy Policy URL | https://licitacionesmype.pe/politica-de-privacidad |
| Web/Desktop URL | https://licitacionesmype.pe |

**Explain how each product and scope works:**

```
Login Kit (user.info.basic, user.info.profile, user.info.stats):
Permite a proveedores del Estado registrarse e iniciar sesion usando su cuenta
de TikTok. Obtenemos nombre, avatar y metricas basicas para mostrar en el perfil
publico del proveedor dentro de nuestra plataforma de licitaciones.

Login Kit (video.list):
Permite al usuario visualizar desde nuestro panel los videos que ha publicado
a traves de nuestra integracion, para dar seguimiento a su contenido.

Content Posting API (video.publish, video.upload):
Nuestra plataforma genera automaticamente videos informativos sobre oportunidades
de contratacion publica, analisis de documentos del Estado y consejos para postular
a licitaciones. El usuario puede revisarlos y publicarlos directamente en su perfil
de TikTok desde nuestro panel de control, ayudandole a posicionarse como proveedor
confiable ante las entidades del Estado.

Webhooks (opcional):
Callback URL: https://licitacionesmype.pe/api/tiktok/webhook
Para recibir confirmacion de publicacion exitosa de videos.
```

---

## 5. Endpoints Laravel (tareas para el agente)

### 5.1 Redirect URI para OAuth

Crear ruta en `routes/web.php` que capture el callback de TikTok:

```php
Route::get('/tiktok-callback', function (Request $request) {
    $code = $request->get('code');
    // Mostrar el code para copiarlo o procesarlo automáticamente
    return response()->json(['code' => $code]);
})->name('tiktok.callback');
```

### 5.2 Webhook para notificaciones (opcional, fase 2)

Ruta en `routes/api.php`:
```php
Route::post('/tiktok/webhook', [TikTokWebhookController::class, 'handle']);
```

---

## 6. Publicar videos (comandos)

Una vez que todo esté configurado:

```bash
# Generar todos los videos
python video_generator.py ALL

# Subir un video a TikTok
python tiktok_uploader.py promo_general

# Subir todos los videos (con delay de 30s entre cada uno)
python tiktok_uploader.py ALL
```

---

## 7. Videos disponibles

| Key | Duración | Contenido |
|---|---|---|
| `promo_general` | 27.5s | Presentación completa del SaaS |
| `problema_solucion` | 10.5s | Formato problema/solución/resultado |
| `funcionalidades_ia` | 13.5s | Funcionalidades de IA |
| `testimonios_datos` | 14.0s | Datos y estadísticas |

---

## 8. Notas para el agente

- **NUNCA** inventes el valor del registro TXT de TikTok. Pregúntaselo al usuario.
- El DNS de `licitacionesmype.pe` puede estar en Cloudflare, el hosting cPanel, o en el mismo servidor. Averiguar antes de tocar.
- Los archivos `.env` con las credenciales de TikTok se guardan en `D:\proyectos\Sunqupacha\markegin IA\.env` y **NO deben committearse**.
- La política de privacidad ya existe en la app Laravel. No es necesario modificarla a menos que TikTok la rechace.
- La app puede tardar 2-5 días en ser aprobada por TikTok después de enviar el formulario.
