<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Licitaciones Públicas Vigentes en Perú Hoy — SEACE al día | Vigilante SEACE</title>
    <meta name="description" content="Convocatorias públicas vigentes en el Perú, actualizadas con el SEACE: obras, bienes y servicios en convocatoria. Reciba aviso cuando se publique algo del rubro de su empresa.">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:title" content="Licitaciones Públicas Vigentes en Perú Hoy — SEACE al día | Vigilante SEACE">
    <meta property="og:description" content="Convocatorias públicas vigentes en el Perú, actualizadas con el SEACE. Reciba aviso de su rubro.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="es_PE">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @unless(app()->environment('production'))
        <meta name="robots" content="noindex, nofollow">
    @endunless
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@500;700&display=swap">
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
            --bg: #f5f7fa;
            --ink: #0f172a;
            --muted: #5b6b7f;
            --navy: #1d3f8f;
            --navy-deep: #12275c;
            --amber: #f0a028;
            --amber-soft: #fdf1dd;
            --emerald: #0e9f6e;
            --emerald-soft: #e2f6ee;
            --line: #dfe5ee;
        }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
        }
        .font-display { font-family: 'Sora', sans-serif; }
        .font-data { font-family: 'JetBrains Mono', ui-monospace, monospace; }
        .kicker {
            font-family: 'JetBrains Mono', monospace;
            font-size: .68rem;
            letter-spacing: .2em;
            text-transform: uppercase;
            color: var(--navy);
            font-weight: 700;
        }
        .btn-wa {
            display: inline-flex; align-items: center; justify-content: center; gap: .55rem;
            background: #1faa57; color: #fff; font-weight: 700;
            border-radius: 12px; padding: .95rem 1.7rem; font-size: .95rem;
            font-family: 'Sora', sans-serif;
            box-shadow: 0 12px 26px -12px rgba(31,170,87,.5);
            transition: background .15s ease, transform .15s ease;
        }
        .btn-wa:hover { background: #19924b; transform: translateY(-1px); }
        .btn-navy {
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            background: var(--navy); color: #fff; font-weight: 700;
            border-radius: 12px; padding: .95rem 1.7rem; font-size: .95rem;
            font-family: 'Sora', sans-serif;
            transition: background .15s ease, transform .15s ease;
        }
        .btn-navy:hover { background: var(--navy-deep); transform: translateY(-1px); }
        .btn-ghost {
            display: inline-flex; align-items: center; justify-content: center;
            border: 1px solid var(--line); color: var(--ink); background: #fff;
            border-radius: 12px; padding: .95rem 1.6rem; font-size: .95rem; font-weight: 600;
            font-family: 'Sora', sans-serif;
            transition: border-color .15s ease;
        }
        .btn-ghost:hover { border-color: var(--navy); }
        .card { background: #fff; border: 1px solid var(--line); border-radius: 16px; }
        .chip-live {
            display: inline-flex; align-items: center; gap: .45rem;
            background: var(--emerald-soft); color: var(--emerald);
            font-family: 'JetBrains Mono', monospace; font-size: .62rem; font-weight: 700;
            letter-spacing: .08em; text-transform: uppercase;
            border-radius: 999px; padding: .3rem .7rem;
        }
        .chip-live::before {
            content: ""; width: 6px; height: 6px; border-radius: 50%;
            background: var(--emerald); animation: pulso 1.6s ease-in-out infinite;
        }
        @keyframes pulso { 0%,100%{opacity:1} 50%{opacity:.35} }
        .row-proc { transition: background .12s ease; }
        .row-proc:hover { background: #f2f6fc; }
        .faq-answer { max-height: 0; overflow: hidden; transition: max-height .25s ease; }
        details[open] .faq-answer { max-height: 420px; }
        details summary::-webkit-details-marker { display: none; }
        ::selection { background: #dbe6fb; }
        .banda-ticker {
            background: linear-gradient(90deg, var(--navy-deep), var(--navy) 70%, #2b5ac2);
        }
        .num-big {
            font-family: 'Sora', sans-serif; font-weight: 800; letter-spacing: -.02em;
            font-variant-numeric: tabular-nums;
        }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            * { transition: none !important; }
            .chip-live::before { animation: none; }
        }
    </style>
</head>
<body>

@php
    $waNumber = '51918874873';
    $waLead = 'https://wa.me/' . $waNumber . '?text=' . rawurlencode('Hola, vi las licitaciones vigentes en Vigilante SEACE y quiero que me avisen cuando se publique algo del rubro de mi empresa.');
    $clientes = ['ZAVATEC SA', 'STEEL MAQUINARIAS', 'CORPORACIÓN FAMOD', 'ESTUDIO JURÍDICO P&B'];
    $hoy = now()->format('d/m/Y \a \l\a\s H:i');
@endphp

{{-- ══ TICKER EN VIVO ══ --}}
<div class="banda-ticker text-white">
    <div class="max-w-6xl mx-auto px-6 py-2 flex flex-wrap items-center justify-between gap-2 text-[12px]">
        <span class="inline-flex items-center gap-2">
            <span class="w-1.5 h-1.5 rounded-full bg-[#4ade80] animate-pulse"></span>
            <span class="font-data">MONITOREO SEACE ACTIVO</span>
        </span>
        <span class="text-white/75">Actualizado hoy · {{ $hoy }}</span>
        <a href="{{ route('buscador.mayores') }}" class="text-white/90 hover:text-white underline underline-offset-2 font-medium">Ir al buscador →</a>
    </div>
</div>

{{-- ══ HEADER ══ --}}
<header class="bg-white/90 backdrop-blur border-b border-[var(--line)]">
    <div class="max-w-6xl mx-auto px-6 h-[4.2rem] flex items-center justify-between">
        <a href="{{ url('/') }}" class="flex items-center gap-2.5">
            <span class="w-9 h-9 rounded-[10px] bg-[var(--navy)] grid place-items-center">
                <svg class="w-[18px] h-[18px] text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
            </span>
            <span class="font-display font-bold text-[1.1rem] tracking-tight text-[var(--ink)]">Vigilante <span class="text-[var(--navy)]">SEACE</span></span>
        </a>
        <nav class="hidden lg:flex items-center gap-7 text-[14px] text-[var(--muted)]">
            <a href="#en-vivo" class="hover:text-[var(--navy)] transition-colors font-medium">En vivo</a>
            <a href="#aviso" class="hover:text-[var(--navy)] transition-colors font-medium">Recibir avisos</a>
            <a href="#faq" class="hover:text-[var(--navy)] transition-colors font-medium">Preguntas</a>
        </nav>
        <a href="#aviso" class="btn-navy !px-5 !py-2.5 !text-[13.5px]">Recibir avisos gratis</a>
    </div>
</header>

<main>

{{-- ══ HERO ══ --}}
<section class="relative overflow-hidden">
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div class="absolute -top-28 right-[-6%] w-[440px] h-[440px] rounded-full bg-[#dbe6fb]/70 blur-[110px]"></div>
        <div class="absolute top-1/3 left-[-8%] w-[340px] h-[340px] rounded-full bg-[var(--amber-soft)]/80 blur-[100px]"></div>
    </div>
    <div class="relative max-w-6xl mx-auto px-6 pt-16 pb-10 text-center">
        <div class="inline-flex items-center gap-3 mb-6">
            <span class="chip-live">En vivo</span>
            <span class="text-[12.5px] font-medium text-[var(--muted)]">Procesos del SEACE en convocatoria ahora mismo</span>
        </div>
        <h1 class="font-display font-extrabold text-[2.4rem] sm:text-[3.4rem] leading-[1.03] tracking-tight text-[var(--ink)] max-w-4xl mx-auto">
            Licitaciones públicas vigentes en el Perú,<br>
            <span class="text-[var(--navy)]">actualizadas con el SEACE.</span>
        </h1>
        <p class="mt-6 text-[16.5px] text-[var(--muted)] leading-relaxed max-w-2xl mx-auto">
            Las entidades del Estado convocan todos los días. Aquí ve lo que está abierto hoy y le avisamos cuando se publique algo del rubro de su empresa.
        </p>

        {{-- Stats rápidas --}}
        <div class="mt-10 grid grid-cols-3 gap-4 max-w-3xl mx-auto">
            <div class="card px-4 py-5">
                <p class="num-big text-[1.6rem] sm:text-[2rem] text-[var(--navy)]">{{ $stats['convocados'] > 0 ? number_format($stats['convocados'], 0, ',', '.') : '—' }}</p>
                <p class="mt-1 text-[12px] text-[var(--muted)] leading-snug">procesos en convocatoria</p>
            </div>
            <div class="card px-4 py-5">
                <p class="num-big text-[1.6rem] sm:text-[2rem] text-[var(--amber)]">{{ $stats['entidades'] > 0 ? number_format($stats['entidades'], 0, ',', '.') : '—' }}</p>
                <p class="mt-1 text-[12px] text-[var(--muted)] leading-snug">entidades que convocan</p>
            </div>
            <div class="card px-4 py-5">
                <p class="num-big text-[1.6rem] sm:text-[2rem] text-[var(--emerald)]">{{ $stats['monto'] > 0 ? 'S/ ' . number_format($stats['monto'] / 1000000, 0, ',', '.') . ' M' : '—' }}</p>
                <p class="mt-1 text-[12px] text-[var(--muted)] leading-snug">en convocatoria activa</p>
            </div>
        </div>

        <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="#aviso" class="btn-navy">
                Recibir avisos de mi rubro
            </a>
            <a href="#en-vivo" class="btn-ghost">Ver procesos vigentes</a>
        </div>
    </div>
</section>

{{-- ══ FEED EN VIVO ══ --}}
<section id="en-vivo" class="bg-white border-y border-[var(--line)]">
    <div class="max-w-6xl mx-auto px-6 py-16 lg:py-20">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
            <div>
                <p class="kicker mb-3">Hoy en el SEACE</p>
                <h2 class="font-display font-bold text-[1.9rem] sm:text-[2.4rem] tracking-tight text-[var(--ink)]">
                    Procesos publicados recientemente
                </h2>
            </div>
            <a href="{{ route('buscador.mayores') }}" class="text-[var(--navy)] font-semibold text-[14.5px] inline-flex items-center gap-2 shrink-0">
                Ver todos en el buscador <span aria-hidden="true">→</span>
            </a>
        </div>

        @if ($procesos->count() > 0)
        <div class="card overflow-hidden divide-y divide-[var(--line)]">
            @foreach ($procesos as $p)
            <div class="row-proc px-5 sm:px-7 py-5 flex flex-col lg:flex-row lg:items-center gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3 flex-wrap mb-1.5">
                        <span class="font-data text-[11px] text-neutral-400">{{ $p->nomenclatura }}</span>
                        <span class="inline-flex items-center gap-1.5 text-[10.5px] font-bold text-[var(--emerald)] bg-[var(--emerald-soft)] rounded-full px-2 py-0.5 uppercase tracking-wide">
                            <span class="w-1 h-1 rounded-full bg-[var(--emerald)]"></span>
                            En convocatoria
                        </span>
                        @if ($p->departamento)
                            <span class="text-[11px] font-medium text-[var(--muted)]">📍 {{ $p->departamento->nombre }}</span>
                        @endif
                    </div>
                    <p class="text-[15.5px] font-semibold text-[var(--ink)] leading-snug">{{ $p->objeto_contratacion }}</p>
                    <p class="text-[13px] text-[var(--muted)] mt-0.5 truncate">{{ $p->entidad_nombre }}</p>
                </div>
                <div class="flex items-center gap-6 shrink-0">
                    <div class="text-right">
                        <p class="text-[10px] text-neutral-400 uppercase tracking-wide mb-0.5">Monto</p>
                        <p class="text-[15px] font-bold text-[var(--navy)]">S/ {{ number_format((float) $p->valor_referencial, 0) }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] text-neutral-400 uppercase tracking-wide mb-0.5">Publicado</p>
                        <p class="text-[13.5px] font-semibold text-[var(--ink)]">{{ $p->fecha_publicacion?->format('d/m/Y') }}</p>
                    </div>
                    <a href="{{ route('buscador.mayores') }}?q={{ urlencode($p->nomenclatura) }}" class="inline-flex items-center justify-center border border-[var(--line)] hover:border-[var(--navy)] text-[var(--navy)] font-semibold text-[13px] rounded-xl px-4 py-2.5 whitespace-nowrap transition-colors bg-white">
                        Ver proceso →
                    </a>
                </div>
            </div>
            @endforeach
        </div>
        <p class="mt-5 text-[13px] text-[var(--muted)] text-center">Los 8 más recientes. Para ver el detalle completo de cada convocatoria, use el buscador.</p>
        @else
        <div class="card p-10 text-center text-[var(--muted)]">No pudimos cargar los procesos ahora. Intenta de nuevo en unos minutos.</div>
        @endif
    </div>
</section>

{{-- ══ CLIENTES ══ --}}
<section class="border-b border-[var(--line)] bg-white">
    <div class="max-w-6xl mx-auto px-6 py-8">
        <p class="text-center text-[11px] uppercase tracking-[.18em] font-bold text-[var(--muted)] mb-5">Empresas que ya reciben sus avisos de licitaciones</p>
        <div class="flex flex-wrap items-center justify-center gap-x-12 gap-y-3">
            @foreach ($clientes as $i => $cliente)
                <span class="text-[15px] font-display font-semibold tracking-tight" style="color: {{ ['#1d3f8f', '#0e9f6e', '#b45309', '#12275c'][$i] }}">{{ $cliente }}</span>
            @endforeach
        </div>
    </div>
</section>

{{-- ══ BANDA FOTOGRÁFICA ══ --}}
<section class="relative overflow-hidden">
    <img src="{{ asset('images/landings/obra-grua.jpg') }}" alt="Obra pública en ejecución financiada con contrataciones del Estado" width="1400" height="933" loading="lazy" class="absolute inset-0 w-full h-full object-cover">
    <div class="absolute inset-0" style="background:linear-gradient(105deg, rgba(18,39,92,.93) 12%, rgba(29,63,143,.8) 50%, rgba(43,90,194,.4) 100%)"></div>
    <div class="relative max-w-5xl mx-auto px-6 py-20 lg:py-24">
        <div class="max-w-2xl">
            <p class="kicker !text-[#ffd58a] mb-5">Detrás de cada convocatoria</p>
            <h2 class="font-display font-bold text-white text-[2rem] sm:text-[2.6rem] leading-[1.07] tracking-tight">
                Hay una obra, un servicio<br>o un bien que el Estado necesita.
            </h2>
            <p class="mt-5 text-white/90 text-[16px] leading-relaxed max-w-xl">
                Municipalidades, gobiernos regionales, ministerios y hospitales publican sus necesidades todos los días en el SEACE. La pregunta no es si su empresa puede venderles — es si se entera a tiempo.
            </p>
            <a href="#aviso" class="mt-7 inline-flex items-center justify-center gap-2 bg-white text-[var(--navy)] hover:bg-neutral-50 font-bold text-[14.5px] px-7 py-3.5 rounded-xl transition-colors">
                Quiero enterarme a tiempo →
            </a>
        </div>
    </div>
</section>

{{-- ══ LEAD CAPTURE ══ --}}
<section id="aviso" class="relative overflow-hidden" style="background:linear-gradient(135deg, #12275c, #1d3f8f 55%, #2b5ac2)">
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div class="absolute -top-28 right-[-6%] w-[400px] h-[400px] rounded-full bg-white/5 blur-[90px]"></div>
        <div class="absolute bottom-[-25%] left-[-6%] w-[360px] h-[360px] rounded-full bg-[var(--amber)]/10 blur-[100px]"></div>
    </div>
    <div class="relative max-w-6xl mx-auto px-6 py-20 lg:py-24 grid lg:grid-cols-2 gap-14 items-center">
        <div class="text-white">
            <p class="kicker !text-[#ffd58a] mb-5">Aviso de su rubro</p>
            <h2 class="font-display font-bold text-white text-[2rem] sm:text-[2.6rem] leading-[1.06] tracking-tight mb-6">
                Cuando se publique algo de su rubro,<br>
                <span class="text-[#ffd58a]">que no tenga que buscarlo.</span>
            </h2>
            <p class="text-white/85 text-[15.5px] leading-relaxed max-w-lg mb-8">
                Cuéntenos qué hace su empresa y en qué regiones trabaja. Cuando el SEACE publique una convocatoria que calza, le llega el aviso por WhatsApp o correo, el mismo día.
            </p>
            <ul class="space-y-3.5">
                @php
                    $aviso = [
                        'Sin costo por recibir información de su rubro.',
                        'Usted decide cuándo empezar y cuándo parar.',
                        'Incluye contratos menores y mayores a 8 UIT (Ley 32069).',
                    ];
                @endphp
                @foreach ($aviso as $item)
                    <li class="flex gap-3 text-[15px] text-white leading-relaxed">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-md bg-white/15 text-[#ffd58a] font-bold text-[11.5px] shrink-0 mt-0.5">✓</span>
                        <span>{{ $item }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="bg-white rounded-3xl p-7 sm:p-9 shadow-2xl">
            <p class="kicker mb-3">Recibir avisos</p>
            <h3 class="font-display font-bold text-[1.5rem] leading-tight tracking-tight text-[var(--ink)] mb-5">
                Déjenos su contacto por WhatsApp o correo.
            </h3>

            <a href="{{ $waLead }}" target="_blank" rel="noopener" class="btn-wa w-full">
                <svg viewBox="0 0 24 24" width="19" height="19" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                Escribir por WhatsApp
            </a>
            <p class="text-center text-[12.5px] text-[var(--muted)] mt-2.5 mb-5">+51 918 874 873 · respondemos en horario de oficina</p>

            <div class="flex items-center gap-3 text-[10.5px] uppercase tracking-widest text-neutral-400 mb-5">
                <span class="h-px flex-1 bg-neutral-200"></span>
                o por correo
                <span class="h-px flex-1 bg-neutral-200"></span>
            </div>

            @if (session('ok'))
                <div class="bg-[var(--emerald-soft)] border border-[var(--emerald)]/40 text-[var(--emerald)] text-[13.5px] leading-relaxed rounded-xl px-4 py-3 mb-4">{{ session('ok') }}</div>
            @endif
            @if (session('error'))
                <div class="bg-red-50 border border-red-300/50 text-red-600 text-[13.5px] leading-relaxed rounded-xl px-4 py-3 mb-4">{{ session('error') }}</div>
            @endif
            <form id="form-correo" method="POST" action="{{ route('landing.agendar-demo') }}">
                @csrf
                <input type="hidden" name="landing" value="licitaciones-vigentes">

                <div class="absolute left-[-9999px] top-[-9999px] opacity-0 pointer-events-none" aria-hidden="true">
                    <label>No llenar este campo <input type="text" name="empresa_web" tabindex="-1" autocomplete="off"></label>
                </div>

                <div class="space-y-3.5">
                    <input class="campo-correo w-full px-4 py-3 rounded-xl border text-[14px] placeholder-neutral-400 focus:outline-none focus:ring-2 transition-colors" style="border-color:var(--line); color:var(--ink)" type="text" name="nombre" required value="{{ old('nombre') }}" placeholder="Nombre completo">
                    @error('nombre')<p class="text-[12px] text-red-500">{{ $message }}</p>@enderror

                    <input class="campo-correo w-full px-4 py-3 rounded-xl border text-[14px] placeholder-neutral-400 focus:outline-none focus:ring-2 transition-colors" style="border-color:var(--line); color:var(--ink)" type="email" name="email" required value="{{ old('email') }}" placeholder="Correo corporativo">
                    @error('email')<p class="text-[12px] text-red-500">{{ $message }}</p>@enderror

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <input class="campo-correo w-full px-4 py-3 rounded-xl border text-[14px] placeholder-neutral-400 focus:outline-none focus:ring-2 transition-colors" style="border-color:var(--line); color:var(--ink)" type="text" name="empresa" value="{{ old('empresa') }}" placeholder="Empresa">
                        <input class="campo-correo w-full px-4 py-3 rounded-xl border text-[14px] placeholder-neutral-400 focus:outline-none focus:ring-2 transition-colors" style="border-color:var(--line); color:var(--ink)" type="text" name="rubro" value="{{ old('rubro') }}" placeholder="Rubro (ej. obras viales)">
                    </div>

                    <div>
                        <label class="block text-[13px] font-semibold mb-1.5" style="color:var(--muted)">¿Cuánto es {{ $demoCaptchaA ?? 7 }} + {{ $demoCaptchaB ?? 3 }}?</label>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <input class="campo-correo w-full sm:flex-1 px-4 py-3 rounded-xl border text-[14px] placeholder-neutral-400 focus:outline-none focus:ring-2 transition-colors" style="border-color:var(--line); color:var(--ink)" type="text" name="captcha" required inputmode="numeric" autocomplete="off" placeholder="Respuesta">
                            <button type="submit" class="btn-navy w-full sm:w-auto !px-6 !py-3 whitespace-nowrap">
                                Enviar solicitud
                            </button>
                        </div>
                        <p class="text-[11px] text-neutral-400 mt-1.5">Verificación anti-robot.</p>
                        @error('captcha')<p class="text-[12px] text-red-500">{{ $message }}</p>@enderror
                        @error('form')<p class="text-[12px] text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <p class="text-[11.5px] text-center text-neutral-400 leading-relaxed">Sin spam. Solo usamos tu correo para avisarte de tu rubro.</p>
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
</section>

{{-- ══ FAQ ══ --}}
<section id="faq" class="max-w-3xl mx-auto px-6 py-20">
    <div class="mb-12 text-center">
        <p class="kicker mb-4">Preguntas frecuentes</p>
        <h2 class="font-display font-bold text-[2rem] sm:text-[2.5rem] tracking-tight text-[var(--ink)]">Sobre las licitaciones vigentes</h2>
    </div>
    <div class="divide-y divide-[var(--line)] bg-white border border-[var(--line)] rounded-2xl px-7">
        @php
            $faqs = [
                ['q' => '¿Estos procesos están vigentes hoy?', 'a' => 'Sí. La lista se actualiza con el SEACE y muestra procesos en estado de convocatoria publicados recientemente. Para el detalle completo (bases, plazos, documentos) use el botón "Ver proceso".'],
                ['q' => '¿Recibir avisos tiene algún costo?', 'a' => 'No. Puede dejar su contacto para que le avisemos cuando se publique algo del rubro de su empresa. Usted decide cuándo empezar y cuándo dejar de recibirlos.'],
                ['q' => '¿Puedo postular a estos procesos sin experiencia previa?', 'a' => 'Depende de cada convocatoria: muchas contrataciones menores a 8 UIT no exigen experiencia previa con el Estado. Cada proceso publica sus requisitos en las bases.'],
                ['q' => '¿Necesito RNP para participar?', 'a' => 'La inscripción en el RNP se exige al momento de ofertar o cotizar. Si aún no lo tiene, los avisos le sirven para ver qué convoca el Estado en su rubro y prepararse.'],
                ['q' => '¿Qué diferencia hay con el buscador del OSCE?', 'a' => 'El buscador del OSCE lo consulta usted manualmente. Nosotros le avisamos cuando aparece algo de su rubro y región, sin que tenga que revisar el portal todos los días.'],
                ['q' => '¿Cuánto cuesta el servicio completo?', 'a' => 'Si más adelante quiere el servicio completo —alertas configuradas, análisis de TDR con IA, score y proformas— el plan Premium cuesta S/ 49 al mes y el de Contratos Mayores S/ 68. Primero reciba los avisos y decida con calma.'],
            ];
        @endphp
        @foreach ($faqs as $i => $faq)
            <details class="group py-5" {{ $i === 0 ? 'open' : '' }}>
                <summary class="flex items-center justify-between gap-6 cursor-pointer list-none select-none">
                    <span class="text-[15.5px] font-semibold tracking-tight text-[var(--ink)]">{{ $faq['q'] }}</span>
                    <span class="w-7 h-7 rounded-full grid place-items-center shrink-0 transition-transform duration-200 group-open:rotate-45" style="background:var(--amber-soft); color:#b45309">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M12 4v16m8-8H4"/></svg>
                    </span>
                </summary>
                <div class="faq-answer">
                    <p class="pt-3 text-[14.5px] text-[var(--muted)] leading-relaxed max-w-2xl">{{ $faq['a'] }}</p>
                </div>
            </details>
        @endforeach
    </div>
</section>

{{-- ══ CTA FINAL ══ --}}
<section class="banda-ticker text-white relative overflow-hidden">
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div class="absolute inset-0 dot-grid opacity-20" style="background-image:radial-gradient(rgba(255,255,255,.22) 1px, transparent 1px); background-size:26px 26px"></div>
    </div>
    <div class="relative max-w-3xl mx-auto px-6 py-20 text-center">
        <p class="kicker !text-[#ffd58a] mb-5">Recibir avisos</p>
        <h2 class="font-display font-bold text-white text-[2.1rem] sm:text-[2.8rem] leading-[1.06] tracking-tight mb-5">
            La convocatoria de su rubro<br>puede publicarse mañana.
        </h2>
        <p class="text-white/85 text-[16px] leading-relaxed max-w-xl mx-auto mb-9">
            Deje su contacto y le avisamos cuando el Estado publique algo que calza con su empresa.
        </p>
        <a href="{{ $waLead }}" target="_blank" rel="noopener" class="btn-wa !px-9 !py-4 !text-[15.5px]">
            Recibir avisos por WhatsApp
        </a>
        <p class="text-white/60 text-[13px] mt-5">+51 918 874 873 · sin costo · usted decide cuándo parar</p>
    </div>
</section>

</main>

{{-- ══ FOOTER ══ --}}
<footer class="bg-[#0c1a33] text-white">
    <div class="max-w-6xl mx-auto px-6 py-12 flex flex-col lg:flex-row items-center justify-between gap-6">
        <div class="flex items-center gap-2.5">
            <span class="w-9 h-9 rounded-[10px] bg-[var(--navy)] grid place-items-center">
                <svg class="w-[18px] h-[18px] text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
            </span>
            <div>
                <p class="font-display font-bold text-[14.5px] leading-tight">Vigilante SEACE</p>
                <p class="text-[11px] text-neutral-400">Producto de Sunqupacha S.A.C.</p>
            </div>
        </div>
        <p class="text-[13px] text-neutral-400 text-center">© {{ date('Y') }} Sunqupacha S.A.C. Todos los derechos reservados.</p>
        <div class="flex items-center gap-6 text-[13px] text-neutral-400">
            <a href="{{ route('legal.politica-privacidad') }}" class="hover:text-white underline underline-offset-2">Privacidad</a>
            <a href="{{ route('legal.condiciones-servicio') }}" class="hover:text-white underline underline-offset-2">Condiciones</a>
            <a href="{{ route('contacto') }}" class="hover:text-white underline underline-offset-2">Contacto</a>
        </div>
    </div>
</footer>

</body>
</html>
