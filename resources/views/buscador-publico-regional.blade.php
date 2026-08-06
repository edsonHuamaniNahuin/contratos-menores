@extends('layouts.public')

@php
    $depDisplay = $nombreDep;
    $locationParts = [$depDisplay];
    if ($nombreProv) $locationParts[] = $nombreProv;
    if ($nombreDist) $locationParts[] = $nombreDist;
    $locationLabel = implode(', ', $locationParts);
    $seoTitle = '🔍 Contratos Menores en ' . $locationLabel . ' 2026 — Buscador SEACE | Vigilante SEACE';
    $seoDescription = 'Busca contratos menores del Estado en ' . $locationLabel . ' (&lt;8 UIT). Licitaciones del SEACE por palabra clave, entidad y tipo de proceso. Resultados actualizados 24/7. Vigilante SEACE.';
    $canonicalUrl = url()->current();
@endphp

@section('title', $seoTitle)
@section('meta_description', $seoDescription)

@section('head')
    <meta name="keywords" content="contratos menores {{ strtolower($depDisplay) }}, licitaciones {{ strtolower($depDisplay) }}, contratos publicos {{ strtolower($depDisplay) }}, seace {{ strtolower($depDisplay) }}, gobierno regional {{ strtolower($depDisplay) }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => $seoTitle,
    'description' => $seoDescription,
    'url' => $canonicalUrl,
    'isPartOf' => ['@type' => 'WebSite', 'name' => 'Vigilante SEACE', 'url' => url('/')],
    'about' => ['@type' => 'Place', 'name' => $locationLabel, 'address' => array_filter([
        '@type' => 'PostalAddress', 'addressRegion' => $depDisplay, 'addressLocality' => $nombreProv,
    ])],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>
@endsection

@section('content')
<article class="min-h-screen bg-neutral-50">
    {{-- ═══ Header ═══ --}}
    <div class="border-b border-neutral-200 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
            <div class="flex items-center gap-2 text-xs text-neutral-400 mb-1">
                <a href="/" class="hover:text-primary-500 transition-colors">Inicio</a>
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <a href="/buscador-publico" class="hover:text-primary-500 transition-colors">Contratos Menores</a>
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-neutral-600 font-medium">{{ $depDisplay }}</span>
            </div>
            <h1 class="text-2xl font-bold text-neutral-900">Contratos Menores en {{ $locationLabel }}</h1>
            <p class="text-sm text-neutral-500 mt-1">Licitaciones del Estado peruano en {{ $locationLabel }} (&lt;8 UIT). Busca por palabra clave, entidad y tipo de proceso. Datos actualizados 24/7 desde el SEACE.</p>
        </div>
    </div>

    {{-- ═══ Sidebar + Content ═══ --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex flex-col lg:flex-row gap-6 -mx-4 sm:mx-0">

            {{-- ═══ Mobile: lista horizontal scrolleable ═══ --}}
            <div class="lg:hidden -mx-1 px-4">
                <div class="flex items-center gap-1.5 overflow-x-auto pb-2 px-1" style="-webkit-overflow-scrolling: touch; scrollbar-width: none;">
                    @foreach($departamentos as $dpto)
                        <a href="{{ url('/buscador-publico/' . $dpto['slug']) }}"
                           class="shrink-0 px-3 py-1.5 rounded-full text-xs font-medium transition-colors whitespace-nowrap
                                  {{ $dpto['slug'] === $seoDep ? 'bg-primary-500 text-white shadow-sm' : 'bg-white text-neutral-600 border border-neutral-200 hover:border-primary-300 hover:text-primary-600' }}">
                            {{ $dpto['nombre'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- ═══ Desktop: sidebar vertical ═══ --}}
            <aside class="hidden lg:block lg:w-56 shrink-0">
                <div class="sticky top-4">
                    <div class="bg-white rounded-2xl border border-neutral-200 shadow-soft overflow-hidden">
                        <div class="px-4 py-3 border-b border-neutral-100 bg-neutral-50/50">
                            <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Departamentos</span>
                        </div>
                        <nav class="max-h-[calc(100vh-12rem)] overflow-y-auto py-1">
                            @foreach($departamentos as $dpto)
                                <a href="{{ url('/buscador-publico/' . $dpto['slug']) }}"
                                   class="flex items-center justify-between px-4 py-2 text-sm transition-colors
                                          {{ $dpto['slug'] === $seoDep ? 'bg-primary-50 text-primary-700 font-semibold border-r-2 border-primary-500' : 'text-neutral-600 hover:bg-neutral-50 hover:text-primary-600' }}">
                                    <span>{{ $dpto['nombre'] }}</span>
                                    <svg class="w-3 h-3 {{ $dpto['slug'] === $seoDep ? 'text-primary-500' : 'text-neutral-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            @endforeach
                        </nav>
                    </div>
                    {{-- Espacio publicitario --}}
                    <div class="mt-4 bg-gradient-to-br from-amber-50 to-yellow-50 rounded-2xl border border-amber-200 p-4 text-center">
                        <p class="text-xs text-amber-700 leading-relaxed">
                            📢 <strong>Aquí podría estar tu marca.</strong><br>
                            <span class="text-amber-600">26 regiones, miles de proveedores del Estado… y cero ruido publicitario. El mejor lugar para que te descubran.</span>
                        </p>
                        <a href="{{ route('contacto') }}" class="inline-block mt-2 text-[11px] font-semibold text-amber-800 bg-amber-100 hover:bg-amber-200 px-3 py-1.5 rounded-full transition-colors">
                            Contáctanos →
                        </a>
                    </div>
                </div>
            </aside>

            {{-- Main Content --}}
            <div id="regional-content" class="flex-1 min-w-0">
                @livewire('buscador-publico', [
                    'initialDep' => $seoDep,
                    'initialProv' => $seoProv,
                    'initialDist' => $seoDist,
                ])
            </div>
        </div>
    </div>
</article>
@endsection
