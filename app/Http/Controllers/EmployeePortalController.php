<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Absence;
use App\Models\PunchLog;
use App\Services\TimeCalculationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class EmployeePortalController extends Controller
{
    public function index(Request $request, TimeCalculationService $calcService)
    {
        $user = Auth::user();

        // Tenta encontrar o funcionário vinculado a este utilizador
        // Lógica: Procura pelo CPF (se o user tiver) ou pelo Email
        $employee = null;

        if (!empty($user->cpf)) {
            $employee = Employee::where('cpf', $user->cpf)->where('is_active', true)->first();
        }

        if (!$employee) {
            $employee = Employee::where('email', $user->email)->where('is_active', true)->first();
        }

        // Se não encontrar vínculo, mostra erro
        if (!$employee) {
            return view('errors.no-employee-linked');
        }

        // Define o mês a visualizar (padrão: mês atual)
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Trava para não mostrar futuro distante
        if ($endDate->isFuture()) {
            $endDate = Carbon::now();
        }

        // Busca dados (Atestados, Batidas Manuais)
        $absences = Absence::where('employee_id', $employee->id)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate]);
            })->orderBy('start_date', 'desc')->get();

        $manualPunches = PunchLog::where('employee_id', $employee->id)
            ->whereBetween('punch_time', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->where('is_manual', true)
            ->orderBy('punch_time', 'desc')
            ->get();

        // Calcula o Espelho
        $report = [];
        $totals = ['worked_minutes' => 0, 'overtime_minutes' => 0, 'delay_minutes' => 0];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $daily = $calcService->calculateDailyTimesheet($employee, $date->format('Y-m-d'));
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

        // Retorna a mesma view do relatório, mas indicando que é modo "Portal" (sem botões de edição)
        return view('timesheet.report', compact('employee', 'report', 'totalsFormatted', 'period', 'absences', 'manualPunches'))
            ->with('isPortal', true);
    }

    private function parseMinutes(string $hhmm): int
    {
        $parts = explode(':', $hhmm);
        return count($parts) === 2 ? ((int)$parts[0] * 60) + (int)$parts[1] : 0;
    }

    private function formatMinutes(int $totalMinutes): string
    {
        return sprintf('%02d:%02d', floor($totalMinutes / 60), $totalMinutes % 60);
    }
}
