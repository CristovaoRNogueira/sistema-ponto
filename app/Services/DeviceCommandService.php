<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Employee;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeviceCommandService
{
    private function authenticate(Device $device): string
    {
        if (empty($device->ip_address)) {
            throw new \Exception("Sem IP configurado.");
        }

        $response = Http::withOptions(['verify' => false])
            ->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->timeout(10)
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

    // NOVA REGRA: $useMode671 diz se devemos forçar a Portaria 671 na URL
    public function sendCommand(Device $device, string $endpoint, array $payload = [], bool $useMode671 = true)
    {
        try {
            $session = $this->authenticate($device);
            
            $url = "https://{$device->ip_address}/{$endpoint}?session={$session}";
            if ($useMode671) {
                $url .= "&mode=671";
            }

            // Injetamos a sessão no corpo do JSON, como exige a API
            $payload['session'] = $session;

            Log::info("==== PAYLOAD ENVIADO PARA {$endpoint} ====", $payload);

            // Usamos ->asJson() para garantir que o Laravel não converta tipos de dados
            $response = Http::withOptions(['verify' => false])
                ->asJson() 
                ->timeout(30)
                ->post($url, $payload);

            if (!$response->successful()) {
                Log::error("Erro {$endpoint} IP {$device->ip_address}: " . $response->body());
                return false;
            }

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
        return $this->sendCommand($device, 'add_users.fcgi', ['users' => $usersPayload]);
    }

    /**
     * Puxa todos os usuários do relógio.
     * Enviamos exatamente como no exemplo do Postman que você mandou.
     */
    public function getUsersFromDevice(Device $device)
    {
        return $this->sendCommand($device, 'load_users.fcgi', [
            'limit' => (int) 1000, // Garantimos que é um Inteiro puro
            'offset' => (int) 0
        ], true);
    }
}