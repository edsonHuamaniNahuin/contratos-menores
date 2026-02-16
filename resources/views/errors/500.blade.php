<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 - Error del Servidor | Vigilante SEACE</title>
    @vite(['resources/css/app.css'])
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-4PRW1QCW48"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-4PRW1QCW48');
    </script>
</head>
<body class="bg-neutral-50 min-h-screen font-sans antialiased">
    <div class="min-h-screen flex items-center justify-center px-6 py-12">
        <div class="w-full max-w-lg text-center space-y-8">

            {{-- Ícono --}}
            <div class="mx-auto w-24 h-24 rounded-full bg-primary-800/10 flex items-center justify-center">
                <svg class="w-12 h-12 text-primary-800" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
            </div>

            {{-- Código --}}
            <p class="text-xs font-semibold tracking-[0.3em] text-primary-400 uppercase">Error 500</p>

            {{-- Título --}}
            <h1 class="text-3xl font-bold text-neutral-900">Algo salió mal</h1>

            {{-- Descripción --}}
            <p class="text-neutral-600 leading-relaxed max-w-sm mx-auto">
                El servidor encontró un error inesperado. Nuestro equipo ya fue notificado.
                Intenta de nuevo en unos minutos.
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
                <button onclick="location.reload()"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-white text-neutral-900 font-semibold text-sm rounded-full border border-neutral-200 hover:bg-neutral-50 transition-colors shadow-soft">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" />
                    </svg>
                    Reintentar
                </button>
            </div>

            {{-- Footer --}}
            <p class="text-xs text-neutral-400 pt-4">
                © {{ date('Y') }} Sunqupacha S.A.C. &middot; Vigilante SEACE
            </p>
        </div>
    </div>
</body>
</html>
