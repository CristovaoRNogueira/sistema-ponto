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


// 🔁 Roda o robô de sincronização (Coleta de Batidas) a cada 1 minuto
Schedule::command('ponto:sync')
    ->everyMinute()
    ->withoutOverlapping() // Evita execução simultânea
    ->appendOutputTo(storage_path('logs/ponto_sync.log'));

// 📡 Roda o Radar de Saúde dos Relógios (Verifica Rede e Bobina de Papel) a cada 5 minutos
Schedule::command('devices:monitor-health')
    ->everyFiveMinutes()
    ->withoutOverlapping() // Não roda de novo se o anterior ainda estiver processando
    ->appendOutputTo(storage_path('logs/devices_health.log'));
