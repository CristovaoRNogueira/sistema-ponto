<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\Employee;
use App\Models\PunchLog;
use App\Models\CommandQueue;
use Illuminate\Support\Facades\Log;

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
                            $punchTime = date('Y-m-d H:i:s', $log['time']);
                            
                            // Busca o servidor pelo PIS (se não existir, cria um registro temporário)
                            $employee = Employee::firstOrCreate(
                                ['pis' => $log['pis']],
                                ['name' => 'Servidor (PIS: '.$log['pis'].')']
                            );

                            // Salva a batida no banco (firstOrCreate evita duplicar a mesma batida)
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

        // 4. Verifica a Fila de Comandos (O Segredo do Enterprise Push)
        // Montamos a estrutura de resposta que o relógio espera
        $response = [
            'request' => [
                'cmds' => []
            ]
        ];

        // Busca se existe alguma ordem do RH pendente para ESTE relógio
        $pendingCommands = CommandQueue::where('device_id', $device->id)
                                ->where('is_executed', false)
                                ->get();

        foreach ($pendingCommands as $command) {
            // Empacota o comando no formato que o relógio entende
            $response['request']['cmds'][] = [
                'id' => $command->id,
                'cmd' => 'request',
                'params' => [
                    'endpoint' => $command->endpoint,
                    'body' => $command->payload
                ]
            ];
            
            // Marca como executado para não enviar o mesmo comando duas vezes
            $command->update(['is_executed' => true]); 
        }

        // 5. Devolve o JSON de resposta para o equipamento
        return response()->json($response, 200);
    }
}