<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * El modo "Recibir todos los procesos" se elimina: todos los correos
     * de alerta pasan a "Filtrar por palabras clave del perfil" para
     * respetar el límite mensual del proveedor SMTP (MailerSend free).
     *
     * Este backfill cambia a los suscriptores existentes que tenían
     * notificar_todo = true.
     */
    public function up(): void
    {
        DB::table('email_subscriptions')
            ->where('notificar_todo', true)
            ->update([
                'notificar_todo' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // No reversible: la opción ya no existe en la UI.
    }
};
