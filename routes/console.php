<?php

use App\Jobs\ImportarTdrNotificarJob;
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
