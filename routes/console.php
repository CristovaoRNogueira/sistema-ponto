<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// 🔁 Roda o robô de sincronização a cada 1 minuto
Schedule::command('ponto:sync')
    ->everyMinute()
    ->withoutOverlapping() // Evita execução simultânea
    ->appendOutputTo(storage_path('logs/ponto_sync.log'));