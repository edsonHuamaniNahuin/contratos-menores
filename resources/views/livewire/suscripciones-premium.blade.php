<div class="p-4 sm:p-6 flex flex-col gap-6 w-full max-w-full min-w-0">

    {{-- Header --}}
    <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-8 border border-neutral-100">
        <h1 class="text-xl sm:text-3xl font-bold text-neutral-900">💎 Suscripciones Premium</h1>
        <p class="text-sm text-neutral-400 mt-2">
            Gestiona suscripciones, trials y pagos de usuarios premium.
        </p>
    </div>

    {{-- Feedback --}}
    @if($successMessage)
        <div class="bg-secondary-500/10 border-l-4 border-secondary-500 rounded-2xl p-4">
            <p class="text-sm text-neutral-900 font-medium">{{ $successMessage }}</p>
        </div>
    @endif
    @if($errorMessage)
        <div class="bg-primary-500/10 border-l-4 border-primary-500 rounded-2xl p-4">
            <p class="text-sm text-neutral-900 font-medium">❌ {{ $errorMessage }}</p>
        </div>
    @endif

    {{-- KPIs --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-5 text-center">
            <p class="text-3xl font-bold text-primary-500">{{ $stats['total_active'] }}</p>
            <p class="text-xs text-neutral-400 mt-1">Activas</p>
        </div>
        <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-5 text-center">
            <p class="text-3xl font-bold text-amber-500">{{ $stats['total_trial'] }}</p>
            <p class="text-xs text-neutral-400 mt-1">En Trial</p>
        </div>
        <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-5 text-center">
            <p class="text-3xl font-bold text-secondary-500">{{ $stats['total_paid'] }}</p>
            <p class="text-xs text-neutral-400 mt-1">Pagadas</p>
        </div>
        <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-5 text-center">
            <p class="text-3xl font-bold text-neutral-400">{{ $stats['total_expired'] }}</p>
            <p class="text-xs text-neutral-400 mt-1">Expiradas</p>
        </div>
    </div>

    {{-- Filtros + Búsqueda --}}
    <div class="bg-white rounded-3xl shadow-soft p-6 border border-neutral-100">
        <div class="flex flex-col md:flex-row items-start md:items-center gap-4">
            <div class="flex-1 w-full">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar por nombre o email..."
                    class="w-full px-5 py-2.5 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm"
                >
            </div>
            <select
                wire:model.live="filterStatus"
                class="px-4 py-2.5 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm"
            >
                <option value="">Todos los estados</option>
                <option value="active">Activa</option>
                <option value="expired">Expirada</option>
                <option value="cancelled">Cancelada</option>
                <option value="payment_pending">Pago pendiente</option>
            </select>
            <select
                wire:model.live="filterPlan"
                class="px-4 py-2.5 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm"
            >
                <option value="">Todos los planes</option>
                <option value="trial">Trial</option>
                <option value="monthly">Mensual</option>
                <option value="yearly">Anual</option>
            </select>
        </div>
    </div>

    {{-- Tabla de suscripciones --}}
    <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 overflow-hidden">
        <div class="p-6 border-b border-neutral-100">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-neutral-900">Suscripciones</h2>
                    <p class="text-xs text-neutral-400 mt-1">Historial completo de suscripciones del sistema.</p>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-neutral-100">
                        <th class="text-left px-6 py-3 text-xs font-semibold text-neutral-400 uppercase tracking-wider">Usuario</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-neutral-400 uppercase tracking-wider">Plan</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-neutral-400 uppercase tracking-wider">Estado</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-neutral-400 uppercase tracking-wider">Inicio</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-neutral-400 uppercase tracking-wider">Vence</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-neutral-400 uppercase tracking-wider">Días</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-neutral-400 uppercase tracking-wider">Monto</th>
                        <th class="text-right px-6 py-3 text-xs font-semibold text-neutral-400 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-50">
                    @forelse($subscriptions as $sub)
                        <tr class="hover:bg-neutral-50 transition-colors">
                            <td class="px-6 py-4">
                                <p class="font-semibold text-neutral-900">{{ $sub->user?->name ?? '—' }}</p>
                                <p class="text-xs text-neutral-400">{{ $sub->user?->email ?? '' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                @switch($sub->plan)
                                    @case('trial')
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                                            ⏱ Trial
                                        </span>
                                        @break
                                    @case('monthly')
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-primary-100 text-primary-600">
                                            📅 Mensual
                                        </span>
                                        @break
                                    @case('yearly')
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-secondary-500/10 text-secondary-600">
                                            📆 Anual
                                        </span>
                                        @break
                                @endswitch
                            </td>
                            <td class="px-6 py-4">
                                @switch($sub->status)
                                    @case('active')
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-secondary-500/10 text-secondary-600">
                                            ● Activa
                                        </span>
                                        @break
                                    @case('expired')
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-neutral-100 text-neutral-500">
                                            ○ Expirada
                                        </span>
                                        @break
                                    @case('cancelled')
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-primary-100 text-primary-600">
                                            ✕ Cancelada
                                        </span>
                                        @break
                                    @case('payment_pending')
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                                            ⧗ Pendiente
                                        </span>
                                        @break
                                @endswitch
                            </td>
                            <td class="px-6 py-4 text-xs text-neutral-600">
                                {{ $sub->starts_at?->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 text-xs text-neutral-600">
                                {{ $sub->ends_at?->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4">
                                @if($sub->isActive())
                                    <span class="text-xs font-bold {{ $sub->daysRemaining() <= 3 ? 'text-amber-600' : 'text-secondary-600' }}">
                                        {{ $sub->daysRemaining() }}d
                                    </span>
                                @else
                                    <span class="text-xs text-neutral-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs text-neutral-600">
                                @if($sub->amount > 0)
                                    S/ {{ number_format($sub->amount, 2) }}
                                @else
                                    <span class="text-neutral-400">Gratis</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if($sub->isActive())
                                        <button
                                            wire:click="openExtendModal({{ $sub->id }})"
                                            class="px-3 py-1.5 text-xs font-semibold rounded-full border border-primary-300 text-primary-600 hover:bg-primary-50 transition-colors"
                                            title="Extender"
                                        >
                                            + Días
                                        </button>
                                        <button
                                            wire:click="cancelSubscription({{ $sub->id }})"
                                            wire:confirm="¿Cancelar la suscripción de {{ $sub->user?->name }}?"
                                            class="px-3 py-1.5 text-xs font-semibold rounded-full border border-neutral-300 text-neutral-500 hover:bg-neutral-50 transition-colors"
                                            title="Cancelar"
                                        >
                                            Cancelar
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <p class="text-neutral-400 text-sm">No se encontraron suscripciones.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($subscriptions->hasPages())
            <div class="p-4 border-t border-neutral-100 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div class="text-xs text-neutral-500">
                    Mostrando <span class="font-semibold text-neutral-900">{{ $subscriptions->firstItem() }}</span> a <span class="font-semibold text-neutral-900">{{ $subscriptions->lastItem() }}</span> de <span class="font-semibold text-neutral-900">{{ $subscriptions->total() }}</span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        wire:click="previousPage"
                        class="px-3 py-2 text-xs font-semibold rounded-full border border-neutral-200 text-neutral-600 hover:text-primary-600 hover:border-primary-400 transition-colors disabled:opacity-50"
                        @if($subscriptions->onFirstPage()) disabled @endif
                    >
                        Anterior
                    </button>
                    <span class="text-xs text-neutral-500">Página {{ $subscriptions->currentPage() }} de {{ $subscriptions->lastPage() }}</span>
                    <button
                        wire:click="nextPage"
                        class="px-3 py-2 text-xs font-semibold rounded-full border border-neutral-200 text-neutral-600 hover:text-primary-600 hover:border-primary-400 transition-colors disabled:opacity-50"
                        @if(! $subscriptions->hasMorePages()) disabled @endif
                    >
                        Siguiente
                    </button>
                </div>
            </div>
        @endif
    </div>

    {{-- Usuarios sin suscripción: Acciones rápidas --}}
    <div class="bg-white rounded-3xl shadow-soft p-6 border border-neutral-100">
        <div class="mb-6">
            <h2 class="text-lg font-bold text-neutral-900">Usuarios sin suscripción activa</h2>
            <p class="text-xs text-neutral-400 mt-1">Otorga premium o activa trial manualmente.</p>
        </div>

        <div class="space-y-3 max-h-80 overflow-y-auto">
            @forelse($usersWithoutSub as $user)
                <div class="bg-neutral-50 rounded-2xl p-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold text-neutral-900">{{ $user->name }}</p>
                        <p class="text-xs text-neutral-500">{{ $user->email }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            wire:click="activateTrialFor({{ $user->id }})"
                            wire:confirm="¿Activar trial de 15 días para {{ $user->name }}?"
                            class="px-4 py-2 text-xs font-semibold rounded-full bg-amber-100 text-amber-700 hover:bg-amber-200 transition-colors"
                        >
                            ⏱ Trial 15d
                        </button>
                        <button
                            wire:click="openGrantModal({{ $user->id }})"
                            class="px-4 py-2 text-xs font-semibold rounded-full bg-primary-500 text-white hover:bg-primary-400 transition-colors"
                        >
                            💎 Premium
                        </button>
                    </div>
                </div>
            @empty
                <p class="text-sm text-neutral-400 text-center py-4">Todos los usuarios tienen suscripción activa.</p>
            @endforelse
        </div>
    </div>

    {{-- Modal: Otorgar Premium --}}
    @if($showGrantModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 backdrop-blur-sm" wire:click.self="$set('showGrantModal', false)">
            <div class="bg-white rounded-3xl shadow-soft p-8 w-full max-w-md mx-4 border border-neutral-100">
                <h3 class="text-lg font-bold text-neutral-900 mb-1">💎 Otorgar Premium</h3>
                <p class="text-xs text-neutral-400 mb-6">Para: <span class="font-semibold text-neutral-900">{{ $grantUserName }}</span></p>

                <div class="space-y-4">
                    <div>
                        <label class="text-xs font-semibold text-neutral-600 mb-1 block">Plan</label>
                        <select wire:model="grantPlan" class="w-full px-4 py-2.5 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm">
                            <option value="monthly">Mensual</option>
                            <option value="yearly">Anual</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-neutral-600 mb-1 block">Días de vigencia</label>
                        <input type="number" wire:model="grantDays" min="1" max="365" class="w-full px-4 py-2.5 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 mt-6">
                    <button
                        wire:click="$set('showGrantModal', false)"
                        class="px-5 py-2.5 rounded-full border border-neutral-200 text-sm font-semibold text-neutral-600 hover:bg-neutral-50 transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        wire:click="grantPremium"
                        class="px-5 py-2.5 rounded-full bg-primary-500 text-white text-sm font-semibold hover:bg-primary-400 transition-colors"
                    >
                        Otorgar
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal: Extender suscripción --}}
    @if($showExtendModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 backdrop-blur-sm" wire:click.self="$set('showExtendModal', false)">
            <div class="bg-white rounded-3xl shadow-soft p-8 w-full max-w-md mx-4 border border-neutral-100">
                <h3 class="text-lg font-bold text-neutral-900 mb-1">⏳ Extender suscripción</h3>
                <p class="text-xs text-neutral-400 mb-6">Para: <span class="font-semibold text-neutral-900">{{ $extendUserName }}</span></p>

                <div>
                    <label class="text-xs font-semibold text-neutral-600 mb-1 block">Días a agregar</label>
                    <input type="number" wire:model="extendDays" min="1" max="365" class="w-full px-4 py-2.5 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm">
                </div>

                <div class="flex items-center justify-end gap-3 mt-6">
                    <button
                        wire:click="$set('showExtendModal', false)"
                        class="px-5 py-2.5 rounded-full border border-neutral-200 text-sm font-semibold text-neutral-600 hover:bg-neutral-50 transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        wire:click="extendSubscription"
                        class="px-5 py-2.5 rounded-full bg-primary-500 text-white text-sm font-semibold hover:bg-primary-400 transition-colors"
                    >
                        Extender
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
