<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Blog — Vigilante SEACE')</title>
    <meta name="description" content="@yield('description', 'Blog de Vigilante SEACE. Guías, noticias y análisis de contratación pública.')">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { 50: '#e8f0f1', 100: '#b8d2d6', 200: '#89b4bb', 300: '#5996a0', 400: '#2a7885', 500: '#025964', 600: '#014752', DEFAULT: '#025964' },
                        secondary: { 50: '#e6f9f2', 100: '#b3eed8', 200: '#80e3be', 300: '#4dd8a4', 400: '#1acd8a', 500: '#00D47E', DEFAULT: '#00D47E' },
                    },
                    borderRadius: { '3xl': '1.5rem', '4xl': '2rem' },
                    boxShadow: { 'soft': '0 4px 20px -2px rgba(0,0,0,0.05)', 'card': '0 1px 3px rgba(0,0,0,0.06), 0 4px 12px rgba(0,0,0,0.04)' }
                }
            }
        }
    </script>
    @yield('head')
</head>
<body class="font-sans antialiased min-h-screen bg-neutral-50 text-neutral-900">
    <!-- Navbar -->
    <header class="bg-white border-b border-neutral-200 sticky top-0 z-30 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
            <div class="flex items-center gap-6">
                <a href="/blog/" class="flex items-center gap-2 shrink-0">
                    <div class="w-8 h-8 bg-gradient-to-br from-primary-500 to-primary-400 rounded-xl flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <span class="font-bold text-neutral-900 text-sm">Vigilante SEACE <span class="text-[10px] text-neutral-400 font-normal tracking-wide">Blog</span></span>
                </a>
                <nav class="hidden md:flex items-center gap-1 text-xs font-medium">
                    <a href="/buscador-publico" class="px-3 py-1.5 rounded-lg text-neutral-500 hover:text-neutral-800 hover:bg-neutral-100 transition-colors">Contratos Menores</a>
                    <a href="/buscador-contratos-mayores" class="px-3 py-1.5 rounded-lg text-neutral-500 hover:text-neutral-800 hover:bg-neutral-100 transition-colors">Contratos Mayores</a>
                </nav>
            </div>
            <a href="/register" class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-500 text-white text-xs font-bold rounded-full hover:bg-primary-400 transition-colors shadow-sm">
                Probar Gratis
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
        </div>
    </header>

    <main>@yield('content')</main>

    <!-- Footer -->
    <footer class="bg-white border-t border-neutral-200 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-xs text-neutral-400">&copy; {{ date('Y') }} Vigilante SEACE. Todos los derechos reservados.</p>
            <div class="flex items-center gap-4 text-xs text-neutral-400">
                <a href="/buscador-publico" class="hover:text-primary-500 transition-colors">Contratos Menores</a>
                <a href="/buscador-contratos-mayores" class="hover:text-primary-500 transition-colors">Contratos Mayores</a>
                <a href="/planes" class="hover:text-primary-500 transition-colors">Planes</a>
                <a href="/contacto" class="hover:text-primary-500 transition-colors">Contacto</a>
            </div>
        </div>
    </footer>
    <script src="/blog/search-index.js"></script>
    <script>
    function searchPosts() {
        var q = document.getElementById('blog-search-input').value.trim().toLowerCase();
        var box = document.getElementById('blog-search-results');
        if (q.length < 2) { box.innerHTML = ''; box.classList.add('hidden'); return; }
        var results = (window.BLOG_POSTS || []).filter(function(p) {
            return p.title.toLowerCase().indexOf(q) !== -1 || p.excerpt.toLowerCase().indexOf(q) !== -1 || p.category.toLowerCase().indexOf(q) !== -1;
        }).slice(0, 5);
        if (results.length === 0) { box.innerHTML = '<div class="px-3 py-2 text-xs text-neutral-400">Sin resultados</div>'; box.classList.remove('hidden'); return; }
        var html = '';
        results.forEach(function(r) {
            html += '<a href="' + r.url + '" class="flex flex-col px-3 py-2 hover:bg-neutral-50 rounded-lg transition-colors"><span class="text-xs font-medium text-neutral-800 line-clamp-1">' + r.title + '</span><span class="text-[10px] text-neutral-400">' + r.category + ' · ' + r.date + '</span></a>';
        });
        box.innerHTML = html;
        box.classList.remove('hidden');
    }
    document.addEventListener('click', function(e) {
        var box = document.getElementById('blog-search-results');
        var input = document.getElementById('blog-search-input');
        if (box && input && !input.contains(e.target) && !box.contains(e.target)) { box.classList.add('hidden'); }
    });
    </script>
</body>
</html>
