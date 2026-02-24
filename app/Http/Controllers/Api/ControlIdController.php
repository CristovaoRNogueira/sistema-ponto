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
        $payload = $request->json()->all();
        
        // 1. Captura o Serial Number do Relógio (NSR)
        $serialNumber = $request->header('device_id') 
                        ?? $request->header('User-Agent') 
                        ?? $request->query('device_id');
        
        if ($serialNumber) {
            $serialNumber = trim(str_replace('iDClass/', '', $serialNumber));
        }

        if (!$serialNumber) {
            return response()->json(['error' => 'Device ID missing'], 400);
        }

        // 2. Busca o Relógio Real. Se não existir, ignora para não poluir o banco.
        $device = Device::where('serial_number', $serialNumber)->first();
        if (!$device) {
            Log::error("Relógio não cadastrado tentou conectar: NSR {$serialNumber}");
            return response()->json(['request' => ['cmds' => []]], 200);
        }

        // 3. TRATAR FEEDBACK DE SUCESSO DO RELÓGIO
        if (isset($payload['responses'])) {
            foreach ($payload['responses'] as $res) {
                $command = CommandQueue::find($res['id']);
                if ($command) {
                    $status = (isset($res['response']['status']) && $res['response']['status'] == 200) ? 'success' : 'error';
                    $command->update(['status' => $status]);
                }
            }
        }

        // 4. PROCESSAR BATIDAS DE PONTO (TICK LOGS)
        if (isset($payload['object_changes'])) {
            foreach ($payload['object_changes'] as $change) {
                if ($change['object'] === 'tick_logs' && $change['type'] === 'inserted') {
                    foreach ($change['values'] as $log) {
                        if (isset($log['pis']) && isset($log['time'])) {
                            
                            // A MÁGICA AQUI: Converte do relógio pro horário de Carinhanha/Bahia!
                            $punchTime = Carbon::createFromTimestamp($log['time'], config('app.timezone', 'America/Bahia'))->format('Y-m-d H:i:s');
                            
                            // Limpa o PIS de zeros extras
                            $pisLimpo = ltrim(trim($log['pis']), '0');
                            
                            // Encontra o SEU servidor exato, sem criar fantasmas!
                            $employee = Employee::where('company_id', $device->company_id)
                                ->where(function($q) use ($pisLimpo) {
                                    $q->where('pis', $pisLimpo)
                                      ->orWhere('pis', str_pad($pisLimpo, 11, '0', STR_PAD_LEFT));
                                })->first();

                            if ($employee) {
                                // Grava a batida perfeitamente vinculada ao Cristovão
                                PunchLog::firstOrCreate([
                                    'employee_id' => $employee->id,
                                    'device_id'   => $device->id,
                                    'punch_time'  => $punchTime
                                ], [
                                    'nsr' => $log['nsr'] ?? null
                                ]);
                            } else {
                                Log::warning("Batida ignorada: PIS {$log['pis']} não pertence a nenhum servidor.");
                            }
                        }
                    }
                }
            }
        }

        // 5. ENVIAR PRÓXIMAS ORDENS PARA O RELÓGIO
        $response = ['request' => ['cmds' => []]];

        $pendingCommands = CommandQueue::where('device_id', $device->id)
                                ->where('status', 'pending')
                                ->orderBy('created_at', 'asc')
                                ->get();

        foreach ($pendingCommands as $command) {
            $response['request']['cmds'][] = [
                'id' => (int) $command->id,
                'cmd' => 'request',
                'params' => [
                    'endpoint' => $command->command_type, 
                    'body' => $command->payload
                ]
            ];
            $command->update(['status' => 'waiting']); 
        }

        return response()->json($response, 200);
    }
}