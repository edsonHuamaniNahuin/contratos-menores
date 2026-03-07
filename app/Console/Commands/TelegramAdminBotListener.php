<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\SignalableCommandInterface;

/**
 * Bot de Telegram para administradores.
 *
 * Escucha comandos interactivos y responde con botones inline:
 *  /start       — Bienvenida + menú principal
 *  /escanear    — Estado de todos los servicios del sistema
 *  /usuarios    — Estadísticas de usuarios
 *  /ingresos    — Resumen de ingresos por suscripciones
 *  /help        — Lista de comandos disponibles
 *
 * Configura: TELEGRAM_ADMIN_BOT_TOKEN, TELEGRAM_ADMIN_CHAT_ID en .env
 */
class TelegramAdminBotListener extends Command implements SignalableCommandInterface, Isolatable
{
    protected $signature = 'telegram:admin-listen {--once : Procesar solo una vez}';
    protected $description = 'Bot Admin de Telegram — escucha comandos interactivos del administrador';

    private const OFFSET_CACHE_KEY = 'telegram:admin_listener:last_offset';
    private const OFFSET_CACHE_TTL = 2592000; // 30 días

    protected int $lastUpdateId = 0;
    protected bool $shouldStop = false;
    protected string $botToken;
    protected string $apiBase;
    protected string $adminChatId;

    /** Servicios systemd a verificar */
    private array $services = [
        'vigilante-queue'     => 'Cola de trabajos (Queue Worker)',
        'vigilante-scheduler' => 'Programador de tareas (Scheduler)',
        'telegram-bot'        => 'Bot Telegram (Usuarios)',
        'telegram-admin-bot'  => 'Bot Telegram (Admin)',
        'whatsapp-bot'        => 'Bot WhatsApp',
        'analizador-tdr'      => 'Analizador TDR (FastAPI)',
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->botToken   = trim((string) config('services.telegram_admin.bot_token', ''));
        $this->apiBase    = rtrim((string) config('services.telegram_admin.api_base', config('services.telegram.api_base', '')), '/');
        $this->adminChatId = trim((string) config('services.telegram_admin.chat_id', ''));

        if (empty($this->botToken)) {
            $this->error('TELEGRAM_ADMIN_BOT_TOKEN no configurado');
            return Command::FAILURE;
        }

        if (empty($this->apiBase)) {
            $this->error('Configura TELEGRAM_API_BASE o TELEGRAM_ADMIN_API_BASE en .env');
            return Command::FAILURE;
        }

        // Restaurar offset persistente
        $this->lastUpdateId = (int) Cache::get(self::OFFSET_CACHE_KEY, 0);
        if ($this->lastUpdateId > 0) {
            $this->info("📍 Offset restaurado: {$this->lastUpdateId}");
        }

        $this->info('🛡️ Bot Admin de Telegram iniciado — PID ' . getmypid());
        $this->info('📡 Esperando comandos del administrador...');
        $this->info('🛑 Ctrl+C para detener');

        do {
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }

            if ($this->shouldStop) {
                break;
            }

            try {
                $updates = $this->getUpdates();

                foreach ($updates as $update) {
                    if ($this->shouldStop) {
                        break;
                    }

                    $this->lastUpdateId = $update['update_id'];
                    Cache::put(self::OFFSET_CACHE_KEY, $this->lastUpdateId, self::OFFSET_CACHE_TTL);

                    // Procesar mensajes (comandos)
                    if (isset($update['message'])) {
                        $this->handleMessage($update['message']);
                    }

                    // Procesar callbacks de botones inline
                    if (isset($update['callback_query'])) {
                        $this->handleCallback($update['callback_query']);
                    }
                }

                if (!$this->option('once') && !$this->shouldStop) {
                    usleep(500_000);
                }

            } catch (\Exception $e) {
                $this->error('Error: ' . $e->getMessage());
                Log::error('Admin Bot Listener Error', ['exception' => $e->getMessage()]);

                if (!$this->option('once') && !$this->shouldStop) {
                    sleep(3);
                }
            }
        } while (!$this->shouldStop && !$this->option('once'));

        $this->info('👋 Admin Bot detenido — PID ' . getmypid());

        return Command::SUCCESS;
    }

    /* ══════════════════════════════════════════════════════════════
       MANEJADORES
    ══════════════════════════════════════════════════════════════ */

    protected function handleMessage(array $message): void
    {
        $chatId = (string) ($message['chat']['id'] ?? '');
        $text   = trim($message['text'] ?? '');

        // Solo responder al admin configurado
        if ($chatId !== $this->adminChatId) {
            $this->sendMessage($chatId, "⛔ No estás autorizado para usar este bot.\n\nTu Chat ID: `{$chatId}`", 'Markdown');
            return;
        }

        $command = strtolower(explode(' ', $text)[0] ?? '');

        match ($command) {
            '/start'    => $this->cmdStart($chatId),
            '/escanear' => $this->cmdEscanear($chatId),
            '/usuarios' => $this->cmdUsuarios($chatId),
            '/ingresos' => $this->cmdIngresos($chatId),
            '/help'     => $this->cmdHelp($chatId),
            default     => $this->cmdUnknown($chatId, $text),
        };
    }

    protected function handleCallback(array $callback): void
    {
        $chatId     = (string) ($callback['message']['chat']['id'] ?? '');
        $callbackId = $callback['id'] ?? '';
        $data       = $callback['data'] ?? '';

        // ACK inmediato
        $this->answerCallback($callbackId);

        if ($chatId !== $this->adminChatId) {
            return;
        }

        match (true) {
            $data === 'admin:escanear'       => $this->cmdEscanear($chatId),
            $data === 'admin:usuarios'       => $this->cmdUsuarios($chatId),
            $data === 'admin:ingresos'       => $this->cmdIngresos($chatId),
            $data === 'admin:help'           => $this->cmdHelp($chatId),
            str_starts_with($data, 'admin:') => $this->cmdUnknown($chatId, $data),
            default                          => null,
        };
    }

    /* ══════════════════════════════════════════════════════════════
       COMANDOS
    ══════════════════════════════════════════════════════════════ */

    protected function cmdStart(string $chatId): void
    {
        $msg = "🛡️ *Bot Admin — Vigilante SEACE*\n\n"
             . "Panel de administración del sistema.\n"
             . "Usa los botones o escribe un comando:\n\n"
             . "📋 /escanear — Estado de servicios\n"
             . "👥 /usuarios — Estadísticas de usuarios\n"
             . "💰 /ingresos — Resumen de ingresos\n"
             . "❓ /help — Ayuda";

        $keyboard = [
            [
                ['text' => '📋 Escanear Servicios', 'callback_data' => 'admin:escanear'],
                ['text' => '👥 Usuarios', 'callback_data' => 'admin:usuarios'],
            ],
            [
                ['text' => '💰 Ingresos', 'callback_data' => 'admin:ingresos'],
                ['text' => '❓ Ayuda', 'callback_data' => 'admin:help'],
            ],
        ];

        $this->sendMessage($chatId, $msg, 'Markdown', $keyboard);
    }

    /**
     * /escanear — Verifica el estado de todos los servicios.
     * En producción (Linux) usa systemctl.
     * En desarrollo (Windows) muestra estado simulado de procesos.
     */
    protected function cmdEscanear(string $chatId): void
    {
        $this->sendMessage($chatId, "🔄 *Escaneando servicios...*", 'Markdown');

        $isLinux   = PHP_OS_FAMILY === 'Linux';
        $results   = [];
        $totalOk   = 0;
        $totalFail = 0;

        foreach ($this->services as $service => $label) {
            if ($isLinux) {
                $status = $this->checkSystemdService($service);
            } else {
                // En Windows: verificar si hay procesos PHP relevantes corriendo
                $status = $this->checkWindowsProcess($service);
            }

            if ($status['active']) {
                $totalOk++;
                $icon = '✅';
            } else {
                $totalFail++;
                $icon = '❌';
            }

            $results[] = "{$icon} *{$label}*\n    └ {$status['detail']}";
        }

        // Verificaciones adicionales
        $dbStatus = $this->checkDatabase();
        $diskInfo = $this->checkDiskSpace();
        $queueSize = $this->checkQueueSize();
        $totalServices = $totalOk + $totalFail;

        $msg = "📋 *ESTADO DEL SISTEMA*\n"
             . "─────────────────────\n\n"
             . "🖥️ *Servicios ({$totalOk}/{$totalServices})*\n\n"
             . implode("\n\n", $results) . "\n\n"
             . "─────────────────────\n\n"
             . "🗄️ *Base de Datos:* " . ($dbStatus ? '✅ Conectada' : '❌ Sin conexión') . "\n"
             . "💾 *Disco:* {$diskInfo}\n"
             . "📦 *Cola de trabajos:* {$queueSize} pendientes\n\n"
             . "🕐 _Verificado: " . now()->format('d/m/Y H:i:s') . "_";

        $keyboard = [
            [
                ['text' => '🔄 Re-escanear', 'callback_data' => 'admin:escanear'],
                ['text' => '🏠 Menú', 'callback_data' => 'admin:help'],
            ],
        ];

        $this->sendMessage($chatId, $msg, 'Markdown', $keyboard);
    }

    /**
     * /usuarios — Estadísticas de usuarios del sistema.
     */
    protected function cmdUsuarios(string $chatId): void
    {
        $total          = User::count();
        $hoy            = User::whereDate('created_at', today())->count();
        $semana         = User::where('created_at', '>=', now()->subWeek())->count();
        $mes            = User::where('created_at', '>=', now()->subMonth())->count();
        $premium        = Subscription::where('status', 'active')->count();
        $trial          = Subscription::where('status', 'active')->where('plan', 'trial')->count();
        $empresas       = User::where('account_type', 'empresa')->count();
        $verificados    = User::whereNotNull('email_verified_at')->count();

        // Últimos 5 usuarios registrados
        $ultimos = User::orderBy('created_at', 'desc')->take(5)->get();
        $listUltimos = '';
        foreach ($ultimos as $u) {
            $tipo = $u->account_type === 'empresa' ? '🏢' : '👤';
            $listUltimos .= "  {$tipo} {$u->name} — _{$u->created_at->diffForHumans()}_\n";
        }

        $msg = "👥 *ESTADÍSTICAS DE USUARIOS*\n"
             . "─────────────────────\n\n"
             . "📊 *Totales*\n"
             . "  👥 Total: *{$total}*\n"
             . "  🏢 Empresas: *{$empresas}*\n"
             . "  ✅ Verificados: *{$verificados}*\n\n"
             . "📈 *Registros recientes*\n"
             . "  📅 Hoy: *{$hoy}*\n"
             . "  📆 Última semana: *{$semana}*\n"
             . "  🗓️ Último mes: *{$mes}*\n\n"
             . "⭐ *Suscripciones activas*\n"
             . "  💎 Premium: *{$premium}*\n"
             . "  🆓 Trial: *{$trial}*\n\n"
             . "🆕 *Últimos registros*\n"
             . $listUltimos . "\n"
             . "🕐 _Consultado: " . now()->format('d/m/Y H:i:s') . "_";

        $keyboard = [
            [
                ['text' => '🔄 Actualizar', 'callback_data' => 'admin:usuarios'],
                ['text' => '📋 Escanear', 'callback_data' => 'admin:escanear'],
            ],
            [
                ['text' => '💰 Ingresos', 'callback_data' => 'admin:ingresos'],
                ['text' => '🏠 Menú', 'callback_data' => 'admin:help'],
            ],
        ];

        $this->sendMessage($chatId, $msg, 'Markdown', $keyboard);
    }

    /**
     * /ingresos — Resumen financiero de suscripciones.
     */
    protected function cmdIngresos(string $chatId): void
    {
        $hoy = Subscription::whereDate('created_at', today())
            ->where('plan', '!=', 'trial')
            ->where('status', 'active')
            ->sum('amount');

        $mes = Subscription::where('created_at', '>=', now()->startOfMonth())
            ->where('plan', '!=', 'trial')
            ->where('status', 'active')
            ->sum('amount');

        $total = Subscription::where('plan', '!=', 'trial')
            ->where('status', 'active')
            ->sum('amount');

        $mensuales = Subscription::where('plan', 'monthly')
            ->where('status', 'active')
            ->count();

        $anuales = Subscription::where('plan', 'yearly')
            ->where('status', 'active')
            ->count();

        $trials = Subscription::where('plan', 'trial')
            ->where('status', 'active')
            ->count();

        $porVencer = Subscription::where('status', 'active')
            ->where('ends_at', '<=', now()->addDays(7))
            ->where('ends_at', '>', now())
            ->count();

        // Últimas 5 suscripciones
        $ultimas = Subscription::with('user')
            ->where('plan', '!=', 'trial')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $listUltimas = '';
        foreach ($ultimas as $s) {
            $userName = $s->user->name ?? 'N/A';
            $plan     = $s->plan === 'yearly' ? '📆 Anual' : '📅 Mensual';
            $listUltimas .= "  {$plan} S/{$s->amount} — {$userName}\n    └ _{$s->created_at->diffForHumans()}_\n";
        }

        $msg = "💰 *RESUMEN DE INGRESOS*\n"
             . "─────────────────────\n\n"
             . "💵 *Montos*\n"
             . "  📅 Hoy: *S/ " . number_format((float) $hoy, 2) . "*\n"
             . "  🗓️ Este mes: *S/ " . number_format((float) $mes, 2) . "*\n"
             . "  💎 Total acumulado: *S/ " . number_format((float) $total, 2) . "*\n\n"
             . "📊 *Suscripciones activas*\n"
             . "  📅 Mensuales: *{$mensuales}*\n"
             . "  📆 Anuales: *{$anuales}*\n"
             . "  🆓 Trials: *{$trials}*\n"
             . "  ⚠️ Por vencer (7 días): *{$porVencer}*\n\n"
             . "🆕 *Últimas suscripciones pagas*\n"
             . $listUltimas . "\n"
             . "🕐 _Consultado: " . now()->format('d/m/Y H:i:s') . "_";

        $keyboard = [
            [
                ['text' => '🔄 Actualizar', 'callback_data' => 'admin:ingresos'],
                ['text' => '👥 Usuarios', 'callback_data' => 'admin:usuarios'],
            ],
            [
                ['text' => '📋 Escanear', 'callback_data' => 'admin:escanear'],
                ['text' => '🏠 Menú', 'callback_data' => 'admin:help'],
            ],
        ];

        $this->sendMessage($chatId, $msg, 'Markdown', $keyboard);
    }

    protected function cmdHelp(string $chatId): void
    {
        $msg = "❓ *COMANDOS DISPONIBLES*\n"
             . "─────────────────────\n\n"
             . "📋 /escanear — Estado de todos los servicios\n"
             . "👥 /usuarios — Estadísticas de usuarios\n"
             . "💰 /ingresos — Resumen de ingresos\n"
             . "❓ /help — Esta ayuda\n\n"
             . "También puedes usar los botones interactivos.";

        $keyboard = [
            [
                ['text' => '📋 Escanear Servicios', 'callback_data' => 'admin:escanear'],
                ['text' => '👥 Usuarios', 'callback_data' => 'admin:usuarios'],
            ],
            [
                ['text' => '💰 Ingresos', 'callback_data' => 'admin:ingresos'],
            ],
        ];

        $this->sendMessage($chatId, $msg, 'Markdown', $keyboard);
    }

    protected function cmdUnknown(string $chatId, string $text): void
    {
        $msg = "🤔 Comando no reconocido: _{$text}_\n\nEscribe /help para ver los comandos disponibles.";

        $keyboard = [
            [
                ['text' => '❓ Ver Ayuda', 'callback_data' => 'admin:help'],
            ],
        ];

        $this->sendMessage($chatId, $msg, 'Markdown', $keyboard);
    }

    /* ══════════════════════════════════════════════════════════════
       VERIFICACIONES DE ESTADO
    ══════════════════════════════════════════════════════════════ */

    protected function checkSystemdService(string $name): array
    {
        try {
            $output = shell_exec("systemctl is-active {$name}.service 2>/dev/null");
            $status = trim((string) $output);

            if ($status === 'active') {
                // Obtener uptime
                $uptime = trim((string) shell_exec("systemctl show {$name}.service --property=ActiveEnterTimestamp --value 2>/dev/null"));
                $since  = $uptime ? "Activo desde " . $uptime : 'Activo';
                return ['active' => true, 'detail' => $since];
            }

            return ['active' => false, 'detail' => "Estado: {$status}"];
        } catch (\Exception $e) {
            return ['active' => false, 'detail' => 'Error al verificar'];
        }
    }

    protected function checkWindowsProcess(string $service): array
    {
        $processMap = [
            'vigilante-queue'     => 'queue:work',
            'vigilante-scheduler' => 'schedule:work',
            'telegram-bot'        => 'telegram:listen',
            'telegram-admin-bot'  => 'telegram:admin-listen',
            'whatsapp-bot'        => 'whatsapp:listen',
            'analizador-tdr'      => 'uvicorn',
        ];

        $search = $processMap[$service] ?? $service;

        try {
            $output = shell_exec("tasklist /FI \"IMAGENAME eq php.exe\" /FO CSV 2>NUL");

            if ($search === 'uvicorn') {
                $output = shell_exec("tasklist /FI \"IMAGENAME eq python.exe\" /FO CSV 2>NUL");
            }

            // En Windows simplemente reportar modo desarrollo
            return ['active' => false, 'detail' => 'Modo desarrollo (Windows)'];
        } catch (\Exception $e) {
            return ['active' => false, 'detail' => 'N/A (Windows)'];
        }
    }

    protected function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function checkDiskSpace(): string
    {
        try {
            if (PHP_OS_FAMILY === 'Linux') {
                $free  = disk_free_space('/');
                $total = disk_total_space('/');
            } else {
                $free  = disk_free_space('D:');
                $total = disk_total_space('D:');
            }

            if ($total > 0) {
                $usedPct = round((1 - $free / $total) * 100);
                $freeGb  = round($free / 1073741824, 1);
                return "{$freeGb} GB libres ({$usedPct}% usado)";
            }

            return 'N/A';
        } catch (\Exception $e) {
            return 'Error al consultar';
        }
    }

    protected function checkQueueSize(): int
    {
        try {
            return DB::table('jobs')->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /* ══════════════════════════════════════════════════════════════
       TELEGRAM API
    ══════════════════════════════════════════════════════════════ */

    protected function getUpdates(): array
    {
        try {
            $url = "{$this->apiBase}/bot{$this->botToken}/getUpdates";

            $response = Http::timeout(35)->get($url, [
                'offset'          => $this->lastUpdateId + 1,
                'timeout'         => 30,
                'allowed_updates' => json_encode(['message', 'callback_query']),
            ]);

            if ($response->successful()) {
                return $response->json('result') ?? [];
            }

            Log::warning('Admin Bot: Error getUpdates', ['status' => $response->status()]);
            return [];
        } catch (\Exception $e) {
            Log::error('Admin Bot: Excepción getUpdates', ['error' => $e->getMessage()]);
            return [];
        }
    }

    protected function sendMessage(string $chatId, string $text, string $parseMode = 'Markdown', ?array $inlineKeyboard = null): bool
    {
        try {
            $url = "{$this->apiBase}/bot{$this->botToken}/sendMessage";

            $payload = [
                'chat_id'    => $chatId,
                'text'       => $text,
                'parse_mode' => $parseMode,
            ];

            if ($inlineKeyboard) {
                $payload['reply_markup'] = json_encode([
                    'inline_keyboard' => $inlineKeyboard,
                ]);
            }

            $response = Http::timeout(10)->post($url, $payload);

            if (!$response->successful()) {
                Log::warning('Admin Bot: Error sendMessage', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Admin Bot: Excepción sendMessage', ['error' => $e->getMessage()]);
            return false;
        }
    }

    protected function answerCallback(string $callbackId): void
    {
        try {
            $url = "{$this->apiBase}/bot{$this->botToken}/answerCallbackQuery";

            Http::timeout(5)->post($url, [
                'callback_query_id' => $callbackId,
            ]);
        } catch (\Exception $e) {
            // Silenciar — no es crítico
        }
    }

    /* ══════════════════════════════════════════════════════════════
       SIGNALS / ISOLATABLE
    ══════════════════════════════════════════════════════════════ */

    public function isolatableId(): string
    {
        return 'telegram-admin-bot-listener';
    }

    public function getSubscribedSignals(): array
    {
        return defined('SIGINT') ? [SIGINT, SIGTERM] : [];
    }

    public function handleSignal(int $signal, int|false $previousExitCode = 0): int|false
    {
        $this->shouldStop = true;
        $this->info("\n🛑 Señal recibida ({$signal}), deteniendo...");
        return false;
    }
}
