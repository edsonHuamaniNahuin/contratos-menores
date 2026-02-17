<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mantenimiento - Vigilante SEACE</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased min-h-screen bg-neutral-50">

    <!-- Navbar -->
    <nav class="bg-white border-b border-neutral-100 px-6 py-4">
        <div class="max-w-3xl mx-auto flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-2">
                <div class="w-9 h-9 bg-gradient-to-br from-primary-500 to-primary-400 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <span class="text-lg font-bold text-neutral-900">Vigilante SEACE</span>
            </a>
            <a href="{{ route('planes') }}" class="px-5 py-2 text-sm font-medium text-neutral-600 hover:text-primary-500 transition-colors">
                ← Volver a planes
            </a>
        </div>
    </nav>

    <!-- Contenido de mantenimiento -->
    <div class="max-w-2xl mx-auto px-6 py-24 text-center">
        <div class="bg-white rounded-3xl shadow-soft p-12 border border-neutral-100">

            <!-- Icono de herramientas -->
            <div class="w-20 h-20 mx-auto mb-8 bg-primary-800/10 rounded-full flex items-center justify-center">
                <svg class="w-10 h-10 text-primary-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M11.42 15.17l-5.21 5.21a2.121 2.121 0 01-3-3l5.21-5.21m4.5-4.5l2.83-2.83a1.5 1.5 0 012.12 0l.71.71a1.5 1.5 0 010 2.12l-2.83 2.83m-7.07 0l7.07-7.07"/>
                </svg>
            </div>

            <h1 class="text-2xl sm:text-3xl font-bold text-neutral-900 mb-4">
                Pasarela de pago en mantenimiento
            </h1>

            <p class="text-neutral-600 text-base leading-relaxed mb-6">
                Estamos realizando mejoras en nuestro sistema de pagos para brindarte
                una experiencia más segura y confiable. Este proceso es temporal.
            </p>

            <div class="bg-secondary-500/10 rounded-2xl p-5 mb-8">
                <div class="flex items-center justify-center gap-2 mb-2">
                    <svg class="w-5 h-5 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                    </svg>
                    <span class="text-sm font-semibold text-secondary-600">Volveremos pronto</span>
                </div>
                <p class="text-sm text-neutral-600">
                    Mientras tanto, puedes seguir usando el buscador público y todas las
                    funcionalidades gratuitas de Vigilante SEACE sin interrupciones.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="{{ route('planes') }}"
                   class="px-6 py-2.5 text-sm font-medium text-primary-800 border border-primary-800/20 hover:bg-primary-800/5 rounded-full transition-colors">
                    Ver planes
                </a>
                <a href="{{ url('/') }}"
                   class="px-6 py-2.5 text-sm font-medium text-white bg-primary-800 hover:bg-primary-600 rounded-full transition-colors">
                    Ir al inicio
                </a>
            </div>
        </div>

        <p class="text-xs text-neutral-400 mt-8">
            Si tienes dudas, escríbenos a
            <a href="mailto:noreply@licitacionesmype.pe" class="text-primary-800 hover:underline">noreply@licitacionesmype.pe</a>
        </p>
    </div>

</body>
</html>
