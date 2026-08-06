<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Página no encontrada — Vigilante SEACE</title>
    <meta name="description" content="La página que buscas no existe. Usa el buscador de licitaciones del SEACE para encontrar contratos del Estado peruano.">
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
    <div class="min-h-screen flex flex-col items-center justify-center px-6 py-12">

        {{-- ═══ 404 Header ═══ --}}
        <div class="w-full max-w-lg text-center space-y-6">
            <div class="mx-auto w-24 h-24 rounded-full bg-primary-500/10 flex items-center justify-center">
                <svg class="w-12 h-12 text-primary-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>

            <p class="text-xs font-semibold tracking-[0.3em] text-primary-400 uppercase">Error 404</p>
            <h1 class="text-3xl font-bold text-neutral-900">Página no encontrada</h1>
            <p class="text-neutral-600 leading-relaxed max-w-sm mx-auto">
                La página que buscas no existe o fue movida. Usá el buscador para encontrar lo que necesitás.
            </p>
        </div>

        {{-- ═══ Buscador rápido ═══ --}}
        <div class="w-full max-w-md mt-8">
            <form action="{{ url('/buscador-publico') }}" method="GET" class="flex gap-2">
                <div class="flex-1 relative">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-5 h-5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    <input type="text" name="palabraClave" placeholder="Buscar licitaciones del Estado..."
                           class="w-full pl-10 pr-4 py-3 bg-white border border-neutral-200 rounded-xl text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 shadow-soft">
                </div>
                <button type="submit" class="px-5 py-3 bg-primary-500 text-white font-semibold text-sm rounded-xl hover:bg-primary-400 transition-colors shadow-soft">Buscar</button>
            </form>
        </div>

        {{-- ═══ Links útiles ═══ --}}
        <div class="w-full max-w-md mt-6">
            <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-3">También te puede interesar</p>
            <div class="grid grid-cols-2 gap-2">
                <a href="{{ url('/buscador-publico') }}" class="flex items-center gap-2 px-4 py-2.5 bg-white rounded-xl border border-neutral-200 text-sm text-neutral-700 hover:border-primary-300 hover:text-primary-600 transition-colors shadow-soft">
                    <svg class="w-4 h-4 shrink-0 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    Buscador SEACE
                </a>
                <a href="{{ url('/buscador-contratos-mayores') }}" class="flex items-center gap-2 px-4 py-2.5 bg-white rounded-xl border border-neutral-200 text-sm text-neutral-700 hover:border-primary-300 hover:text-primary-600 transition-colors shadow-soft">
                    <svg class="w-4 h-4 shrink-0 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    Contratos Mayores
                </a>
                <a href="{{ url('/manual') }}" class="flex items-center gap-2 px-4 py-2.5 bg-white rounded-xl border border-neutral-200 text-sm text-neutral-700 hover:border-primary-300 hover:text-primary-600 transition-colors shadow-soft">
                    <svg class="w-4 h-4 shrink-0 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    Manual
                </a>
                <a href="{{ url('/blog') }}" class="flex items-center gap-2 px-4 py-2.5 bg-white rounded-xl border border-neutral-200 text-sm text-neutral-700 hover:border-primary-300 hover:text-primary-600 transition-colors shadow-soft">
                    <svg class="w-4 h-4 shrink-0 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                    Blog
                </a>
            </div>
        </div>

        {{-- Botones principales --}}
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3 mt-8">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-primary-500 text-white font-semibold text-sm rounded-full hover:bg-primary-400 transition-colors shadow-soft">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                Ir al inicio
            </a>
        </div>

        <p class="text-xs text-neutral-400 mt-8">
            &copy; {{ date('Y') }} Sunqupacha S.A.C. &middot; Vigilante SEACE
        </p>
    </div>
</body>
</html>
