---
pagination:
    collection: posts
    perPage: 6
---
@extends('_layouts.master')

@section('title', 'Blog — Vigilante SEACE')
@section('description', 'Guías, noticias y análisis sobre contrataciones del Estado peruano en el SEACE. Contratos menores, mayores, análisis de TDR con IA.')

@section('content')
<div class="bg-gradient-to-br from-primary-500 to-primary-600 text-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-16 sm:py-20">
        <h1 class="text-3xl sm:text-4xl font-extrabold mb-4">Blog de Vigilante SEACE</h1>
        <p class="text-base sm:text-lg text-primary-100/90 max-w-2xl">Guías, noticias y análisis sobre contrataciones del Estado peruano. Aprendé a usar el SEACE, interpretar TDRs y detectar direccionamiento.</p>
    </div>
</div>

<div class="max-w-6xl mx-auto px-4 sm:px-6 py-12">
    @if($pagination->items->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($pagination->items as $post)
                <a href="{{ $post->getUrl() }}" class="group bg-white rounded-3xl shadow-soft border border-neutral-200 overflow-hidden hover:border-primary-300 hover:shadow-lg transition-all flex flex-col">
                    <div class="p-6 flex-1 flex flex-col">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-primary-50 text-primary-600 border border-primary-100">
                                {{ $post->category ?? 'General' }}
                            </span>
                            <span class="text-xs text-neutral-400">{{ $post->getDate()->format('d M Y') }}</span>
                        </div>
                        <h2 class="text-lg font-bold text-neutral-900 group-hover:text-primary-600 transition-colors mb-2 line-clamp-2">
                            {{ $post->title }}
                        </h2>
                        <p class="text-sm text-neutral-500 line-clamp-3 leading-relaxed flex-1">
                            {{ $post->excerpt ?? \Illuminate\Support\Str::limit(strip_tags($post->getContent()), 160) }}
                        </p>
                        <div class="flex items-center gap-2 mt-4 pt-4 border-t border-neutral-100">
                            <div class="w-7 h-7 rounded-full bg-primary-500/10 flex items-center justify-center text-primary-600 text-[10px] font-bold">
                                {{ strtoupper(substr($post->author ?? 'V', 0, 1)) }}
                            </div>
                            <span class="text-xs text-neutral-500">{{ $post->author ?? 'Vigilante SEACE' }}</span>
                            <span class="ml-auto text-xs font-medium text-primary-500 group-hover:translate-x-1 transition-transform inline-flex items-center gap-1">
                                Leer <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- Paginación simple --}}
        @if($pagination->pages->count() > 1)
            <div class="flex items-center justify-center gap-2 mt-10">
                @if($previous = $pagination->previous)
                    <a href="{{ $previous }}" class="w-8 h-8 flex items-center justify-center rounded-lg text-neutral-500 hover:text-primary-600 hover:bg-primary-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                @endif
                @foreach($pagination->pages as $pageNumber => $pagePath)
                    <a href="{{ $pagePath }}" class="w-8 h-8 text-xs font-medium rounded-lg flex items-center justify-center transition-colors {{ $pagination->currentPage == $pageNumber ? 'bg-primary-500 text-white' : 'text-neutral-500 hover:bg-neutral-100' }}">
                        {{ $pageNumber }}
                    </a>
                @endforeach
                @if($next = $pagination->next)
                    <a href="{{ $next }}" class="w-8 h-8 flex items-center justify-center rounded-lg text-neutral-500 hover:text-primary-600 hover:bg-primary-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @endif
            </div>
        @endif
    @else
        <div class="text-center py-16">
            <p class="text-neutral-500 text-lg">No hay posts publicados aún.</p>
            <p class="text-neutral-400 text-sm mt-1">Volvé pronto para ver las primeras publicaciones.</p>
        </div>
    @endif
</div>
@endsection
