<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Checkout Premium - Vigilante SEACE</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased min-h-screen bg-neutral-50">

    <!-- Navbar -->
    <nav class="bg-white border-b border-neutral-100 px-6 py-4">
        <div class="max-w-3xl mx-auto flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-2">
                <div class="w-9 h-9 bg-gradient-to-br from-primary-500 to-primary-400 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <span class="text-lg font-bold text-neutral-900">Vigilante SEACE</span>
            </a>
            <a href="{{ route('planes') }}" class="px-5 py-2 text-sm font-medium text-neutral-600 hover:text-primary-500 transition-colors">
                ← Volver a planes
            </a>
        </div>
    </nav>

    <div class="max-w-3xl mx-auto px-6 py-16" x-data="checkoutForm()" x-cloak>
        <div class="grid md:grid-cols-5 gap-8">

            {{-- Resumen del plan --}}
            <div class="md:col-span-2">
                <div class="bg-white rounded-3xl shadow-soft p-6 border border-neutral-100 sticky top-8">
                    <h2 class="text-lg font-bold text-neutral-900 mb-4">Resumen</h2>

                    @if($isTrial)
                        {{-- Trial --}}
                        <div class="bg-amber-50 rounded-2xl p-4 mb-4 border border-amber-200">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-lg">🎁</span>
                                <p class="text-sm font-bold text-neutral-900">Trial 15 días gratis</p>
                            </div>
                            <p class="text-xs text-neutral-600">
                                Registra tu tarjeta y disfruta de todas las funciones Premium durante 15 días sin costo.
                            </p>
                        </div>

                        <div class="flex items-baseline justify-between mb-2">
                            <span class="text-sm text-neutral-600">Hoy pagas</span>
                            <span class="text-2xl font-bold text-secondary-500">S/ 0.00</span>
                        </div>
                        <div class="border-t border-neutral-100 pt-2">
                            <p class="text-xs text-neutral-400">
                                Después del trial se cobra automáticamente <strong class="text-neutral-900">S/ {{ number_format($price, 2) }}/mes</strong>.
                                Puedes cancelar en cualquier momento.
                            </p>
                        </div>
                    @else
                        {{-- Pago directo --}}
                        <div class="bg-neutral-50 rounded-2xl p-4 mb-4">
                            <p class="text-sm font-semibold text-neutral-900">
                                Plan {{ $plan === 'yearly' ? 'Anual' : 'Mensual' }}
                            </p>
                            <p class="text-xs text-neutral-400 mt-1">
                                {{ $plan === 'yearly' ? '12 meses de acceso Premium' : '1 mes de acceso Premium' }}
                            </p>
                        </div>

                        <div class="flex items-baseline justify-between mb-2">
                            <span class="text-sm text-neutral-600">Subtotal</span>
                            <span class="text-sm font-semibold text-neutral-900">S/ {{ number_format($price, 2) }}</span>
                        </div>
                        <div class="border-t border-neutral-100 pt-2 flex items-baseline justify-between">
                            <span class="text-sm font-bold text-neutral-900">Total</span>
                            <span class="text-2xl font-bold text-primary-500">S/ {{ number_format($price, 2) }}</span>
                        </div>

                        @if($plan === 'yearly')
                            <div class="mt-4 bg-secondary-500/10 rounded-2xl p-3 text-center">
                                <p class="text-xs font-semibold text-secondary-600">Ahorras S/ {{ number_format(49*12 - $price, 2) }} al año</p>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Formulario de pago --}}
            <div class="md:col-span-3">
                <div class="bg-white rounded-3xl shadow-soft p-8 border border-neutral-100">
                    <h1 class="text-xl font-bold text-neutral-900 mb-1">
                        {{ $isTrial ? 'Registra tu tarjeta' : 'Datos de pago' }}
                    </h1>
                    <p class="text-sm text-neutral-400 mb-6">
                        {{ $isTrial ? 'Solo registramos tu tarjeta. No se realizará ningún cobro hoy.' : 'Pago seguro procesado por Openpay' }}
                    </p>

                    {{-- Errores --}}
                    <div x-show="errorMessage" x-transition class="mb-6 bg-primary-500/10 border-l-4 border-primary-500 rounded-2xl p-4">
                        <p class="text-sm text-neutral-900 font-medium" x-text="errorMessage"></p>
                    </div>

                    @if(session('error'))
                        <div class="mb-6 bg-primary-500/10 border-l-4 border-primary-500 rounded-2xl p-4">
                            <p class="text-sm text-neutral-900 font-medium">{{ session('error') }}</p>
                        </div>
                    @endif

                    <form id="payment-form" @submit.prevent="submitPayment">
                        <input type="hidden" name="plan" value="{{ $plan }}">
                        <input type="hidden" name="token_id" x-model="tokenId">
                        <input type="hidden" name="device_session_id" x-model="deviceSessionId">

                        {{-- Nombre en la tarjeta --}}
                        <div class="mb-4">
                            <label class="text-xs font-semibold text-neutral-600 mb-1.5 block">Nombre en la tarjeta</label>
                            <input
                                type="text"
                                x-model="cardName"
                                placeholder="Como aparece en tu tarjeta"
                                class="w-full px-5 py-2.5 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm"
                                required
                            >
                        </div>

                        {{-- Número de tarjeta --}}
                        <div class="mb-4">
                            <label class="text-xs font-semibold text-neutral-600 mb-1.5 block">Número de tarjeta</label>
                            <input
                                type="text"
                                x-model="cardNumber"
                                placeholder="4111 1111 1111 1111"
                                maxlength="19"
                                @input="formatCardNumber"
                                class="w-full px-5 py-2.5 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm font-mono"
                                required
                            >
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            {{-- Expiración --}}
                            <div>
                                <label class="text-xs font-semibold text-neutral-600 mb-1.5 block">Expiración</label>
                                <div class="flex gap-2">
                                    <input
                                        type="text"
                                        x-model="cardMonth"
                                        placeholder="MM"
                                        maxlength="2"
                                        class="w-full px-4 py-2.5 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm text-center font-mono"
                                        required
                                    >
                                    <input
                                        type="text"
                                        x-model="cardYear"
                                        placeholder="AA"
                                        maxlength="2"
                                        class="w-full px-4 py-2.5 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm text-center font-mono"
                                        required
                                    >
                                </div>
                            </div>

                            {{-- CVV --}}
                            <div>
                                <label class="text-xs font-semibold text-neutral-600 mb-1.5 block">CVV</label>
                                <input
                                    type="password"
                                    x-model="cardCvv"
                                    placeholder="123"
                                    maxlength="4"
                                    class="w-full px-5 py-2.5 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm font-mono"
                                    required
                                >
                            </div>
                        </div>

                        <button
                            type="submit"
                            :disabled="processing"
                            class="w-full mt-4 px-6 py-3.5 text-sm font-semibold text-white rounded-full hover:opacity-90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 {{ $isTrial ? 'bg-secondary-500 hover:bg-secondary-400' : 'bg-primary-500 hover:bg-primary-400' }}"
                        >
                            <svg x-show="processing" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            @if($isTrial)
                                <span x-text="processing ? 'Registrando tarjeta...' : 'Registrar tarjeta y empezar trial'"></span>
                            @else
                                <span x-text="processing ? 'Procesando pago...' : 'Pagar S/ {{ number_format($price, 2) }}'"></span>
                            @endif
                        </button>
                    </form>

                    <div class="mt-6 flex items-center justify-center gap-3">
                        <svg class="w-4 h-4 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <p class="text-xs text-neutral-400">
                            {{ $isTrial ? 'Tu tarjeta se almacena de forma segura. No se cobra hasta que termine el trial.' : 'Tus datos de pago son procesados de forma segura' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Openpay JS SDK --}}
    @if($sandbox)
        <script src="https://js.openpay.pe/openpay.v1.min.js"></script>
        <script src="https://js.openpay.pe/openpay-data.v1.min.js"></script>
    @else
        <script src="https://js.openpay.pe/openpay.v1.min.js"></script>
        <script src="https://js.openpay.pe/openpay-data.v1.min.js"></script>
    @endif

    <script>
        // Inicializar Openpay
        OpenPay.setId('{{ $merchantId }}');
        OpenPay.setApiKey('{{ $publicKey }}');
        OpenPay.setSandboxMode({{ $sandbox ? 'true' : 'false' }});

        var deviceSessionId = OpenPay.deviceData.setup("payment-form", "device_session_id");

        function checkoutForm() {
            return {
                cardName: '',
                cardNumber: '',
                cardMonth: '',
                cardYear: '',
                cardCvv: '',
                tokenId: '',
                deviceSessionId: deviceSessionId,
                processing: false,
                errorMessage: '',
                isTrial: {{ $isTrial ? 'true' : 'false' }},

                formatCardNumber() {
                    let v = this.cardNumber.replace(/\s/g, '').replace(/\D/g, '');
                    this.cardNumber = v.replace(/(.{4})/g, '$1 ').trim();
                },

                submitPayment() {
                    this.processing = true;
                    this.errorMessage = '';

                    var cardData = {
                        "card_number": this.cardNumber.replace(/\s/g, ''),
                        "holder_name": this.cardName,
                        "expiration_year": this.cardYear,
                        "expiration_month": this.cardMonth,
                        "cvv2": this.cardCvv
                    };

                    var self = this;

                    OpenPay.token.create(cardData, function(response) {
                        self.tokenId = response.data.id;

                        // Enviar al backend
                        fetch('{{ route("planes.charge") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                plan: '{{ $plan }}',
                                token_id: self.tokenId,
                                device_session_id: self.deviceSessionId,
                                is_trial: self.isTrial
                            })
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                window.location.href = data.redirect || '{{ route("home") }}';
                            } else {
                                self.errorMessage = data.error || 'Error al procesar.';
                                self.processing = false;
                            }
                        })
                        .catch(err => {
                            self.errorMessage = 'Error de conexión. Intenta de nuevo.';
                            self.processing = false;
                        });

                    }, function(response) {
                        self.errorMessage = response.data.description || 'Error al validar la tarjeta.';
                        self.processing = false;
                    });
                }
            }
        }
    </script>

    <style>[x-cloak]{display:none!important}</style>
    @livewireScripts
</body>
</html>
