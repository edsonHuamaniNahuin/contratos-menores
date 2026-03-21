@extends('layouts.public')

@section('title', 'Condiciones del Servicio — Licitaciones MYPe')
@section('meta_description', 'Condiciones del servicio de Licitaciones MYPe (Vigilante SEACE). Términos de uso, responsabilidades y limitaciones de la plataforma.')

@section('content')
<section class="bg-neutral-50 py-16 sm:py-20">
    <div class="max-w-3xl mx-auto px-6">

        <h1 class="text-3xl sm:text-4xl font-extrabold text-neutral-900 mb-4">Condiciones del Servicio</h1>
        <p class="text-sm text-neutral-400 mb-10">Última actualización: 21 de marzo de 2026</p>

        <div class="prose prose-neutral max-w-none space-y-8 text-neutral-600 leading-relaxed">

            {{-- 1 --}}
            <div>
                <h2 class="text-xl font-bold text-neutral-900 mb-3">1. Aceptación de las condiciones</h2>
                <p>
                    Al acceder o utilizar la plataforma <strong>Licitaciones MYPe</strong> (Vigilante SEACE), operada
                    por <strong>Sunqupacha S.A.C.</strong>, aceptas estas condiciones del servicio en su totalidad.
                    Si no estás de acuerdo con alguna de estas condiciones, no debes utilizar la plataforma.
                </p>
            </div>

            {{-- 2 --}}
            <div>
                <h2 class="text-xl font-bold text-neutral-900 mb-3">2. Descripción del servicio</h2>
                <p>Licitaciones MYPe es una plataforma que ofrece:</p>
                <ul class="list-disc pl-6 space-y-1.5 mt-2">
                    <li><strong>Monitoreo automatizado</strong> de procesos de contratación pública publicados en el Sistema Electrónico de Contrataciones del Estado (SEACE).</li>
                    <li><strong>Buscador público</strong> de licitaciones con filtros avanzados.</li>
                    <li><strong>Análisis de TDR con inteligencia artificial:</strong> extracción automática de requisitos, penalidades, reglas de ejecución y montos referenciales.</li>
                    <li><strong>Análisis de direccionamiento:</strong> detección de indicios de posibles irregularidades en las bases.</li>
                    <li><strong>Score de compatibilidad:</strong> evaluación automatizada de qué tan compatible es tu empresa con una licitación específica.</li>
                    <li><strong>Notificaciones automáticas</strong> por WhatsApp, Telegram y/o correo electrónico.</li>
                    <li><strong>Descarga de documentos</strong> de los procesos de contratación.</li>
                </ul>
            </div>

            {{-- 3 --}}
            <div>
                <h2 class="text-xl font-bold text-neutral-900 mb-3">3. Registro y cuenta de usuario</h2>
                <ul class="list-disc pl-6 space-y-1.5">
                    <li>Para acceder a las funcionalidades avanzadas es necesario crear una cuenta proporcionando información veraz y actualizada.</li>
                    <li>Eres responsable de mantener la confidencialidad de tus credenciales de acceso.</li>
                    <li>Debes notificarnos inmediatamente si detectas un uso no autorizado de tu cuenta.</li>
                    <li>Nos reservamos el derecho de suspender o eliminar cuentas que incumplan estas condiciones.</li>
                </ul>
            </div>

            {{-- 4 --}}
            <div>
                <h2 class="text-xl font-bold text-neutral-900 mb-3">4. Planes y pagos</h2>
                <ul class="list-disc pl-6 space-y-1.5">
                    <li>La plataforma ofrece planes gratuitos y de pago. Las características de cada plan se detallan en la página de <a href="{{ route('planes') }}" class="text-brand-800 hover:underline">Planes y precios</a>.</li>
                    <li>Los pagos se procesan a través de pasarelas seguras (MercadoPago, Openpay). No almacenamos datos de tarjeta.</li>
                    <li>Las suscripciones se renuevan automáticamente al finalizar cada período, salvo cancelación previa.</li>
                    <li>Puedes cancelar tu suscripción en cualquier momento desde la sección de Configuración de tu cuenta.</li>
                    <li>Los reembolsos se evaluarán caso por caso conforme al Código de Protección y Defensa del Consumidor (Ley N.° 29571).</li>
                </ul>
            </div>

            {{-- 5 --}}
            <div>
                <h2 class="text-xl font-bold text-neutral-900 mb-3">5. Uso aceptable</h2>
                <p>Al utilizar la plataforma te comprometes a:</p>
                <ul class="list-disc pl-6 space-y-1.5 mt-2">
                    <li>Usar el servicio únicamente para fines legítimos relacionados con contrataciones públicas.</li>
                    <li>No intentar acceder de forma no autorizada a los sistemas, cuentas de otros usuarios o datos restringidos.</li>
                    <li>No realizar scraping, extracción masiva o automatizada de datos de la plataforma.</li>
                    <li>No revender, redistribuir o sublicenciar el acceso al servicio sin autorización escrita.</li>
                    <li>No utilizar los análisis de IA como asesoría legal definitiva. Los resultados son orientativos.</li>
                </ul>
            </div>

            {{-- 6 --}}
            <div>
                <h2 class="text-xl font-bold text-neutral-900 mb-3">6. Propiedad intelectual</h2>
                <p>
                    Todo el contenido de la plataforma (diseño, código, textos, logotipos, marcas, algoritmos de análisis)
                    es propiedad de Sunqupacha S.A.C. o de sus licenciantes, y está protegido por las leyes de propiedad
                    intelectual del Perú y tratados internacionales aplicables.
                </p>
                <p class="mt-3">
                    Los datos de licitaciones provienen del SEACE (información pública del Estado peruano). El valor
                    agregado de los análisis, formatos y funcionalidades de la plataforma es propiedad de Sunqupacha S.A.C.
                </p>
            </div>

            {{-- 7 --}}
            <div>
                <h2 class="text-xl font-bold text-neutral-900 mb-3">7. Limitación de responsabilidad</h2>
                <ul class="list-disc pl-6 space-y-1.5">
                    <li>La información de licitaciones se obtiene del SEACE y se presenta "tal cual". No garantizamos la exactitud, integridad o actualización de los datos del SEACE.</li>
                    <li>Los análisis realizados por inteligencia artificial son orientativos y no constituyen asesoría legal, financiera o profesional.</li>
                    <li>El score de compatibilidad es una estimación automatizada y no garantiza la adjudicación de un proceso.</li>
                    <li>No somos responsables por decisiones comerciales tomadas basándose en la información proporcionada por la plataforma.</li>
                    <li>No garantizamos la disponibilidad ininterrumpida del servicio. Pueden ocurrir interrupciones por mantenimiento o factores externos.</li>
                    <li>Nuestra responsabilidad total por cualquier reclamación no excederá el monto pagado por el usuario en los últimos 12 meses.</li>
                </ul>
            </div>

            {{-- 8 --}}
            <div>
                <h2 class="text-xl font-bold text-neutral-900 mb-3">8. Privacidad y datos personales</h2>
                <p>
                    El tratamiento de datos personales se rige por nuestra
                    <a href="{{ route('legal.politica-privacidad') }}" class="text-brand-800 hover:underline">Política de Privacidad</a>,
                    que forma parte integral de estas condiciones. Al usar la plataforma, aceptas dicha política.
                </p>
                <p class="mt-3">
                    Puedes solicitar la eliminación de tus datos en cualquier momento siguiendo las instrucciones
                    en nuestra página de <a href="{{ route('legal.eliminacion-datos') }}" class="text-brand-800 hover:underline">Eliminación de Datos</a>.
                </p>
            </div>

            {{-- 9 --}}
            <div>
                <h2 class="text-xl font-bold text-neutral-900 mb-3">9. Notificaciones por WhatsApp y Telegram</h2>
                <ul class="list-disc pl-6 space-y-1.5">
                    <li>Al activar notificaciones por WhatsApp o Telegram, autorizas el envío de mensajes relacionados con el servicio.</li>
                    <li>Los mensajes incluyen: alertas de nuevas licitaciones, resultados de análisis, descargas de documentos y avisos del sistema.</li>
                    <li>Puedes desactivar las notificaciones en cualquier momento desde la Configuración de tu cuenta o enviando "STOP" al canal correspondiente.</li>
                    <li>Las notificaciones por WhatsApp están sujetas a los términos de uso de WhatsApp Business API (Meta Platforms, Inc.).</li>
                </ul>
            </div>

            {{-- 10 --}}
            <div>
                <h2 class="text-xl font-bold text-neutral-900 mb-3">10. Modificaciones del servicio y condiciones</h2>
                <p>
                    Nos reservamos el derecho de modificar, suspender o discontinuar cualquier aspecto del servicio,
                    así como actualizar estas condiciones. Los cambios significativos serán notificados a los
                    usuarios con al menos 15 días de anticipación por correo electrónico. El uso continuado de
                    la plataforma después de los cambios implica la aceptación de las nuevas condiciones.
                </p>
            </div>

            {{-- 11 --}}
            <div>
                <h2 class="text-xl font-bold text-neutral-900 mb-3">11. Terminación</h2>
                <ul class="list-disc pl-6 space-y-1.5">
                    <li>Puedes cerrar tu cuenta en cualquier momento desde la sección de Configuración.</li>
                    <li>Nos reservamos el derecho de suspender o terminar cuentas que infrinjan estas condiciones, sin previo aviso en casos graves.</li>
                    <li>Tras la terminación, perderás acceso a las funcionalidades de la cuenta. Los datos se eliminarán conforme a la Política de Privacidad.</li>
                </ul>
            </div>

            {{-- 12 --}}
            <div>
                <h2 class="text-xl font-bold text-neutral-900 mb-3">12. Legislación aplicable y jurisdicción</h2>
                <p>
                    Estas condiciones se rigen por las leyes de la República del Perú. Cualquier controversia que surja
                    en relación con el uso de la plataforma será sometida a la jurisdicción de los tribunales competentes
                    de la ciudad de Lima, Perú.
                </p>
            </div>

            {{-- 13 --}}
            <div>
                <h2 class="text-xl font-bold text-neutral-900 mb-3">13. Contacto</h2>
                <p>Para consultas sobre estas condiciones del servicio:</p>
                <ul class="list-disc pl-6 space-y-1.5 mt-2">
                    <li><strong>Empresa:</strong> Sunqupacha S.A.C.</li>
                    <li><strong>Correo:</strong> <a href="mailto:services@sunqupacha.com" class="text-brand-800 hover:underline">services@sunqupacha.com</a></li>
                    <li><strong>Teléfono:</strong> <a href="tel:+51918874873" class="text-brand-800 hover:underline">+51 918 874 873</a></li>
                </ul>
            </div>

        </div>
    </div>
</section>
@endsection
