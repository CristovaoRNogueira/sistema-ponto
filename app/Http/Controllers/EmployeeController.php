<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Shift;
use App\Models\Device;
use App\Services\DeviceCommandService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly DeviceCommandService $commandService
    ) {}

    // Lista todos os servidores e aplica filtro do Menu Lateral (Organograma)
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $query = Employee::where('company_id', $companyId);

        // SE O RH CLICOU NO MENU LATERAL PARA FILTRAR...
        if ($request->filled('department_id')) {
            $deptId = $request->department_id;
            $dept = Department::find($deptId);
            
            // Se clicou na "Secretaria" (Pai), puxamos os servidores dela E dos departamentos filhos
            if ($dept && $dept->parent_id === null) {
                $childIds = $dept->children()->pluck('id')->toArray();
                $allIds = array_merge([$deptId], $childIds);
                $query->whereIn('department_id', $allIds);
            } else {
                // Se clicou num departamento específico (ex: Hospital), puxa apenas de lá
                $query->where('department_id', $deptId);
            }
        }

        $employees = $query->orderBy('name')->paginate(15);
        
        // Puxa o Organograma para desenhar o Menu Lateral na view index
        $secretariats = Department::where('company_id', $companyId)
                                  ->whereNull('parent_id')
                                  ->with('children')
                                  ->orderBy('name')
                                  ->get();

        return view('employees.index', compact('employees', 'secretariats'));
    }

    // Abre a tela do formulário de cadastro (Abas)
    public function create()
    {
        $companyId = Auth::user()->company_id;
        
        // Puxa a hierarquia de Secretarias para o <select> com optgroup
        $secretariats = Department::where('company_id', $companyId)
                                  ->whereNull('parent_id')
                                  ->with('children')
                                  ->orderBy('name')
                                  ->get();
                                  
        $shifts = Shift::where('company_id', $companyId)->get();
        $devices = Device::where('company_id', $companyId)->get();
        $jobTitles = DB::table('job_titles')->where('company_id', $companyId)->get();
        $costCenters = DB::table('cost_centers')->where('company_id', $companyId)->get();

        return view('employees.create', compact('secretariats', 'shifts', 'devices', 'jobTitles', 'costCenters'));
    }

    // Processa o salvamento do formulário e envia pro Relógio (add_users.fcgi)
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'cpf' => 'nullable|string|max:14|unique:employees,cpf', // Verifica se o CPF já existe
            'pis' => 'required|string|max:20|unique:employees,pis', // Verifica se o PIS já existe
            'device_id' => 'required|exists:devices,id'], 
            [ 'cpf.unique' => 'Este CPF já está cadastrado no sistema.',
            'pis.unique' => 'Este PIS já está cadastrado no sistema.',
        ]);

        $companyId = Auth::user()->company_id;

        $employee = new Employee();
        $employee->forceFill([
            'company_id' => $companyId,
            'name' => $request->name,
            'cpf' => $request->cpf,
            'rg' => $request->rg,
            'pis' => $request->pis,
            'registration_number' => $request->registration_number,
            'department_id' => $request->department_id,
            'shift_id' => $request->shift_id,
            'job_title_id' => $request->job_title_id,
            'cost_center_id' => $request->cost_center_id,
            'mobile_access' => $request->has('mobile_access'),
            'app_password' => $request->app_password,
            'is_active' => true,
        ])->save();

        $device = Device::where('company_id', $companyId)->findOrFail($request->device_id);
        $this->commandService->sendEmployeeToDevice($employee, $device);

        return redirect()->route('employees.index')->with('success', 'Servidor registrado e comando enviado para a fila do relógio!');
    }

    // Abre a tela de Edição
    public function edit(Employee $employee)
    {
        // Proteção Multiempresa
        if ($employee->company_id !== Auth::user()->company_id) {
            abort(403, 'Acesso Negado.');
        }

        $companyId = Auth::user()->company_id;
        
        $secretariats = Department::where('company_id', $companyId)
                                  ->whereNull('parent_id')
                                  ->with('children')
                                  ->orderBy('name')
                                  ->get();
                                  
        $shifts = Shift::where('company_id', $companyId)->get();
        $devices = Device::where('company_id', $companyId)->get();
        $jobTitles = DB::table('job_titles')->where('company_id', $companyId)->get();
        $costCenters = DB::table('cost_centers')->where('company_id', $companyId)->get();

        return view('employees.edit', compact('employee', 'secretariats', 'shifts', 'devices', 'jobTitles', 'costCenters'));
    }

    // Processa a Atualização dos dados e envia pro Relógio (update_users.fcgi)
    public function update(Request $request, Employee $employee)
    {
        if ($employee->company_id !== Auth::user()->company_id) {
            abort(403, 'Acesso Negado.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            // O comando abaixo diz: É único, exceto para este funcionário que já é o dono do CPF
            'cpf' => 'nullable|string|max:14|unique:employees,cpf,' . $employee->id,
            'pis' => 'required|string|max:20|unique:employees,pis,' . $employee->id,
            'device_id' => 'required|exists:devices,id'
        ], [
            'cpf.unique' => 'Este CPF já está cadastrado em outro servidor.',
            'pis.unique' => 'Este PIS já está cadastrado em outro servidor.',
        ]);

        $employee->update([
            'name' => $request->name,
            'cpf' => $request->cpf,
            'rg' => $request->rg,
            'pis' => $request->pis,
            'registration_number' => $request->registration_number,
            'department_id' => $request->department_id,
            'shift_id' => $request->shift_id,
            'job_title_id' => $request->job_title_id,
            'cost_center_id' => $request->cost_center_id,
            'mobile_access' => $request->has('mobile_access'),
        ]);

        if ($request->filled('app_password')) {
            $employee->update(['app_password' => $request->app_password]);
        }

        if ($request->filled('device_id')) {
            $device = Device::where('company_id', Auth::user()->company_id)->findOrFail($request->device_id);
            $this->commandService->updateEmployeeOnDevice($employee, $device);
        }

        return redirect()->route('employees.index')->with('success', 'Servidor atualizado com sucesso!');
    }

    // Processa a Exclusão e envia comando de remoção (remove_users.fcgi)
    public function destroy(Request $request, Employee $employee)
    {
        if ($employee->company_id !== Auth::user()->company_id) {
            abort(403, 'Acesso Negado.');
        }

        if ($request->filled('device_id')) {
            $device = Device::where('company_id', Auth::user()->company_id)->findOrFail($request->device_id);
            $this->commandService->removeEmployeeFromDevice($employee, $device);
        }

        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'Servidor removido do sistema.');
    }
}