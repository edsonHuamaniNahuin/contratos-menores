<?php

namespace App\Livewire;

use App\Services\Payments\PaymentGatewayManager;
use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Configuracion extends Component
{
    // Configuración Telegram Bot (Procesos SEACE)
    public string $telegram_bot_token = '';
    public string $telegram_chat_id = '';
    public bool $telegram_enabled = false;

    // Configuración Telegram Admin Bot (Nuevos usuarios y suscripciones)
    public string $telegram_admin_bot_token = '';
    public string $telegram_admin_chat_id = '';
    public bool $telegram_admin_enabled = false;

    // Configuración API Analizador TDR
    public string $analizador_url = 'http://127.0.0.1:8001';
    public bool $analizador_enabled = false;

    // Configuración WhatsApp Bot
    public string $whatsapp_bot_token = '';
    public string $whatsapp_group_id = '';
    public bool $whatsapp_enabled = false;

    // Configuración Pasarela de Pago
    public string $payment_gateway = 'mercadopago';
    public string $mercadopago_access_token = '';
    public string $mercadopago_public_key = '';
    public string $mercadopago_webhook_secret = '';
    public string $openpay_merchant_id = '';
    public string $openpay_private_key = '';
    public string $openpay_public_key = '';
    public bool $openpay_production = false;

    // Testing
    public ?array $telegramTestResult = null;
    public ?array $telegramAdminTestResult = null;
    public ?array $analizadorTestResult = null;
    public ?array $gatewayTestResult = null;
    public bool $loadingTelegramTest = false;
    public bool $loadingTelegramAdminTest = false;
    public bool $loadingAnalizadorTest = false;
    public bool $loadingGatewayTest = false;


    public function mount()
    {
        // Cargar configuración actual desde config (usar ?? '' para evitar null en propiedades string)
        $this->telegram_bot_token = config('services.telegram.bot_token') ?? '';
        $this->telegram_chat_id = config('services.telegram.chat_id') ?? '';
        $this->telegram_enabled = !empty($this->telegram_bot_token) && !empty($this->telegram_chat_id);

        // Cargar configuración del bot admin de Telegram
        $this->telegram_admin_bot_token = config('services.telegram_admin.bot_token') ?? '';
        $this->telegram_admin_chat_id = config('services.telegram_admin.chat_id') ?? '';
        $this->telegram_admin_enabled = !empty($this->telegram_admin_bot_token) && !empty($this->telegram_admin_chat_id);

        // Cargar configuración del analizador
        $this->analizador_url = config('services.analizador_tdr.url') ?? 'http://127.0.0.1:8001';
        $this->analizador_enabled = (bool) (config('services.analizador_tdr.enabled') ?? false);

        // Cargar configuración WhatsApp
        $this->whatsapp_bot_token = config('services.whatsapp.bot_token') ?? '';
        $this->whatsapp_group_id = config('services.whatsapp.group_id') ?? '';
        $this->whatsapp_enabled = !empty($this->whatsapp_bot_token) && !empty($this->whatsapp_group_id);

        // Cargar configuración de pasarela de pago
        $this->payment_gateway = config('services.payment_gateway') ?? 'mercadopago';
        $this->mercadopago_access_token = config('services.mercadopago.access_token') ?? '';
        $this->mercadopago_public_key = config('services.mercadopago.public_key') ?? '';
        $this->mercadopago_webhook_secret = config('services.mercadopago.webhook_secret') ?? '';
        $this->openpay_merchant_id = config('services.openpay.merchant_id') ?? '';
        $this->openpay_private_key = config('services.openpay.private_key') ?? '';
        $this->openpay_public_key = config('services.openpay.public_key') ?? '';
        $this->openpay_production = (bool) (config('services.openpay.production') ?? false);
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

            // Validar credenciales de la pasarela activa
            if ($this->payment_gateway === 'mercadopago') {
                if (empty($this->mercadopago_access_token) || empty($this->mercadopago_public_key)) {
                    session()->flash('error', 'Access Token y Public Key de MercadoPago son requeridos');
                    return;
                }
            } elseif ($this->payment_gateway === 'openpay') {
                if (empty($this->openpay_merchant_id) || empty($this->openpay_private_key) || empty($this->openpay_public_key)) {
                    session()->flash('error', 'ID, Private Key y Public Key de Openpay son requeridos');
                    return;
                }
            }

            // Actualizar archivo .env
            $this->updateEnvFile([
                'TELEGRAM_BOT_TOKEN' => $this->telegram_bot_token,
                'TELEGRAM_CHAT_ID' => $this->telegram_chat_id,
                'TELEGRAM_ADMIN_BOT_TOKEN' => $this->telegram_admin_bot_token,
                'TELEGRAM_ADMIN_CHAT_ID' => $this->telegram_admin_chat_id,
                'ANALIZADOR_TDR_URL' => $this->analizador_url,
                'ANALIZADOR_TDR_ENABLED' => $this->analizador_enabled ? 'true' : 'false',
                'PAYMENT_GATEWAY' => $this->payment_gateway,
                'MERCADOPAGO_ACCESS_TOKEN' => $this->mercadopago_access_token,
                'MERCADOPAGO_PUBLIC_KEY' => $this->mercadopago_public_key,
                'MERCADOPAGO_WEBHOOK_SECRET' => $this->mercadopago_webhook_secret,
                'OPENPAY_MERCHANT_ID' => $this->openpay_merchant_id,
                'OPENPAY_PRIVATE_KEY' => $this->openpay_private_key,
                'OPENPAY_PUBLIC_KEY' => $this->openpay_public_key,
                'OPENPAY_PRODUCTION' => $this->openpay_production ? 'true' : 'false',
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

    public function probarTelegramAdmin()
    {
        $this->loadingTelegramAdminTest = true;
        $this->telegramAdminTestResult = null;

        try {
            if (empty($this->telegram_admin_bot_token) || empty($this->telegram_admin_chat_id)) {
                throw new \Exception('Bot Token y Chat ID del Admin Bot son requeridos');
            }

            $mensaje = "🧪 *Prueba de Conexión — Admin Bot*\n\n"
                     . "✅ Bot de notificaciones administrativas conectado correctamente\n"
                     . "📅 Fecha: " . now()->format('d/m/Y H:i:s') . "\n\n"
                     . "Recibirás alertas de: nuevos usuarios, compras de suscripciones.";

            $apiBase = rtrim((string) config('services.telegram_admin.api_base', config('services.telegram.api_base', '')), '/');

            if (empty($apiBase)) {
                throw new \Exception('Configura TELEGRAM_API_BASE en el .env antes de probar');
            }

            $url = sprintf('%s/bot%s/sendMessage', $apiBase, $this->telegram_admin_bot_token);

            $response = Http::timeout(10)->post($url, [
                'chat_id' => $this->telegram_admin_chat_id,
                'text' => $mensaje,
                'parse_mode' => 'Markdown',
            ]);

            if ($response->successful()) {
                $this->telegramAdminTestResult = [
                    'success' => true,
                    'message' => 'Mensaje enviado exitosamente al Admin Bot',
                ];
            } else {
                throw new \Exception('Error HTTP ' . $response->status() . ': ' . $response->body());
            }
        } catch (\Exception $e) {
            $this->telegramAdminTestResult = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        } finally {
            $this->loadingTelegramAdminTest = false;
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

    public function probarGateway()
    {
        $this->loadingGatewayTest = true;
        $this->gatewayTestResult = null;

        try {
            $manager = new PaymentGatewayManager();
            $gateway = $manager->driver($this->payment_gateway);

            if (!$gateway->isConfigured()) {
                throw new \Exception("La pasarela «{$gateway->displayName()}» no tiene las credenciales completas.");
            }

            // Verificar que la pasarela pueda responder
            if ($this->payment_gateway === 'mercadopago') {
                // Verificar con un GET a la API de MercadoPago (sin cobrar nada)
                $response = Http::withToken($this->mercadopago_access_token)
                    ->timeout(10)
                    ->get('https://api.mercadopago.com/v1/payment_methods');

                if ($response->successful()) {
                    $methods = collect($response->json())->pluck('name')->take(5)->implode(', ');
                    $this->gatewayTestResult = [
                        'success' => true,
                        'message' => "Conexión exitosa con MercadoPago. Métodos disponibles: {$methods}",
                    ];
                } else {
                    throw new \Exception('Error HTTP ' . $response->status() . ': ' . ($response->json('message') ?? $response->body()));
                }
            } elseif ($this->payment_gateway === 'openpay') {
                // Verificar con GET a la API de Openpay
                $baseUrl = $this->openpay_production
                    ? 'https://api.openpay.pe/v1'
                    : 'https://sandbox-api.openpay.pe/v1';

                $response = Http::withBasicAuth($this->openpay_private_key, '')
                    ->timeout(10)
                    ->get("{$baseUrl}/{$this->openpay_merchant_id}");

                if ($response->successful()) {
                    $this->gatewayTestResult = [
                        'success' => true,
                        'message' => 'Conexión exitosa con Openpay. Merchant verificado.',
                    ];
                } else {
                    throw new \Exception('Error HTTP ' . $response->status() . ': ' . $response->body());
                }
            }
        } catch (\Exception $e) {
            $this->gatewayTestResult = [
                'success' => false,
                'error' => 'Error al verificar pasarela: ' . $e->getMessage(),
            ];
        } finally {
            $this->loadingGatewayTest = false;
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

        // Admin bot
        $this->telegram_admin_bot_token = env('TELEGRAM_ADMIN_BOT_TOKEN', '');
        $this->telegram_admin_chat_id = env('TELEGRAM_ADMIN_CHAT_ID', '');
        $this->telegram_admin_enabled = !empty($this->telegram_admin_bot_token) && !empty($this->telegram_admin_chat_id);

        // Pasarela de pago
        $this->payment_gateway = env('PAYMENT_GATEWAY', 'mercadopago');
        $this->mercadopago_access_token = env('MERCADOPAGO_ACCESS_TOKEN', '');
        $this->mercadopago_public_key = env('MERCADOPAGO_PUBLIC_KEY', '');
        $this->mercadopago_webhook_secret = env('MERCADOPAGO_WEBHOOK_SECRET', '');
        $this->openpay_merchant_id = env('OPENPAY_MERCHANT_ID', '');
        $this->openpay_private_key = env('OPENPAY_PRIVATE_KEY', '');
        $this->openpay_public_key = env('OPENPAY_PUBLIC_KEY', '');
        $this->openpay_production = filter_var(env('OPENPAY_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN);
    }
}
