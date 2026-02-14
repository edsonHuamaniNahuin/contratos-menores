<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Planes - Vigilante SEACE</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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

    <!-- Footer mínimo -->
    <footer class="border-t border-neutral-100 bg-white py-6">
        <div class="max-w-5xl mx-auto px-6 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-xs text-neutral-400">© {{ date('Y') }} Vigilante SEACE. Todos los derechos reservados.</p>
            <div class="flex items-center gap-4">
                <a href="{{ route('buscador.publico') }}" class="text-xs text-neutral-400 hover:text-primary-500 transition-colors">Buscador</a>
                <a href="{{ route('login') }}" class="text-xs text-neutral-400 hover:text-primary-500 transition-colors">Iniciar sesión</a>
            </div>
        </div>
    </footer>

</body>
</html>
