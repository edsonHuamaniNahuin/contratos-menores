{{-- Banner de Consentimiento de Cookies — GA Consent Mode v2 --}}
<div
    x-data="cookieConsent()"
    x-show="showBanner"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="translate-y-full opacity-0"
    x-transition:enter-end="translate-y-0 opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="translate-y-0 opacity-100"
    x-transition:leave-end="translate-y-full opacity-0"
    x-cloak
    class="fixed bottom-0 inset-x-0 z-[9999] p-4 sm:p-6"
>
    <div class="max-w-xl mx-auto bg-white rounded-3xl shadow-lg border border-neutral-100 p-5 sm:p-6">
        {{-- Encabezado --}}
        <div class="flex items-start gap-3 mb-3">
            <div class="flex-shrink-0 w-9 h-9 rounded-full bg-primary-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-neutral-900">Tu privacidad importa</h3>
                <p class="text-xs text-neutral-400 mt-1 leading-relaxed">
                    Usamos cookies de analítica para entender cómo se usa el sitio y mejorar tu experiencia. No recopilamos datos personales con fines publicitarios.
                </p>
            </div>
        </div>

        {{-- Detalle expandible --}}
        <div x-show="showDetails" x-collapse class="mb-3">
            <div class="bg-neutral-50 rounded-2xl p-4 text-xs text-neutral-600 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="font-medium text-neutral-900">Cookies esenciales</span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-primary-100 text-primary-500 font-medium">Siempre activas</span>
                </div>
                <p>Necesarias para el funcionamiento del sitio (sesión, CSRF, preferencias).</p>

                <div class="flex items-center justify-between pt-2 border-t border-neutral-100">
                    <span class="font-medium text-neutral-900">Cookies de analítica</span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-neutral-200 text-neutral-600 font-medium">Opcionales</span>
                </div>
                <p>Google Analytics recopila datos anónimos de navegación para mejorar el servicio.</p>
            </div>
        </div>

        {{-- Botones --}}
        <div class="flex items-center gap-2">
            <button
                @click="accept()"
                class="flex-1 px-4 py-2 text-xs font-semibold text-white bg-primary-500 rounded-full hover:bg-primary-400 transition-colors"
            >
                Aceptar todo
            </button>
            <button
                @click="reject()"
                class="flex-1 px-4 py-2 text-xs font-semibold text-neutral-600 bg-neutral-50 border border-neutral-200 rounded-full hover:bg-neutral-100 transition-colors"
            >
                Solo esenciales
            </button>
            <button
                @click="showDetails = !showDetails"
                class="px-3 py-2 text-xs font-medium text-neutral-400 hover:text-neutral-600 transition-colors"
                x-text="showDetails ? 'Menos' : 'Más info'"
            ></button>
        </div>
    </div>
</div>

<script>
function cookieConsent() {
    return {
        showBanner: false,
        showDetails: false,

        init() {
            const consent = localStorage.getItem('cookie_consent');
            if (!consent) {
                this.showBanner = true;
            } else if (consent === 'granted') {
                this.grantAnalytics();
            }
            // Si consent === 'denied', no hacemos nada (ya está denied por defecto)
        },

        accept() {
            localStorage.setItem('cookie_consent', 'granted');
            this.grantAnalytics();
            this.showBanner = false;
        },

        reject() {
            localStorage.setItem('cookie_consent', 'denied');
            this.showBanner = false;
        },

        grantAnalytics() {
            if (typeof gtag === 'function') {
                gtag('consent', 'update', {
                    analytics_storage: 'granted'
                });
            }
        }
    };
}
</script>
