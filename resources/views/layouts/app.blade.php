<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Vigilante SEACE') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="h-screen bg-neutral-50 flex overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r border-neutral-100 flex flex-col">
            <!-- Logo -->
            <div class="p-6 border-b border-neutral-100">
                <h1 class="text-xl font-bold text-neutral-900">Vigilante SEACE</h1>
                <p class="text-xs text-neutral-400 mt-1">Sistema de Monitoreo</p>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 p-4 space-y-1">
                <a href="{{ route('home') }}" class="flex items-center gap-3 px-4 py-3 rounded-full text-sm font-medium transition-colors {{ request()->routeIs('home') ? 'bg-primary-500 text-white' : 'text-neutral-600 hover:bg-neutral-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Dashboard
                </a>

                <a href="{{ route('cuentas.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-full text-sm font-medium transition-colors {{ request()->routeIs('cuentas.*') ? 'bg-primary-500 text-white' : 'text-neutral-600 hover:bg-neutral-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Cuentas
                </a>

                <a href="{{ route('prueba-endpoints') }}" class="flex items-center gap-3 px-4 py-3 rounded-full text-sm font-medium transition-colors {{ request()->routeIs('prueba-endpoints') ? 'bg-primary-500 text-white' : 'text-neutral-600 hover:bg-neutral-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Endpoints
                </a>

                <a href="{{ route('configuracion') }}" class="flex items-center gap-3 px-4 py-3 rounded-full text-sm font-medium transition-colors {{ request()->routeIs('configuracion') ? 'bg-primary-500 text-white' : 'text-neutral-600 hover:bg-neutral-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Configuración
                </a>

                <a href="{{ route('tdr.repository') }}" class="flex items-center gap-3 px-4 py-3 rounded-full text-sm font-medium transition-colors {{ request()->routeIs('tdr.repository') ? 'bg-primary-500 text-white' : 'text-neutral-600 hover:bg-neutral-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                    Repositorio TDR
                </a>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 overflow-y-auto bg-neutral-50">
            <!-- Navbar -->
            <header class="bg-neutral-50 border-b border-neutral-100 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex-1 max-w-xl">
                        <div class="relative">
                            <input type="text" placeholder="Buscar..." class="w-full px-6 py-2.5 pl-12 bg-white border border-neutral-100 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <svg class="w-5 h-5 text-neutral-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 ml-4">
                        <button class="p-2.5 rounded-full bg-white border border-neutral-100 hover:bg-neutral-50 transition-colors">
                            <svg class="w-5 h-5 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-6">
                @yield('content')
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
