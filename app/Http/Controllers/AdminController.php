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

class AdminController extends Controller
{
    // Injetamos o nosso Serviço de Comandos no Controller
    public function __construct(
        private readonly DeviceCommandService $commandService
    ) {}

    /**
     * Renderiza o ecrã principal (Dashboard do RH)
     */
    public function index()
    {
        // Pega as últimas 50 batidas registadas no sistema
        $punches = PunchLog::with(['employee', 'device'])
                    ->orderBy('punch_time', 'desc') 
                    ->take(50)
                    ->get();
        
        // Dados para preencher os formulários (Selects)
        $devices = Device::all();
        $departments = Department::all();
        $shifts = Shift::all();
        $employees = Employee::where('is_active', true)->orderBy('name')->get();

        return view('dashboard', compact('punches', 'devices', 'departments', 'shifts', 'employees'));
    }

    /**
     * Processa o formulário de registo de novo servidor
     */
    public function storeEmployee(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'cpf' => 'nullable|string|max:14|unique:employees,cpf',
            'pis' => 'required|string|max:20|unique:employees,pis',
            'registration_number' => 'nullable|string',
            'department_id' => 'nullable|exists:departments,id',
            'shift_id' => 'nullable|exists:shifts,id',
            'device_id' => 'required|exists:devices,id'
        ]);

        // 1. Guarda o servidor na base de dados central
        $employee = Employee::create([
            'name' => $request->name,
            'cpf' => $request->cpf,
            'pis' => $request->pis,
            'registration_number' => $request->registration_number,
            'department_id' => $request->department_id,
            'shift_id' => $request->shift_id,
            'is_active' => true,
        ]);

        $device = Device::findOrFail($request->device_id);

        // 2. Coloca o comando na fila para o relógio físico puxar
        $this->commandService->sendEmployeeToDevice($employee, $device);

        return redirect()->back()->with('success', 'Servidor registado no sistema! O comando foi enviado para a fila do relógio e será sincronizado em breve.');
    }

    /**
     * Ação para forçar a ressincronização de um servidor para o relógio
     */
    public function syncEmployeeToDevice(Request $request, Employee $employee)
    {
        $request->validate([
            'device_id' => 'required|exists:devices,id'
        ]);

        $device = Device::findOrFail($request->device_id);
        $this->commandService->sendEmployeeToDevice($employee, $device);

        return redirect()->back()->with('success', 'Comando de sincronização reenviado para o relógio.');
    }

    /**
     * RH regista um Atestado Médico ou Justificativa (Abona horas)
     */
    public function storeAbsence(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
        ]);

        Absence::create([
            'employee_id' => $request->employee_id,
            'type' => $request->type,
            'start_date' => Carbon::parse($request->start_date)->startOfDay(),
            'end_date' => Carbon::parse($request->end_date)->endOfDay(),
            'waive_hours' => true, // Perdoa a falta no cálculo
            'reason' => $request->reason,
        ]);

        return redirect()->back()->with('success', 'Atestado/Justificativa registado. As horas do servidor foram abonadas no período.');
    }

    /**
     * RH regista uma Troca de Plantão (Exceção) para a Saúde/Guardas
     */
    public function storeShiftSwap(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'exception_date' => 'required|date',
            'type' => 'required|in:swap,extra,day_off',
            'daily_work_minutes' => 'required_if:type,swap,extra'
        ]);

        ShiftException::create([
            'employee_id' => $request->employee_id,
            'exception_date' => $request->exception_date,
            'type' => $request->type,
            'in_1' => $request->in_1,
            'out_1' => $request->out_1,
            'in_2' => $request->in_2,
            'out_2' => $request->out_2,
            'daily_work_minutes' => $request->daily_work_minutes ?? 0,
            'observation' => $request->observation,
        ]);

        return redirect()->back()->with('success', 'Exceção de plantão configurada com sucesso para a data informada.');
    }

    /**
     * Gera o Espelho de Ponto Mensal do Servidor para o fecho da folha
     */
    public function reportTimesheet(Request $request, Employee $employee, TimeCalculationService $calcService)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Evita calcular dias do futuro se estivermos a meio do mês atual
        if ($endDate->isFuture()) {
            $endDate = Carbon::now();
        }

        $report = [];
        $totals = [
            'expected_minutes' => 0,
            'worked_minutes' => 0,
            'overtime_minutes' => 0,
            'delay_minutes' => 0,
        ];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dateString = $date->format('Y-m-d');
            
            // Calcula o dia usando a inteligência do Service
            $daily = $calcService->calculateDailyTimesheet($employee, $dateString);
            
            $report[] = $daily;

            // Totaliza os minutos (ignora dias com inconsistência de marcação para não gerar saldo irreal)
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

        // Traduz e formata para Pt-BR (Ex: Fevereiro / 2026)
        Carbon::setLocale('pt_BR');
        $period = $startDate->translatedFormat('F / Y');

        return view('timesheet.report', compact('employee', 'report', 'totalsFormatted', 'period'));
    }

    /**
     * Auxiliar: Converte string "HH:MM" para o total em minutos
     */
    private function parseMinutes(string $hhmm): int
    {
        $parts = explode(':', $hhmm);
        if (count($parts) !== 2) return 0;
        
        list($hours, $minutes) = $parts;
        return ((int)$hours * 60) + (int)$minutes;
    }

    /**
     * Auxiliar: Formata um número inteiro de minutos para a visualização "HH:MM"
     */
    private function formatMinutes(int $totalMinutes): string
    {
        return sprintf('%02d:%02d', floor($totalMinutes / 60), $totalMinutes % 60);
    }
}