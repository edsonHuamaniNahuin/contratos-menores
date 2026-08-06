@extends('layouts.public')

@php
    $nombreCorto = Str::limit($entidadNombre, 50);
    $seoTitle = '📊 Contratos Mayores de ' . $nombreCorto . ' 2026 — SEACE | Vigilante SEACE';
    $seoDescription = 'Contratos mayores (>8 UIT) de ' . $entidadNombre . '. Licitaciones públicas, concursos públicos y adjudicaciones simplificadas. Análisis IA y proformas. Vigilante SEACE.';
    $canonicalUrl = url()->current();
@endphp

@section('title', $seoTitle)
@section('meta_description', $seoDescription)

@section('head')
    <meta name="keywords" content="contratos mayores {{ strtolower($nombreCorto) }}, licitaciones {{ strtolower($nombreCorto) }}, seace {{ strtolower($nombreCorto) }}, ruc {{ $entidadRuc }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => $seoTitle,
    'description' => $seoDescription,
    'url' => $canonicalUrl,
    'isPartOf' => ['@type' => 'WebSite', 'name' => 'Vigilante SEACE', 'url' => url('/')],
    'about' => ['@type' => 'Organization', 'name' => $entidadNombre, 'identifier' => $entidadRuc],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>
    <style>
        #regional-mayores-content [wire\:id] > .bg-white:first-of-type {
            padding-bottom: 0 !important;
        }
        #regional-mayores-content [wire\:id] > .bg-white:first-of-type > .bg-white.rounded-3xl.overflow-hidden {
            margin-left: -1rem !important;
            margin-right: -1rem !important;
            margin-bottom: -1rem !important;
        }
        @media (min-width: 1024px) {
            #regional-mayores-content [wire\:id] > .bg-white:first-of-type > .bg-white.rounded-3xl.overflow-hidden {
                margin-left: -1.5rem !important;
                margin-right: -1.5rem !important;
                margin-bottom: -1.5rem !important;
            }
        }
        /* Evitar desbordamiento de tabla: overflow-x auto para scroll horizontal */
        #regional-mayores-content,
        #regional-mayores-content [wire\:id],
        #regional-mayores-content [wire\:id] > div {
            max-width: 100% !important;
        }
        #regional-mayores-content [wire\:id] {
            overflow-x: auto !important;
            overflow-y: visible !important;
            width: 100% !important;
        }
        #regional-mayores-content [wire\:id] [style*="overflow: visible"] {
            overflow-x: auto !important;
        }
        #regional-mayores-content table {
            min-width: 700px;
        }
    </style>
@endsection

@section('content')
<article class="min-h-screen bg-neutral-50">
    {{-- ═══ Header ═══ --}}
    <div class="border-b border-neutral-200 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
            <div class="flex items-center gap-2 text-xs text-neutral-400 mb-1">
                <a href="/" class="hover:text-primary-500 transition-colors">Inicio</a>
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <a href="/buscador-contratos-mayores" class="hover:text-primary-500 transition-colors">Contratos Mayores</a>
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-neutral-600 font-medium">{{ $nombreCorto }}</span>
            </div>
            <h1 class="text-2xl font-bold text-neutral-900">Contratos Mayores de {{ $entidadNombre }}</h1>
            <p class="text-sm text-neutral-500 mt-1">
                Licitaciones públicas, concursos públicos y adjudicaciones simplificadas de {{ $nombreCorto }} (&gt;8 UIT). Datos actualizados 24/7 desde el SEACE 3.0.
                @if($entidadRuc) <span class="text-neutral-400">RUC: {{ $entidadRuc }}</span> @endif
            </p>
        </div>
    </div>

    {{-- ═══ Sidebar + Content ═══ --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex flex-col lg:flex-row gap-6 -mx-4 sm:mx-0">

            {{-- ═══ Mobile: lista horizontal scrolleable ═══ --}}
            <div class="lg:hidden px-4">
                <div class="flex items-center gap-1.5 overflow-x-auto pb-2" style="-webkit-overflow-scrolling: touch; scrollbar-width: none;">
                    @foreach(array_slice($entidades, 0, 15) as $ent)
                        <a href="{{ url('/buscador-contratos-mayores/' . $ent['slug']) }}"
                           class="shrink-0 px-3 py-1.5 rounded-full text-xs font-medium transition-colors whitespace-nowrap
                                  {{ $ent['slug'] === $entidadSlug ? 'bg-primary-500 text-white shadow-sm' : 'bg-white text-neutral-600 border border-neutral-200 hover:border-primary-300 hover:text-primary-600' }}">
                            {{ Str::limit($ent['nombre'], 28) }}
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- ═══ Desktop: sidebar vertical con buscador ═══ --}}
            <aside class="hidden lg:block lg:w-60 shrink-0">
                <div class="sticky top-4 space-y-4">
                    <div class="bg-white rounded-2xl border border-neutral-200 shadow-soft overflow-hidden" x-data="{ buscar: '', mostrarTodos: false }">
                        <div class="px-4 py-3 border-b border-neutral-100 bg-neutral-50/50">
                            <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Entidades</span>
                        </div>
                        {{-- Buscador --}}
                        <div class="px-3 pt-2 pb-1">
                            <div class="relative">
                                <svg class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-neutral-400 pointer-events-none shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                                <input type="text" x-model="buscar" placeholder="Filtrar entidad..."
                                       class="w-full pl-3 pr-8 py-1.5 text-xs bg-neutral-50 border border-neutral-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                        </div>
                        <nav class="max-h-[calc(100vh-24rem)] overflow-y-auto py-1">
                            @php $entidadesMostradas = 0; @endphp
                            @foreach($entidades as $ent)
                                @php
                                    $matchExpr = "(buscar === '' || '" . strtolower(addslashes($ent['nombre'])) . "'.includes(buscar.toLowerCase()) || '" . addslashes($ent['ruc'] ?? '') . "'.includes(buscar))";
                                    $showExpr = $entidadesMostradas < 20 ? $matchExpr : "({$matchExpr}) && (buscar !== '' || mostrarTodos)";
                                @endphp
                                <a href="{{ url('/buscador-contratos-mayores/' . $ent['slug']) }}"
                                   x-show="{{ $showExpr }}"
                                   class="flex items-center justify-between px-4 py-2 text-sm transition-colors
                                          {{ $ent['slug'] === $entidadSlug ? 'bg-primary-50 text-primary-700 font-semibold border-r-2 border-primary-500' : 'text-neutral-600 hover:bg-neutral-50 hover:text-primary-600' }}"
                                   title="{{ $ent['nombre'] }}">
                                    <span class="truncate">{{ Str::limit($ent['nombre'], 32) }}</span>
                                    <svg class="w-3 h-3 shrink-0 {{ $ent['slug'] === $entidadSlug ? 'text-primary-500' : 'text-neutral-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/></svg>
                                </a>
                                @php $entidadesMostradas++; @endphp
                            @endforeach
                        </nav>
                        {{-- Botón Ver más/menos --}}
                        <div class="px-3 py-2 border-t border-neutral-100" x-show="buscar === ''">
                            <button @click="mostrarTodos = !mostrarTodos"
                                    class="w-full text-xs text-primary-600 hover:text-primary-700 font-medium py-1 transition-colors"
                                    x-text="mostrarTodos ? 'Mostrar menos ▲' : 'Ver las {{ count($entidades) }} entidades ▼'"></button>
                        </div>
                    </div>

                    {{-- Espacio publicitario --}}
                    <div class="bg-gradient-to-br from-amber-50 to-yellow-50 rounded-2xl border border-amber-200 p-4 text-center">
                        <p class="text-xs text-amber-700 leading-relaxed">
                            📢 <strong>Aquí podría estar tu marca.</strong><br>
                            <span class="text-amber-600">88 entidades del Estado, cientos de proveedores… y cero ruido publicitario.</span>
                        </p>
                        <a href="{{ route('contacto') }}" class="inline-block mt-2 text-[11px] font-semibold text-amber-800 bg-amber-100 hover:bg-amber-200 px-3 py-1.5 rounded-full transition-colors">
                            Contáctanos →
                        </a>
                    </div>
                </div>
            </aside>

            {{-- Main Content --}}
            <div id="regional-mayores-content" class="flex-1 min-w-0 w-full overflow-x-auto">
                <script>
                    if (!localStorage.getItem('vistaMayores')) {
                        localStorage.setItem('vistaMayores', 'grid');
                    }
                </script>
                @livewire('buscador-mayores', [
                    'initialEntidad' => $entidadNombre,
                ])
            </div>
        </div>
    </div>
</article>
@endsection
