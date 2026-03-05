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
    protected $description = 'Verifica ativamente o status (Online/Offline) e nível de bobina dos relógios Control iD via VPN.';

    public function handle()
    {
        $devices = Device::all();

        foreach ($devices as $device) {
            if (empty($device->ip_address)) {
                continue;
            }

            // FORÇA O HTTPS 
            $baseUrl = "https://{$device->ip_address}";

            try {
                // Autenticação com verify => false
                $loginResponse = Http::withOptions(['verify' => false])
                    ->timeout(10)
                    ->post("{$baseUrl}/login.fcgi", [
                        'login' => $device->username ?? 'admin',
                        'password' => $device->password ?? 'admin'
                    ]);

                if (!$loginResponse->successful()) {
                    throw new \Exception("Falha na autenticação HTTP " . $loginResponse->status());
                }

                $sessionToken = $loginResponse->json('session');

                // Consulta Impressora com verify => false
                $printerResponse = Http::withOptions(['verify' => false])
                    ->withHeaders(['session' => $sessionToken])
                    ->timeout(10)
                    ->post("{$baseUrl}/get_printer_status.fcgi");

                $paperStatus = 'ok';
                if ($printerResponse->successful()) {
                    $statusData = $printerResponse->json();

                    if (isset($statusData['paper_empty']) && $statusData['paper_empty'] == true) {
                        $paperStatus = 'empty';
                    } elseif (isset($statusData['paper_low']) && $statusData['paper_low'] == true) {
                        $paperStatus = 'low';
                    }
                }

                $device->update([
                    'is_online' => true,
                    'last_seen_at' => Carbon::now(),
                    'paper_status' => $paperStatus,
                    'last_error' => null
                ]);

                $this->info("Relógio [{$device->name}] - Online - Bobina: {$paperStatus}");

                // Logout
                Http::withOptions(['verify' => false])
                    ->withHeaders(['session' => $sessionToken])
                    ->post("{$baseUrl}/logout.fcgi");
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
