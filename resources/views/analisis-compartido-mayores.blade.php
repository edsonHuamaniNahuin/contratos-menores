@extends('layouts.public')

@section('title', 'Análisis TDR — Contrato Mayor | Vigilante SEACE')
@section('meta_description', 'Análisis de TDR de Contrato Mayor generado con IA por Vigilante SEACE. Resultados completos del proceso ' . ($analisis->contexto_contrato['nomenclatura'] ?? '') . '.')

@php
    $data = $analisis->resumen ?? [];
    $contexto = $analisis->contexto_contrato ?? [];
    $pageUrl = url()->current();
    $shareTitle = 'Análisis TDR IA — ' . ($contexto['nomenclatura'] ?? 'Contrato Mayor');
    $shareText = '🤖 Análisis TDR de ' . ($contexto['entidad_nombre'] ?? 'Entidad')
        . ' (' . ($contexto['nomenclatura'] ?? '') . ')'
        . ' — vía Vigilante SEACE';
@endphp

@section('content')
<section class="bg-neutral-50 py-12 sm:py-16">
    <div class="max-w-3xl mx-auto px-4 sm:px-6">

        {{-- Cabecera --}}
        <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-primary-500/10">
                <svg class="w-6 h-6 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-neutral-900">🤖 Análisis TDR con IA</h1>
                <p class="text-sm text-neutral-400 mt-0.5">
                    Contrato Mayor · Generado el {{ $analisis->analizado_en?->timezone(config('app.timezone', 'UTC'))->format('d/m/Y H:i') ?? 'N/D' }}
                    · {{ ucfirst($analisis->proveedor) }}
                </p>
            </div>
        </div>

        {{-- Contexto del contrato --}}
        @if(!empty($contexto))
        <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-5 sm:p-6 mb-6">
            <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-3">📌 Contexto del Contrato</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach([
                    'Nomenclatura' => $contexto['nomenclatura'] ?? null,
                    'Entidad' => $contexto['entidad_nombre'] ?? null,
                    'RUC' => $contexto['entidad_ruc'] ?? null,
                    'Objeto' => $contexto['objeto_contratacion'] ?? null,
                    'Descripción' => $contexto['descripcion_objeto'] ?? null,
                    'Estado' => $contexto['estado'] ?? null,
                    'Método' => $contexto['metodo_contratacion'] ?? null,
                    'Moneda' => $contexto['moneda'] ?? null,
                ] as $label => $valor)
                    @if($valor)
                    <div>
                        <p class="text-xs text-neutral-400">{{ $label }}</p>
                        <p class="text-sm font-semibold text-neutral-900">{{ \Illuminate\Support\Str::limit($valor, 120) }}</p>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
        @endif

        {{-- Resumen Ejecutivo --}}
        @php $resumen = $data['resumen_ejecutivo'] ?? null; @endphp
        @if($resumen)
            <div class="relative bg-gradient-to-br from-primary-500/5 to-secondary-500/5 border border-primary-200/50 rounded-2xl p-5 lg:p-6 mb-6">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 w-8 h-8 rounded-xl bg-primary-500/10 flex items-center justify-center mt-0.5">
                        <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-primary-800 uppercase tracking-[0.2em] mb-2">📊 Resumen Ejecutivo</p>
                        <p class="text-sm lg:text-base text-neutral-800 leading-relaxed">{{ $resumen }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Requisitos de Calificación --}}
        @php $calificacion = $data['requisitos_admisibilidad_y_calificacion'] ?? []; @endphp
        @if(!empty($calificacion))
        <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-5 sm:p-6 mb-6">
            <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-3">🛠️ Requisitos de Calificación (Pasa/No Pasa)</p>
            <dl class="space-y-3 text-sm">
                @if(!empty($calificacion['habilitaciones_legales_obligatorias']))
                    <div><dt class="text-[11px] font-semibold text-neutral-400 uppercase tracking-wider">Habilitaciones Legales</dt><dd class="text-neutral-700 mt-0.5">{{ is_array($calificacion['habilitaciones_legales_obligatorias']) ? implode(', ', $calificacion['habilitaciones_legales_obligatorias']) : $calificacion['habilitaciones_legales_obligatorias'] }}</dd></div>
                @endif
                @if(!empty($calificacion['equipamiento_infraestructura']))
                    <div><dt class="text-[11px] font-semibold text-neutral-400 uppercase tracking-wider">Equipamiento</dt><dd class="text-neutral-700 mt-0.5">{{ is_array($calificacion['equipamiento_infraestructura']) ? implode(', ', $calificacion['equipamiento_infraestructura']) : $calificacion['equipamiento_infraestructura'] }}</dd></div>
                @endif
                @if(!empty($calificacion['experiencia_financiera_postor']))
                    <div><dt class="text-[11px] font-semibold text-neutral-400 uppercase tracking-wider">Experiencia Financiera</dt><dd class="text-neutral-700 mt-0.5">{{ $calificacion['experiencia_financiera_postor'] }}</dd></div>
                @endif
                @if(!empty($calificacion['perfil_personal_clave']))
                    <div><dt class="text-[11px] font-semibold text-neutral-400 uppercase tracking-wider mb-2">Personal Clave</dt>
                        @foreach($calificacion['perfil_personal_clave'] as $p)
                            <dd class="bg-neutral-50 rounded-xl p-3 mb-2 border border-neutral-100">
                                <p class="font-semibold text-neutral-800">{{ $p['cargo'] ?? '---' }}</p>
                                <p class="text-xs text-neutral-500">{{ $p['formacion_academica'] ?? '' }}</p>
                                <p class="text-xs text-neutral-500">{{ $p['experiencia_especifica_obligatoria'] ?? '' }}</p>
                            </dd>
                        @endforeach
                    </div>
                @endif
            </dl>
        </div>
        @endif

        {{-- Factores de Evaluación --}}
        @php $factores = $data['factores_puntaje_evaluacion'] ?? []; @endphp
        @if(!empty($factores))
        <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-5 sm:p-6 mb-6">
            <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-3">📊 Factores de Evaluación (0-100 pts)</p>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-neutral-50 border-b border-neutral-100">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-neutral-500 uppercase">Factor</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-neutral-500 uppercase">Puntaje Máx</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-neutral-500 uppercase">Criterio</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach($factores as $f)
                            <tr>
                                <td class="px-3 py-2 font-medium text-neutral-800">{{ $f['factor_nombre'] ?? '---' }}</td>
                                <td class="px-3 py-2 text-center font-bold text-neutral-900">{{ $f['puntaje_maximo_asignado'] ?? 0 }}</td>
                                <td class="px-3 py-2 text-neutral-600">{{ $f['criterio_evaluacion'] ?? '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Consorcio + Garantías --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            @php $consorcio = $data['parametros_consorcio'] ?? null; @endphp
            @if($consorcio)
                <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-5 sm:p-6">
                    <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-3">🤝 Consorcio</p>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-xs text-neutral-500">Permitido</dt><dd class="font-semibold {{ ($consorcio['permite_consorcio'] ?? false) ? 'text-green-600' : 'text-red-600' }}">{{ ($consorcio['permite_consorcio'] ?? false) ? 'Sí' : 'No' }}</dd></div>
                        @if($consorcio['limite_maximo_integrantes'] ?? null)<div class="flex justify-between"><dt class="text-xs text-neutral-500">Límite integrantes</dt><dd class="font-semibold">{{ $consorcio['limite_maximo_integrantes'] }}</dd></div>@endif
                        @if($consorcio['porcentaje_minimo_individual'] ?? null)<div class="flex justify-between"><dt class="text-xs text-neutral-500">% Mín x miembro</dt><dd class="font-semibold">{{ $consorcio['porcentaje_minimo_individual'] }}</dd></div>@endif
                        @if($consorcio['porcentaje_minimo_mayor_experiencia'] ?? null)<div class="flex justify-between"><dt class="text-xs text-neutral-500">% Mín mayor exp</dt><dd class="font-semibold">{{ $consorcio['porcentaje_minimo_mayor_experiencia'] }}</dd></div>@endif
                    </dl>
                </div>
            @endif

            @php $garantias = $data['garantias_y_penalidades'] ?? null; @endphp
            @if($garantias)
                <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-5 sm:p-6">
                    <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-3">🔒 Garantías y Penalidades</p>
                    <dl class="space-y-2 text-sm">
                        @if($garantias['porcentaje_garantia_fiel_cumplimiento'] ?? null)<div class="flex justify-between"><dt class="text-xs text-neutral-500">Garantía Fiel Cumpl.</dt><dd class="font-semibold">{{ $garantias['porcentaje_garantia_fiel_cumplimiento'] }}</dd></div>@endif
                        <div class="flex justify-between"><dt class="text-xs text-neutral-500">Retención MYPE</dt><dd class="font-semibold {{ ($garantias['permite_retencion_mype'] ?? false) ? 'text-green-600' : 'text-red-600' }}">{{ ($garantias['permite_retencion_mype'] ?? false) ? 'Sí' : 'No mencionada' }}</dd></div>
                        @if($garantias['penalidad_mora_tope_maximo'] ?? null)<div class="flex justify-between"><dt class="text-xs text-neutral-500">Penalidad mora</dt><dd class="font-semibold">{{ $garantias['penalidad_mora_tope_maximo'] }}</dd></div>@endif
                        @if($garantias['otras_penalidades_tope'] ?? null)<div class="flex justify-between"><dt class="text-xs text-neutral-500">Otras penalidades</dt><dd class="font-semibold">{{ $garantias['otras_penalidades_tope'] }}</dd></div>@endif
                        @if($garantias['plazo_estimado_ejecucion'] ?? null)<div class="flex justify-between"><dt class="text-xs text-neutral-500">Plazo ejecución</dt><dd class="font-semibold">{{ $garantias['plazo_estimado_ejecucion'] }}</dd></div>@endif
                    </dl>
                </div>
            @endif
        </div>

        {{-- Presupuesto --}}
        @php $monto = $data['presupuesto_referencial'] ?? null; @endphp
        @if($monto)
        <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-5 sm:p-6 mb-6">
            <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-2">💰 Presupuesto Referencial</p>
            <p class="text-2xl font-extrabold text-neutral-900">{{ $monto }}</p>
        </div>
        @endif

        {{-- ═══ Botones de compartir ═══ --}}
        <div x-data="shareWidget()" class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-5 sm:p-6 mb-6">
            <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-4">📤 Compartir este análisis</p>

            <div class="flex flex-wrap gap-3">
                <button @click="nativeShare()"
                        x-show="canNativeShare"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary-500 text-white text-sm font-semibold hover:bg-primary-400 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                    Compartir
                </button>

                <a href="https://wa.me/?text={{ urlencode($shareText . ' ' . $pageUrl) }}" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-green-200 text-green-700 text-sm font-semibold hover:bg-green-50 transition-colors">
                    WhatsApp
                </a>

                <a href="https://t.me/share/url?url={{ urlencode($pageUrl) }}&text={{ urlencode($shareText) }}" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-blue-200 text-blue-700 text-sm font-semibold hover:bg-blue-50 transition-colors">
                    Telegram
                </a>

                <button @click="copyLink()"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-neutral-200 text-neutral-600 text-sm font-semibold hover:bg-neutral-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    <span x-text="copied ? '¡Copiado!' : 'Copiar enlace'"></span>
                </button>
            </div>
        </div>

        {{-- CTA Registro --}}
        @guest
        <div class="text-center bg-gradient-to-br from-primary-500 to-primary-600 rounded-3xl p-8 text-white shadow-soft">
            <p class="text-lg font-bold mb-2">¿Quieres análisis ilimitados?</p>
            <p class="text-sm text-primary-100 mb-5">Regístrate en Vigilante SEACE para analizar TDRs con IA, crear proformas automáticas y más.</p>
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-white text-primary-600 rounded-full text-sm font-bold hover:bg-primary-50 transition-colors">
                Registrarse gratis
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
        </div>
        @endguest

    </div>
</section>

{{-- Share widget JS --}}
<script>
function shareWidget() {
    return {
        copied: false,
        canNativeShare: !!navigator.share,
        nativeShare() {
            navigator.share({ title: @js($shareTitle), text: @js($shareText), url: @js($pageUrl) }).catch(() => {});
        },
        copyLink() {
            navigator.clipboard.writeText(@js($pageUrl)).then(() => {
                this.copied = true;
                setTimeout(() => this.copied = false, 2000);
            }).catch(() => {});
        }
    };
}
</script>
@stop
