<?php

use Illuminate\Support\Facades\Cache;

if (!function_exists('departamentosSidebar')) {
    /**
     * Lista de departamentos para sidebar, con cache 24h y fallback estático.
     */
    function departamentosSidebar(): array
    {
        return Cache::remember('departamentos_sidebar', 86400, function () {
            try {
                $svc = app(\App\Services\SeaceBuscadorPublicoService::class);
                $deps = $svc->obtenerDepartamentos();
                $lista = [];
                foreach ($deps['data'] ?? [] as $d) {
                    $name = trim($d['nom'] ?? '');
                    if (!$name) continue;
                    $slug = mb_strtolower($name);
                    $slug = strtr($slug, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u']);
                    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
                    $slug = trim($slug, '-');
                    if ($slug) $lista[] = ['nombre' => ucwords(str_replace('-', ' ', $slug)), 'slug' => $slug];
                }
                return $lista ?: departamentosFallback();
            } catch (\Throwable $e) {
                return departamentosFallback();
            }
        });
    }
}

if (!function_exists('departamentosFallback')) {
    function departamentosFallback(): array
    {
        return [
            ['nombre' => 'Amazonas', 'slug' => 'amazonas'],
            ['nombre' => 'Ancash', 'slug' => 'ancash'],
            ['nombre' => 'Apurimac', 'slug' => 'apurimac'],
            ['nombre' => 'Arequipa', 'slug' => 'arequipa'],
            ['nombre' => 'Ayacucho', 'slug' => 'ayacucho'],
            ['nombre' => 'Cajamarca', 'slug' => 'cajamarca'],
            ['nombre' => 'Callao', 'slug' => 'callao'],
            ['nombre' => 'Cusco', 'slug' => 'cusco'],
            ['nombre' => 'Huancavelica', 'slug' => 'huancavelica'],
            ['nombre' => 'Huanuco', 'slug' => 'huanuco'],
            ['nombre' => 'Ica', 'slug' => 'ica'],
            ['nombre' => 'Junin', 'slug' => 'junin'],
            ['nombre' => 'La Libertad', 'slug' => 'la-libertad'],
            ['nombre' => 'Lambayeque', 'slug' => 'lambayeque'],
            ['nombre' => 'Lima', 'slug' => 'lima'],
            ['nombre' => 'Loreto', 'slug' => 'loreto'],
            ['nombre' => 'Madre De Dios', 'slug' => 'madre-de-dios'],
            ['nombre' => 'Moquegua', 'slug' => 'moquegua'],
            ['nombre' => 'Pasco', 'slug' => 'pasco'],
            ['nombre' => 'Piura', 'slug' => 'piura'],
            ['nombre' => 'Puno', 'slug' => 'puno'],
            ['nombre' => 'San Martin', 'slug' => 'san-martin'],
            ['nombre' => 'Tacna', 'slug' => 'tacna'],
            ['nombre' => 'Tumbes', 'slug' => 'tumbes'],
            ['nombre' => 'Ucayali', 'slug' => 'ucayali'],
        ];
    }
}

if (!function_exists('entidadesMayoresSidebar')) {
    /**
     * Lista de entidades para sidebar de Contratos Mayores, con cache 24h desde DB.
     */
    function entidadesMayoresSidebar(): array
    {
        return Cache::remember('entidades_mayores_sidebar', 86400, function () {
            try {
                $entidades = \App\Models\EntidadMayor::orderBy('nombre')->get(['nombre', 'ruc']);
                $lista = [];
                foreach ($entidades as $e) {
                    $slug = mb_strtolower(trim($e->nombre));
                    $slug = strtr($slug, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u']);
                    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
                    $slug = trim($slug, '-');
                    if ($slug) {
                        $lista[] = ['nombre' => $e->nombre, 'slug' => $slug, 'ruc' => $e->ruc];
                    }
                }
                return $lista;
            } catch (\Throwable $e) {
                return [];
            }
        });
    }
}
