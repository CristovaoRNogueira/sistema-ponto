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

        $response = Http::withOptions([
                'verify' => false, 
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
            
            // =========================================================
            // AQUI ESTÁ A MUDANÇA: A URL com o session e o mode=671
            // =========================================================
            $url = "https://{$device->ip_address}/{$endpoint}?session={$session}&mode=671";

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

    /**
     * Envia um funcionário individual para o relógio
     */
    public function sendEmployeeToDevice(Employee $employee, Device $device)
    {
        $cpfLimpo = preg_replace('/[^0-9]/', '', $employee->cpf ?? '');
        
        $userData = [
            'name' => substr($employee->name, 0, 50),
            'cpf' => (int) $cpfLimpo, // Transformado em Inteiro
        ];

        if (!empty($employee->registration_number)) {
            $userData['registration'] = (int) $employee->registration_number; // Transformado em Inteiro
        }

        return $this->sendCommand($device, 'add_users.fcgi', [
            'users' => [$userData]
        ]);
    }

    /**
     * Atualiza um funcionário individual no relógio
     */
    public function updateEmployeeOnDevice(Employee $employee, Device $device)
    {
        $cpfLimpo = preg_replace('/[^0-9]/', '', $employee->cpf ?? '');

        $userData = [
            'name' => substr($employee->name, 0, 50),
            'cpf' => (int) $cpfLimpo, // Transformado em Inteiro
        ];

        if (!empty($employee->registration_number)) {
            $userData['registration'] = (int) $employee->registration_number; // Transformado em Inteiro
        }

        return $this->sendCommand($device, 'update_users.fcgi', [
            'users' => [$userData]
        ]);
    }

    /**
     * Remove um funcionário do relógio
     */
    public function removeEmployeeFromDevice(Employee $employee, Device $device)
    {
        $cpfLimpo = preg_replace('/[^0-9]/', '', $employee->cpf ?? '');
        
        return $this->sendCommand($device, 'remove_users.fcgi', [
            'users' => [(int) $cpfLimpo]
        ]);
    }

    /**
     * Sincronização em Lote (Botão azul de Sincronizar)
     */
    public function syncUsersBatch(Device $device, array $usersPayload)
    {
        return $this->sendCommand($device, 'add_users.fcgi', ['users' => $usersPayload]);
    }
}