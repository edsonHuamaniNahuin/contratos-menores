{{-- Microsoft Clarity (grabaciones de sesión + mapas) — modelo OPT-OUT:
     se carga por defecto y SOLO se omite si el visitante rechazó las cookies
     analíticas. Si acepta después, se carga al vuelo. Declarado en la política
     de privacidad (§9 Cookies). --}}
@if(app()->environment('production') && config('services.clarity.project_id'))
<script>
(function () {
    var PROYECTO = @json(config('services.clarity.project_id'));
    function cargarClarity() {
        if (window.__clarityInstalado) return;
        window.__clarityInstalado = true;
        (function (c, l, a, r, i, t, y) {
            c[a] = c[a] || function () { (c[a].q = c[a].q || []).push(arguments); };
            t = l.createElement(r); t.async = 1; t.src = "https://www.clarity.ms/tag/" + i;
            y = l.getElementsByTagName(r)[0]; y.parentNode.insertBefore(t, y);
        })(window, document, "clarity", "script", PROYECTO);
        // Señal de consentimiento (cubre el modo consentimiento del proyecto; inocua si está apagado)
        if (typeof window.clarity === "function") { window.clarity("consent", true); }
    }
    window.cargarClarity = cargarClarity;
    if (localStorage.getItem('cookie_consent') !== 'denied') { cargarClarity(); }
    window.addEventListener('cookie-consent-granted', cargarClarity);
})();
</script>
@endif

