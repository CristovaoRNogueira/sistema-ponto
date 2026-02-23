<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PunchLog;
use App\Models\Device;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Shift;
use App\Models\Absence;
use App\Models\ShiftException;
use App\Services\DeviceCommandService;
use App\Services\TimeCalculationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function __construct(
        private readonly DeviceCommandService $commandService
    ) {}

    public function index()
    {
        $companyId = Auth::user()->company_id;

        // Traz as últimas batidas
        $punches = PunchLog::with(['employee', 'device'])
                    ->whereHas('employee', function($q) use ($companyId) {
                        $q->where('company_id', $companyId);
                    })
                    ->orderBy('punch_time', 'desc') 
                    ->take(20)
                    ->get();
        
        $totalEmployees = Employee::where('company_id', $companyId)->where('is_active', true)->count();
        $offlineDevices = 0; 
        
        // Simulação de Gráfico
        $chartLabels = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex'];
        $chartPresences = [42, 45, 40, 48, 41]; 
        $chartAbsences = [3, 0, 5, 1, 4];

        return view('dashboard', compact(
            'punches', 'totalEmployees', 'offlineDevices', 
            'chartLabels', 'chartPresences', 'chartAbsences'
        ));
    }

    public function storeEmployee(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'cpf' => 'nullable|string|max:14',
            'pis' => 'required|string|max:20',
            'device_id' => 'required|exists:devices,id'
        ]);

        $companyId = Auth::user()->company_id;

        // Usa forceFill para gravar o company_id sem precisar alterar o Model agora
        $employee = new Employee();
        $employee->forceFill([
            'company_id' => $companyId,
            'name' => $request->name,
            'cpf' => $request->cpf,
            'pis' => $request->pis,
            'registration_number' => $request->registration_number,
            'department_id' => $request->department_id,
            'shift_id' => $request->shift_id,
            'is_active' => true,
        ])->save();

        // Pega o relógio garantindo que pertence à mesma empresa
        $device = Device::where('company_id', $companyId)->findOrFail($request->device_id);

        $this->commandService->sendEmployeeToDevice($employee, $device);

        return redirect()->back()->with('success', 'Servidor registrado e enviado ao relógio!');
    }

    public function syncEmployeeToDevice(Request $request, Employee $employee)
    {
        $request->validate(['device_id' => 'required|exists:devices,id']);
        
        // Proteção Multiempresa
        if ($employee->company_id !== Auth::user()->company_id) {
            abort(403, 'Acesso negado.');
        }

        $device = Device::where('company_id', Auth::user()->company_id)->findOrFail($request->device_id);
        $this->commandService->sendEmployeeToDevice($employee, $device);

        return redirect()->back()->with('success', 'Comando reenviado para o relógio.');
    }

    public function storeAbsence(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
        ]);

        // Proteção
        $employee = Employee::where('company_id', Auth::user()->company_id)->findOrFail($request->employee_id);

        Absence::create([
            'employee_id' => $employee->id,
            'type' => $request->type,
            'start_date' => Carbon::parse($request->start_date)->startOfDay(),
            'end_date' => Carbon::parse($request->end_date)->endOfDay(),
            'waive_hours' => true,
            'reason' => $request->reason,
        ]);

        return redirect()->back()->with('success', 'Atestado/Justificativa registrado.');
    }

    public function storeShiftSwap(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'exception_date' => 'required|date',
            'type' => 'required|in:swap,extra,day_off'
        ]);

        $employee = Employee::where('company_id', Auth::user()->company_id)->findOrFail($request->employee_id);

        ShiftException::create([
            'employee_id' => $employee->id,
            'exception_date' => $request->exception_date,
            'type' => $request->type,
            'in_1' => $request->in_1,
            'out_1' => $request->out_1,
            'in_2' => $request->in_2,
            'out_2' => $request->out_2,
            'daily_work_minutes' => $request->daily_work_minutes ?? 0,
            'observation' => $request->observation,
        ]);

        return redirect()->back()->with('success', 'Exceção configurada com sucesso.');
    }

    public function reportTimesheet(Request $request, Employee $employee, TimeCalculationService $calcService)
    {
        if ($employee->company_id !== Auth::user()->company_id) {
            abort(403, 'Acesso negado.');
        }

        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        if ($endDate->isFuture()) {
            $endDate = Carbon::now();
        }

        $report = [];
        $totals = ['expected_minutes' => 0, 'worked_minutes' => 0, 'overtime_minutes' => 0, 'delay_minutes' => 0];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dateString = $date->format('Y-m-d');
            $daily = $calcService->calculateDailyTimesheet($employee, $dateString);
            $report[] = $daily;

            if ($daily['status'] !== 'divergent') {
                $totals['worked_minutes'] += $this->parseMinutes($daily['worked_formatted']);
                if ($daily['balance_minutes'] > 0) {
                    $totals['overtime_minutes'] += $daily['balance_minutes'];
                } elseif ($daily['balance_minutes'] < 0) {
                    $totals['delay_minutes'] += abs($daily['balance_minutes']); 
                }
            }
        }

        $totalsFormatted = [
            'worked' => $this->formatMinutes($totals['worked_minutes']),
            'overtime' => $this->formatMinutes($totals['overtime_minutes']),
            'delay' => $this->formatMinutes($totals['delay_minutes']),
        ];

        Carbon::setLocale('pt_BR');
        $period = $startDate->translatedFormat('F / Y');

        return view('timesheet.report', compact('employee', 'report', 'totalsFormatted', 'period'));
    }

    private function parseMinutes(string $hhmm): int {
        $parts = explode(':', $hhmm);
        if (count($parts) !== 2) return 0;
        return ((int)$parts[0] * 60) + (int)$parts[1];
    }

    private function formatMinutes(int $totalMinutes): string {
        return sprintf('%02d:%02d', floor($totalMinutes / 60), $totalMinutes % 60);
    }
}