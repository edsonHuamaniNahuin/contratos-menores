<?php

namespace App\Contracts;

/**
 * Contrato para servicios de notificación de procesos SEACE.
 *
 * Principio SOLID: Interface Segregation (ISP)
 * Cada canal (Telegram, WhatsApp, Email) implementa esta interfaz,
 * permitiendo al ImportadorTdrEngine trabajar con cualquier canal
 * sin conocer los detalles de implementación (Dependency Inversion).
 */
interface NotificationChannelContract
{
    /**
     * Enviar notificación de un proceso a un suscriptor específico.
     *
     * @param  object  $suscripcion  Modelo del suscriptor (TelegramSubscription, WhatsAppSubscription, etc.)
     * @param  array   $contratoData  Datos del contrato SEACE (formato crudo del buscador)
     * @param  array   $matchedKeywords  Keywords que coincidieron con el contrato
     * @return array{success: bool, message: string}
     */
    public function enviarProcesoASuscriptor(object $suscripcion, array $contratoData, array $matchedKeywords = []): array;

    /**
     * Enviar un mensaje de texto simple a un chat/número.
     *
     * @param  string  $recipientId  Chat ID (Telegram) o Phone Number (WhatsApp)
     * @param  string  $mensaje      Texto del mensaje
     * @return array{success: bool, message: string}
     */
    public function enviarMensaje(string $recipientId, string $mensaje): array;

    /**
     * Enviar un mensaje con botones interactivos.
     *
     * @param  string  $recipientId  Chat ID o Phone Number
     * @param  string  $mensaje      Texto del mensaje
     * @param  array   $keyboard     Estructura de botones (cada canal lo adapta a su formato)
     * @return array{success: bool, message: string}
     */
    public function enviarMensajeConBotones(string $recipientId, string $mensaje, array $keyboard): array;

    /**
     * Verificar si el canal está habilitado y correctamente configurado.
     */
    public function isEnabled(): bool;

    /**
     * Nombre identificador del canal (telegram, whatsapp, email).
     */
    public function channelName(): string;
}
