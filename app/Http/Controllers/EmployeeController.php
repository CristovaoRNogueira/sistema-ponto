<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Shift;
use App\Models\Device;
use App\Services\DeviceCommandService;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly DeviceCommandService $commandService
    ) {}

    // Lista todos os servidores da empresa
    public function index()
    {
        $employees = Employee::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->paginate(15); // Paginação de 15 em 15

        return view('employees.index', compact('employees'));
    }

    // Abre a tela do formulário de cadastro (Abas)
    public function create()
    {
        $companyId = Auth::user()->company_id;
        $departments = Department::where('company_id', $companyId)->get();
        $shifts = Shift::where('company_id', $companyId)->get();
        $devices = Device::where('company_id', $companyId)->get();

        return view('employees.create', compact('departments', 'shifts', 'devices'));
    }

    // Processa o salvamento do formulário e envia pro Relógio
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'cpf' => 'nullable|string|max:14',
            'pis' => 'required|string|max:20',
            'device_id' => 'required|exists:devices,id'
        ]);

        $companyId = Auth::user()->company_id;

        $employee = new Employee();
        $employee->forceFill([
            'company_id' => $companyId,
            'name' => $request->name,
            'cpf' => $request->cpf,
            'pis' => $request->pis,
            'registration_number' => $request->registration_number,
            'department_id' => $request->department_id,
            'shift_id' => $request->shift_id,
            'mobile_access' => $request->has('mobile_access'),
            'is_active' => true,
        ])->save();

        $device = Device::where('company_id', $companyId)->findOrFail($request->device_id);
        $this->commandService->sendEmployeeToDevice($employee, $device);

        return redirect()->route('employees.index')->with('success', 'Servidor registrado e enviado ao relógio com sucesso!');
    }
}