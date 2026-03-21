@extends('layouts.app')

@section('content')
<div class="p-4 lg:p-6">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-neutral-900">Análisis de Direccionamiento</h2>
        <p class="text-sm text-neutral-500 mt-1">Inteligencia anticorrupción — Análisis de TDR con IA para detectar direccionamiento</p>
    </div>
    @livewire('direccionamiento-dashboard')
</div>
@endsection
