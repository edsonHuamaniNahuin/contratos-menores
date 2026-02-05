<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SeaceScraperService;
use App\Services\TelegramNotificationService;
use Illuminate\Support\Facades\DB;
use Exception;

class SeaceTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seace:test
                            {--telegram : Probar solo notificaciones de Telegram}
                            {--auth : Probar solo autenticación SEACE}
                            {--db : Probar solo conexión a base de datos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica la configuración del sistema y prueba las conexiones';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("🔍 DIAGNÓSTICO DEL SISTEMA VIGILANTE SEACE");
        $this->info("═══════════════════════════════════════════════");
        $this->newLine();

        $allTests = !$this->option('telegram') && !$this->option('auth') && !$this->option('db');

        if ($allTests || $this->option('db')) {
            $this->testDatabase();
        }

        if ($allTests || $this->option('auth')) {
            $this->testSeaceAuth();
        }

        if ($allTests || $this->option('telegram')) {
            $this->testTelegram();
        }

        $this->newLine();
        $this->info("✅ Diagnóstico completado");

        return Command::SUCCESS;
    }

    /**
     * Probar conexión a la base de datos
     */
    protected function testDatabase(): void
    {
        $this->info("📊 Probando conexión a MySQL...");

        try {
            DB::connection()->getPdo();
            $dbName = DB::connection()->getDatabaseName();

            $this->info("   ✅ Conexión exitosa a: {$dbName}");

            // Verificar tabla contratos
            if (DB::getSchemaBuilder()->hasTable('contratos')) {
                $count = DB::table('contratos')->count();
                $this->info("   ✅ Tabla 'contratos' existe ({$count} registros)");
            } else {
                $this->warn("   ⚠️  Tabla 'contratos' no existe. Ejecuta: php artisan migrate");
            }

        } catch (Exception $e) {
            $this->error("   ❌ Error de conexión: {$e->getMessage()}");
            $this->warn("   💡 Verifica las credenciales en el archivo .env");
        }

        $this->newLine();
    }

    /**
     * Probar autenticación con SEACE
     */
    protected function testSeaceAuth(): void
    {
        $this->info("🔐 Probando autenticación con SEACE...");

        // Verificar variables de entorno
        $ruc = config('services.seace.ruc_proveedor');
        $password = config('services.seace.password');
        $baseUrl = config('services.seace.base_url');

        if (empty($ruc)) {
            $this->error("   ❌ SEACE_RUC_PROVEEDOR no configurado en .env");
            $this->newLine();
            return;
        }

        if (empty($password)) {
            $this->error("   ❌ SEACE_PASSWORD no configurado en .env");
            $this->newLine();
            return;
        }

        $this->info("   📋 RUC: {$ruc}");
        $this->info("   🌐 URL: {$baseUrl}");
        $this->info("   🔑 Password: " . str_repeat('*', strlen($password)));
        $this->newLine();

        try {
            $scraper = new SeaceScraperService();

            $this->info("   🔄 Intentando login...");

            if ($scraper->fullLogin()) {
                $this->info("   ✅ Login exitoso");
                $this->info("   💡 El token se guardó en cache correctamente");

                // Intentar obtener maestras como prueba adicional
                $this->info("   🔄 Probando endpoint de maestras...");
                $maestras = $scraper->fetchMaestra('estados');

                if (!empty($maestras)) {
                    $this->info("   ✅ Endpoint de maestras funcionando");
                    $this->info("   📊 Estados disponibles: " . count($maestras));
                } else {
                    $this->warn("   ⚠️  No se obtuvieron datos de maestras");
                }

            } else {
                $this->error("   ❌ Error de autenticación");
                $this->warn("   💡 Verifica RUC y contraseña en .env");
                $this->warn("   💡 Revisa los logs en: storage/logs/laravel.log");
            }

        } catch (Exception $e) {
            $this->error("   ❌ Excepción: {$e->getMessage()}");
        }

        $this->newLine();
    }

    /**
     * Probar notificaciones de Telegram
     */
    protected function testTelegram(): void
    {
        $this->info("📱 Probando notificaciones de Telegram...");

        $botToken = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        if (empty($botToken)) {
            $this->error("   ❌ TELEGRAM_BOT_TOKEN no configurado en .env");
            $this->newLine();
            return;
        }

        if (empty($chatId)) {
            $this->error("   ❌ TELEGRAM_CHAT_ID no configurado en .env");
            $this->newLine();
            return;
        }

        $this->info("   🤖 Bot Token: " . substr($botToken, 0, 15) . "...");
        $this->info("   💬 Chat ID: {$chatId}");
        $this->newLine();

        try {
            $telegram = new TelegramNotificationService();

            $this->info("   📤 Enviando mensaje de prueba...");

            $testMessage = "🧪 <b>Mensaje de Prueba</b>\n\n";
            $testMessage .= "✅ El sistema de notificaciones está funcionando correctamente.\n\n";
            $testMessage .= "📅 Fecha: " . now()->format('d/m/Y H:i:s') . "\n";
            $testMessage .= "🤖 <i>Vigilante SEACE - Test</i>";

            if ($telegram->sendMessage($testMessage)) {
                $this->info("   ✅ Mensaje de prueba enviado exitosamente");
                $this->info("   💡 Verifica tu Telegram para confirmar la recepción");
            } else {
                $this->error("   ❌ Error al enviar mensaje");
                $this->warn("   💡 Verifica el Bot Token y Chat ID");
                $this->warn("   💡 Revisa los logs en: storage/logs/laravel.log");
            }

        } catch (Exception $e) {
            $this->error("   ❌ Excepción: {$e->getMessage()}");
        }

        $this->newLine();
    }
}
