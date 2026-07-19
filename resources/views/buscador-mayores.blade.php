@extends('layouts.app')

@section('title', 'Contratos Mayores | Vigilante SEACE')

@section('seo')
    <meta name="description" content="Buscador de Contratos Mayores - Procedimientos de Selección del SEACE 3.0 (Licitaciones Públicas, Concursos Públicos).">
    <link rel="canonical" href="{{ route('buscador.mayores') }}">
    <meta property="og:title" content="Buscador Público - Contratos Mayores | Vigilante SEACE">
    <meta property="og:description" content="Busca licitaciones, concursos públicos y adjudicaciones en el SEACE 3.0">
    <meta property="og:url" content="{{ route('buscador.mayores') }}">
    <meta property="og:type" content="website">
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "Buscador Público Contratos Mayores",
        "url": "{{ route('buscador.mayores') }}",
        "description": "Buscador de contratos mayores del SEACE 3.0",
        "applicationCategory": "BusinessApplication"
    }
    </script>
@endsection

{{-- Contenido --}}
@section('content')
    @livewire('buscador-mayores')
@endsection
