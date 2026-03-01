@extends('layouts.public')

@section('title', 'Manual del Usuario — Licitaciones MYPe')
@section('meta_description', 'Guía completa para usar Licitaciones MYPe: buscador de licitaciones SEACE, análisis con IA, notificaciones automáticas, score de compatibilidad y más.')

@section('content')
<div x-data="{
    activeTab: window.location.hash ? window.location.hash.substring(1) : 'inicio',
    openFaq: null,
    setTab(id) {
        this.activeTab = id;
        window.history.replaceState(null, '', '#' + id);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },
    toggleFaq(id) {
        this.openFaq = this.openFaq === id ? null : id;
    },
    tabs: [
        { id: 'inicio', icon: '🏠', label: 'Introducción' },
        { id: 'buscador', icon: '🔍', label: 'Buscador' },
        { id: 'dashboard', icon: '📊', label: 'Dashboard' },
        { id: 'premium', icon: '⭐', label: 'Premium' },
        { id: 'bots', icon: '🤖', label: 'Bots' },
        { id: 'faq', icon: '❓', label: 'FAQ' },
    ]
}">

    {{-- ═══ HERO ═══ --}}
    <div class="bg-gradient-to-br from-brand-900 via-brand-800 to-brand-600 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 800 300" fill="none">
                <circle cx="700" cy="50" r="200" fill="white"/>
                <circle cx="100" cy="250" r="150" fill="white"/>
            </svg>
        </div>
        <div class="relative z-10 max-w-4xl mx-auto px-6 py-12 sm:py-16">
            <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm text-white text-xs font-semibold px-4 py-1.5 rounded-full mb-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                Manual del Usuario
            </div>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-white mb-3">
                Guía Completa de Licitaciones MYPe
            </h1>
            <p class="text-primary-200 text-base sm:text-lg leading-relaxed max-w-2xl">
                Aprende a usar todas las herramientas para monitorear licitaciones del Estado peruano con Vigilante SEACE.
            </p>
        </div>
    </div>

    {{-- ═══ TAB NAVIGATION ═══ --}}
    <div class="sticky top-[73px] z-40 bg-white border-b border-neutral-100 shadow-sm">
        <div class="max-w-4xl mx-auto px-6">
            <div class="flex items-center gap-1 overflow-x-auto scrollbar-hide py-2 -mx-1">
                <template x-for="tab in tabs" :key="tab.id">
                    <button
                        @click="setTab(tab.id)"
                        :class="activeTab === tab.id
                            ? 'bg-brand-800 text-white shadow-md'
                            : 'text-neutral-500 hover:bg-neutral-50 hover:text-neutral-700'"
                        class="flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium transition-all whitespace-nowrap shrink-0"
                    >
                        <span x-text="tab.icon" class="text-sm"></span>
                        <span x-text="tab.label"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>

    {{-- ═══ CONTENIDO POR TABS ═══ --}}
    <div class="max-w-4xl mx-auto px-6 py-10">

        {{-- ━━━ TAB 1: INTRODUCCIÓN ━━━ --}}
        <div x-show="activeTab === 'inicio'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
            <div class="space-y-8">
                <section class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-8">
                    <h2 class="text-2xl font-bold text-neutral-900 mb-4">¿Qué es Licitaciones MYPe?</h2>
                    <p class="text-neutral-600 leading-relaxed mb-4">
                        <strong class="text-neutral-900">Licitaciones MYPe</strong> es una plataforma de monitoreo inteligente que te permite rastrear, analizar y recibir alertas automáticas sobre licitaciones y contrataciones del Estado peruano publicadas en el SEACE (Sistema Electrónico de Contrataciones del Estado).
                    </p>
                    <p class="text-neutral-600 leading-relaxed mb-6">
                        El motor de nuestra plataforma, <strong class="text-brand-800">Vigilante SEACE</strong>, trabaja 24/7 monitoreando el portal de contrataciones del Estado para que tú no tengas que hacerlo.
                    </p>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div class="bg-neutral-50 rounded-2xl p-5 border border-neutral-200">
                            <h3 class="text-sm font-bold text-neutral-900 mb-2">🆓 Plan Gratuito</h3>
                            <ul class="text-sm text-neutral-600 space-y-1.5">
                                <li class="flex items-center gap-2"><span class="text-brand-800">✓</span> Buscador público de procesos</li>
                                <li class="flex items-center gap-2"><span class="text-brand-800">✓</span> Descarga de documentos TDR</li>
                                <li class="flex items-center gap-2"><span class="text-brand-800">✓</span> Dashboard de gráficos</li>
                                <li class="flex items-center gap-2"><span class="text-brand-800">✓</span> Vista de detalle de cada proceso</li>
                            </ul>
                        </div>
                        <div class="bg-brand-900/5 rounded-2xl p-5 border border-primary-400/15">
                            <h3 class="text-sm font-bold text-brand-800 mb-2">⭐ Plan Premium</h3>
                            <ul class="text-sm text-neutral-600 space-y-1.5">
                                <li class="flex items-center gap-2"><span class="text-brand-800">✓</span> Análisis de TDR con IA</li>
                                <li class="flex items-center gap-2"><span class="text-brand-800">✓</span> Score de compatibilidad</li>
                                <li class="flex items-center gap-2"><span class="text-brand-800">✓</span> Notificaciones Telegram / WhatsApp / Email</li>
                                <li class="flex items-center gap-2"><span class="text-brand-800">✓</span> Seguimiento en calendario</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <section class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-8">
                    <h2 class="text-2xl font-bold text-neutral-900 mb-6">¿Cómo funciona?</h2>
                    <div class="space-y-6">
                        @foreach ([
                            ['Crea tu cuenta', 'Regístrate gratis en minutos. Solo necesitas un correo electrónico.'],
                            ['Busca procesos', 'Usa el buscador público para explorar licitaciones por palabra clave, departamento, objeto o entidad.'],
                            ['Activa Premium', 'Desbloquea análisis con IA, notificaciones automáticas y score de compatibilidad.'],
                            ['Recibe alertas', 'Configura tus palabras clave y recibe notificaciones por Telegram, WhatsApp o Email.'],
                        ] as $i => $step)
                        <div class="flex items-start gap-4">
                            <span class="bg-brand-800 text-white text-sm font-bold w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0">{{ $i + 1 }}</span>
                            <div>
                                <p class="text-sm font-semibold text-neutral-900 mb-1">{{ $step[0] }}</p>
                                <p class="text-sm text-neutral-500">{{ $step[1] }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </section>

                <div class="flex justify-end">
                    <button @click="setTab('buscador')" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-800 hover:text-brand-900 transition-colors">
                        Siguiente: Buscador Público
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- ━━━ TAB 2: BUSCADOR PÚBLICO ━━━ --}}
        <div x-show="activeTab === 'buscador'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
            <div class="space-y-8">
                <section class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-8">
                    <h2 class="text-2xl font-bold text-neutral-900 mb-2">🔍 Buscador Público</h2>
                    <p class="text-sm text-neutral-400 mb-6">Accesible sin iniciar sesión</p>

                    <p class="text-neutral-600 leading-relaxed mb-6">
                        El Buscador Público es la herramienta principal de la plataforma. Te permite buscar procesos de contratación del Estado peruano en tiempo real directamente desde la API del SEACE.
                    </p>

                    <h3 class="text-lg font-bold text-neutral-900 mb-3">Filtros disponibles</h3>
                    <div class="overflow-x-auto mb-6">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-neutral-200">
                                    <th class="text-left py-3 px-4 font-semibold text-neutral-900">Filtro</th>
                                    <th class="text-left py-3 px-4 font-semibold text-neutral-900">Descripción</th>
                                </tr>
                            </thead>
                            <tbody class="text-neutral-600">
                                <tr class="border-b border-neutral-50"><td class="py-3 px-4 font-medium">🔤 Palabra clave</td><td class="py-3 px-4">Busca en la descripción del proceso (ej: "laptop", "consultoria")</td></tr>
                                <tr class="border-b border-neutral-50"><td class="py-3 px-4 font-medium">🏢 Entidad</td><td class="py-3 px-4">Escribe al menos 3 letras para ver sugerencias de entidades públicas</td></tr>
                                <tr class="border-b border-neutral-50"><td class="py-3 px-4 font-medium">🎯 Objeto de contrato</td><td class="py-3 px-4">Bien, Servicio, Obra o Consultoría de Obra</td></tr>
                                <tr class="border-b border-neutral-50"><td class="py-3 px-4 font-medium">📋 Estado</td><td class="py-3 px-4">Vigente, En Evaluación, Culminado, Borrador</td></tr>
                                <tr class="border-b border-neutral-50"><td class="py-3 px-4 font-medium">📍 Departamento</td><td class="py-3 px-4">Filtra por ubicación geográfica (incluye provincia y distrito)</td></tr>
                                <tr><td class="py-3 px-4 font-medium">📄 Registros/página</td><td class="py-3 px-4">Configura cuántos resultados ver por página</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <h3 class="text-lg font-bold text-neutral-900 mb-3">Acciones por proceso</h3>
                    <p class="text-neutral-600 leading-relaxed mb-4">Al hacer clic en un proceso verás su detalle completo con varias opciones:</p>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <div class="flex items-start gap-3 bg-neutral-50 rounded-2xl p-4">
                            <span class="text-lg">📥</span>
                            <div>
                                <p class="text-sm font-semibold text-neutral-900">Descargar TDR</p>
                                <p class="text-xs text-neutral-500">Descarga el PDF de Términos de Referencia</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 bg-brand-900/5 rounded-2xl p-4 border border-primary-400/15">
                            <span class="text-lg">🤖</span>
                            <div>
                                <p class="text-sm font-semibold text-neutral-900">Analizar TDR con IA <span class="text-[10px] bg-brand-800 text-white px-2 py-0.5 rounded-full ml-1">Premium</span></p>
                                <p class="text-xs text-neutral-500">Analiza requisitos, penalidades y reglas</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 bg-brand-900/5 rounded-2xl p-4 border border-primary-400/15">
                            <span class="text-lg">📌</span>
                            <div>
                                <p class="text-sm font-semibold text-neutral-900">Hacer Seguimiento <span class="text-[10px] bg-brand-800 text-white px-2 py-0.5 rounded-full ml-1">Premium</span></p>
                                <p class="text-xs text-neutral-500">Agrega al calendario de seguimientos</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 bg-brand-900/5 rounded-2xl p-4 border border-primary-400/15">
                            <span class="text-lg">🏅</span>
                            <div>
                                <p class="text-sm font-semibold text-neutral-900">Compatibilidad IA <span class="text-[10px] bg-brand-800 text-white px-2 py-0.5 rounded-full ml-1">Premium</span></p>
                                <p class="text-xs text-neutral-500">Calcula compatibilidad con tu empresa</p>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="flex justify-between">
                    <button @click="setTab('inicio')" class="inline-flex items-center gap-2 text-sm font-semibold text-neutral-500 hover:text-neutral-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Introducción
                    </button>
                    <button @click="setTab('dashboard')" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-800 hover:text-brand-900 transition-colors">
                        Siguiente: Dashboard
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- ━━━ TAB 3: DASHBOARD + SEGUIMIENTOS + MIS PROCESOS ━━━ --}}
        <div x-show="activeTab === 'dashboard'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
            <div class="space-y-8">
                <section class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-8">
                    <h2 class="text-2xl font-bold text-neutral-900 mb-2">📊 Dashboard de Gráficos</h2>
                    <p class="text-sm text-neutral-400 mb-6">Requiere iniciar sesión</p>

                    <p class="text-neutral-600 leading-relaxed mb-6">
                        Visualiza datos y estadísticas de las contrataciones del Estado peruano para entender el panorama de las licitaciones.
                    </p>

                    <h3 class="text-lg font-bold text-neutral-900 mb-3">Métricas y gráficos incluidos</h3>
                    <div class="grid sm:grid-cols-2 gap-3 mb-6">
                        @foreach ([
                            ['KPIs (Contadores)', 'Total de procesos desglosados por estado.'],
                            ['Distribución por Estado', 'Gráfico de contratos por cada estado.'],
                            ['Distribución por Objeto', 'Proporción entre bienes, servicios, obras y consultorías.'],
                            ['Publicaciones por Mes', 'Tendencias de los últimos 6 meses.'],
                            ['Top Entidades', 'Las 8 entidades con más procesos.'],
                            ['Distribución por Departamento', 'Top 10 departamentos con más contrataciones.'],
                        ] as $i => $item)
                        <div class="flex items-start gap-3">
                            <span class="bg-brand-800 text-white text-xs font-bold w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0">{{ $i + 1 }}</span>
                            <div>
                                <p class="text-sm font-semibold text-neutral-900">{{ $item[0] }}</p>
                                <p class="text-xs text-neutral-500">{{ $item[1] }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="bg-neutral-50 rounded-2xl p-5 border border-neutral-200">
                        <p class="text-sm font-semibold text-neutral-900 mb-2">💡 Filtros disponibles</p>
                        <p class="text-xs text-neutral-600">Búsqueda por texto, Estado, Objeto de contrato, Departamento y Rango de fechas.</p>
                    </div>
                </section>

                <section class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-8">
                    <h2 class="text-2xl font-bold text-neutral-900 mb-2">📅 Seguimientos (Calendario)</h2>
                    <p class="text-xs font-medium text-brand-800 bg-brand-800/5 inline-block px-3 py-1 rounded-full mb-6">⭐ Función Premium</p>

                    <p class="text-neutral-600 leading-relaxed mb-6">
                        Agrega procesos a un calendario visual mensual para organizar tus postulaciones y nunca perder una fecha límite.
                    </p>

                    <h3 class="text-lg font-bold text-neutral-900 mb-3">¿Cómo funciona?</h3>
                    <div class="space-y-3 mb-6">
                        @foreach ([
                            'Desde el Buscador, abre el detalle de un proceso y haz clic en <strong>"📌 Hacer Seguimiento"</strong>.',
                            'El proceso aparecerá en tu <strong>calendario personal</strong> en los días de cotización.',
                            'Los procesos se muestran con <strong>colores de urgencia</strong> según los días restantes.',
                        ] as $i => $step)
                        <div class="flex items-start gap-4">
                            <span class="bg-brand-800 text-white text-xs font-bold w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0">{{ $i + 1 }}</span>
                            <p class="text-sm text-neutral-600">{!! $step !!}</p>
                        </div>
                        @endforeach
                    </div>

                    <h3 class="text-lg font-bold text-neutral-900 mb-3">Indicadores de urgencia</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="rounded-2xl p-4 text-center bg-red-50 border border-red-200">
                            <p class="text-2xl mb-1">🔴</p>
                            <p class="text-xs font-bold text-red-700">Crítico</p>
                            <p class="text-xs text-red-500">≤ 2 días</p>
                        </div>
                        <div class="rounded-2xl p-4 text-center bg-orange-50 border border-orange-200">
                            <p class="text-2xl mb-1">🟠</p>
                            <p class="text-xs font-bold text-orange-700">Alto</p>
                            <p class="text-xs text-orange-500">≤ 5 días</p>
                        </div>
                        <div class="rounded-2xl p-4 text-center bg-yellow-50 border border-yellow-200">
                            <p class="text-2xl mb-1">🟡</p>
                            <p class="text-xs font-bold text-yellow-700">Medio</p>
                            <p class="text-xs text-yellow-500">≤ 7 días</p>
                        </div>
                        <div class="rounded-2xl p-4 text-center bg-green-50 border border-green-200">
                            <p class="text-2xl mb-1">🟢</p>
                            <p class="text-xs font-bold text-green-700">Estable</p>
                            <p class="text-xs text-green-500">> 7 días</p>
                        </div>
                    </div>
                </section>

                <section class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-8">
                    <h2 class="text-2xl font-bold text-neutral-900 mb-2">📋 Mis Procesos Notificados</h2>
                    <p class="text-xs font-medium text-brand-800 bg-brand-800/5 inline-block px-3 py-1 rounded-full mb-6">⭐ Función Premium</p>

                    <p class="text-neutral-600 leading-relaxed mb-6">
                        Historial completo de todos los procesos notificados por Telegram, WhatsApp o Email según tus palabras clave.
                    </p>

                    <h3 class="text-lg font-bold text-neutral-900 mb-3">Re-notificación</h3>
                    <p class="text-neutral-600 text-sm leading-relaxed mb-3">Puedes re-enviar cualquier proceso a tus canales activos:</p>
                    <ul class="space-y-2 text-sm text-neutral-600">
                        @foreach (['Telegram — Re-envía a todas tus suscripciones activas', 'WhatsApp — Re-envía a tu suscripción activa', 'Email — Re-envía al correo suscrito'] as $ch)
                        <li class="flex items-center gap-2">
                            <span class="w-5 h-5 bg-brand-800 rounded-full flex items-center justify-center text-white text-xs">✓</span>
                            <strong>{{ Str::before($ch, ' —') }}</strong>{{ Str::after($ch, Str::before($ch, ' —')) }}
                        </li>
                        @endforeach
                    </ul>
                </section>

                <div class="flex justify-between">
                    <button @click="setTab('buscador')" class="inline-flex items-center gap-2 text-sm font-semibold text-neutral-500 hover:text-neutral-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Buscador
                    </button>
                    <button @click="setTab('premium')" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-800 hover:text-brand-900 transition-colors">
                        Siguiente: Premium
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- ━━━ TAB 4: PREMIUM (Funciones + Keywords + Score + Planes) ━━━ --}}
        <div x-show="activeTab === 'premium'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
            <div class="space-y-8">
                <section class="relative bg-gradient-to-br from-brand-900 via-brand-800 to-brand-600 rounded-3xl p-8 overflow-hidden">
                    <div class="absolute inset-0 opacity-5">
                        <svg class="w-full h-full" viewBox="0 0 800 400" fill="none">
                            <circle cx="650" cy="80" r="250" fill="white"/>
                            <circle cx="150" cy="300" r="180" fill="white"/>
                        </svg>
                    </div>
                    <div class="relative z-10">
                        <h2 class="text-2xl font-bold text-white mb-2">⭐ Funciones Premium</h2>
                        <p class="text-primary-200 text-sm mb-8">Lo que desbloqueas con tu suscripción</p>

                        <div class="grid sm:grid-cols-2 gap-4">
                            @foreach ([
                                ['🤖 Análisis TDR con IA', 'La IA extrae: requisitos, reglas de ejecución, penalidades y monto referencial. Ahorra horas.'],
                                ['🏅 Score de Compatibilidad', 'Evalúa qué tan compatible es cada proceso con tu empresa. Puntaje de 0 a 10.'],
                                ['🔔 Notificaciones Automáticas', 'Alertas por Telegram, WhatsApp o Email cuando aparezca un proceso con tus keywords. 24/7.'],
                                ['📅 Seguimientos & Calendario', 'Agenda procesos en un calendario visual con alertas de urgencia por colores.'],
                                ['🔑 Palabras Clave', 'Configura hasta 20 keywords por suscripción. Solo recibes lo que te importa.'],
                                ['📋 Historial de Procesos', 'Historial completo de procesos notificados con opción de re-notificar.'],
                            ] as $feat)
                            <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-5">
                                <h3 class="font-bold text-white mb-2">{{ $feat[0] }}</h3>
                                <p class="text-primary-200 text-sm leading-relaxed">{{ $feat[1] }}</p>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-8">
                    <h2 class="text-2xl font-bold text-neutral-900 mb-2">🔑 Palabras Clave (Keywords)</h2>
                    <p class="text-xs font-medium text-brand-800 bg-brand-800/5 inline-block px-3 py-1 rounded-full mb-6">⭐ Clave para las notificaciones</p>

                    <p class="text-neutral-600 leading-relaxed mb-6">
                        Las palabras clave son el corazón del sistema de notificaciones. El bot monitorea constantemente el SEACE y te avisa cuando un proceso coincida con alguna de tus keywords.
                    </p>

                    <h3 class="text-lg font-bold text-neutral-900 mb-3">¿Cómo configurarlas?</h3>
                    <div class="space-y-3 mb-6">
                        <div class="flex items-start gap-4">
                            <span class="bg-brand-800 text-white text-xs font-bold w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0">1</span>
                            <p class="text-sm text-neutral-600"><strong>Desde la web:</strong> ve a tu Perfil y agrega tus keywords en la sección de suscripciones.</p>
                        </div>
                        <div class="flex items-start gap-4">
                            <span class="bg-brand-800 text-white text-xs font-bold w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0">2</span>
                            <p class="text-sm text-neutral-600"><strong>Desde Telegram:</strong> usa el botón "🔑 Mis Keywords" en el bot y agrégalas directamente.</p>
                        </div>
                    </div>

                    <h3 class="text-lg font-bold text-neutral-900 mb-3">Ejemplos de buenas palabras clave</h3>
                    <div class="flex flex-wrap gap-2 mb-6">
                        @foreach (['laptop', 'consultoría', 'limpieza', 'software', 'capacitación', 'seguridad', 'mantenimiento', 'servicio de internet', 'útiles de oficina', 'transporte'] as $keyword)
                            <span class="inline-flex items-center bg-brand-800/5 text-brand-800 text-xs font-medium px-3 py-1.5 rounded-full border border-primary-400/20">{{ $keyword }}</span>
                        @endforeach
                    </div>

                    <div class="bg-orange-50 rounded-2xl p-5 border border-orange-200">
                        <p class="text-sm font-semibold text-orange-800 mb-2">⚠️ Tips importantes</p>
                        <ul class="text-xs text-orange-700 space-y-1.5">
                            <li>• Usa palabras específicas y relevantes a tu negocio</li>
                            <li>• Evita palabras muy genéricas como "servicio" sola</li>
                            <li>• Combina términos técnicos con descripciones comunes</li>
                            <li>• Revisa y actualiza tus keywords cada cierto tiempo</li>
                        </ul>
                    </div>
                </section>

                <section class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-8">
                    <h2 class="text-2xl font-bold text-neutral-900 mb-2">🏅 Score de Compatibilidad</h2>
                    <p class="text-xs font-medium text-brand-800 bg-brand-800/5 inline-block px-3 py-1 rounded-full mb-6">⭐ Potenciada por IA</p>

                    <p class="text-neutral-600 leading-relaxed mb-6">
                        Puntaje calculado por IA que mide qué tan adecuado es un proceso SEACE para tu empresa, basándose en el TDR y el perfil de tu compañía.
                    </p>

                    <div class="bg-neutral-50 rounded-2xl p-6 mb-6">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="flex-1 h-4 rounded-full bg-gradient-to-r from-red-400 via-yellow-400 to-green-500 relative">
                                <span class="absolute left-0 -top-6 text-xs text-neutral-500 font-medium">0</span>
                                <span class="absolute left-1/2 -translate-x-1/2 -top-6 text-xs text-neutral-500 font-medium">5</span>
                                <span class="absolute right-0 -top-6 text-xs text-neutral-500 font-medium">10</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-3 text-center">
                            <div><p class="text-sm font-bold text-red-600">0 - 3.9</p><p class="text-xs text-neutral-500">Baja</p></div>
                            <div><p class="text-sm font-bold text-yellow-600">4.0 - 6.9</p><p class="text-xs text-neutral-500">Media</p></div>
                            <div><p class="text-sm font-bold text-green-600">7.0 - 10</p><p class="text-xs text-neutral-500">Alta</p></div>
                        </div>
                    </div>

                    <h3 class="text-lg font-bold text-neutral-900 mb-3">Factores que considera la IA</h3>
                    <ul class="space-y-2 text-sm text-neutral-600 mb-6">
                        <li class="flex items-start gap-2"><span class="text-brand-800">▸</span><span><strong>Perfil de tu empresa</strong> — Se compara con tu "company copy"</span></li>
                        <li class="flex items-start gap-2"><span class="text-brand-800">▸</span><span><strong>Requisitos del TDR</strong> — Evalúa experiencia y condiciones</span></li>
                        <li class="flex items-start gap-2"><span class="text-brand-800">▸</span><span><strong>Objeto de contrato</strong> — Compara con tus especialidades</span></li>
                        <li class="flex items-start gap-2"><span class="text-brand-800">▸</span><span><strong>Ubicación</strong> — Compatibilidad por zona de operación</span></li>
                    </ul>

                    <div class="bg-neutral-50 rounded-2xl p-5 border border-neutral-200">
                        <p class="text-sm font-semibold text-neutral-900 mb-2">💡 Requisito previo</p>
                        <p class="text-xs text-neutral-600">Para usar el score, debes configurar el <strong>"Company Copy"</strong> en tu suscripción del bot de Telegram.</p>
                    </div>
                </section>

                <section class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-8">
                    <h2 class="text-2xl font-bold text-neutral-900 mb-6">💎 Planes y Precios</h2>
                    <div class="grid sm:grid-cols-3 gap-4 mb-6">
                        <div class="rounded-2xl border border-neutral-200 p-6 text-center">
                            <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-2">Gratuito</p>
                            <p class="text-3xl font-extrabold text-neutral-900 mb-1">S/ 0</p>
                            <p class="text-xs text-neutral-400 mb-5">para siempre</p>
                            <ul class="text-xs text-neutral-600 space-y-2 text-left">
                                <li class="flex items-center gap-2"><span class="text-brand-800">✓</span> Buscador público</li>
                                <li class="flex items-center gap-2"><span class="text-brand-800">✓</span> Descarga de TDR</li>
                                <li class="flex items-center gap-2"><span class="text-brand-800">✓</span> Dashboard</li>
                                <li class="flex items-center gap-2 text-neutral-300"><span>✗</span> Análisis IA</li>
                            </ul>
                        </div>
                        <div class="rounded-2xl border-2 border-brand-800 p-6 text-center relative">
                            <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-brand-800 text-white text-xs font-bold px-4 py-1 rounded-full">Popular</span>
                            <p class="text-xs font-semibold text-brand-800 uppercase tracking-wider mb-2">Mensual</p>
                            <p class="text-3xl font-extrabold text-neutral-900 mb-1">S/ 49</p>
                            <p class="text-xs text-neutral-400 mb-5">por mes</p>
                            <ul class="text-xs text-neutral-600 space-y-2 text-left">
                                <li class="flex items-center gap-2"><span class="text-brand-800">✓</span> Todo lo gratuito</li>
                                <li class="flex items-center gap-2"><span class="text-brand-800">✓</span> Análisis TDR con IA</li>
                                <li class="flex items-center gap-2"><span class="text-brand-800">✓</span> Bots Telegram + WhatsApp</li>
                                <li class="flex items-center gap-2"><span class="text-brand-800">✓</span> Score compatibilidad</li>
                            </ul>
                        </div>
                        <div class="rounded-2xl border border-primary-400/30 bg-brand-900/5 p-6 text-center relative">
                            <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-brand-600 text-white text-xs font-bold px-4 py-1 rounded-full">Ahorra 20%</span>
                            <p class="text-xs font-semibold text-brand-800 uppercase tracking-wider mb-2">Anual</p>
                            <p class="text-3xl font-extrabold text-neutral-900 mb-1">S/ 470</p>
                            <p class="text-xs text-neutral-400 mb-5">por año</p>
                            <ul class="text-xs text-neutral-600 space-y-2 text-left">
                                <li class="flex items-center gap-2"><span class="text-brand-800">✓</span> Todo lo mensual</li>
                                <li class="flex items-center gap-2"><span class="text-brand-800">✓</span> 2 meses gratis</li>
                                <li class="flex items-center gap-2"><span class="text-brand-800">✓</span> Soporte prioritario</li>
                            </ul>
                        </div>
                    </div>
                    <div class="text-center">
                        <a href="{{ route('planes') }}" class="inline-flex items-center gap-2 bg-brand-800 text-white font-bold text-sm px-8 py-3 rounded-full hover:bg-brand-900 transition-colors">Ver Planes Completos →</a>
                    </div>
                </section>

                <div class="flex justify-between">
                    <button @click="setTab('dashboard')" class="inline-flex items-center gap-2 text-sm font-semibold text-neutral-500 hover:text-neutral-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Dashboard
                    </button>
                    <button @click="setTab('bots')" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-800 hover:text-brand-900 transition-colors">
                        Siguiente: Bots
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- ━━━ TAB 5: BOTS (Telegram + WhatsApp) ━━━ --}}
        <div x-show="activeTab === 'bots'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
            <div class="space-y-8">
                <section class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-8">
                    <h2 class="text-2xl font-bold text-neutral-900 mb-2">🤖 Bot de Telegram</h2>
                    <p class="text-xs font-medium text-brand-800 bg-brand-800/5 inline-block px-3 py-1 rounded-full mb-6">⭐ Tu asistente 24/7</p>

                    <p class="text-neutral-600 leading-relaxed mb-6">
                        El bot de Telegram te envía notificaciones automáticas y te permite analizar documentos directamente desde el chat.
                    </p>

                    <h3 class="text-lg font-bold text-neutral-900 mb-3">¿Cómo empezar?</h3>
                    <div class="space-y-3 mb-8">
                        @foreach ([
                            '<strong>Suscríbete</strong> al plan Premium desde <a href="' . route('planes') . '" class="text-brand-800 font-semibold underline">Planes</a>.',
                            '<strong>Configura tu suscripción Telegram</strong> desde tu Perfil: ingresa tu Chat ID y palabras clave.',
                            '<strong>Abre Telegram</strong> y busca el bot. ¡Comenzarás a recibir notificaciones!',
                        ] as $i => $step)
                        <div class="flex items-start gap-4">
                            <span class="bg-brand-800 text-white text-xs font-bold w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0">{{ $i + 1 }}</span>
                            <p class="text-sm text-neutral-600">{!! $step !!}</p>
                        </div>
                        @endforeach
                    </div>

                    <h3 class="text-lg font-bold text-neutral-900 mb-4">Botones en cada notificación</h3>
                    <div class="space-y-3 mb-6">
                        @foreach ([
                            ['🤖', 'Analizar TDR', 'Descarga el TDR y lo analiza con IA. Resumen con requisitos, reglas, penalidades y monto.'],
                            ['📥', 'Descargar TDR', 'Descarga el PDF directamente en tu chat de Telegram.'],
                            ['🏅', 'Compatibilidad IA', 'Calcula el score entre tu empresa y el proceso. Requiere "Company Copy".'],
                            ['🔄', 'Recalcular Score', 'Nuevo cálculo si actualizaste tu perfil de empresa.'],
                        ] as $btn)
                        <div class="flex items-start gap-4 bg-neutral-50 rounded-2xl p-5">
                            <span class="text-2xl flex-shrink-0">{{ $btn[0] }}</span>
                            <div>
                                <p class="text-sm font-bold text-neutral-900 mb-1">{{ $btn[1] }}</p>
                                <p class="text-xs text-neutral-500 leading-relaxed">{{ $btn[2] }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="bg-brand-900/5 rounded-2xl p-5 border border-primary-400/15">
                        <p class="text-sm font-semibold text-brand-800 mb-2">ℹ️ ¿Cómo obtener tu Chat ID?</p>
                        <p class="text-xs text-neutral-600">Busca <strong>@userinfobot</strong> en Telegram, envíale cualquier mensaje y te devolverá tu Chat ID.</p>
                    </div>
                </section>

                <section class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-8">
                    <h2 class="text-2xl font-bold text-neutral-900 mb-2">📱 Bot de WhatsApp</h2>
                    <p class="text-xs font-medium text-brand-800 bg-brand-800/5 inline-block px-3 py-1 rounded-full mb-6">⭐ Función Premium</p>

                    <p class="text-neutral-600 leading-relaxed mb-6">
                        Similar al bot de Telegram, te envía notificaciones automáticas directamente a tu número de teléfono registrado.
                    </p>

                    <h3 class="text-lg font-bold text-neutral-900 mb-3">Funcionalidades</h3>
                    <ul class="space-y-2 text-sm text-neutral-600">
                        @foreach ([
                            'Notificaciones automáticas por coincidencia de keywords',
                            'Información detallada del proceso en cada mensaje',
                            'Descarga de documentos TDR',
                            'Análisis de TDR con IA',
                            'Score de compatibilidad',
                        ] as $feat)
                        <li class="flex items-center gap-2">
                            <span class="w-5 h-5 bg-brand-800 rounded-full flex items-center justify-center text-white text-xs">✓</span>
                            {{ $feat }}
                        </li>
                        @endforeach
                    </ul>
                </section>

                <div class="flex justify-between">
                    <button @click="setTab('premium')" class="inline-flex items-center gap-2 text-sm font-semibold text-neutral-500 hover:text-neutral-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Premium
                    </button>
                    <button @click="setTab('faq')" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-800 hover:text-brand-900 transition-colors">
                        Siguiente: FAQ
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- ━━━ TAB 6: FAQ ━━━ --}}
        <div x-show="activeTab === 'faq'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
            <div class="space-y-8">
                <section class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-8">
                    <h2 class="text-2xl font-bold text-neutral-900 mb-2">❓ Preguntas Frecuentes</h2>
                    <p class="text-sm text-neutral-400 mb-8">Las dudas más comunes de nuestros usuarios</p>

                    <div class="space-y-3">
                        @php
                        $faqs = [
                            ['¿Necesito cuenta en el SEACE para usar la plataforma?', '<strong>No.</strong> Licitaciones MYPe consulta la información pública del SEACE por ti. Solo necesitas crear una cuenta en nuestra plataforma. El buscador público ni siquiera requiere registro.'],
                            ['¿Qué significa el score de compatibilidad?', 'Es un puntaje de <strong>0 a 10</strong> calculado por IA que indica qué tan compatible es un proceso con tu empresa. 7+ = alta compatibilidad. Requiere configurar tu "Company Copy".'],
                            ['¿Cada cuánto se actualizan los procesos?', 'Sincronización automática <strong>cada 42-50 minutos</strong> 24/7. El buscador público consulta la API del SEACE en tiempo real.'],
                            ['¿Cómo configuro las notificaciones Telegram?', 'Suscríbete Premium → Perfil → Agregar suscripción Telegram → Ingresa Chat ID (del bot @userinfobot) → Agrega palabras clave → ¡Activa!'],
                            ['¿Qué es el análisis de TDR con IA?', 'Nuestra IA lee los TDR completos y extrae: <strong>Requisitos de Calificación</strong>, <strong>Reglas de Ejecución</strong>, <strong>Penalidades</strong> y <strong>Monto Referencial</strong>. Ahorra horas de lectura.'],
                            ['¿Cuántas palabras clave puedo tener?', 'Hasta <strong>20 palabras clave</strong> por suscripción. Puedes crear múltiples suscripciones si necesitas más.'],
                            ['¿Puedo probar antes de pagar?', '<strong>¡Sí!</strong> <strong>15 días de prueba</strong> con todas las funciones Premium. El buscador y descarga de TDR son siempre gratuitos.'],
                            ['¿Qué métodos de pago aceptan?', 'Tarjeta de crédito y débito (Visa, Mastercard, Amex) vía MercadoPago. 100% seguro, sin redirecciones.'],
                            ['¿Cómo funciona el seguimiento?', '"📌 Hacer Seguimiento" en cualquier proceso → Se agrega al calendario con colores: <strong>rojo</strong> (≤2 días), <strong>naranja</strong> (≤5), <strong>amarillo</strong> (≤7), <strong>verde</strong> (>7).'],
                            ['¿Puedo cancelar mi suscripción?', '<strong>Sí.</strong> Cancela en cualquier momento. Disfrutas Premium hasta que termine el periodo contratado. Sin cargos ocultos.'],
                            ['¿Qué es el "Company Copy"?', 'Texto que describe servicios, experiencia y capacidades de tu empresa. La IA lo usa para calcular el score. Más detalle = más precisión.'],
                            ['¿Las notificaciones funcionan 24/7?', '<strong>Sí.</strong> El sistema opera 24/7. En cuanto se publica un proceso que coincida con tus keywords, recibes la notificación en minutos.'],
                        ];
                        @endphp

                        @foreach ($faqs as $i => $faq)
                        <div class="border border-neutral-100 rounded-2xl overflow-hidden">
                            <button @click="toggleFaq('faq{{ $i + 1 }}')" class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-neutral-50 transition-colors">
                                <span class="text-sm font-semibold text-neutral-900">{{ $faq[0] }}</span>
                                <svg class="w-5 h-5 text-neutral-400 transition-transform flex-shrink-0 ml-4" :class="openFaq === 'faq{{ $i + 1 }}' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="openFaq === 'faq{{ $i + 1 }}'" x-collapse class="px-6 pb-4">
                                <p class="text-sm text-neutral-600 leading-relaxed">{!! $faq[1] !!}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </section>

                {{-- CTA --}}
                <section class="bg-gradient-to-br from-brand-900 to-brand-600 rounded-3xl p-8 sm:p-12 text-center overflow-hidden relative">
                    <div class="relative z-10">
                        <h2 class="text-2xl sm:text-3xl font-extrabold text-white mb-3">¿Listo para empezar?</h2>
                        <p class="text-primary-200 text-sm sm:text-base mb-8 max-w-lg mx-auto">
                            No pierdas más oportunidades de negocio con el Estado. Deja que la tecnología trabaje por ti.
                        </p>
                        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                            <a href="{{ route('buscador.publico') }}" class="inline-flex items-center gap-2 bg-white text-brand-800 font-bold text-sm px-8 py-3 rounded-full hover:bg-neutral-50 transition-colors">
                                🔍 Explorar Buscador
                            </a>
                            @guest
                            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 bg-white/10 text-white border border-white/20 font-bold text-sm px-8 py-3 rounded-full hover:bg-white/20 transition-colors">
                                🚀 Crear Cuenta Gratis
                            </a>
                            @endguest
                        </div>
                    </div>
                </section>

                <section class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-8 text-center">
                    <h3 class="text-lg font-bold text-neutral-900 mb-2">¿Tienes más preguntas?</h3>
                    <p class="text-sm text-neutral-500 mb-5">Nuestro equipo está listo para ayudarte</p>
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 text-sm">
                        <a href="{{ route('contacto') }}" class="text-brand-800 font-semibold hover:underline">✉️ Formulario de contacto</a>
                        <span class="hidden sm:inline text-neutral-300">|</span>
                        <a href="mailto:services@sunqupacha.com" class="text-brand-800 font-semibold hover:underline">📧 services@sunqupacha.com</a>
                        <span class="hidden sm:inline text-neutral-300">|</span>
                        <span class="text-neutral-600">📞 +51 918 874 873</span>
                    </div>
                </section>

                <div class="flex justify-start">
                    <button @click="setTab('bots')" class="inline-flex items-center gap-2 text-sm font-semibold text-neutral-500 hover:text-neutral-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Bots
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
