<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Checkout Premium - Vigilante SEACE</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased min-h-screen bg-neutral-50">

    <!-- Navbar -->
    <nav class="bg-white border-b border-neutral-100 px-6 py-4">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-2">
                <div class="w-9 h-9 bg-gradient-to-br from-primary-500 to-primary-400 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <span class="text-lg font-bold text-neutral-900">Vigilante SEACE</span>
            </a>
            <a href="{{ route('planes') }}" class="px-5 py-2 text-sm font-medium text-neutral-600 hover:text-primary-500 transition-colors">
                &larr; Volver a planes
            </a>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-6 py-16" x-data="mpCheckout()" x-cloak>

        {{-- ═══ Vista de resultado (post-pago) ═══ --}}
        <template x-if="paymentResult">
            <div class="max-w-lg mx-auto">
                {{-- Éxito --}}
                <template x-if="paymentResult === 'success'">
                    <div class="bg-white rounded-3xl shadow-soft p-10 border border-neutral-100 text-center space-y-6">
                        <div class="w-20 h-20 mx-auto bg-secondary-500/10 rounded-full flex items-center justify-center">
                            <svg class="w-10 h-10 text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-neutral-900">¡Pago exitoso!</h1>
                            <p class="text-sm text-neutral-500 mt-2">Tu suscripción Premium ha sido activada correctamente.</p>
                        </div>
                        <a href="{{ route('home') }}" class="inline-block px-8 py-3.5 text-sm font-semibold text-white rounded-full bg-gradient-to-r from-primary-500 to-secondary-500 hover:opacity-90 transition shadow-lg">
                            Ir al Dashboard
                        </a>
                    </div>
                </template>

                {{-- Error --}}
                <template x-if="paymentResult === 'failure'">
                    <div class="bg-white rounded-3xl shadow-soft p-10 border border-neutral-100 text-center space-y-6">
                        <div class="w-20 h-20 mx-auto bg-primary-500/10 rounded-full flex items-center justify-center">
                            <svg class="w-10 h-10 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-neutral-900">Pago no completado</h1>
                            <p class="text-sm text-neutral-500 mt-2" x-text="errorMessage || 'El pago no se pudo procesar. Puedes intentarlo nuevamente.'"></p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3 justify-center">
                            <button @click="resetForm()" class="px-8 py-3.5 text-sm font-semibold text-white rounded-full bg-primary-500 hover:bg-primary-400 transition shadow-lg">
                                Reintentar
                            </button>
                            <a href="{{ route('home') }}" class="px-8 py-3.5 text-sm font-semibold text-neutral-700 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 transition">
                                Ir al inicio
                            </a>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        {{-- ═══ Formulario de pago (pre-pago) ═══ --}}
        <template x-if="!paymentResult">
            <div class="grid md:grid-cols-5 gap-8">

                {{-- Resumen del plan --}}
                <div class="md:col-span-2">
                    <div class="bg-white rounded-3xl shadow-soft p-6 border border-neutral-100 sticky top-8">
                        <h2 class="text-lg font-bold text-neutral-900 mb-4">Resumen</h2>

                        <div class="bg-neutral-50 rounded-2xl p-4 mb-4">
                            <p class="text-sm font-semibold text-neutral-900">
                                @if($isTrial ?? false)
                                    Prueba Gratuita — 15 días
                                @else
                                    Plan {{ $plan === 'yearly' ? 'Anual' : 'Mensual' }}
                                @endif
                            </p>
                            <p class="text-xs text-neutral-400 mt-1">
                                @if($isTrial ?? false)
                                    Registra tu tarjeta para iniciar el trial. No se cobra hasta que termine.
                                @else
                                    {{ $plan === 'yearly' ? '12 meses de acceso Premium' : '1 mes de acceso Premium' }}
                                @endif
                            </p>
                        </div>

                        <div class="flex items-baseline justify-between mb-2">
                            <span class="text-sm text-neutral-600">{{ ($isTrial ?? false) ? 'Cobro hoy' : 'Subtotal' }}</span>
                            <span class="text-sm font-semibold text-neutral-900">S/ {{ number_format($price, 2) }}</span>
                        </div>
                        <div class="border-t border-neutral-100 pt-2 flex items-baseline justify-between">
                            <span class="text-sm font-bold text-neutral-900">Total</span>
                            <span class="text-2xl font-bold text-primary-500">S/ {{ number_format($price, 2) }}</span>
                        </div>

                        @if($isTrial ?? false)
                            <div class="mt-4 bg-amber-50 rounded-2xl p-3 text-center">
                                <p class="text-xs font-semibold text-amber-700">Al terminar el trial (15 días) se cobra S/ 49/mes automáticamente</p>
                            </div>
                        @elseif($plan === 'yearly')
                            <div class="mt-4 bg-secondary-500/10 rounded-2xl p-3 text-center">
                                <p class="text-xs font-semibold text-secondary-500">Ahorras S/ {{ number_format(49*12 - $price, 2) }} al año</p>
                            </div>
                        @endif

                        {{-- Badge Mercado Pago --}}
                        <div class="mt-5 flex items-center justify-center gap-2 bg-[#009EE3]/5 rounded-2xl p-3">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z" fill="#009EE3"/>
                                <path d="M15.5 8.5c-.83 0-1.5.67-1.5 1.5v4c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5v-4c0-.83-.67-1.5-1.5-1.5zM8.5 8.5c-.83 0-1.5.67-1.5 1.5v4c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5v-4c0-.83-.67-1.5-1.5-1.5z" fill="white"/>
                            </svg>
                            <span class="text-xs font-medium text-[#009EE3]">Procesado por Mercado Pago</span>
                        </div>

                        {{-- Métodos de pago aceptados --}}
                        <div class="mt-4 pt-4 border-t border-neutral-100">
                            <p class="text-xs font-semibold text-neutral-500 mb-3 text-center">Métodos de pago</p>
                            <div class="flex items-center justify-center gap-2 flex-wrap">
                                <span class="inline-flex items-center gap-1 bg-neutral-50 text-[10px] text-neutral-600 px-2.5 py-1.5 rounded-full border border-neutral-200">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                    Tarjeta
                                </span>
                                <span class="inline-flex items-center gap-1 bg-neutral-50 text-[10px] text-neutral-600 px-2.5 py-1.5 rounded-full border border-neutral-200">
                                    Transferencia
                                </span>
                                <span class="inline-flex items-center gap-1 bg-neutral-50 text-[10px] text-neutral-600 px-2.5 py-1.5 rounded-full border border-neutral-200">
                                    Efectivo
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Formulario / selector de método --}}
                <div class="md:col-span-3 space-y-6">

                    {{-- ═══ SELECTOR DE MÉTODO DE PAGO ═══ --}}
                    <div class="bg-white rounded-3xl shadow-soft p-6 border border-neutral-100">
                        <h2 class="text-lg font-bold text-neutral-900 mb-4">
                            @if($isTrial ?? false)
                                Registra tu tarjeta
                            @else
                                Elige tu método de pago
                            @endif
                        </h2>

                        @if($isTrial ?? false)
                            <p class="text-sm text-neutral-400 mb-4">
                                Para activar tu trial de 15 días necesitamos una tarjeta. No se realizará ningún cobro hoy.
                            </p>
                        @endif

                        <div class="grid grid-cols-1 {{ ($isTrial ?? false) ? '' : 'sm:grid-cols-2' }} gap-3">
                            {{-- Tarjeta --}}
                            <button @click="paymentMethod = 'card'"
                                :class="paymentMethod === 'card'
                                    ? 'border-primary-500 bg-primary-500/5 ring-2 ring-primary-500/20'
                                    : 'border-neutral-200 hover:border-neutral-300'"
                                class="flex items-center gap-3 p-4 rounded-2xl border-2 transition-all text-left">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                                    :class="paymentMethod === 'card' ? 'bg-primary-500/10' : 'bg-neutral-100'">
                                    <svg class="w-5 h-5" :class="paymentMethod === 'card' ? 'text-primary-500' : 'text-neutral-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold" :class="paymentMethod === 'card' ? 'text-primary-500' : 'text-neutral-900'">Tarjeta crédito/débito</p>
                                    <p class="text-xs text-neutral-400">Visa, Mastercard, Amex</p>
                                </div>
                            </button>

                            {{-- Otros métodos de pago via Checkout Pro (Solo para pagos, no para trial) --}}
                            @if(!($isTrial ?? false))
                            <button @click="paymentMethod = 'otros'"
                                :class="paymentMethod === 'otros'
                                    ? 'border-[#009EE3] bg-[#009EE3]/5 ring-2 ring-[#009EE3]/20'
                                    : 'border-neutral-200 hover:border-neutral-300'"
                                class="flex items-center gap-3 p-4 rounded-2xl border-2 transition-all text-left">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                                    :class="paymentMethod === 'otros' ? 'bg-[#009EE3]/10' : 'bg-neutral-100'">
                                    <svg class="w-5 h-5" :class="paymentMethod === 'otros' ? 'text-[#009EE3]' : 'text-neutral-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold" :class="paymentMethod === 'otros' ? 'text-[#009EE3]' : 'text-neutral-900'">Otros métodos</p>
                                    <p class="text-xs text-neutral-400">Transferencia, efectivo, saldo MP</p>
                                </div>
                            </button>
                            @endif
                        </div>
                    </div>

                    {{-- ═══ FORMULARIO TARJETA (in-page) ═══ --}}
                    <div x-show="paymentMethod === 'card'" x-transition class="bg-white rounded-3xl shadow-soft p-8 border border-neutral-100">
                        <h1 class="text-xl font-bold text-neutral-900 mb-1">
                            @if($isTrial ?? false)
                                Datos de tarjeta (validación)
                            @else
                                Datos de tarjeta
                            @endif
                        </h1>
                        <p class="text-sm text-neutral-400 mb-6">
                            @if($isTrial ?? false)
                                Solo validamos tu tarjeta. No se realizará ningún cobro hasta que termine tu trial.
                            @else
                                Pago seguro. No necesitas cuenta de Mercado Pago.
                            @endif
                        </p>

                        @if(app()->environment('local'))
                            <div class="mb-6 bg-amber-50 border border-amber-200 rounded-2xl p-4">
                                <p class="text-xs font-bold text-amber-700 mb-1">Modo Test &mdash; Tarjetas de prueba</p>
                                <p class="text-xs text-amber-600 mb-2">Usa estas tarjetas y pon <strong>APRO</strong> como nombre del titular para aprobar:</p>
                                <div class="text-xs text-amber-700 font-mono space-y-0.5">
                                    <p>Visa: 4009 1753 3280 6176 &bull; CVV: 123 &bull; Exp: 11/30</p>
                                    <p>MC: &nbsp;5031 7557 3453 0604 &bull; CVV: 123 &bull; Exp: 11/30</p>
                                </div>
                                <p class="text-xs text-amber-500 mt-1">DNI: 12345678 &bull; Otros nombres: OTHE (rechazado), CONT (pendiente)</p>
                            </div>
                        @endif

                        {{-- Errores --}}
                        <div x-show="errorMessage && paymentMethod === 'card'" x-transition class="mb-6 bg-primary-500/10 border-l-4 border-primary-500 rounded-2xl p-4">
                            <p class="text-sm text-neutral-900 font-medium" x-text="errorMessage"></p>
                        </div>

                        @if(session('error'))
                            <div class="mb-6 bg-primary-500/10 border-l-4 border-primary-500 rounded-2xl p-4">
                                <p class="text-sm text-neutral-900 font-medium">{{ session('error') }}</p>
                            </div>
                        @endif

                        <form @submit.prevent="submitCardPayment">

                            {{-- Email --}}
                            <div class="mb-4">
                                <label class="text-xs font-semibold text-neutral-600 mb-1.5 block">Email</label>
                                <input type="email" x-model="email" placeholder="tu@email.com"
                                    class="w-full px-5 py-2.5 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm"
                                    required>
                            </div>

                            {{-- Nombre en la tarjeta --}}
                            <div class="mb-4">
                                <label class="text-xs font-semibold text-neutral-600 mb-1.5 block">Nombre en la tarjeta</label>
                                <input type="text" x-model="cardName" placeholder="Como aparece en tu tarjeta"
                                    class="w-full px-5 py-2.5 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm"
                                    required>
                            </div>

                            {{-- Número de tarjeta --}}
                            <div class="mb-4">
                                <label class="text-xs font-semibold text-neutral-600 mb-1.5 block">Número de tarjeta</label>
                                <input type="text" x-model="cardNumber" placeholder="4111 1111 1111 1111"
                                    maxlength="19" @input="formatCardNumber"
                                    class="w-full px-5 py-2.5 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm font-mono"
                                    required>
                            </div>

                            <div class="grid grid-cols-2 gap-4 mb-4">
                                {{-- Expiración --}}
                                <div>
                                    <label class="text-xs font-semibold text-neutral-600 mb-1.5 block">Expiración</label>
                                    <div class="flex gap-2">
                                        <input type="text" x-model="cardMonth" placeholder="MM" maxlength="2"
                                            class="w-full px-4 py-2.5 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm text-center font-mono"
                                            required>
                                        <input type="text" x-model="cardYear" placeholder="AA" maxlength="2"
                                            class="w-full px-4 py-2.5 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm text-center font-mono"
                                            required>
                                    </div>
                                </div>

                                {{-- CVV --}}
                                <div>
                                    <label class="text-xs font-semibold text-neutral-600 mb-1.5 block">CVV</label>
                                    <input type="password" x-model="cardCvv" placeholder="123" maxlength="4"
                                        class="w-full px-5 py-2.5 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm font-mono"
                                        required>
                                </div>
                            </div>

                            {{-- Documento de identidad --}}
                            <div class="grid grid-cols-3 gap-4 mb-6">
                                <div>
                                    <label class="text-xs font-semibold text-neutral-600 mb-1.5 block">Tipo Doc.</label>
                                    <select x-model="docType"
                                        class="w-full px-4 py-2.5 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm bg-white"
                                        required>
                                        <option value="DNI">DNI</option>
                                        <option value="CE">CE</option>
                                        <option value="RUC">RUC</option>
                                        <option value="Otro">Otro</option>
                                    </select>
                                </div>
                                <div class="col-span-2">
                                    <label class="text-xs font-semibold text-neutral-600 mb-1.5 block">Nro. Documento</label>
                                    <input type="text" x-model="docNumber" placeholder="12345678"
                                        class="w-full px-5 py-2.5 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm font-mono"
                                        required>
                                </div>
                            </div>

                            {{-- Botón de pago --}}
                            <button type="submit" :disabled="processing"
                                class="w-full px-6 py-3.5 text-sm font-semibold text-white rounded-full bg-primary-500 hover:bg-primary-400 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-lg">
                                <svg x-show="processing" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="processing ? 'Procesando pago...' : 'Pagar S/ {{ number_format($price, 2) }} con Tarjeta'"></span>
                            </button>
                        </form>

                        <div class="mt-6 flex items-center justify-center gap-3">
                            <svg class="w-4 h-4 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <p class="text-xs text-neutral-400">Tus datos son procesados de forma segura por Mercado Pago</p>
                        </div>
                    </div>

                    {{-- ═══ OTROS MÉTODOS VIA CHECKOUT PRO ═══ --}}
                    <div x-show="paymentMethod === 'otros'" x-transition class="bg-white rounded-3xl shadow-soft p-8 border border-neutral-100">
                        <h1 class="text-xl font-bold text-neutral-900 mb-1">Otros métodos de pago</h1>
                        <p class="text-sm text-neutral-400 mb-6">Serás redirigido a Mercado Pago para completar el pago.</p>

                        {{-- Errores --}}
                        <div x-show="errorMessage && paymentMethod === 'otros'" x-transition class="mb-6 bg-primary-500/10 border-l-4 border-primary-500 rounded-2xl p-4">
                            <p class="text-sm text-neutral-900 font-medium" x-text="errorMessage"></p>
                        </div>

                        {{-- Métodos disponibles --}}
                        <div class="space-y-3 mb-8">
                            <div class="flex items-center gap-4 bg-neutral-50 rounded-2xl p-4 border border-neutral-200">
                                <div class="w-10 h-10 bg-neutral-200 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-neutral-900">Transferencia bancaria</p>
                                    <p class="text-xs text-neutral-500">BCP, Interbank, BBVA, Scotiabank</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 bg-neutral-50 rounded-2xl p-4 border border-neutral-200">
                                <div class="w-10 h-10 bg-neutral-200 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-neutral-900">PagoEfectivo</p>
                                    <p class="text-xs text-neutral-500">Paga en agentes, bodegas o bancos</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 bg-[#009EE3]/5 rounded-2xl p-4 border border-[#009EE3]/10">
                                <div class="w-10 h-10 bg-[#009EE3] rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-[#009EE3]">Dinero en cuenta</p>
                                    <p class="text-xs text-neutral-500">Saldo en tu cuenta Mercado Pago</p>
                                </div>
                            </div>
                        </div>

                        @if(!empty($initPoint))
                            <a href="{{ $initPoint }}"
                                class="w-full px-6 py-3.5 text-sm font-semibold text-white rounded-full bg-[#009EE3] hover:bg-[#0088cc] transition-colors flex items-center justify-center gap-2 shadow-lg">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z" fill="white"/>
                                    <path d="M15.5 8.5c-.83 0-1.5.67-1.5 1.5v4c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5v-4c0-.83-.67-1.5-1.5-1.5zM8.5 8.5c-.83 0-1.5.67-1.5 1.5v4c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5v-4c0-.83-.67-1.5-1.5-1.5z" fill="#009EE3"/>
                                </svg>
                                <span>Pagar S/ {{ number_format($price, 2) }} con Mercado Pago</span>
                            </a>
                            <p class="text-xs text-neutral-400 text-center mt-4">
                                Serás redirigido a Mercado Pago donde podrás elegir transferencia, efectivo u otros métodos.
                            </p>
                        @else
                            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 text-center">
                                <p class="text-sm text-amber-700 font-medium">No se pudo inicializar el checkout alternativo.</p>
                                <p class="text-xs text-amber-600 mt-1">Por favor usa el pago con tarjeta o intenta más tarde.</p>
                                <button @click="paymentMethod = 'card'" class="mt-3 text-sm font-semibold text-primary-500 hover:underline">
                                    &larr; Pagar con tarjeta
                                </button>
                            </div>
                        @endif

                        <div class="mt-6 flex items-center justify-center gap-3">
                            <svg class="w-4 h-4 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <p class="text-xs text-neutral-400">Transacción segura procesada por Mercado Pago</p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- MercadoPago.js v2 SDK --}}
    <script src="https://sdk.mercadopago.com/js/v2"></script>

    <script>
        function mpCheckout() {
            const mp = new MercadoPago('{{ $publicKey }}', { locale: 'es-PE' });

            return {
                // Payment method selection
                paymentMethod: 'card',

                // Card form fields
                email: '{{ auth()->user()->email ?? '' }}',
                cardName: @js(app()->environment('local') ? 'APRO' : (auth()->user()->name ?? '')),
                cardNumber: '',
                cardMonth: '',
                cardYear: '',
                cardCvv: '',
                docType: 'DNI',
                docNumber: '',

                // State
                processing: false,
                errorMessage: '',
                paymentResult: null,

                formatCardNumber() {
                    let v = this.cardNumber.replace(/\s/g, '').replace(/\D/g, '');
                    this.cardNumber = v.replace(/(.{4})/g, '$1 ').trim();
                },

                resetForm() {
                    this.paymentResult = null;
                    this.errorMessage = '';
                    this.processing = false;
                    this.cardNumber = '';
                    this.cardMonth = '';
                    this.cardYear = '';
                    this.cardCvv = '';
                },

                async submitCardPayment() {
                    this.processing = true;
                    this.errorMessage = '';

                    try {
                        const cardToken = await mp.createCardToken({
                            cardNumber: this.cardNumber.replace(/\s/g, ''),
                            cardholderName: this.cardName,
                            cardExpirationMonth: this.cardMonth,
                            cardExpirationYear: '20' + this.cardYear,
                            securityCode: this.cardCvv,
                            identificationType: this.docType,
                            identificationNumber: this.docNumber,
                        });

                        if (!cardToken || !cardToken.id) {
                            this.errorMessage = 'No se pudo validar la tarjeta. Revisa los datos e intenta de nuevo.';
                            this.processing = false;
                            return;
                        }

                        const response = await fetch('{{ route("planes.charge") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                plan: '{{ $plan }}',
                                token_id: cardToken.id,
                                device_session_id: 'mp_' + Date.now(),
                                is_trial: {{ ($isTrial ?? false) ? 'true' : 'false' }},
                            }),
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.paymentResult = 'success';
                            setTimeout(() => {
                                window.location.href = data.redirect || '{{ route("home") }}';
                            }, 2000);
                        } else {
                            this.errorMessage = data.error || 'Error al procesar el pago.';
                            this.processing = false;
                        }

                    } catch (err) {
                        console.error('MercadoPago error:', err);

                        if (err && err.message) {
                            this.errorMessage = err.message;
                        } else if (err && err[0] && err[0].message) {
                            this.errorMessage = err[0].message;
                        } else {
                            this.errorMessage = 'Error al validar la tarjeta. Revisa los datos e intenta de nuevo.';
                        }

                        this.processing = false;
                    }
                }
            };
        }
    </script>

    <style>[x-cloak]{display:none!important}</style>
    <p class="text-center text-xs text-neutral-400 pb-8">&copy; {{ date('Y') }} Sunqupacha S.A.C. Todos los derechos reservados.</p>
    @livewireScripts
</body>
</html>
