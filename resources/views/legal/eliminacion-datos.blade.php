@extends('layouts.public')

@section('title', 'Eliminación de Datos — Licitaciones MYPe')
@section('meta_description', 'Instrucciones para solicitar la eliminación de tus datos personales en Licitaciones MYPe (Vigilante SEACE).')

@section('content')
<section class="bg-neutral-50 py-16 sm:py-20">
    <div class="max-w-3xl mx-auto px-6">

        <h1 class="text-3xl sm:text-4xl font-extrabold text-neutral-900 mb-4">Eliminación de Datos</h1>
        <p class="text-sm text-neutral-400 mb-10">Instrucciones para solicitar la eliminación de tu información personal</p>

        <div class="prose prose-neutral max-w-none space-y-8 text-neutral-600 leading-relaxed">

            {{-- Intro --}}
            <div>
                <p>
                    En <strong>Licitaciones MYPe</strong> (Vigilante SEACE), operado por <strong>Sunqupacha S.A.C.</strong>,
                    respetamos tu derecho a la eliminación de datos personales conforme a la Ley N.° 29733 de Protección
                    de Datos Personales del Perú. A continuación te explicamos cómo puedes solicitar la eliminación de
                    tu información.
                </p>
            </div>

            {{-- Opción 1 --}}
            <div>
                <h2 class="text-xl font-bold text-neutral-900 mb-3">Opción 1: Eliminar tu cuenta desde la plataforma</h2>
                <p>Si tienes una cuenta activa, puedes eliminar tu cuenta y todos los datos asociados siguiendo estos pasos:</p>
                <ol class="list-decimal pl-6 space-y-2 mt-3">
                    <li>Inicia sesión en <a href="{{ route('login') }}" class="text-brand-800 hover:underline">tu cuenta</a>.</li>
                    <li>Ve a <strong>Configuración</strong> en el menú lateral.</li>
                    <li>Desplázate hacia abajo hasta la sección <strong>"Eliminar cuenta"</strong>.</li>
                    <li>Confirma la eliminación ingresando tu contraseña.</li>
                </ol>
                <p class="mt-3">
                    Al eliminar tu cuenta, se eliminarán de forma permanente:
                </p>
                <ul class="list-disc pl-6 space-y-1.5 mt-2">
                    <li>Tu perfil y datos de registro (nombre, correo, teléfono).</li>
                    <li>Tu perfil empresarial y descripción de servicios.</li>
                    <li>Tus suscripciones y preferencias de notificación.</li>
                    <li>Tu historial de búsquedas y análisis de TDR.</li>
                    <li>Tus puntajes de compatibilidad y datos asociados.</li>
                    <li>Tu conexión con WhatsApp y/o Telegram.</li>
                </ul>
            </div>

            {{-- Opción 2 --}}
            <div>
                <h2 class="text-xl font-bold text-neutral-900 mb-3">Opción 2: Solicitar eliminación por correo electrónico</h2>
                <p>
                    Si no puedes acceder a tu cuenta o prefieres solicitar la eliminación de forma manual,
                    envía un correo electrónico a:
                </p>
                <div class="bg-white rounded-2xl border border-neutral-200 p-6 mt-4">
                    <p class="text-neutral-900 font-semibold">
                        📧 <a href="mailto:services@sunqupacha.com?subject=Solicitud%20de%20eliminaci%C3%B3n%20de%20datos&body=Solicito%20la%20eliminaci%C3%B3n%20de%20mis%20datos%20personales.%0A%0ANombre%3A%20%0ACorreo%20registrado%3A%20%0ATel%C3%A9fono%20(si%20aplica)%3A%20%0A%0AMotivo%20(opcional)%3A%20" class="text-brand-800 hover:underline">services@sunqupacha.com</a>
                    </p>
                    <p class="text-sm text-neutral-500 mt-2">Asunto: "Solicitud de eliminación de datos"</p>
                </div>
                <p class="mt-4">En tu correo, incluye la siguiente información para verificar tu identidad:</p>
                <ul class="list-disc pl-6 space-y-1.5 mt-2">
                    <li>Nombre completo.</li>
                    <li>Correo electrónico registrado en la plataforma.</li>
                    <li>Número de teléfono asociado (si aplica).</li>
                </ul>
            </div>

            {{-- Opción 3 --}}
            <div>
                <h2 class="text-xl font-bold text-neutral-900 mb-3">Opción 3: Dejar de recibir mensajes de WhatsApp</h2>
                <p>
                    Si deseas dejar de recibir notificaciones por WhatsApp sin eliminar tu cuenta completa:
                </p>
                <ul class="list-disc pl-6 space-y-1.5 mt-2">
                    <li>Envía el mensaje <strong>"STOP"</strong> al número de WhatsApp desde el que recibes las alertas.</li>
                    <li>O desactiva las notificaciones de WhatsApp desde la sección <strong>Configuración</strong> en tu cuenta.</li>
                </ul>
            </div>

            {{-- Plazos --}}
            <div>
                <h2 class="text-xl font-bold text-neutral-900 mb-3">Plazos de procesamiento</h2>
                <ul class="list-disc pl-6 space-y-1.5">
                    <li><strong>Eliminación desde la plataforma:</strong> inmediata.</li>
                    <li><strong>Solicitud por correo electrónico:</strong> procesada dentro de 10 días hábiles.</li>
                    <li><strong>Copias de seguridad:</strong> los datos se eliminan completamente de los respaldos en un plazo máximo de 30 días.</li>
                </ul>
            </div>

            {{-- Datos retenidos --}}
            <div>
                <h2 class="text-xl font-bold text-neutral-900 mb-3">Datos que podemos retener</h2>
                <p>
                    Conforme a la legislación vigente, ciertos datos pueden ser retenidos después de la eliminación
                    de la cuenta por motivos legales o contractuales:
                </p>
                <ul class="list-disc pl-6 space-y-1.5 mt-2">
                    <li>Registros de transacciones financieras (requeridos por la normativa tributaria peruana, hasta por 5 años).</li>
                    <li>Información necesaria para resolver disputas o reclamaciones pendientes.</li>
                </ul>
            </div>

            {{-- Contacto --}}
            <div>
                <h2 class="text-xl font-bold text-neutral-900 mb-3">Contacto</h2>
                <p>Si tienes preguntas sobre la eliminación de tus datos:</p>
                <ul class="list-disc pl-6 space-y-1.5 mt-2">
                    <li><strong>Correo:</strong> <a href="mailto:services@sunqupacha.com" class="text-brand-800 hover:underline">services@sunqupacha.com</a></li>
                    <li><strong>Teléfono:</strong> <a href="tel:+51918874873" class="text-brand-800 hover:underline">+51 918 874 873</a></li>
                </ul>
            </div>

        </div>
    </div>
</section>
@endsection
