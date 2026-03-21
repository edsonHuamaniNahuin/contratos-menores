@extends('layouts.public')

@section('title', 'Política de Privacidad — Licitaciones MYPe')
@section('meta_description', 'Política de privacidad de Licitaciones MYPe (Vigilante SEACE). Conoce cómo recopilamos, usamos y protegemos tu información personal.')

@section('content')
<section class="bg-neutral-50 py-16 sm:py-20">
    <div class="max-w-3xl mx-auto px-6">

        <h1 class="text-3xl sm:text-4xl font-extrabold text-neutral-900 mb-4">Política de Privacidad</h1>
        <p class="text-sm text-neutral-400 mb-10">Última actualización: 21 de marzo de 2026</p>

        <div class="prose prose-neutral max-w-none space-y-8 text-neutral-600 leading-relaxed">

            {{-- 1 --}}
            <div>
                <h2 class="text-xl font-bold text-neutral-900 mb-3">1. Información general</h2>
                <p>
                    <strong>Sunqupacha S.A.C.</strong> (en adelante "nosotros" o "la empresa"), con domicilio en Perú,
                    opera la plataforma <strong>Licitaciones MYPe</strong> (también conocida como Vigilante SEACE),
                    accesible desde <a href="https://licitacionesmype.pe" class="text-brand-800 hover:underline">https://licitacionesmype.pe</a>.
                    Esta política describe cómo recopilamos, usamos, almacenamos y protegemos la información personal de nuestros usuarios.
                </p>
            </div>

            {{-- 2 --}}
            <div>
                <h2 class="text-xl font-bold text-neutral-900 mb-3">2. Datos que recopilamos</h2>
                <p>Podemos recopilar los siguientes tipos de información:</p>
                <ul class="list-disc pl-6 space-y-1.5 mt-2">
                    <li><strong>Datos de registro:</strong> nombre, correo electrónico, contraseña (cifrada), número de teléfono.</li>
                    <li><strong>Datos de perfil empresarial:</strong> razón social, RUC, rubro de negocio, descripción de servicios.</li>
                    <li><strong>Datos de uso:</strong> búsquedas realizadas, procesos monitoreados, análisis de TDR solicitados, preferencias de notificación.</li>
                    <li><strong>Datos de comunicación:</strong> número de teléfono de WhatsApp y/o Telegram para el envío de notificaciones y alertas que el usuario solicite.</li>
                    <li><strong>Datos técnicos:</strong> dirección IP, tipo de navegador, sistema operativo, páginas visitadas (a través de cookies analíticas con consentimiento).</li>
                    <li><strong>Datos de pago:</strong> procesados directamente por nuestras pasarelas de pago (MercadoPago, Openpay). No almacenamos datos de tarjeta de crédito o débito en nuestros servidores.</li>
                </ul>
            </div>

            {{-- 3 --}}
            <div>
                <h2 class="text-xl font-bold text-neutral-900 mb-3">3. Finalidad del tratamiento</h2>
                <p>Utilizamos la información recopilada para:</p>
                <ul class="list-disc pl-6 space-y-1.5 mt-2">
                    <li>Proveer el servicio de monitoreo de licitaciones y contrataciones públicas del SEACE.</li>
                    <li>Enviar notificaciones y alertas personalizadas por WhatsApp, Telegram o correo electrónico.</li>
                    <li>Realizar análisis automatizados de Términos de Referencia (TDR) mediante inteligencia artificial.</li>
                    <li>Calcular puntajes de compatibilidad entre el perfil del usuario y las licitaciones publicadas.</li>
                    <li>Gestionar la cuenta del usuario, suscripciones y pagos.</li>
                    <li>Mejorar la plataforma y la experiencia del usuario.</li>
                    <li>Cumplir con obligaciones legales aplicables.</li>
                </ul>
            </div>

            {{-- 4 --}}
            <div>
                <h2 class="text-xl font-bold text-neutral-900 mb-3">4. Base legal</h2>
                <p>
                    El tratamiento de los datos personales se realiza conforme a la Ley N.° 29733, Ley de Protección de Datos
                    Personales del Perú, y su Reglamento aprobado por Decreto Supremo N.° 003-2013-JUS. Las bases legales
                    aplicables son:
                </p>
                <ul class="list-disc pl-6 space-y-1.5 mt-2">
                    <li><strong>Consentimiento:</strong> al registrarse y aceptar estos términos.</li>
                    <li><strong>Ejecución contractual:</strong> para proveer el servicio contratado.</li>
                    <li><strong>Interés legítimo:</strong> para mejorar la plataforma y prevenir fraude.</li>
                </ul>
            </div>

            {{-- 5 --}}
            <div>
                <h2 class="text-xl font-bold text-neutral-900 mb-3">5. Compartición de datos</h2>
                <p>No vendemos, alquilamos ni compartimos tus datos personales con terceros, salvo en los siguientes casos:</p>
                <ul class="list-disc pl-6 space-y-1.5 mt-2">
                    <li><strong>Proveedores de servicios:</strong> pasarelas de pago (MercadoPago, Openpay), servicios de mensajería (WhatsApp Business API, Telegram Bot API), servicios de IA (Google Gemini) para el análisis de documentos.</li>
                    <li><strong>Obligación legal:</strong> cuando sea requerido por ley, orden judicial o autoridad competente.</li>
                    <li><strong>Protección de derechos:</strong> para proteger nuestros derechos legales o la seguridad de los usuarios.</li>
                </ul>
            </div>

            {{-- 6 --}}
            <div>
                <h2 class="text-xl font-bold text-neutral-900 mb-3">6. Almacenamiento y seguridad</h2>
                <p>
                    Los datos se almacenan en servidores seguros. Implementamos medidas técnicas y organizativas para proteger
                    la información, incluyendo:
                </p>
                <ul class="list-disc pl-6 space-y-1.5 mt-2">
                    <li>Cifrado de contraseñas mediante algoritmos seguros (bcrypt).</li>
                    <li>Comunicaciones cifradas mediante HTTPS/TLS.</li>
                    <li>Acceso restringido a los datos personales solo al personal autorizado.</li>
                    <li>Copias de seguridad periódicas.</li>
                </ul>
            </div>

            {{-- 7 --}}
            <div>
                <h2 class="text-xl font-bold text-neutral-900 mb-3">7. Retención de datos</h2>
                <p>
                    Conservamos tus datos personales mientras tu cuenta esté activa o sea necesario para prestarte el servicio.
                    Tras la eliminación de la cuenta, los datos se eliminan en un plazo máximo de 30 días, salvo cuando
                    exista una obligación legal de conservarlos por un período mayor.
                </p>
            </div>

            {{-- 8 --}}
            <div>
                <h2 class="text-xl font-bold text-neutral-900 mb-3">8. Derechos del usuario</h2>
                <p>Como titular de tus datos personales, tienes derecho a:</p>
                <ul class="list-disc pl-6 space-y-1.5 mt-2">
                    <li><strong>Acceso:</strong> solicitar información sobre los datos que tenemos sobre ti.</li>
                    <li><strong>Rectificación:</strong> corregir datos inexactos o incompletos.</li>
                    <li><strong>Cancelación:</strong> solicitar la eliminación de tus datos personales.</li>
                    <li><strong>Oposición:</strong> oponerte al tratamiento de tus datos para fines específicos.</li>
                </ul>
                <p class="mt-3">
                    Para ejercer estos derechos, envía un correo a
                    <a href="mailto:services@sunqupacha.com" class="text-brand-800 hover:underline">services@sunqupacha.com</a>
                    con el asunto "Derechos de datos personales" indicando tu solicitud.
                    También puedes solicitar la eliminación de tus datos desde nuestra
                    <a href="{{ route('legal.eliminacion-datos') }}" class="text-brand-800 hover:underline">página de eliminación de datos</a>.
                </p>
            </div>

            {{-- 9 --}}
            <div>
                <h2 class="text-xl font-bold text-neutral-900 mb-3">9. Cookies</h2>
                <p>
                    Utilizamos cookies estrictamente necesarias para el funcionamiento de la plataforma y cookies analíticas
                    (Google Analytics) solo con tu consentimiento previo. Puedes gestionar tus preferencias de cookies
                    en cualquier momento desde el banner de consentimiento.
                </p>
            </div>

            {{-- 10 --}}
            <div>
                <h2 class="text-xl font-bold text-neutral-900 mb-3">10. Uso de WhatsApp Business API</h2>
                <p>
                    Si eliges recibir notificaciones por WhatsApp, tu número de teléfono será procesado a través de la
                    API de WhatsApp Business (Meta Platforms, Inc.). Los mensajes se envían únicamente para las funcionalidades
                    que hayas solicitado (alertas de licitaciones, análisis de TDR, descarga de documentos). Puedes dejar
                    de recibir mensajes en cualquier momento enviando "STOP" o eliminando tu suscripción desde la plataforma.
                </p>
            </div>

            {{-- 11 --}}
            <div>
                <h2 class="text-xl font-bold text-neutral-900 mb-3">11. Cambios en esta política</h2>
                <p>
                    Nos reservamos el derecho de actualizar esta política de privacidad. Cualquier cambio significativo
                    será notificado a los usuarios mediante correo electrónico o aviso en la plataforma. La fecha de
                    última actualización se indica al inicio de este documento.
                </p>
            </div>

            {{-- 12 --}}
            <div>
                <h2 class="text-xl font-bold text-neutral-900 mb-3">12. Contacto</h2>
                <p>
                    Para consultas sobre esta política de privacidad o el tratamiento de tus datos, puedes contactarnos:
                </p>
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
