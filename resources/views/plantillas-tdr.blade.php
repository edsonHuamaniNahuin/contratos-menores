<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Modelo de TDR en Word para Contrataciones del Estado — Plantilla Gratis | Vigilante SEACE</title>
    <meta name="description" content="Descarga gratis un modelo de TDR (Términos de Referencia) editable en Word, con la estructura que usan las entidades del Estado peruano: objeto, especificaciones, requisitos, plazo y penalidades.">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:title" content="Modelo de TDR en Word — Plantilla para Contrataciones del Estado | Vigilante SEACE">
    <meta property="og:description" content="Descarga gratis un modelo de TDR editable en Word con la estructura de las entidades del Estado peruano.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="es_PE">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @unless(app()->environment('production'))
        <meta name="robots" content="noindex, nofollow">
    @endunless
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;1,400&family=Inter:wght@400;500;600;700&display=swap">
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
            --paper: #fbfaf6;
            --ink: #1c1917;
            --muted: #6b6359;
            --teal: #0e6b5f;
            --teal-deep: #07453d;
            --amber: #d97706;
            --line: #e7e0d3;
            --doc-blue: #2b579a;
        }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--paper);
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
        }
        .font-serif { font-family: 'Lora', Georgia, serif; }
        .kicker {
            font-size: .72rem; letter-spacing: .18em; text-transform: uppercase;
            font-weight: 700; color: var(--teal);
        }
        .btn-descarga {
            display: inline-flex; align-items: center; justify-content: center; gap: .6rem;
            background: var(--teal); color: #fff; font-weight: 700;
            border-radius: 12px; padding: 1rem 2rem; font-size: 1rem;
            box-shadow: 0 14px 30px -14px rgba(14, 107, 95, .55);
            transition: background .15s ease, transform .15s ease;
        }
        .btn-descarga:hover { background: var(--teal-deep); transform: translateY(-1px); }
        .doc-shadow { box-shadow: 0 30px 70px -30px rgba(28, 25, 23, .45); }
        .faq-answer { max-height: 0; overflow: hidden; transition: max-height .25s ease; }
        details[open] .faq-answer { max-height: 420px; }
        details summary::-webkit-details-marker { display: none; }
        ::selection { background: #d8ebe5; }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            * { transition: none !important; }
        }
    </style>

@include('partials.meta-pixel', ['embudo' => 'LM-TDR'])
</head>
<body>

@php
    $waNumber = '51918874873';
@endphp

{{-- ══ HEADER ══ --}}
<header class="border-b border-[var(--line)] bg-white/85 backdrop-blur sticky top-0 z-50">
    <div class="max-w-5xl mx-auto px-6 h-16 flex items-center justify-between">
        <a href="{{ url('/') }}" class="flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg bg-[var(--teal)] grid place-items-center text-white text-sm font-bold">V</span>
            <span class="font-bold text-[15px] tracking-tight text-[var(--ink)]">Vigilante SEACE</span>
        </a>
        <a href="{{ url('/buscador-contratos-mayores') }}" class="text-[13.5px] font-medium text-[var(--muted)] hover:text-[var(--teal)] transition-colors hidden sm:inline">
            Ver licitaciones vigentes →
        </a>
    </div>
</header>

<main>

{{-- ══ HERO ══ --}}
<section class="relative overflow-hidden">
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div class="absolute -top-32 right-[-6%] w-[420px] h-[420px] rounded-full bg-[#dcefe9]/60 blur-[110px]"></div>
        <div class="absolute top-1/2 left-[-8%] w-[300px] h-[300px] rounded-full bg-[#f6e9cd]/70 blur-[100px]"></div>
    </div>
    <div class="relative max-w-5xl mx-auto px-6 pt-16 pb-14 grid lg:grid-cols-2 gap-12 items-center">
        <div>
            <p class="kicker mb-5">Plantilla gratis · Word editable</p>
            <h1 class="font-serif font-semibold text-[2.3rem] sm:text-[3rem] leading-[1.08] tracking-tight text-[var(--ink)]">
                Modelo de TDR listo para editar,<br>
                <em class="text-[var(--teal)]">no para empezar de cero.</em>
            </h1>
            <p class="mt-5 text-[16px] text-[var(--muted)] leading-relaxed max-w-lg">
                Descarga un modelo de Términos de Referencia en Word con la estructura que usan las entidades del Estado: antecedentes, objeto, especificaciones, requisitos, plazo, pago y penalidades. Solo reemplaza lo que está entre corchetes.
            </p>
            <div class="mt-7 flex flex-wrap gap-x-6 gap-y-2 text-[13.5px] text-[var(--muted)]">
                <span class="inline-flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-[18px] h-[18px] rounded-[5px] bg-[#e0f4ee] text-[var(--teal)] font-bold text-[11px]">✓</span>
                    Formato .doc compatible con Word
                </span>
                <span class="inline-flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-[18px] h-[18px] rounded-[5px] bg-[#e0f4ee] text-[var(--teal)] font-bold text-[11px]">✓</span>
                    Estructura alineada al SEACE
                </span>
                <span class="inline-flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-[18px] h-[18px] rounded-[5px] bg-[#e0f4ee] text-[var(--teal)] font-bold text-[11px]">✓</span>
                    Sin registro en la app
                </span>
            </div>
        </div>

        {{-- Vista previa del documento --}}
        <div class="relative mx-auto w-full max-w-md">
            <div class="doc-shadow rounded-xl overflow-hidden bg-white border border-[var(--line)]">
                <div class="px-4 py-2 flex items-center justify-between" style="background:var(--doc-blue)">
                    <span class="text-white text-[11px] font-semibold">modelo-tdr-contrataciones-estado.doc</span>
                    <span class="text-white/90 text-[10px] font-bold bg-white/20 rounded px-1.5 py-0.5">WORD</span>
                </div>
                <div class="px-7 py-6 text-[11px] leading-relaxed" style="font-family:Georgia, 'Times New Roman', serif; color:#1a1a1a;">
                    <p class="text-[13px] font-bold" style="color:#1a3a5c; border-bottom:2px solid #1a3a5c; padding-bottom:4px;">TÉRMINOS DE REFERENCIA</p>
                    <p class="mt-2 text-[10px]">Entidad: [RAZÓN SOCIAL DE LA ENTIDAD]</p>
                    <p class="text-[10px]">Objeto: [Descripción del servicio o bien]</p>
                    <p class="mt-3 text-[10.5px] font-bold" style="color:#1a3a5c;">1. Antecedentes</p>
                    <p class="text-[9.5px]" style="color:#666;">[Contexto que origina la contratación...]</p>
                    <p class="mt-2 text-[10.5px] font-bold" style="color:#1a3a5c;">2. Objeto de la contratación</p>
                    <p class="text-[9.5px]" style="color:#666;">[Bien, servicio o consultoría requerida...]</p>
                    <p class="mt-2 text-[10.5px] font-bold" style="color:#1a3a5c;">3. Especificaciones</p>
                    <table class="w-full border-collapse mt-1 text-[9px]">
                        <tr style="background:#1a3a5c; color:#fff;">
                            <th style="padding:2.5px 5px; text-align:left;">Ítem</th>
                            <th style="padding:2.5px 5px; text-align:left;">Descripción</th>
                            <th style="padding:2.5px 5px; text-align:left;">Und</th>
                        </tr>
                        <tr><td style="border:1px solid #ddd; padding:2.5px 5px;">1</td><td style="border:1px solid #ddd; padding:2.5px 5px; color:#666;">[Actividad]</td><td style="border:1px solid #ddd; padding:2.5px 5px;">[glb]</td></tr>
                        <tr><td style="border:1px solid #ddd; padding:2.5px 5px;">2</td><td style="border:1px solid #ddd; padding:2.5px 5px; color:#666;">[Actividad]</td><td style="border:1px solid #ddd; padding:2.5px 5px;">[mes]</td></tr>
                    </table>
                    <p class="mt-3 text-[10.5px] font-bold" style="color:#1a3a5c;">4. Requisitos del proveedor</p>
                    <p class="text-[9.5px]" style="color:#666;">• RNP [especialidad/categoría] · [N] años de experiencia</p>
                    <p class="mt-3 text-[10.5px] font-bold" style="color:#1a3a5c;">5. Plazo y forma de pago</p>
                    <p class="text-[9.5px]" style="color:#666;">[N] días calendario · pago contra conformidad</p>
                </div>
            </div>
            <div class="absolute -bottom-4 left-1/2 -translate-x-1/2 bg-[var(--teal)] text-white text-[12px] font-semibold rounded-full px-5 py-2 shadow-lg whitespace-nowrap">
                10 apartados · listo para editar
            </div>
        </div>
    </div>
</section>

{{-- ══ CAPTURA ══ --}}
<section class="bg-white border-y border-[var(--line)]">
    <div class="max-w-3xl mx-auto px-6 py-16 text-center">
        @if (session('ok'))
            <div class="bg-[#e0f4ee] border border-[var(--teal)]/30 text-[var(--teal-deep)] text-[15px] font-semibold rounded-2xl px-6 py-5 mb-6">
                ✅ Listo, {{ old('nombre', '') }}. Descarga tu modelo:
            </div>
            <a href="{{ route('plantillas-tdr.descargar') }}" class="btn-descarga !text-[1.05rem]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 4v12m0 0l-4-4m4 4l4-4"/></svg>
                Descargar modelo de TDR (.doc)
            </a>
            <p class="mt-4 text-[13px] text-[var(--muted)]">También te enviamos el enlace a tu correo.</p>
        @else
            <p class="kicker mb-4">Descarga gratuita</p>
            <h2 class="font-serif font-semibold text-[1.9rem] sm:text-[2.4rem] tracking-tight text-[var(--ink)] mb-3">
                Deja tu correo y descárgalo al instante.
            </h2>
            <p class="text-[15px] text-[var(--muted)] mb-8 max-w-md mx-auto leading-relaxed">
                Sin registro, sin costo. Solo necesitamos tu correo para enviarte el archivo y, si quieres, avisarte de licitaciones de tu rubro.
            </p>

            @error('form')
                <div class="bg-red-50 border border-red-300/50 text-red-600 text-[13px] rounded-xl px-4 py-3 mb-4 inline-block">{{ $message }}</div>
            @enderror
            <form id="form-tdr" method="POST" action="{{ route('plantillas-tdr.capturar') }}" class="max-w-md mx-auto text-left">
                @csrf
                <div class="absolute left-[-9999px] top-[-9999px] opacity-0 pointer-events-none" aria-hidden="true">
                    <label>No llenar este campo <input type="text" name="empresa_web" tabindex="-1" autocomplete="off"></label>
                </div>
                <div class="space-y-3.5">
                    <div>
                        <label class="block text-[13px] font-semibold text-[var(--ink)] mb-1.5">Nombre</label>
                        <input class="campo-tdr w-full px-4 py-3 rounded-xl border text-[14px] placeholder-neutral-400 focus:outline-none focus:ring-2 transition-colors" style="border-color:var(--line); color:var(--ink)" type="text" name="nombre" required value="{{ old('nombre') }}" placeholder="Tu nombre">
                        @error('nombre')<p class="text-[12px] text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-[13px] font-semibold text-[var(--ink)] mb-1.5">Correo electrónico</label>
                        <input class="campo-tdr w-full px-4 py-3 rounded-xl border text-[14px] placeholder-neutral-400 focus:outline-none focus:ring-2 transition-colors" style="border-color:var(--line); color:var(--ink)" type="email" name="email" required value="{{ old('email') }}" placeholder="tu@correo.com">
                        @error('email')<p class="text-[12px] text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-[13px] font-semibold mb-1.5" style="color:var(--muted)">¿Cuánto es {{ $demoCaptchaA ?? 7 }} + {{ $demoCaptchaB ?? 3 }}?</label>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <input class="campo-tdr w-full sm:flex-1 px-4 py-3 rounded-xl border text-[14px] placeholder-neutral-400 focus:outline-none focus:ring-2 transition-colors" style="border-color:var(--line); color:var(--ink)" type="text" name="captcha" required inputmode="numeric" autocomplete="off" placeholder="Respuesta">
                            <button type="submit" class="btn-descarga w-full sm:w-auto !px-6 !py-3 whitespace-nowrap">
                                Descargar ahora
                            </button>
                        </div>
                        <p class="text-[11px] text-neutral-400 mt-1.5">Verificación anti-robot.</p>
                        @error('captcha')<p class="text-[12px] text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <p class="text-[11.5px] text-center text-neutral-400 leading-relaxed">Sin spam. Solo usamos tu correo para enviarte la plantilla.</p>
                </div>
            </form>
        @endif
    </div>
</section>

{{-- ══ QUÉ INCLUYE ══ --}}
<section class="max-w-5xl mx-auto px-6 py-16">
    <div class="max-w-2xl mb-10">
        <p class="kicker mb-4">Qué incluye el modelo</p>
        <h2 class="font-serif font-semibold text-[1.9rem] sm:text-[2.3rem] tracking-tight text-[var(--ink)]">
            La estructura que esperan ver las entidades.
        </h2>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @php
            $secciones = [
                ['t' => 'Antecedentes y finalidad', 'd' => 'Contexto que justifica la contratación y el beneficio público esperado.'],
                ['t' => 'Objeto y especificaciones', 'd' => 'Descripción del bien o servicio con tabla editable de ítems, unidades y cantidades.'],
                ['t' => 'Requisitos del proveedor', 'd' => 'RNP, experiencia, personal clave y documentación requerida.'],
                ['t' => 'Plazo, lugar y pagos', 'd' => 'Cronograma de trabajo, forma de pago y condiciones económicas.'],
                ['t' => 'Penalidades', 'd' => 'Cláusula de penalidad por atraso ajustada a la normativa vigente.'],
                ['t' => 'Anexos', 'd' => 'Estructura para propuesta económica, cronograma y formularios de calificación.'],
            ];
        @endphp
        @foreach ($secciones as $s)
            <div class="bg-white border border-[var(--line)] rounded-2xl p-6">
                <h3 class="text-[15.5px] font-bold text-[var(--ink)] mb-2">{{ $s['t'] }}</h3>
                <p class="text-[13.5px] text-[var(--muted)] leading-relaxed">{{ $s['d'] }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- ══ FAQ ══ --}}
<section id="faq" class="bg-white border-t border-[var(--line)]">
    <div class="max-w-2xl mx-auto px-6 py-16">
        <div class="mb-10 text-center">
            <p class="kicker mb-4">Preguntas frecuentes</p>
            <h2 class="font-serif font-semibold text-[1.8rem] sm:text-[2.2rem] tracking-tight text-[var(--ink)]">Sobre la plantilla</h2>
        </div>
        <div class="divide-y divide-[var(--line)] border-y border-[var(--line)] px-6">
            @php
                $faqs = [
                    ['q' => '¿La plantilla es gratis?', 'a' => 'Sí, 100% gratis. Dejas tu correo para que te enviemos el enlace y puedes descargarla al instante. No necesitas crear cuenta.'],
                    ['q' => '¿En qué formato se descarga?', 'a' => 'En formato .doc, compatible con Microsoft Word, Google Docs y LibreOffice. Edítalo como cualquier documento.'],
                    ['q' => '¿Sirve para cualquier tipo de contratación?', 'a' => 'Es un modelo base orientado a servicios, consultorías y obras. Cada entidad publica sus propios requisitos en la convocatoria; usa este modelo como estructura y adáptalo a las bases del proceso real.'],
                    ['q' => '¿Está actualizado con la Ley 32069?', 'a' => 'La estructura del modelo es compatible con los TDR que se publican bajo la nueva Ley de Contrataciones del Estado (Ley 32069). Siempre verifica los requisitos específicos en la convocatoria a la que postules.'],
                ];
            @endphp
            @foreach ($faqs as $i => $faq)
                <details class="group py-5" {{ $i === 0 ? 'open' : '' }}>
                    <summary class="flex items-center justify-between gap-6 cursor-pointer list-none select-none">
                        <span class="text-[15px] font-semibold tracking-tight text-[var(--ink)]">{{ $faq['q'] }}</span>
                        <span class="font-serif text-[1.4rem] leading-none text-[var(--teal)] transition-transform duration-200 group-open:rotate-45" aria-hidden="true">+</span>
                    </summary>
                    <div class="faq-answer">
                        <p class="pt-3 text-[14px] text-[var(--muted)] leading-relaxed">{{ $faq['a'] }}</p>
                    </div>
                </details>
            @endforeach
        </div>
    </div>
</section>

{{-- ══ CTA FINAL ══ --}}
<section class="bg-[var(--teal-deep)] text-white">
    <div class="max-w-2xl mx-auto px-6 py-16 text-center">
        <p class="kicker !text-[#9fdccb] mb-4">¿También vendes al Estado?</p>
        <h2 class="font-serif font-semibold text-white text-[1.8rem] sm:text-[2.2rem] tracking-tight mb-4">
            Te avisamos cuando se publique una licitación de tu rubro.
        </h2>
        <p class="text-white/80 text-[14.5px] leading-relaxed max-w-lg mx-auto mb-7">
            Vigilante SEACE monitorea el SEACE y te avisa por WhatsApp el mismo día. Sin costo por recibir avisos de tu rubro. WhatsApp está temporalmente deshabilitado desde el 1 de octubre de 2026.
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode('Hola, descargué el modelo de TDR y quiero que me avisen de licitaciones de mi rubro.') }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 bg-white text-[var(--teal-deep)] hover:bg-neutral-50 font-bold text-[15px] px-8 py-3.5 rounded-xl transition-colors">
                Quiero avisos de mi rubro
            </a>
            <a href="{{ url('/licitaciones-vigentes') }}" class="inline-flex items-center justify-center border border-white/30 text-white font-semibold text-[14.5px] px-7 py-3.5 rounded-xl hover:bg-white/10 transition-colors">
                Ver licitaciones vigentes
            </a>
        </div>
        <div class="mt-7 max-w-lg mx-auto text-left">
            @include('partials.nota-whatsapp')
        </div>
    </div>
</section>

</main>

{{-- ══ FOOTER ══ --}}
<footer class="bg-[#f4f2ec] border-t border-[var(--line)]">
    <div class="max-w-5xl mx-auto px-6 py-8 flex flex-col sm:flex-row items-center justify-between gap-4">
        <p class="text-[12.5px] text-[var(--muted)]">© {{ date('Y') }} Sunqupacha S.A.C. · Vigilante SEACE · licitacionesmype.pe</p>
        <div class="flex items-center gap-5 text-[12.5px] text-[var(--muted)]">
            <a href="{{ route('legal.politica-privacidad') }}" class="hover:text-[var(--ink)] underline underline-offset-2">Privacidad</a>
            <a href="{{ route('legal.condiciones-servicio') }}" class="hover:text-[var(--ink)] underline underline-offset-2">Condiciones</a>
            <a href="{{ route('contacto') }}" class="hover:text-[var(--ink)] underline underline-offset-2">Contacto</a>
        </div>
    </div>
</footer>

<script>
(function () {
    var t0 = null;
    document.querySelectorAll('#form-tdr .campo-tdr').forEach(function (el) {
        el.addEventListener('focus', function () { if (t0 === null) { t0 = Date.now(); } });
    });
    document.getElementById('form-tdr') && document.getElementById('form-tdr').addEventListener('submit', function () {
        if (t0 !== null) {
            var h = document.createElement('input');
            h.type = 'hidden';
            h.name = 'tiempo_llenado_ms';
            h.value = String(Date.now() - t0);
            this.appendChild(h);
        }
    });
    if (typeof gtag === 'function') {
        document.querySelectorAll('a[href*="wa.me/"]').forEach(function (el) {
            el.addEventListener('click', function () {
                gtag('event', 'lead_whatsapp_click', { embudo: 'LM-TDR', canal: 'whatsapp' });
            });
        });
    }
    @if (session('ok'))
    if (typeof gtag === 'function') {
        gtag('event', 'demo_lead_enviado', { embudo: 'LM-TDR', canal: 'correo' });
    }
    @endif
})();
</script>

</body>
</html>
