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
            ->with('department')
            ->groupBy('department_id')
            ->orderByDesc('total')
            ->get();
    }

    public function getActiveAbsences($companyId, Carbon $startDate, Carbon $endDate, $departmentId = null)
    {
        $query = Absence::with(['employee:id,name,department_id', 'employee.department:id,name'])
            ->whereHas('employee', function ($q) use ($companyId, $departmentId) {
                $q->where('company_id', $companyId);
                if ($departmentId) $q->where('department_id', $departmentId);
            })
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($sub) use ($startDate, $endDate) {
                        $sub->where('start_date', '<=', $startDate)->where('end_date', '>=', $endDate);
                    });
            });

        return $query->orderBy('start_date', 'desc')->paginate(10, ['*'], 'absences_page');
    }

    public function getConsolidatedRankings($companyId, Carbon $startDate, Carbon $endDate, $departmentId = null)
    {
        // NOVO CACHE (V6): Atualiza sozinho!
        $cacheKey = "dash_v6_{$companyId}_{$startDate->format('Ym')}_{$departmentId}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($companyId, $startDate, $endDate, $departmentId) {

            $limitDate = $endDate->isFuture() ? Carbon::today() : clone $endDate;

            if ($limitDate->lt($startDate)) {
                return ['delays' => [], 'absences' => [], 'bankHours' => [], 'working_days' => 0];
            }

            $query = Employee::where('company_id', $companyId)
                ->where('is_active', true)
                ->with(['department.shift', 'department.parent.shift', 'shift']);

            if ($departmentId) $query->where('department_id', $departmentId);
            $employees = $query->get();

            $diasUteisMes = $startDate->diffInDaysFiltered(fn(Carbon $d) => !$d->isWeekend(), $limitDate);
            if ($diasUteisMes == 0) $diasUteisMes = 1;

            $delays = [];
            $absences = [];
            $bankHours = [];

            foreach ($employees as $emp) {
                $qtdAtrasos = 0;
                $diasFalta = 0;

                $cargaMensalMin = 0;
                $totalTrabalhadoMin = 0;

                $ultimaFalta = null;
                $ultimoAtraso = null;
                $diasTrabalhados = 0;

                // LEITURA DA TOLERÂNCIA DA JORNADA DO SERVIDOR
                $effectiveShift = $emp->shift ?? $emp->department?->shift ?? $emp->department?->parent?->shift;
                $tolerance = $effectiveShift ? $effectiveShift->tolerance_minutes : 0;

                for ($date = $startDate->copy(); $date->lte($limitDate); $date->addDay()) {

                    $report = $this->timeService->calculateDailyTimesheet($emp, $date->format('Y-m-d'));

                    $workedMin = $report['worked_minutes'] ?? 0;
                    $expectedMin = $report['expected_minutes'] ?? 0;
                    $balanceMin = $report['balance_minutes'] ?? 0;
                    $status = $report['status'];

                    if ($workedMin > 0) {
                        $diasTrabalhados++;
                    }

                    $cargaMensalMin += $expectedMin;
                    $totalTrabalhadoMin += $workedMin;

                    $isFaltaIntegral = false;
                    if ($status === 'absence') {
                        $isFaltaIntegral = true;
                    } elseif ($status === 'delay' && $report['worked_formatted'] === '00:00' && !$report['is_weekend'] && $status !== 'holiday' && $status !== 'justified') {
                        $isFaltaIntegral = true;
                    }

                    if ($isFaltaIntegral) {
                        $diasFalta++;
                        $ultimaFalta = $date->format('d/m/Y');
                    } else {
                        // REGRA NOVA DE ATRASOS: Só conta o "dia como atrasado" se o débito do dia for MAIOR que a tolerância
                        if ($balanceMin < 0 && abs($balanceMin) > $tolerance && in_array($status, ['delay', 'incomplete', 'divergent'])) {
                            $qtdAtrasos++;
                            $ultimoAtraso = $date->format('d/m/Y');
                        }
                    }
                }

                $saldoLiquido = $totalTrabalhadoMin - $cargaMensalMin;

                $formatMin = fn($min) => sprintf('%02d:%02d', floor($min / 60), $min % 60);
                $formatSaldo = fn($min) => ($min < 0 ? '-' : '+') . sprintf('%02d:%02d', abs(intdiv($min, 60)), abs($min % 60));

                if ($qtdAtrasos > 0) {
                    $delays[] = [
                        'employee' => $emp,
                        'qtd' => $qtdAtrasos,
                        // ENVIA O SALDO LÍQUIDO MENSAL EM VEZ DA SOMA DE ATRASOS
                        'saldo_min' => $saldoLiquido,
                        'formatted_saldo' => $formatSaldo($saldoLiquido),
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

                if ($cargaMensalMin > 0 || $saldoLiquido != 0 || $totalTrabalhadoMin > 0) {
                    $bankHours[] = [
                        'employee' => $emp,
                        'balance_min' => $saldoLiquido,
                        'carga_formatted' => $formatMin($cargaMensalMin),
                        'trabalhado_formatted' => $formatMin($totalTrabalhadoMin),
                        'saldo_formatted' => $formatSaldo($saldoLiquido),
                        'critical_positive' => $saldoLiquido > 2400,
                        'critical_negative' => $saldoLiquido < -1200,
                    ];
                }
            }

            // ORDENAÇÕES ATUALIZADAS
            usort($delays, fn($a, $b) => $b['qtd'] <=> $a['qtd']); // Mantém maior nº de dias de atraso no topo

            // FALTAS: DO MENOR PARA O MAIOR (Crescente)
            usort($absences, fn($a, $b) => $a['days'] <=> $b['days']);

            // SALDO LÍQUIDO: DO MENOR PARA O MAIOR (Crescente - os mais negativos ficam no topo)
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
