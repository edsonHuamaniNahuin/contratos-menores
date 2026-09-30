{{-- ═══ Meta Pixel (Facebook) — carga solo en producción y si hay ID configurado ═══
     Variable: META_PIXEL_ID en .env
     Uso: @include('partials.meta-pixel')
     Eventos: PageView (automático) + Contact (click en WhatsApp) + Lead (envío ok).
     En landings pasar el embudo: @include('partials.meta-pixel', ['embudo' => 'E1'])
--}}
@if(app()->environment('production') && config('services.meta.pixel_id'))
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '{{ config('services.meta.pixel_id') }}');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id={{ config('services.meta.pixel_id') }}&ev=PageView&noscript=1"
/></noscript>
<script>
(function () {
    var EMBUDO = '{{ $embudo ?? 'GENERAL' }}';
    function metaTrack(evento) {
        if (typeof fbq === 'function') {
            fbq('track', evento, { content_category: EMBUDO });
        }
    }
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('a[href*="wa.me/"]').forEach(function (el) {
            el.addEventListener('click', function () {
                metaTrack('Contact');
            });
        });
    });
    @if (session('ok'))
    document.addEventListener('DOMContentLoaded', function () {
        metaTrack('Lead');
    });
    @endif
})();
</script>
@endif
