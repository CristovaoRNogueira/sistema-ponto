<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\Employee;
use App\Models\PunchLog;
use App\Models\CommandQueue;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ControlIdController extends Controller
{
    public function handlePush(Request $request)
    {
        // 1. Recebe o pacote de dados em formato JSON
        $payload = $request->json()->all();
        
        // O relógio Control iD sempre envia o seu número de série no cabeçalho (header)
        $serialNumber = $request->header('device_id'); 
        
        if (!$serialNumber) {
            Log::warning('Tentativa de conexão sem Device ID.');
            return response()->json(['error' => 'Device ID missing'], 400);
        }

        // 2. Identifica o relógio ou cadastra automaticamente se for novo
        $device = Device::firstOrCreate(
            ['serial_number' => $serialNumber],
            ['name' => 'iDClass - ' . $serialNumber]
        );

        // 3. Processa as batidas de ponto (Tick Logs)
        if (isset($payload['object_changes'])) {
            foreach ($payload['object_changes'] as $change) {
                // Verifica se a alteração é uma nova inserção de batida de ponto
                if ($change['object'] === 'tick_logs' && $change['type'] === 'inserted') {
                    
                    foreach ($change['values'] as $log) {
                        if (isset($log['pis']) && isset($log['time'])) {
                            // Converte a hora do relógio (Unix) para o formato do banco (Y-m-d H:i:s)
                            $punchTime = Carbon::createFromTimestamp($log['time'], 'America/Bahia')->toDateTimeString();
                            
                            // Busca o servidor pelo PIS (se não existir, cria um registro temporário)
                            $employee = Employee::firstOrCreate(
                                ['pis' => $log['pis']],
                                [
                                    'name' => 'Servidor (PIS: '.$log['pis'].')',
                                    'is_active' => true // Obrigatório pela nossa nova arquitetura
                                ]
                            );

                            // Salva a batida no banco
                            PunchLog::firstOrCreate([
                                'employee_id' => $employee->id,
                                'nsr'         => $log['nsr'] ?? null,
                                'device_id'   => $device->id,
                                'punch_time'  => $punchTime
                            ]);
                        }
                    }
                }
            }
        }

        // 4. Verifica a Fila de Comandos (Alinhado com a Documentação Oficial)
        $response = [
            'request' => [
                'cmds' => []
            ]
        ];

        // Busca ordens pendentes para este relógio
        $pendingCommands = CommandQueue::where('device_id', $device->id)
                                ->where('status', 'pending')
                                ->get();

        foreach ($pendingCommands as $command) {
            
            // Empacota o comando no formato exato da API
            // Agora o 'command_type' já possui o nome correto (ex: add_users.fcgi)
            $response['request']['cmds'][] = [
                'id' => $command->id,
                'cmd' => 'request',
                'params' => [
                    'endpoint' => $command->command_type, 
                    'body' => $command->payload
                ]
            ];
            
            // Marca como SUCESSO para não enviar o mesmo comando duas vezes
            $command->update(['status' => 'success']); 
        }

        // 5. Devolve o JSON de resposta (HTTP 200 OK) para o equipamento
        return response()->json($response, 200);
    }
}