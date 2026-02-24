<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Employee;
use App\Models\CommandQueue;

class DeviceCommandService
{
    /**
     * Envia a ordem para cadastrar um funcionário no relógio físico.
     * Referência do Postman: /add_users.fcgi
     */
    public function sendEmployeeToDevice(Employee $employee, Device $device): CommandQueue
    {
        // Estrutura EXATA exigida pelo add_users.fcgi na coleção do Postman
        $payload = [
            'users' => [
                [
                    'admin' => false,
                    'name' => substr($employee->name, 0, 50),
                    'pis' => (string) $employee->pis,
                    'cpf' => $employee->cpf ? (string) $employee->cpf : '', // Suporte a Portaria 671
                    'registration' => (string) $employee->registration_number,
                    // 'password' => '12345', // Opcional: pode definir uma senha padrão se quiser
                    'templates' => [] // Biometrias (vazio no primeiro cadastro)
                ]
            ]
        ];

        return CommandQueue::create([
            'device_id' => $device->id,
            'command_type' => 'add_users.fcgi', // Usando o endpoint oficial
            'payload' => $payload,
            'status' => 'pending'
        ]);
    }

    /**
     * Envia a ordem para atualizar um usuário existente.
     * Referência do Postman: /update_users.fcgi
     */
    public function updateEmployeeOnDevice(Employee $employee, Device $device): CommandQueue
    {
        $payload = [
            'users' => [
                [
                    'admin' => false,
                    'name' => substr($employee->name, 0, 50),
                    'pis' => (string) $employee->pis,
                    'cpf' => $employee->cpf ? (string) $employee->cpf : '',
                    'registration' => (string) $employee->registration_number,
                ]
            ]
        ];

        return CommandQueue::create([
            'device_id' => $device->id,
            'command_type' => 'update_users.fcgi',
            'payload' => $payload,
            'status' => 'pending'
        ]);
    }

    /**
     * Envia a ordem para DELETAR um usuário do relógio físico.
     * Referência do Postman: /remove_users.fcgi
     */
    public function removeEmployeeFromDevice(Employee $employee, Device $device): CommandQueue
    {
        $payload = [
            // A API de remoção da Control iD pede um array simples com os IDs (PIS/CPF)
            'users' => [
                (int) $employee->pis
            ]
        ];

        return CommandQueue::create([
            'device_id' => $device->id,
            'command_type' => 'remove_users.fcgi',
            'payload' => $payload,
            'status' => 'pending'
        ]);
    }
}