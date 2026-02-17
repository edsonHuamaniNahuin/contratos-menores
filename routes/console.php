<?php

use App\Jobs\ImportarContratosDiarioJob;
use App\Jobs\ImportarTdrNotificarJob;
use App\Jobs\NotificarEmailSuscriptoresJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Importador TDR + Notificación Telegram (Automatizado)
|--------------------------------------------------------------------------
| Lunes a viernes, cada 2 horas entre 10:00 y 18:00 (hora Lima).
| Horarios: 10:00, 12:00, 14:00, 16:00, 18:00
| Usa la fecha del día actual para buscar procesos publicados hoy.
*/
Schedule::job(new ImportarTdrNotificarJob())
    ->weekdays()
    ->everyTwoHours()
    ->between('10:00', '18:00')
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
| Notificaciones por Email (Procesos nuevos del SEACE)
|--------------------------------------------------------------------------
| Se ejecuta junto con el importador Telegram (L-V, cada 2h, 10:00-18:00).
| Envía un email por cada proceso nuevo que coincida con los filtros del
| suscriptor. Usa dedup para no enviar el mismo proceso dos veces.
| Dos modos: "recibir todo" o "solo keywords que coincidan".
*/
Schedule::job(new NotificarEmailSuscriptoresJob())
    ->weekdays()
    ->everyTwoHours()
    ->between('10:00', '18:00')
    ->timezone('America/Lima')
    ->withoutOverlapping(30)
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/notificar-email-schedule.log'));
