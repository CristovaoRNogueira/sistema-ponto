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
use App\Models\DepartmentShiftException;
use App\Services\DeviceCommandService;
use App\Services\TimeCalculationService;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AdminController extends Controller
{
    public function __construct(
        private readonly DeviceCommandService $commandService,
        private readonly DashboardService $dashboardService
    ) {}

    // A NOVA FUNÇÃO INDEX (DASHBOARD INTELIGENTE COM NÍVEIS DE ACESSO)
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;

        // 1. Captura os filtros de período e departamento
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);
        $departmentId = $request->input('department_id');
        $filterDate = $request->input('filter_date');

        // 2. Trava de Segurança Enterprise (Gestor Setorial)
        if (!Auth::user()->isAdmin() && Auth::user()->isOperator()) {
            $departmentId = Auth::user()->department_id;
        }

        // 3. Define Datas Limite (CRUCIAL: Definir antes de usar nos serviços)
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // 4. Busca TODAS as Lotações (Pais e Filhas) para o filtro
        $allDepartmentsQuery = Department::where('company_id', $companyId)
            ->with('parent') // Carrega o pai para exibir a hierarquia "Secretaria > Setor"
            ->orderBy('name');

        if (!Auth::user()->isAdmin() && Auth::user()->isOperator()) {
            $allDepartmentsQuery->where('id', Auth::user()->department_id);
        }
        $allDepartments = $allDepartmentsQuery->get();

        // 5. Gera dados do Calendário Operacional
        $calendarData = $this->dashboardService->getOperationalCalendarData($companyId, $month, $year, $departmentId);

        // 6. Estatísticas e Gráficos
        $deptDistribution = $this->dashboardService->getEmployeesByDepartment($companyId, $departmentId);
        $chartLabels = $deptDistribution->map(fn($d) => $d->department->name ?? 'Sem Setor')->toArray();
        $chartData = $deptDistribution->map(fn($d) => $d->total)->toArray();
        $totalEmployees = array_sum($chartData);

        // 7. Feed de Batidas Recentes
        $punches = PunchLog::with(['employee', 'device'])
            ->whereHas('employee', function ($q) use ($companyId, $departmentId) {
                $q->where('company_id', $companyId);
                if ($departmentId) $q->where('department_id', $departmentId);
            })
            ->orderBy('punch_time', 'desc')
            ->take(8)
            ->get();

        // 8. Rankings e Listas (Agora usando $startDate corretamente definida)
        $absences = $this->dashboardService->getActiveAbsences($companyId, $startDate, $endDate, $departmentId);
        $rankings = $this->dashboardService->getConsolidatedRankings($companyId, $startDate, $endDate, $departmentId, $filterDate);

        return view('dashboard', compact(
            'month',
            'year',
            'departmentId',
            'filterDate',
            'allDepartments', // Variável nova com todos os departamentos
            'calendarData',   // Dados do calendário
            'totalEmployees',
            'chartLabels',
            'chartData',
            'punches',
            'absences',
            'rankings'
        ));
    }

    // --- CADASTRO E GERENCIAMENTO DE SERVIDORES ---

    public function storeEmployee(Request $request)
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
            'is_active' => true,
        ])->save();

        $device = Device::where('company_id', $companyId)->findOrFail($request->device_id);
        $this->commandService->sendEmployeeToDevice($employee, $device);

        return redirect()->back()->with('success', 'Servidor registrado e enviado ao relógio!');
    }

    public function syncEmployeeToDevice(Request $request, Employee $employee)
    {
        $request->validate(['device_id' => 'required|exists:devices,id']);

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

    public function destroyAbsence(Absence $absence)
    {
        if ($absence->employee->company_id !== Auth::user()->company_id) {
            abort(403, 'Acesso negado.');
        }

        $absence->delete();
        Cache::flush();

        return redirect()->back()->with('success', 'Atestado/Ausência removido com sucesso! O espelho e o saldo mensal foram recalculados.');
    }

    public function destroyPunchLog(PunchLog $punchLog)
    {
        if ($punchLog->employee->company_id !== Auth::user()->company_id) {
            abort(403, 'Acesso negado.');
        }

        $punchLog->delete();
        \Illuminate\Support\Facades\Cache::flush();

        return redirect()->back()->with('success', 'Batida manual excluída com sucesso! O espelho foi recalculado.');
    }

    public function destroyDepartmentException(DepartmentShiftException $exception)
    {
        if ($exception->department->company_id !== Auth::user()->company_id) {
            abort(403, 'Acesso negado.');
        }

        $exception->delete();
        \Illuminate\Support\Facades\Cache::flush();

        return redirect()->back()->with('success', 'Recesso / Exceção do departamento excluída! Os saldos foram restaurados.');
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

    public function storeDepartmentException(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'exception_date' => 'required|date',
            'type' => 'required|in:partial,day_off',
            'daily_work_minutes' => 'nullable|integer',
            'observation' => 'required|string',
        ]);

        if (!Auth::user()->isAdmin() && Auth::user()->department_id != $request->department_id) {
            abort(403, 'Você só pode gerenciar o seu próprio departamento.');
        }

        DepartmentShiftException::create([
            'department_id' => $request->department_id,
            'exception_date' => $request->exception_date,
            'type' => $request->type,
            'daily_work_minutes' => $request->type === 'day_off' ? 0 : ($request->daily_work_minutes ?? 0),
            'observation' => $request->observation,
        ]);

        Cache::flush();

        return redirect()->back()->with('success', 'Exceção / Recesso aplicado ao departamento com sucesso!');
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

        $absences = Absence::where('employee_id', $employee->id)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($sub) use ($startDate, $endDate) {
                        $sub->where('start_date', '<=', $startDate)->where('end_date', '>=', $endDate);
                    });
            })->orderBy('start_date', 'desc')->get();

        $manualPunches = PunchLog::where('employee_id', $employee->id)
            ->whereBetween('punch_time', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->where('is_manual', true)
            ->orderBy('punch_time', 'desc')
            ->get();

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

        return view('timesheet.report', compact('employee', 'report', 'totalsFormatted', 'period', 'absences', 'manualPunches'));
    }

    private function parseMinutes(string $hhmm): int
    {
        $parts = explode(':', $hhmm);
        if (count($parts) !== 2) return 0;
        return ((int)$parts[0] * 60) + (int)$parts[1];
    }

    private function formatMinutes(int $totalMinutes): string
    {
        return sprintf('%02d:%02d', floor($totalMinutes / 60), $totalMinutes % 60);
    }

    public function exportMonthlyClosing(Request $request, TimeCalculationService $calcService)
    {
        $companyId = Auth::user()->company_id;
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $limitDate = $endDate->isFuture() ? Carbon::today() : clone $endDate;

        $query = Employee::where('company_id', $companyId)
            ->where('is_active', true)
            ->with(['department.shift', 'department.parent.shift', 'shift'])
            ->orderBy('name');

        if (!Auth::user()->isAdmin() && Auth::user()->isOperator()) {
            $query->where('department_id', Auth::user()->department_id);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        $employees = $query->get();

        $fileName = "fechamento_ponto_{$month}_{$year}.csv";

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Matricula', 'Nome do Servidor', 'CPF', 'Secretaria/Lotacao', 'Dias Trabalhados', 'Faltas Integrais', 'Dias de Atraso', 'Carga Mensal Exigida', 'Total Trabalhado', 'Saldo Liquido (Mes)'];

        $callback = function () use ($employees, $startDate, $limitDate, $columns, $calcService) {
            $file = fopen('php://output', 'w');
            fputs($file, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
            fputcsv($file, $columns, ';');

            if ($limitDate->lt($startDate)) {
                fclose($file);
                return;
            }

            foreach ($employees as $emp) {
                $diasTrabalhados = 0;
                $faltasIntegrais = 0;
                $diasAtraso = 0;
                $cargaMensalMin = 0;
                $totalTrabalhadoMin = 0;

                $effectiveShift = $emp->shift ?? $emp->department?->shift ?? $emp->department?->parent?->shift;
                $tolerance = $effectiveShift ? $effectiveShift->tolerance_minutes : 0;

                for ($date = $startDate->copy(); $date->lte($limitDate); $date->addDay()) {
                    $daily = $calcService->calculateDailyTimesheet($emp, $date->format('Y-m-d'));

                    $workedMin = $daily['worked_minutes'] ?? 0;
                    $expectedMin = $daily['expected_minutes'] ?? 0;
                    $balanceMin = $daily['balance_minutes'] ?? 0;
                    $status = $daily['status'];

                    if ($workedMin > 0) {
                        $diasTrabalhados++;
                    }

                    $cargaMensalMin += $expectedMin;
                    $totalTrabalhadoMin += $workedMin;

                    $isFaltaIntegral = false;
                    if ($status === 'absence') {
                        $isFaltaIntegral = true;
                    } elseif ($status === 'delay' && $daily['worked_formatted'] === '00:00' && !$daily['is_weekend'] && $status !== 'holiday' && $status !== 'justified') {
                        $isFaltaIntegral = true;
                    }

                    if ($isFaltaIntegral) {
                        $faltasIntegrais++;
                    } else {
                        if ($balanceMin < 0 && abs($balanceMin) > $tolerance && in_array($status, ['delay', 'incomplete', 'divergent'])) {
                            $diasAtraso++;
                        }
                    }
                }

                $saldoLiquido = $totalTrabalhadoMin - $cargaMensalMin;

                $formatMin = fn($min) => sprintf('%02d:%02d', floor($min / 60), $min % 60);
                $formatSaldo = fn($min) => ($min < 0 ? '-' : '+') . sprintf('%02d:%02d', abs(intdiv($min, 60)), abs($min % 60));

                $row = [
                    $emp->registration_number ?? 'S/N',
                    $emp->name,
                    $emp->cpf,
                    $emp->department->name ?? 'Sem Lotacao',
                    $diasTrabalhados,
                    $faltasIntegrais,
                    $diasAtraso,
                    $formatMin($cargaMensalMin),
                    $formatMin($totalTrabalhadoMin),
                    $formatSaldo($saldoLiquido)
                ];
                fputcsv($file, $row, ';');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
