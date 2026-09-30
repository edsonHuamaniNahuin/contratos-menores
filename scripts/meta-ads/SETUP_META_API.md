# 🔑 SETUP — Meta Marketing API (lanzar campañas de Facebook por código)

> Con esto podré lanzar las campañas MA-Alertas / MA-Software / MA-Vigentes
> automáticamente desde el script `scripts/meta-ads/create_campaigns.py`.
> Se hace **una sola vez** (~15-20 min).

---

## Qué necesitas obtener (4 datos)

| Dato | Dónde encontrarlo | Ejemplo |
|---|---|---|
| **Access Token** | Paso 4 (System User) | `EAAG...` |
| **Ad Account ID** | Ads Manager → Configuración de la cuenta → ID de la cuenta | `act_1234567890` |
| **Page ID** | facebook.com/página → Información → ID de la página | `123456789` |
| **Pixel ID** | Meta Business Suite → Configuración de datos → Píxeles | `987654321` |

---

## PASO 1 — Crear la app de desarrollador (5 min)
1. Entra a **https://developers.facebook.com** con tu cuenta de Meta.
2. **Mis aplicaciones → Crear aplicación** → tipo **Negocio**.
3. Nombre: `Vigilante SEACE Ads` → Crear.

## PASO 2 — Añadir el producto Marketing API (2 min)
1. Dentro de la app → **Añadir productos** → **Marketing API** → Configurar.
2. Te pedirá vincular tu **cuenta publicitaria**: selecciona tu Ad Account.

## PASO 3 — Conectar la página y el pixel (2 min)
1. En la app → **Configuración → Permisos y funciones** → añadir la **Page** y el **Pixel** que usarás.
2. El token del paso siguiente debe tener permisos de anunciante sobre esa cuenta.

## PASO 4 — Crear el System User + token (5 min)
1. **Business Settings** (business.facebook.com/settings) → **Usuarios del sistema** → **Añadir**.
2. Nombre: `vigilante-ads-bot` · Rol: **Administrador**.
3. En el usuario creado → **Generar nuevo token**:
   - App: `Vigilante SEACE Ads`
   - Caducidad: **Nunca** (o 90 días si no aparece "Nunca")
   - Permisos: `ads_management`, `ads_read`, `pages_read_engagement`, `business_management`
4. **Copiar el token** (solo se muestra una vez).
5. Opcional pero recomendado: en la misma página del System User, asignar los **activos** (Ad Account, Page y Pixel).

## PASO 5 — Pasarme los datos
Envíame los 4 datos del cuadro superior y ejecuto:
```
python scripts/meta-ads/create_campaigns.py --token <TOKEN> --ad-account act_XXX --page-id XXX --pixel-id XXX
```
Las 3 campañas se crean **PAUSED** para revisión. Con `--activate` se lanzan activas.

---

## Notas técnicas
- **Imágenes**: el script sube las URLs públicas de licitacionesmype.pe (`/images/landings/*.jpg`). Si Meta rechaza alguna, cámbiala en `campaigns_config.json`.
- **Audiencias**: los intereses (SEACE, OSCE, Licitaciones...) se resuelven solos por nombre contra el catálogo de Meta; los que no existan se omiten.
- **Presupuesto**: `daily_budget_cents` en centavos de sol peruano → 1650 = S/ 16.50/día por campaña (~S/ 500/mes). Ajustable en el JSON.
- **Objetivo inicial**: OUTCOME_TRAFFIC con CTA. Si prefieres **Click a WhatsApp** (objetivo Mensajes), se cambia el `objective` y el CTA en el JSON — requiere verificar el número de WhatsApp en Meta Business (WhatsApp → número de negocio).
- **Conversión**: el pixel ya reporta `Contact` y `Lead` desde las landings; usa el evento como objetivo de optimización en el Ad Set cuando haya volumen.
