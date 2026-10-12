@extends('layouts.app')

@section('title', 'Respuesta a Leads')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8" x-data="respuestaLeads()">

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-neutral-900">Respuesta a Leads</h1>
        <p class="text-sm text-neutral-500 mt-1">
            Todo listo para responder manualmente: descarga el flyer, copia el mensaje de WhatsApp o el correo.
            Escribe el nombre del lead una vez y se aplica a todas las plantillas.
        </p>
    </div>

    {{-- Nombre del lead --}}
    <div class="bg-white border border-neutral-200 rounded-2xl p-5 mb-6">
        <label class="block text-sm font-semibold text-neutral-700 mb-2">Nombre de la persona (lead o cliente)</label>
        <div class="flex flex-col sm:flex-row gap-3">
            <input type="text" x-model="nombre" placeholder="Ej: Juan Pérez"
                   class="flex-1 px-4 py-3 rounded-xl border border-neutral-300 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
            <button @click="aplicarNombre()"
                    class="px-6 py-3 rounded-xl bg-primary-500 text-white text-sm font-semibold hover:opacity-90 transition">
                Aplicar a las plantillas
            </button>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">

        {{-- WhatsApp --}}
        <div class="bg-white border border-neutral-200 rounded-2xl p-5 flex flex-col">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.149-.174.198-.298.297-.497.1-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                </div>
                <h2 class="text-lg font-bold text-neutral-900">Mensaje de WhatsApp</h2>
            </div>

            <textarea x-model="waTexto" rows="14"
                      class="w-full flex-1 px-4 py-3 rounded-xl border border-neutral-300 text-[13px] leading-relaxed font-mono focus:outline-none focus:ring-2 focus:ring-primary-500"></textarea>

            <div class="flex flex-wrap gap-3 mt-4">
                <button @click="copiar(waTexto, 'wa')"
                        class="px-4 py-2.5 rounded-xl bg-neutral-900 text-white text-sm font-semibold hover:opacity-90 transition">
                    <span x-show="copiado !== 'wa'">Copiar mensaje</span>
                    <span x-show="copiado === 'wa'" x-cloak>Copiado ✓</span>
                </button>
                <a :href="'https://wa.me/51918874873?text=' + encodeURIComponent(waTexto)" target="_blank" rel="noopener"
                   class="px-4 py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:opacity-90 transition">
                    Abrir WhatsApp con este texto
                </a>
            </div>
        </div>

        {{-- Correo --}}
        <div class="bg-white border border-neutral-200 rounded-2xl p-5 flex flex-col">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-sky-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-sky-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                </div>
                <h2 class="text-lg font-bold text-neutral-900">Correo</h2>
            </div>

            <label class="block text-xs font-semibold text-neutral-500 uppercase tracking-wide mb-1">Asunto</label>
            <div class="flex gap-2 mb-3">
                <input type="text" x-model="correoAsunto" readonly
                       class="flex-1 px-3 py-2.5 rounded-xl border border-neutral-200 bg-neutral-50 text-[13px] font-mono">
                <button @click="copiar(correoAsunto, 'asunto')"
                        class="px-3 py-2.5 rounded-xl bg-neutral-100 text-neutral-700 text-xs font-semibold hover:bg-neutral-200 transition">
                    <span x-show="copiado !== 'asunto'">Copiar</span>
                    <span x-show="copiado === 'asunto'" x-cloak>✓</span>
                </button>
            </div>

            <label class="block text-xs font-semibold text-neutral-500 uppercase tracking-wide mb-1">Mensaje</label>
            <textarea x-model="correoTexto" rows="11"
                      class="w-full flex-1 px-4 py-3 rounded-xl border border-neutral-300 text-[13px] leading-relaxed font-mono focus:outline-none focus:ring-2 focus:ring-primary-500"></textarea>

            <div class="flex flex-wrap gap-3 mt-4">
                <button @click="copiar(correoTexto, 'correo')"
                        class="px-4 py-2.5 rounded-xl bg-neutral-900 text-white text-sm font-semibold hover:opacity-90 transition">
                    <span x-show="copiado !== 'correo'">Copiar mensaje</span>
                    <span x-show="copiado === 'correo'" x-cloak>Copiado ✓</span>
                </button>
                <a :href="'mailto:?subject=' + encodeURIComponent(correoAsunto) + '&body=' + encodeURIComponent(correoTexto)"
                   class="px-4 py-2.5 rounded-xl bg-sky-600 text-white text-sm font-semibold hover:opacity-90 transition">
                    Abrir en mi correo
                </a>
            </div>

            <div class="mt-4 p-3 bg-neutral-50 border border-neutral-200 rounded-xl">
                <p class="text-xs text-neutral-500 leading-relaxed">
                    Envío automatizado (branded, con botones):<br>
                    <code class="text-[11px] text-neutral-700">php artisan lead:responder correo@lead.com --nombre="Juan"</code>
                </p>
            </div>
        </div>

        {{-- Pedir testimonios --}}
        <div class="lg:col-span-2 bg-white border border-neutral-200 rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="currentColor" viewBox="0 0 24 24"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.958a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.367 2.446a1 1 0 00-.363 1.118l1.286 3.958c.3.921-.755 1.688-1.539 1.118l-3.367-2.446a1 1 0 00-1.176 0l-3.367 2.446c-.783.57-1.838-.197-1.538-1.118l1.286-3.958a1 1 0 00-.364-1.118L2.05 9.385c-.783-.57-.38-1.81.588-1.81h4.163a1 1 0 00.95-.69l1.298-3.958z"/></svg>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-neutral-900">Pedir testimonios (clientes actuales)</h2>
                    <p class="text-xs text-neutral-500">Tu mejor prueba social: los clientes que ya pagan. Envía el pedido por WhatsApp.</p>
                </div>
            </div>

            <textarea x-model="testiTexto" rows="7"
                      class="w-full px-4 py-3 rounded-xl border border-neutral-300 text-[13px] leading-relaxed font-mono focus:outline-none focus:ring-2 focus:ring-primary-500"></textarea>

            <div class="flex flex-wrap gap-3 mt-4">
                <button @click="copiar(testiTexto, 'testi')"
                        class="px-4 py-2.5 rounded-xl bg-neutral-900 text-white text-sm font-semibold hover:opacity-90 transition">
                    <span x-show="copiado !== 'testi'">Copiar mensaje</span>
                    <span x-show="copiado === 'testi'" x-cloak>Copiado ✓</span>
                </button>
                <a :href="'https://wa.me/51918874873?text=' + encodeURIComponent(testiTexto)" target="_blank" rel="noopener"
                   class="px-4 py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:opacity-90 transition">
                    Abrir WhatsApp con este texto
                </a>
            </div>

            <p class="mt-3 text-xs text-neutral-400">Cuando un cliente acepte, pásame su frase + nombre/empresa y la subimos a las 3 landings.</p>
        </div>

        {{-- Flyer --}}
        <div class="lg:col-span-2 bg-white border border-neutral-200 rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-primary-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/></svg>
                </div>
                <h2 class="text-lg font-bold text-neutral-900">Flyer para WhatsApp</h2>
            </div>

            <div class="flex flex-col md:flex-row gap-6">
                <img src="{{ url('/images/wsp/vigilante-info-1350.png') }}" alt="Flyer Vigilante SEACE"
                     class="w-full md:w-64 rounded-xl border border-neutral-200">

                <div class="flex-1 flex flex-col gap-3">
                    <p class="text-sm text-neutral-600 leading-relaxed">
                        Adjúntalo en WhatsApp (o envíalo como imagen). Ideal para el primer contacto cuando piden información.
                    </p>
                    <a href="{{ url('/images/wsp/vigilante-info-1350.png') }}?descargar=1" download="vigilante-seace-info.png"
                       class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-primary-500 text-white text-sm font-semibold hover:opacity-90 transition w-fit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 4v12m0 0l-4-4m4 4l4-4"/></svg>
                        Descargar PNG
                    </a>
                    <button @click="copiar(flyerUrl, 'flyer')"
                            class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-neutral-100 text-neutral-700 text-sm font-semibold hover:bg-neutral-200 transition w-fit">
                        <span x-show="copiado !== 'flyer'">Copiar enlace público</span>
                        <span x-show="copiado === 'flyer'" x-cloak>Copiado ✓</span>
                    </button>
                    <p class="text-xs text-neutral-400 break-all" x-text="flyerUrl"></p>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function respuestaLeads() {
    return {
        nombre: '',
        copiado: '',
        flyerUrl: 'https://licitacionesmype.pe/images/wsp/vigilante-info-1350.png',
        waTexto: `Hola {nombre} 👋 Gracias por escribir a Vigilante SEACE.

Te comparto cómo funciona:
• Te avisamos el mismo día cuando el Estado publica una licitación de tu rubro (WhatsApp, Telegram o correo).
• La IA analiza el TDR y te dice si calificas antes de postular.
• Incluye buscador de contratos menores y mayores + seguimiento de procesos.

Actívalo hoy y tienes 5 días de garantía: si no te convence, te devolvemos tu dinero. Crea tu cuenta aquí: https://licitacionesmype.pe/register

¿Te muestro el sistema en una demo rápida de 20 minutos? ¿Qué día te queda bien?`,
        correoAsunto: 'Vigilante SEACE — así te avisamos de las licitaciones de tu rubro',
        correoTexto: `Hola {nombre} 👋

Gracias por tu interés en Vigilante SEACE. Te resumo cómo ayudamos a proveedores del Estado:

🔔 Alertas el mismo día: convocatorias de tu rubro directo a WhatsApp, Telegram o correo, apenas el Estado las publica.
🤖 Análisis de TDR con IA: la IA lee las bases y te dice si calificas, antes de invertir en postular.
🔍 Contratos menores y mayores: buscador completo con entidades, montos y estados.
📊 Seguimiento de tus procesos: plazos y documentos siempre a la vista.

Actívalo hoy y tienes 5 días de garantía: si no te convence, te devolvemos tu dinero: https://licitacionesmype.pe/register

¿Prefieres una demo de 20 minutos? Responde a este correo o escríbenos al WhatsApp +51 918 874 873.

Saludos,
Equipo Vigilante SEACE
Sunqupacha S.A.C. · licitacionesmype.pe
Servicio privado e independiente, no afiliado al OSCE ni al Estado peruano.`,
        testiTexto: `Hola {nombre} 👋 Te escribo del equipo de Vigilante SEACE. ¡Gracias por confiar en el sistema!

Estamos mejorando nuestra página y nos encantaría mostrar tu experiencia: ¿nos regalas 1-2 líneas sobre cómo te ayuda? (Ej: "me avisan el mismo día", "ya no reviso el SEACE a mano", "encontré procesos que antes se me pasaban").

Con tu permiso publicaríamos tu nombre y empresa. ¡Gracias!`,
        aplicarNombre() {
            const reemplazo = this.nombre.trim() !== '' ? this.nombre.trim() : 'estimado proveedor';
            this.waTexto = this.waTexto.replaceAll('{nombre}', reemplazo);
            this.correoTexto = this.correoTexto.replaceAll('{nombre}', reemplazo);
            this.testiTexto = this.testiTexto.replaceAll('{nombre}', reemplazo);
        },
        async copiar(texto, seccion) {
            try {
                await navigator.clipboard.writeText(texto);
                this.copiado = seccion;
                setTimeout(() => { if (this.copiado === seccion) this.copiado = ''; }, 1800);
            } catch (e) {
                alert('No se pudo copiar. Copia manualmente.');
            }
        },
    }
}
</script>
@endsection
