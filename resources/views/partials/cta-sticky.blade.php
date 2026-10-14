{{--
    Barra CTA fija para MÓVIL (donde está el 83% del tráfico).
    Corrige el hallazgo del heatmap: solo ~23 de 192 visitas llegaban a ver
    el formulario y el CTA no era visible sin scroll. Esta barra mantiene
    SIEMPRE a la vista: [Agendar demo] + [WhatsApp].

    Parámetros:
      $wa       (string) link de WhatsApp
      $variante ('oscuro' | 'claro')
      $btnClass (string) clases del botón principal (según paleta de la landing)
      $btnColor (string) color del ícono WhatsApp del botón secundario
--}}
@php
    $variante = $variante ?? 'oscuro';
    $btnClass = $btnClass ?? 'bg-[var(--mint-soft)] text-[var(--teal-deep)]';
    $btnColor = $btnColor ?? ($variante === 'oscuro' ? '#7fe3c4' : '#0a9f76');
@endphp

<div class="lg:hidden fixed bottom-0 inset-x-0 z-[70] px-3 pt-2.5 pb-3"
     style="{{ $variante === 'oscuro'
        ? 'background:rgba(4,26,29,.96); border-top:1px solid rgba(127,227,196,.25); backdrop-filter:blur(8px);'
        : 'background:rgba(255,255,255,.97); border-top:1px solid #E5E7EB; box-shadow:0 -6px 20px rgba(0,0,0,.07); backdrop-filter:blur(8px);' }}">
    <div class="flex gap-2.5 max-w-lg mx-auto">
        <a href="#form-correo" data-scroll-forma
           class="flex-1 flex items-center justify-center gap-2 rounded-xl font-bold text-[15px] py-3.5 transition-opacity hover:opacity-90 {{ $btnClass }}">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Agendar demo gratis
        </a>
        <a href="{{ $wa }}" target="_blank" rel="noopener" aria-label="Escribir por WhatsApp"
           class="flex items-center justify-center rounded-xl px-4 py-3.5 transition-opacity hover:opacity-90"
           style="{{ $variante === 'oscuro'
              ? 'background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.3);'
              : 'background:#E7F6EF; border:1px solid #BFE8D6;' }}">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="{{ $btnColor }}"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.149-.174.198-.298.297-.497.1-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        </a>
    </div>
</div>

<style>
    @media (max-width: 1023.98px) {
        body { padding-bottom: 86px; }
    }
</style>

<script>
(function () {
    document.querySelectorAll('[data-scroll-forma]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            var f = document.getElementById('form-correo');
            if (f) { f.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
        });
    });
})();
</script>
