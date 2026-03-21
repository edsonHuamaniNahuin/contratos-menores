<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Licitaciones MYPe')</title>
    <meta name="description" content="@yield('meta_description', 'Plataforma inteligente de monitoreo de licitaciones y contrataciones del Estado peruano. Buscador SEACE, alertas automáticas y análisis con IA.')">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="canonical" href="{{ url()->current() }}">
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
    @stack('head')
</head>
<body class="font-sans antialiased min-h-screen bg-white">

    {{-- ═══ Navbar Pública ═══ --}}
    <nav class="bg-white border-b border-neutral-100 px-6 py-4 sticky top-0 z-50 backdrop-blur-lg bg-white/95" x-data="{ mobileMenu: false }">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <a href="{{ route('landing') }}" class="flex items-center gap-2.5">
                <div class="w-9 h-9 bg-gradient-to-br from-brand-800 to-brand-600 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div>
                    <span class="text-lg font-bold text-neutral-900 block leading-tight">Licitaciones MYPe</span>
                    <span class="text-[10px] text-neutral-400 font-medium tracking-wider uppercase">Vigilante SEACE</span>
                </div>
            </a>

            {{-- Desktop links --}}
            <div class="hidden md:flex items-center gap-1">
                <a href="{{ route('landing') }}" class="px-4 py-2 text-sm font-medium rounded-full transition-colors {{ request()->routeIs('landing') ? 'text-brand-800 bg-brand-800/5' : 'text-neutral-600 hover:text-brand-800' }}">
                    Inicio
                </a>
                <a href="{{ route('buscador.publico') }}" class="px-4 py-2 text-sm font-medium rounded-full transition-colors {{ request()->routeIs('buscador.publico') ? 'text-brand-800 bg-brand-800/5' : 'text-neutral-600 hover:text-brand-800' }}">
                    Buscador
                </a>
                <a href="{{ route('planes') }}" class="px-4 py-2 text-sm font-medium rounded-full transition-colors {{ request()->routeIs('planes') ? 'text-brand-800 bg-brand-800/5' : 'text-neutral-600 hover:text-brand-800' }}">
                    Planes
                </a>
                <a href="{{ route('manual') }}" class="px-4 py-2 text-sm font-medium rounded-full transition-colors {{ request()->routeIs('manual') ? 'text-brand-800 bg-brand-800/5' : 'text-neutral-600 hover:text-brand-800' }}">
                    Manual
                </a>
                <a href="{{ route('contacto') }}" class="px-4 py-2 text-sm font-medium rounded-full transition-colors {{ request()->routeIs('contacto') ? 'text-brand-800 bg-brand-800/5' : 'text-neutral-600 hover:text-brand-800' }}">
                    Contacto
                </a>
            </div>

            {{-- Auth buttons --}}
            <div class="hidden md:flex items-center gap-3">
                @auth
                    <a href="{{ route('home') }}" class="px-5 py-2 text-sm font-medium text-neutral-600 hover:text-brand-800 transition-colors">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="px-5 py-2 text-sm font-medium text-neutral-600 hover:text-brand-800 transition-colors">
                        Iniciar sesión
                    </a>
                    <a href="{{ route('register') }}" class="px-5 py-2.5 text-sm font-semibold text-white bg-brand-800 hover:bg-brand-900 rounded-full transition-colors">
                        Crear cuenta gratis
                    </a>
                @endauth
            </div>

            {{-- Mobile hamburger --}}
            <button @click="mobileMenu = !mobileMenu" class="md:hidden p-2 rounded-lg text-neutral-600 hover:bg-neutral-50">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path x-show="!mobileMenu" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    <path x-show="mobileMenu" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Mobile menu --}}
        <div x-show="mobileMenu" x-collapse class="md:hidden mt-4 pb-2 border-t border-neutral-100 pt-4">
            <div class="flex flex-col gap-1">
                <a href="{{ route('landing') }}" class="px-4 py-2.5 text-sm font-medium rounded-xl text-neutral-600 hover:bg-neutral-50">Inicio</a>
                <a href="{{ route('buscador.publico') }}" class="px-4 py-2.5 text-sm font-medium rounded-xl text-neutral-600 hover:bg-neutral-50">Buscador</a>
                <a href="{{ route('planes') }}" class="px-4 py-2.5 text-sm font-medium rounded-xl text-neutral-600 hover:bg-neutral-50">Planes</a>
                <a href="{{ route('manual') }}" class="px-4 py-2.5 text-sm font-medium rounded-xl text-neutral-600 hover:bg-neutral-50">Manual</a>
                <a href="{{ route('contacto') }}" class="px-4 py-2.5 text-sm font-medium rounded-xl text-neutral-600 hover:bg-neutral-50">Contacto</a>
                <div class="border-t border-neutral-100 mt-2 pt-2">
                    @auth
                        <a href="{{ route('home') }}" class="px-4 py-2.5 text-sm font-medium rounded-xl text-brand-800">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="px-4 py-2.5 text-sm font-medium rounded-xl text-neutral-600">Iniciar sesión</a>
                        <a href="{{ route('register') }}" class="block mx-4 mt-2 text-center py-2.5 text-sm font-semibold text-white bg-brand-800 rounded-full">Crear cuenta gratis</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    {{-- ═══ Contenido ═══ --}}
    <main>
        @yield('content')
    </main>

    {{-- ═══ Footer ═══ --}}
    <footer class="bg-neutral-900 text-white mt-auto">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10">
                {{-- Marca --}}
                <div class="lg:col-span-1 space-y-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 bg-gradient-to-br from-brand-600 to-primary-400 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="font-bold block leading-tight">Licitaciones MYPe</span>
                            <span class="text-[10px] text-neutral-500 font-medium tracking-wider uppercase">Vigilante SEACE</span>
                        </div>
                    </div>
                    <p class="text-sm text-neutral-400 leading-relaxed">
                        Plataforma de monitoreo inteligente de licitaciones del Estado peruano.
                    </p>
                </div>

                {{-- Plataforma --}}
                <div class="space-y-4">
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Plataforma</h4>
                    <ul class="space-y-2.5 text-sm text-neutral-400">
                        <li><a href="{{ route('buscador.publico') }}" class="hover:text-white transition-colors">Buscador público</a></li>
                        <li><a href="{{ route('planes') }}" class="hover:text-white transition-colors">Planes y precios</a></li>
                        <li><a href="{{ route('manual') }}" class="hover:text-white transition-colors">Manual del usuario</a></li>
                    </ul>
                </div>

                {{-- Empresa --}}
                <div class="space-y-4">
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Empresa</h4>
                    <ul class="space-y-2.5 text-sm text-neutral-400">
                        <li><a href="{{ route('contacto') }}" class="hover:text-white transition-colors">Contacto</a></li>
                        <li><a href="{{ route('legal.politica-privacidad') }}" class="hover:text-white transition-colors">Política de privacidad</a></li>
                        <li><a href="{{ route('legal.condiciones-servicio') }}" class="hover:text-white transition-colors">Condiciones del servicio</a></li>
                        <li><a href="{{ route('legal.eliminacion-datos') }}" class="hover:text-white transition-colors">Eliminación de datos</a></li>
                        @guest
                            <li><a href="{{ route('login') }}" class="hover:text-white transition-colors">Iniciar sesión</a></li>
                            <li><a href="{{ route('register') }}" class="hover:text-white transition-colors">Crear cuenta</a></li>
                        @endguest
                    </ul>
                </div>

                {{-- Contacto --}}
                <div class="space-y-4">
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Contacto</h4>
                    <ul class="space-y-2.5 text-sm text-neutral-400">
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-neutral-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <a href="mailto:services@sunqupacha.com" class="hover:text-white transition-colors">services@sunqupacha.com</a>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-neutral-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <a href="tel:+51918874873" class="hover:text-white transition-colors">+51 918 874 873</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-neutral-800 mt-12 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-xs text-neutral-500">&copy; {{ date('Y') }} Sunqupacha S.A.C. Todos los derechos reservados.</p>
                <p class="text-xs text-neutral-600">Licitaciones MYPe es un producto de <a href="https://sunqupacha.com" target="_blank" class="text-neutral-400 hover:text-white">Sunqupacha</a></p>
            </div>
        </div>
    </footer>

    @livewireScripts
    @stack('scripts')
    @include('components.cookie-consent')
</body>
</html>
