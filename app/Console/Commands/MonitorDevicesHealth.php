<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Device;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MonitorDevicesHealth extends Command
{
    protected $signature = 'devices:monitor-health';
    protected $description = 'Verifica ativamente o status (Online/Offline) e nível de bobina dos relógios Control iDClass via VPN.';

    public function handle()
    {
        $devices = Device::all();

        foreach ($devices as $device) {
            if (empty($device->ip_address)) {
                continue;
            }

            $baseUrl = "https://{$device->ip_address}";

            try {
                // 1. Faz o Login ignorando o SSL
                $loginResponse = Http::withOptions(['verify' => false])->asJson()
                    ->timeout(10)
                    ->post("{$baseUrl}/login.fcgi", [
                        'login' => $device->username ?? 'admin',
                        'password' => $device->password ?? 'admin'
                    ]);

                if (!$loginResponse->successful() || !isset($loginResponse['session'])) {
                    throw new \Exception("Falha na autenticação HTTP " . $loginResponse->status());
                }

                $sessionToken = $loginResponse['session'];

                // 2. CHAMA O COMANDO EXATO QUE DESCOBRIMOS (get_system_information.fcgi)
                $sysRes = Http::withOptions(['verify' => false])->asJson()
                    ->timeout(10)
                    ->post("{$baseUrl}/get_system_information.fcgi?session={$sessionToken}", [
                        'session' => $sessionToken
                    ]);

                $paperStatus = 'ok';
                $avisoExtra = null;

                if ($sysRes->successful()) {
                    $data = $sysRes->json();

                    // Lê as variáveis oficiais do iDClass
                    $paperOk = $data['paper_ok'] ?? true;
                    $isLow = isset($data['low_paper']) && $data['low_paper'] === true;

                    if ($paperOk === false) {
                        $paperStatus = 'empty';
                        $avisoExtra = "Bobina vazia ou tampa aberta.";
                    } elseif ($isLow) {
                        $paperStatus = 'low';
                        $avisoExtra = "Pouco papel restante.";
                    }
                } else {
                    throw new \Exception("Erro ao ler informações: HTTP " . $sysRes->status());
                }

                $device->update([
                    'is_online' => true,
                    'last_seen_at' => Carbon::now(),
                    'paper_status' => $paperStatus,
                    'last_error' => $avisoExtra
                ]);

                $this->info("Relógio [{$device->name}] - Online - Bobina: {$paperStatus}");

                // Logout
                Http::withOptions(['verify' => false])->asJson()
                    ->post("{$baseUrl}/logout.fcgi?session={$sessionToken}", [
                        'session' => $sessionToken
                    ]);
            } catch (\Exception $e) {
                $device->update([
                    'is_online' => false,
                    'last_error' => substr($e->getMessage(), 0, 255)
                ]);
                $this->error("Relógio [{$device->name}] - OFFLINE (Erro: " . $e->getMessage() . ")");
            }
        }
    }
}
