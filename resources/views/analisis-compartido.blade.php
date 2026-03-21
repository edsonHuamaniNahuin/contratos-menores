@extends('layouts.public')

@section('title', ($analisis->tipo_analisis === 'direccionamiento' ? 'Análisis de Direccionamiento' : 'Análisis TDR') . ' — Licitaciones MYPe')
@section('meta_description', 'Análisis de TDR generado con IA por Licitaciones MYPe. Resultados completos del proceso ' . ($contexto['codigo_proceso'] ?? '') . '.')

@php
    $esDireccionamiento = $analisis->tipo_analisis === 'direccionamiento';
    $data = $analisis->resumen ?? [];
    $contexto = $analisis->contexto_contrato ?? [];
    $pageUrl = url()->current();
    $shareTitle = $esDireccionamiento
        ? 'Análisis de Direccionamiento — ' . ($contexto['codigo_proceso'] ?? 'Proceso SEACE')
        : 'Análisis TDR IA — ' . ($contexto['codigo_proceso'] ?? 'Proceso SEACE');
    $shareText = ($esDireccionamiento ? '🔍 Direccionamiento' : '🤖 Análisis TDR')
        . ' de ' . ($contexto['entidad'] ?? 'Entidad SEACE')
        . ' (' . ($contexto['codigo_proceso'] ?? '') . ')'
        . ' — vía Licitaciones MYPe';
@endphp

@section('content')
<section class="bg-neutral-50 py-12 sm:py-16">
    <div class="max-w-3xl mx-auto px-4 sm:px-6">

        {{-- Cabecera --}}
        <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center {{ $esDireccionamiento ? 'bg-primary-900/10' : 'bg-secondary-500/10' }}">
                @if($esDireccionamiento)
                    <svg class="w-6 h-6 text-primary-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                @else
                    <svg class="w-6 h-6 text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                @endif
            </div>
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-neutral-900">
                    {{ $esDireccionamiento ? '🔍 Análisis de Direccionamiento' : '🤖 Análisis TDR con IA' }}
                </h1>
                <p class="text-sm text-neutral-400 mt-0.5">
                    Generado el {{ $analisis->analizado_en?->timezone(config('app.timezone', 'UTC'))->format('d/m/Y H:i') ?? 'N/D' }}
                    · {{ ucfirst($analisis->proveedor) }}
                </p>
            </div>
        </div>

        {{-- Archivo --}}
        <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-5 sm:p-6 mb-6">
            <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-2">Archivo analizado</p>
            <p class="text-base font-bold text-neutral-900 flex items-center gap-2">
                <svg class="w-5 h-5 text-primary-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                {{ $analisis->archivo?->nombre_original ?? 'Archivo' }}
            </p>
        </div>

        {{-- Contexto del contrato --}}
        @if(!empty($contexto))
        <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-5 sm:p-6 mb-6">
            <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-3">📌 Contexto del Contrato</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach([
                    'Entidad' => $contexto['entidad'] ?? null,
                    'Código' => $contexto['codigo_proceso'] ?? null,
                    'Objeto' => $contexto['objeto'] ?? null,
                    'Estado' => $contexto['estado'] ?? null,
                    'Etapa' => $contexto['etapa'] ?? null,
                    'Publicación' => $contexto['fecha_publicacion'] ?? null,
                    'Fin Cotización' => $contexto['fecha_cierre'] ?? null,
                ] as $label => $valor)
                    @if($valor)
                    <div>
                        <p class="text-xs text-neutral-400">{{ $label }}</p>
                        <p class="text-sm font-semibold text-neutral-900">{{ $valor }}</p>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
        @endif

        {{-- Contenido del análisis --}}
        @if($esDireccionamiento)
            {{-- ── Direccionamiento ── --}}
            @php
                $score = (int) ($data['score_riesgo_corrupcion'] ?? 0);
                $veredicto = $data['veredicto_flash'] ?? 'SIN VEREDICTO';
                $hallazgos = $data['hallazgos_criticos'] ?? [];
                $argumento = $data['argumento_para_observacion'] ?? '';
                $veredictoColor = match($veredicto) {
                    'LIMPIO' => 'text-green-600 bg-green-50 border-green-200',
                    'SOSPECHOSO' => 'text-yellow-600 bg-yellow-50 border-yellow-200',
                    'ALTAMENTE DIRECCIONADO' => 'text-red-600 bg-red-50 border-red-200',
                    default => 'text-neutral-600 bg-neutral-50 border-neutral-200',
                };
            @endphp

            <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-5 sm:p-6 mb-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 mb-4">
                    <div>
                        <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider">Score de Riesgo</p>
                        <p class="text-4xl font-extrabold text-neutral-900 mt-1">{{ $score }}<span class="text-lg text-neutral-400">/100</span></p>
                    </div>
                    <div class="px-4 py-2 rounded-full border text-sm font-bold {{ $veredictoColor }}">
                        {{ $veredicto }}
                    </div>
                </div>
                {{-- Barra visual --}}
                <div class="w-full bg-neutral-100 rounded-full h-3 overflow-hidden">
                    <div class="h-full rounded-full transition-all {{ $score <= 30 ? 'bg-green-500' : ($score <= 60 ? 'bg-yellow-500' : 'bg-red-500') }}"
                         style="width: {{ min($score, 100) }}%"></div>
                </div>
            </div>

            @if(!empty($hallazgos))
            <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-5 sm:p-6 mb-6">
                <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-4">🚩 Hallazgos Críticos</p>
                <div class="space-y-4">
                    @foreach($hallazgos as $hallazgo)
                        @if(is_array($hallazgo))
                            @php
                                $gravedad = $hallazgo['nivel_de_gravedad'] ?? 'Medio';
                                $gravedadColor = match($gravedad) {
                                    'Alto' => 'border-red-300 bg-red-50',
                                    'Medio' => 'border-yellow-300 bg-yellow-50',
                                    'Bajo' => 'border-green-300 bg-green-50',
                                    default => 'border-neutral-300 bg-neutral-50',
                                };
                                $gravedadBadge = match($gravedad) {
                                    'Alto' => 'bg-red-100 text-red-700',
                                    'Medio' => 'bg-yellow-100 text-yellow-700',
                                    'Bajo' => 'bg-green-100 text-green-700',
                                    default => 'bg-neutral-100 text-neutral-700',
                                };
                            @endphp
                            <div class="rounded-2xl border p-4 {{ $gravedadColor }}">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $gravedadBadge }}">{{ $gravedad }}</span>
                                    <span class="text-xs font-semibold text-neutral-500">[{{ $hallazgo['categoria'] ?? 'General' }}]</span>
                                </div>
                                @if($hallazgo['red_flag_detectada'] ?? '')
                                    <p class="text-sm font-bold text-neutral-900 mb-1">🚨 {{ $hallazgo['red_flag_detectada'] }}</p>
                                @endif
                                @if($hallazgo['descripcion_hallazgo'] ?? '')
                                    <p class="text-sm text-neutral-700">{{ $hallazgo['descripcion_hallazgo'] }}</p>
                                @endif
                            </div>
                        @else
                            <p class="text-sm text-neutral-700">• {{ $hallazgo }}</p>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif

            @if($argumento)
            <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-5 sm:p-6 mb-6">
                <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-3">📝 Argumento para Observación</p>
                <p class="text-sm text-neutral-700 leading-relaxed whitespace-pre-line">{{ $argumento }}</p>
            </div>
            @endif

        @else
            {{-- ── Análisis General TDR ── --}}
            @php
                $secciones = [
                    ['titulo' => 'Resumen Ejecutivo', 'icono' => '📊', 'keys' => ['resumen_ejecutivo', 'resumen', 'executive_summary']],
                    ['titulo' => 'Requisitos Técnicos', 'icono' => '🛠️', 'keys' => ['requisitos_tecnicos', 'requisitos_calificacion', 'requisitos', 'requirements']],
                    ['titulo' => 'Reglas Operativas', 'icono' => '⚙️', 'keys' => ['reglas_de_negocio', 'reglas_ejecucion', 'condiciones_servicio', 'reglas']],
                    ['titulo' => 'Políticas y Penalidades', 'icono' => '⚖️', 'keys' => ['politicas_y_penalidades', 'politicas', 'penalidades', 'politicas_penalidades', 'penalidad']],
                    ['titulo' => 'Recomendaciones', 'icono' => '💡', 'keys' => ['recomendaciones', 'observaciones']],
                ];
            @endphp

            @foreach($secciones as $seccion)
                @php
                    $contenido = null;
                    foreach ($seccion['keys'] as $key) {
                        if (isset($data[$key]) && !empty($data[$key])) {
                            $contenido = $data[$key];
                            break;
                        }
                    }
                @endphp
                @if($contenido)
                <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-5 sm:p-6 mb-6">
                    <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-3">{{ $seccion['icono'] }} {{ $seccion['titulo'] }}</p>
                    @if(is_array($contenido))
                        <ul class="space-y-2">
                            @foreach($contenido as $key => $item)
                                @if(is_string($item) && trim($item) !== '')
                                    <li class="text-sm text-neutral-700 flex gap-2">
                                        <span class="text-neutral-400 shrink-0">•</span>
                                        <span>{{ $item }}</span>
                                    </li>
                                @elseif(is_array($item))
                                    @foreach($item as $subKey => $subVal)
                                        @if(is_string($subVal) && trim($subVal) !== '')
                                            <li class="text-sm text-neutral-700 flex gap-2">
                                                <span class="text-neutral-400 shrink-0">•</span>
                                                <span>@if(is_string($subKey) && !is_numeric($subKey))<strong>{{ ucfirst(str_replace('_', ' ', $subKey)) }}:</strong> @endif{{ $subVal }}</span>
                                            </li>
                                        @endif
                                    @endforeach
                                @endif
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-neutral-700 leading-relaxed whitespace-pre-line">{{ $contenido }}</p>
                    @endif
                </div>
                @endif
            @endforeach

            @php
                $monto = $data['presupuesto_referencial'] ?? $data['monto_referencial'] ?? $data['monto'] ?? null;
            @endphp
            @if($monto)
            <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-5 sm:p-6 mb-6">
                <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-2">💰 Presupuesto Referencial</p>
                <p class="text-2xl font-extrabold text-neutral-900">{{ $monto }}</p>
            </div>
            @endif
        @endif

        {{-- ═══ Botones de compartir ═══ --}}
        <div x-data="shareWidget()" class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-5 sm:p-6 mb-6">
            <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-4">📤 Compartir este análisis</p>

            <div class="flex flex-wrap gap-3">
                {{-- Compartir nativo (móvil) --}}
                <button @click="nativeShare()"
                        x-show="canNativeShare"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full bg-primary-800 text-white text-sm font-semibold hover:bg-primary-900 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                    Compartir
                </button>

                {{-- WhatsApp --}}
                <a :href="'https://wa.me/?text=' + encodeURIComponent(shareText + ' ' + pageUrl)"
                   target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full bg-[#25D366] text-white text-sm font-semibold hover:bg-[#1ebe57] transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    WhatsApp
                </a>

                {{-- Telegram --}}
                <a :href="'https://t.me/share/url?url=' + encodeURIComponent(pageUrl) + '&text=' + encodeURIComponent(shareText)"
                   target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full bg-[#0088cc] text-white text-sm font-semibold hover:bg-[#006daa] transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.223-.548.223l.188-2.85 5.18-4.68c.223-.198-.054-.308-.346-.11l-6.4 4.03-2.76-.918c-.6-.183-.612-.6.125-.89l10.782-4.156c.5-.18.943.11.78.89z"/></svg>
                    Telegram
                </a>

                {{-- Facebook --}}
                <a :href="'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(pageUrl)"
                   target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full bg-[#1877F2] text-white text-sm font-semibold hover:bg-[#1466d8] transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    Facebook
                </a>

                {{-- X / Twitter --}}
                <a :href="'https://twitter.com/intent/tweet?url=' + encodeURIComponent(pageUrl) + '&text=' + encodeURIComponent(shareText)"
                   target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full bg-neutral-900 text-white text-sm font-semibold hover:bg-neutral-800 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    X
                </a>

                {{-- LinkedIn --}}
                <a :href="'https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(pageUrl)"
                   target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full bg-[#0A66C2] text-white text-sm font-semibold hover:bg-[#0856a3] transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    LinkedIn
                </a>

                {{-- Copiar enlace --}}
                <button @click="copyLink()"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full bg-neutral-100 text-neutral-700 text-sm font-semibold hover:bg-neutral-200 transition-colors border border-neutral-200">
                    <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    <svg x-show="copied" class="w-4 h-4 text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span x-text="copied ? '¡Copiado!' : 'Copiar enlace'"></span>
                </button>
            </div>
        </div>

        {{-- CTA --}}
        <div class="bg-gradient-to-br from-primary-900 to-primary-800 rounded-3xl p-6 sm:p-8 text-center">
            <h2 class="text-xl font-extrabold text-white mb-2">¿Quieres analizar más procesos?</h2>
            <p class="text-sm text-primary-400 mb-5">Regístrate gratis y recibe alertas automáticas de licitaciones SEACE con análisis IA.</p>
            <div class="flex flex-wrap justify-center gap-3">
                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-secondary-500 text-white text-sm font-bold hover:bg-secondary-600 transition-colors">
                    Crear cuenta gratis
                </a>
                <a href="{{ route('buscador.publico') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-white/10 text-white text-sm font-bold hover:bg-white/20 transition-colors border border-white/20">
                    Ir al buscador
                </a>
            </div>
        </div>

    </div>
</section>

<script>
function shareWidget() {
    return {
        pageUrl: @json($pageUrl),
        shareText: @json($shareText),
        shareTitle: @json($shareTitle),
        copied: false,
        canNativeShare: !!navigator.share,

        async nativeShare() {
            try {
                await navigator.share({
                    title: this.shareTitle,
                    text: this.shareText,
                    url: this.pageUrl,
                });
            } catch (e) {
                // Usuario canceló o no soportado
            }
        },

        async copyLink() {
            try {
                await navigator.clipboard.writeText(this.pageUrl);
            } catch {
                const ta = document.createElement('textarea');
                ta.value = this.pageUrl;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
            }
            this.copied = true;
            setTimeout(() => { this.copied = false; }, 3000);
        }
    };
}
</script>
@endsection
