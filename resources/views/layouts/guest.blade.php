<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Vigilante SEACE') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
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
<body class="bg-neutral-50 min-h-screen font-sans">
    <div class="min-h-screen flex flex-col lg:flex-row">
        <div class="hidden lg:flex lg:w-1/2 bg-primary-500 text-white items-center justify-center p-12">
            <div class="max-w-md space-y-8">
                <div class="space-y-3">
                    <p class="text-sm uppercase tracking-[0.4em] text-secondary-200">Licitaciones MYPe</p>
                    <h1 class="text-4xl font-bold leading-tight text-white">Vigilante SEACE</h1>
                </div>
                <p class="text-base text-primary-100 leading-relaxed">
                    Encuentra oportunidades de contratación pública antes que tu competencia.
                </p>
                <ul class="space-y-3 text-sm text-primary-100">
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-secondary-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Monitoreo en tiempo real de convocatorias del SEACE</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-secondary-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Alertas instantáneas por Telegram, WhatsApp y correo</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-secondary-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Análisis automático de TDR con inteligencia artificial</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-secondary-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Filtros por departamento, rubro y palabra clave</span>
                    </li>
                </ul>
            </div>
        </div>
        <div class="flex-1 flex items-center justify-center px-6 py-10">
            <main class="w-full max-w-md">
                @yield('content')
                <p class="text-center text-xs text-neutral-400 mt-8">&copy; {{ date('Y') }} Sunqupacha S.A.C. Todos los derechos reservados.</p>
            </main>
        </div>
    </div>
    @livewireScripts
    @stack('scripts')
    @include('components.cookie-consent')
</body>
</html>
