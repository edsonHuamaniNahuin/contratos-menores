@extends('layouts.app')

@php
    // ─── Construir SEO dinámico basado en filtros activos ───
    $dep = request('dep', request('departamento', ''));
    $objeto = request('objeto', '');
    $estado = request('estado', '');
    $q = request('palabraClave', request('q', ''));
    $entidad = request('entidad', request('entidadTexto', ''));
    $pagina = (int) request('pagina', request('pag', 1));

    $titleParts = [];
    $descParts = [];

    if ($q) {
        $titleParts[] = '"' . e(Str::limit($q, 40)) . '"';
        $descParts[] = 'con la palabra clave "' . e($q) . '"';
    }
    if ($entidad) {
        $titleParts[] = e(Str::limit($entidad, 50));
        $descParts[] = 'de la entidad ' . e($entidad);
    }
    if ($objeto) {
        $objetoLabel = ucfirst(str_replace('-', ' ', $objeto));
        $titleParts[] = $objetoLabel;
        $descParts[] = 'del tipo ' . $objetoLabel;
    }
    if ($estado) {
        $estadoLabel = ucfirst(str_replace('-', ' ', $estado));
        $descParts[] = 'en estado ' . $estadoLabel;
    }
    if ($dep) {
        $depLabel = ucfirst(str_replace('-', ' ', $dep));
        $titleParts[] = $depLabel;
        $descParts[] = 'en ' . $depLabel;
    }

    $seoTitle = count($titleParts) > 0
        ? 'Licitaciones ' . implode(' | ', $titleParts) . ' — SEACE | Licitaciones MYPe'
        : 'Buscador de Licitaciones SEACE — Licitaciones MYPe';

    if ($pagina > 1) {
        $seoTitle .= " — Página {$pagina}";
    }

    $seoDescription = count($descParts) > 0
        ? 'Encuentra licitaciones del Estado peruano ' . implode(', ', $descParts) . '. Publicadas en el SEACE. Filtra por entidad, departamento, objeto y estado.'
        : 'Busca y filtra licitaciones del Estado peruano publicadas en el SEACE. Encuentra contratos de bienes, servicios, obras y consultorías por entidad, departamento, estado y palabra clave.';

    // Canonical: solo filtros significativos, sin defaults vacíos
    $canonicalParams = array_filter([
        'palabraClave' => $q ?: null,
        'entidad' => $entidad ?: null,
        'objeto' => $objeto ?: null,
        'estado' => $estado ?: null,
        'dep' => $dep ?: null,
        'prov' => request('prov') ?: null,
        'dist' => request('dist') ?: null,
        'pagina' => $pagina > 1 ? $pagina : null,
    ]);
    $canonicalUrl = url('/buscador-publico') . (count($canonicalParams) > 0 ? '?' . http_build_query($canonicalParams) : '');
@endphp

@section('title', $seoTitle)

@section('seo')
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="keywords" content="licitaciones{{ $dep ? ', licitaciones ' . e($dep) : '' }}{{ $objeto ? ', ' . e($objeto) : '' }}, SEACE, contrataciones del estado, Perú, OSCE, buscador licitaciones, compras públicas">
    <link rel="canonical" href="{{ $canonicalUrl }}">

    @if($pagina > 1)
        @php
            $prevParams = $canonicalParams;
            $prevParams['pagina'] = $pagina - 1 > 1 ? $pagina - 1 : null;
            $prevParams = array_filter($prevParams);
        @endphp
        <link rel="prev" href="{{ url('/buscador-publico') . (count($prevParams) > 0 ? '?' . http_build_query($prevParams) : '') }}">
    @endif
    <link rel="next" href="{{ url('/buscador-publico') . '?' . http_build_query(array_merge($canonicalParams, ['pagina' => $pagina + 1])) }}">

    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $canonicalUrl }}">

    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "WebPage",
        "name": "{{ e($seoTitle) }}",
        "description": "{{ e($seoDescription) }}",
        "url": "{{ $canonicalUrl }}",
        "isPartOf": {
            "@@type": "WebSite",
            "name": "Licitaciones MYPe",
            "url": "{{ url('/') }}"
        },
        "potentialAction": {
            "@@type": "SearchAction",
            "target": "{{ url('/buscador-publico') }}?palabraClave={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>
@endsection

@section('content')
    <article>
        <header class="sr-only">
            <h1>{{ $seoTitle }}</h1>
            <p>{{ $seoDescription }}</p>
        </header>

        <section aria-label="Buscador de licitaciones SEACE">
            @livewire('buscador-publico')
        </section>
    </article>
@endsection
