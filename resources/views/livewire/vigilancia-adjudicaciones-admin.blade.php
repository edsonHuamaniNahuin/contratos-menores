<div class="max-w-5xl mx-auto px-4 py-8">
    <div class="mb-8">
        <p class="text-xs font-semibold uppercase text-neutral-400 tracking-[0.2em]">Administración</p>
        <h1 class="text-3xl font-black text-neutral-900">Vigilancia de Adjudicaciones</h1>
        <p class="text-sm text-neutral-500 mt-2">Procesos mayores a S/ {{ number_format((float) $umbral, 0) }} se monitorean cada 5 horas contra la API OCDS. Al detectar buena pro se alerta a los destinatarios configurados.</p>
    </div>

    {{-- Estadísticas --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-2xl shadow-soft border border-neutral-200 p-5">
            <p class="text-xs font-semibold uppercase text-neutral-400">Vigilados</p>
            <p class="text-3xl font-black text-neutral-900 mt-1">{{ number_format($stats['vigilados']) }}</p>
            <p class="text-xs text-neutral-400 mt-1">Procesos &gt;= S/ {{ number_format((float) $umbral, 0) }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-soft border border-neutral-200 p-5">
            <p class="text-xs font-semibold uppercase text-neutral-400">Pendientes</p>
            <p class="text-3xl font-black text-amber-600 mt-1">{{ number_format($stats['pendientes']) }}</p>
            <p class="text-xs text-neutral-400 mt-1">En espera de buena pro</p>
        </div>
        <div class="bg-white rounded-2xl shadow-soft border border-neutral-200 p-5">
            <p class="text-xs font-semibold uppercase text-neutral-400">Buena pro notificada</p>
            <p class="text-3xl font-black text-emerald-600 mt-1">{{ number_format($stats['notificados']) }}</p>
            <p class="text-xs text-neutral-400 mt-1">Alertas enviadas</p>
        </div>
    </div>

    {{-- Umbral --}}
    <div class="bg-white rounded-2xl shadow-soft border border-neutral-200 p-5 mb-8">
        <h2 class="text-lg font-bold text-neutral-900 mb-3">Umbral de vigilancia</h2>
        <div class="flex flex-col sm:flex-row sm:items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-neutral-600 mb-1.5">Monto mínimo (S/)</label>
                <input type="number" min="1" step="1000" wire:model="umbral" class="w-full sm:w-64 px-4 py-2.5 rounded-xl text-sm bg-neutral-50 border border-neutral-100 focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>
            <button wire:click="guardarUmbral" class="px-4 py-2.5 bg-primary-500 hover:bg-primary-600 text-white rounded-xl text-sm font-bold transition-colors">Guardar umbral</button>
            <button wire:click="ejecutarAhora" wire:loading.attr="disabled" class="px-4 py-2.5 bg-neutral-800 hover:bg-neutral-900 text-white rounded-xl text-sm font-bold transition-colors flex items-center gap-2">
                <div wire:loading wire:target="ejecutarAhora" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                Ejecutar job ahora
            </button>
        </div>
        <p class="text-xs text-neutral-400 mt-3">El job registra procesos nuevos &gt;= umbral y consulta 1x1 los pendientes. En producción corre cada 5 horas automáticamente.</p>
    </div>

    {{-- Destinatarios --}}
    <div class="bg-white rounded-2xl shadow-soft border border-neutral-200 p-5">
        <h2 class="text-lg font-bold text-neutral-900 mb-1">Destinatarios de alertas</h2>
        <p class="text-sm text-neutral-500 mb-4">Recibirán email y/o WhatsApp cuando un proceso vigilado pase a buena pro.</p>

        {{-- Formulario --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-5">
            <div>
                <label class="block text-xs font-medium text-neutral-600 mb-1.5">Email</label>
                <input type="email" wire:model="nuevoEmail" placeholder="correo@empresa.com" class="w-full px-4 py-2.5 rounded-xl text-sm bg-neutral-50 border border-neutral-100 focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-neutral-600 mb-1.5">WhatsApp (con código de país)</label>
                <input type="text" wire:model="nuevoTelefono" placeholder="51998294604" class="w-full px-4 py-2.5 rounded-xl text-sm bg-neutral-50 border border-neutral-100 focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>
        </div>
        <button wire:click="agregarDestinatario" class="px-4 py-2.5 bg-secondary-500 hover:bg-secondary-600 text-white rounded-xl text-sm font-bold transition-colors mb-6">Agregar destinatario</button>

        {{-- Lista --}}
        @if(empty($destinatarios))
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
                ⚠️ No hay destinatarios configurados. Las buenas pro se detectarán pero NO se enviarán alertas.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-neutral-100 text-left text-xs uppercase text-neutral-400">
                            <th class="py-2 pr-4">Email</th>
                            <th class="py-2 pr-4">WhatsApp</th>
                            <th class="py-2 pr-4">Estado</th>
                            <th class="py-2">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($destinatarios as $d)
                            <tr class="border-b border-neutral-50">
                                <td class="py-3 pr-4 text-neutral-800">{{ $d['email'] ?: '—' }}</td>
                                <td class="py-3 pr-4 text-neutral-800">{{ $d['telefono'] ?: '—' }}</td>
                                <td class="py-3 pr-4">
                                    <button wire:click="toggleActivo({{ $d['id'] }})" class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $d['activo'] ? 'bg-emerald-100 text-emerald-700' : 'bg-neutral-100 text-neutral-500' }}">
                                        {{ $d['activo'] ? 'Activo' : 'Inactivo' }}
                                    </button>
                                </td>
                                <td class="py-3">
                                    <button wire:click="eliminarDestinatario({{ $d['id'] }})" wire:confirm="¿Eliminar este destinatario?" class="text-red-500 hover:text-red-700 text-xs font-semibold">Eliminar</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
