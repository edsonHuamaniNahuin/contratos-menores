<?php

namespace App\Console\Commands;

use App\Mail\RespuestaLeadMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Envía la plantilla de respuesta comercial a un lead que pidió información.
 *
 * Uso:
 *   php artisan lead:responder correo@proveedor.com --nombre="Juan Pérez"
 */
class LeadResponderCommand extends Command
{
    protected $signature = 'lead:responder
        {email : Correo del lead}
        {--nombre= : Nombre del lead (opcional)}';

    protected $description = 'Envía la plantilla de respuesta de Vigilante SEACE a un lead.';

    public function handle(): int
    {
        $email = trim((string) $this->argument('email'));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('El correo no es válido: ' . $email);
            return self::FAILURE;
        }

        $nombre = trim((string) ($this->option('nombre') ?? ''));

        try {
            Mail::to($email)->send(new RespuestaLeadMail($nombre));
        } catch (\Throwable $e) {
            $this->error('No se pudo enviar: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info("Plantilla enviada a {$email}" . ($nombre !== '' ? " (para {$nombre})" : ''));

        return self::SUCCESS;
    }
}
