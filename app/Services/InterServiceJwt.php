<?php

namespace App\Services;

/**
 * Genera y verifica JWT HMAC-SHA256 para autenticación inter-servicio
 * entre Laravel y el microservicio Python de análisis de TDR.
 *
 * No requiere dependencias externas — implementación RFC 7519 mínima.
 *
 * Seguridad:
 * - Tokens de corta vida (TTL configurable, default 60s)
 * - HMAC-SHA256 con secreto de 256 bits
 * - Claims: iss (emisor), sub (user_id), act (acción), iat, exp
 * - Protección contra replay attacks por expiración corta
 */
class InterServiceJwt
{
    /** Algoritmo HMAC */
    private const ALGO = 'sha256';

    /** Header JWT fijo (no necesita variar) */
    private const HEADER = '{"alg":"HS256","typ":"JWT"}';

    /** Tiempo de vida del token en segundos (default 120s = 2 min). */
    private int $ttl;

    /** Secreto compartido (hex-encoded 256 bits). */
    private string $secret;

    public function __construct()
    {
        $this->secret = (string) config('services.analizador_tdr.secret', '');
        $this->ttl = (int) config('services.analizador_tdr.jwt_ttl', 120);
    }

    /**
     * Genera un JWT firmado para enviar al microservicio Python.
     *
     * @param int|null    $userId  ID del usuario autenticado (0 = sistema/bot)
     * @param string      $action  Acción: analyze-tdr, analyze-dir, generate-proforma, batch, health
     * @param string|null $origin  Origen de la petición: web, telegram, whatsapp, job, cli
     */
    public function sign(?int $userId = null, string $action = 'analyze-tdr', ?string $origin = 'web'): string
    {
        $this->ensureSecret();

        $now = time();

        $payload = [
            'iss' => 'vigilante-seace',
            'sub' => $userId ?? 0,
            'act' => $action,
            'org' => $origin ?? 'web',
            'iat' => $now,
            'exp' => $now + $this->ttl,
        ];

        $headerB64 = $this->base64url(self::HEADER);
        $payloadB64 = $this->base64url(json_encode($payload, JSON_UNESCAPED_SLASHES));

        $signature = hash_hmac(self::ALGO, "{$headerB64}.{$payloadB64}", $this->getKey(), true);
        $signatureB64 = $this->base64url($signature);

        return "{$headerB64}.{$payloadB64}.{$signatureB64}";
    }

    /**
     * Verifica y decodifica un JWT (útil para tests).
     *
     * @return array Payload decodificado
     * @throws \RuntimeException Si el token es inválido o expirado
     */
    public function verify(string $token): array
    {
        $this->ensureSecret();

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new \RuntimeException('JWT malformado');
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        // Verificar firma
        $expectedSig = hash_hmac(self::ALGO, "{$headerB64}.{$payloadB64}", $this->getKey(), true);

        if (!hash_equals($this->base64url($expectedSig), $signatureB64)) {
            throw new \RuntimeException('Firma JWT inválida');
        }

        $payload = json_decode(base64_decode(strtr($payloadB64, '-_', '+/')), true);

        if (!$payload || !isset($payload['exp'])) {
            throw new \RuntimeException('Payload JWT inválido');
        }

        // Verificar expiración (con 5s de tolerancia para clock skew)
        if ($payload['exp'] < (time() - 5)) {
            throw new \RuntimeException('Token JWT expirado');
        }

        return $payload;
    }

    /**
     * Indica si el secreto está configurado.
     */
    public function isConfigured(): bool
    {
        return !empty($this->secret) && strlen($this->secret) >= 32;
    }

    /**
     * Base64url encoding (RFC 7515).
     */
    private function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Retorna la clave binaria derivada del hex secret.
     */
    private function getKey(): string
    {
        return hex2bin($this->secret) ?: $this->secret;
    }

    /**
     * Lanza excepción si el secreto no está configurado.
     */
    private function ensureSecret(): void
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException(
                'ANALIZADOR_TDR_SECRET no configurado. '
                . 'Genera uno con: php -r "echo bin2hex(random_bytes(32));"'
            );
        }
    }
}
