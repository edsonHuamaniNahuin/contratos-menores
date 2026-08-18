<?php

use App\Models\EmailTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Plantilla de campaña para avisar a los usuarios cuya ventana de 24h
     * de WhatsApp está vencida que no están recibiendo alertas y cómo
     * reactivarlas (política de Meta: la ventana se abre con un mensaje
     * del usuario o respondiendo una alerta del bot).
     */
    public function up(): void
    {
        EmailTemplate::updateOrCreate(
            ['name' => 'Ventana 24h WhatsApp vencida'],
            [
                'subject' => '⚠️ No estás recibiendo alertas de WhatsApp — reactiva tu ventana',
                'body' => '<p>Hola <strong>{{ nombre }}</strong>,</p>'
                    . '<p>Detectamos que <strong>no estás recibiendo alertas de nuevos procesos</strong> en tu WhatsApp.</p>'
                    . '<p>Por políticas de Meta (WhatsApp Business), solo podemos enviarte notificaciones dentro de una '
                    . '<strong>ventana de 24 horas</strong> desde tu último mensaje o respuesta a una alerta del bot. '
                    . 'Si han pasado más de 24 horas sin que interactúes, las alertas se pausan automáticamente.</p>'
                    . '<p>Para <strong>reactivar tus alertas</strong> y recibir los procesos pendientes:</p>'
                    . '<ol>'
                    . '<li>Abre WhatsApp y envía el mensaje <strong>"hola"</strong> al número <strong>+51 998 294 604</strong> '
                    . '(clic en este enlace: <a href="https://wa.me/51998294604?text=Hola%20Vigilante%20SEACE" target="_blank">enviar "hola" al bot</a>).</li>'
                    . '<li>También puedes <strong>responder cualquier alerta o botón</strong> que te haya llegado antes: '
                    . 'cada respuesta renueva la ventana por otras 24 horas.</li>'
                    . '<li>Una vez reactivada, recibirás automáticamente las notificaciones que quedaron pendientes.</li>'
                    . '</ol>'
                    . '<p>Este proceso mantiene tu cuenta activa y segura según las normas de WhatsApp.</p>'
                    . '<p>Saludos,<br><strong>Equipo Vigilante SEACE</strong></p>',
                'created_by' => 1,
            ]
        );
    }

    public function down(): void
    {
        EmailTemplate::where('name', 'Ventana 24h WhatsApp vencida')->delete();
    }
};
