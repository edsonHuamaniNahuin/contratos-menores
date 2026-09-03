# Notificaciones — Telegram + WhatsApp

## Arquitectura

```
ImportarTdrNotificarJob (cada 2h)
  │
  └─ ImportadorTdrEngine
      │
      ├─ TelegramNotificationService
      │   └─ POST api.telegram.org/bot{TOKEN}/sendMessage
      │       └─ Interactive buttons (Analizar, Descargar, Compatibilidad, Proforma)
      │
      └─ WhatsAppNotificationService
          └─ POST graph.facebook.com/v22.0/{phone_id}/messages
              ├─ Interactive List Message (si ventana 24h activa)
              └─ Template hello_world (si ventana cerrada)
```

## Configuración

```env
# Telegram
TELEGRAM_BOT_TOKEN=<token>
TELEGRAM_CHAT_ID=<chat_id>
TELEGRAM_ADMIN_BOT_TOKEN=<token_admin>

# WhatsApp
WHATSAPP_TOKEN=<token>
WHATSAPP_PHONE_NUMBER_ID=<id>
WHATSAPP_API_VERSION=v22.0
WHATSAPP_VERIFY_TOKEN=<token>
```

## Flujo de Notificación

1. `ImportarTdrNotificarJob` corre cada 2h (06:00-20:00)
2. Carga suscriptores activos de Telegram + WhatsApp con keywords
3. `ImportadorTdrEngine.ejecutar()`:
   - Consulta API SEACE (page_size=150)
   - Filtra por fecha
   - Para cada suscriptor: verifica keywords
   - Si match → envía notificación vía canal
4. Dedup per-suscriptor en `notification_sends`

## Keywords y Suscripciones

- Las keywords se configuran en `/configuracion-alertas`
- Se almacenan en `subscriber_profile_keyword` (pivot) vía `SubscriberProfile`
- Coincidencia: texto normalizado (lowercase + ASCII) contra:
  - `nomEntidad`, `desContratacion`, `desObjetoContrato`, `nomObjetoContrato`

## Canales

### Telegram
- Comando long-polling: `php artisan telegram:listen`
- Botones interactivos con 4 acciones por contrato
- Formato HTML para rich text

### WhatsApp
- Comando long-polling: `php artisan whatsapp:listen`
- Interactive List Message (5 opciones)
- Template fallback `hello_world` (en_US) para abrir ventana 24h
- **Importante:** El template `hello_world` solo existe en `en_US`, no en `es`. No cambiar.

## Problemas Conocidos

- Meta requiere ventana de 24h para mensajes interactivos
- Fuera de la ventana: solo templates pre-aprobados
- WhatsApp Cloud API v22.0 puede ser deprecada — verificar periódicamente
- El webhook de WhatsApp debe apuntar a dominio accesible públicamente
