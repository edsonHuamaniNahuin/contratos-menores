<div class="p-4 sm:p-6 flex flex-col gap-6 w-full max-w-3xl mx-auto">

    {{-- Header --}}
    <div class="bg-white rounded-3xl shadow-soft p-6 sm:p-8 border border-neutral-100 text-center">
        <div class="inline-flex items-center justify-center w-14 h-14 bg-primary-500/10 rounded-full mb-4">
            <svg class="w-7 h-7 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-neutral-900">Contáctanos</h1>
        <p class="text-sm text-neutral-500 mt-2">¿Tienes preguntas o necesitas más información? Envíanos un mensaje y te responderemos a la brevedad.</p>
    </div>

    {{-- Feedback --}}
    @if($successMessage)
        <div class="bg-secondary-500/10 border border-secondary-500/30 rounded-2xl px-5 py-4 text-sm text-neutral-800 flex items-center gap-3">
            <svg class="w-5 h-5 text-secondary-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ $successMessage }}
        </div>
    @endif

    @if($errorMessage)
        <div class="bg-primary-500/10 border border-primary-500/30 rounded-2xl px-5 py-4 text-sm text-neutral-800 flex items-center gap-3">
            <svg class="w-5 h-5 text-primary-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            {{ $errorMessage }}
        </div>
    @endif

    {{-- Formulario --}}
    <form wire:submit="enviar" class="bg-white rounded-3xl shadow-soft p-6 sm:p-8 border border-neutral-100 space-y-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            {{-- Nombre --}}
            <div class="flex flex-col gap-1.5">
                <label for="nombre" class="text-xs font-semibold text-neutral-600">Nombre completo</label>
                <input
                    id="nombre"
                    type="text"
                    wire:model="nombre"
                    placeholder="Tu nombre"
                    class="w-full px-4 py-3 rounded-full border border-neutral-200 text-sm text-neutral-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                >
                @error('nombre')
                    <p class="text-xs text-primary-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email --}}
            <div class="flex flex-col gap-1.5">
                <label for="email" class="text-xs font-semibold text-neutral-600">Correo electrónico</label>
                <input
                    id="email"
                    type="email"
                    wire:model="email"
                    placeholder="tu@correo.com"
                    class="w-full px-4 py-3 rounded-full border border-neutral-200 text-sm text-neutral-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                >
                @error('email')
                    <p class="text-xs text-primary-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Asunto --}}
        <div class="flex flex-col gap-1.5">
            <label for="asunto" class="text-xs font-semibold text-neutral-600">Asunto</label>
            <input
                id="asunto"
                type="text"
                wire:model="asunto"
                placeholder="¿Sobre qué quieres escribirnos?"
                class="w-full px-4 py-3 rounded-full border border-neutral-200 text-sm text-neutral-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            >
            @error('asunto')
                <p class="text-xs text-primary-500">{{ $message }}</p>
            @enderror
        </div>

        {{-- Mensaje --}}
        <div class="flex flex-col gap-1.5">
            <label for="mensaje" class="text-xs font-semibold text-neutral-600">Mensaje</label>
            <textarea
                id="mensaje"
                wire:model="mensaje"
                rows="5"
                placeholder="Escribe tu mensaje aquí..."
                class="w-full px-4 py-3 rounded-2xl border border-neutral-200 text-sm text-neutral-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"
            ></textarea>
            @error('mensaje')
                <p class="text-xs text-primary-500">{{ $message }}</p>
            @enderror
        </div>

        {{-- Botón --}}
        <div class="flex justify-end">
            <button
                type="submit"
                class="bg-primary-500 text-white text-sm font-semibold px-8 py-3 rounded-full shadow-soft hover:bg-primary-400 transition-colors flex items-center gap-2"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-60 cursor-not-allowed"
            >
                <svg wire:loading wire:target="enviar" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span wire:loading.remove wire:target="enviar">Enviar mensaje</span>
                <span wire:loading wire:target="enviar">Enviando...</span>
            </button>
        </div>
    </form>

    {{-- Info adicional --}}
    <div class="bg-white rounded-3xl shadow-soft p-6 sm:p-8 border border-neutral-100">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-center">
            <div class="flex flex-col items-center gap-2">
                <div class="w-10 h-10 bg-primary-500/10 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <p class="text-xs font-semibold text-neutral-900">Email</p>
                <a href="mailto:services@sunqupacha.com" class="text-xs text-neutral-500 hover:text-primary-500 transition-colors">services@sunqupacha.com</a>
            </div>
            <div class="flex flex-col items-center gap-2">
                <div class="w-10 h-10 bg-primary-500/10 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <p class="text-xs font-semibold text-neutral-900">Teléfono</p>
                <a href="tel:+51918874873" class="text-xs text-neutral-500 hover:text-primary-500 transition-colors">+51 918 874 873</a>
            </div>
            <div class="flex flex-col items-center gap-2">
                <div class="w-10 h-10 bg-secondary-500/10 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-secondary-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                        <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 17.394c-.248.694-.916 1.363-1.884 1.541-.552.1-1.273.18-3.701-.795-3.108-1.248-5.107-4.415-5.261-4.62-.149-.198-1.213-1.614-1.213-3.076 0-1.463.768-2.182 1.04-2.479.272-.298.595-.372.792-.372.198 0 .397.002.57.01.182.01.427-.069.669.51.247.595.841 2.058.916 2.207.075.149.124.322.025.52-.1.199-.149.323-.298.497-.148.173-.312.387-.446.52-.148.148-.303.309-.13.606.173.298.77 1.271 1.653 2.059 1.135 1.012 2.093 1.325 2.39 1.475.297.148.471.124.644-.075.173-.198.743-.867.94-1.164.199-.298.397-.249.67-.15.272.1 1.733.818 2.03.967.298.149.496.223.57.347.075.124.075.719-.173 1.413z"/>
                    </svg>
                </div>
                <p class="text-xs font-semibold text-neutral-900">WhatsApp</p>
                <a href="https://wa.me/51918874873" target="_blank" class="text-xs text-neutral-500 hover:text-primary-500 transition-colors">Escríbenos por WhatsApp</a>
            </div>
        </div>
    </div>
</div>
