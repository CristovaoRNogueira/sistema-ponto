<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Employee;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DeviceCommandService
{
    private function authenticate(Device $device): string
    {
        if (empty($device->ip_address)) {
            throw new \Exception("Sem IP configurado.");
        }

        // Usando o mesmo formato idêntico ao do robô ponto:sync
        $response = Http::withOptions(['verify' => false])->asJson()->timeout(10)
            ->post("https://{$device->ip_address}/login.fcgi", [
                'login' => $device->username ?? 'admin',
                'password' => $device->password ?? 'admin'
            ]);

        if ($response->successful() && isset($response['session'])) {
            return $response['session'];
        }

        $erro = $response->body() ?: 'Timeout/Sem Resposta';
        throw new \Exception("Erro de Login (HTTP {$response->status()}): {$erro}");
    }

    public function sendCommand(Device $device, string $endpoint, array $payload = [], bool $useMode671 = true)
    {
        try {
            $session = $this->authenticate($device);

            $url = "https://{$device->ip_address}/{$endpoint}?session={$session}";
            if ($useMode671) {
                $url .= "&mode=671";
            }

            $finalPayload = ['session' => $session];
            if (!empty($payload)) {
                $finalPayload = array_merge($finalPayload, $payload);
            }

            Log::info("==== PAYLOAD ENVIADO PARA {$endpoint} ====", $finalPayload);

            $response = Http::withOptions(['verify' => false])->asJson()->timeout(30)
                ->post($url, $finalPayload);

            if (!$response->successful()) {
                Log::error("Erro {$endpoint} IP {$device->ip_address}: " . $response->body());
                return false;
            }

            // Se qualquer comando passar, significa que a máquina está online
            $device->update([
                'is_online' => true,
                'last_seen_at' => Carbon::now(),
                'last_error' => null
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error("FALHA DE SINCRONIZAÇÃO ({$device->name}): " . $e->getMessage());
            return false;
        }
    }

    public function sendEmployeeToDevice(Employee $employee, Device $device)
    {
        $cpfLimpo = preg_replace('/[^0-9]/', '', $employee->cpf ?? '');
        $userData = ['name' => substr($employee->name, 0, 50), 'cpf' => (int) $cpfLimpo];
        if (!empty($employee->registration_number)) {
            $userData['registration'] = (int) $employee->registration_number;
        }
        return $this->sendCommand($device, 'add_users.fcgi', ['users' => [$userData]]);
    }

    public function updateEmployeeOnDevice(Employee $employee, Device $device)
    {
        $cpfLimpo = preg_replace('/[^0-9]/', '', $employee->cpf ?? '');
        $userData = ['name' => substr($employee->name, 0, 50), 'cpf' => (int) $cpfLimpo];
        if (!empty($employee->registration_number)) {
            $userData['registration'] = (int) $employee->registration_number;
        }
        return $this->sendCommand($device, 'update_users.fcgi', ['users' => [$userData]]);
    }

    public function removeEmployeeFromDevice(Employee $employee, Device $device)
    {
        $cpfLimpo = preg_replace('/[^0-9]/', '', $employee->cpf ?? '');
        return $this->sendCommand($device, 'remove_users.fcgi', ['users' => [(int) $cpfLimpo]]);
    }

    public function syncUsersBatch(Device $device, array $usersPayload)
    {
        $chunks = array_chunk($usersPayload, 50);
        $success = true;

        foreach ($chunks as $chunk) {
            $result = $this->sendCommand($device, 'add_users.fcgi', ['users' => $chunk]);
            if ($result === false) {
                $success = false;
                Log::error("Falha ao enviar lote de servidores para o relógio " . $device->name);
                break;
            }
        }

        return $success;
    }

    public function getUsersFromDevice(Device $device, int $limit = 100, int $offset = 0)
    {
        return $this->sendCommand(
            $device,
            'load_users.fcgi',
            [
                'limit' => (int) $limit,
                'offset' => (int) $offset
            ],
            true
        );
    }

    // =================================================================
    // RADAR DE SAÚDE (Isolado e à prova de falhas)
    // =================================================================
    public function checkHealth(Device $device)
    {
        try {
            // 1. TENTA LOGAR. Se passar daqui, a máquina está viva na rede!
            $session = $this->authenticate($device);

            // JÁ MARCA COMO ONLINE IMEDIATAMENTE!
            $device->update([
                'is_online' => true,
                'last_seen_at' => Carbon::now(),
                'last_error' => null
            ]);

            // 2. Tenta pegar a bobina (Pode falhar em firmwares antigos, mas não derruba a rede)
            try {
                $response = Http::withOptions(['verify' => false])->asJson()->timeout(5)
                    ->post("https://{$device->ip_address}/get_printer_status.fcgi?session={$session}", [
                        'session' => $session
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $paperStatus = 'ok';
                    if (isset($data['paper_empty']) && $data['paper_empty'] == '1') {
                        $paperStatus = 'empty';
                    } elseif (isset($data['paper_low']) && $data['paper_low'] == '1') {
                        $paperStatus = 'low';
                    }
                    $device->update(['paper_status' => $paperStatus]);
                }
            } catch (\Exception $subE) {
                Log::warning("A máquina {$device->name} está online, mas não retornou o status do papel.");
            }

            return true;
        } catch (\Exception $e) {
            // Se falhou o login (Timeout), aí sim a máquina caiu
            $device->update([
                'is_online' => false,
                'last_error' => substr($e->getMessage(), 0, 255)
            ]);
            return false;
        }
    }
}
