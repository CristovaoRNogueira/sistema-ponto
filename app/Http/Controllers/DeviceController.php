<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Employee;
use App\Models\PunchLog;
use App\Models\CommandQueue;
use App\Services\DeviceCommandService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            'ip_address' => 'nullable|ipv4', // Valida se é um IP válido
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

        // 1. A CORREÇÃO DO ERRO 500: Desvincula as batidas antigas deste relógio (Preserva o histórico!)
        PunchLog::where('device_id', $device->id)->update(['device_id' => null]);
        
        // 2. Remove comandos pendentes na fila (caso tenha sobrado algo do sistema antigo)
        CommandQueue::where('device_id', $device->id)->delete();

        // 3. Agora sim, exclui o aparelho com segurança
        $device->delete();

        return back()->with('success', 'Relógio excluído com sucesso!');
    }

    // Sincronização em Lote (Novo Motor IP)
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
            $pisLimpo = preg_replace('/[^0-9]/', '', (string)$emp->pis);
            
            // Se o PIS não tiver exatos 11 dígitos, usamos o bypass de 11 zeros (Regra Control iD 671)
            if (strlen($pisLimpo) !== 11) {
                $pisLimpo = '00000000000';
            }

            $usersPayload[] = [
                'name' => substr($emp->name, 0, 50),
                'pis' => $pisLimpo,
                'cpf' => $cpfLimpo, // O CPF vai no seu lugar correto!
                'registration' => (string) $emp->registration_number,
            ];
        }

        // Dispara o comando direto para o IP do equipamento!
        $response = $commandService->syncUsersBatch($device, $usersPayload);

        if ($response !== false) {
            return back()->with('success', count($employees) . ' servidores injetados no relógio ' . $device->name);
        } else {
            return back()->with('error', 'Falha ao conectar. Verifique se o Endereço IP está correto e se o relógio está na mesma rede.');
        }
    }
}