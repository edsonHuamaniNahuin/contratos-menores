<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Vigilante SEACE') }}</title>
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
<body class="font-sans antialiased min-h-screen bg-neutral-100" x-data="{ sidebarOpen: false }" :class="{ 'overflow-hidden': sidebarOpen }">
    <div class="min-h-screen bg-neutral-100 flex overflow-x-hidden">
        <!-- Overlay para cerrar sidebar en mobile -->
        <div
            x-show="sidebarOpen"
            @click="sidebarOpen = false"
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-neutral-900 bg-opacity-50 z-40 lg:hidden"
            style="display: none;"
        ></div>

        <!-- Sidebar - Responsive -->
        <aside
            x-bind:class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed lg:static inset-y-0 left-0 z-50 w-64 bg-white border-r border-neutral-100 flex flex-col transition-transform duration-300 ease-in-out lg:translate-x-0"
        >
            <!-- Logo -->
            <div class="p-6 border-b border-neutral-100 flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-neutral-900">Vigilante SEACE</h1>
                    <p class="text-xs text-neutral-400 mt-1">Sistema de Monitoreo</p>
                </div>
                <!-- Botón cerrar en mobile -->
                <button @click="sidebarOpen = false" class="lg:hidden text-neutral-400 hover:text-neutral-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 p-4 space-y-1">
                @auth
                    <a href="{{ route('home') }}" class="flex items-center gap-3 px-4 py-3 rounded-full text-sm font-medium transition-colors {{ request()->routeIs('home') ? 'bg-primary-500 text-white' : 'text-neutral-600 hover:bg-neutral-50' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Dashboard
                    </a>
                @endauth

                <a href="{{ route('buscador.publico') }}" class="flex items-center gap-3 px-4 py-3 rounded-full text-sm font-medium transition-colors {{ request()->routeIs('buscador.publico') ? 'bg-primary-500 text-white' : 'text-neutral-600 hover:bg-neutral-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Buscador Público
                </a>

                <a href="{{ route('planes') }}" class="flex items-center gap-3 px-4 py-3 rounded-full text-sm font-medium transition-colors {{ request()->routeIs('planes') ? 'bg-primary-500 text-white' : 'text-neutral-600 hover:bg-neutral-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Planes
                </a>

                @auth
                    @can('follow-contracts')
                        <a href="{{ route('seguimientos') }}" class="flex items-center gap-3 px-4 py-3 rounded-full text-sm font-medium transition-colors {{ request()->routeIs('seguimientos') ? 'bg-primary-500 text-white' : 'text-neutral-600 hover:bg-neutral-50' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Seguimientos
                        </a>
                    @endcan

                    @can('view-mis-procesos')
                        <a href="{{ route('mis.procesos') }}" class="flex items-center gap-3 px-4 py-3 rounded-full text-sm font-medium transition-colors {{ request()->routeIs('mis.procesos') ? 'bg-primary-500 text-white' : 'text-neutral-600 hover:bg-neutral-50' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            Mis Procesos
                        </a>
                    @endcan

                    @can('view-suscriptores')
                        <a href="{{ route('suscriptores') }}" class="flex items-center gap-3 px-4 py-3 rounded-full text-sm font-medium transition-colors {{ request()->routeIs('suscriptores') ? 'bg-primary-500 text-white' : 'text-neutral-600 hover:bg-neutral-50' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Suscriptores
                        </a>
                    @endcan

                    @can('view-cuentas')
                    <a href="{{ route('cuentas.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-full text-sm font-medium transition-colors {{ request()->routeIs('cuentas.*') ? 'bg-primary-500 text-white' : 'text-neutral-600 hover:bg-neutral-50' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Cuentas
                    </a>
                    @endcan

                    @can('view-prueba-endpoints')
                    <a href="{{ route('prueba-endpoints') }}" class="flex items-center gap-3 px-4 py-3 rounded-full text-sm font-medium transition-colors {{ request()->routeIs('prueba-endpoints') ? 'bg-primary-500 text-white' : 'text-neutral-600 hover:bg-neutral-50' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Endpoints
                    </a>
                    @endcan

                    @can('view-configuracion')
                    <a href="{{ route('configuracion') }}" class="flex items-center gap-3 px-4 py-3 rounded-full text-sm font-medium transition-colors {{ request()->routeIs('configuracion') ? 'bg-primary-500 text-white' : 'text-neutral-600 hover:bg-neutral-50' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Configuración
                    </a>
                    @endcan

                    @can('view-tdr-repository')
                    <a href="{{ route('tdr.repository') }}" class="flex items-center gap-3 px-4 py-3 rounded-full text-sm font-medium transition-colors {{ request()->routeIs('tdr.repository') ? 'bg-primary-500 text-white' : 'text-neutral-600 hover:bg-neutral-50' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                        Repositorio TDR
                    </a>
                    @endcan

                    @can('manage-roles-permissions')
                    <a href="{{ route('roles.permisos') }}" class="flex items-center gap-3 px-4 py-3 rounded-full text-sm font-medium transition-colors {{ request()->routeIs('roles.permisos') ? 'bg-primary-500 text-white' : 'text-neutral-600 hover:bg-neutral-50' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5 0l-1.5-1.5M13 12l1.5 1.5M4 7h7m-7 6h3m5 0h7"/>
                        </svg>
                        Roles y Permisos
                    </a>
                    @endcan

                    @can('manage-subscriptions')
                    <a href="{{ route('suscripciones.premium') }}" class="flex items-center gap-3 px-4 py-3 rounded-full text-sm font-medium transition-colors {{ request()->routeIs('suscripciones.premium') ? 'bg-primary-500 text-white' : 'text-neutral-600 hover:bg-neutral-50' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Suscripciones
                    </a>
                    @endcan
                @endauth
            </nav>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 bg-neutral-100 flex flex-col min-h-screen min-w-0 overflow-x-hidden">
            <!-- Navbar -->
            <header class="bg-white border-b border-neutral-200 px-4 lg:px-6 py-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <!-- Botón hamburger para mobile -->
                    <button
                        @click="sidebarOpen = !sidebarOpen"
                        class="lg:hidden p-2 rounded-lg text-neutral-600 hover:bg-neutral-100 transition-colors"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <div class="flex-1 max-w-xl mx-4">
                        <div class="relative hidden sm:block">
                            <input type="text" placeholder="Buscar..." class="w-full px-6 py-2.5 pl-12 bg-white border border-neutral-100 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <svg class="w-5 h-5 text-neutral-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        @auth
                            <!-- Dropdown usuario autenticado -->
                            <div x-data="{ open: false }" class="relative">
                                <button
                                    @click="open = !open"
                                    @click.outside="open = false"
                                    class="flex items-center gap-2 bg-white border border-neutral-100 rounded-full pl-3 pr-2 py-1.5 shadow-sm hover:bg-neutral-50 transition-colors"
                                >
                                    <div class="w-8 h-8 rounded-full bg-primary-500 flex items-center justify-center text-white text-sm font-bold shrink-0">
                                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                    </div>
                                    <span class="text-sm font-medium text-neutral-900 hidden sm:block max-w-[140px] truncate">{{ auth()->user()->name }}</span>
                                    <svg class="w-4 h-4 text-neutral-400 shrink-0 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>

                                <!-- Panel dropdown -->
                                <div
                                    x-show="open"
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-100"
                                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                    x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                                    class="absolute right-0 mt-2 w-64 bg-white rounded-2xl shadow-lg border border-neutral-100 py-2 z-50"
                                    style="display: none;"
                                >
                                    <!-- Info usuario -->
                                    <div class="px-4 py-3 border-b border-neutral-100">
                                        <p class="text-sm font-semibold text-neutral-900 truncate">{{ auth()->user()->name }}</p>
                                        <p class="text-xs text-neutral-400 truncate">{{ auth()->user()->email }}</p>
                                    </div>

                                    <!-- Mi perfil -->
                                    <div class="px-2 pt-2">
                                        <a href="{{ route('perfil') }}" class="w-full flex items-center gap-3 px-3 py-2.5 text-sm text-neutral-600 hover:bg-neutral-50 rounded-xl transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                            Mi perfil
                                        </a>
                                    </div>

                                    <!-- Logout -->
                                    <div class="px-2 pt-2">
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 text-sm text-neutral-600 hover:bg-neutral-50 rounded-xl transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                                </svg>
                                                Cerrar sesión
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @else
                            <!-- Login para visitantes -->
                            <a href="{{ route('login') }}" class="flex items-center gap-2 rounded-full px-5 py-2.5 text-sm font-medium transition-colors shadow-sm border border-neutral-200 text-neutral-700 bg-white hover:bg-neutral-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                </svg>
                                Iniciar sesión
                            </a>
                        @endauth
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-0 lg:p-6 w-full min-w-0 overflow-x-hidden">
                @yield('content')
            </main>
        </div>
    </div>

    @livewireScripts
    @stack('scripts')
    @include('components.cookie-consent')
</body>
</html>
