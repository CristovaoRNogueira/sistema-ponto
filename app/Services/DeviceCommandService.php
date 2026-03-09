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
    // RADAR DE SAÚDE DEFINITIVO (Mapeado para o iDClass da Prefeitura)
    // =================================================================
    public function checkHealth(Device $device)
    {
        try {
            // 1. TENTA LOGAR (Se passar, a rede está viva!)
            $session = $this->authenticate($device);

            $paperStatus = 'ok';
            $avisoExtra = null;

            // 2. CHAMA O COMANDO EXATO QUE O SEU RELÓGIO SUPORTA
            $response = Http::withOptions(['verify' => false])->asJson()->timeout(10)
                ->post("https://{$device->ip_address}/get_system_information.fcgi?session={$session}", [
                    'session' => $session
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // 3. LÊ AS VARIÁVEIS EXATAS DO SEU FIRMWARE (Com base no Raio-X)
                $paperOk = $data['paper_ok'] ?? true;
                $isLow = isset($data['low_paper']) && $data['low_paper'] === true;

                if ($paperOk === false) {
                    $paperStatus = 'empty';
                    $avisoExtra = "Bobina de papel vazia ou tampa aberta.";
                } elseif ($isLow) {
                    $paperStatus = 'low';
                    $avisoExtra = "Pouco papel restante.";
                }
            }

            // 4. ATUALIZA O BANCO DE DADOS
            $device->update([
                'is_online' => true,
                'last_seen_at' => \Carbon\Carbon::now(),
                'paper_status' => $paperStatus,
                'last_error' => $avisoExtra
            ]);

            return true;
        } catch (\Exception $e) {
            // Falha grave: Desligado da tomada ou VPN caiu
            $device->update([
                'is_online' => false,
                'last_error' => substr($e->getMessage(), 0, 255)
            ]);
            return false;
        }
    }
}
