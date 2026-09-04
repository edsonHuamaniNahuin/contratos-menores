<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Alertas de licitaciones públicas para contratistas — Vigilante SEACE</title>
    <meta name="description" content="Licitaciones del SEACE filtradas para tu empresa, el mismo día de publicación. Contratistas de obras, energía y servicios ven primero los procesos de su rubro. Agenda una reunión para ver el sistema en vivo.">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:title" content="Alertas de licitaciones públicas para contratistas — Vigilante SEACE">
    <meta property="og:description" content="Licitaciones del SEACE filtradas para tu empresa, el mismo día de publicación. Agenda una reunión para ver el sistema en vivo.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="es_PE">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @unless(app()->environment('production'))
        <meta name="robots" content="noindex, nofollow">
    @endunless
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,400;1,9..144,500&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap">
    @vite(['resources/css/app.css'])
    @if(app()->environment('production'))
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-4PRW1QCW48');
    </script>
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-4PRW1QCW48"></script>
    @endif
    <style>
        :root {
            --ink: #16211f;
            --paper: #f6f3ec;
            --paper-2: #efe9dd;
            --teal: #0e5a54;
            --teal-deep: #07352f;
            --mint: #9fdfc9;
            --mint-soft: #d8efe6;
            --line: #ddd5c6;
        }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--paper);
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
        }
        .font-display { font-family: 'Fraunces', Georgia, serif; }
        .font-mono2 { font-family: 'JetBrains Mono', ui-monospace, monospace; }
        .kicker {
            font-size: .72rem;
            letter-spacing: .18em;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--teal);
        }
        .kicker-light { color: var(--mint); }
        .btn-wa {
            display: inline-flex; align-items: center; justify-content: center; gap: .6rem;
            background: #1faa57; color: #fff; font-weight: 600;
            border-radius: 999px; padding: 1rem 1.75rem; font-size: 1rem;
            box-shadow: 0 10px 30px -10px rgba(31, 170, 87, .55);
            transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .btn-wa:hover { background: #1c9c4f; transform: translateY(-1px); box-shadow: 0 16px 36px -12px rgba(31, 170, 87, .6); }
        .btn-ghost {
            display: inline-flex; align-items: center; justify-content: center;
            border: 1px solid var(--line); color: var(--ink);
            border-radius: 999px; padding: 1rem 1.75rem; font-size: 1rem; font-weight: 500;
            transition: border-color .15s ease, background .15s ease;
        }
        .btn-ghost:hover { border-color: var(--teal); background: #fff; }
        .check-mark { color: var(--teal); font-weight: 700; }
        .check-mark-light { color: var(--mint); font-weight: 700; }
        .step-num {
            font-family: 'Fraunces', Georgia, serif;
            font-size: 3.4rem; line-height: 1; color: transparent;
            -webkit-text-stroke: 1.2px var(--teal);
        }
        .faq-answer { max-height: 0; overflow: hidden; transition: max-height .25s ease; }
        details[open] .faq-answer { max-height: 400px; }
        details summary::-webkit-details-marker { display: none; }
        .wa-tick { color: #53bdeb; }
        ::selection { background: #cfe8e2; }
        .row-process:hover { background: #fbf8f2; }
        .estado-chip {
            font-family: 'JetBrains Mono', monospace; font-size: .65rem; font-weight: 600;
            letter-spacing: .06em; text-transform: uppercase;
        }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            * { transition: none !important; }
        }
    </style>
</head>
<body>

@php
    $waNumber = '51918874873';
    $waLink = 'https://wa.me/' . $waNumber . '?text=' . rawurlencode('Hola, quiero agendar una reunión para ver Vigilante SEACE Premium en vivo. Mi empresa postula a licitaciones del Estado.');
    $clientes = ['ZAVATEC SA', 'STEEL MAQUINARIAS', 'CORPORACIÓN FAMOD', 'ESTUDIO JURÍDICO P&B'];
    $fechaHoy = now()->format('d/m/Y');
@endphp

{{-- ══ HEADER MINIMAL ══ --}}
<header class="max-w-6xl mx-auto px-6 pt-8 flex items-center justify-between">
    <a href="{{ url('/') }}" class="flex items-baseline gap-2">
        <span class="font-display font-semibold text-[1.35rem] tracking-tight text-[var(--ink)]">Vigilante<span class="text-[var(--teal)]">.</span></span>
        <span class="text-[.68rem] uppercase tracking-[.2em] text-neutral-500">SEACE</span>
    </a>
    <a href="{{ $waLink }}" target="_blank" rel="noopener" class="btn-ghost !py-2.5 !px-5 text-sm hidden sm:inline-flex">
        Agenda una reunión
    </a>
</header>

<main>

{{-- ══ HERO ══ --}}
<section class="relative max-w-6xl mx-auto px-6 pt-12 pb-16">
    <div class="absolute inset-x-6 top-0 h-px bg-gradient-to-r from-transparent via-[var(--line)] to-transparent" aria-hidden="true"></div>
    <div class="grid lg:grid-cols-2 gap-14 items-center">
        <div>
            <p class="kicker mb-5">Para contratistas y proveedores del Estado</p>
            <h1 class="font-display font-medium text-[2.5rem] sm:text-[3.3rem] leading-[1.05] tracking-tight text-[var(--ink)]">
                Esta semana se publicaron<br>
                <span class="text-[var(--teal)]">{{ $stats['semana'] > 0 ? number_format($stats['semana'], 0, ',', '.') : 'cientos de' }}</span>
                convocatorias en el SEACE.<br>
                <em class="italic">¿Cuántas eran de tu rubro?</em>
            </h1>
            <p class="mt-6 text-lg text-neutral-700 leading-relaxed max-w-xl">
                Vigilante SEACE rastrea el SEACE todos los días y te avisa por WhatsApp cuando una entidad pública convoca algo que calza con tu empresa. Llegas a cotizar con días de ventaja, no cuando ya cerró.
            </p>
            <div class="mt-9 flex flex-col sm:flex-row gap-4">
                <a href="{{ $waLink }}" target="_blank" rel="noopener" class="btn-wa">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    Quiero agendar una reunión
                </a>
                <a href="#en-vivo" class="btn-ghost">Ver procesos reales</a>
            </div>
            <p class="mt-5 text-sm text-neutral-600">
                Reunión de 20 minutos, sin compromiso. Te mostramos el sistema con procesos reales de tu rubro.
            </p>
        </div>

        {{-- Tarjeta: conversación WhatsApp con proceso real --}}
        <div class="relative max-w-md mx-auto w-full">
            <div class="absolute -top-5 right-2 sm:-right-4 rotate-2 bg-[var(--teal-deep)] text-white rounded-xl px-4 py-2 shadow-lg text-xs font-semibold z-10">
                Proceso real publicado hoy · {{ $fechaHoy }}
            </div>
            <div class="rounded-[1.6rem] bg-white border border-[var(--line)] shadow-2xl shadow-neutral-900/15 overflow-hidden">
                <div class="bg-[#efe7dd] px-5 py-3.5 flex items-center gap-3 border-b border-[#e2d8c9]">
                    <div class="w-9 h-9 rounded-full bg-[var(--teal)] text-white flex items-center justify-center font-display text-lg">V</div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[14.5px] font-semibold text-neutral-800 leading-tight">Vigilante SEACE</p>
                        <p class="text-[11.5px] text-[var(--teal)] leading-tight font-medium">en línea</p>
                    </div>
                </div>
                <div class="bg-[#e6dfd3] px-4 py-5 space-y-3">
                    <div class="flex justify-end">
                        <div class="bg-[#d9fdd3] rounded-2xl rounded-tr-md px-4 py-2.5 max-w-[82%]">
                            <p class="text-[13.5px] text-neutral-800 leading-snug">¿Publicaron algo de <strong>obras viales</strong> esta semana?</p>
                            <p class="text-[10px] text-neutral-400 text-right mt-1.5">9:04 <span class="wa-tick font-semibold">✓✓</span></p>
                        </div>
                    </div>
                    <div class="flex justify-start">
                        <div class="bg-white rounded-2xl rounded-tl-md px-4 py-3.5 w-full max-w-[90%]">
                            <p class="estado-chip text-[var(--teal)] mb-2">● Nuevo proceso · convocatoria</p>
                            @if ($demo)
                                <p class="font-mono2 text-[10.5px] text-neutral-400 tracking-tight mb-1.5">{{ $demo->nomenclatura }}</p>
                                <p class="text-[15px] font-semibold text-neutral-900 leading-snug mb-1.5">{{ $demo->objeto_contratacion }}</p>
                                <p class="text-[12.5px] text-neutral-600 leading-snug mb-3">
                                    {{ mb_strlen($demo->descripcion_objeto ?? '') > 130 ? mb_substr($demo->descripcion_objeto, 0, 130) . '…' : ($demo->descripcion_objeto ?? '') }}
                                </p>
                            @else
                                <p class="font-mono2 text-[10.5px] text-neutral-400 tracking-tight mb-1.5">LP-SM-MPH-2026-001</p>
                                <p class="text-[15px] font-semibold text-neutral-900 leading-snug mb-1.5">Servicio de mantenimiento vial periódico</p>
                                <p class="text-[12.5px] text-neutral-600 leading-snug mb-3">Mantenimiento de pistas y veredas en zona urbana: reposición de calzada, sardineles y bermas centrales.</p>
                            @endif
                            <div class="flex items-center justify-between border-t border-neutral-100 pt-3">
                                <div class="min-w-0 pr-3">
                                    <p class="text-[10px] text-neutral-400 uppercase tracking-wide mb-0.5">Entidad</p>
                                    <p class="text-[12.5px] font-semibold text-neutral-800 leading-tight truncate">{{ $demo->entidad_nombre ?? 'Municipalidad provincial' }}</p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-[10px] text-neutral-400 uppercase tracking-wide mb-0.5">Monto referencial</p>
                                    <p class="text-[15px] font-bold text-[var(--teal)]">S/ {{ $demo ? number_format((float) $demo->valor_referencial, 0) : '1,250,000' }}</p>
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                <span class="text-[10.5px] bg-[#e8f2ef] text-[var(--teal)] rounded-full px-2.5 py-1 font-semibold">obras civiles</span>
                                <span class="text-[10.5px] bg-[#e8f2ef] text-[var(--teal)] rounded-full px-2.5 py-1 font-semibold">pistas y veredas</span>
                                <span class="text-[10.5px] bg-[#e8f2ef] text-[var(--teal)] rounded-full px-2.5 py-1 font-semibold">carreteras</span>
                            </div>
                            <p class="text-[10px] text-neutral-400 text-right mt-2">9:04 <span class="wa-tick font-semibold">✓✓</span></p>
                        </div>
                    </div>
                    <div class="flex justify-start gap-2">
                        <span class="inline-flex items-center bg-white border border-neutral-200 rounded-full px-3.5 py-1.5 text-[12px] font-medium text-neutral-700">🤖 Analizar TDR</span>
                        <span class="inline-flex items-center bg-white border border-neutral-200 rounded-full px-3.5 py-1.5 text-[12px] font-medium text-neutral-700">📋 Armar cotización</span>
                    </div>
                </div>
            </div>
            <div class="absolute -bottom-4 -left-2 sm:-left-5 -rotate-2 bg-[var(--mint-soft)] border border-[var(--mint)] rounded-xl px-4 py-2.5 text-[12px] font-semibold text-[var(--teal-deep)] z-10">
                &lt; 5 min después de publicarse en el SEACE
            </div>
        </div>
    </div>
</section>

{{-- ══ BANDA CLIENTES ══ --}}
<section class="border-y border-[var(--line)]/70 bg-white/70">
    <div class="max-w-6xl mx-auto px-6 py-8 flex flex-wrap items-center justify-center gap-x-10 gap-y-4">
        <span class="text-[11px] uppercase tracking-[.18em] text-neutral-500 font-semibold">Empresas que ya reciben sus alertas</span>
        @foreach ($clientes as $cliente)
            <span class="font-display text-[1.05rem] text-neutral-500 tracking-tight">{{ $cliente }}</span>
        @endforeach
    </div>
</section>

{{-- ══ EN VIVO: PROCESOS REALES ══ --}}
@if ($procesos->count() > 0)
<section id="en-vivo" class="max-w-6xl mx-auto px-6 py-20">
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6 mb-10">
        <div class="max-w-xl">
            <p class="kicker mb-4">Hoy en el SEACE</p>
            <h2 class="font-display font-medium text-[2.1rem] sm:text-[2.6rem] leading-[1.07] tracking-tight">
                Esto se publicó hace pocos días. Sin alertas, no lo habrías visto.
            </h2>
        </div>
        <a href="{{ route('buscador.mayores') }}" class="text-[var(--teal)] font-semibold text-[15px] inline-flex items-center gap-2 shrink-0 group">
            Ver todos en el buscador
            <span class="transition-transform group-hover:translate-x-1" aria-hidden="true">→</span>
        </a>
    </div>

    <div class="border border-[var(--line)] bg-white rounded-[1.4rem] overflow-hidden divide-y divide-[var(--line)]/60 shadow-sm">
        @foreach ($procesos->take(4) as $p)
        <div class="row-process px-6 py-5 grid grid-cols-1 md:grid-cols-12 gap-4 md:items-center transition-colors">
            <div class="md:col-span-7">
                <div class="flex items-center gap-3 mb-1.5 flex-wrap">
                    <span class="font-mono2 text-[11px] text-neutral-400 tracking-tight">{{ $p->nomenclatura }}</span>
                    <span class="estado-chip text-white bg-[var(--teal)] rounded-full px-2.5 py-1">{{ $p->estado ?? 'Vigente' }}</span>
                    @if ($p->departamento)
                        <span class="text-[11px] text-neutral-500 font-medium">{{ $p->departamento->nombre }}</span>
                    @endif
                </div>
                <p class="text-[16px] font-semibold text-neutral-900 leading-snug">{{ $p->objeto_contratacion }}</p>
                <p class="text-[13px] text-neutral-600 mt-1 truncate">{{ $p->entidad_nombre }}</p>
            </div>
            <div class="md:col-span-3 text-left md:text-right">
                <p class="text-[10px] text-neutral-400 uppercase tracking-wide mb-0.5">Monto referencial</p>
                <p class="text-[17px] font-bold text-[var(--teal)]">S/ {{ number_format((float) $p->valor_referencial, 0) }}</p>
                @if ($p->fecha_fin)
                    <p class="text-[11.5px] text-neutral-500 mt-1">Cierra: {{ $p->fecha_fin->format('d/m/Y') }}</p>
                @endif
            </div>
            <div class="md:col-span-2 text-left md:text-right">
                <p class="text-[10px] text-neutral-400 uppercase tracking-wide mb-1">Publicado</p>
                <p class="text-[13.5px] font-semibold text-neutral-800">{{ $p->fecha_publicacion ? $p->fecha_publicacion->format('d/m') : '—' }}</p>
            </div>
        </div>
        @endforeach
    </div>

    <p class="mt-6 text-[13.5px] text-neutral-500 text-center max-w-xl mx-auto leading-relaxed">
        Los procesos de contratación mayor a 8 UIT se publican con días de anticipación. Las alertas te avisan el mismo día, con el TDR analizado por IA.
    </p>
</section>
@endif

{{-- ══ MÉTRICAS ══ --}}
<section class="max-w-6xl mx-auto px-6 pb-20 grid grid-cols-2 lg:grid-cols-4 gap-x-10 gap-y-12">
    @php
        $metricas = [
            ['valor' => $stats['procesos'] > 0 ? number_format($stats['procesos'], 0, ',', '.') : '29,000+', 'label' => 'procesos monitoreados en el SEACE'],
            ['valor' => '25', 'label' => 'departamentos cubiertos del Perú'],
            ['valor' => $stats['monto'] > 0 ? 'S/ ' . number_format($stats['monto'] / 1000000, 1, ',', '.') . ' M' : 'S/ +1000 M', 'label' => 'en procesos monitoreados hoy'],
            ['valor' => '< 5 min', 'label' => 'entre la publicación del SEACE y tu WhatsApp'],
        ];
    @endphp
    @foreach ($metricas as $m)
        <div class="border-l-2 border-[var(--teal)] pl-5">
            <p class="font-display text-[2rem] sm:text-[2.3rem] leading-none tracking-tight text-[var(--ink)]">{{ $m['valor'] }}</p>
            <p class="mt-2.5 text-[13px] text-neutral-600 leading-snug">{{ $m['label'] }}</p>
        </div>
    @endforeach
</section>

{{-- ══ PROBLEMA ══ --}}
<section class="bg-[var(--teal-deep)] text-white">
    <div class="max-w-6xl mx-auto px-6 py-20 grid lg:grid-cols-2 gap-12 items-start">
        <div>
            <p class="kicker kicker-light mb-5">El problema</p>
            <h2 class="font-display font-medium text-white text-[2.1rem] sm:text-[2.7rem] leading-[1.08] tracking-tight">
                Revisar el SEACE a mano es un trabajo de tiempo completo que nadie te paga.
            </h2>
        </div>
        <div class="space-y-7 text-[var(--mint-soft)] leading-relaxed text-[17px]">
            <p>
                Cada día el Estado publica cientos de convocatorias. Las de tu rubro son pocas, aparecen de golpe y las ventanas para cotizar duran días — a veces horas.
            </p>
            <p>
                Si te enteras el día del cierre, no postulas. Y si no postulas, no existes para las entidades. Así de simple.
            </p>
            <ul class="space-y-4 pt-2 text-white">
                <li class="flex gap-3">
                    <span class="check-mark-light text-[var(--mint)] mt-0.5">✓</span>
                    <span><strong>El que cotiza primero</strong> se lleva la atención de la entidad. El que llega tarde, ni siquiera aparece.</span>
                </li>
                <li class="flex gap-3">
                    <span class="check-mark-light text-[var(--mint)] mt-0.5">✓</span>
                    <span><strong>Un TDR de 40 páginas</strong> esconde requisitos que te descalifican. Leerlos todos, para cada proceso, no es viable.</span>
                </li>
                <li class="flex gap-3">
                    <span class="check-mark-light text-[var(--mint)] mt-0.5">✓</span>
                    <span><strong>Tu competencia ya usa alertas.</strong> No porque sea más lista: porque se entera primero.</span>
                </li>
            </ul>
        </div>
    </div>
</section>

{{-- ══ CÓMO FUNCIONA ══ --}}
<section id="como-funciona" class="max-w-6xl mx-auto px-6 py-20">
    <div class="max-w-2xl mb-14">
        <p class="kicker mb-5">Cómo funciona</p>
        <h2 class="font-display font-medium text-[2.1rem] sm:text-[2.7rem] leading-[1.08] tracking-tight">
            De la convocatoria a tu WhatsApp, sin que muevas un dedo.
        </h2>
    </div>
    <div class="grid md:grid-cols-3 gap-12">
        <div>
            <p class="step-num mb-6">01</p>
            <h3 class="text-xl font-semibold tracking-tight mb-2">Dinos qué haces</h3>
            <p class="text-neutral-600 leading-relaxed text-[15px]">Tus rubros — obras viales, movimiento de tierras, alta tensión, lo que sea — y las regiones donde trabajas. Lo configuramos juntos en la reunión.</p>
        </div>
        <div>
            <p class="step-num mb-6">02</p>
            <h3 class="text-xl font-semibold tracking-tight mb-2">Llega la alerta</h3>
            <p class="text-neutral-600 leading-relaxed text-[15px]">Cuando una entidad pública convoca algo de tu rubro, el aviso llega a tu WhatsApp con entidad, objeto y monto. El mismo día de publicación.</p>
        </div>
        <div>
            <p class="step-num mb-6">03</p>
            <h3 class="text-xl font-semibold tracking-tight mb-2">Decide con la IA</h3>
            <p class="text-neutral-600 leading-relaxed text-[15px]">La IA lee el TDR y te dice si el proceso exige experiencia o certificaciones que no tienes. Postulas solo donde de verdad calzas.</p>
        </div>
    </div>
</section>

{{-- ══ LA REUNIÓN ══ --}}
<section class="bg-[var(--paper-2)] border-y border-[var(--line)]/70">
    <div class="max-w-6xl mx-auto px-6 py-20 grid lg:grid-cols-12 gap-14 items-center">
        <div class="lg:col-span-7">
            <p class="kicker mb-5">La reunión</p>
            <h2 class="font-display font-medium text-[2.1rem] sm:text-[2.7rem] leading-[1.08] tracking-tight mb-6">
                En 20 minutos ves el sistema con tus propios procesos.
            </h2>
            <p class="text-neutral-700 text-lg leading-relaxed max-w-xl mb-8">
                Nada de diapositivas. Buscamos en vivo licitaciones vigentes de tu rubro, armamos tu perfil y te mostramos lo que pasa cuando llega una alerta.
            </p>
            <ul class="space-y-5">
                @php
                    $reunion = [
                        'Tu perfil de empresa y rubros configurados frente a ti.',
                        'Un proceso real y vigente de tu sector, encontrado en vivo.',
                        'El análisis de TDR con IA: requisitos, experiencia exigida, score de compatibilidad.',
                        'La proforma de cotización generada en minutos, lista para enviar.',
                        'Precios claros y qué incluye cada plan. Sin letra pequeña.',
                    ];
                @endphp
                @foreach ($reunion as $item)
                    <li class="flex gap-4">
                        <span class="check-mark mt-1 text-[var(--teal)]">✓</span>
                        <span class="text-neutral-700 text-[16px] leading-relaxed">{{ $item }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="lg:col-span-5">
            <div class="bg-[var(--teal-deep)] rounded-[1.8rem] p-8 sm:p-10 text-white">
                <p class="kicker kicker-light mb-6">Agenda tu reunión</p>
                <h3 class="font-display font-medium text-white text-[1.8rem] leading-tight tracking-tight mb-4">
                    Hablemos 20 minutos.
                </h3>
                <p class="text-[var(--mint-soft)] text-[15.5px] leading-relaxed mb-6">
                    Escríbenos por WhatsApp o deja tu correo y coordinamos el horario. Sin compromiso: si el sistema no te sirve, te lo decimos en la misma llamada.
                </p>
                <a href="{{ $waLink }}" target="_blank" rel="noopener" class="btn-wa w-full">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    Escribir por WhatsApp
                </a>
                <p class="text-center text-[var(--mint-soft)]/80 text-[13px] mt-3 mb-6">+51 918 874 873 · respondemos en horario de oficina</p>

                <div class="flex items-center gap-3 text-white/50 text-[11px] uppercase tracking-widest mb-5">
                    <span class="h-px flex-1 bg-white/20"></span>
                    o por correo
                    <span class="h-px flex-1 bg-white/20"></span>
                </div>

                @if (session('ok'))
                    <div class="bg-white/10 border border-white/25 text-white text-[13.5px] leading-relaxed rounded-xl px-4 py-3 mb-4">
                        {{ session('ok') }}
                    </div>
                @endif
                @error('form')
                    <div class="bg-red-500/20 border border-red-300/40 text-white text-[13px] leading-relaxed rounded-xl px-4 py-3 mb-4">{{ $message }}</div>
                @enderror
                <form id="form-correo" method="POST" action="{{ route('landing.agendar-demo') }}">
                    @csrf
                    <input type="hidden" name="landing" value="alertas-licitaciones">

                    <div class="absolute left-[-9999px] top-[-9999px] opacity-0 pointer-events-none" aria-hidden="true">
                        <label>No llenar este campo <input type="text" name="empresa_web" tabindex="-1" autocomplete="off"></label>
                    </div>

                    <div class="space-y-3.5">
                        <input class="campo-correo" type="text" name="nombre" required value="{{ old('nombre') }}" placeholder="Nombre completo" class="w-full px-4 py-3 rounded-xl bg-white text-neutral-900 text-[14px] placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-white/60">
                        @error('nombre')<p class="text-[12px] text-red-200">{{ $message }}</p>@enderror

                        <input class="campo-correo" type="email" name="email" required value="{{ old('email') }}" placeholder="Correo corporativo" class="w-full px-4 py-3 rounded-xl bg-white text-neutral-900 text-[14px] placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-white/60">
                        @error('email')<p class="text-[12px] text-red-200">{{ $message }}</p>@enderror

                        <input class="campo-correo" type="text" name="empresa" value="{{ old('empresa') }}" placeholder="Empresa" class="w-full px-4 py-3 rounded-xl bg-white text-neutral-900 text-[14px] placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-white/60">

                        <input class="campo-correo" type="text" name="rubro" value="{{ old('rubro') }}" placeholder="Rubro (ej. obras viales, energía)" class="w-full px-4 py-3 rounded-xl bg-white text-neutral-900 text-[14px] placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-white/60">

                        <div>
                            <label class="block text-white/85 text-[13px] mb-1.5">¿Cuánto es {{ $demoCaptchaA ?? 7 }} + {{ $demoCaptchaB ?? 3 }}?</label>
                            <div class="flex flex-col sm:flex-row gap-3">
                                <input class="campo-correo w-full sm:flex-1 px-4 py-3 rounded-xl bg-white text-neutral-900 text-[14px] placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-white/60" type="text" name="captcha" required inputmode="numeric" autocomplete="off" placeholder="Respuesta">
                                <button type="submit" class="w-full sm:w-auto shrink-0 rounded-xl bg-[var(--mint-soft)] text-[var(--teal-deep)] font-bold text-[15px] px-6 py-3 transition-opacity hover:opacity-90 whitespace-nowrap">
                                    Enviar solicitud
                                </button>
                            </div>
                            <p class="text-white/45 text-[11px] mt-1.5">Verificación anti-robot.</p>
                            @error('captcha')<p class="text-[12px] text-red-200">{{ $message }}</p>@enderror
                            @error('form')<p class="text-[12px] text-red-200">{{ $message }}</p>@enderror
                        </div>
                        <p class="text-white/50 text-[11.5px] text-center leading-relaxed">Sin spam. Solo usamos tu correo para coordinar la reunión.</p>
                    </div>
                </form>
                <script>
                    (function () {
                        var t0 = null;
                        document.querySelectorAll('#form-correo .campo-correo').forEach(function (el) {
                            el.addEventListener('focus', function () { if (t0 === null) { t0 = Date.now(); } });
                        });
                        document.getElementById('form-correo').addEventListener('submit', function () {
                            if (t0 !== null) {
                                var h = document.createElement('input');
                                h.type = 'hidden';
                                h.name = 'tiempo_llenado_ms';
                                h.value = String(Date.now() - t0);
                                this.appendChild(h);
                            }
                        });
                    })();
                </script>
            </div>
        </div>
    </div>
</section>

{{-- ══ PARA QUIÉN ══ --}}
<section class="max-w-6xl mx-auto px-6 py-20">
    <div class="max-w-2xl mb-12">
        <p class="kicker mb-5">Para quién es</p>
        <h2 class="font-display font-medium text-[2.1rem] sm:text-[2.7rem] leading-[1.08] tracking-tight">
            Rubros que ya vigilan contratistas en Vigilante SEACE.
        </h2>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-x-10 gap-y-3">
        @php
            $rubros = ['Obras civiles y edificación', 'Pistas y veredas', 'Movimiento de tierras', 'Carreteras', 'Mantenimiento de infraestructura', 'Instalaciones eléctricas y alta tensión', 'Electromecánicos', 'Estructuras metálicas', 'Maquinaria pesada', 'Consultoría y supervisión de obras', 'Servicios de limpieza y logística', 'TI, telecomunicaciones y data center'];
        @endphp
        @foreach ($rubros as $rubro)
            <div class="flex items-center gap-3 border-b border-[var(--line)]/60 py-3.5">
                <span class="check-mark text-[var(--teal)]">✓</span>
                <span class="text-neutral-700 text-[15.5px]">{{ $rubro }}</span>
            </div>
        @endforeach
    </div>
    <p class="mt-10 text-neutral-600 max-w-xl leading-relaxed text-[15px]">
        ¿Tu rubro no está en la lista? Igual escríbenos. Si alguien publica procesos de lo que haces en el SEACE, lo vigilamos.
    </p>
</section>

{{-- ══ FAQ ══ --}}
<section class="bg-white border-y border-[var(--line)]/70">
    <div class="max-w-3xl mx-auto px-6 py-20">
        <div class="mb-12 text-center">
            <p class="kicker mb-5">Preguntas frecuentes</p>
            <h2 class="font-display font-medium text-[2.1rem] sm:text-[2.5rem] leading-[1.08] tracking-tight">Lo que preguntan antes de la reunión</h2>
        </div>
        <div class="divide-y divide-[var(--line)]/70 border-y border-[var(--line)]/70">
            @php
                $faqs = [
                    ['q' => '¿La reunión es realmente gratis?', 'a' => 'Sí, y no vendemos nada en la llamada. Te mostramos el sistema con procesos de tu rubro para que decidas con información. Si no te sirve, te lo decimos ahí mismo.'],
                    ['q' => '¿Necesito estar inscrito en el RNP?', 'a' => 'No para recibir alertas. La inscripción en el RNP se necesita al momento de cotizar u ofertar. Las alertas te sirven para ver qué hay en tu rubro antes de dar ese paso.'],
                    ['q' => '¿Qué diferencia hay con revisar el buscador del OSCE?', 'a' => 'El buscador del OSCE lo revisas tú, manualmente, y muestra todo el país sin filtrar por tu empresa. Nosotros vigilamos el SEACE por ti y te avisamos solo cuando aparece algo de tu rubro y región. Además analizamos el TDR con IA para que no leas 40 páginas de bases de procesos imposibles.'],
                    ['q' => '¿Cubre mi región o solo Lima?', 'a' => 'Todo el Perú. Configuras uno o varios departamentos, o recibes procesos de todo el país.'],
                    ['q' => '¿Cuánto cuesta?', 'a' => 'El plan Premium cuesta S/ 49 al mes e incluye alertas, análisis de TDR con IA y score de compatibilidad. El plan con Contratos Mayores cuesta S/ 68 al mes y suma los procesos mayores a 8 UIT. En la reunión vemos cuál calza con lo que haces.'],
                    ['q' => '¿Sirve si nunca he vendido al Estado?', 'a' => 'Sí. La mayoría de contrataciones menores a 8 UIT no exigen experiencia previa con el Estado. Empiezas cotizando procesos chicos, ganas algunos, y con ese historial ya puedes ir por procesos más grandes.'],
                    ['q' => '¿Cómo se paga?', 'a' => 'Con tarjeta (Visa o Mastercard) o por Yape. El cobro es mensual y puedes cancelar cuando quieras desde tu panel, sin permanencia ni cláusulas raras.'],
                ];
            @endphp
            @foreach ($faqs as $i => $faq)
                <details class="group py-6" {{ $i === 0 ? 'open' : '' }}>
                    <summary class="flex items-center justify-between gap-6 cursor-pointer list-none select-none">
                        <span class="text-[16.5px] font-semibold tracking-tight text-neutral-900">{{ $faq['q'] }}</span>
                        <span class="font-display text-[1.6rem] leading-none text-[var(--teal)] transition-transform duration-200 group-open:rotate-45" aria-hidden="true">+</span>
                    </summary>
                    <div class="faq-answer">
                        <p class="pt-4 text-neutral-600 leading-relaxed text-[15px] max-w-2xl">{{ $faq['a'] }}</p>
                    </div>
                </details>
            @endforeach
        </div>
    </div>
</section>

{{-- ══ CTA FINAL ══ --}}
<section class="bg-[var(--teal-deep)] text-white relative overflow-hidden">
    <div class="absolute -left-24 top-1/2 -translate-y-1/2 w-80 h-80 rounded-full bg-[var(--mint)]/10 blur-3xl" aria-hidden="true"></div>
    <div class="relative max-w-3xl mx-auto px-6 py-24 text-center">
        <p class="kicker kicker-light mb-6">Agenda tu reunión</p>
        <h2 class="font-display font-medium text-white text-[2.3rem] sm:text-[3rem] leading-[1.06] tracking-tight mb-6">
            La próxima convocatoria que calza con tu empresa<br>
            <em class="italic text-[var(--mint)]">podría publicarse mañana.</em>
        </h2>
        <p class="text-[var(--mint-soft)] text-lg leading-relaxed max-w-xl mx-auto mb-10">
            Esta semana se publicaron {{ $stats['semana'] > 0 ? number_format($stats['semana'], 0, ',', '.') : 'cientos de' }} procesos en el SEACE. 20 minutos de reunión y sabes si esto te sirve.
        </p>
        <a href="{{ $waLink }}" target="_blank" rel="noopener" class="btn-wa !px-10 !py-4 !text-lg">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            Quiero agendar mi reunión
        </a>
        <p class="text-[var(--mint-soft)]/80 text-sm mt-5">+51 918 874 873 · sin compromiso · 20 minutos</p>
    </div>
</section>

</main>

{{-- ══ FOOTER ══ --}}
<footer class="max-w-6xl mx-auto px-6 py-10 flex flex-col sm:flex-row items-center justify-between gap-4">
    <p class="text-[13px] text-neutral-500">© {{ date('Y') }} Sunqupacha S.A.C. Vigilante SEACE es un producto de Sunqupacha.</p>
    <div class="flex items-center gap-6 text-[13px] text-neutral-500">
        <a href="{{ route('legal.politica-privacidad') }}" class="hover:text-neutral-800 underline underline-offset-2">Privacidad</a>
        <a href="{{ route('legal.condiciones-servicio') }}" class="hover:text-neutral-800 underline underline-offset-2">Condiciones</a>
        <a href="{{ route('contacto') }}" class="hover:text-neutral-800 underline underline-offset-2">Contacto</a>
    </div>
</footer>

</body>
</html>
