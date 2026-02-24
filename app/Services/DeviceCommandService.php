<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Employee;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeviceCommandService
{
    /**
     * Faz o login no equipamento e retorna o Token de Sessão
     */
    private function authenticate(Device $device): string
    {
        if (empty($device->ip_address)) {
            throw new \Exception("Sem IP configurado.");
        }

        // Força bruta para ignorar SSL e Cabeçalhos estritos
        $response = Http::withOptions([
                'verify' => false, // Ignora o erro 60 do SSL
            ])
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
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

    /**
     * Envia qualquer comando para o relógio já autenticado
     */
    public function sendCommand(Device $device, string $endpoint, array $payload = [])
    {
        try {
            $session = $this->authenticate($device);
            $url = "https://{$device->ip_address}/{$endpoint}?session={$session}";

            // A MÁGICA AQUI: Vai gravar no log o que estamos tentando enviar
            Log::info("==== PAYLOAD ENVIADO PARA {$endpoint} ====", $payload);

            $response = Http::withOptions([
                    'verify' => false, 
                ])
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->timeout(15)
                ->post($url, $payload);

            if (!$response->successful()) {
                Log::error("Erro no comando {$endpoint} para {$device->ip_address}: " . $response->body());
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
        $pisLimpo = preg_replace('/[^0-9]/', '', (string)$employee->pis);
        
        if (strlen($pisLimpo) !== 11) {
            $pisLimpo = '00000000000';
        }

        return $this->sendCommand($device, 'add_users.fcgi', [
            'users' => [[
                'admin' => false,
                'name' => substr($employee->name, 0, 50),
                'pis' => $pisLimpo,
                'cpf' => $cpfLimpo,
                'registration' => (string) $employee->registration_number,
            ]]
        ]);
    }

    public function updateEmployeeOnDevice(Employee $employee, Device $device)
    {
        $cpfLimpo = preg_replace('/[^0-9]/', '', $employee->cpf ?? '');
        $pisLimpo = preg_replace('/[^0-9]/', '', (string)$employee->pis);
        
        if (strlen($pisLimpo) !== 11) {
            $pisLimpo = '00000000000';
        }

        return $this->sendCommand($device, 'update_users.fcgi', [
            'users' => [[
                'admin' => false,
                'name' => substr($employee->name, 0, 50),
                'pis' => $pisLimpo,
                'cpf' => $cpfLimpo,
                'registration' => (string) $employee->registration_number,
            ]]
        ]);
    }

    public function removeEmployeeFromDevice(Employee $employee, Device $device)
    {
        return $this->sendCommand($device, 'remove_users.fcgi', ['users' => [(int) $employee->pis]]);
    }

    public function syncUsersBatch(Device $device, array $usersPayload)
    {
        return $this->sendCommand($device, 'add_users.fcgi', ['users' => $usersPayload]);
    }
}