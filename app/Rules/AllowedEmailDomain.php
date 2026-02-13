<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida que el dominio del correo esté en la lista blanca.
 * Para cuentas tipo "empresa" esta regla se omite en el controller.
 */
class AllowedEmailDomain implements ValidationRule
{
    /**
     * Dominios exactos permitidos.
     */
    protected array $allowedDomains = [
        'gmail.com',
        'outlook.com',
        'outlook.es',
        'hotmail.com',
        'hotmail.es',
        'yahoo.com',
        'yahoo.es',
        'live.com',
        'msn.com',
        'icloud.com',
        'protonmail.com',
        'proton.me',
    ];

    /**
     * Sufijos de dominio permitidos (dominios institucionales peruanos y educativos).
     */
    protected array $allowedSuffixes = [
        '.com.pe',
        '.org.pe',
        '.edu.pe',
        '.gob.pe',
        '.net.pe',
        '.mil.pe',
        '.edu',
        '.gov',
        '.org',
        '.ac.uk',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $email = strtolower(trim($value));
        $domain = substr($email, strrpos($email, '@') + 1);

        if (empty($domain)) {
            $fail('El correo electrónico no tiene un dominio válido.');
            return;
        }

        // Verificar dominios exactos
        if (in_array($domain, $this->allowedDomains, true)) {
            return;
        }

        // Verificar sufijos institucionales
        foreach ($this->allowedSuffixes as $suffix) {
            if (str_ends_with($domain, $suffix)) {
                return;
            }
        }

        $fail('El dominio de correo ":domain" no está permitido. Usa Gmail, Outlook, Yahoo o un correo institucional (.com.pe, .edu.pe, .gob.pe).');
    }

    /**
     * Reemplaza placeholders en el mensaje de error.
     */
    public function message(): string
    {
        return 'El dominio de correo no está en la lista de dominios permitidos.';
    }
}
