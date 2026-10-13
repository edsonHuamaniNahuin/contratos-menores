@extends('layouts.app')

@section('title', 'Heatmaps de Landings')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 py-8" x-data="heatmaps()" x-init="cargar()">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-neutral-900">Heatmaps de Landings</h1>
        <p class="text-sm text-neutral-500 mt-1">
            Clics reales (coordenadas) y profundidad de scroll de los visitantes. Vista móvil (83% del tráfico).
        </p>
    </div>

    {{-- Controles --}}
    <div class="flex flex-wrap items-center gap-3 mb-6">
        @foreach($landings as $slug => $nombre)
            <a href="{{ route('admin.heatmaps', ['landing' => $slug, 'dias' => $dias]) }}"
               class="px-4 py-2 rounded-full text-sm font-semibold transition {{ $landing === $slug ? 'bg-primary-500 text-white' : 'bg-white border border-neutral-200 text-neutral-700 hover:bg-neutral-50' }}">
                {{ $nombre }}
            </a>
        @endforeach

        <span class="mx-2 h-6 w-px bg-neutral-200"></span>

        @foreach([1 => 'Hoy', 7 => '7 días', 14 => '14 días', 30 => '30 días'] as $d => $label)
            <a href="{{ route('admin.heatmaps', ['landing' => $landing, 'dias' => $d]) }}"
               class="px-3 py-2 rounded-full text-xs font-semibold transition {{ $dias === $d ? 'bg-neutral-900 text-white' : 'bg-white border border-neutral-200 text-neutral-600 hover:bg-neutral-50' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Resumen --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
        <div class="bg-white border border-neutral-200 rounded-2xl p-4">
            <p class="text-xs text-neutral-400 uppercase font-semibold">Clics</p>
            <p class="text-2xl font-bold text-neutral-900" x-text="datos ? datos.total_clicks : '—'"></p>
        </div>
        <template x-for="h in ['25','50','75','100']" :key="h">
            <div class="bg-white border border-neutral-200 rounded-2xl p-4">
                <p class="text-xs text-neutral-400 uppercase font-semibold">Llegan al <span x-text="h"></span>%</p>
                <p class="text-2xl font-bold text-neutral-900" x-text="datos ? datos.scroll[h] : '—'"></p>
            </div>
        </template>
    </div>

    <div class="grid lg:grid-cols-[390px,1fr] gap-6">
        {{-- Mapa sobre screenshot --}}
        <div class="bg-white border border-neutral-200 rounded-2xl p-4">
            <div class="relative w-[390px] max-w-full">
                <img :src="screenshotUrl()" alt="Landing" class="w-full rounded-xl border border-neutral-200" @load="pintar()">
                <canvas x-ref="lienzo" class="absolute inset-0 w-full h-full pointer-events-none"></canvas>
            </div>
            <p class="text-[11px] text-neutral-400 mt-3">Los puntos rojos son clics reales (más intenso = más clics en esa zona).</p>
        </div>

        {{-- Top elementos --}}
        <div class="bg-white border border-neutral-200 rounded-2xl p-5 self-start">
            <h2 class="text-sm font-bold text-neutral-900 mb-4 uppercase tracking-wide">Elementos más clickeados</h2>
            <template x-if="datos && datos.targets.length === 0">
                <p class="text-sm text-neutral-400">Aún no hay clics registrados en este periodo. Los datos empiezan a acumularse con el tráfico.</p>
            </template>
            <div class="space-y-2">
                <template x-for="t in (datos ? datos.targets : [])" :key="t.target">
                    <div class="flex items-center gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="h-6 rounded-md bg-primary-100 overflow-hidden">
                                <div class="h-full bg-primary-400" :style="'width:' + porcentaje(t.total) + '%'"></div>
                            </div>
                        </div>
                        <span class="text-xs text-neutral-600 font-mono truncate max-w-[240px]" x-text="t.target"></span>
                        <span class="text-xs font-bold text-neutral-900" x-text="t.total"></span>
                    </div>
                </template>
            </div>
            <p class="text-[11px] text-neutral-400 mt-4">Tip: si el botón del formulario o el de WhatsApp no aparecen arriba, la landing necesita reordenarse.</p>
        </div>
    </div>
</div>

<script>
function heatmaps() {
    return {
        datos: null,
        landing: @json($landing),
        dias: @json($dias),
        async cargar() {
            const r = await fetch(`{{ route('admin.heatmaps.data') }}?landing=${this.landing}&dias=${this.dias}`);
            this.datos = await r.json();
            this.$nextTick(() => this.pintar());
        },
        screenshotUrl() {
            return `/images/heatmap/${this.landing}.png`;
        },
        porcentaje(total) {
            if (!this.datos || this.datos.targets.length === 0) return 0;
            return Math.round((total / this.datos.targets[0].total) * 100);
        },
        pintar() {
            const img = this.$el.querySelector('img');
            const canvas = this.$refs.lienzo;
            if (!img || !canvas || !this.datos) return;
            const w = img.clientWidth;
            const h = img.clientHeight;
            canvas.width = w;
            canvas.height = h;
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, w, h);

            this.datos.clicks.forEach((c) => {
                if (c.x_pct === null || c.y_pct === null) return;
                const x = (parseFloat(c.x_pct) / 100) * w;
                const y = (parseFloat(c.y_pct) / 100) * h;
                const grad = ctx.createRadialGradient(x, y, 0, x, y, 22);
                grad.addColorStop(0, 'rgba(239,68,68,0.35)');
                grad.addColorStop(1, 'rgba(239,68,68,0)');
                ctx.fillStyle = grad;
                ctx.beginPath();
                ctx.arc(x, y, 22, 0, Math.PI * 2);
                ctx.fill();
            });
        },
    }
}
</script>
@endsection
