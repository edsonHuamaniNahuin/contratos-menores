# WhatsApp: switch maestro de alertas (ON/OFF)

> Runbook operativo. Última actualización: **30/09/2026 23:51 (America/Lima)**.

## 1. Estado actual

- **Canal APAGADO** en producción desde el **30/09/2026 23:51** (una hora antes del cambio de política de Meta).
- **Motivo:** desde el **01/10/2026** Meta cobra también los mensajes de **servicio** (después de los primeros
  1,000 por número/mes) y los de **utilidad** enviados en la ventana de 24h. Las alertas del sistema son
  plantillas categoría **Marketing** (la tarifa más alta: ~0.258 AED ≈ S/ 0.26 por mensaje entregado).
- **Comando usado:** `php artisan whatsapp:alertas off`.
- **Qué ve el usuario:** modal informativo en los 4 buscadores, página pública `/aviso-whatsapp`,
  banner y toggle deshabilitado en `/configuracion-alertas`, y notas declarativas ("temporalmente
  deshabilitado") en landing, planes, manual, legales y landings de campaña.

## 2. Cómo funciona el switch

- **Doble condición** (`App\Services\WhatsAppNotificationService::alertasActivas()`):
  1. `config('services.whatsapp.alertas_activas')` — env `WHATSAPP_ALERTAS_ACTIVAS` (alias `ALERTAS_WSP`), default `true`.
  2. Sin centinela local: `storage/app/whatsapp-alertas.pausadas`.
- El centinela se consulta **en cada envío** → efecto **inmediato** en web, cola y bots, sin reinicios, y
  **sobrevive a `optimize:clear`** (no es cache).
- **Punto único de corte:** `WhatsAppNotificationService` bloquea `enviarProcesoASuscriptor`,
  `enviarMensaje`, `enviarMensajeConBotones`, `enviarDocumento` y `enviarTemplate`.
- **Flujos que se omiten además (para no marcar como enviado):**
  - Menores: `ImportarTdrNotificarJob` no registra el canal WhatsApp.
  - Mayores/re-alertas: `NotificarContratosMayoresJob::resolveChannel`.
  - Buena pro: `VigilarAdjudicacionesMayoresJob` (externos + opt-in).
  - Reenvíos: `ReenviarWhatsAppPendientesJob`.
  - Web: `MisProcesosNotificados`, `ConfiguracionAlertas`, `Suscriptores`, `PruebaEndpoints`.
  - Bot: `WhatsAppBotListener` sigue corriendo pero no envía nada (y se recupera solo al reactivar).
- **No afecta:** Telegram, correo electrónico, IA, buscadores, seguimiento ni pagos.

## 3. Comandos

Producción (siempre con el entorno de `www-data`):

```bash
cd /var/www/vigilante-seace

# Apagar (instantáneo)
sudo -u www-data env HOME=/tmp XDG_CONFIG_HOME=/tmp php artisan whatsapp:alertas off

# Ver estado
sudo -u www-data env HOME=/tmp XDG_CONFIG_HOME=/tmp php artisan whatsapp:alertas status
# → WHATSAPP_ALERTAS_ACTIVAS / Pausa local / Credenciales / ESTADO EFECTIVO

# Reactivar (instantáneo)
sudo -u www-data env HOME=/tmp XDG_CONFIG_HOME=/tmp php artisan whatsapp:alertas on
```

Alternativa por entorno (requiere limpiar config y reiniciar daemons porque su config se carga al arrancar):

```bash
# .env
WHATSAPP_ALERTAS_ACTIVAS=false        # o ALERTAS_WSP=false
php artisan optimize:clear
systemctl restart vigilante-queue telegram-bot whatsapp-bot
```

## 4. Evidencia de la desactivación

- Centinela: `storage/app/whatsapp-alertas.pausadas` creado por `www-data` el 30/09/2026 23:51:15.
- `whatsapp:alertas status` → `Pausa local: SÍ`, `ESTADO EFECTIVO: 🔕 PAUSADAS`.
- Prueba end-to-end: `alertasActivas=false`; `enviarMensaje()` y `enviarTemplate()` devuelven
  "Alertas por WhatsApp pausadas…" **sin llamar a la API de Meta**.

## 5. Por qué se apagó (contexto de costos)

- Costo real observado en Meta (panel de facturación): **3.87 AED / 15 entregados = 0.258 AED ≈ S/ 0.26**
  por mensaje de la plantilla `nuevo_contrato` (**categoría Marketing**, `es_PE`, APPROVED).
- Histórico (mar–sep 2026, `notification_sends`): **3,762** envíos WhatsApp, **803 fallidos** (no facturados),
  **2,959 no fallidos** → costo probable acumulado **≈ S/ 780** (techo ≈ S/ 991).
- Run-rate septiembre: 1,802 envíos/mes → **≈ S/ 315–475/mes**.
- Proyección octubre (+25% volumen): **S/ 395–590/mes** según entregas y tarifa.
- Política de Meta desde 01/10/2026 (fuentes al pie): cobra por **entrega**; **1,000 mensajes de servicio
  gratis por número/mes**; utilidad en ventana de 24h pasa a ser pagada; **72h gratis** si el usuario llega
  desde un punto de entrada gratuito (anuncios clic-a-WhatsApp, botón CTA de Facebook); método de pago
  debía estar registrado antes del 30/09/2026.

## 6. Checklist para PRENDER en el futuro

1. **Negocio:** confirmar que el gasto (~S/ 400–600/mes y creciendo) está aprobado.
2. **Tarifas:** revisar WhatsApp Manager → Facturación (tarifa vigente y moneda; hoy la WABA factura en AED).
3. **Categoría:** evaluar pedir reclasificación de `nuevo_contrato` a **Utility** (≈ 20–30% del costo de
   Marketing) o crear una plantilla nueva nacida como Utility; el contenido debe ser un aviso solicitado por
   el usuario, sin tono promocional.
4. **Pago:** verificar que el método de pago sigue registrado (Meta exige pago para mensajes de servicio).
5. **Canales alternos:** confirmar Telegram y correo activos (siguen siendo el respaldo).
6. **Encender:** `php artisan whatsapp:alertas on` y verificar con `status` → `ESTADO EFECTIVO: ✅ ACTIVAS`.
7. **Prueba controlada:** enviar una prueba desde `/configuracion-alertas` (botón de prueba) o
   `/prueba-endpoints`, y confirmar entrega en Meta.
8. **Monitoreo 48h:** `storage/logs/laravel.log` (errores 131047/131056) y panel de Meta (entregas/cargos).
9. **Avisos en la web:** el banner de `/configuracion-alertas` y las notas se ocultan solos cuando el
   switch vuelve a ON (leen `alertasActivas()`), **pero el modal de los buscadores no**: usa
   `localStorage('aviso-wsp-oct2026')`, así que seguiría apareciendo una vez por dispositivo. Antes de
   reactivar, condicionar el `@include('partials.aviso-whatsapp-modal')` a
   `!app(WhatsAppNotificationService::class)->alertasActivas()` en los 4 buscadores (pendiente de
   implementar) o cambiar la clave del localStorage.
10. **Usuarios:** comunicar la reactivación (correo/Telegram) y actualizar `/aviso-whatsapp`.

## 7. Troubleshooting

| Síntoma | Causa probable | Solución |
|---|---|---|
| Sigue enviando tras `off` | Archivo centinela no legible por el proceso | Verificar `storage/app/whatsapp-alertas.pausadas` (permisos `www-data`) |
| No envía tras `on` | Env `WHATSAPP_ALERTAS_ACTIVAS=false` | Revisar `.env`; si se cambió, `optimize:clear` + reiniciar daemons |
| `status` dice PAUSADAS sin centinela | Env en false | Corregir `.env` |
| Bot sin responder | Canal apagado (por diseño) o 131056 backoff | `whatsapp:alertas status`; reintentar tras backoff |
| Error 131047 | Ventana de 24h cerrada del usuario | Comportamiento normal de Meta; el reenvío se encola |

## 8. Referencias

- Meta — precios (tarifas por mercado/categoría): https://whatsappbusiness.com/es-la/products/platform-pricing/
- Meta — mensajes sin plantilla: https://developers.facebook.com/documentation/business-messaging/whatsapp/pricing/non-template-messages
- Gestión (14/09/2026): "WhatsApp empezará a cobrar por mensaje desde el 1 de octubre"
- Ahora (22/09/2026): "Meta cobrará por mensajes de servicio y utilidad en WhatsApp Business API desde el 1 de octubre"

## 9. Código relacionado

- `app/Services/WhatsAppNotificationService.php` (`alertasActivas()`, `pausarAlertas()`, `reanudarAlertas()`).
- `app/Console/Commands/WhatsAppAlertasCommand.php` (`whatsapp:alertas on|off|status`).
- Jobs gateados: `ImportarTdrNotificarJob`, `NotificarContratosMayoresJob`, `VigilarAdjudicacionesMayoresJob`,
  `ReenviarWhatsAppPendientesJob`.
- UI: `resources/views/livewire/configuracion-alertas.blade.php`, `resources/views/partials/nota-whatsapp.blade.php`,
  `resources/views/partials/aviso-whatsapp-modal.blade.php`, `resources/views/aviso-whatsapp.blade.php`.
- Config: `config/services.php` → `whatsapp.alertas_activas`; `.env.example` → `WHATSAPP_ALERTAS_ACTIVAS`.
