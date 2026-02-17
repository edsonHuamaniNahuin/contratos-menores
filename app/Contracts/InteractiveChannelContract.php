<?php

namespace App\Contracts;

/**
 * Contrato para canales que soportan interacción con botones (callbacks).
 *
 * Principio SOLID: Interface Segregation (ISP)
 * Solo los canales con soporte de botones interactivos implementan esta interfaz.
 * Telegram y WhatsApp la implementan; Email NO la necesita.
 */
interface InteractiveChannelContract
{
    /**
     * Construir la estructura de botones por defecto para un contrato.
     * (Analizar TDR, Descargar TDR, Ver Compatibilidad)
     *
     * @param  array  $contratoData  Datos del contrato SEACE
     * @return array|null  Estructura de botones nativa del canal, o null si no aplica
     */
    public function buildDefaultKeyboard(array $contratoData): ?array;

    /**
     * Cachear el contexto de un contrato para uso posterior en callbacks.
     *
     * @param  array  $contratoData  Datos del contrato SEACE
     */
    public function cacheContratoContext(array $contratoData): void;

    /**
     * Enviar un documento/archivo binario al usuario.
     *
     * @param  string  $recipientId   Chat ID o Phone Number
     * @param  string  $documentBinary  Contenido binario del archivo
     * @param  string  $filename        Nombre del archivo
     * @param  string  $caption         Texto adjunto al documento
     * @return array{success: bool, message: string}
     */
    public function enviarDocumento(string $recipientId, string $documentBinary, string $filename, string $caption = ''): array;
}
