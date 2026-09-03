# Suscripciones y Pagos

## Planes

- **Gratuito** — acceso básico al buscador
- **Proveedor Premium** — análisis IA, seguimiento, proformas, notificaciones

## Pasarela de Pago

- **MercadoPago** (principal)
- Openpay (alternativo)

## Flujo de Pago

1. Usuario selecciona plan (Mensual S/49 / Anual S/470)
2. Redirigido a checkout de MercadoPago
3. Pago con tarjeta (producción) o simulado (QA/local)
4. Webhook `mercadopago.webhook` recibe confirmación
5. `Subscription` creada/renovada con rol premium asignado

## Cobro Recurrente

- **Job:** `subscriptions:renew` (cada 6h)
- Intenta renovar suscripciones que vencen en 24h
- Cobra la tarjeta almacenada automáticamente
- Trial → Mensual (S/49), Mensual → Mensual (S/49), Anual → Anual (S/470)

## Alertas de Vencimiento

- **Job:** `subscriptions:alerts` (diario 08:00)
- Email 3 y 1 día antes del vencimiento
- Tipos: trial terminando, renovación próxima, suscripción expirando

## Expiración

- **Job:** `subscriptions:expire` (cada hora)
- Revoca rol premium al expirar suscripción/trial

## Cancelación

- Desde `/mi-suscripcion` o `/billing`
- Formulario con 7 razones predefinidas + texto libre
- Columna `cancellation_reason` en `subscriptions`
- Auto-renew se puede desactivar sin cancelar
