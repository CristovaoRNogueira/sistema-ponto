<?php
namespace App\Http\Controllers;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;
use App\Models\CommandQueue;

class DeviceController extends Controller {
    public function index() {
        $devices = Device::where('company_id', Auth::user()->company_id)->get();
        return view('devices.index', compact('devices'));
    }
    public function store(Request $request) {
        $request->validate(['name' => 'required|string', 'serial_number' => 'required|string|unique:devices,serial_number']);
        Device::create(['name' => $request->name, 'serial_number' => $request->serial_number, 'company_id' => Auth::user()->company_id]);
        return back()->with('success', 'Relógio adicionado! Configure a URL no equipamento.');
    }
    public function destroy(Device $device) {
        if($device->company_id === Auth::user()->company_id) $device->delete();
        return back()->with('success', 'Relógio removido!');
    }

    public function syncEmployees(Device $device)
    {
        // Pega todos os servidores ativos da empresa deste relógio
        $employees = Employee::where('company_id', $device->company_id)
            ->where('is_active', true)
            ->get();

        if ($employees->isEmpty()) {
            return back()->with('error', 'Nenhum servidor ativo encontrado para sincronizar.');
        }

        $usersPayload = [];

        foreach ($employees as $emp) {
            $usersPayload[] = [
                'name' => current(explode(' ', $emp->name)), // Control iD sugere nomes curtos no display
                'pis' => $emp->pis,
                'registration' => (string) $emp->registration_number,
                // O relógio precisa de um ID ou CPF. Usamos PIS como ID principal caso CPF seja nulo.
                'cpf' => $emp->cpf ?? preg_replace('/[^0-9]/', '', $emp->pis), 
            ];
        }

        // Dividimos em lotes de 50 servidores por comando para não travar a memória do relógio
        $chunks = array_chunk($usersPayload, 50);

        foreach ($chunks as $chunk) {
            CommandQueue::create([
                'device_id' => $device->id,
                'command_type' => 'add_users.fcgi',
                'payload' => ['users' => $chunk],
                'status' => 'pending'
            ]);
        }

        return back()->with('success', count($employees) . ' servidores foram enviados para a fila do relógio ' . $device->name);
    }
}