<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

/**
 * Contrato para modelos de suscripción de canales de notificación.
 *
 * Principio SOLID: Liskov Substitution (LSP) + Interface Segregation (ISP)
 * TelegramSubscription y WhatsAppSubscription implementan esta interfaz,
 * permitiendo al ImportadorTdrEngine iterar sobre suscriptores sin importar el canal.
 */
interface ChannelSubscriptionContract
{
    /**
     * Obtener el identificador del destinatario (chat_id, phone_number, etc.)
     */
    public function getRecipientId(): string;

    /**
     * Resolver coincidencias entre los keywords del suscriptor y un contrato.
     *
     * @param  array  $contratoData  Datos crudos del contrato SEACE
     * @return array{pasa: bool, keywords: string[]}
     */
    public function resolverCoincidenciasContrato(array $contratoData): array;

    /**
     * Registrar que se envió una notificación a este suscriptor.
     */
    public function registrarNotificacion(): void;

    /**
     * Obtener el texto descriptivo de la empresa (company_copy).
     */
    public function getCompanyCopy(): ?string;

    /**
     * Scope: solo suscripciones activas.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActivas($query);
}
