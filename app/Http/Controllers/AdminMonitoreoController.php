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
     */
    protected function erroresRecientes(int $limite = 15): array
    {
        $ruta = storage_path('logs/laravel.log');

        if (!is_file($ruta)) {
            return [];
        }

        $errores = [];
        $lineas = @file($ruta);

        if (!is_array($lineas)) {
            return [];
        }

        // Leer de atrás hacia adelante para capturar los más recientes
        for ($i = count($lineas) - 1; $i >= 0 && count($errores) < $limite; $i--) {
            $linea = $lineas[$i];
            if (!str_contains($linea, '.ERROR:')) {
                continue;
            }

            // Extraer fecha y mensaje corto
            preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $linea, $mFecha);
            $fecha = $mFecha[1] ?? '';

            $mensaje = preg_replace('/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] \w+\.ERROR:\s*/', '', trim($linea));
            $mensaje = mb_substr($mensaje, 0, 200);

            $errores[] = [
                'fecha'   => $fecha,
                'mensaje' => $mensaje,
            ];
        }

        return $errores;
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
