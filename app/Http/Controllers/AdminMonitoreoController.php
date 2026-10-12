<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Vista de monitoreo del sistema en tiempo real.
 *
 * Muestra el estado de:
 *  - Cola de jobs (pendientes y fallidos)
 *  - Servicios systemd (queue, scheduler, bots, php-fpm, apache, mysql)
 *  - Salud del servidor (load, memoria, disco)
 *  - Errores recientes del log de Laravel
 *  - Últimas corridas de los jobs programados
 */
class AdminMonitoreoController extends Controller
{
    protected const SERVICIOS = [
        'vigilante-queue'      => 'Queue Worker',
        'vigilante-scheduler'  => 'Laravel Scheduler',
        'telegram-bot'         => 'Bot Telegram',
        'whatsapp-bot'         => 'Bot WhatsApp',
        'analizador-tdr'       => 'Analizador TDR (IA)',
        'php-fpm'              => 'PHP-FPM',
        'apache2'              => 'Apache',
        'mysql'                => 'MySQL',
    ];

    public function __invoke(Request $request)
    {
        return view('admin.monitoreo', [
            'servicios'      => $this->estadoServicios(),
            'salud'          => $this->saludServidor(),
            'cola'           => $this->estadoCola(),
            'errores'        => $this->erroresRecientes(15),
            'schedules'      => $this->ultimasCorridasScheduler(),
            'refrescadoEn'   => now(),
        ]);
    }

    /**
     * Estado de servicios systemd (is-active no requiere root).
     */
    protected function estadoServicios(): array
    {
        $resultado = [];

        foreach (self::SERVICIOS as $servicio => $label) {
            $estado = 'desconocido';
            $raw = @shell_exec("systemctl is-active {$servicio} 2>&1");

            if (is_string($raw)) {
                $estado = trim($raw);
            }

            $resultado[] = [
                'servicio' => $servicio,
                'label'    => $label,
                'estado'   => $estado,
                'activo'   => $estado === 'active',
            ];
        }

        return $resultado;
    }

    /**
     * Métricas básicas del servidor desde /proc y PHP.
     */
    protected function saludServidor(): array
    {
        // Load average
        $load = 'N/A';
        $loadRaw = @file_get_contents('/proc/loadavg');
        if ($loadRaw !== false) {
            $partes = explode(' ', trim($loadRaw));
            $load = implode(' / ', array_slice($partes, 0, 3));
        }

        // Memoria
        $memUsada = null;
        $memTotal = null;
        $memPct = null;
        $memRaw = @file_get_contents('/proc/meminfo');
        if ($memRaw !== false) {
            preg_match('/MemTotal:\s+(\d+)/', $memRaw, $mTotal);
            preg_match('/MemAvailable:\s+(\d+)/', $memRaw, $mAvail);
            if ($mTotal && $mAvail) {
                $memTotal = (int) $mTotal[1];
                $memUsada = $memTotal - (int) $mAvail[1];
                $memPct = round(($memUsada / $memTotal) * 100, 1);
            }
        }

        // Disco
        $discoTotal = @disk_total_space('/');
        $discoLibre = @disk_free_space('/');
        $discoPct = ($discoTotal > 0) ? round((($discoTotal - $discoLibre) / $discoTotal) * 100, 1) : null;

        // Uptime
        $uptime = 'N/A';
        $uptimeRaw = @file_get_contents('/proc/uptime');
        if ($uptimeRaw !== false) {
            $segundos = (int) explode(' ', trim($uptimeRaw))[0];
            $uptime = $this->formatearUptime($segundos);
        }

        return [
            'load'        => $load,
            'mem_usada'   => $memUsada !== null ? round($memUsada / 1024 / 1024, 1) : null,
            'mem_total'   => $memTotal !== null ? round($memTotal / 1024 / 1024, 1) : null,
            'mem_pct'     => $memPct,
            'disco_total' => $discoTotal !== false ? round($discoTotal / 1024 / 1024 / 1024, 1) : null,
            'disco_libre' => $discoLibre !== false ? round($discoLibre / 1024 / 1024 / 1024, 1) : null,
            'disco_pct'   => $discoPct,
            'uptime'      => $uptime,
        ];
    }

    /**
     * Estado de la cola de jobs y fallos.
     */
    protected function estadoCola(): array
    {
        try {
            $pendientes = DB::table('jobs')->count();
        } catch (\Throwable $e) {
            $pendientes = 0;
        }

        try {
            $fallidos24h = DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subDay())
                ->count();
        } catch (\Throwable $e) {
            $fallidos24h = 0;
        }

        try {
            $ultimoFallo = DB::table('failed_jobs')->max('failed_at');
        } catch (\Throwable $e) {
            $ultimoFallo = null;
        }

        return [
            'pendientes'     => $pendientes,
            'fallidos_24h'   => $fallidos24h,
            'ultimo_fallo'   => $ultimoFallo,
        ];
    }

    /**
     * Errores recientes del log de Laravel (últimos N).
     *
     * Lee solo la cola del archivo (512 KB) en vez de cargar todo el log
     * en memoria, y usa el log más reciente (con LOG_STACK=daily los
     * archivos rotan por día: laravel-YYYY-MM-DD.log).
     */
    protected function erroresRecientes(int $limite = 15): array
    {
        $ruta = $this->logReciente();

        if ($ruta === null) {
            return [];
        }

        $handle = @fopen($ruta, 'r');
        if ($handle === false) {
            return [];
        }

        $tamano = (int) (@filesize($ruta) ?: 0);
        $leer = min($tamano, 512 * 1024);

        fseek($handle, max(0, $tamano - $leer));
        if ($tamano > $leer) {
            fgets($handle); // descartar la primera línea parcial
        }

        $errores = [];
        while (($linea = fgets($handle)) !== false) {
            if (!str_contains($linea, '.ERROR:')) {
                continue;
            }

            preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $linea, $mFecha);

            $mensaje = preg_replace('/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] \w+\.ERROR:\s*/', '', trim($linea));

            $errores[] = [
                'fecha'   => $mFecha[1] ?? '',
                'mensaje' => mb_substr((string) $mensaje, 0, 200),
            ];
        }

        fclose($handle);

        return array_slice($errores, -$limite);
    }

    /**
     * Archivo de log más reciente (soporta single y daily).
     */
    protected function logReciente(): ?string
    {
        $archivos = array_filter(
            glob(storage_path('logs/laravel*.log')) ?: [],
            'is_file'
        );

        if (empty($archivos)) {
            return null;
        }

        usort($archivos, fn ($a, $b) => filemtime($b) <=> filemtime($a));

        return $archivos[0];
    }

    /**
     * Últimas corridas de los jobs programados desde sus logs dedicados.
     */
    protected function ultimasCorridasScheduler(): array
    {
        $logs = [
            'Refresco estados mayores'  => storage_path('logs/refrescar-estados-mayores.log'),
            'Importar contratos mayores' => storage_path('logs/importar-contratos-mayores.log'),
            'Notificar mayores'          => storage_path('logs/notificar-mayores-schedule.log'),
            'Scheduler'                  => '/var/log/vigilante-seace/scheduler.log',
        ];

        $resultado = [];

        foreach ($logs as $nombre => $ruta) {
            $ultimaLinea = null;
            $modificado = null;

            if (is_file($ruta)) {
                $modificado = filemtime($ruta);
                $contenido = @file($ruta);
                if (is_array($contenido) && !empty($contenido)) {
                    // Última línea no vacía
                    for ($i = count($contenido) - 1; $i >= 0; $i--) {
                        $linea = trim($contenido[$i]);
                        if ($linea !== '') {
                            $ultimaLinea = mb_substr($linea, 0, 160);
                            break;
                        }
                    }
                }
            }

            $stale = $modificado !== null
                && (time() - $modificado) > (6 * 3600); // más de 6h sin tocar = posible problema

            $resultado[] = [
                'nombre'     => $nombre,
                'ultima'     => $ultimaLinea,
                'hace'       => $modificado !== null ? $this->hace($modificado) : 'sin log',
                'stale'      => $stale,
            ];
        }

        return $resultado;
    }

    protected function formatearUptime(int $segundos): string
    {
        $dias = floor($segundos / 86400);
        $horas = floor(($segundos % 86400) / 3600);
        $minutos = floor(($segundos % 3600) / 60);

        return "{$dias}d {$horas}h {$minutos}m";
    }

    protected function hace(int $timestamp): string
    {
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return "{$diff}s";
        }
        if ($diff < 3600) {
            return floor($diff / 60) . 'm';
        }
        if ($diff < 86400) {
            return floor($diff / 3600) . 'h';
        }

        return floor($diff / 86400) . 'd';
    }
}
