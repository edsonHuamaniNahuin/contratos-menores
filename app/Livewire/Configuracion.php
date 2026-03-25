<?php

namespace App\Livewire;

use App\Models\SystemSetting;
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
        // Leer desde BD (system_settings), con fallback a config/.env
        $this->telegram_bot_token = SystemSetting::getValue('telegram_bot_token', config('services.telegram.bot_token') ?? '') ?? '';
        $this->telegram_chat_id = SystemSetting::getValue('telegram_chat_id', config('services.telegram.chat_id') ?? '') ?? '';
        $this->telegram_enabled = !empty($this->telegram_bot_token) && !empty($this->telegram_chat_id);

        $this->telegram_admin_bot_token = SystemSetting::getValue('telegram_admin_bot_token', config('services.telegram_admin.bot_token') ?? '') ?? '';
        $this->telegram_admin_chat_id = SystemSetting::getValue('telegram_admin_chat_id', config('services.telegram_admin.chat_id') ?? '') ?? '';
        $this->telegram_admin_enabled = !empty($this->telegram_admin_bot_token) && !empty($this->telegram_admin_chat_id);

        $this->analizador_url = SystemSetting::getValue('analizador_tdr_url', config('services.analizador_tdr.url') ?? 'http://127.0.0.1:8001') ?? 'http://127.0.0.1:8001';
        $this->analizador_enabled = (bool) SystemSetting::getValue('analizador_tdr_enabled', config('services.analizador_tdr.enabled') ? '1' : '0');

        $this->whatsapp_bot_token = SystemSetting::getValue('whatsapp_bot_token', config('services.whatsapp.bot_token') ?? '') ?? '';
        $this->whatsapp_group_id = SystemSetting::getValue('whatsapp_group_id', config('services.whatsapp.group_id') ?? '') ?? '';
        $this->whatsapp_enabled = !empty($this->whatsapp_bot_token) && !empty($this->whatsapp_group_id);

        $this->payment_gateway = SystemSetting::getValue('payment_gateway', config('services.payment_gateway') ?? 'mercadopago') ?? 'mercadopago';
        $this->mercadopago_access_token = SystemSetting::getValue('mercadopago_access_token', config('services.mercadopago.access_token') ?? '') ?? '';
        $this->mercadopago_public_key = SystemSetting::getValue('mercadopago_public_key', config('services.mercadopago.public_key') ?? '') ?? '';
        $this->mercadopago_webhook_secret = SystemSetting::getValue('mercadopago_webhook_secret', config('services.mercadopago.webhook_secret') ?? '') ?? '';
        $this->openpay_merchant_id = SystemSetting::getValue('openpay_merchant_id', config('services.openpay.merchant_id') ?? '') ?? '';
        $this->openpay_private_key = SystemSetting::getValue('openpay_private_key', config('services.openpay.private_key') ?? '') ?? '';
        $this->openpay_public_key = SystemSetting::getValue('openpay_public_key', config('services.openpay.public_key') ?? '') ?? '';
        $this->openpay_production = (bool) SystemSetting::getValue('openpay_production', config('services.openpay.production') ? '1' : '0');
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

            if ($this->telegram_admin_enabled) {
                if (empty($this->telegram_admin_bot_token) || empty($this->telegram_admin_chat_id)) {
                    session()->flash('error', 'Bot Token y Chat ID son requeridos para el Bot Admin de Telegram');
                    return;
                }
            }

            if ($this->analizador_enabled) {
                if (empty($this->analizador_url)) {
                    session()->flash('error', 'URL del Analizador TDR es requerida');
                    return;
                }
            }

            // Validar credenciales de la pasarela activa (solo si el usuario llenó algo parcial)
            if ($this->payment_gateway === 'mercadopago') {
                $mpHasAny = !empty($this->mercadopago_access_token) || !empty($this->mercadopago_public_key);
                $mpHasAll = !empty($this->mercadopago_access_token) && !empty($this->mercadopago_public_key);
                if ($mpHasAny && !$mpHasAll) {
                    session()->flash('error', 'Access Token y Public Key de MercadoPago son requeridos si configuras esa pasarela');
                    return;
                }
            } elseif ($this->payment_gateway === 'openpay') {
                $opHasAny = !empty($this->openpay_merchant_id) || !empty($this->openpay_private_key) || !empty($this->openpay_public_key);
                $opHasAll = !empty($this->openpay_merchant_id) && !empty($this->openpay_private_key) && !empty($this->openpay_public_key);
                if ($opHasAny && !$opHasAll) {
                    session()->flash('error', 'Si configuras Openpay, completa ID, Private Key y Public Key');
                    return;
                }
            }

            // Guardar en base de datos (system_settings)
            SystemSetting::setMany([
                'telegram_bot_token' => $this->telegram_bot_token,
                'telegram_chat_id' => $this->telegram_chat_id,
                'telegram_admin_bot_token' => $this->telegram_admin_bot_token,
                'telegram_admin_chat_id' => $this->telegram_admin_chat_id,
                'analizador_tdr_url' => $this->analizador_url,
                'analizador_tdr_enabled' => $this->analizador_enabled ? '1' : '0',
                'whatsapp_bot_token' => $this->whatsapp_bot_token,
                'whatsapp_group_id' => $this->whatsapp_group_id,
                'payment_gateway' => $this->payment_gateway,
                'mercadopago_access_token' => $this->mercadopago_access_token,
                'mercadopago_public_key' => $this->mercadopago_public_key,
                'mercadopago_webhook_secret' => $this->mercadopago_webhook_secret,
                'openpay_merchant_id' => $this->openpay_merchant_id,
                'openpay_private_key' => $this->openpay_private_key,
                'openpay_public_key' => $this->openpay_public_key,
                'openpay_production' => $this->openpay_production ? '1' : '0',
            ]);

            session()->flash('success', '✓ Configuración guardada correctamente.');

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

    public function render()
    {
        return view('livewire.configuracion');
    }
}
