@extends('layouts.app')

@section('title', 'Buscador de Licitaciones SEACE — Licitaciones MYPe')

@section('seo')
    <meta name="description" content="Busca y filtra licitaciones del Estado peruano publicadas en el SEACE. Encuentra contratos de bienes, servicios, obras y consultorías por entidad, departamento, estado y palabra clave.">
    <meta name="keywords" content="licitaciones, SEACE, contrataciones del estado, Perú, OSCE, buscador licitaciones, compras públicas">
    <link rel="canonical" href="{{ url('/buscador-publico') }}">
    <meta property="og:title" content="Buscador de Licitaciones SEACE — Licitaciones MYPe">
    <meta property="og:description" content="Busca y filtra licitaciones del Estado peruano publicadas en el SEACE. Contratos de bienes, servicios, obras y consultorías.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/buscador-publico') }}">
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "WebPage",
        "name": "Buscador de Licitaciones SEACE",
        "description": "Busca y filtra licitaciones del Estado peruano publicadas en el SEACE por entidad, departamento, objeto y estado.",
        "url": "{{ url('/buscador-publico') }}",
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
            <h1>Buscador de Licitaciones y Contrataciones del Estado Peruano — SEACE</h1>
            <p>Encuentra procesos de contratación pública del Perú. Filtra por entidad, departamento, objeto de contratación, estado y palabras clave.</p>
        </header>

        <section aria-label="Buscador de licitaciones SEACE">
            @livewire('buscador-publico')
        </section>
    </article>
@endsection
