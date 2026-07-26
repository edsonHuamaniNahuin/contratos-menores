@extends('layouts.app')

@section('title', 'Pagar con Yape — Vigilante SEACE')

@section('content')
<div class="max-w-xl mx-auto px-4 py-8 sm:py-12">
    <a href="{{ route('planes') }}" class="inline-flex items-center gap-1.5 text-sm text-neutral-500 hover:text-primary-500 transition-colors mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Volver a Planes
    </a>

    <h1 class="text-2xl sm:text-3xl font-extrabold text-neutral-900 mb-2">Pagar con Yape</h1>
    <p class="text-sm text-neutral-500 mb-8">{{ $planLabel }} — <strong>S/ {{ number_format($monto, 2) }}</strong></p>

    @if($pagosPendientes > 0)
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 mb-6 text-sm text-amber-800">
            Ya tienes <strong>{{ $pagosPendientes }}</strong> pago(s) pendiente(s) de validación. No puedes enviar otro hasta que sea procesado.
        </div>
    @else
        {{-- Info Card --}}
        <div class="bg-white rounded-2xl shadow-card border border-neutral-100 p-5 mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-neutral-900">Escanea con Yape</h2>
                <p class="text-xs text-neutral-400">Abre Yape, escanea y paga el monto exacto</p>
            </div>
            <div class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-50 rounded-xl text-base font-bold text-primary-700 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                S/ {{ number_format($monto, 2) }}
            </div>
        </div>

        {{-- QR Image --}}
        <div class="bg-white rounded-2xl shadow-card border border-neutral-100 overflow-hidden mb-6">
            @if(file_exists(public_path('images/yape-qr.jpeg')))
                <img src="{{ asset('images/yape-qr.jpeg') }}" alt="QR Yape" class="w-full" style="display:block">
            @else
                <div class="aspect-square w-full bg-neutral-100 flex items-center justify-center">
                    <div class="text-center p-8">
                        <svg class="w-24 h-24 text-neutral-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" stroke-width="1.5"/><rect x="6" y="6" width="6" height="6" rx="0.5" stroke-width="1.5"/><rect x="12" y="6" width="6" height="6" rx="0.5" stroke-width="1.5"/><rect x="6" y="12" width="3" height="6" rx="0.5" stroke-width="1.5"/><rect x="12" y="12" width="6" height="2" rx="0.5" stroke-width="1.5"/><rect x="12" y="16" width="3" height="2" rx="0.5" stroke-width="1.5"/></svg>
                        <p class="text-sm text-neutral-400 font-medium">QR no configurado</p>
                        <p class="text-xs text-neutral-300 mt-1">public/images/yape-qr.jpeg</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- Upload Form --}}
        <form action="{{ route('pago.yape.submit', $plan) }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-card border border-neutral-100 p-6 space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-semibold text-neutral-700 mb-2">
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Screenshot del pago <span class="text-red-500">*</span>
                    </span>
                </label>
                <input type="file" name="comprobante" accept="image/jpeg,image/png,image/webp" required
                    class="w-full text-sm text-neutral-600 file:mr-4 file:py-2.5 file:px-5 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 file:transition-colors file:cursor-pointer"
                >
                <p class="text-[11px] text-neutral-400 mt-1.5">JPG, PNG o WebP. Máximo 5 MB.</p>
                @error('comprobante')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="referencia" class="block text-sm font-semibold text-neutral-700 mb-2">
                    Referencia adicional (opcional)
                </label>
                <input type="text" id="referencia" name="referencia_adicional" maxlength="500"
                    placeholder="Ej: Pago realizado el 25/07 a las 3pm por S/ 49.00"
                    class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
            </div>

            <div>
                <label for="telefono" class="block text-sm font-semibold text-neutral-700 mb-2">
                    Teléfono de contacto (opcional)
                </label>
                <input type="tel" id="telefono" name="telefono" maxlength="9" pattern="[0-9]{9}" inputmode="numeric"
                    placeholder="Ej: 987654321"
                    class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                    class="flex-1 py-3 bg-primary-500 text-white text-sm font-bold rounded-full hover:bg-primary-400 transition-colors shadow-sm flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 4v11"/></svg>
                    Enviar comprobante
                </button>
                <a href="{{ route('planes') }}" class="px-5 py-3 text-sm font-medium text-neutral-500 hover:text-neutral-700 transition-colors">
                    Cancelar
                </a>
            </div>

            <p class="text-[11px] text-neutral-400 text-center pt-2">
                Tu pago será validado manualmente en un plazo máximo de 24 horas.
                Recibirás una notificación cuando se active tu plan Premium.
            </p>
        </form>
    @endif
</div>
@endsection
