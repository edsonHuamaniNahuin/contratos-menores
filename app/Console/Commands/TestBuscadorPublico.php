<?php

namespace App\Console\Commands;

use App\Services\SeaceBuscadorPublicoService;
use Illuminate\Console\Command;

class TestBuscadorPublico extends Command
{
    protected $signature = 'seace:test-publico';
    protected $description = 'Prueba rápida del buscador público SEACE';

    public function handle()
    {
        $this->info('🔍 Probando Buscador Público SEACE...');
        $this->newLine();

        $buscador = new SeaceBuscadorPublicoService();

        // 1. Probar departamentos
        $this->info('1️⃣ Obteniendo departamentos...');
        $deptos = $buscador->obtenerDepartamentos();

        if ($deptos['success']) {
            $this->info('   ✅ Departamentos: ' . count($deptos['data']));
            $this->line('   📍 Primeros 3: ' . collect($deptos['data'])->take(3)->pluck('nom')->implode(', '));
        } else {
            $this->error('   ❌ Error al obtener departamentos');
        }

        $this->newLine();

        // 2. Probar objetos de contratación
        $this->info('2️⃣ Obteniendo objetos de contratación...');
        $objetos = $buscador->obtenerObjetosContratacion();

        if ($objetos['success']) {
            $this->info('   ✅ Objetos: ' . count($objetos['data']));
            $this->line('   📦 Disponibles: ' . collect($objetos['data'])->pluck('nom')->implode(', '));
        } else {
            $this->error('   ❌ Error al obtener objetos');
        }

        $this->newLine();

        // 3. Probar búsqueda simple
        $this->info('3️⃣ Realizando búsqueda de prueba (estado: Vigente, departamento: Amazonas)...');
        $resultado = $buscador->buscarContratos([
            'anio' => now()->year,
            'lista_estado_contrato' => 2, // Vigente
            'codigo_departamento' => 1, // Amazonas
            'page' => 1,
            'page_size' => 5,
        ]);

        if ($resultado['success']) {
            $total = $resultado['pagination']['total_elements'] ?? 0;
            $resultados = count($resultado['data']);

            $this->info("   ✅ Búsqueda exitosa: {$resultados} de {$total} contratos encontrados");

            if ($resultados > 0) {
                $primer = $resultado['data'][0];
                $this->line("   📋 Primer resultado:");
                $this->line("      - Código: " . ($primer['desContratacion'] ?? 'N/A'));
                $this->line("      - Entidad: " . \Illuminate\Support\Str::limit($primer['nomEntidad'] ?? 'N/A', 50));
                $this->line("      - Estado: " . ($primer['nomEstadoContrato'] ?? 'N/A'));
            }
        } else {
            $this->error('   ❌ Error en búsqueda: ' . ($resultado['error'] ?? 'Error desconocido'));
        }

        $this->newLine();

        // 4. Probar provincias (Lima = 15)
        $this->info('4️⃣ Obteniendo provincias de Lima (id=15)...');
        $provincias = $buscador->obtenerProvincias(15);

        if ($provincias['success']) {
            $this->info('   ✅ Provincias: ' . count($provincias['data']));
            $this->line('   🏙️ Primeras 5: ' . collect($provincias['data'])->take(5)->pluck('nom')->implode(', '));
        } else {
            $this->error('   ❌ Error al obtener provincias');
        }

        $this->newLine();
        $this->info('✅ Pruebas completadas');

        return Command::SUCCESS;
    }
}
