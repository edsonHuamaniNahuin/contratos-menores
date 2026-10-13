{{-- Microsoft Clarity (grabaciones de sesión + mapas) — solo carga si el usuario
     aceptó cookies analíticas (mismo criterio que GA4). --}}
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
    }
    window.cargarClarity = cargarClarity;
    if (localStorage.getItem('cookie_consent') === 'granted') { cargarClarity(); }
    window.addEventListener('cookie-consent-granted', cargarClarity);
})();
</script>
@endif
