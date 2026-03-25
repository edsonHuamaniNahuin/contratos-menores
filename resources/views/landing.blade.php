@extends('layouts.public')

@section('title', 'Software de Licitaciones Perú — Alertas SEACE, Analizador TDR con IA | Licitaciones MYPe')
@section('meta_description', 'Software de monitoreo de licitaciones del SEACE para MYPEs en Perú. Alertas automáticas por Telegram y WhatsApp, análisis de TDR con inteligencia artificial, score de compatibilidad. Contrataciones menores hasta 8 UIT — Ley 32069.')

@push('head')
{{-- Schema.org SoftwareApplication --}}
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "SoftwareApplication",
    "name": "Licitaciones MYPe — Vigilante SEACE",
    "applicationCategory": "BusinessApplication",
    "operatingSystem": "Web",
    "description": "Software de monitoreo de licitaciones del SEACE para MYPEs en Perú. Alertas automáticas, análisis de TDR con IA y score de compatibilidad.",
    "url": "{{ config('app.url') }}",
    "offers": [
        {
            "@@type": "Offer",
            "price": "0",
            "priceCurrency": "PEN",
            "name": "Plan Gratuito",
            "description": "Buscador público ilimitado, dashboard de estadísticas"
        },
        {
            "@@type": "Offer",
            "price": "49",
            "priceCurrency": "PEN",
            "name": "Plan Premium",
            "description": "Análisis TDR con IA, alertas Telegram/WhatsApp/Email, score de compatibilidad"
        }
    ],
    "featureList": [
        "Monitoreo automatizado del SEACE 24/7",
        "Alertas por Telegram, WhatsApp y Email",
        "Análisis de TDR con inteligencia artificial",
        "Score de compatibilidad empresa-proceso",
        "Detección de direccionamiento en licitaciones",
        "Buscador público por departamento y entidad"
    ]
}
</script>
{{-- Schema.org FAQPage --}}
@verbatim
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [
        {
            "@type": "Question",
            "name": "¿Qué son las contrataciones menores a 8 UIT y cómo participar?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Las contrataciones menores a 8 UIT son procesos de compra del Estado peruano por montos hasta S/ 41,600 (2025). No requieren proceso de selección formal, pero la entidad debe publicarlas en el SEACE. Las MYPEs pueden participar si están inscritas en el RNP (Registro Nacional de Proveedores), son hábiles tributariamente y presentan su cotización dentro del plazo."
            }
        },
        {
            "@type": "Question",
            "name": "¿Cómo funciona el analizador de TDR con inteligencia artificial?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Nuestro analizador de TDR emplea IA para leer documentos de 20-50 páginas en segundos. Extrae requisitos técnicos, experiencia exigida, penalidades, plazos, montos y un score de compatibilidad con tu empresa. También detecta señales de direccionamiento mediante análisis forense anticorrupción."
            }
        },
        {
            "@type": "Question",
            "name": "¿Qué es el SEACE y cómo se usa para encontrar licitaciones?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "El SEACE (Sistema Electrónico de Contrataciones del Estado) es la plataforma oficial del OSCE donde las entidades públicas publican sus convocatorias. Licitaciones MYPe monitorea el SEACE automáticamente 24/7 y te envía alertas cuando hay procesos relevantes para tu empresa."
            }
        },
        {
            "@type": "Question",
            "name": "¿Necesito estar inscrito en el RNP para licitar con el Estado?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Sí. El RNP (Registro Nacional de Proveedores) es requisito obligatorio para participar en contrataciones del Estado peruano. Puedes inscribirte como proveedor de bienes, servicios, consultor de obras o ejecutor de obras a través del portal del OSCE."
            }
        },
        {
            "@type": "Question",
            "name": "¿La Ley 32069 aplica para empresas MYPE?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "La Ley 32069 actualiza la Ley de Contrataciones del Estado y establece disposiciones que favorecen a las MYPEs, incluyendo el fomento de la participación de pequeñas y medianas empresas en las compras públicas, simplificación de requisitos y reserva de cuotas en determinados procesos."
            }
        }
    ]
}
</script>
@endverbatim
@endpush

@section('content')

{{-- ═══════════════════════════════════════════════════════════════
     HERO — Keyword: software licitaciones perú, alertas SEACE
═══════════════════════════════════════════════════════════════ --}}
<section class="relative bg-gradient-to-br from-brand-900 via-brand-800 to-brand-600 overflow-hidden">
    <div class="absolute inset-0 opacity-10" aria-hidden="true">
        <svg class="w-full h-full" viewBox="0 0 1200 600" fill="none" width="1200" height="600">
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
            Software de licitaciones<br>
            <span class="text-secondary-400">para MYPEs en Perú</span>
        </h1>
        <p class="text-white/70 text-lg sm:text-xl max-w-2xl mx-auto mb-10 leading-relaxed">
            Monitorea licitaciones del SEACE con inteligencia artificial. Recibe <strong class="text-white">alertas automáticas por Telegram y WhatsApp</strong>, analiza TDR en segundos y encuentra contrataciones menores hasta 8 UIT compatibles con tu empresa.
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
        <p class="text-white/70 text-sm font-medium mt-6">Obtén 15 días de prueba Premium al registrarte</p>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════
     STATS BAR — Cifras de autoridad (E-E-A-T)
═══════════════════════════════════════════════════════════════ --}}
<section class="bg-white">
    <div class="max-w-6xl mx-auto px-6 py-10">
        <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 px-8 py-8">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-8 text-center">
                <div>
                    <p class="text-2xl sm:text-3xl font-extrabold text-neutral-900">29,000+</p>
                    <p class="text-sm text-neutral-600 mt-1">Procesos monitoreados</p>
                </div>
                <div>
                    <p class="text-2xl sm:text-3xl font-extrabold text-neutral-900">25</p>
                    <p class="text-sm text-neutral-600 mt-1">Departamentos cubiertos</p>
                </div>
                <div>
                    <p class="text-2xl sm:text-3xl font-extrabold text-neutral-900">24/7</p>
                    <p class="text-sm text-neutral-600 mt-1">Monitoreo continuo</p>
                </div>
                <div>
                    <p class="text-2xl sm:text-3xl font-extrabold text-neutral-900">&lt; 5 min</p>
                    <p class="text-sm text-neutral-600 mt-1">Alerta tras publicación</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════
     PROBLEMA → SOLUCIÓN — Keywords: revisar SEACE, licitaciones MYPE
═══════════════════════════════════════════════════════════════ --}}
<section class="bg-neutral-50 py-20">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-12">
            <p class="text-xs font-bold text-brand-800 uppercase tracking-widest mb-3">El problema de las MYPEs</p>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-neutral-900 mb-4">
                Revisar el SEACE manualmente es una pesadilla
            </h2>
            <p class="text-base text-neutral-600 max-w-2xl mx-auto leading-relaxed">
                Miles de contrataciones nuevas cada día en el portal del OSCE, interfaces lentas, documentos TDR extensos y oportunidades de licitación que se pierden en minutos.
            </p>
        </div>

        <div class="grid sm:grid-cols-3 gap-8">
            <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-8 text-center">
                <div class="w-14 h-14 bg-brand-800/10 rounded-2xl flex items-center justify-center mx-auto mb-5">
                    <svg class="w-7 h-7 text-brand-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="text-base font-bold text-neutral-900 mb-2">2-3 horas diarias perdidas</h3>
                <p class="text-sm text-neutral-600 leading-relaxed">Revisar manualmente cada licitación en el SEACE consume horas buscando contrataciones menores relevantes para tu MYPE.</p>
            </div>
            <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-8 text-center">
                <div class="w-14 h-14 bg-brand-800/10 rounded-2xl flex items-center justify-center mx-auto mb-5">
                    <svg class="w-7 h-7 text-brand-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h3 class="text-base font-bold text-neutral-900 mb-2">TDR de 20-50 páginas</h3>
                <p class="text-sm text-neutral-600 leading-relaxed">Cada proceso tiene Términos de Referencia extensos que necesitas leer para evaluar requisitos, experiencia y penalidades.</p>
            </div>
            <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-8 text-center">
                <div class="w-14 h-14 bg-brand-800/10 rounded-2xl flex items-center justify-center mx-auto mb-5">
                    <svg class="w-7 h-7 text-brand-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="text-base font-bold text-neutral-900 mb-2">Cotizaciones que vencen rápido</h3>
                <p class="text-sm text-neutral-600 leading-relaxed">Las contrataciones menores hasta 8 UIT tienen ventanas cortas. Si no las ves a tiempo, pierdes la oportunidad de cotizar.</p>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════
     FEATURES — Keywords: alertas licitaciones, analizador TDR IA, bot licitaciones
═══════════════════════════════════════════════════════════════ --}}
<section class="bg-white py-20">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-12">
            <p class="text-xs font-bold text-brand-800 uppercase tracking-widest mb-3">La solución para tu empresa</p>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-neutral-900 mb-4">
                Alertas de licitaciones y análisis TDR con IA
            </h2>
            <p class="text-base text-neutral-600 max-w-2xl mx-auto leading-relaxed">
                Automatiza el monitoreo del SEACE, analiza Términos de Referencia en segundos y prioriza las mejores oportunidades de contratación pública.
            </p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
            @php
            $features = [
                [
                    'icon' => '<svg class="w-7 h-7 text-brand-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>',
                    'title' => 'Buscador de licitaciones SEACE',
                    'desc' => 'Busca contrataciones por palabra clave, departamento, entidad, objeto o estado. Resultados en tiempo real desde el portal SEACE del OSCE.',
                    'premium' => false,
                ],
                [
                    'icon' => '<svg class="w-7 h-7 text-brand-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>',
                    'title' => 'Analizador de TDR con IA',
                    'desc' => 'La inteligencia artificial lee documentos TDR completos (20-50 páginas) y extrae requisitos técnicos, experiencia, penalidades, plazos y montos en segundos.',
                    'premium' => true,
                ],
                [
                    'icon' => '<svg class="w-7 h-7 text-brand-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>',
                    'title' => 'Score de compatibilidad',
                    'desc' => 'Puntaje automático de 0 a 10 que mide qué tan compatible es cada licitación con el perfil y experiencia de tu empresa MYPE.',
                    'premium' => true,
                ],
                [
                    'icon' => '<svg class="w-7 h-7 text-brand-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>',
                    'title' => 'Bot de alertas Telegram y WhatsApp',
                    'desc' => 'Recibe notificaciones automáticas por Telegram, WhatsApp o Email cuando se publican licitaciones que coinciden con tus palabras clave.',
                    'premium' => true,
                ],
                [
                    'icon' => '<svg class="w-7 h-7 text-brand-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>',
                    'title' => 'Detección de direccionamiento',
                    'desc' => 'Análisis forense con IA que detecta señales de direccionamiento ilegal en TDR: requisitos excesivos, experiencia desproporcionada y más.',
                    'premium' => true,
                ],
                [
                    'icon' => '<svg class="w-7 h-7 text-brand-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
                    'title' => 'Dashboard analítico de contrataciones',
                    'desc' => 'Estadísticas y gráficos de las contrataciones del Estado: distribución por departamento, entidad, tipo de objeto y tendencias mensuales.',
                    'premium' => false,
                ],
            ];
            @endphp

            @foreach ($features as $feat)
            <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-8 hover:shadow-lg transition-shadow">
                <div class="flex items-start justify-between mb-5">
                    <div class="w-14 h-14 bg-brand-800/10 rounded-2xl flex items-center justify-center">
                        {!! $feat['icon'] !!}
                    </div>
                    @if ($feat['premium'])
                        <span class="text-[10px] font-bold bg-brand-800 text-white px-2.5 py-1 rounded-full">Premium</span>
                    @else
                        <span class="text-[10px] font-bold bg-neutral-100 text-neutral-600 px-2.5 py-1 rounded-full">Gratis</span>
                    @endif
                </div>
                <h3 class="text-base font-bold text-neutral-900 mb-2">{{ $feat['title'] }}</h3>
                <p class="text-sm text-neutral-600 leading-relaxed">{{ $feat['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════
     COMPARATIVA — Commercial investigation: software vs consultoría
═══════════════════════════════════════════════════════════════ --}}
<section class="bg-neutral-50 py-20">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-12">
            <p class="text-xs font-bold text-brand-800 uppercase tracking-widest mb-3">Comparativa</p>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-neutral-900 mb-4">
                ¿Software de licitaciones o consultoría manual?
            </h2>
            <p class="text-base text-neutral-600 max-w-2xl mx-auto leading-relaxed">
                Las MYPEs necesitan herramientas accesibles, no consultores costosos. Compara y decide.
            </p>
        </div>

        <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr>
                            <th class="text-left py-4 px-6 bg-neutral-50 font-semibold text-neutral-900">Criterio</th>
                            <th class="text-center py-4 px-6 bg-brand-800 text-white font-semibold">Licitaciones MYPe</th>
                            <th class="text-center py-4 px-6 bg-neutral-50 font-semibold text-neutral-900">Consultoría tradicional</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        <tr>
                            <td class="py-3.5 px-6 text-sm text-neutral-900 font-medium">Costo mensual</td>
                            <td class="py-3.5 px-6 text-center font-semibold text-brand-800">Desde S/ 0 · Premium S/ 49</td>
                            <td class="py-3.5 px-6 text-center text-neutral-600">S/ 500 — S/ 2,000+</td>
                        </tr>
                        <tr>
                            <td class="py-3.5 px-6 text-sm text-neutral-900 font-medium">Monitoreo del SEACE</td>
                            <td class="py-3.5 px-6 text-center"><span class="inline-flex items-center gap-1.5 text-brand-800 font-medium"><svg class="w-4 h-4 text-secondary-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Automático 24/7</span></td>
                            <td class="py-3.5 px-6 text-center text-neutral-600">Horario laboral</td>
                        </tr>
                        <tr>
                            <td class="py-3.5 px-6 text-sm text-neutral-900 font-medium">Análisis de TDR con IA</td>
                            <td class="py-3.5 px-6 text-center"><span class="inline-flex items-center gap-1.5 text-brand-800 font-medium"><svg class="w-4 h-4 text-secondary-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Resumen automático en segundos</span></td>
                            <td class="py-3.5 px-6 text-center text-neutral-600">Manual, horas/días</td>
                        </tr>
                        <tr>
                            <td class="py-3.5 px-6 text-sm text-neutral-900 font-medium">Alertas Telegram y WhatsApp</td>
                            <td class="py-3.5 px-6 text-center"><span class="inline-flex items-center gap-1.5 text-brand-800 font-medium"><svg class="w-4 h-4 text-secondary-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Automáticas por keywords</span></td>
                            <td class="py-3.5 px-6 text-center text-neutral-600">No disponible</td>
                        </tr>
                        <tr>
                            <td class="py-3.5 px-6 text-sm text-neutral-900 font-medium">Notificaciones por correo</td>
                            <td class="py-3.5 px-6 text-center"><span class="inline-flex items-center gap-1.5 text-brand-800 font-medium"><svg class="w-4 h-4 text-secondary-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Resumen + seguimiento directo</span></td>
                            <td class="py-3.5 px-6 text-center text-neutral-600">Email manual</td>
                        </tr>
                        <tr>
                            <td class="py-3.5 px-6 text-sm text-neutral-900 font-medium">Score de compatibilidad</td>
                            <td class="py-3.5 px-6 text-center"><span class="inline-flex items-center gap-1.5 text-brand-800 font-medium"><svg class="w-4 h-4 text-secondary-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Puntaje automático 0-10</span></td>
                            <td class="py-3.5 px-6 text-center text-neutral-600">Evaluación subjetiva</td>
                        </tr>
                        <tr>
                            <td class="py-3.5 px-6 text-sm text-neutral-900 font-medium">Detección de direccionamiento</td>
                            <td class="py-3.5 px-6 text-center"><span class="inline-flex items-center gap-1.5 text-brand-800 font-medium"><svg class="w-4 h-4 text-secondary-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> IA forense anticorrupción</span></td>
                            <td class="py-3.5 px-6 text-center text-neutral-600">No disponible</td>
                        </tr>
                        <tr>
                            <td class="py-3.5 px-6 text-sm text-neutral-900 font-medium">Seguimiento y calendario</td>
                            <td class="py-3.5 px-6 text-center"><span class="inline-flex items-center gap-1.5 text-brand-800 font-medium"><svg class="w-4 h-4 text-secondary-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Fechas y recordatorios</span></td>
                            <td class="py-3.5 px-6 text-center text-neutral-600">Manual en Excel</td>
                        </tr>
                        <tr>
                            <td class="py-3.5 px-6 text-sm text-neutral-900 font-medium">Alertas inteligentes con IA</td>
                            <td class="py-3.5 px-6 text-center"><span class="inline-flex items-center gap-1.5 text-brand-800 font-medium"><svg class="w-4 h-4 text-secondary-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Análisis + score en cada alerta</span></td>
                            <td class="py-3.5 px-6 text-center text-neutral-600">No disponible</td>
                        </tr>
                        <tr>
                            <td class="py-3.5 px-6 text-sm text-neutral-900 font-medium">Cobertura nacional</td>
                            <td class="py-3.5 px-6 text-center"><span class="inline-flex items-center gap-1.5 text-brand-800 font-medium"><svg class="w-4 h-4 text-secondary-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> 25 departamentos</span></td>
                            <td class="py-3.5 px-6 text-center text-neutral-600">1-3 departamentos</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════
     HOW IT WORKS — Keywords: cómo participar licitaciones, RNP, cotizar
═══════════════════════════════════════════════════════════════ --}}
<section class="bg-white py-20">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-12">
            <p class="text-xs font-bold text-brand-800 uppercase tracking-widest mb-3">Súper fácil</p>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-neutral-900">
                ¿Cómo empezar a ganar licitaciones del Estado?
            </h2>
        </div>

        <div class="space-y-6">
            @foreach ([
                ['Crea tu cuenta gratis', 'Regístrate en menos de 1 minuto. Solo necesitas un correo electrónico. Accede al buscador de licitaciones del SEACE de forma inmediata.'],
                ['Busca contrataciones menores y licitaciones', 'Usa el buscador público para encontrar procesos por palabra clave, departamento, entidad o tipo de contrato. Filtra contrataciones menores hasta 8 UIT ideales para MYPEs.'],
                ['Configura alertas por Telegram o WhatsApp', 'Activa Premium, agrega tus palabras clave y conecta tu bot de Telegram o WhatsApp para recibir notificaciones cuando se publiquen licitaciones relevantes.'],
                ['Deja que la IA analice los TDR por ti', 'El analizador de TDR con inteligencia artificial lee documentos completos, obtén scores de compatibilidad y prioriza las mejores oportunidades para tu empresa.'],
            ] as $i => $step)
            <div class="flex items-start gap-5">
                <div class="flex-shrink-0">
                    <span class="bg-brand-800 text-white text-lg font-bold w-12 h-12 rounded-full flex items-center justify-center shadow-soft">{{ $i + 1 }}</span>
                </div>
                <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-6 flex-1">
                    <h3 class="text-base font-bold text-neutral-900 mb-1">{{ $step[0] }}</h3>
                    <p class="text-sm text-neutral-600 leading-relaxed">{{ $step[1] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════
     TIPOS DE CONTRATACIÓN — Informational: Ley 32069, contrataciones menores
═══════════════════════════════════════════════════════════════ --}}
<section class="bg-neutral-50 py-20">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-12">
            <p class="text-xs font-bold text-brand-800 uppercase tracking-widest mb-3">Contrataciones del Estado</p>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-neutral-900 mb-4">
                Tipos de licitaciones que puedes ganar como MYPE
            </h2>
            <p class="text-base text-neutral-600 max-w-2xl mx-auto leading-relaxed">
                El Estado peruano ofrece distintas modalidades de contratación. La <strong class="text-neutral-900">Ley 32069</strong> y el OSCE regulan los procesos publicados en el SEACE.
            </p>
        </div>

        <div class="grid sm:grid-cols-2 gap-8">
            <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-8">
                <div class="flex items-center gap-3 mb-4">
                    <span class="bg-secondary-100 text-secondary-600 text-xs font-bold px-3 py-1 rounded-full">Ideal para MYPEs</span>
                </div>
                <h3 class="text-base font-bold text-neutral-900 mb-3">Contrataciones menores a 8 UIT</h3>
                <p class="text-sm text-neutral-600 leading-relaxed mb-4">
                    Procesos por montos hasta <strong class="text-neutral-900">S/ 41,600</strong> (2025) que no requieren proceso de selección formal. La entidad solicita cotizaciones y adjudica directamente. Requisitos: RNP vigente, habilidad tributaria y cotización dentro del plazo.
                </p>
                <ul class="text-sm text-neutral-600 space-y-2">
                    <li class="flex items-center gap-2.5"><svg class="w-4 h-4 text-secondary-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Sin proceso de selección</li>
                    <li class="flex items-center gap-2.5"><svg class="w-4 h-4 text-secondary-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Adjudicación directa</li>
                    <li class="flex items-center gap-2.5"><svg class="w-4 h-4 text-secondary-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Plazos cortos de cotización</li>
                </ul>
            </div>

            <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-8">
                <h3 class="text-base font-bold text-neutral-900 mb-3">Adjudicación Simplificada</h3>
                <p class="text-sm text-neutral-600 leading-relaxed mb-4">
                    Para bienes y servicios de 8 UIT a 400 UIT. Proceso con etapas de registro, presentación de ofertas y evaluación. Publicado en el SEACE con cronograma definido.
                </p>
                <ul class="text-sm text-neutral-600 space-y-2">
                    <li class="flex items-center gap-2.5"><svg class="w-4 h-4 text-brand-800 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Proceso formal en SEACE</li>
                    <li class="flex items-center gap-2.5"><svg class="w-4 h-4 text-brand-800 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Evaluación técnica y económica</li>
                    <li class="flex items-center gap-2.5"><svg class="w-4 h-4 text-brand-800 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Mayor valor de contrato</li>
                </ul>
            </div>

            <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-8">
                <h3 class="text-base font-bold text-neutral-900 mb-3">Licitación Pública</h3>
                <p class="text-sm text-neutral-600 leading-relaxed mb-4">
                    Para bienes y obras de mayor cuantía. Proceso completo con bases, absolución de consultas, evaluación y buena pro. Requiere experiencia demostrable y garantías.
                </p>
                <ul class="text-sm text-neutral-600 space-y-2">
                    <li class="flex items-center gap-2.5"><svg class="w-4 h-4 text-brand-800 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Mayor cuantía</li>
                    <li class="flex items-center gap-2.5"><svg class="w-4 h-4 text-brand-800 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Proceso más riguroso</li>
                    <li class="flex items-center gap-2.5"><svg class="w-4 h-4 text-brand-800 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Requiere garantías formales</li>
                </ul>
            </div>

            <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-8">
                <h3 class="text-base font-bold text-neutral-900 mb-3">Concurso Público de Servicios</h3>
                <p class="text-sm text-neutral-600 leading-relaxed mb-4">
                    Para servicios y consultorías de mayor cuantía. Incluye consultorías de obra y servicios especializados. Evaluación técnica detallada de propuestas.
                </p>
                <ul class="text-sm text-neutral-600 space-y-2">
                    <li class="flex items-center gap-2.5"><svg class="w-4 h-4 text-brand-800 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Servicios especializados</li>
                    <li class="flex items-center gap-2.5"><svg class="w-4 h-4 text-brand-800 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Consultorías de obra</li>
                    <li class="flex items-center gap-2.5"><svg class="w-4 h-4 text-brand-800 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Peso técnico relevante</li>
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════
     PRICING PREVIEW — Transactional: SaaS licitaciones precio
═══════════════════════════════════════════════════════════════ --}}
<section class="bg-white py-20">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-12">
            <p class="text-xs font-bold text-brand-800 uppercase tracking-widest mb-3">Planes accesibles para MYPEs</p>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-neutral-900 mb-4">
                Empieza gratis, crece cuando quieras
            </h2>
        </div>

        <div class="grid sm:grid-cols-2 gap-8 max-w-2xl mx-auto">
            <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-8">
                <p class="text-xs font-bold text-neutral-600 uppercase tracking-widest mb-2">Gratuito</p>
                <p class="text-4xl font-extrabold text-neutral-900 mb-1">S/ 0</p>
                <p class="text-sm text-neutral-600 mb-6">para siempre</p>
                <ul class="text-sm text-neutral-600 space-y-3 mb-8">
                    <li class="flex items-center gap-2.5"><svg class="w-4 h-4 text-secondary-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Buscador de licitaciones ilimitado</li>
                    <li class="flex items-center gap-2.5"><svg class="w-4 h-4 text-secondary-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Descarga de documentos TDR</li>
                    <li class="flex items-center gap-2.5"><svg class="w-4 h-4 text-secondary-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Dashboard de estadísticas</li>
                    <li class="flex items-center gap-2.5"><svg class="w-4 h-4 text-secondary-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Detalle de cada proceso SEACE</li>
                </ul>
                <a href="{{ route('register') }}" class="block w-full text-center py-3 text-sm font-bold border-2 border-neutral-200 text-neutral-900 rounded-full hover:bg-neutral-50 transition-colors">
                    Crear Cuenta Gratis
                </a>
            </div>

            <div class="bg-white rounded-3xl shadow-soft border-2 border-brand-800 p-8 relative">
                <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-brand-800 text-white text-xs font-bold px-4 py-1 rounded-full">Recomendado</span>
                <p class="text-xs font-bold text-brand-800 uppercase tracking-widest mb-2">Premium</p>
                <p class="text-4xl font-extrabold text-neutral-900 mb-1">S/ 49<span class="text-lg text-neutral-500 font-normal">/mes</span></p>
                <p class="text-sm text-neutral-600 mb-6">15 días de prueba al registrarte</p>
                <ul class="text-sm text-neutral-600 space-y-3 mb-8">
                    <li class="flex items-center gap-2.5"><svg class="w-4 h-4 text-secondary-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Todo lo del plan gratuito</li>
                    <li class="flex items-center gap-2.5"><svg class="w-4 h-4 text-secondary-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Analizador de TDR con IA</li>
                    <li class="flex items-center gap-2.5"><svg class="w-4 h-4 text-secondary-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Score de compatibilidad</li>
                    <li class="flex items-center gap-2.5"><svg class="w-4 h-4 text-secondary-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Alertas Telegram + WhatsApp + Email</li>
                    <li class="flex items-center gap-2.5"><svg class="w-4 h-4 text-secondary-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Detección de direccionamiento</li>
                    <li class="flex items-center gap-2.5"><svg class="w-4 h-4 text-secondary-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Seguimiento y calendario</li>
                </ul>
                <a href="{{ route('planes') }}" class="block w-full text-center py-3 text-sm font-bold bg-brand-800 text-white rounded-full hover:bg-brand-900 transition-colors">
                    Ver Todos los Planes →
                </a>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════
     FAQ — GEO optimized: preguntas frecuentes, Ley 32069, RNP, 8 UIT
═══════════════════════════════════════════════════════════════ --}}
<section class="bg-neutral-50 py-20">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-12">
            <p class="text-xs font-bold text-brand-800 uppercase tracking-widest mb-3">Preguntas frecuentes</p>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-neutral-900 mb-4">
                Lo que las MYPEs preguntan sobre licitaciones
            </h2>
        </div>

        <div class="space-y-4" x-data="{ open: null }">
            @foreach ([
                [
                    'q' => '¿Qué son las contrataciones menores a 8 UIT y cómo participar?',
                    'a' => 'Las contrataciones menores a 8 UIT son procesos de compra del Estado peruano por montos hasta S/ 41,600 (2025). No requieren proceso de selección formal, pero la entidad debe publicarlas en el SEACE. Las MYPEs pueden participar si están inscritas en el <strong>RNP</strong> (Registro Nacional de Proveedores), son hábiles tributariamente y presentan su cotización dentro del plazo.',
                ],
                [
                    'q' => '¿Cómo funciona el analizador de TDR con inteligencia artificial?',
                    'a' => 'Nuestro analizador de TDR emplea IA (modelos de lenguaje avanzados) para leer documentos de 20-50 páginas en segundos. Extrae requisitos técnicos, experiencia exigida, penalidades, plazos, montos y un <strong>score de compatibilidad</strong> con tu empresa. También detecta señales de <strong>direccionamiento</strong> mediante análisis forense anticorrupción.',
                ],
                [
                    'q' => '¿Qué es el SEACE y cómo se usa para encontrar licitaciones?',
                    'a' => 'El <strong>SEACE</strong> (Sistema Electrónico de Contrataciones del Estado) es la plataforma oficial del <strong>OSCE</strong> donde las entidades públicas publican sus convocatorias, bases, TDR y resultados. Licitaciones MYPe monitorea el SEACE automáticamente 24/7 y te envía alertas cuando hay procesos relevantes para tu empresa.',
                ],
                [
                    'q' => '¿Necesito estar inscrito en el RNP para licitar con el Estado?',
                    'a' => 'Sí. El <strong>RNP</strong> (Registro Nacional de Proveedores) administrado por el OSCE es requisito obligatorio para participar en contrataciones del Estado peruano, incluyendo contrataciones menores a 8 UIT. Puedes inscribirte como proveedor de bienes, servicios, consultor de obras o ejecutor de obras.',
                ],
                [
                    'q' => '¿La Ley 32069 aplica para empresas MYPE?',
                    'a' => 'Sí. La <strong>Ley 32069</strong> actualiza la Ley de Contrataciones del Estado y establece disposiciones que favorecen a las MYPEs: fomento de la participación de pequeñas y medianas empresas, simplificación de requisitos y reserva de cuotas en determinados procesos de contratación pública.',
                ],
                [
                    'q' => '¿Qué diferencia hay entre el buscador gratuito y el plan Premium?',
                    'a' => 'El <strong>buscador gratuito</strong> te permite buscar licitaciones del SEACE sin límite y ver el detalle de cada proceso. El plan <strong>Premium</strong> (S/ 49/mes) agrega: análisis automático de TDR con IA, alertas por Telegram/WhatsApp/Email, score de compatibilidad, detección de direccionamiento y calendario de seguimientos.',
                ],
            ] as $i => $faq)
            <div class="bg-white rounded-2xl border border-neutral-100 shadow-soft overflow-hidden">
                <button
                    @click="open === {{ $i }} ? open = null : open = {{ $i }}"
                    class="w-full flex items-center justify-between px-6 py-4 text-left"
                >
                    <span class="text-sm font-bold text-neutral-900 pr-4">{{ $faq['q'] }}</span>
                    <svg class="w-5 h-5 text-neutral-400 flex-shrink-0 transition-transform" :class="open === {{ $i }} && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === {{ $i }}" x-collapse>
                    <div class="px-6 pb-4 text-sm text-neutral-600 leading-relaxed">
                        {!! $faq['a'] !!}
                    </div>
                </div>
            </div>
            @endforeach
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
    <div class="relative z-10 max-w-6xl mx-auto px-6 text-center">
        <h2 class="text-3xl sm:text-4xl font-extrabold text-white mb-4">
            Empieza a ganar licitaciones del Estado hoy
        </h2>
        <p class="text-white/70 text-base sm:text-lg mb-10 max-w-2xl mx-auto leading-relaxed">
            Únete a las MYPEs que ya usan inteligencia artificial para encontrar contrataciones menores, analizar TDR y cotizar más rápido en el SEACE.
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
