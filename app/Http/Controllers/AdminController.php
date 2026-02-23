<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PunchLog;
use App\Models\Device;
use App\Models\Employee;
use App\Models\CommandQueue;

class AdminController extends Controller
{
    // Renderiza a tela principal (Dashboard)
    public function index()
    {
        // Pega as últimas 50 batidas registradas, trazendo junto os dados do Servidor e do Relógio
        $punches = PunchLog::with(['employee', 'device'])
                    ->orderBy('punch_time', 'desc')
                    ->take(50)
                    ->get();
        
        // Pega todos os relógios cadastrados para o Select do formulário
        $devices = Device::all();

        return view('dashboard', compact('punches', 'devices'));
    }

    // Processa o formulário de cadastro de novo servidor
    public function storeEmployee(Request $request)
    {
        // Valida os dados enviados pelo RH
        $request->validate([
            'name' => 'required|string|max:255',
            'pis' => 'required|string|max:20|unique:employees,pis',
            'registration_number' => 'nullable|string',
            'device_id' => 'required|exists:devices,id' // Qual relógio vai receber este servidor?
        ]);

        // 1. Salva o servidor no banco de dados do sistema
        $employee = Employee::create([
            'name' => $request->name,
            'pis' => $request->pis,
            'registration_number' => $request->registration_number,
        ]);

        // 2. Monta o comando exato que o relógio iDClass exige para cadastrar alguém
        $payloadiDClass = [
            'object' => 'users',
            'values' => [
                [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'registration' => $employee->registration_number ?? (string)$employee->id
                ]
            ]
        ];

        // 3. Coloca a ordem na Fila de Comandos
        CommandQueue::create([
            'device_id' => $request->device_id,
            'endpoint'  => 'create_objects',
            'payload'   => $payloadiDClass
        ]);

        return redirect()->back()->with('success', 'Servidor cadastrado no sistema! O comando foi enviado para a fila do relógio e será sincronizado em breve.');
    }
}