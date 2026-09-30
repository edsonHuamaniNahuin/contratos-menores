{{-- Aviso de servicio: suspensión de alertas automáticas por WhatsApp (oct 2026).
     Se muestra una vez por dispositivo (localStorage). Usa Alpine, ya cargado por Livewire. --}}
<div
    x-data="{
        abierto: false,
        init() {
            if (localStorage.getItem('aviso-wsp-oct2026') === 'visto') return;
            setTimeout(() => { this.abierto = true; }, 900);
        },
        cerrar() {
            localStorage.setItem('aviso-wsp-oct2026', 'visto');
            this.abierto = false;
        }
    }"
    x-show="abierto"
    x-cloak
    x-on:keydown.escape.window="cerrar()"
    class="fixed inset-0 z-[300] flex items-center justify-center p-4"
    style="display: none;"
    role="dialog"
    aria-modal="true"
    aria-labelledby="aviso-wsp-titulo"
>
    <div class="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm" @click="cerrar()"></div>

    <div
        class="relative w-full max-w-md bg-white rounded-[2rem] shadow-soft border border-neutral-200 overflow-hidden"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-3 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
    >
        <div class="p-6 sm:p-7">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-green-50 border border-green-100 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.019-.458.13-.606.134-.133.297-.347.446-.52.149-.174.198-.298.297-.497.1-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-700 text-[10px] font-bold uppercase tracking-wide">
                        Actualización · 1 de octubre de 2026
                    </span>
                    <h2 id="aviso-wsp-titulo" class="text-lg sm:text-xl font-bold text-neutral-900 mt-2 leading-snug">
                        WhatsApp temporalmente deshabilitado
                    </h2>
                </div>
            </div>

            <div class="mt-4 space-y-3 text-sm text-neutral-600 leading-relaxed">
                <p>
                    Desde el <strong class="text-neutral-900">1 de octubre de 2026</strong>, el canal de WhatsApp está
                    <strong class="text-neutral-900">temporalmente deshabilitado para alertas automáticas</strong>
                    por las nuevas políticas y tarifas de WhatsApp Business (Meta). Aplica a todos los planes, incluido Premium.
                </p>
                <div class="rounded-2xl bg-primary-50 border border-primary-100 px-4 py-3 text-[13px] text-brand-800">
                    Tus alertas por <strong>Telegram</strong> y <strong>correo electrónico</strong> siguen funcionando
                    igual, sin costo adicional, junto con todas las funciones de tu plan.
                </div>
            </div>

            <div class="mt-5 flex flex-col sm:flex-row gap-2.5">
                <a
                    href="{{ route('aviso.whatsapp') }}"
                    @click="cerrar()"
                    class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-3 rounded-full bg-primary-500 hover:bg-primary-600 text-white text-sm font-bold transition-colors shadow-sm"
                >
                    Ver detalles
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                <button
                    type="button"
                    @click="cerrar()"
                    class="flex-1 px-5 py-3 rounded-full border border-neutral-200 text-neutral-600 hover:text-neutral-900 hover:border-neutral-300 text-sm font-semibold transition-colors"
                >
                    Entendido
                </button>
            </div>

            <p class="mt-3 text-[11px] text-neutral-400 text-center">
                El soporte humano sigue disponible por WhatsApp.
            </p>
        </div>
    </div>
</div>
