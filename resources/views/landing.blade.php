@extends('layouts.public')

@section('title', 'Licitaciones MYPe — Monitoreo Inteligente de Licitaciones del Estado Peruano')
@section('meta_description', 'Plataforma de monitoreo de licitaciones del SEACE con IA. Buscador público, análisis automático de TDR, notificaciones por Telegram y WhatsApp, score de compatibilidad.')

@section('content')

{{-- ═══════════════════════════════════════════════════════════════
     HERO
═══════════════════════════════════════════════════════════════ --}}
<section class="relative bg-gradient-to-br from-brand-900 via-brand-800 to-brand-600 overflow-hidden">
    <div class="absolute inset-0 opacity-10">
        <svg class="w-full h-full" viewBox="0 0 1200 600" fill="none">
            <circle cx="1000" cy="100" r="350" fill="white"/>
            <circle cx="200" cy="500" r="250" fill="white"/>
            <circle cx="600" cy="300" r="100" fill="white"/>
        </svg>
    </div>
    <div class="relative z-10 max-w-5xl mx-auto px-6 py-20 sm:py-28 text-center">
        <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm text-white text-xs font-semibold px-4 py-1.5 rounded-full mb-6">
            <span class="w-2 h-2 bg-secondary-400 rounded-full animate-pulse"></span>
            Vigilante SEACE monitoreando 24/7
        </div>
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white leading-tight mb-6">
            No pierdas ninguna<br>
            <span class="text-secondary-400">licitación del Estado</span>
        </h1>
        <p class="text-primary-200 text-lg sm:text-xl max-w-2xl mx-auto mb-10 leading-relaxed">
            Monitorea miles de procesos del SEACE con inteligencia artificial. Recibe alertas automáticas, analiza TDR en segundos y prioriza las oportunidades más compatibles con tu empresa.
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ route('buscador.publico') }}" class="inline-flex items-center gap-2 bg-white text-brand-800 font-bold text-base px-8 py-3.5 rounded-full hover:bg-neutral-50 transition-colors shadow-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Explorar Buscador Gratis
            </a>
            @guest
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 bg-white/10 border border-white/25 text-white font-bold text-base px-8 py-3.5 rounded-full hover:bg-white/20 transition-colors backdrop-blur-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Crear Cuenta Gratis
            </a>
            @endguest
        </div>
        <p class="text-white/70 text-sm font-medium mt-6">Sin tarjeta de crédito &middot; 15 días de prueba Premium</p>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════
     STATS BAR
═══════════════════════════════════════════════════════════════ --}}
<section class="bg-white border-b border-neutral-100">
    <div class="max-w-5xl mx-auto px-6 py-8">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 text-center">
            <div>
                <p class="text-2xl sm:text-3xl font-extrabold text-neutral-900">29,000+</p>
                <p class="text-xs text-neutral-400 mt-1">Procesos monitoreados</p>
            </div>
            <div>
                <p class="text-2xl sm:text-3xl font-extrabold text-neutral-900">25</p>
                <p class="text-xs text-neutral-400 mt-1">Departamentos cubiertos</p>
            </div>
            <div>
                <p class="text-2xl sm:text-3xl font-extrabold text-neutral-900">24/7</p>
                <p class="text-xs text-neutral-400 mt-1">Monitoreo continuo</p>
            </div>
            <div>
                <p class="text-2xl sm:text-3xl font-extrabold text-neutral-900">&lt; 5 min</p>
                <p class="text-xs text-neutral-400 mt-1">Alerta tras publicación</p>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════
     PROBLEMA → SOLUCIÓN
═══════════════════════════════════════════════════════════════ --}}
<section class="bg-neutral-50 py-20">
    <div class="max-w-5xl mx-auto px-6">
        <div class="text-center mb-14">
            <p class="text-xs font-semibold text-brand-800 uppercase tracking-wider mb-2">El problema</p>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-neutral-900 mb-4">
                Revisar el SEACE manualmente es una pesadilla
            </h2>
            <p class="text-neutral-500 max-w-xl mx-auto">
                Miles de procesos nuevos cada día, interfaces lentas, documentos extensos y oportunidades que se pierden en minutos.
            </p>
        </div>

        <div class="grid sm:grid-cols-3 gap-6">
            <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-6 text-center">
                <div class="w-12 h-12 bg-red-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="text-sm font-bold text-neutral-900 mb-2">Horas perdidas</h3>
                <p class="text-xs text-neutral-500 leading-relaxed">Revisar manualmente el portal SEACE puede consumir 2-3 horas diarias buscando oportunidades relevantes.</p>
            </div>
            <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-6 text-center">
                <div class="w-12 h-12 bg-orange-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h3 class="text-sm font-bold text-neutral-900 mb-2">TDR extensos</h3>
                <p class="text-xs text-neutral-500 leading-relaxed">Cada proceso tiene documentos de 20-50 páginas que necesitas leer para evaluar si aplica a tu empresa.</p>
            </div>
            <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-6 text-center">
                <div class="w-12 h-12 bg-yellow-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="text-sm font-bold text-neutral-900 mb-2">Oportunidades perdidas</h3>
                <p class="text-xs text-neutral-500 leading-relaxed">Los procesos tienen ventanas cortas de cotización. Si no los ves a tiempo, pierdes la oportunidad.</p>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════
     FEATURES
═══════════════════════════════════════════════════════════════ --}}
<section class="bg-white py-20">
    <div class="max-w-5xl mx-auto px-6">
        <div class="text-center mb-14">
            <p class="text-xs font-semibold text-brand-800 uppercase tracking-wider mb-2">La solución</p>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-neutral-900 mb-4">
                Vigilante SEACE trabaja por ti
            </h2>
            <p class="text-neutral-500 max-w-xl mx-auto">
                Automatiza el monitoreo, análisis y priorización de licitaciones con inteligencia artificial.
            </p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @php
            $features = [
                [
                    'icon' => '<svg class="w-7 h-7 text-brand-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>',
                    'title' => 'Buscador Inteligente',
                    'desc' => 'Busca por palabra clave, departamento, entidad, objeto o estado. Resultados en tiempo real desde el SEACE.',
                    'premium' => false,
                ],
                [
                    'icon' => '<svg class="w-7 h-7 text-brand-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>',
                    'title' => 'Análisis TDR con IA',
                    'desc' => 'La IA lee documentos completos y extrae requisitos, penalidades, reglas y montos en segundos.',
                    'premium' => true,
                ],
                [
                    'icon' => '<svg class="w-7 h-7 text-brand-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>',
                    'title' => 'Score de Compatibilidad',
                    'desc' => 'Puntaje de 0 a 10 que mide qué tan compatible es cada proceso con tu empresa.',
                    'premium' => true,
                ],
                [
                    'icon' => '<svg class="w-7 h-7 text-brand-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>',
                    'title' => 'Alertas Automáticas',
                    'desc' => 'Notificaciones en Telegram, WhatsApp o Email cuando hay procesos que coinciden con tus keywords.',
                    'premium' => true,
                ],
                [
                    'icon' => '<svg class="w-7 h-7 text-brand-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
                    'title' => 'Calendario de Seguimientos',
                    'desc' => 'Agenda procesos con indicadores de urgencia por colores para nunca perder una fecha límite.',
                    'premium' => true,
                ],
                [
                    'icon' => '<svg class="w-7 h-7 text-brand-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
                    'title' => 'Dashboard Analítico',
                    'desc' => 'Estadísticas y gráficos de las contrataciones del Estado: por estado, objeto, entidad y departamento.',
                    'premium' => false,
                ],
            ];
            @endphp

            @foreach ($features as $feat)
            <div class="bg-neutral-50 rounded-3xl p-6 border border-neutral-100 hover:shadow-soft transition-shadow">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-12 h-12 bg-brand-800/10 rounded-2xl flex items-center justify-center">
                        {!! $feat['icon'] !!}
                    </div>
                    @if ($feat['premium'])
                        <span class="text-[10px] font-bold bg-brand-800 text-white px-2.5 py-1 rounded-full">Premium</span>
                    @else
                        <span class="text-[10px] font-bold bg-neutral-200 text-neutral-600 px-2.5 py-1 rounded-full">Gratis</span>
                    @endif
                </div>
                <h3 class="text-sm font-bold text-neutral-900 mb-2">{{ $feat['title'] }}</h3>
                <p class="text-xs text-neutral-500 leading-relaxed">{{ $feat['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════
     HOW IT WORKS
═══════════════════════════════════════════════════════════════ --}}
<section class="bg-neutral-50 py-20">
    <div class="max-w-4xl mx-auto px-6">
        <div class="text-center mb-14">
            <p class="text-xs font-semibold text-brand-800 uppercase tracking-wider mb-2">Súper fácil</p>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-neutral-900">
                ¿Cómo funciona?
            </h2>
        </div>

        <div class="space-y-8">
            @foreach ([
                ['Crea tu cuenta gratis', 'Regístrate en menos de 1 minuto. Solo necesitas un correo electrónico. Sin tarjeta de crédito.'],
                ['Busca y explora procesos', 'Usa el buscador público para encontrar licitaciones por palabra clave, departamento, entidad o tipo de contrato.'],
                ['Configura tus alertas', 'Activa Premium, agrega tus palabras clave y conecta tu Telegram o WhatsApp para recibir notificaciones automáticas.'],
                ['Deja que la IA trabaje', 'Analiza TDR en segundos, obtén scores de compatibilidad y prioriza las mejores oportunidades para tu empresa.'],
            ] as $i => $step)
            <div class="flex items-start gap-6">
                <div class="flex-shrink-0">
                    <span class="bg-brand-800 text-white text-lg font-bold w-12 h-12 rounded-full flex items-center justify-center">{{ $i + 1 }}</span>
                </div>
                <div class="bg-white rounded-2xl shadow-soft border border-neutral-100 p-6 flex-1">
                    <h3 class="text-base font-bold text-neutral-900 mb-1">{{ $step[0] }}</h3>
                    <p class="text-sm text-neutral-500 leading-relaxed">{{ $step[1] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════
     PRICING PREVIEW
═══════════════════════════════════════════════════════════════ --}}
<section class="bg-white py-20">
    <div class="max-w-4xl mx-auto px-6">
        <div class="text-center mb-14">
            <p class="text-xs font-semibold text-brand-800 uppercase tracking-wider mb-2">Planes simples</p>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-neutral-900 mb-4">
                Empieza gratis, crece cuando quieras
            </h2>
        </div>

        <div class="grid sm:grid-cols-2 gap-6 max-w-2xl mx-auto">
            <div class="rounded-3xl border border-neutral-200 p-8">
                <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-2">Gratuito</p>
                <p class="text-4xl font-extrabold text-neutral-900 mb-1">S/ 0</p>
                <p class="text-xs text-neutral-400 mb-6">para siempre</p>
                <ul class="text-sm text-neutral-600 space-y-3 mb-8">
                    <li class="flex items-center gap-2"><span class="text-brand-800">✓</span> Buscador público ilimitado</li>
                    <li class="flex items-center gap-2"><span class="text-brand-800">✓</span> Descarga de documentos TDR</li>
                    <li class="flex items-center gap-2"><span class="text-brand-800">✓</span> Dashboard de estadísticas</li>
                    <li class="flex items-center gap-2"><span class="text-brand-800">✓</span> Detalle de cada proceso</li>
                </ul>
                <a href="{{ route('register') }}" class="block w-full text-center py-3 text-sm font-bold border-2 border-neutral-200 text-neutral-700 rounded-full hover:bg-neutral-50 transition-colors">
                    Crear Cuenta Gratis
                </a>
            </div>

            <div class="rounded-3xl border-2 border-brand-800 p-8 relative">
                <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-brand-800 text-white text-xs font-bold px-4 py-1 rounded-full">Recomendado</span>
                <p class="text-xs font-semibold text-brand-800 uppercase tracking-wider mb-2">Premium</p>
                <p class="text-4xl font-extrabold text-neutral-900 mb-1">S/ 49<span class="text-lg text-neutral-400 font-normal">/mes</span></p>
                <p class="text-xs text-neutral-400 mb-6">15 días de prueba gratis</p>
                <ul class="text-sm text-neutral-600 space-y-3 mb-8">
                    <li class="flex items-center gap-2"><span class="text-brand-800">✓</span> Todo lo del plan gratuito</li>
                    <li class="flex items-center gap-2"><span class="text-brand-800">✓</span> Análisis TDR con IA</li>
                    <li class="flex items-center gap-2"><span class="text-brand-800">✓</span> Score de compatibilidad</li>
                    <li class="flex items-center gap-2"><span class="text-brand-800">✓</span> Alertas Telegram + WhatsApp + Email</li>
                    <li class="flex items-center gap-2"><span class="text-brand-800">✓</span> Calendario de seguimientos</li>
                </ul>
                <a href="{{ route('planes') }}" class="block w-full text-center py-3 text-sm font-bold bg-brand-800 text-white rounded-full hover:bg-brand-900 transition-colors">
                    Ver Todos los Planes →
                </a>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════
     CTA FINAL
═══════════════════════════════════════════════════════════════ --}}
<section class="bg-gradient-to-br from-brand-900 via-brand-800 to-brand-600 py-20 relative overflow-hidden">
    <div class="absolute inset-0 opacity-5">
        <svg class="w-full h-full" viewBox="0 0 1200 400" fill="none">
            <circle cx="900" cy="50" r="300" fill="white"/>
            <circle cx="300" cy="350" r="200" fill="white"/>
        </svg>
    </div>
    <div class="relative z-10 max-w-3xl mx-auto px-6 text-center">
        <h2 class="text-3xl sm:text-4xl font-extrabold text-white mb-4">
            Empieza a ganar licitaciones hoy
        </h2>
        <p class="text-primary-200 text-base sm:text-lg mb-10 max-w-xl mx-auto leading-relaxed">
            Únete a las empresas que ya usan inteligencia artificial para encontrar las mejores oportunidades en el Estado peruano.
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 bg-white text-brand-800 font-bold text-base px-8 py-3.5 rounded-full hover:bg-neutral-50 transition-colors shadow-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Crear Cuenta Gratis
            </a>
            <a href="{{ route('manual') }}" class="inline-flex items-center gap-2 text-white/80 font-medium text-sm hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                Ver el Manual
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>
</section>

@endsection
