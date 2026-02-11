<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Vigilante SEACE') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-neutral-50 min-h-screen font-sans">
    <div class="min-h-screen flex flex-col lg:flex-row">
        <div class="hidden lg:flex lg:w-1/2 bg-primary-500 text-white items-center justify-center p-12">
            <div class="max-w-md space-y-6">
                <p class="text-sm uppercase tracking-[0.4em] text-secondary-200">Sequence Dashboard</p>
                <h1 class="text-4xl font-bold leading-tight">Vigilante SEACE</h1>
                <p class="text-base text-primary-100 leading-relaxed">
                    Plataforma segura para monitorear contratos, ejecutar scraping resiliente y coordinar alertas
                    automáticas mediante Telegram. Ingresa tus credenciales para continuar.
                </p>
            </div>
        </div>
        <div class="flex-1 flex items-center justify-center px-6 py-10">
            <main class="w-full max-w-md">
                @yield('content')
            </main>
        </div>
    </div>
    @livewireScripts
    @stack('scripts')
</body>
</html>
