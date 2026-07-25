<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>__TITLE__ — Blog Vigilante SEACE</title>
    <meta name="description" content="__EXCERPT__">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <meta property="og:title" content="__TITLE__">
    <meta property="og:description" content="__EXCERPT__">
    <meta property="og:type" content="article">
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
    <style>
        .prose { max-width: none; color: #374151; line-height: 1.75; }
        .prose h2 { font-size: 1.625rem; font-weight: 700; margin-top: 2.5rem; margin-bottom: 1rem; color: #025964; border-bottom: 2px solid #e8f0f1; padding-bottom: 0.375rem; }
        .prose h3 { font-size: 1.25rem; font-weight: 600; margin-top: 2rem; margin-bottom: 0.625rem; color: #014752; }
        .prose p { margin-bottom: 1.125rem; }
        .prose ul, .prose ol { padding-left: 1.5rem; margin-bottom: 1.125rem; }
        .prose li { margin-bottom: 0.375rem; }
        .prose a { color: #025964; text-decoration: underline; text-underline-offset: 2px; }
        .prose a:hover { color: #00D47E; }
        .prose blockquote { border-left: 4px solid #00D47E; background: #e6f9f2; padding: 0.75rem 1.25rem; margin: 1.75rem 0; border-radius: 0 0.75rem 0.75rem 0; color: #014752; font-style: normal; font-weight: 500; }
        .prose blockquote p { margin-bottom: 0; }
        .prose code { background: #f1f5f9; padding: 0.125rem 0.5rem; border-radius: 0.375rem; font-size: 0.875em; color: #025964; font-weight: 500; }
        .prose pre { background: #0f172a; color: #e2e8f0; padding: 1.25rem; border-radius: 1rem; overflow-x: auto; margin-bottom: 1.25rem; font-size: 0.875em; line-height: 1.6; }
        .prose pre code { background: transparent; padding: 0; color: inherit; font-weight: 400; }
        .prose strong { color: #111827; font-weight: 600; }
        .prose img { border-radius: 1rem; max-width: 100%; margin: 2rem 0; box-shadow: 0 4px 20px -2px rgba(0,0,0,0.08); }
        .prose table { width: 100%; border-collapse: separate; border-spacing: 0; margin: 1.75rem 0; border-radius: 0.75rem; overflow: hidden; border: 1px solid #e5e7eb; }
        .prose th { background: #f1f5f9; font-weight: 600; color: #334155; font-size: 0.8125rem; text-transform: uppercase; letter-spacing: 0.05em; padding: 0.75rem 1rem; text-align: left; border-bottom: 2px solid #e5e7eb; }
        .prose td { padding: 0.625rem 1rem; font-size: 0.875rem; border-bottom: 1px solid #f1f5f9; color: #475569; }
        .prose tr:last-child td { border-bottom: none; }
        .callout { border-left: 4px solid #025964; background: #e8f0f1; padding: 1rem 1.25rem; margin: 1.75rem 0; border-radius: 0 0.75rem 0.75rem 0; }
        .callout p { margin-bottom: 0; color: #014752; }
    </style>
</head>
<body class="font-sans antialiased min-h-screen bg-neutral-50 text-neutral-900">
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

    <main>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
            <article>
                <!-- Breadcrumb -->
                <div class="flex items-center gap-2 text-xs text-neutral-400 mb-6">
                    <a href="/blog/" class="hover:text-primary-500 transition-colors">Blog</a>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <a href="__CATEGORY_URL__" class="hover:text-primary-500 transition-colors font-medium">__CATEGORY__</a>
                </div>

                <!-- Category badge + date -->
                <div class="flex items-center gap-3 mb-4">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-primary-50 text-primary-600 border border-primary-100">__CATEGORY__</span>
                    <span class="text-xs text-neutral-400">__DATE_SHORT__</span>
                </div>

                <!-- Title -->
                <h1 class="text-3xl sm:text-4xl font-extrabold text-neutral-900 leading-tight mb-6">__TITLE__</h1>

                <!-- Author + Share -->
                <div class="flex items-center justify-between pb-8 mb-8 border-b border-neutral-200">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary-500 to-primary-400 flex items-center justify-center text-white text-sm font-bold shadow-sm">__INITIAL__</div>
                        <div>
                            <p class="text-sm font-semibold text-neutral-900">__AUTHOR__</p>
                            <p class="text-xs text-neutral-400">__DATE_LONG__ · __READTIME__ min de lectura</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="https://wa.me/?text=__SHARE_TEXT__" target="_blank" rel="noopener" class="w-8 h-8 flex items-center justify-center rounded-lg border border-green-200 text-green-600 hover:bg-green-50 transition-colors" title="WhatsApp"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg></a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=__URL_ENCODED__" target="_blank" rel="noopener" class="w-8 h-8 flex items-center justify-center rounded-lg border border-blue-200 text-blue-600 hover:bg-blue-50 transition-colors" title="LinkedIn"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg></a>
                        <button onclick="navigator.clipboard.writeText(window.location.href);var t=this;t.innerHTML='<svg class=&quot;w-4 h-4&quot; fill=&quot;none&quot; stroke=&quot;currentColor&quot; viewBox=&quot;0 0 24 24&quot;><path stroke-linecap=&quot;round&quot; stroke-linejoin=&quot;round&quot; stroke-width=&quot;2&quot; d=&quot;M5 13l4 4L19 7&quot;/></svg>';setTimeout(function(){t.innerHTML='<svg class=&quot;w-4 h-4&quot; fill=&quot;none&quot; stroke=&quot;currentColor&quot; viewBox=&quot;0 0 24 24&quot;><path stroke-linecap=&quot;round&quot; stroke-linejoin=&quot;round&quot; stroke-width=&quot;2&quot; d=&quot;M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1&quot;/></svg>'},2000)" class="w-8 h-8 flex items-center justify-center rounded-lg border border-neutral-200 text-neutral-500 hover:text-primary-600 hover:bg-primary-50 transition-colors" title="Copiar enlace"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg></button>
                    </div>
                </div>

                <!-- Featured image -->
                __IMAGE_BLOCK__

                <!-- Content -->
                <div class="prose">__CONTENT__</div>
            </article>

            <!-- Author bio -->
            <div class="mt-12 p-6 bg-white rounded-2xl shadow-card border border-neutral-100 flex items-start gap-4">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary-500 to-primary-400 flex items-center justify-center text-white font-bold text-lg shadow-sm shrink-0">__INITIAL__</div>
                <div>
                    <p class="text-sm font-bold text-neutral-900">__AUTHOR__</p>
                    <p class="text-xs text-neutral-500 leading-relaxed mt-0.5">Equipo de Vigilante SEACE. Ayudamos a MYPEs y proveedores a encontrar, analizar y ganar licitaciones del Estado peruano.</p>
                </div>
            </div>

            <!-- In-article CTA -->
            <div class="mt-8 bg-gradient-to-br from-primary-500 to-primary-600 text-white rounded-2xl p-6 sm:p-8 flex flex-col sm:flex-row items-start sm:items-center gap-5">
                <div class="flex-1">
                    <p class="text-lg sm:text-xl font-bold mb-1">Analiza TDRs automáticamente con IA</p>
                    <p class="text-sm text-primary-100 leading-relaxed">Regístrate gratis en Vigilante SEACE. Extrae requisitos, plazos y penalidades de cualquier TDR en segundos.</p>
                </div>
                <a href="/register" class="inline-flex items-center gap-2 px-6 py-3 bg-white text-primary-600 rounded-full text-sm font-bold hover:bg-primary-50 transition-colors shadow-md shrink-0">
                    Probar Gratis Ahora
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
            </div>

            <!-- Related posts -->
            __RELATED__

            <!-- Back -->
            <div class="mt-8 pt-6 border-t border-neutral-200">
                <a href="/blog/" class="inline-flex items-center gap-1.5 text-sm font-medium text-primary-500 hover:text-primary-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Todos los artículos
                </a>
            </div>
        </div>
    </main>

    <footer class="bg-white border-t border-neutral-200 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-xs text-neutral-400">&copy; __YEAR__ Vigilante SEACE. Todos los derechos reservados.</p>
            <div class="flex items-center gap-4 text-xs text-neutral-400">
                <a href="/buscador-publico" class="hover:text-primary-500">Contratos Menores</a>
                <a href="/buscador-contratos-mayores" class="hover:text-primary-500">Contratos Mayores</a>
                <a href="/planes" class="hover:text-primary-500">Planes</a>
                <a href="/contacto" class="hover:text-primary-500">Contacto</a>
            </div>
        </div>
    </footer>
    <script src="/blog/search-index.js"></script>
    <script>
    // Search is not on post pages, but the JS is here for potential future use
    </script>
</body>
</html>
