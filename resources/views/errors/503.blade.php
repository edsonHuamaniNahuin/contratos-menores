<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>503 - En mantenimiento | Vigilante SEACE</title>
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
            <div class="mx-auto w-24 h-24 rounded-full bg-secondary-500/10 flex items-center justify-center">
                <svg class="w-12 h-12 text-secondary-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085" />
                </svg>
            </div>

            {{-- Código --}}
            <p class="text-xs font-semibold tracking-[0.3em] text-primary-400 uppercase">Error 503</p>

            {{-- Título --}}
            <h1 class="text-3xl font-bold text-neutral-900">En mantenimiento</h1>

            {{-- Descripción --}}
            <p class="text-neutral-600 leading-relaxed max-w-sm mx-auto">
                Estamos realizando mejoras en la plataforma. Volveremos en unos minutos. Gracias por tu paciencia.
            </p>

            {{-- Animación de progreso --}}
            <div class="flex items-center justify-center gap-2">
                <div class="w-2 h-2 rounded-full bg-secondary-500 animate-bounce" style="animation-delay: 0ms"></div>
                <div class="w-2 h-2 rounded-full bg-secondary-500 animate-bounce" style="animation-delay: 150ms"></div>
                <div class="w-2 h-2 rounded-full bg-secondary-500 animate-bounce" style="animation-delay: 300ms"></div>
            </div>

            {{-- Reintentar --}}
            <div>
                <button onclick="location.reload()"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-primary-800 text-white font-semibold text-sm rounded-full hover:bg-primary-600 transition-colors">
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
