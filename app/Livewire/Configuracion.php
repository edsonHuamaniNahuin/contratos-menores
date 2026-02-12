<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Configuracion extends Component
{
    // Configuración Telegram Bot
    public string $telegram_bot_token = '';
    public string $telegram_chat_id = '';
    public bool $telegram_enabled = false;

    // Configuración API Analizador TDR
    public string $analizador_url = 'http://127.0.0.1:8001';
    public bool $analizador_enabled = false;

    // Testing
    public ?array $telegramTestResult = null;
    public ?array $analizadorTestResult = null;
    public bool $loadingTelegramTest = false;
    public bool $loadingAnalizadorTest = false;


    public function mount()
    {
        // Cargar configuración actual desde config
        $this->telegram_bot_token = config('services.telegram.bot_token', '');
        $this->telegram_chat_id = config('services.telegram.chat_id', '');
        $this->telegram_enabled = !empty($this->telegram_bot_token) && !empty($this->telegram_chat_id);

        // Cargar configuración del analizador
        $this->analizador_url = config('services.analizador_tdr.url', 'http://127.0.0.1:8001');
        $this->analizador_enabled = config('services.analizador_tdr.enabled', false);
    }

    public function guardarConfiguracion()
    {
        try {
            // Validar datos
            if ($this->telegram_enabled) {
                if (empty($this->telegram_bot_token) || empty($this->telegram_chat_id)) {
                    session()->flash('error', 'Bot Token y Chat ID son requeridos para Telegram');
                    return;
                }
            }

            if ($this->analizador_enabled) {
                if (empty($this->analizador_url)) {
                    session()->flash('error', 'URL del Analizador TDR es requerida');
                    return;
                }
            }

            // Actualizar archivo .env
            $this->updateEnvFile([
                'TELEGRAM_BOT_TOKEN' => $this->telegram_bot_token,
                'TELEGRAM_CHAT_ID' => $this->telegram_chat_id,
                'ANALIZADOR_TDR_URL' => $this->analizador_url,
                'ANALIZADOR_TDR_ENABLED' => $this->analizador_enabled ? 'true' : 'false',
            ]);

            // Limpiar cache de config para que relea el .env
            if (file_exists(base_path('bootstrap/cache/config.php'))) {
                @unlink(base_path('bootstrap/cache/config.php'));
            }
            \Artisan::call('config:clear');

            session()->flash('success', '✓ Configuración guardada correctamente.');

            // Recargar valores desde el .env actualizado
            $this->refreshConfigFromEnv();

        } catch (\Exception $e) {
            Log::error('Error al guardar configuración', [
                'error' => $e->getMessage()
            ]);
            session()->flash('error', 'Error al guardar configuración: ' . $e->getMessage());
        }
    }

    public function probarTelegram()
    {
        $this->loadingTelegramTest = true;
        $this->telegramTestResult = null;

        try {
            if (empty($this->telegram_bot_token) || empty($this->telegram_chat_id)) {
                throw new \Exception('Bot Token y Chat ID son requeridos');
            }

            $mensaje = "🧪 *Prueba de Conexión*\n\n"
                     . "✅ Bot de Telegram conectado correctamente al sistema Vigilante SEACE\n"
                     . "📅 Fecha: " . now()->format('d/m/Y H:i:s') . "\n\n"
                     . "Este es un mensaje de prueba del sistema de notificaciones.";

            $apiBase = rtrim((string) config('services.telegram.api_base', ''), '/');

            if (empty($apiBase)) {
                throw new \Exception('Configura TELEGRAM_API_BASE en el .env antes de probar');
            }

            $url = sprintf('%s/bot%s/sendMessage', $apiBase, $this->telegram_bot_token);

            $response = Http::timeout(10)->post($url, [
                'chat_id' => $this->telegram_chat_id,
                'text' => $mensaje,
                'parse_mode' => 'Markdown',
            ]);

            if ($response->successful()) {
                $this->telegramTestResult = [
                    'success' => true,
                    'message' => 'Mensaje enviado exitosamente a Telegram',
                    'data' => $response->json()
                ];
            } else {
                throw new \Exception('Error HTTP ' . $response->status() . ': ' . $response->body());
            }

        } catch (\Exception $e) {
            $this->telegramTestResult = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        } finally {
            $this->loadingTelegramTest = false;
        }
    }

    public function probarAnalizador()
    {
        $this->loadingAnalizadorTest = true;
        $this->analizadorTestResult = null;

        try {
            if (empty($this->analizador_url)) {
                throw new \Exception('URL del Analizador TDR es requerida');
            }

            // Probar health check
            $response = Http::timeout(5)->get("{$this->analizador_url}/health");

            if ($response->successful()) {
                $data = $response->json();
                $this->analizadorTestResult = [
                    'success' => true,
                    'message' => 'Analizador TDR conectado correctamente',
                    'data' => $data
                ];
            } else {
                throw new \Exception('Error HTTP ' . $response->status() . ': ' . $response->body());
            }

        } catch (\Exception $e) {
            $this->analizadorTestResult = [
                'success' => false,
                'error' => 'No se pudo conectar al Analizador TDR: ' . $e->getMessage()
            ];
        } finally {
            $this->loadingAnalizadorTest = false;
        }
    }

    private function updateEnvFile(array $data)
    {
        $envFile = base_path('.env');

        if (!file_exists($envFile)) {
            throw new \Exception('Archivo .env no encontrado');
        }

        $envContent = file_get_contents($envFile);

        foreach ($data as $key => $value) {
            // Asegurar que el valor sea string
            $value = (string) $value;

            // Escapar comillas dobles dentro del valor
            $escapedValue = str_replace('"', '\"', $value);

            // Si el valor contiene espacios o caracteres especiales, envolver en comillas
            $needsQuotes = preg_match('/[\s#"\'\\\\]/', $value) || $value === '';
            $replacement = $needsQuotes ? "{$key}=\"{$escapedValue}\"" : "{$key}={$escapedValue}";

            $pattern = "/^" . preg_quote($key, '/') . "=.*/m";

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent = rtrim($envContent) . "\n{$replacement}\n";
            }
        }

        file_put_contents($envFile, $envContent);
    }

    public function render()
    {
        return view('livewire.configuracion');
    }

    /**
     * Recarga los valores del componente desde el .env actualizado.
     */
    private function refreshConfigFromEnv(): void
    {
        // Forzar re-lectura del .env (Dotenv)
        $dotenv = \Dotenv\Dotenv::createImmutable(base_path());
        $dotenv->safeLoad();

        $this->analizador_url = env('ANALIZADOR_TDR_URL', 'http://127.0.0.1:8001');
        $this->analizador_enabled = filter_var(env('ANALIZADOR_TDR_ENABLED', false), FILTER_VALIDATE_BOOLEAN);
        $this->telegram_bot_token = env('TELEGRAM_BOT_TOKEN', '');
        $this->telegram_chat_id = env('TELEGRAM_CHAT_ID', '');
        $this->telegram_enabled = !empty($this->telegram_bot_token) && !empty($this->telegram_chat_id);
    }
}
