<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Planes - Vigilante SEACE</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Google Analytics — Consent Mode v2 -->
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('consent', 'default', {
            'ad_storage': 'denied',
            'ad_user_data': 'denied',
            'ad_personalization': 'denied',
            'analytics_storage': 'denied'
        });
        if (localStorage.getItem('cookie_consent') === 'granted') {
            gtag('consent', 'update', { 'analytics_storage': 'granted' });
        }
    </script>
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-4PRW1QCW48"></script>
    <script>
        gtag('js', new Date());
        gtag('config', 'G-4PRW1QCW48');
    </script>
</head>
<body class="font-sans antialiased min-h-screen bg-neutral-50">

    <!-- Navbar simple -->
    <nav class="bg-white border-b border-neutral-100 px-6 py-4">
        <div class="max-w-5xl mx-auto flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-2">
                <div class="w-9 h-9 bg-gradient-to-br from-primary-500 to-primary-400 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <span class="text-lg font-bold text-neutral-900">Vigilante SEACE</span>
            </a>
            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ route('home') }}" class="px-5 py-2 text-sm font-medium text-neutral-600 hover:text-primary-500 transition-colors">
                        Ir al Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="px-5 py-2 text-sm font-medium text-neutral-600 hover:text-primary-500 transition-colors">
                        Iniciar sesión
                    </a>
                    <a href="{{ route('register') }}" class="px-5 py-2.5 text-sm font-medium text-white bg-primary-500 hover:bg-primary-400 rounded-full transition-colors">
                        Crear cuenta gratis
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <div class="max-w-5xl mx-auto px-6 pt-16 pb-10 text-center">
        <h1 class="text-4xl font-bold text-neutral-900 leading-tight">
            Elige el plan que necesitas
        </h1>
        <p class="mt-4 text-lg text-neutral-400 max-w-2xl mx-auto">
            Monitorea las licitaciones del SEACE de forma simple.
            Empieza gratis y mejora cuando lo necesites.
        </p>
    </div>

    <!-- Cards de precios -->
    <div class="max-w-5xl mx-auto px-6 pb-20">
        <div class="grid md:grid-cols-2 gap-8 max-w-3xl mx-auto">

            <!-- ═══════════════════════════════════════════ -->
            <!-- CARD 1: PLAN GRATUITO                      -->
            <!-- ═══════════════════════════════════════════ -->
            <div class="bg-white rounded-3xl shadow-soft p-8 border border-neutral-100 flex flex-col">
                <!-- Encabezado -->
                <div class="mb-6">
                    <span class="inline-block px-4 py-1 text-xs font-semibold rounded-full bg-neutral-50 text-neutral-600 border border-neutral-200">
                        GRATUITO
                    </span>
                    <div class="mt-4 flex items-baseline gap-1">
                        <span class="text-4xl font-bold text-neutral-900">S/ 0</span>
                        <span class="text-sm text-neutral-400">/mes</span>
                    </div>
                    <p class="mt-2 text-sm text-neutral-400">
                        Para empezar a buscar oportunidades sin costo.
                    </p>
                </div>

                <!-- Separador -->
                <div class="border-t border-neutral-100 my-2"></div>

                <!-- Lista de beneficios -->
                <ul class="space-y-4 mt-4 flex-1">
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-secondary-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-sm text-neutral-600">
                            <strong class="text-neutral-900">Buscador de licitaciones</strong><br>
                            Busca entre miles de procesos publicados en el SEACE. Filtra por departamento, tipo de servicio y palabras clave.
                        </span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-secondary-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-sm text-neutral-600">
                            <strong class="text-neutral-900">Descarga de documentos TDR</strong><br>
                            Descarga los Términos de Referencia de cualquier proceso directamente desde la plataforma.
                        </span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-secondary-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-sm text-neutral-600">
                            <strong class="text-neutral-900">Datos actualizados</strong><br>
                            La información se sincroniza periódicamente con el portal oficial del SEACE para que siempre veas lo más reciente.
                        </span>
                    </li>
                </ul>

                <!-- Cosas que NO incluye -->
                <ul class="space-y-3 mt-6 pt-4 border-t border-neutral-100">
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-neutral-300 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span class="text-sm text-neutral-400">Alertas por Telegram</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-neutral-300 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span class="text-sm text-neutral-400">Notificaciones por correo</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-neutral-300 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span class="text-sm text-neutral-400">Análisis de TDR con inteligencia artificial</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-neutral-300 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span class="text-sm text-neutral-400">Seguimiento de procesos y calendario</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-neutral-300 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span class="text-sm text-neutral-400">Score de compatibilidad con tu empresa</span>
                    </li>
                </ul>

                <!-- Botón -->
                <div class="mt-8">
                    @auth
                        <span class="block w-full text-center px-6 py-3 text-sm font-medium text-neutral-400 bg-neutral-50 rounded-full border border-neutral-200">
                            Ya tienes una cuenta
                        </span>
                    @else
                        <a href="{{ route('register') }}" class="block w-full text-center px-6 py-3 text-sm font-medium text-primary-500 bg-white border-2 border-primary-500 rounded-full hover:bg-primary-500 hover:text-white transition-colors">
                            Crear cuenta gratis
                        </a>
                    @endauth
                </div>
            </div>

            <!-- ═══════════════════════════════════════════ -->
            <!-- CARD 2: PLAN PREMIUM                       -->
            <!-- ═══════════════════════════════════════════ -->
            <div class="relative bg-white rounded-3xl shadow-soft p-8 border-2 border-primary-500 flex flex-col">
                <!-- Badge "Popular" -->
                <div class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                    <span class="inline-block px-5 py-1 text-xs font-bold rounded-full bg-primary-500 text-white shadow-sm">
                        RECOMENDADO
                    </span>
                </div>

                <!-- Encabezado -->
                <div class="mb-6">
                    <span class="inline-block px-4 py-1 text-xs font-semibold rounded-full bg-primary-500/10 text-primary-500">
                        PREMIUM
                    </span>
                    <div class="mt-4 flex items-baseline gap-1">
                        <span class="text-4xl font-bold text-neutral-900">S/ 49</span>
                        <span class="text-sm text-neutral-400">/mes</span>
                    </div>
                    <p class="mt-2 text-sm text-neutral-400">
                        Para proveedores que quieren ganar más licitaciones.
                    </p>
                </div>

                <!-- Separador -->
                <div class="border-t border-neutral-100 my-2"></div>

                <!-- Incluye todo lo gratuito -->
                <div class="flex items-center gap-2 mt-4 mb-3">
                    <div class="w-5 h-5 rounded-full bg-secondary-500/10 flex items-center justify-center shrink-0">
                        <svg class="w-3 h-3 text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <span class="text-sm font-semibold text-neutral-900">Todo lo del plan Gratuito, más:</span>
                </div>

                <!-- Lista de beneficios PREMIUM -->
                <ul class="space-y-4 flex-1">
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-secondary-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-sm text-neutral-600">
                            <strong class="text-neutral-900">Alertas por Telegram</strong><br>
                            Recibe notificaciones en tu Telegram cuando aparezcan licitaciones que coincidan con tus palabras clave. Hasta 2 suscripciones con 5 palabras clave cada una.
                        </span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-secondary-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-sm text-neutral-600">
                            <strong class="text-neutral-900">Notificaciones por correo</strong><br>
                            Recibe un email por cada nuevo proceso que coincida con tus intereses. Incluye resumen del contrato y botón directo para hacer seguimiento.
                        </span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-secondary-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-sm text-neutral-600">
                            <strong class="text-neutral-900">Análisis con inteligencia artificial</strong><br>
                            Un resumen automático de cada TDR: qué requisitos piden, qué experiencia necesitas, las penalidades y el monto estimado. Sin necesidad de leer todo el documento.
                        </span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-secondary-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-sm text-neutral-600">
                            <strong class="text-neutral-900">Score de compatibilidad</strong><br>
                            Te decimos qué tan compatible es cada licitación con el rubro de tu empresa. Así no pierdes tiempo en procesos que no van contigo.
                        </span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-secondary-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-sm text-neutral-600">
                            <strong class="text-neutral-900">Seguimiento de procesos</strong><br>
                            Marca los procesos que te interesan y ve sus fechas importantes en un calendario. Así nunca se te pasa una fecha de cotización.
                        </span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-secondary-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-sm text-neutral-600">
                            <strong class="text-neutral-900">Alertas inteligentes con IA</strong><br>
                            Las notificaciones incluyen el análisis IA y el score de compatibilidad, para que desde tu celular o correo sepas si vale la pena cotizar.
                        </span>
                    </li>
                </ul>

                <!-- Botón -->
                <div class="mt-8">
                    @auth
                        <a href="https://wa.me/51999999999?text=Hola%2C%20quiero%20activar%20el%20plan%20Premium%20de%20Vigilante%20SEACE" target="_blank" class="block w-full text-center px-6 py-3 text-sm font-medium text-white bg-primary-500 rounded-full hover:bg-primary-400 transition-colors">
                            Solicitar Premium
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="block w-full text-center px-6 py-3 text-sm font-medium text-white bg-primary-500 rounded-full hover:bg-primary-400 transition-colors">
                            Empezar ahora
                        </a>
                    @endauth
                    <p class="mt-3 text-xs text-neutral-400 text-center">
                        Se activa manualmente por el equipo. Sin compromisos.
                    </p>
                </div>
            </div>

        </div>

        <!-- Nota al pie -->
        <div class="mt-16 text-center max-w-2xl mx-auto">
            <div class="bg-white rounded-3xl shadow-soft p-8 border border-neutral-100">
                <div class="flex items-center justify-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-primary-500/10 flex items-center justify-center">
                        <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-neutral-900">¿Tienes dudas?</h3>
                </div>
                <p class="text-sm text-neutral-600 leading-relaxed">
                    Si no estás seguro de cuál plan te conviene, empieza con el gratuito.
                    Puedes explorar el buscador y descargar TDR sin compromiso.
                    Cuando necesites alertas automáticas, análisis IA o seguimiento, pasa a Premium.
                </p>
                <div class="mt-6">
                    <a href="{{ route('buscador.publico') }}" class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-medium text-primary-500 border border-primary-500 rounded-full hover:bg-primary-500 hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Probar el buscador gratis
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="border-t border-neutral-100 bg-white py-8">
        <div class="max-w-5xl mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                {{-- Marca --}}
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-8 h-8 bg-gradient-to-br from-primary-500 to-primary-400 rounded-xl flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                            </svg>
                        </div>
                        <span class="text-sm font-bold text-neutral-900">Vigilante SEACE</span>
                    </div>
                    <p class="text-xs text-neutral-400 leading-relaxed">
                        Sistema de monitoreo automatizado de licitaciones del SEACE.
                    </p>
                </div>

                {{-- Contacto (protegido contra bots con JS) --}}
                <div>
                    <h4 class="text-xs font-bold text-neutral-900 uppercase tracking-wider mb-3">Contacto</h4>
                    <ul class="space-y-2">
                        <li>
                            <a id="ct-email" href="#" class="text-xs text-neutral-400 hover:text-primary-500 transition-colors flex items-center gap-2">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <span id="ct-email-text" data-a="c2VydmljZXM=" data-b="c3VucXVwYWNoYS5jb20="></span>
                            </a>
                        </li>
                        <li>
                            <a id="ct-phone" href="#" class="text-xs text-neutral-400 hover:text-primary-500 transition-colors flex items-center gap-2">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <span id="ct-phone-text" data-p="378 478 819 15+"></span>
                            </a>
                        </li>
                        <li>
                            <a id="ct-wa" href="#" target="_blank" class="text-xs text-neutral-400 hover:text-primary-500 transition-colors flex items-center gap-2">
                                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                </svg>
                                WhatsApp
                            </a>
                        </li>
                    </ul>
                </div>

                {{-- Enlaces --}}
                <div>
                    <h4 class="text-xs font-bold text-neutral-900 uppercase tracking-wider mb-3">Enlaces</h4>
                    <ul class="space-y-2">
                        <li><a href="{{ route('buscador.publico') }}" class="text-xs text-neutral-400 hover:text-primary-500 transition-colors">Buscador público</a></li>
                        <li><a href="{{ route('login') }}" class="text-xs text-neutral-400 hover:text-primary-500 transition-colors">Iniciar sesión</a></li>
                        <li><a href="{{ route('register') }}" class="text-xs text-neutral-400 hover:text-primary-500 transition-colors">Registrarse</a></li>
                    </ul>
                </div>
            </div>

            {{-- Copyright --}}
            <div class="mt-8 pt-6 border-t border-neutral-100 text-center">
                <p class="text-xs text-neutral-400">© {{ date('Y') }} Sunqupacha S.A.C. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    {{-- Anti-bot: decodifica contacto solo en navegadores reales --}}
    <script>
    (function(){
        var d=atob,r=function(s){return s.split('').reverse().join('')};
        // Email: base64("services") + "@" + base64("sunqupacha.com")
        var eS=document.getElementById('ct-email-text');
        if(eS){
            var u=d(eS.dataset.a),h=d(eS.dataset.b),e=u+'\x40'+h;
            eS.textContent=e;
            document.getElementById('ct-email').href='m\x61ilto:'+e;
        }
        // Phone: reversed "+51 918 874 873"
        var pS=document.getElementById('ct-phone-text');
        if(pS){
            var p=r(pS.dataset.p);
            pS.textContent=p;
            document.getElementById('ct-phone').href='t\x65l:'+p.replace(/\s/g,'');
        }
        // WhatsApp: build from phone digits
        var wA=document.getElementById('ct-wa');
        if(wA){
            var wn=r('37847881'+'15');
            wA.href='https://wa.me/'+wn;
        }
    })();
    </script>

    @include('components.cookie-consent')
</body>
</html>
