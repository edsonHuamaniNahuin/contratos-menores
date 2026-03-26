@extends('layouts.public')

@section('title', 'Guía para Cotizar en SEACE — Licitaciones MYPe')
@section('meta_description', 'Guía informativa paso a paso para enviar una cotización en el portal oficial SEACE del OSCE. Licitaciones MYPe es un servicio independiente, no afiliado al gobierno.')
@section('noindex', true)

@php
    $codigoProceso = request('proceso', '');
    $entidad = request('entidad', '');
    $idContrato = (int) request('id', 0);
    $seaceBase = rtrim(config('services.seace.frontend_origin', 'https://prod6.seace.gob.pe'), '/');
    $urlLogin = $seaceBase . '/auth-proveedor/';
@endphp

@section('content')
<section class="bg-neutral-50 py-12 sm:py-16" x-data="cotizarGuia()">
    <div class="max-w-2xl mx-auto px-4 sm:px-6">

        {{-- Banner: Sitio independiente --}}
        <div class="flex items-center gap-3 bg-amber-50 border border-amber-200 rounded-2xl px-5 py-4 mb-8">
            <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-xs text-amber-800 leading-relaxed">
                <strong>Licitaciones MYPe es un servicio privado e independiente.</strong>
                No somos el SEACE ni el OSCE. No recopilamos tus credenciales del portal del Estado.
                Esta página es solo una guía informativa.
            </p>
        </div>

        {{-- Cabecera --}}
        <div class="text-center mb-8">
            <div class="w-16 h-16 rounded-3xl bg-secondary-500/10 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-neutral-900 mb-2">💼 Cotizar en SEACE</h1>
            <p class="text-sm text-neutral-400 max-w-md mx-auto">
                Sigue estos pasos para enviar tu cotización de manera segura en el portal oficial del SEACE.
            </p>
        </div>

        {{-- Datos del proceso --}}
        @if($codigoProceso)
        <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-5 sm:p-6 mb-6">
            <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-3">Proceso a Cotizar</p>
            <div class="flex items-center gap-3 mb-2">
                <p class="text-lg font-extrabold text-neutral-900 flex-1">{{ $codigoProceso }}</p>
                <button @click="copiarCodigo()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-primary-800 text-white text-xs font-semibold hover:bg-primary-900 transition-colors">
                    <svg x-show="!copiado" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    <svg x-show="copiado" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span x-text="copiado ? '¡Copiado!' : 'Copiar'"></span>
                </button>
            </div>
            @if($entidad)
            <p class="text-sm text-neutral-400">🏢 {{ $entidad }}</p>
            @endif
        </div>
        @endif

        {{-- Pasos --}}
        <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-5 sm:p-6 mb-6">
            <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-5">📋 Pasos para cotizar</p>

            <div class="space-y-5">
                {{-- Paso 1 --}}
                <div class="flex items-start gap-4">
                    <span class="w-8 h-8 rounded-full bg-secondary-500 text-white text-sm font-bold flex items-center justify-center shrink-0">1</span>
                    <div class="flex-1">
                        <p class="text-sm font-bold text-neutral-900">Copia el código del proceso</p>
                        <p class="text-xs text-neutral-400 mt-0.5">
                            @if($codigoProceso)
                                Toca el botón "Copiar" arriba para copiar <strong>{{ $codigoProceso }}</strong> al portapapeles.
                            @else
                                Copia el código del proceso que recibiste en tu notificación de Telegram o WhatsApp.
                            @endif
                        </p>
                    </div>
                </div>

                {{-- Paso 2 --}}
                <div class="flex items-start gap-4">
                    <span class="w-8 h-8 rounded-full bg-secondary-500 text-white text-sm font-bold flex items-center justify-center shrink-0">2</span>
                    <div class="flex-1">
                        <p class="text-sm font-bold text-neutral-900">Abre el portal oficial del SEACE</p>
                        <p class="text-xs text-neutral-400 mt-0.5">Haz clic en el botón de abajo. El portal oficial del SEACE (seace.gob.pe) te pedirá ingresar con las credenciales de tu cuenta de proveedor — <strong class="text-neutral-600">Licitaciones MYPe nunca solicita ni almacena esas credenciales.</strong></p>
                    </div>
                </div>

                {{-- Paso 3 --}}
                <div class="flex items-start gap-4">
                    <span class="w-8 h-8 rounded-full bg-secondary-500 text-white text-sm font-bold flex items-center justify-center shrink-0">3</span>
                    <div class="flex-1">
                        <p class="text-sm font-bold text-neutral-900">Busca y cotiza</p>
                        <p class="text-xs text-neutral-400 mt-0.5">En el buscador del portal SEACE, pega el código (Ctrl+V o pegado largo en móvil) y envía tu cotización directamente.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Nota de seguridad --}}
        <div class="bg-primary-900/5 border border-primary-400/20 rounded-2xl px-5 py-4 mb-6">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-primary-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <div>
                    <p class="text-sm font-bold text-primary-800 mb-1">Tu contraseña del SEACE es solo tuya</p>
                    <p class="text-xs text-primary-700">
                        El portal oficial del SEACE (seace.gob.pe) protege cada sesión de forma individual.
                        Nunca compartas tus credenciales con ningún servicio externo — ningún sistema puede cotizar válidamente en tu nombre.
                        <strong>Licitaciones MYPe no solicita, no almacena ni tiene acceso a tus credenciales del Estado.</strong>
                    </p>
                </div>
            </div>
        </div>

        {{-- Botón principal --}}
        <div class="text-center mb-8">
            <a href="{{ $urlLogin }}" target="_blank" rel="noopener"
               @if($codigoProceso) @click="copiarCodigo()" @endif
               class="inline-flex items-center gap-2 px-8 py-4 rounded-full bg-secondary-500 text-white text-base font-bold hover:bg-secondary-600 transition-colors shadow-lg shadow-secondary-500/20">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                Ir al portal SEACE
            </a>
            <p class="text-xs text-neutral-400 mt-3">Se abrirá en una nueva pestaña</p>
        </div>

        {{-- CTA manual --}}
        <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-5 sm:p-6 text-center">
            <p class="text-sm text-neutral-400 mb-3">¿Necesitas más ayuda? Revisa nuestro manual completo.</p>
            <a href="{{ route('manual') }}#buscador" class="inline-flex items-center gap-2 text-sm font-semibold text-primary-800 hover:text-primary-900 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                Ver Manual del Usuario
            </a>
        </div>

    </div>
</section>

<script>
function cotizarGuia() {
    return {
        codigo: @json($codigoProceso),
        copiado: false,

        copiarCodigo() {
            if (!this.codigo) return;
            navigator.clipboard.writeText(this.codigo).then(() => {
                this.copiado = true;
                setTimeout(() => { this.copiado = false; }, 3000);
            }).catch(() => {
                const ta = document.createElement('textarea');
                ta.value = this.codigo;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                this.copiado = true;
                setTimeout(() => { this.copiado = false; }, 3000);
            });
        }
    };
}
</script>
@endsection
