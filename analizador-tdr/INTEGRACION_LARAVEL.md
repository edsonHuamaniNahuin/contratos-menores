# 🔗 INTEGRACIÓN CON LARAVEL (Vigilante SEACE)

Esta guía muestra cómo integrar el microservicio de análisis de TDRs con el proyecto Laravel principal.

## 🎯 Escenario de Integración

El proyecto **Vigilante SEACE** (Laravel) detecta nuevos contratos del SEACE. Cuando encuentra un TDR interesante, puede enviarlo al microservicio Python para obtener un análisis automatizado.

## 📋 Flujo de Integración

```
┌─────────────────────┐
│   Laravel Backend   │
│  (Vigilante SEACE)  │
└──────────┬──────────┘
           │
           │ 1. Detecta nuevo contrato
           │ 2. Descarga PDF del TDR
           │
           ▼
┌─────────────────────────────┐
│  HTTP Client (Guzzle)       │
│  POST /analyze-tdr          │
│  Content-Type: multipart    │
└──────────┬──────────────────┘
           │
           ▼
┌──────────────────────────────┐
│ Python Microservicio         │
│ (Analizador TDR)             │
│ localhost:8001               │
└──────────┬───────────────────┘
           │
           │ JSON Response
           │
           ▼
┌──────────────────────────────┐
│ Laravel Controller           │
│ - Guarda análisis en DB      │
│ - Notifica vía Telegram      │
│ - Muestra en dashboard       │
└──────────────────────────────┘
```

## 🔧 Implementación en Laravel

### 1. Crear Servicio HTTP para el Microservicio

Crea un servicio en Laravel para comunicarte con el microservicio:

```php
<?php
// app/Services/TDRAnalyzerService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class TDRAnalyzerService
{
    private string $baseUrl;
    private int $timeout;

    public function __construct()
    {
        // URL del microservicio (configurable en .env)
        $this->baseUrl = config('services.tdr_analyzer.url', 'http://localhost:8001');
        $this->timeout = config('services.tdr_analyzer.timeout', 120);
    }

    /**
     * Analiza un TDR enviándolo al microservicio Python.
     *
     * @param string $pdfPath Ruta absoluta del PDF
     * @param string|null $llmProvider Proveedor LLM (gemini, openai, anthropic)
     * @return array|null Análisis estructurado o null si falla
     */
    public function analyzeTDR(string $pdfPath, ?string $llmProvider = null): ?array
    {
        try {
            Log::info("Enviando TDR al microservicio de análisis", [
                'pdf' => basename($pdfPath),
                'provider' => $llmProvider ?? 'default'
            ]);

            // Preparar el request
            $request = Http::timeout($this->timeout)
                ->attach('file', file_get_contents($pdfPath), basename($pdfPath));

            // Agregar parámetro opcional
            if ($llmProvider) {
                $request = $request->withQueryParameters(['llm_provider' => $llmProvider]);
            }

            // Enviar al microservicio
            $response = $request->post("{$this->baseUrl}/analyze-tdr");

            // Verificar respuesta
            if ($response->successful()) {
                $data = $response->json();
                
                Log::info("Análisis completado exitosamente", [
                    'score' => $data['score_compatibilidad'] ?? 'N/A'
                ]);

                return $data;
            }

            // Manejo de errores
            Log::error("Error al analizar TDR", [
                'status' => $response->status(),
                'error' => $response->json()['detail'] ?? $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error("Excepción al comunicarse con microservicio", [
                'message' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Verifica si el microservicio está disponible.
     *
     * @return bool
     */
    public function isHealthy(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/health");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
```

### 2. Configurar en `config/services.php`

```php
<?php
// config/services.php

return [
    // ... otros servicios

    'tdr_analyzer' => [
        'url' => env('TDR_ANALYZER_URL', 'http://localhost:8001'),
        'timeout' => env('TDR_ANALYZER_TIMEOUT', 120), // 2 minutos
        'enabled' => env('TDR_ANALYZER_ENABLED', true),
    ],
];
```

### 3. Agregar variables al `.env`

```env
# .env de Laravel

# Microservicio de Análisis de TDRs
TDR_ANALYZER_URL=http://localhost:8001
TDR_ANALYZER_TIMEOUT=120
TDR_ANALYZER_ENABLED=true
```

### 4. Crear Migración para Almacenar Análisis

```php
<?php
// database/migrations/2026_02_03_create_tdr_analisis_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tdr_analisis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')->constrained('contratos')->onDelete('cascade');
            
            // Datos del análisis
            $table->text('resumen_ejecutivo');
            $table->json('requisitos_tecnicos');
            $table->json('reglas_de_negocio');
            $table->json('politicas_y_penalidades')->nullable();
            $table->string('presupuesto_referencial')->nullable();
            $table->integer('score_compatibilidad');
            
            // Metadatos
            $table->string('llm_provider', 50)->default('gemini');
            $table->json('datos_raw')->nullable(); // JSON completo del análisis
            
            $table->timestamps();
            
            // Índices
            $table->index('score_compatibilidad');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tdr_analisis');
    }
};
```

### 5. Crear Modelo Eloquent

```php
<?php
// app/Models/TDRAnalisis.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TDRAnalisis extends Model
{
    protected $table = 'tdr_analisis';

    protected $fillable = [
        'contrato_id',
        'resumen_ejecutivo',
        'requisitos_tecnicos',
        'reglas_de_negocio',
        'politicas_y_penalidades',
        'presupuesto_referencial',
        'score_compatibilidad',
        'llm_provider',
        'datos_raw',
    ];

    protected $casts = [
        'requisitos_tecnicos' => 'array',
        'reglas_de_negocio' => 'array',
        'politicas_y_penalidades' => 'array',
        'datos_raw' => 'array',
        'score_compatibilidad' => 'integer',
    ];

    // Relación con Contrato
    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class);
    }

    // Accesor para badge de score
    public function getScoreBadgeAttribute(): string
    {
        return match(true) {
            $this->score_compatibilidad >= 8 => 'success',
            $this->score_compatibilidad >= 6 => 'warning',
            default => 'danger'
        };
    }
}
```

### 6. Actualizar Modelo Contrato

```php
<?php
// app/Models/Contrato.php (agregar relación)

use Illuminate\Database\Eloquent\Relations\HasOne;

class Contrato extends Model
{
    // ... código existente

    /**
     * Relación con análisis de TDR.
     */
    public function analisisTDR(): HasOne
    {
        return $this->hasOne(TDRAnalisis::class);
    }

    /**
     * Verifica si el contrato tiene análisis.
     */
    public function tieneAnalisis(): bool
    {
        return $this->analisisTDR()->exists();
    }
}
```

### 7. Crear Comando Artisan para Análisis

```php
<?php
// app/Console/Commands/AnalyzeTDRCommand.php

namespace App\Console\Commands;

use App\Models\Contrato;
use App\Models\TDRAnalisis;
use App\Services\TDRAnalyzerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class AnalyzeTDRCommand extends Command
{
    protected $signature = 'seace:analyze-tdr {contrato_id}';
    protected $description = 'Analiza el TDR de un contrato usando IA';

    public function __construct(
        private TDRAnalyzerService $analyzerService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $contratoId = $this->argument('contrato_id');

        // Buscar contrato
        $contrato = Contrato::find($contratoId);

        if (!$contrato) {
            $this->error("Contrato #{$contratoId} no encontrado");
            return self::FAILURE;
        }

        // Verificar que tenga PDF
        if (!$contrato->pdf_path) {
            $this->error("El contrato no tiene un PDF asociado");
            return self::FAILURE;
        }

        $this->info("Analizando TDR del contrato: {$contrato->numero_contrato}");
        $this->info("PDF: {$contrato->pdf_path}");

        // Obtener ruta absoluta del PDF
        $pdfPath = Storage::path($contrato->pdf_path);

        if (!file_exists($pdfPath)) {
            $this->error("El archivo PDF no existe en el storage");
            return self::FAILURE;
        }

        // Enviar al microservicio
        $this->info("Enviando al microservicio de análisis...");
        
        $analisis = $this->analyzerService->analyzeTDR($pdfPath);

        if (!$analisis) {
            $this->error("❌ Error al analizar el TDR");
            return self::FAILURE;
        }

        // Guardar en la base de datos
        TDRAnalisis::updateOrCreate(
            ['contrato_id' => $contrato->id],
            [
                'resumen_ejecutivo' => $analisis['resumen_ejecutivo'],
                'requisitos_tecnicos' => $analisis['requisitos_tecnicos'],
                'reglas_de_negocio' => $analisis['reglas_de_negocio'],
                'politicas_y_penalidades' => $analisis['politicas_y_penalidades'] ?? [],
                'presupuesto_referencial' => $analisis['presupuesto_referencial'],
                'score_compatibilidad' => $analisis['score_compatibilidad'],
                'llm_provider' => 'gemini', // o detectar del response
                'datos_raw' => $analisis,
            ]
        );

        $this->info("✅ Análisis completado y guardado");
        $this->info("Score de compatibilidad: {$analisis['score_compatibilidad']}/10");

        return self::SUCCESS;
    }
}
```

### 8. Vista Blade para Mostrar Análisis

```blade
{{-- resources/views/contratos/show.blade.php --}}

@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">{{ $contrato->numero_contrato }}</h1>

    {{-- Información básica del contrato --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Información del Contrato</h2>
        <dl class="grid grid-cols-2 gap-4">
            <div>
                <dt class="font-medium text-gray-600">Entidad:</dt>
                <dd>{{ $contrato->entidad }}</dd>
            </div>
            <div>
                <dt class="font-medium text-gray-600">Objeto:</dt>
                <dd>{{ $contrato->objeto }}</dd>
            </div>
            <div>
                <dt class="font-medium text-gray-600">Monto:</dt>
                <dd>S/ {{ number_format($contrato->monto, 2) }}</dd>
            </div>
            <div>
                <dt class="font-medium text-gray-600">Fecha:</dt>
                <dd>{{ $contrato->fecha_publicacion->format('d/m/Y') }}</dd>
            </div>
        </dl>
    </div>

    @if($contrato->tieneAnalisis())
        {{-- Análisis de TDR con IA --}}
        @php $analisis = $contrato->analisisTDR @endphp
        
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg shadow-lg p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold text-gray-800">
                    🤖 Análisis de TDR con IA
                </h2>
                <span class="px-4 py-2 rounded-full text-white font-bold text-lg
                    @if($analisis->score_compatibilidad >= 8) bg-green-500
                    @elseif($analisis->score_compatibilidad >= 6) bg-yellow-500
                    @else bg-red-500 @endif">
                    {{ $analisis->score_compatibilidad }}/10
                </span>
            </div>

            {{-- Resumen Ejecutivo --}}
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-2">📝 Resumen Ejecutivo</h3>
                <p class="text-gray-700 leading-relaxed">{{ $analisis->resumen_ejecutivo }}</p>
            </div>

            {{-- Requisitos Técnicos --}}
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-2">🔧 Requisitos Técnicos</h3>
                <ul class="list-disc list-inside space-y-1">
                    @foreach($analisis->requisitos_tecnicos as $req)
                        <li class="text-gray-700">{{ $req }}</li>
                    @endforeach
                </ul>
            </div>

            {{-- Reglas de Negocio --}}
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-2">📋 Reglas de Negocio</h3>
                <ul class="list-disc list-inside space-y-1">
                    @foreach($analisis->reglas_de_negocio as $regla)
                        <li class="text-gray-700">{{ $regla }}</li>
                    @endforeach
                </ul>
            </div>

            {{-- Penalidades --}}
            @if(count($analisis->politicas_y_penalidades) > 0)
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-2 text-red-600">⚠️ Políticas y Penalidades</h3>
                <ul class="list-disc list-inside space-y-1">
                    @foreach($analisis->politicas_y_penalidades as $pen)
                        <li class="text-gray-700">{{ $pen }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Presupuesto --}}
            @if($analisis->presupuesto_referencial)
            <div>
                <h3 class="text-lg font-semibold mb-2">💰 Presupuesto Referencial</h3>
                <p class="text-2xl font-bold text-green-600">{{ $analisis->presupuesto_referencial }}</p>
            </div>
            @endif

            <div class="mt-4 text-sm text-gray-500">
                Analizado con: {{ strtoupper($analisis->llm_provider) }} | 
                {{ $analisis->created_at->diffForHumans() }}
            </div>
        </div>
    @else
        {{-- Botón para analizar --}}
        <div class="bg-gray-50 rounded-lg shadow p-6 text-center">
            <p class="text-gray-600 mb-4">Este contrato aún no ha sido analizado con IA</p>
            <button 
                wire:click="analizarTDR({{ $contrato->id }})"
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg">
                🤖 Analizar TDR con IA
            </button>
        </div>
    @endif
</div>
@endsection
```

## 🚀 Uso

### Desde Artisan Command

```bash
# Analizar un contrato específico
php artisan seace:analyze-tdr 123
```

### Desde Código PHP

```php
use App\Services\TDRAnalyzerService;

$analyzer = app(TDRAnalyzerService::class);

// Verificar salud del servicio
if ($analyzer->isHealthy()) {
    // Analizar TDR
    $resultado = $analyzer->analyzeTDR(storage_path('pdfs/tdr.pdf'));
    
    if ($resultado) {
        // Guardar en DB o procesar
        TDRAnalisis::create([...]);
    }
}
```

## 🔄 Flujo Automático

Puedes automatizar el análisis cuando se detecta un nuevo contrato:

```php
// app/Events/NuevoContratoDetectado.php
Event::listen(NuevoContratoDetectado::class, function ($event) {
    if (config('services.tdr_analyzer.enabled')) {
        dispatch(new AnalyzarTDRJob($event->contrato));
    }
});
```

## 📝 Notas

- El microservicio debe estar corriendo en `localhost:8001` (o configurar otra URL)
- El timeout recomendado es de 120 segundos (análisis puede tardar)
- Verificar salud del microservicio antes de enviar requests críticos

---

**Última actualización:** 3 de febrero de 2026
