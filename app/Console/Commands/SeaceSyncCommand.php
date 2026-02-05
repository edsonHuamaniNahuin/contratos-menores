<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SeaceScraperService;
use App\Services\TelegramNotificationService;
use App\Models\Contrato;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Exception;

class SeaceSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seace:sync
                            {--year= : Año a consultar (por defecto el año actual)}
                            {--force : Forzar ejecución sin delay aleatorio}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza contratos del SEACE con la base de datos local y notifica nuevos contratos';

    protected SeaceScraperService $scraper;
    protected TelegramNotificationService $telegram;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->scraper = new SeaceScraperService();
        $this->telegram = new TelegramNotificationService();

        $startTime = now();
        $this->info("🚀 Iniciando sincronización SEACE - {$startTime->format('Y-m-d H:i:s')}");

        // Aplicar delay aleatorio (estrategia ninja)
        if (!$this->option('force')) {
            $this->applyRandomDelay();
        }

        try {
            // Obtener año a consultar
            $year = $this->option('year') ?? now()->year;

            // Obtener contratos del SEACE
            $this->info("📡 Consultando SEACE (año: {$year})...");
            $contratosData = $this->scraper->fetchLatestContracts($year);

            if (empty($contratosData)) {
                $this->warn('⚠️  No se obtuvieron contratos del SEACE');
                return Command::SUCCESS;
            }

            $this->info("✅ Se obtuvieron " . count($contratosData) . " contratos");

            // Procesar y guardar contratos
            $stats = $this->processContratos($contratosData);

            // Mostrar estadísticas
            $this->displayStats($stats, $startTime);

            return Command::SUCCESS;

        } catch (Exception $e) {
            $this->error("❌ Error en sincronización: {$e->getMessage()}");
            Log::error('SeaceSyncCommand: Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Aplicar delay aleatorio entre ejecuciones (estrategia ninja)
     */
    protected function applyRandomDelay(): void
    {
        $minMinutes = config('services.seace.min_delay_minutes', 42);
        $maxMinutes = config('services.seace.max_delay_minutes', 50);

        $delaySeconds = rand($minMinutes * 60, $maxMinutes * 60);
        $delayMinutes = round($delaySeconds / 60, 1);

        $this->info("⏱️  Aplicando delay estratégico: {$delayMinutes} minutos");
        $this->info("    (Entre {$minMinutes} y {$maxMinutes} min para evitar detección)");

        // Mostrar barra de progreso para el delay
        $bar = $this->output->createProgressBar($delaySeconds);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %elapsed:6s%/%estimated:-6s%');

        for ($i = 0; $i < $delaySeconds; $i++) {
            sleep(1);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    /**
     * Procesar y guardar contratos
     */
    protected function processContratos(array $contratosData): array
    {
        $stats = [
            'total' => count($contratosData),
            'nuevos' => 0,
            'actualizados' => 0,
            'errores' => 0,
            'notificados' => 0,
        ];

        $this->info("💾 Procesando contratos...");
        $bar = $this->output->createProgressBar($stats['total']);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');

        foreach ($contratosData as $data) {
            try {
                $bar->setMessage("Procesando: " . ($data['desContratacion'] ?? 'N/A'));

                // Transformar fechas del formato SEACE a Carbon
                $fechaPublicacion = $this->parseSeaceDate($data['fecPublica'] ?? null);
                $inicioCotizacion = $this->parseSeaceDate($data['fecIniCotizacion'] ?? null);
                $finCotizacion = $this->parseSeaceDate($data['fecFinCotizacion'] ?? null);

                // Crear o actualizar contrato según JSON real del SEACE
                $contrato = Contrato::updateOrCreate(
                    ['id_contrato_seace' => $data['idContrato']],
                    [
                        'nro_contratacion' => $data['nroContratacion'] ?? 0,
                        'codigo_proceso' => $data['desContratacion'] ?? 'N/A',
                        'entidad' => $data['nomEntidad'] ?? 'N/A',
                        'id_objeto_contrato' => $data['idObjetoContrato'] ?? 0,
                        'objeto' => $data['nomObjetoContrato'] ?? 'N/A',
                        'descripcion' => $data['desObjetoContrato'] ?? 'Sin descripción',
                        'id_estado_contrato' => $data['idEstadoContrato'] ?? 0,
                        'estado' => $data['nomEstadoContrato'] ?? 'Desconocido',
                        'fecha_publicacion' => $fechaPublicacion,
                        'inicio_cotizacion' => $inicioCotizacion,
                        'fin_cotizacion' => $finCotizacion,
                        'etapa_contratacion' => $data['nomEtapaContratacion'] ?? null,
                        'id_tipo_cotizacion' => $data['idTipoCotizacion'] ?? null,
                        'num_subsanaciones_total' => $data['numSubsanacionesTotal'] ?? 0,
                        'num_subsanaciones_pendientes' => $data['numSubsanacionesPendientes'] ?? 0,
                        'datos_raw' => $data, // Guardar JSON completo para auditoría
                    ]
                );

                // Si es nuevo, notificar
                if ($contrato->wasRecentlyCreated) {
                    $stats['nuevos']++;

                    // Enviar notificación a Telegram CON DATOS DE ARCHIVOS
                    try {
                        // Obtener archivos TDR del contrato para el botón de análisis
                        $baseUrl = config('services.seace.base_url');
                        $responseArchivos = $this->scraper->makeResilientRequest(
                            'GET',
                            $baseUrl . '/archivo/archivos/listar-archivo-contrato',
                            [],
                            ['idContrato' => $contrato->id_contrato_seace]
                        );

                        $archivos = $responseArchivos->successful() ? $responseArchivos->json('data', []) : [];

                        // Tomar primer archivo TDR (si existe)
                        $archivo = $archivos[0] ?? null;

                        // Enviar notificación con datos completos
                        if ($this->telegram->notifyNewContract($contrato, $archivo)) {
                            $stats['notificados']++;
                        }
                    } catch (Exception $e) {
                        Log::warning('SeaceSyncCommand: Error al obtener archivos para notificación', [
                            'id_contrato' => $contrato->id,
                            'error' => $e->getMessage()
                        ]);
                        // Aún así notificar (sin botón de análisis)
                        if ($this->telegram->notifyNewContract($contrato, null)) {
                            $stats['notificados']++;
                        }
                    }
                } else {
                    $stats['actualizados']++;
                }

                $bar->advance();

            } catch (Exception $e) {
                $stats['errores']++;
                Log::error('SeaceSyncCommand: Error procesando contrato', [
                    'contrato_id' => $data['idContrato'] ?? 'N/A',
                    'error' => $e->getMessage()
                ]);
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);

        return $stats;
    }

    /**
     * Parsear fecha del formato SEACE (DD/MM/YYYY HH:mm:ss) a Carbon
     */
    protected function parseSeaceDate(?string $dateString): ?Carbon
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('d/m/Y H:i:s', $dateString);
        } catch (Exception $e) {
            Log::warning('SeaceSyncCommand: Error parseando fecha', [
                'date' => $dateString,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Mostrar estadísticas de la sincronización
     */
    protected function displayStats(array $stats, Carbon $startTime): void
    {
        $endTime = now();
        $duration = $startTime->diffInSeconds($endTime);

        $this->newLine();
        $this->info("📊 ESTADÍSTICAS DE SINCRONIZACIÓN");
        $this->info("═══════════════════════════════════════");
        $this->info("📋 Total procesados:    {$stats['total']}");
        $this->info("🆕 Nuevos contratos:    {$stats['nuevos']}");
        $this->info("🔄 Actualizados:        {$stats['actualizados']}");
        $this->info("❌ Errores:             {$stats['errores']}");
        $this->info("📱 Notificados:         {$stats['notificados']}");
        $this->info("═══════════════════════════════════════");
        $this->info("⏱️  Tiempo de ejecución: {$duration} segundos");
        $this->info("✅ Sincronización completada - {$endTime->format('Y-m-d H:i:s')}");

        // Log de la operación
        Log::info('SeaceSyncCommand: Sincronización completada', [
            'stats' => $stats,
            'duration_seconds' => $duration,
            'start_time' => $startTime->toDateTimeString(),
            'end_time' => $endTime->toDateTimeString(),
        ]);
    }
}
