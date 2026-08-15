<?php

use App\Jobs\ImportarContratosDiarioJob;
use App\Jobs\ImportarContratosMayoresJob;
use App\Jobs\ImportarTdrNotificarJob;
use App\Jobs\NotificarContratosMayoresJob;
use App\Jobs\NotificarEmailSuscriptoresJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Importador TDR + Notificación Telegram/WhatsApp (Automatizado)
|--------------------------------------------------------------------------
| Lunes a domingo, cada 2 horas entre 06:00 y 20:00 (hora Lima).
| Horarios: 06:00, 08:00, 10:00, 12:00, 14:00, 16:00, 18:00, 20:00
| La ejecución de las 06:00 busca procesos de HOY + AYER (cubre la
| brecha nocturna 20:00→06:00). Las demás solo buscan HOY.
| Carbon::subDay() maneja automáticamente fin de mes (31→1, 28/29→1).
*/
Schedule::job(new ImportarTdrNotificarJob())
    ->everyTwoHours()
    ->between('06:00', '20:00')
    ->timezone('America/Lima')
    ->withoutOverlapping(30)
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/importador-tdr-schedule.log'));

/*
|--------------------------------------------------------------------------
| Importador de Contratos Diario (Dashboard Charts)
|--------------------------------------------------------------------------
| Todos los días a las 2:00 AM (hora Lima).
| Escanea TODOS los departamentos con la fecha del día anterior.
| Alimenta las tablas que nutren los gráficos del dashboard.
*/
Schedule::job(new ImportarContratosDiarioJob())
    ->dailyAt('02:00')
    ->timezone('America/Lima')
    ->withoutOverlapping(30)
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/importar-contratos-diario.log'));

/*
|--------------------------------------------------------------------------
| Expirar Suscripciones Vencidas
|--------------------------------------------------------------------------
| Cada hora revisa si hay suscripciones/trials expirados y revoca el rol
| premium automáticamente.
*/
Schedule::command('subscriptions:expire')
    ->hourly()
    ->timezone('America/Lima')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/subscriptions-expire.log'));

/*
|--------------------------------------------------------------------------
| Campañas de correo — envío programado
|--------------------------------------------------------------------------
| Cada 5 minutos revisa si hay campañas de correo programadas para enviar.
*/
Schedule::command('campaigns:send-scheduled')
    ->everyFiveMinutes()
    ->timezone('America/Lima')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Renovar Suscripciones (Cobro Recurrente)
|--------------------------------------------------------------------------
| Cada 6 horas intenta renovar suscripciones que vencen en las próximas 24h.
| Cobra la tarjeta almacenada en Openpay automáticamente.
| Trial vencido → cobra S/49 → convierte a mensual.
| Mensual vencido → cobra S/49 → renueva 30 días.
| Anual vencido → cobra S/470 → renueva 365 días.
*/
Schedule::command('subscriptions:renew')
    ->everySixHours()
    ->timezone('America/Lima')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/subscriptions-renew.log'));

/*
|--------------------------------------------------------------------------
| Alertas de Vencimiento de Suscripción (Email)
|--------------------------------------------------------------------------
| Todos los días a las 08:00 AM (hora Lima).
| Envia emails a usuarios cuyo trial/suscripción vence en 1 o 3 días.
| Avisa del cobro automático inminente si tienen auto_renew activo.
*/
Schedule::command('subscriptions:alerts')
    ->dailyAt('08:00')
    ->timezone('America/Lima')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/subscriptions-alerts.log'));

/*
|--------------------------------------------------------------------------
| Notificaciones por Email (Procesos nuevos del SEACE)
|--------------------------------------------------------------------------
| Se ejecuta junto con el importador TDR (L-D, cada 2h, 06:00-20:00).
| Envía un email por cada proceso nuevo que coincida con los filtros del
| suscriptor. Usa dedup para no enviar el mismo proceso dos veces.
| Dos modos: "recibir todo" o "solo keywords que coincidan".
*/
Schedule::job(new NotificarEmailSuscriptoresJob())
    ->everyTwoHours()
    ->between('06:00', '20:00')
    ->timezone('America/Lima')
    ->withoutOverlapping(30)
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/notificar-email-schedule.log'));

/*
|--------------------------------------------------------------------------
| Notificador de Contratos Mayores (Telegram + WhatsApp)
|--------------------------------------------------------------------------
| Cada 2 horas (06:00-20:00, igual que Menores).
| Lee contratos_mayores publicados en las últimas 12h (ventana amplia para
| cubrir el hueco nocturno 20:00-06:00), compara keywords y envía.
| Dedup per-suscriptor vía ProcessNotificationTracker (notified_processes
| + notification_sends): cada contrato se notifica UNA vez por canal/usuario.
*/
Schedule::job(new NotificarContratosMayoresJob(12))
    ->everyTwoHours()
    ->between('06:00', '20:00')
    ->timezone('America/Lima')
    ->withoutOverlapping(30)
    ->appendOutputTo(storage_path('logs/notificar-mayores-schedule.log'));

/*
|--------------------------------------------------------------------------
| Importador de Contratos Mayores (API OCDS → BD)
|--------------------------------------------------------------------------
| Cada 3 horas. Escanea 15 páginas (page_size=20, ~300 releases ≈ últimas
| 2-3 horas de eventos) y persiste los nuevos en contratos_mayores.
| También actualiza cambios recientes (estado/proveedores/fechas).
|
| El refresco de estados ANTIGUOS lo hace RefrescarEstadosContratosMayoresJob
| (abajo) vía /records?ocid=, por eso este job puede ser liviano.
|
| Costo: 15 páginas × 8 corridas/día = 120 llamadas HTTP/día.
*/
Schedule::job(new ImportarContratosMayoresJob(15, 20))
    ->cron('0 0,3,6,9,12,15,18,21 * * *')
    ->timezone('America/Lima')
    ->withoutOverlapping(30)
    ->appendOutputTo(storage_path('logs/importar-contratos-mayores.log'));

/*
|--------------------------------------------------------------------------
| Refresco de Estados — Contratos Mayores (estrategia día/noche)
|--------------------------------------------------------------------------
| MADRUGADA (03:00): refresco de contratos publicados en los últimos
| 30 días (los que tienen probabilidad real de cambiar de estado).
| Garantiza frescura diaria del universo activo.
| Costo: ~600-800 llamadas HTTP (~6-8 min) a las 3 AM.
|
| DÍA (13:00, 18:00): refresco ligero de los últimos 7 días, 300 contratos
| c/u (los más viejos primero). Captura cambios de la mañana y la tarde.
| Costo: 600 llamadas HTTP/día.
|
| IMPORT (cada 3h): descubre contratos nuevos (15 páginas, 120 calls/día).
|
| El escaneo GLOBAL (todos los contratos sin filtro de fecha) se ejecuta
| SOLO manualmente: dispatch(new RefrescarEstadosContratosMayoresJob(20000, 0)).
*/
Schedule::job(new \App\Jobs\RefrescarEstadosContratosMayoresJob(20000, 30))
    ->cron('0 3 * * *')
    ->timezone('America/Lima')
    ->withoutOverlapping(60)
    ->appendOutputTo(storage_path('logs/refrescar-estados-mayores.log'));

Schedule::job(new \App\Jobs\RefrescarEstadosContratosMayoresJob(300, 7))
    ->cron('0 13,18 * * *')
    ->timezone('America/Lima')
    ->withoutOverlapping(60)
    ->appendOutputTo(storage_path('logs/refrescar-estados-mayores.log'));

/*
|--------------------------------------------------------------------------
| Limpieza de Cache Antiguo — Contratos Mayores
|--------------------------------------------------------------------------
| Diario a las 03:30 AM. Elimina keys de cache tipo
| "contratos_mayores:ocids:YYYY-MM-DD" con 2+ días de antigüedad.
*/
Schedule::command('contratos-mayores:limpiar-cache --dias=2')
    ->dailyAt('03:30')
    ->timezone('America/Lima')
    ->appendOutputTo(storage_path('logs/limpiar-cache-mayores.log'));

/*
|--------------------------------------------------------------------------
| Refresco de Entidades — Contratos Mayores
|--------------------------------------------------------------------------
| Semanal (domingo 04:00 AM). Extrae entidades únicas desde contratos_mayores,
| las persiste en entidades_mayores, e invalida el cache de 7 días.
| Alimenta el autocompletado de entidades en el buscador.
*/
Schedule::command('contratos-mayores:actualizar-entidades')
    ->weeklyOn(0, '04:00')
    ->timezone('America/Lima')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/entidades-mayores.log'));

/*
|--------------------------------------------------------------------------
| Backup Automático de Base de Datos
|--------------------------------------------------------------------------
| Cada día a la hora configurada (BACKUP_SCHEDULE_AT, por defecto 03:00).
| Solo en entornos locales (local/qa), nunca en producción.
| Retiene máximo BACKUP_MAX_BACKUPS (7) archivos, rota los antiguos.
| No genera backup si ya existe uno del mismo día.
*/
if (!app()->environment('production')) {
    Schedule::command('db:backup')
        ->dailyAt(env('BACKUP_SCHEDULE_AT', '03:00'))
        ->timezone('America/Lima')
        ->withoutOverlapping()
        ->appendOutputTo(storage_path('logs/db-backup.log'));
}
