<?php

namespace App\Contracts;

/**
 * Contrato para el servicio de tracking de notificaciones.
 *
 * Principio SOLID: Dependency Inversion (DIP)
 * ImportadorTdrEngine y MisProcesosNotificados dependen de esta
 * abstracción, no de la implementación concreta.
 *
 * Principio SOLID: Interface Segregation (ISP)
 * Solo expone los métodos necesarios para:
 *   1. Consultar si un proceso ya fue notificado (dedup)
 *   2. Registrar un envío
 *   3. Consultar historial de un usuario
 */
interface NotificationTrackerContract
{
    /**
     * Obtener los IDs de procesos SEACE ya notificados a un usuario+canal+recipient.
     *
     * Usado por ImportadorTdrEngine para dedup per-subscriber.
     *
     * @param  int     $userId      ID del usuario
     * @param  string  $canal       Canal de notificación (telegram, whatsapp, email)
     * @param  string  $recipientId Identificador del destinatario (chat_id, phone, email)
     * @return array<string>        Lista de seace_proceso_id ya notificados
     */
    public function getNotifiedProcessIds(int $userId, string $canal, string $recipientId): array;

    /**
     * Registrar el envío de una notificación.
     *
     * Persiste el proceso (si no existe) y el envío en BD.
     * Retorna true si se registró correctamente, false si ya existía (duplicado).
     *
     * @param  array   $contratoData       Datos crudos del contrato SEACE
     * @param  string  $seaceProcesoId     Identificador único del proceso
     * @param  int     $userId             ID del usuario
     * @param  string  $canal              Canal (telegram, whatsapp, email)
     * @param  string  $recipientId        ID del destinatario
     * @param  string|null $subscriptionLabel  Etiqueta legible de la suscripción
     * @param  array   $keywordsMatched    Keywords que coincidieron
     * @return bool
     */
    public function recordNotification(
        array $contratoData,
        string $seaceProcesoId,
        int $userId,
        string $canal,
        string $recipientId,
        ?string $subscriptionLabel = null,
        array $keywordsMatched = []
    ): bool;

    /**
     * Verificar si un proceso específico ya fue notificado a un usuario+canal+recipient.
     *
     * @param  string  $seaceProcesoId
     * @param  int     $userId
     * @param  string  $canal
     * @param  string  $recipientId
     * @return bool
     */
    public function wasAlreadyNotified(
        string $seaceProcesoId,
        int $userId,
        string $canal,
        string $recipientId
    ): bool;
}
