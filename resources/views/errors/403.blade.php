<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Acceso denegado | Vigilante SEACE</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-neutral-50 min-h-screen font-sans antialiased">
    <div class="min-h-screen flex items-center justify-center px-6 py-12">
        <div class="w-full max-w-lg text-center space-y-8">

            {{-- Ícono --}}
            <div class="mx-auto w-24 h-24 rounded-full bg-primary-800/10 flex items-center justify-center">
                <svg class="w-12 h-12 text-primary-800" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
            </div>

            {{-- Código --}}
            <p class="text-xs font-semibold tracking-[0.3em] text-primary-400 uppercase">Error 403</p>

            {{-- Título --}}
            <h1 class="text-3xl font-bold text-neutral-900">Acceso denegado</h1>

            {{-- Descripción --}}
            <p class="text-neutral-600 leading-relaxed max-w-sm mx-auto">
                No tienes permisos para acceder a este recurso. Si crees que es un error, contacta al administrador.
            </p>

            {{-- Acciones --}}
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="{{ url('/') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-primary-800 text-white font-semibold text-sm rounded-full hover:bg-primary-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                    Ir al inicio
                </a>
                <button onclick="history.back()"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-white text-neutral-900 font-semibold text-sm rounded-full border border-neutral-200 hover:bg-neutral-50 transition-colors shadow-soft">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                    </svg>
                    Volver atrás
                </button>
            </div>

            {{-- Footer --}}
            <p class="text-xs text-neutral-400 pt-4">
                Vigilante SEACE &middot; Monitoreo Inteligente de Contrataciones
            </p>
        </div>
    </div>
</body>
</html>
