@extends('layouts.public')

@section('title', 'Cambios en las alertas por WhatsApp — Vigilante SEACE')
@section('meta_description', 'El canal de WhatsApp está temporalmente deshabilitado para alertas automáticas desde el 1 de octubre de 2026, por los nuevos precios y políticas de WhatsApp Business. Tus alertas por Telegram y correo siguen funcionando igual.')
@section('noindex', '1')

@section('content')
<article class="max-w-3xl mx-auto px-6 py-12 lg:py-16">

    {{-- ── Encabezado ── --}}
    <header class="text-center">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-700 text-[11px] font-bold uppercase tracking-wide">
            Actualización de servicio
        </span>
        <h1 class="mt-4 text-3xl lg:text-4xl font-bold text-neutral-900 leading-tight">
            WhatsApp está temporalmente deshabilitado para alertas
        </h1>
        <p class="mt-4 text-base lg:text-lg text-neutral-600 leading-relaxed max-w-2xl mx-auto">
            Desde el <strong class="text-neutral-900">1 de octubre de 2026</strong>, Meta aplica nuevas políticas
            y tarifas a WhatsApp Business. Por eso, el canal de WhatsApp está <strong class="text-neutral-900">temporalmente
            deshabilitado para alertas automáticas en todas las suscripciones</strong>, incluido Premium.
        </p>
        <p class="mt-3 text-sm text-neutral-400">Última actualización: {{ now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</p>
    </header>

    {{-- ── Qué cambia / qué sigue igual ── --}}
    <section class="mt-10 grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="rounded-3xl border border-neutral-200 bg-neutral-50 p-6">
            <div class="w-10 h-10 rounded-2xl bg-red-50 border border-red-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <h2 class="mt-3 text-base font-bold text-neutral-900">Qué cambia (temporalmente)</h2>
            <ul class="mt-3 space-y-2 text-sm text-neutral-600">
                <li class="flex gap-2"><span class="text-red-400 shrink-0">•</span> Las alertas automáticas por WhatsApp quedan en pausa mientras el canal esté deshabilitado.</li>
                <li class="flex gap-2"><span class="text-red-400 shrink-0">•</span> Aplica a todos los planes y tipos de alerta (menores, mayores y buena pro).</li>
                <li class="flex gap-2"><span class="text-red-400 shrink-0">•</span> El bot no envía avisos proactivos por ese canal.</li>
            </ul>
        </div>
        <div class="rounded-3xl border border-green-200 bg-green-50/50 p-6">
            <div class="w-10 h-10 rounded-2xl bg-white border border-green-200 flex items-center justify-center">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h2 class="mt-3 text-base font-bold text-neutral-900">Qué sigue igual</h2>
            <ul class="mt-3 space-y-2 text-sm text-neutral-600">
                <li class="flex gap-2"><span class="text-green-500 shrink-0">✓</span> Alertas por <strong class="text-neutral-800">Telegram</strong>, sin costo adicional.</li>
                <li class="flex gap-2"><span class="text-green-500 shrink-0">✓</span> Alertas por <strong class="text-neutral-800">correo electrónico</strong>.</li>
                <li class="flex gap-2"><span class="text-green-500 shrink-0">✓</span> Buscador, análisis IA, direccionamiento, proformas, compatibilidad y seguimiento.</li>
                <li class="flex gap-2"><span class="text-green-500 shrink-0">✓</span> Soporte humano por WhatsApp.</li>
            </ul>
        </div>
    </section>

    {{-- ── Por qué ── --}}
    <section class="mt-10 rounded-3xl border border-neutral-200 bg-white p-6 lg:p-8">
        <h2 class="text-xl font-bold text-neutral-900">¿Por qué tomamos esta decisión?</h2>
        <div class="mt-4 space-y-3 text-sm text-neutral-600 leading-relaxed">
            <p>
                WhatsApp Business cobra a las empresas por cada conversación que inician. Además, si el usuario
                no escribió en las últimas 24 horas, cada plantilla de notificación tiene un costo mayor.
            </p>
            <p>
                Con las nuevas políticas y tarifas de Meta, mantener el envío masivo de alertas por WhatsApp
                implicaría subir el precio del plan. Preferimos <strong class="text-neutral-900">conservar tu precio
                y priorizar los canales que no dependen de esas tarifas</strong>: Telegram y correo electrónico.
            </p>
        </div>
    </section>

    {{-- ── Qué hacer ── --}}
    <section class="mt-10 rounded-3xl bg-brand-800 text-white p-6 lg:p-8">
        <h2 class="text-xl font-bold">¿Qué debo hacer para seguir recibiendo alertas?</h2>
        <ol class="mt-5 space-y-4 text-sm text-white/90">
            <li class="flex gap-3">
                <span class="w-6 h-6 rounded-full bg-white/15 flex items-center justify-center text-xs font-bold shrink-0">1</span>
                <span>Conecta tu <strong>Telegram</strong> en Configuración de Alertas. Toma un minuto con el código QR.</span>
            </li>
            <li class="flex gap-3">
                <span class="w-6 h-6 rounded-full bg-white/15 flex items-center justify-center text-xs font-bold shrink-0">2</span>
                <span>Verifica que tu <strong>correo electrónico</strong> esté actualizado en tu cuenta.</span>
            </li>
            <li class="flex gap-3">
                <span class="w-6 h-6 rounded-full bg-white/15 flex items-center justify-center text-xs font-bold shrink-0">3</span>
                <span>Listo: seguirás recibiendo los procesos que calzan con tus palabras clave, sin interrupciones.</span>
            </li>
        </ol>
        <div class="mt-6 flex flex-col sm:flex-row gap-3">
            <a href="{{ route('configuracion-alertas') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-full bg-white text-brand-800 text-sm font-bold hover:bg-white/90 transition-colors">
                Configurar mis alertas
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
            <a href="https://wa.me/51918874873" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-full border border-white/30 text-white text-sm font-semibold hover:bg-white/10 transition-colors">
                Escribir a soporte
            </a>
        </div>
    </section>

    {{-- ── Preguntas frecuentes ── --}}
    <section class="mt-10">
        <h2 class="text-xl font-bold text-neutral-900">Preguntas frecuentes</h2>
        <div class="mt-4 space-y-3">
            @php
                $faqs = [
                    ['¿Desde cuándo rige el cambio?', 'Desde el 1 de octubre de 2026 el canal de WhatsApp queda temporalmente deshabilitado. Hasta el 30 de setiembre las alertas se enviaron con normalidad.'],
                    ['¿Es definitivo?', 'No. Es temporal: lo reactivaremos si Meta cambia las condiciones o si encontramos una forma sostenible de volver a ofrecerlo sin subir el precio del plan.'],
                    ['¿Cambia el precio de mi plan?', 'No. Este ajuste no modifica el precio de tu suscripción. Tampoco tienes que hacer ningún trámite adicional.'],
                    ['¿Voy a perder mi historial de notificaciones?', 'No. Todo tu historial sigue disponible en “Mis Procesos Notificados”, y puedes reenviarte los pendientes por Telegram.'],
                    ['¿El bot de WhatsApp deja de existir?', 'El canal queda temporalmente deshabilitado. El soporte humano sigue disponible por WhatsApp para ayudarte con tu cuenta o tu plan.'],
                    ['¿Telegram tiene algún costo?', 'No. Las alertas por Telegram y correo están incluidas en tu plan, sin costo adicional.'],
                    ['¿Por qué no subir el precio del plan y mantener WhatsApp?', 'Preferimos no trasladarte un costo variable y creciente que depende de las tarifas de Meta. Telegram y el correo cubren la misma necesidad: avisarte el mismo día de publicación.'],
                ];
            @endphp
            @foreach($faqs as [$pregunta, $respuesta])
                <details class="group rounded-2xl border border-neutral-200 bg-white open:bg-neutral-50 transition-colors">
                    <summary class="flex items-center justify-between gap-4 px-5 py-4 cursor-pointer list-none">
                        <span class="text-sm font-semibold text-neutral-900">{{ $pregunta }}</span>
                        <svg class="w-4 h-4 text-neutral-400 shrink-0 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <div class="px-5 pb-4 text-sm text-neutral-600 leading-relaxed">{{ $respuesta }}</div>
                </details>
            @endforeach
        </div>
    </section>

    {{-- ── Cierre ── --}}
    <section class="mt-10 text-center">
        <p class="text-sm text-neutral-500">
            Si tienes dudas sobre tu suscripción o quieres migrar tus alertas a Telegram, escríbenos y te ayudamos.
        </p>
        <div class="mt-4 flex flex-col sm:flex-row justify-center gap-3">
            <a href="{{ route('configuracion-alertas') }}" class="inline-flex items-center justify-center px-6 py-3 rounded-full bg-primary-500 hover:bg-primary-600 text-white text-sm font-bold transition-colors shadow-sm">
                Ir a Configuración de Alertas
            </a>
            <a href="{{ route('contacto') }}" class="inline-flex items-center justify-center px-6 py-3 rounded-full border border-neutral-200 text-neutral-700 hover:border-neutral-300 text-sm font-semibold transition-colors">
                Contactar a soporte
            </a>
        </div>
    </section>

</article>
@endsection
