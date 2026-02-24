<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Employee;
use App\Models\PunchLog;
use App\Models\CommandQueue;
use App\Services\DeviceCommandService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // Importação adicionada para evitar erro 500

class DeviceController extends Controller
{
    public function index()
    {
        $devices = Device::where('company_id', Auth::user()->company_id)->get();
        return view('devices.index', compact('devices'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'serial_number' => 'required|string|max:255|unique:devices',
            'ip_address' => 'nullable|ipv4',
        ]);

        Device::create([
            'company_id' => Auth::user()->company_id,
            'name' => $request->name,
            'serial_number' => $request->serial_number,
            'ip_address' => $request->ip_address,
            'username' => $request->username ?? 'admin',
            'password' => $request->password ?? 'admin'
        ]);

        return back()->with('success', 'Relógio cadastrado com sucesso!');
    }

    public function destroy(Device $device)
    {
        if ($device->company_id !== Auth::user()->company_id) {
            abort(403);
        }

        // Desvincula as batidas antigas deste relógio para preservar o histórico
        PunchLog::where('device_id', $device->id)->update(['device_id' => null]);
        
        // Remove comandos pendentes na fila
        CommandQueue::where('device_id', $device->id)->delete();

        $device->delete();

        return back()->with('success', 'Relógio excluído com sucesso!');
    }

    /**
     * Sincronização em Lote (Envia do Sistema para o Relógio)
     */
    public function syncEmployees(Device $device, DeviceCommandService $commandService)
    {
        $employees = Employee::where('company_id', $device->company_id)
            ->where('is_active', true)
            ->get();

        if ($employees->isEmpty()) {
            return back()->with('error', 'Nenhum servidor ativo encontrado para sincronizar.');
        }

        $usersPayload = [];
        foreach ($employees as $emp) {
            $cpfLimpo = preg_replace('/[^0-9]/', '', $emp->cpf ?? '');
            
            if (empty($cpfLimpo) || strlen($cpfLimpo) !== 11) {
                continue; 
            }

            $userData = [
                'name' => substr($emp->name, 0, 50),
                'cpf' => (int) $cpfLimpo,
            ];

            if (!empty($emp->registration_number)) {
                $userData['registration'] = (int) $emp->registration_number;
            }

            $usersPayload[] = $userData;
        }

        if (empty($usersPayload)) {
            return back()->with('error', 'Nenhum servidor possui um CPF válido (11 dígitos) para envio.');
        }

        $response = $commandService->syncUsersBatch($device, $usersPayload);

        if ($response !== false) {
            return back()->with('success', count($usersPayload) . ' servidores injetados no relógio ' . $device->name);
        } else {
            return back()->with('error', 'Falha ao conectar ou erro no comando. Verifique o log para detalhes.');
        }
    }

    /**
     * Importar servidores (Puxa do Relógio para o Sistema)
     */
    public function importEmployees(Device $device, DeviceCommandService $commandService)
    {
        $response = $commandService->getUsersFromDevice($device);

        // O relógio pode retornar os dados na chave 'users' ou 'values'
        if (!$response || (!isset($response['users']) && !isset($response['values']))) {
            Log::error("Resposta bruta do relógio {$device->name}:", (array)$response);
            return back()->with('error', 'O relógio não retornou nenhum usuário ou a lista está vazia.');
        }

        $users = $response['users'] ?? $response['values'] ?? [];

        if (empty($users)) {
             return back()->with('error', 'O relógio retornou uma lista vazia. Verifique se os usuários estão no menu do equipamento.');
        }

        $importados = 0;

        foreach ($users as $userDevice) {
            $documento = $userDevice['cpf'] ?? $userDevice['pis'] ?? null;
            
            if (!$documento) continue;

            $cpfLimpo = preg_replace('/[^0-9]/', '', (string)$documento);
            $cpfLimpo = str_pad($cpfLimpo, 11, '0', STR_PAD_LEFT);

            $existe = Employee::where('company_id', $device->company_id)
                ->where('cpf', $cpfLimpo)
                ->exists();

            if (!$existe) {
                Employee::create([
                    'company_id' => $device->company_id,
                    'name' => $userDevice['name'] ?? 'Servidor Importado',
                    'cpf' => $cpfLimpo,
                    'registration_number' => $userDevice['registration'] ?? null,
                    'is_active' => true,
                ]);
                $importados++;
            }
        }

        return back()->with('success', "{$importados} novos servidores foram importados com sucesso!");
    }
}