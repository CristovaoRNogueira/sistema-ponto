<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Department;
use App\Models\Absence;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Services\TimeCalculationService;

class DashboardService
{
    public function __construct(
        private TimeCalculationService $timeService
    ) {}

    public function getEmployeesByDepartment($companyId, $departmentId = null)
    {
        $query = Employee::where('company_id', $companyId)->where('is_active', true);
        if ($departmentId) $query->where('department_id', $departmentId);

        return $query->select('department_id', DB::raw('count(*) as total'))
            ->with('department:id,name')
            ->groupBy('department_id')
            ->orderByDesc('total')
            ->get();
    }

    public function getActiveAbsences($companyId, Carbon $startDate, Carbon $endDate, $departmentId = null)
    {
        $query = Absence::with(['employee:id,name,department_id', 'employee.department:id,name'])
            ->whereHas('employee', function($q) use ($companyId, $departmentId) {
                $q->where('company_id', $companyId);
                if ($departmentId) $q->where('department_id', $departmentId);
            })
            ->where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function($sub) use ($startDate, $endDate) {
                      $sub->where('start_date', '<=', $startDate)->where('end_date', '>=', $endDate);
                  });
            });

        return $query->orderBy('start_date', 'desc')->paginate(10, ['*'], 'absences_page');
    }

    public function getConsolidatedRankings($companyId, Carbon $startDate, Carbon $endDate, $departmentId = null)
    {
        $cacheKey = "dash_rankings_{$companyId}_{$startDate->format('Ym')}_{$departmentId}";

        return Cache::remember($cacheKey, now()->addMinutes(60), function () use ($companyId, $startDate, $endDate, $departmentId) {
            
            // CORREÇÃO 1: Analisa apenas até ONTEM para não dar "falsa falta" no dia de hoje
            $limitDate = $endDate->isFuture() ? Carbon::yesterday() : clone $endDate;
            
            // Se hoje for dia 1º, "ontem" cai no mês passado. Logo, retorna zerado para não dar erro.
            if ($limitDate->lt($startDate)) {
                return [ 'delays' => [], 'absences' => [], 'bankHours' => [], 'working_days' => 0 ];
            }

            $query = Employee::where('company_id', $companyId)->where('is_active', true)->with('department:id,name');
            if ($departmentId) $query->where('department_id', $departmentId);
            $employees = $query->get();
            
            $diasUteisMes = $startDate->diffInDaysFiltered(fn(Carbon $d) => !$d->isWeekend(), $limitDate);
            if ($diasUteisMes == 0) $diasUteisMes = 1;

            $delays = [];
            $absences = [];
            $bankHours = [];

            foreach ($employees as $emp) {
                $totalAtrasosMin = 0;
                $qtdAtrasos = 0;
                $diasFalta = 0;
                $bancoHorasMin = 0;
                $ultimaFalta = null;
                $ultimoAtraso = null;
                $diasTrabalhados = 0; 

                for ($date = $startDate->copy(); $date->lte($limitDate); $date->addDay()) {
                    
                    $report = $this->timeService->calculateDailyTimesheet($emp, $date->format('Y-m-d'));

                    if ($report['worked_formatted'] !== '00:00') {
                        $diasTrabalhados++;
                    }

                    // CORREÇÃO 2: Código simplificado confiando 100% no motor de cálculo (FALTA INTEGRAL)
                    if ($report['status'] === 'absence') {
                        $diasFalta++;
                        $ultimaFalta = $date->format('d/m/Y');
                    }

                    // CORREÇÃO 3: Confia 100% no motor (ATRASO / SAÍDA)
                    if ($report['status'] === 'delay') {
                        $totalAtrasosMin += abs($report['balance_minutes']);
                        $qtdAtrasos++;
                        $ultimoAtraso = $date->format('d/m/Y');
                    }

                    $bancoHorasMin += $report['balance_minutes'];
                }

                if ($qtdAtrasos > 0) {
                    $delays[] = [
                        'employee' => $emp,
                        'qtd' => $qtdAtrasos,
                        'total_min' => $totalAtrasosMin,
                        'formatted_time' => sprintf('%02d:%02d', floor($totalAtrasosMin / 60), $totalAtrasosMin % 60),
                        'percent' => round(($qtdAtrasos / $diasUteisMes) * 100, 1),
                        'last' => $ultimoAtraso
                    ];
                }

                if ($diasFalta >= 1) {
                    $absences[] = [
                        'employee' => $emp,
                        'days' => $diasFalta,
                        'last' => $ultimaFalta,
                        'never_clocked_in' => ($diasTrabalhados === 0),
                        'critical' => $diasFalta >= 3
                    ];
                }

                if ($bancoHorasMin != 0) {
                    $bankHours[] = [
                        'employee' => $emp,
                        'balance_min' => $bancoHorasMin,
                        'critical_positive' => $bancoHorasMin > 2400,
                        'critical_negative' => $bancoHorasMin < -1200, 
                        'formatted' => ($bancoHorasMin < 0 ? '-' : '+') . sprintf('%02d:%02d', abs(intdiv($bancoHorasMin, 60)), abs($bancoHorasMin % 60))
                    ];
                }
            }

            usort($delays, fn($a, $b) => $b['qtd'] <=> $a['qtd']);
            usort($absences, fn($a, $b) => $b['days'] <=> $a['days']);
            usort($bankHours, fn($a, $b) => $a['balance_min'] <=> $b['balance_min']); 

            return [
                'delays' => $delays,
                'absences' => $absences,
                'bankHours' => $bankHours,
                'working_days' => $diasUteisMes
            ];
        });
    }
}