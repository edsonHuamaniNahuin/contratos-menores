<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Software de Licitaciones Públicas Perú — Monitoreo SEACE con IA | Vigilante SEACE</title>
    <meta name="description" content="Software para proveedores del Estado peruano: monitoreo del SEACE, análisis de TDR con IA, proforma de cotización en Word/PDF y reportes en Excel. Solicite una demo de 20 minutos con procesos reales de su rubro.">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:title" content="Software de Licitaciones Públicas Perú — Monitoreo SEACE con IA | Vigilante SEACE">
    <meta property="og:description" content="Monitoreo del SEACE con IA para proveedores del Estado. Solicite una demo con procesos reales de su rubro.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="es_PE">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @unless(app()->environment('production'))
        <meta name="robots" content="noindex, nofollow">
    @endunless
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap">
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
            --bg: #f4f8f6;
            --ink: #0f1b19;
            --muted: #5b6f6a;
            --teal: #0b6e63;
            --teal-deep: #0a3a34;
            --mint: #17b885;
            --mint-2: #0fa46f;
            --mint-soft: #e0f6ec;
            --violet: #7c5cf0;
            --violet-soft: #efeafd;
            --amber: #df9117;
            --amber-soft: #fcf0da;
            --coral: #d94b4b;
            --coral-soft: #fdecec;
            --wa: #1faa57;
            --wa-dark: #19924b;
            --word: #2b579a;
            --excel: #107c41;
            --line: #dce7e2;
        }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
        }
        .font-display { font-family: 'Space Grotesk', sans-serif; }
        .font-data { font-family: 'JetBrains Mono', ui-monospace, monospace; }
        .grad-text {
            background: linear-gradient(100deg, var(--teal) 10%, var(--mint) 90%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .kicker {
            font-family: 'JetBrains Mono', monospace;
            font-size: .68rem;
            letter-spacing: .22em;
            text-transform: uppercase;
            color: var(--teal);
            font-weight: 600;
        }
        .btn-wa {
            display: inline-flex; align-items: center; justify-content: center; gap: .55rem;
            background: var(--wa); color: #fff; font-weight: 700;
            border-radius: 12px; padding: .95rem 1.6rem; font-size: .95rem;
            font-family: 'Space Grotesk', sans-serif;
            box-shadow: 0 12px 28px -12px rgba(31, 170, 87, .55);
            transition: background .15s ease, transform .15s ease;
        }
        .btn-wa:hover { background: var(--wa-dark); transform: translateY(-1px); }
        .btn-teal {
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            background: var(--teal); color: #fff; font-weight: 700;
            border-radius: 12px; padding: .95rem 1.6rem; font-size: .95rem;
            font-family: 'Space Grotesk', sans-serif;
            box-shadow: 0 12px 28px -12px rgba(11, 110, 99, .5);
            transition: background .15s ease, transform .15s ease;
        }
        .btn-teal:hover { background: var(--teal-deep); transform: translateY(-1px); }
        .btn-ghost {
            display: inline-flex; align-items: center; justify-content: center;
            border: 1px solid var(--line); color: var(--ink);
            border-radius: 12px; padding: .95rem 1.6rem; font-size: .95rem; font-weight: 600;
            background: #fff;
            font-family: 'Space Grotesk', sans-serif;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .btn-ghost:hover { border-color: var(--teal); box-shadow: 0 8px 22px -14px rgba(11, 110, 99, .5); }
        .card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 18px;
        }
        .check-chip {
            display: inline-flex; align-items: center; justify-content: center;
            width: 20px; height: 20px; border-radius: 6px; flex-shrink: 0;
            background: var(--mint-soft); color: var(--mint-2); font-weight: 800; font-size: 12px;
        }
        .x-chip {
            display: inline-flex; align-items: center; justify-content: center;
            width: 20px; height: 20px; border-radius: 6px; flex-shrink: 0;
            background: var(--coral-soft); color: var(--coral); font-weight: 800; font-size: 12px;
        }
        .faq-answer { max-height: 0; overflow: hidden; transition: max-height .25s ease; }
        details[open] .faq-answer { max-height: 420px; }
        details summary::-webkit-details-marker { display: none; }
        ::selection { background: #cdeee2; }
        .dot-grid {
            background-image: radial-gradient(rgba(11, 110, 99, .16) 1px, transparent 1px);
            background-size: 22px 22px;
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
    $waLink = 'https://wa.me/' . $waNumber . '?text=' . rawurlencode('Buenas, represento a una empresa proveedora del Estado. Quiero agendar una demo de Vigilante SEACE para ver el software con procesos de nuestro rubro.');
    $clientes = ['ZAVATEC SA', 'STEEL MAQUINARIAS', 'CORPORACIÓN FAMOD', 'ESTUDIO JURÍDICO P&B'];
    $hoy = now()->format('d/m/Y');
@endphp

{{-- ══ HEADER ══ --}}
<header class="sticky top-0 z-50 bg-white/85 backdrop-blur border-b border-[var(--line)]/80">
    <div class="max-w-6xl mx-auto px-6 h-[4.2rem] flex items-center justify-between">
        <a href="{{ url('/') }}" class="flex items-center gap-2.5">
            <span class="w-9 h-9 rounded-[10px] bg-gradient-to-br from-[var(--teal)] to-[var(--mint)] grid place-items-center shadow-sm">
                <svg class="w-[18px] h-[18px] text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
            </span>
            <span class="font-display font-bold text-[1.15rem] tracking-tight text-[var(--ink)]">vigilante<span class="text-[var(--teal)]">.seace</span></span>
        </a>
        <nav class="hidden lg:flex items-center gap-8 text-[14px] text-[var(--muted)]">
            <a href="#software" class="hover:text-[var(--teal)] transition-colors font-medium">El software</a>
            <a href="#entregables" class="hover:text-[var(--teal)] transition-colors font-medium">Entregables</a>
            <a href="#funcionalidades" class="hover:text-[var(--teal)] transition-colors font-medium">Funcionalidades</a>
            <a href="#faq" class="hover:text-[var(--teal)] transition-colors font-medium">Preguntas</a>
        </nav>
        <a href="{{ $waLink }}" target="_blank" rel="noopener" class="btn-teal !px-5 !py-2.5 !text-[13.5px]">
            Agendar demo <span aria-hidden="true">→</span>
        </a>
    </div>
</header>

<main>

{{-- ══ HERO ══ --}}
<section class="relative overflow-hidden">
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div class="absolute -top-32 right-[-8%] w-[480px] h-[480px] rounded-full bg-[var(--mint-soft)]/70 blur-[110px]"></div>
        <div class="absolute top-40 left-[-10%] w-[380px] h-[380px] rounded-full bg-[var(--violet-soft)]/80 blur-[100px]"></div>
        <div class="absolute inset-0 dot-grid opacity-[.5]" style="mask-image:radial-gradient(ellipse 60% 55% at 30% 10%, black, transparent)"></div>
    </div>
    <div class="relative max-w-6xl mx-auto px-6 pt-14 pb-16 lg:pt-20 grid lg:grid-cols-2 gap-14 items-center">
        <div>
            <div class="inline-flex items-center gap-2 mb-6">
                <span class="inline-flex items-center gap-1.5 text-[12px] font-bold text-[var(--violet)] bg-[var(--violet-soft)] rounded-full px-3.5 py-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-[var(--violet)]"></span>
                    Software para proveedores del Estado
                </span>
                <span class="hidden sm:inline-flex items-center text-[11.5px] font-semibold text-[var(--muted)] bg-white border border-[var(--line)] rounded-full px-3 py-1.5">Ley 32069</span>
            </div>
            <h1 class="font-display font-bold text-[2.5rem] sm:text-[3.2rem] leading-[1.03] tracking-tight text-[var(--ink)]">
                Vigilamos el SEACE por su empresa.<br>
                <span class="grad-text">Usted recibe el trabajo listo.</span>
            </h1>
            <p class="mt-6 text-[16.5px] text-[var(--muted)] leading-relaxed max-w-xl">
                Monitoreo de licitaciones que calzan con su rubro, análisis de TDR con IA y documentos listos para presentar: la proforma en Word o PDF y reportes en Excel.
            </p>
            <div class="mt-9 flex flex-col sm:flex-row gap-4">
                <a href="{{ $waLink }}" target="_blank" rel="noopener" class="btn-wa">
                    <svg viewBox="0 0 24 24" width="19" height="19" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    Agendar demo por WhatsApp
                </a>
                <a href="#entregables" class="btn-ghost">Ver lo que recibe</a>
            </div>
            <div class="mt-7 flex flex-wrap items-center gap-x-5 gap-y-2.5 text-[13px] text-[var(--muted)]">
                <span class="inline-flex items-center gap-2">
                    <span class="check-chip">✓</span>
                    Demo con procesos de su rubro
                </span>
                <span class="inline-flex items-center gap-2">
                    <span class="check-chip">✓</span>
                    20 minutos · sin compromiso
                </span>
            </div>
        </div>

        {{-- Entregables reales --}}
        <div class="relative pt-6 lg:pt-0">
            {{-- Excel (atrás) --}}
            <div class="absolute -top-1 -left-2 sm:-left-4 rotate-[-3deg] w-[74%] bg-white border border-[var(--line)] rounded-xl shadow-[0_18px_40px_-24px_rgba(16,124,65,.35)] overflow-hidden z-0">
                <div class="px-4 py-2 flex items-center justify-between" style="background:#107c41">
                    <span class="text-white text-[10.5px] font-bold font-data">Data Seace Analítico.xlsx</span>
                    <span class="text-white/90 text-[9.5px] font-bold bg-white/20 rounded px-1.5 py-0.5">XLSX</span>
                </div>
                <div class="px-3 py-2 overflow-hidden">
                    <table class="w-full text-[8.5px] leading-tight">
                        <thead>
                            <tr style="background:#e6f4ec; color:#107c41">
                                <th class="px-1.5 py-1 text-left font-bold">ENTIDAD</th>
                                <th class="px-1.5 py-1 text-left font-bold">NOMENCLATURA</th>
                                <th class="px-1.5 py-1 text-left font-bold">PROCESO</th>
                                <th class="px-1.5 py-1 text-right font-bold">VALOR</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($procesos->take(3) as $p)
                            <tr class="border-b border-neutral-100">
                                <td class="px-1.5 py-1 text-neutral-700">{{ mb_strtoupper(mb_substr($p->entidad_nombre ?? '', 0, 24)) }}</td>
                                <td class="px-1.5 py-1 text-neutral-500 font-data">{{ $p->nomenclatura }}</td>
                                <td class="px-1.5 py-1 text-neutral-600">{{ mb_strtoupper(mb_substr($p->objeto_contratacion ?? '', 0, 26)) }}</td>
                                <td class="px-1.5 py-1 text-right text-neutral-800 font-semibold">S/ {{ number_format((float) $p->valor_referencial, 0) }}</td>
                            </tr>
                            @empty
                            <tr class="border-b border-neutral-100">
                                <td class="px-1.5 py-1 text-neutral-700">MUNICIPALIDAD DE HUARAZ</td>
                                <td class="px-1.5 py-1 text-neutral-500 font-data">LP-SM-2026-001</td>
                                <td class="px-1.5 py-1 text-neutral-600">PISTAS Y VEREDAS</td>
                                <td class="px-1.5 py-1 text-right text-neutral-800 font-semibold">S/ 1,250,000</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Proforma Word (delante) --}}
            <div class="relative ml-auto w-[86%] rotate-[1.5deg] bg-[#fdfdfd] border border-[var(--line)] rounded-xl shadow-[0_24px_50px_-28px_rgba(43,87,154,.4)] overflow-hidden z-10">
                <div class="px-4 py-2 flex items-center justify-between" style="background:#2b579a">
                    <span class="text-white text-[10.5px] font-bold font-data">Proforma_Técnica_Cotización.docx</span>
                    <span class="text-white/90 text-[9.5px] font-bold bg-white/20 rounded px-1.5 py-0.5">DOCX</span>
                </div>
                <div class="px-5 py-4 text-[11px] leading-snug" style="font-family:Georgia, 'Times New Roman', serif; color:#1a1a1a;">
                    <p class="text-[12.5px] font-bold" style="color:#1a3a5c; border-bottom:2px solid #1a3a5c; padding-bottom:4px;">📋 Proforma Técnica de Cotización</p>
                    <table class="w-full my-2 text-[9.5px]">
                        <tr>
                            <td style="font-weight:bold; width:90px; color:#444;">Empresa:</td>
                            <td><strong>[Su empresa]</strong></td>
                            <td style="font-weight:bold; width:55px; color:#444;">Fecha:</td>
                            <td>{{ $hoy }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight:bold; color:#444;">Rubro:</td>
                            <td>Obras civiles · Vialidad</td>
                            <td style="font-weight:bold; color:#444;">Entidad:</td>
                            <td>{{ mb_strtoupper(mb_substr($demo->entidad_nombre ?? 'ENTIDAD PÚBLICA', 0, 22)) }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight:bold; color:#444;">Proceso:</td>
                            <td colspan="3"><em>{{ mb_strtoupper(mb_substr($demo->objeto_contratacion ?? 'PROCESO', 0, 42)) }}</em></td>
                        </tr>
                    </table>
                    <p class="text-[10.5px] font-bold" style="color:#1a3a5c; margin-bottom:3px;">Tabla de Cotización</p>
                    <table class="w-full border-collapse text-[9px]">
                        <tr style="background:#1a3a5c; color:#fff;">
                            <th style="padding:3px 5px; text-align:left; width:20px;">Ítem</th>
                            <th style="padding:3px 5px; text-align:left;">Descripción</th>
                            <th style="padding:3px 5px; text-align:left; width:40px;">Und</th>
                            <th style="padding:3px 5px; text-align:right; width:38px;">Cant.</th>
                            <th style="padding:3px 5px; text-align:right; width:66px;">P. Unit. S/</th>
                            <th style="padding:3px 5px; text-align:right; width:66px;">Subtotal S/</th>
                        </tr>
                        <tr>
                            <td style="padding:3px 5px;">1</td>
                            <td style="padding:3px 5px;">Cuadrilla de mantenimiento vial</td>
                            <td style="padding:3px 5px;">mes</td>
                            <td style="padding:3px 5px; text-align:right;">2.00</td>
                            <td style="padding:3px 5px; text-align:right;">12,500.00</td>
                            <td style="padding:3px 5px; text-align:right;">25,000.00</td>
                        </tr>
                        <tr style="background:#f5f8fc;">
                            <td style="padding:3px 5px;">2</td>
                            <td style="padding:3px 5px;">Sardinel de concreto f'c=175 kg/cm²</td>
                            <td style="padding:3px 5px;">ml</td>
                            <td style="padding:3px 5px; text-align:right;">80.00</td>
                            <td style="padding:3px 5px; text-align:right;">48.50</td>
                            <td style="padding:3px 5px; text-align:right;">3,880.00</td>
                        </tr>
                        <tr>
                            <td style="padding:3px 5px;">3</td>
                            <td style="padding:3px 5px;">Señalización horizontal termoplástico</td>
                            <td style="padding:3px 5px;">m²</td>
                            <td style="padding:3px 5px; text-align:right;">120.00</td>
                            <td style="padding:3px 5px; text-align:right;">22.00</td>
                            <td style="padding:3px 5px; text-align:right;">2,640.00</td>
                        </tr>
                        <tr style="background:#e8f0fb; font-weight:bold;">
                            <td colspan="5" style="padding:3px 5px; text-align:right;">TOTAL ESTIMADO:</td>
                            <td style="padding:3px 5px; text-align:right;">S/ 31,520.00</td>
                        </tr>
                    </table>
                    <p class="mt-2 text-[8.5px]" style="color:#888;">Viabilidad (IA): proceso compatible con su perfil. Requisito a verificar: residente con CIP vigente. Condiciones: precios con IGV, vigencia de oferta 30 días.</p>
                </div>
            </div>

            {{-- Chip análisis IA --}}
            <div class="absolute -bottom-5 left-2 sm:left-0 rotate-[-1.5deg] bg-white border rounded-xl shadow-lg px-4 py-2.5 z-20 max-w-[250px]" style="border-color:#d9cffb">
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex items-center gap-1.5 text-[10px] font-bold" style="color:#5b3fd6">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2L3 14h7l-1 8 10-12h-7l1-8z"/></svg>
                        Análisis IA del TDR
                    </span>
                </div>
                <div class="flex items-center gap-2.5">
                    <span class="text-[15px] font-extrabold" style="color:#5b3fd6">8.7<span class="text-neutral-400 font-bold text-[11px]">/10</span></span>
                    <div class="flex-1 h-1.5 rounded-full bg-neutral-100 overflow-hidden">
                        <div class="h-full rounded-full" style="width:87%; background:linear-gradient(90deg,#7c5cf0,#17b885)"></div>
                    </div>
                    <span class="text-[10.5px] text-neutral-500">compatible</span>
                </div>
            </div>
            <p class="mt-8 text-center text-[11.5px] text-[var(--muted)]">Formatos reales que el software genera, con un proceso publicado en el SEACE.</p>
        </div>
    </div>
</section>

{{-- ══ CLIENTES ══ --}}
<section class="border-y border-[var(--line)]/70 bg-white/80">
    <div class="max-w-6xl mx-auto px-6 py-8">
        <p class="text-center text-[11px] uppercase tracking-[.18em] font-bold text-[var(--muted)] mb-5">Empresas proveedoras del Estado que ya lo usan</p>
        <div class="flex flex-wrap items-center justify-center gap-x-12 gap-y-3">
            @foreach ($clientes as $i => $cliente)
                <span class="text-[15px] font-display font-semibold tracking-tight" style="color: {{ ['#0b6e63', '#5b3fd6', '#b45309', '#107c41'][$i] }}">
                    {{ $cliente }}
                </span>
            @endforeach
        </div>
    </div>
</section>

{{-- ══ MÉTRICAS (cards con color) ══ --}}
<section class="max-w-6xl mx-auto px-6 py-16">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $metricas = [
                ['v' => $stats['procesos'] > 0 ? number_format($stats['procesos'], 0, ',', '.') : '29,000+', 'l' => 'procesos monitoreados en el SEACE', 'bg' => '#e0f6ec', 'fg' => '#0fa46f'],
                ['v' => $stats['alertas'] > 0 ? number_format($stats['alertas'], 0, ',', '.') : '10,000+', 'l' => 'alertas entregadas a proveedores', 'bg' => '#e9e3fc', 'fg' => '#6a4de0'],
                ['v' => '25', 'l' => 'departamentos del Perú cubiertos', 'bg' => '#fdf0d9', 'fg' => '#c07d0e'],
                ['v' => $stats['monto'] > 0 ? 'S/ ' . number_format($stats['monto'] / 1000000, 1, ',', '.') . ' M' : 'S/ +1,000 M', 'l' => 'en procesos visibles hoy', 'bg' => '#e3f2ea', 'fg' => '#107c41'],
            ];
        @endphp
        @foreach ($metricas as $m)
            <div class="card p-6 relative overflow-hidden">
                <span class="absolute top-0 left-0 right-0 h-1" style="background:linear-gradient(90deg, {{ $m['fg'] }}, transparent)"></span>
                <p class="font-display font-bold text-[1.75rem] tracking-tight" style="color:{{ $m['fg'] }}">{{ $m['v'] }}</p>
                <p class="mt-2 text-[12.5px] text-[var(--muted)] leading-snug">{{ $m['l'] }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- ══ PROBLEMA ══ --}}
<section id="software" class="bg-white border-y border-[var(--line)]/70 overflow-hidden">
    <div class="max-w-6xl mx-auto px-6 py-20 lg:py-24">
        <div class="grid lg:grid-cols-12 gap-10 items-center mb-16">
            <div class="lg:col-span-7">
                <p class="kicker mb-4">El problema que resolvemos</p>
                <h2 class="font-display font-bold text-[2.1rem] sm:text-[2.8rem] leading-[1.06] tracking-tight text-[var(--ink)]">
                    La diferencia entre ganar un proceso<br>y enterarse tarde <span class="grad-text">es de horas.</span>
                </h2>
                <p class="text-[15.5px] text-[var(--muted)] max-w-xl leading-relaxed mt-5">
                    Las entidades publican cientos de convocatorias al día. Buscarlas a mano entre el portal del OSCE, planillas y correos cuesta horas que su equipo no tiene.
                </p>
            </div>
            <div class="lg:col-span-5">
                <div class="relative">
                    <img src="{{ asset('images/landings/obra-grua.jpg') }}" alt="Obra de construcción en ejecución" width="1400" height="933" loading="lazy" class="w-full h-[240px] sm:h-[280px] object-cover rounded-2xl">
                    <div class="absolute inset-0 rounded-2xl ring-1 ring-inset ring-black/10" aria-hidden="true"></div>
                    <div class="absolute bottom-3 left-3 bg-white/95 backdrop-blur rounded-xl px-3.5 py-2 shadow-lg">
                        <p class="text-[11px] font-bold text-[var(--ink)]">El proceso que calza con su empresa</p>
                        <p class="text-[10.5px] text-[var(--muted)]">se publica y cierra mientras usted revisa otra cosa</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="grid sm:grid-cols-3 gap-5">
            @php
                $problemas = [
                    ['t' => 'Horas perdidas revisando', 'd' => 'Buscar procesos relevantes en el SEACE a mano toma varias horas a la semana y aun así hay convocatorias que se escapan.', 'bg' => '#fdecec', 'fg' => '#d94b4b'],
                    ['t' => 'TDR de 20 a 50 páginas', 'd' => 'Cada proceso trae bases extensas. Saber si su empresa califica exige leerlas todas, para cada convocatoria.', 'bg' => '#fdf0d9', 'fg' => '#c07d0e'],
                    ['t' => 'Cotizaciones que se arman tarde', 'd' => 'Armar la proforma en Excel desde cero, con datos a mano, retrasa la presentación y abre la puerta a errores.', 'bg' => '#e9e3fc', 'fg' => '#6a4de0'],
                ];
            @endphp
            @foreach ($problemas as $p2)
                <div class="bg-white border border-[var(--line)] rounded-2xl p-7 shadow-[0_6px_24px_-18px_rgba(15,27,25,.25)]">
                    <span class="inline-flex w-9 h-9 rounded-[10px] items-center justify-center mb-5" style="background:{{ $p2['bg'] }}">
                        <svg class="w-4.5 h-4.5" style="color:{{ $p2['fg'] }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
                    </span>
                    <h3 class="text-[16.5px] font-bold text-[var(--ink)] mb-2.5">{{ $p2['t'] }}</h3>
                    <p class="text-[14px] text-[var(--muted)] leading-relaxed">{{ $p2['d'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══ ENTREGABLES ══ --}}
<section id="entregables" class="relative overflow-hidden">
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div class="absolute top-0 right-[-6%] w-[320px] h-[320px] rounded-full bg-[var(--mint-soft)]/60 blur-[100px]"></div>
    </div>
    <div class="relative max-w-6xl mx-auto px-6 py-20 lg:py-24">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6 mb-14">
            <div class="max-w-2xl">
                <p class="kicker mb-4">Entregables</p>
                <h2 class="font-display font-bold text-[2.1rem] sm:text-[2.8rem] leading-[1.06] tracking-tight text-[var(--ink)]">
                    No recibe una pantalla.<br><span class="grad-text">Recibe documentos listos para usar.</span>
                </h2>
            </div>
            <p class="text-[15px] text-[var(--muted)] max-w-xs leading-relaxed">
                Cada alerta puede terminar en un documento descargable, con los datos del proceso y de su empresa.
            </p>
        </div>
        <div class="grid sm:grid-cols-3 gap-5">
            @php
                $entregables = [
                    ['t' => 'Proforma de cotización', 'f' => 'Word · PDF · Excel', 'd' => 'Cotización con su empresa, rubro, partidas, precios y total. Se genera en minutos y se envía tal cual a la entidad.', 'bar' => '#2b579a', 'soft' => '#e8eff9', 'icon' => '<path d="M7 2.5h8l4 4v15H7z"/><path d="M15 2.5v4h4"/><path d="M9.5 12h6M9.5 15.5h6"/>'],
                    ['t' => 'Reporte en Excel', 'f' => 'Plantilla analítica', 'd' => 'Exporte los procesos de su bandeja a Excel con 30 columnas de análisis comercial: entidad, fechas, valores, ganadores.', 'bar' => '#107c41', 'soft' => '#e3f2ea', 'icon' => '<path d="M6 2.5h12v19H6z"/><path d="M6 7.5h12M6 12.5h12M6 17.5h12M10 2.5v19M14 2.5v19"/><path d="M10 12.5l4-4M14 12.5l-4 4"/>'],
                    ['t' => 'Análisis del TDR', 'f' => 'Informe con IA', 'd' => 'Requisitos, experiencia exigida, penalidades y score de compatibilidad extraídos del documento de bases en segundos.', 'bar' => '#6a4de0', 'soft' => '#e9e3fc', 'icon' => '<path d="M6 2.5h8l4 4v15H6z"/><path d="M14 2.5v4h4"/><circle cx="16.5" cy="15" r="3.2"/><path d="M18.8 17.2l2 2"/>'],
                ];
            @endphp
            @foreach ($entregables as $e)
                <div class="bg-white border border-[var(--line)] rounded-2xl p-7 relative overflow-hidden transition-transform hover:-translate-y-1">
                    <span class="absolute top-0 left-0 right-0 h-1.5" style="background:{{ $e['bar'] }}"></span>
                    <div class="w-12 h-12 rounded-xl grid place-items-center mb-5" style="background:{{ $e['soft'] }}">
                        <svg class="w-[24px] h-[24px]" style="color:{{ $e['bar'] }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">{!! $e['icon'] !!}</svg>
                    </div>
                    <h3 class="text-[16.5px] font-bold text-[var(--ink)] mb-1.5">{{ $e['t'] }}</h3>
                    <p class="font-data text-[10px] font-bold uppercase tracking-wider mb-3" style="color:{{ $e['bar'] }}">{{ $e['f'] }}</p>
                    <p class="text-[14px] text-[var(--muted)] leading-relaxed">{{ $e['d'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══ FUNCIONALIDADES ══ --}}
<section id="funcionalidades" class="bg-white border-y border-[var(--line)]/70">
    <div class="max-w-6xl mx-auto px-6 py-20 lg:py-24">
        <div class="max-w-2xl mb-14">
            <p class="kicker mb-4">Qué incluye</p>
            <h2 class="font-display font-bold text-[2.1rem] sm:text-[2.8rem] leading-[1.06] tracking-tight text-[var(--ink)]">
                Un flujo completo, <span class="grad-text">del aviso a la proforma.</span>
            </h2>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @php
                $features = [
                    ['t' => 'Monitoreo del SEACE 24/7', 'd' => 'Seguimiento continuo de contratos menores y mayores a 8 UIT, con procesos publicados el mismo día (Ley 32069).', 'bg' => '#e0f6ec', 'fg' => '#0fa46f', 'i' => '<circle cx="12" cy="12" r="8"/><path d="M12 7.5V12l3 2.2"/>'],
                    ['t' => 'Filtros por rubro y región', 'd' => 'Solo le llegan procesos que calzan con la actividad de su empresa y las regiones donde opera.', 'bg' => '#e9e3fc', 'fg' => '#6a4de0', 'i' => '<circle cx="12" cy="12" r="8.5"/><circle cx="12" cy="12" r="4.5"/><circle cx="12" cy="12" r="1.1" fill="currentColor" stroke="none"/>'],
                    ['t' => 'Alertas por WhatsApp, Telegram o email', 'd' => 'El aviso llega al canal de su preferencia, con entidad, objeto, monto y plazo de presentación.', 'bg' => '#e3f2ea', 'fg' => '#1faa57', 'i' => '<rect x="3" y="5" width="18" height="12" rx="2.5"/><path d="M7 10h10M7 13.5h6"/><circle cx="18.5" cy="7.5" r="2" fill="currentColor" stroke="none"/>'],
                    ['t' => 'Análisis de TDR con IA', 'd' => 'La IA lee los documentos y extrae requisitos técnicos, experiencia exigida, penalidades y plazos.', 'bg' => '#fdf0d9', 'fg' => '#c07d0e', 'i' => '<path d="M6 2.5h8l4 4v15H6z"/><path d="M14 2.5v4h4"/><path d="M12.2 10.2l1.6 3.2 3.2 1.6-3.2 1.6-1.6 3.2-1.6-3.2-3.2-1.6 3.2-1.6z"/>'],
                    ['t' => 'Score de compatibilidad', 'd' => 'Cada proceso con una nota de 0 a 10 según el perfil de su empresa. Prioriza donde tiene opción real.', 'bg' => '#fdecec', 'fg' => '#d94b4b', 'i' => '<path d="M4.5 16.5a7.5 7.5 0 0115 0"/><path d="M12 16.5V9"/><circle cx="12" cy="16.5" r="1.4" fill="currentColor" stroke="none"/>'],
                    ['t' => 'Proforma de cotización', 'd' => 'Genera la cotización en Word, PDF o Excel con los costos de su empresa, lista para presentar.', 'bg' => '#e8eff9', 'fg' => '#2b579a', 'i' => '<path d="M6 2.5h8l4 4v15H6z"/><path d="M14 2.5v4h4"/><path d="M9 12.5h6M9 16h6"/><path d="M9 19h6"/>'],
                ];
            @endphp
            @foreach ($features as $f)
                <div class="bg-white border border-[var(--line)] rounded-2xl p-7 transition-all hover:-translate-y-1 hover:shadow-[0_16px_36px_-22px_rgba(15,27,25,.3)]">
                    <div class="w-11 h-11 rounded-xl grid place-items-center mb-5" style="background:{{ $f['bg'] }}">
                        <svg class="w-[22px] h-[22px]" style="color:{{ $f['fg'] }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $f['i'] !!}</svg>
                    </div>
                    <h3 class="text-[16px] font-bold text-[var(--ink)] mb-2">{{ $f['t'] }}</h3>
                    <p class="text-[14px] text-[var(--muted)] leading-relaxed">{{ $f['d'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══ BANDA FOTOGRÁFICA ══ --}}
<section class="relative overflow-hidden">
    <img src="{{ asset('images/landings/equipo-obra-casco.jpg') }}" alt="Equipo de ingeniería y construcción revisando avance de obra" width="1400" height="788" loading="lazy" class="absolute inset-0 w-full h-full object-cover">
    <div class="absolute inset-0" style="background:linear-gradient(105deg, rgba(10,58,52,.92) 15%, rgba(11,110,99,.72) 55%, rgba(14,140,125,.35) 100%)"></div>
    <div class="relative max-w-5xl mx-auto px-6 py-20 lg:py-24">
        <div class="max-w-2xl">
            <p class="kicker !text-[#8ee6c8] mb-5">En obra y en licitación</p>
            <h2 class="font-display font-bold text-white text-[2rem] sm:text-[2.6rem] leading-[1.07] tracking-tight">
                Su equipo está en la obra.<br>¿Quién revisa las convocatorias?
            </h2>
            <p class="mt-5 text-white/85 text-[16px] leading-relaxed max-w-xl">
                El software trabaja mientras su gente trabaja: monitorea, filtra y deja cada proceso con su análisis listo. Usted decide en qué postular cuando llega a la oficina.
            </p>
        </div>
    </div>
</section>

{{-- ══ LA DEMO + FORMULARIO ══ --}}
<section class="relative overflow-hidden" style="background:linear-gradient(135deg, #0a3a34, #0b6e63 60%, #0e8c7d)">
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div class="absolute -top-24 right-[-8%] w-[380px] h-[380px] rounded-full bg-white/5 blur-[90px]"></div>
        <div class="absolute bottom-[-30%] left-[-6%] w-[360px] h-[360px] rounded-full bg-[var(--mint)]/15 blur-[100px]"></div>
    </div>
    <div class="relative max-w-6xl mx-auto px-6 py-20 lg:py-24 grid lg:grid-cols-2 gap-14 items-center">
        <div class="text-white">
            <p class="kicker !text-[#8ee6c8] mb-5">Agende una demo</p>
            <h2 class="font-display font-bold text-white text-[2rem] sm:text-[2.7rem] leading-[1.06] tracking-tight mb-6">
                Vea el software con los procesos<br>de su empresa.
            </h2>
            <p class="text-white/80 text-[16px] leading-relaxed max-w-lg mb-8">
                En 20 minutos configuramos los rubros y regiones de su empresa, buscamos procesos reales que calzan con su actividad, generamos una proforma y usted recorre el panel. Sin diapositivas y sin compromiso.
            </p>
            <ul class="space-y-3.5">
                @php
                    $demoList = [
                        'Configuración de su perfil de empresa y rubros.',
                        'Búsqueda en vivo de procesos vigentes de su sector.',
                        'Análisis de un TDR real: requisitos, experiencia y score.',
                        'Generación de una proforma de cotización en minutos.',
                        'Planes y costos claros, con facturación incluida.',
                    ];
                @endphp
                @foreach ($demoList as $item)
                    <li class="flex gap-3 text-[15px] text-white leading-relaxed">
                        <span class="check-chip !bg-white/15 !text-[#8ee6c8]">✓</span>
                        <span>{{ $item }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="mt-9 rounded-2xl overflow-hidden ring-1 ring-white/20 shadow-2xl max-w-lg">
                <img src="{{ asset('images/manual/dashboard-mis-procesos.png') }}" alt="Panel de Vigilante SEACE con los procesos de su empresa" width="1200" height="720" loading="lazy" class="w-full h-auto">
                <div class="bg-white/10 backdrop-blur px-4 py-2.5 text-[12px] text-white/80">Panel real de Vigilante SEACE — sus procesos, seguimientos y alertas en un solo lugar.</div>
            </div>
        </div>

        <div class="bg-white rounded-3xl p-7 sm:p-9 shadow-2xl">
            <p class="kicker mb-3">Demo de 20 minutos</p>
            <h3 class="font-display font-bold text-[1.6rem] leading-tight tracking-tight text-[var(--ink)] mb-2">
                Coordine su demo por WhatsApp o correo.
            </h3>
            <p class="text-[14px] text-[var(--muted)] leading-relaxed mb-6">
                Sin compromiso: si el software no encaja con su empresa, se lo decimos en la misma llamada.
            </p>

            <a href="{{ $waLink }}" target="_blank" rel="noopener" class="btn-wa w-full">
                <svg viewBox="0 0 24 24" width="19" height="19" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                Agendar por WhatsApp
            </a>
            <p class="text-center text-[12.5px] text-[var(--muted)] mt-2.5 mb-5">+51 918 874 873 · horario de oficina</p>

            <div class="flex items-center gap-3 text-[10.5px] uppercase tracking-widest text-neutral-400 mb-5">
                <span class="h-px flex-1 bg-neutral-200"></span>
                o por correo
                <span class="h-px flex-1 bg-neutral-200"></span>
            </div>

            @if (session('ok'))
                <div class="bg-[var(--mint-soft)] border border-[var(--mint)]/40 text-[var(--teal-deep)] text-[13.5px] leading-relaxed rounded-xl px-4 py-3 mb-4">
                    {{ session('ok') }}
                </div>
            @endif
            @if (session('error'))
                <div class="bg-[var(--coral-soft)] border border-[var(--coral)]/30 text-[var(--coral)] text-[13.5px] leading-relaxed rounded-xl px-4 py-3 mb-4">{{ session('error') }}</div>
            @endif
            <form id="form-correo" method="POST" action="{{ route('landing.agendar-demo') }}">
                @csrf
                <input type="hidden" name="landing" value="software-licitaciones">

                <div class="absolute left-[-9999px] top-[-9999px] opacity-0 pointer-events-none" aria-hidden="true">
                    <label>No llenar este campo <input type="text" name="empresa_web" tabindex="-1" autocomplete="off"></label>
                </div>

                <div class="space-y-3.5">
                    <input class="campo-correo w-full px-4 py-3 rounded-xl border text-[14px] placeholder-neutral-400 focus:outline-none focus:ring-2 transition-colors" style="border-color:var(--line); color:var(--ink); --tw-ring-color:var(--teal)" type="text" name="nombre" required value="{{ old('nombre') }}" placeholder="Nombre completo">
                    @error('nombre')<p class="text-[12px]" style="color:var(--coral)">{{ $message }}</p>@enderror

                    <input class="campo-correo w-full px-4 py-3 rounded-xl border text-[14px] placeholder-neutral-400 focus:outline-none focus:ring-2 transition-colors" style="border-color:var(--line); color:var(--ink)" type="email" name="email" required value="{{ old('email') }}" placeholder="Correo corporativo">
                    @error('email')<p class="text-[12px]" style="color:var(--coral)">{{ $message }}</p>@enderror

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <input class="campo-correo w-full px-4 py-3 rounded-xl border text-[14px] placeholder-neutral-400 focus:outline-none focus:ring-2 transition-colors" style="border-color:var(--line); color:var(--ink)" type="text" name="empresa" value="{{ old('empresa') }}" placeholder="Empresa">
                        <input class="campo-correo w-full px-4 py-3 rounded-xl border text-[14px] placeholder-neutral-400 focus:outline-none focus:ring-2 transition-colors" style="border-color:var(--line); color:var(--ink)" type="text" name="rubro" value="{{ old('rubro') }}" placeholder="Rubro (ej. obras viales)">
                    </div>

                    <div>
                        <label class="block text-[13px] font-semibold mb-1.5" style="color:var(--muted)">¿Cuánto es {{ $demoCaptchaA ?? 7 }} + {{ $demoCaptchaB ?? 3 }}?</label>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <input class="campo-correo w-full sm:flex-1 px-4 py-3 rounded-xl border text-[14px] placeholder-neutral-400 focus:outline-none focus:ring-2 transition-colors" style="border-color:var(--line); color:var(--ink)" type="text" name="captcha" required inputmode="numeric" autocomplete="off" placeholder="Respuesta">
                            <button type="submit" class="btn-teal w-full sm:w-auto !px-6 !py-3 whitespace-nowrap">
                                Enviar solicitud
                            </button>
                        </div>
                        <p class="text-[11px] text-neutral-400 mt-1.5">Verificación anti-robot.</p>
                        @error('captcha')<p class="text-[12px]" style="color:var(--coral)">{{ $message }}</p>@enderror
                        @error('form')<p class="text-[12px]" style="color:var(--coral)">{{ $message }}</p>@enderror
                    </div>
                    <p class="text-[11.5px] text-center text-neutral-400 leading-relaxed">Sin spam. Solo usamos tu correo para coordinar la reunión.</p>
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
        <h2 class="font-display font-bold text-[2rem] sm:text-[2.5rem] leading-[1.06] tracking-tight text-[var(--ink)]">Antes de la demo</h2>
    </div>
    <div class="divide-y divide-[var(--line)] bg-white border border-[var(--line)] rounded-2xl px-7 shadow-[0_10px_30px_-24px_rgba(15,27,25,.3)]">
        @php
            $faqs = [
                ['q' => '¿Es una plataforma web o hay que instalar algo?', 'a' => 'Es una plataforma 100% web. Se accede desde el navegador de una computadora, tablet o celular. Las alertas llegan por WhatsApp, Telegram o correo, de modo que no es necesario tener la página abierta para recibirlas.'],
                ['q' => '¿La demo tiene algún costo o compromiso?', 'a' => 'No. Son 20 minutos por videollamada o teléfono. Configuramos el perfil con los rubros de su empresa, buscamos procesos reales de su sector y usted recorre el panel. Si no le sirve, no pasa nada.'],
                ['q' => '¿Qué diferencia hay con el buscador del OSCE?', 'a' => 'El buscador del OSCE es un portal de consulta: usted abre, busca y filtra a mano. Vigilante SEACE es un software que trabaja por su empresa: monitorea el SEACE, filtra por rubro y región, analiza el TDR con IA, entrega un score de compatibilidad y genera la proforma de cotización.'],
                ['q' => '¿Los documentos que genera son editables?', 'a' => 'Sí. La proforma se descarga en Word, PDF o Excel, y el reporte de procesos en Excel con plantilla analítica. Ambos los puede ajustar su equipo antes de presentarlos.'],
                ['q' => '¿Con qué tipos de procesos trabaja?', 'a' => 'Con contratos menores a 8 UIT y contratos mayores a 8 UIT bajo la Ley 32069: licitaciones públicas, subastas inversas y otras modalidades publicadas en el SEACE.'],
                ['q' => '¿Cuánto cuesta?', 'a' => 'El plan Premium cuesta S/ 49 al mes e incluye alertas, análisis de TDR con IA, score de compatibilidad y proformas. El plan con Contratos Mayores cuesta S/ 68 al mes. En la demo vemos cuál se ajusta a la actividad de su empresa.'],
                ['q' => '¿Sirve para el rubro de nuestra empresa?', 'a' => 'Si su empresa provee bienes, servicios, consultoría u obras al Estado, sí. En la demo configuramos sus rubros exactos y vemos en vivo cuántos procesos hay hoy que calcen con ellos.'],
                ['q' => '¿Cómo se factura y se paga?', 'a' => 'Emitimos comprobante electrónico (boleta o factura). El pago se realiza con tarjeta Visa o Mastercard, o por Yape. El cobro es mensual y la cancelación se hace desde su panel, sin permanencia.'],
            ];
        @endphp
        @foreach ($faqs as $i => $faq)
            <details class="group py-5" {{ $i === 0 ? 'open' : '' }}>
                <summary class="flex items-center justify-between gap-6 cursor-pointer list-none select-none">
                    <span class="text-[15.5px] font-semibold tracking-tight text-[var(--ink)]">{{ $faq['q'] }}</span>
                    <span class="w-7 h-7 rounded-full grid place-items-center shrink-0 transition-transform duration-200 group-open:rotate-45" style="background:var(--mint-soft); color:var(--mint-2)">
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
<section class="relative overflow-hidden" style="background:linear-gradient(120deg, #0a3a34, #0b6e63 55%, #0e8c7d)">
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[600px] h-[340px] rounded-full bg-[var(--mint)]/15 blur-[110px]"></div>
        <div class="absolute inset-0 dot-grid opacity-20" style="background-image:radial-gradient(rgba(255,255,255,.25) 1px, transparent 1px)"></div>
    </div>
    <div class="relative max-w-3xl mx-auto px-6 py-24 text-center text-white">
        <p class="kicker !text-[#8ee6c8] mb-6">Agende su demo</p>
        <h2 class="font-display font-bold text-white text-[2.3rem] sm:text-[3rem] leading-[1.05] tracking-tight mb-6">
            La próxima convocatoria de su rubro<br>
            <span class="text-[#8ee6c8]">puede publicarse esta semana.</span>
        </h2>
        <p class="text-white/80 text-[16.5px] leading-relaxed max-w-xl mx-auto mb-10">
            Una demo de 20 minutos con procesos reales de su sector es suficiente para saber si este software le sirve a su empresa.
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ $waLink }}" target="_blank" rel="noopener" class="btn-wa !px-10 !py-4 !text-[16px]">
                Agendar demo por WhatsApp
            </a>
        </div>
        <p class="text-white/60 text-[13.5px] mt-5 font-data">+51 918 874 873 · sin compromiso · 20 minutos</p>
    </div>
</section>

</main>

{{-- ══ FOOTER ══ --}}
<footer class="bg-[#0c1513] text-white">
    <div class="max-w-6xl mx-auto px-6 py-12 flex flex-col lg:flex-row items-center justify-between gap-6">
        <div class="flex items-center gap-2.5">
            <span class="w-9 h-9 rounded-[10px] bg-gradient-to-br from-[var(--teal)] to-[var(--mint)] grid place-items-center">
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
