<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\CuentaSeace;
use App\Models\TelegramSubscription;
use App\Services\TelegramNotificationService;
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

    // Gestión de Suscriptores Telegram
    public string $nuevo_chat_id = '';
    public string $nuevo_nombre = '';
    public string $nuevo_username = '';
    public bool $nuevo_activo = true;
    public ?int $editando_suscripcion_id = null;

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

            // Limpiar cache de config
            \Artisan::call('config:clear');

            session()->flash('success', '✓ Configuración guardada correctamente. Recarga la página para aplicar cambios.');

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
        $envContent = file_get_contents($envFile);

        foreach ($data as $key => $value) {
            $escapedValue = str_replace('"', '\"', $value);
            $pattern = "/^{$key}=.*/m";

            if (preg_match($pattern, $envContent)) {
                // Actualizar existente
                $envContent = preg_replace($pattern, "{$key}=\"{$escapedValue}\"", $envContent);
            } else {
                // Agregar nuevo
                $envContent .= "\n{$key}=\"{$escapedValue}\"";
            }
        }

        file_put_contents($envFile, $envContent);
    }

    public function render()
    {
        $suscripciones = TelegramSubscription::orderBy('created_at', 'desc')->get();

        return view('livewire.configuracion', [
            'suscripciones' => $suscripciones,
        ]);
    }

    // ==================== GESTIÓN DE SUSCRIPTORES ====================

    public function agregarSuscriptor()
    {
        $this->validate([
            'nuevo_chat_id' => 'required|string|unique:telegram_subscriptions,chat_id' . ($this->editando_suscripcion_id ? ",{$this->editando_suscripcion_id}" : ''),
            'nuevo_nombre' => 'nullable|string|max:255',
            'nuevo_username' => 'nullable|string|max:255',
        ], [
            'nuevo_chat_id.required' => 'El Chat ID es obligatorio',
            'nuevo_chat_id.unique' => 'Este Chat ID ya está registrado',
        ]);

        try {
            if ($this->editando_suscripcion_id) {
                // Actualizar existente
                $suscripcion = TelegramSubscription::find($this->editando_suscripcion_id);
                $suscripcion->update([
                    'chat_id' => $this->nuevo_chat_id,
                    'nombre' => $this->nuevo_nombre ?: null,
                    'username' => $this->nuevo_username ?: null,
                    'activo' => $this->nuevo_activo,
                ]);

                session()->flash('success', '✅ Suscriptor actualizado exitosamente');
                Log::info('Telegram: Suscriptor actualizado', ['id' => $suscripcion->id]);
            } else {
                // Crear nuevo
                $suscripcion = TelegramSubscription::create([
                    'chat_id' => $this->nuevo_chat_id,
                    'nombre' => $this->nuevo_nombre ?: null,
                    'username' => $this->nuevo_username ?: null,
                    'activo' => $this->nuevo_activo,
                    'subscrito_at' => now(),
                ]);

                session()->flash('success', '✅ Suscriptor agregado exitosamente');
                Log::info('Telegram: Nuevo suscriptor', ['id' => $suscripcion->id]);
            }

            $this->resetSuscriptorForm();

        } catch (\Exception $e) {
            session()->flash('error', '❌ Error: ' . $e->getMessage());
            Log::error('Error al guardar suscriptor', ['error' => $e->getMessage()]);
        }
    }

    public function editarSuscriptor($id)
    {
        $suscripcion = TelegramSubscription::findOrFail($id);

        $this->editando_suscripcion_id = $id;
        $this->nuevo_chat_id = $suscripcion->chat_id;
        $this->nuevo_nombre = $suscripcion->nombre ?? '';
        $this->nuevo_username = $suscripcion->username ?? '';
        $this->nuevo_activo = $suscripcion->activo;
    }

    public function toggleActivoSuscriptor($id)
    {
        try {
            $suscripcion = TelegramSubscription::findOrFail($id);
            $suscripcion->update(['activo' => !$suscripcion->activo]);

            $estado = $suscripcion->activo ? 'activado' : 'desactivado';
            session()->flash('success', "✅ Suscriptor {$estado}");
            Log::info('Telegram: Toggle activo', ['id' => $id, 'nuevo_estado' => $suscripcion->activo]);

        } catch (\Exception $e) {
            session()->flash('error', '❌ Error: ' . $e->getMessage());
        }
    }

    public function eliminarSuscriptor($id)
    {
        try {
            $suscripcion = TelegramSubscription::findOrFail($id);
            $chatId = $suscripcion->chat_id;
            $suscripcion->delete();

            session()->flash('success', '✅ Suscriptor eliminado');
            Log::info('Telegram: Suscriptor eliminado', ['id' => $id, 'chat_id' => $chatId]);

        } catch (\Exception $e) {
            session()->flash('error', '❌ Error: ' . $e->getMessage());
        }
    }

    public function probarNotificacionSuscriptor($id)
    {
        try {
            $suscripcion = TelegramSubscription::findOrFail($id);
            $servicio = new TelegramNotificationService();

            $mensajePrueba = "🧪 <b>MENSAJE DE PRUEBA</b>\n\n";
            $mensajePrueba .= "Este es un mensaje de prueba del sistema Vigilante SEACE.\n\n";
            $mensajePrueba .= "✅ Tu suscripción está funcionando correctamente.\n";
            $mensajePrueba .= "📅 Fecha: " . now()->format('d/m/Y H:i:s');

            $resultado = $servicio->enviarMensaje($suscripcion->chat_id, $mensajePrueba);

            if ($resultado['success']) {
                session()->flash('success', '✅ Mensaje de prueba enviado exitosamente');
                Log::info('Telegram: Prueba exitosa', ['id' => $id, 'chat_id' => $suscripcion->chat_id]);
            } else {
                session()->flash('error', '❌ Error al enviar: ' . $resultado['message']);
            }

        } catch (\Exception $e) {
            session()->flash('error', '❌ Error: ' . $e->getMessage());
        }
    }

    public function resetSuscriptorForm()
    {
        $this->nuevo_chat_id = '';
        $this->nuevo_nombre = '';
        $this->nuevo_username = '';
        $this->nuevo_activo = true;
        $this->editando_suscripcion_id = null;
        $this->resetValidation(['nuevo_chat_id', 'nuevo_nombre', 'nuevo_username']);
    }
}
