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
use App\Services\DashboardService; // <- NOVO SERVIÇO IMPORTADO
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function __construct(
        private readonly DeviceCommandService $commandService,
        private readonly DashboardService $dashboardService // <- INJETADO NO CONSTRUTOR
    ) {}

    // A NOVA FUNÇÃO INDEX (DASHBOARD INTELIGENTE)
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;

        // Captura os filtros de período e departamento
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);
        $departmentId = $request->input('department_id');

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Traz as secretarias para popular o menu <select> de filtro
        $secretariats = Department::where('company_id', $companyId)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        // 1. Gráficos Básicos (Lotação de Servidores)
        $deptDistribution = $this->dashboardService->getEmployeesByDepartment($companyId, $departmentId);
        $chartLabels = $deptDistribution->map(fn($d) => $d->department->name ?? 'Sem Setor')->toArray();
        $chartData = $deptDistribution->map(fn($d) => $d->total)->toArray();
        $totalEmployees = array_sum($chartData);

        // 2. Feed de Últimas Batidas Brutas
        $punches = PunchLog::with(['employee', 'device'])
            ->whereHas('employee', function($q) use ($companyId, $departmentId) {
                $q->where('company_id', $companyId);
                if ($departmentId) $q->where('department_id', $departmentId);
            })
            ->orderBy('punch_time', 'desc') 
            ->take(8)
            ->get();

        // 3. Atestados Ativos
        $absences = $this->dashboardService->getActiveAbsences($companyId, $startDate, $endDate, $departmentId);

        // 4. Rankings Inteligentes (Cacheado por 1 hora)
        $rankings = $this->dashboardService->getConsolidatedRankings($companyId, $startDate, $endDate, $departmentId);

        return view('dashboard', compact(
            'month', 'year', 'departmentId', 'secretariats', 
            'totalEmployees', 'chartLabels', 'chartData', 'punches',
            'absences', 'rankings'
        ));
    }

    // --- DAQUI PARA BAIXO, TUDO É O SEU CÓDIGO ORIGINAL INTACTO ---

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

    // --- GERAÇÃO DO FECHAMENTO MENSAL EM LOTE (EXCEL/CSV) ---
    public function exportMonthlyClosing(Request $request, TimeCalculationService $calcService)
    {
        $companyId = Auth::user()->company_id;
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        
        // Se o mês ainda não acabou, calcula só até o dia de hoje
        if ($endDate->isFuture()) {
            $endDate = Carbon::today();
        }

        $employees = Employee::where('company_id', $companyId)
            ->where('is_active', true)
            ->with('department')
            ->orderBy('name')
            ->get();

        $fileName = "fechamento_ponto_{$month}_{$year}.csv";
        
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        // Colunas do cabeçalho do Excel
        $columns = ['Matricula', 'Nome do Servidor', 'CPF', 'Secretaria/Lotacao', 'Dias Trabalhados', 'Dias Falta', 'Horas Extras (+)', 'Atrasos/Faltas (-)', 'Saldo Liquido (Banco)'];

        // Função de streaming (processa e baixa o ficheiro em tempo real sem sobrecarregar a memória RAM)
        $callback = function() use($employees, $startDate, $endDate, $columns, $calcService) {
            $file = fopen('php://output', 'w');
            
            // Adiciona BOM (Byte Order Mark) para o Excel abrir os acentos e "ç" do Português de forma perfeita
            fputs($file, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
            
            // Delimitador Ponto e Vírgula (;) é o padrão do Excel no Brasil/Portugal
            fputcsv($file, $columns, ';');

            foreach ($employees as $emp) {
                $diasTrabalhados = 0;
                $faltas = 0;
                $bancoMinutos = 0;
                $extrasMinutos = 0;
                $atrasosMinutos = 0;

                // Percorre os dias do mês do servidor
                for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                    $daily = $calcService->calculateDailyTimesheet($emp, $date->format('Y-m-d'));
                    
                    if ($daily['worked_formatted'] !== '00:00') {
                        $diasTrabalhados++;
                    }
                    
                    // Lógica de falta injustificada
                    if ($daily['status'] === 'absence' || ($daily['worked_formatted'] === '00:00' && !$daily['is_weekend'] && $daily['status'] !== 'holiday' && $daily['status'] !== 'justified' && $daily['status'] !== 'day_off')) {
                        $faltas++;
                    }

                    if ($daily['balance_minutes'] > 0) {
                        $extrasMinutos += $daily['balance_minutes'];
                    } elseif ($daily['balance_minutes'] < 0) {
                        $atrasosMinutos += abs($daily['balance_minutes']);
                    }
                    $bancoMinutos += $daily['balance_minutes'];
                }

                // Funções auxiliares para formatar os minutos em formato de horas "HH:MM"
                $formatMin = fn($min) => sprintf('%02d:%02d', floor($min / 60), $min % 60);
                $formatSaldo = fn($min) => ($min < 0 ? '-' : '+') . sprintf('%02d:%02d', abs(intdiv($min, 60)), abs($min % 60));

                $row = [
                    $emp->registration_number ?? 'S/N',
                    $emp->name,
                    $emp->cpf,
                    $emp->department->name ?? 'Sem Lotação',
                    $diasTrabalhados,
                    $faltas,
                    $formatMin($extrasMinutos),
                    $formatMin($atrasosMinutos),
                    $formatSaldo($bancoMinutos)
                ];
                
                fputcsv($file, $row, ';');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}