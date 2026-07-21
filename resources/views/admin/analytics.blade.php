@extends('layouts.app')

@section('title', 'Analytics - Vigilante SEACE')

@section('content')
<div class="p-4 sm:p-6 flex flex-col gap-6 w-full max-w-full min-w-0">
    <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-8 border border-neutral-100">
        <div>
            <p class="text-xs font-semibold uppercase text-neutral-400 tracking-[0.2em]">Analytics</p>
            <h1 class="text-2xl lg:text-3xl font-bold text-neutral-900 mt-1">Dashboard de Metricas GA4</h1>
            <p class="text-sm text-neutral-500 mt-1">Ultimos 7 dias · {{ now()->format('d/m/Y') }}</p>
        </div>
    </div>

    {{-- KPIs principales --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        @foreach([
            ['label' => 'Usuarios', 'value' => $totals['activeUsers'] ?? '---', 'icon' => 'users', 'color' => 'blue'],
            ['label' => 'Sesiones', 'value' => $totals['sessions'] ?? '---', 'icon' => 'activity', 'color' => 'green'],
            ['label' => 'Page Views', 'value' => $totals['screenPageViews'] ?? '---', 'icon' => 'eye', 'color' => 'purple'],
            ['label' => 'Tiempo Promedio', 'value' => isset($totals['averageSessionDuration']) ? gmdate('i:s', (int)$totals['averageSessionDuration']) : '---', 'icon' => 'clock', 'color' => 'amber'],
            ['label' => 'Bounce Rate', 'value' => isset($totals['bounceRate']) ? round($totals['bounceRate'] * 100) . '%' : '---', 'icon' => 'trending-down', 'color' => 'red'],
        ] as $kpi)
        <div class="bg-white rounded-2xl shadow-soft border border-neutral-100 p-5">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 rounded-lg bg-{{ $kpi['color'] }}-100 flex items-center justify-center">
                    @if($kpi['icon'] === 'users')<svg class="w-4 h-4 text-{{ $kpi['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    @elseif($kpi['icon'] === 'activity')<svg class="w-4 h-4 text-{{ $kpi['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    @elseif($kpi['icon'] === 'eye')<svg class="w-4 h-4 text-{{ $kpi['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    @elseif($kpi['icon'] === 'clock')<svg class="w-4 h-4 text-{{ $kpi['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @else<svg class="w-4 h-4 text-{{ $kpi['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                    @endif
                </div>
                <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">{{ $kpi['label'] }}</span>
            </div>
            <p class="text-2xl font-black text-neutral-900">{{ $kpi['value'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Paginas mas visitadas --}}
    <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-6">
        <h3 class="text-sm font-bold text-neutral-900 mb-4">Paginas mas visitadas</h3>
        <div class="space-y-3">
            @php $validPages = array_filter($topPages); $maxViews = count($validPages) > 0 ? max(array_column($validPages, 'screenPageViews')) : 1; @endphp
            @foreach($validPages as $page)
            <div class="flex items-center gap-3">
                <span class="text-xs text-neutral-500 w-20 truncate" title="{{ $page['pageTitle'] }}">{{ Str::limit($page['pageTitle'], 25) }}</span>
                <div class="flex-1 h-6 bg-neutral-100 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-blue-500 to-blue-400 rounded-full flex items-center px-3" style="width: {{ max(($page['screenPageViews'] / $maxViews) * 100, 5) }}%">
                        <span class="text-[10px] font-bold text-white">{{ $page['screenPageViews'] }}</span>
                    </div>
                </div>
                <span class="text-[10px] text-neutral-400 w-12 text-right">{{ $page['activeUsers'] }} users</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Fuentes de trafico --}}
    <div class="grid md:grid-cols-2 gap-6">
        <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-6">
            <h3 class="text-sm font-bold text-neutral-900 mb-4">Como llegan los usuarios</h3>
            <div class="space-y-2">
                @foreach(array_filter($sources) as $src)
                <div class="flex items-center justify-between py-2 border-b border-neutral-50">
                    <span class="text-sm text-neutral-700">{{ $src['sessionSource'] }} / {{ $src['sessionMedium'] }}</span>
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-bold text-neutral-900">{{ $src['activeUsers'] }}</span>
                        <span class="text-[10px] text-neutral-400">{{ $src['sessions'] }} sesiones</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-6">
            <h3 class="text-sm font-bold text-neutral-900 mb-4">Eventos rastreados</h3>
            <div class="space-y-2">
                @foreach(array_filter($events) as $evt)
                <div class="flex items-center justify-between py-2 border-b border-neutral-50">
                    <span class="text-sm text-neutral-700">{{ $evt['eventName'] }}</span>
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-bold text-neutral-900">{{ $evt['eventCount'] }}</span>
                        <span class="text-[10px] text-neutral-400">{{ $evt['totalUsers'] }} users</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- User Journey: Landing → Buscador → Planes → Registro --}}
    <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-6">
        <h3 class="text-sm font-bold text-neutral-900 mb-4">Flujo del Usuario (Journey)</h3>
        <div class="flex items-center gap-2 overflow-x-auto pb-4">
            @php
            $journey = [
                ['page' => 'Landing (/)', 'views' => $pageViews['/'] ?? 0, 'color' => 'slate'],
                ['page' => 'Buscador Publico', 'views' => $pageViews['/buscador-publico'] ?? 0, 'color' => 'blue'],
                ['page' => 'Buscador Mayores', 'views' => $pageViews['/buscador-contratos-mayores'] ?? 0, 'color' => 'amber'],
                ['page' => 'Planes', 'views' => $pageViews['/planes'] ?? 0, 'color' => 'green'],
                ['page' => 'Registro', 'views' => $pageViews['/register'] ?? 0, 'color' => 'purple'],
                ['page' => 'Dashboard', 'views' => $pageViews['/dashboard'] ?? 0, 'color' => 'indigo'],
            ];
            $maxJourney = max(array_column($journey, 'views')) ?: 1;
            @endphp
            @foreach($journey as $step)
            <div class="flex items-center gap-2 flex-shrink-0">
                <div class="bg-{{ $step['color'] }}-50 border border-{{ $step['color'] }}-200 rounded-2xl p-4 text-center min-w-[120px]">
                    <p class="text-[10px] font-bold text-{{ $step['color'] }}-600 uppercase tracking-wider mb-1">{{ $step['page'] }}</p>
                    <p class="text-2xl font-black text-neutral-900">{{ $step['views'] }}</p>
                    <p class="text-[10px] text-neutral-400">views</p>
                    <div class="mt-2 h-1 bg-neutral-100 rounded-full">
                        <div class="h-full bg-{{ $step['color'] }}-500 rounded-full" style="width: {{ max(($step['views'] / $maxJourney) * 100, 3) }}%"></div>
                    </div>
                </div>
                @if(!$loop->last)
                <svg class="w-5 h-5 text-neutral-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
