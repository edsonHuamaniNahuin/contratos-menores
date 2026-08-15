@extends('layouts.app')

@section('title', 'Monitoreo del Sistema - Vigilante SEACE')

@section('content')
<div class="p-4 sm:p-6 flex flex-col gap-6 w-full max-w-full min-w-0">

    {{-- Header --}}
    <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-8 border border-neutral-100">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase text-neutral-400 tracking-[0.2em]">Monitoreo</p>
                <h1 class="text-2xl lg:text-3xl font-bold text-neutral-900 mt-1">Estado del Sistema</h1>
                <p class="text-sm text-neutral-500 mt-1">Jobs, servicios y errores en tiempo real · Actualizado {{ $refrescadoEn->format('d/m/Y H:i:s') }}</p>
            </div>
            <a href="{{ url()->current() }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-500 text-white rounded-full text-sm font-medium hover:bg-primary-400 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Refrescar
            </a>
        </div>
    </div>

    {{-- KPIs salud del servidor --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        @php
            $kpIs = [
                ['label' => 'Load Average', 'value' => $salud['load'], 'color' => $salud['load'] !== 'N/A' && (float) explode(' / ', $salud['load'])[0] > 5 ? 'red' : 'blue'],
                ['label' => 'Memoria', 'value' => $salud['mem_pct'] !== null ? $salud['mem_pct'] . '% (' . $salud['mem_usada'] . 'G/' . $salud['mem_total'] . 'G)' : 'N/A', 'color' => $salud['mem_pct'] !== null && $salud['mem_pct'] > 85 ? 'red' : 'green'],
                ['label' => 'Disco', 'value' => $salud['disco_pct'] !== null ? $salud['disco_pct'] . '% (' . $salud['disco_libre'] . 'G libre)' : 'N/A', 'color' => $salud['disco_pct'] !== null && $salud['disco_pct'] > 85 ? 'red' : 'purple'],
                ['label' => 'Uptime', 'value' => $salud['uptime'], 'color' => 'amber'],
                ['label' => 'Errores 24h (jobs)', 'value' => $cola['fallidos_24h'], 'color' => $cola['fallidos_24h'] > 0 ? 'red' : 'green'],
            ];
        @endphp
        @foreach($kpIs as $kpi)
        <div class="bg-white rounded-2xl shadow-soft border border-neutral-100 p-5">
            <p class="text-[10px] font-bold text-{{ $kpi['color'] }}-500 uppercase tracking-wider mb-2">{{ $kpi['label'] }}</p>
            <p class="text-xl lg:text-2xl font-black text-neutral-900 truncate">{{ $kpi['value'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Servicios --}}
    <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 overflow-hidden">
        <div class="px-4 sm:px-6 py-4 border-b border-neutral-100">
            <h2 class="text-lg font-bold text-neutral-900">Servicios del sistema</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-neutral-50 text-xs text-neutral-400 uppercase tracking-wider">
                    <tr>
                        <th class="text-left px-4 sm:px-6 py-3">Servicio</th>
                        <th class="text-left px-4 py-3">Descripción</th>
                        <th class="text-left px-4 py-3">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-50">
                    @foreach($servicios as $svc)
                    <tr>
                        <td class="px-4 sm:px-6 py-3 font-mono text-xs text-neutral-700">{{ $svc['servicio'] }}</td>
                        <td class="px-4 py-3 text-neutral-600">{{ $svc['label'] }}</td>
                        <td class="px-4 py-3">
                            @if($svc['activo'])
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                    Activo
                                </span>
                            @elseif($svc['estado'] === 'desconocido')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-neutral-100 text-neutral-500 text-xs font-semibold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-neutral-400"></span>
                                    Desconocido
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                    {{ ucfirst($svc['estado']) }}
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">

        {{-- Cola de jobs --}}
        <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-neutral-100 flex items-center justify-between">
                <h2 class="text-lg font-bold text-neutral-900">Cola de jobs</h2>
                @if($cola['pendientes'] > 10)
                    <span class="px-2.5 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold">{{ $cola['pendientes'] }} acumulados</span>
                @endif
            </div>
            <div class="p-4 sm:p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-neutral-600">Jobs pendientes</span>
                    <span class="text-lg font-bold {{ $cola['pendientes'] > 10 ? 'text-amber-600' : 'text-neutral-900' }}">{{ $cola['pendientes'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-neutral-600">Fallidos (últimas 24h)</span>
                    <span class="text-lg font-bold {{ $cola['fallidos_24h'] > 0 ? 'text-red-600' : 'text-neutral-900' }}">{{ $cola['fallidos_24h'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-neutral-600">Último fallo</span>
                    <span class="text-sm {{ $cola['ultimo_fallo'] ? 'text-red-600' : 'text-neutral-400' }}">{{ $cola['ultimo_fallo'] ? \Carbon\Carbon::parse($cola['ultimo_fallo'])->diffForHumans() : 'Nunca' }}</span>
                </div>
            </div>
        </div>

        {{-- Últimas corridas del scheduler --}}
        <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-neutral-100">
                <h2 class="text-lg font-bold text-neutral-900">Jobs programados</h2>
            </div>
            <div class="p-4 sm:p-6 space-y-4">
                @foreach($schedules as $sch)
                <div class="border border-neutral-100 rounded-xl p-3">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-semibold text-neutral-800">{{ $sch['nombre'] }}</span>
                        <span class="text-xs {{ $sch['stale'] ? 'text-amber-600 font-semibold' : 'text-neutral-400' }}">
                            {{ $sch['hace'] }}{{ $sch['stale'] ? ' ⚠️' : '' }}
                        </span>
                    </div>
                    <p class="text-xs text-neutral-500 break-words">{{ $sch['ultima'] ?? 'Sin ejecución registrada' }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Errores recientes --}}
    <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 overflow-hidden">
        <div class="px-4 sm:px-6 py-4 border-b border-neutral-100 flex items-center justify-between">
            <h2 class="text-lg font-bold text-neutral-900">Errores recientes</h2>
            <span class="text-xs text-neutral-400">Últimos {{ count($errores) }}</span>
        </div>
        @if(empty($errores))
            <div class="p-8 text-center">
                <p class="text-sm text-green-600 font-semibold">✅ Sin errores registrados</p>
            </div>
        @else
            <div class="divide-y divide-neutral-50 max-h-96 overflow-y-auto">
                @foreach($errores as $error)
                <div class="px-4 sm:px-6 py-3 flex flex-col sm:flex-row sm:items-start gap-1 sm:gap-4">
                    <span class="text-xs font-mono text-neutral-400 whitespace-nowrap">{{ $error['fecha'] }}</span>
                    <p class="text-xs text-red-700 break-words flex-1">{{ $error['mensaje'] }}</p>
                </div>
                @endforeach
            </div>
        @endif
    </div>

</div>
@endsection
