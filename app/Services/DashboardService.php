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

    // 1. Gráfico e Cards de Lotação (Super rápido, vai direto no banco)
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

    // 4. Atestados Ativos (Vai direto no banco)
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

    // 2, 3 e 5. Rankings Complexos (Usa seu motor atual com CACHE)
    public function getConsolidatedRankings($companyId, Carbon $startDate, Carbon $endDate, $departmentId = null)
    {
        // Chave única para o cache baseada no mês, ano e filtro
        $cacheKey = "dash_rankings_{$companyId}_{$startDate->format('Ym')}_{$departmentId}";

        // Guarda o resultado na memória por 60 minutos
        return Cache::remember($cacheKey, now()->addMinutes(60), function () use ($companyId, $startDate, $endDate, $departmentId) {
            
            $query = Employee::where('company_id', $companyId)->where('is_active', true)->with('department:id,name');
            if ($departmentId) $query->where('department_id', $departmentId);
            
            $employees = $query->get();
            
            $delays = [];
            $absences = [];
            $bankHours = [];

            // Limita a contagem até o dia de hoje (não contar faltas amanhã)
            $limitDate = $endDate->isFuture() ? Carbon::today() : $endDate;
            $diasUteisMes = $startDate->diffInDaysFiltered(fn(Carbon $d) => !$d->isWeekend(), $limitDate);
            if ($diasUteisMes == 0) $diasUteisMes = 1; // Evita divisão por zero

            foreach ($employees as $emp) {
                $totalAtrasosMin = 0;
                $qtdAtrasos = 0;
                $diasFalta = 0;
                $bancoHorasMin = 0;
                $ultimaFalta = null;
                $ultimoAtraso = null;

                // Faz um loop pelos dias do mês
                for ($date = $startDate->copy(); $date->lte($limitDate); $date->addDay()) {
                    
                    // CORREÇÃO AQUI: Chama o método correto passando a data como string 'Y-m-d'
                    $report = $this->timeService->calculateDailyTimesheet($emp, $date->format('Y-m-d'));

                    if ($report['status'] === 'absence' || ($report['worked_formatted'] == '00:00' && !$report['is_weekend'] && $report['status'] != 'holiday' && $report['status'] != 'justified')) {
                        $diasFalta++;
                        $ultimaFalta = $date->format('d/m/Y');
                    }

                    if ($report['status'] === 'delay' && $report['balance_minutes'] < 0) {
                        // Como seu motor retorna balance_minutes negativo para atraso, usamos abs() para somar os minutos perdidos
                        $totalAtrasosMin += abs($report['balance_minutes']);
                        $qtdAtrasos++;
                        $ultimoAtraso = $date->format('d/m/Y');
                    }

                    // Soma o saldo do dia (Positivo é extra, Negativo é atraso)
                    $bancoHorasMin += $report['balance_minutes'];
                }

                // 2. Monta Ranking de Atrasos
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

                // 3. Monta Ranking de Sem Registro (Faltas)
                if ($diasFalta >= 1) {
                    $absences[] = [
                        'employee' => $emp,
                        'days' => $diasFalta,
                        'last' => $ultimaFalta,
                        'critical' => $diasFalta >= 3 // Destaca mais de 3 faltas
                    ];
                }

                // 5. Monta Banco de Horas
                if ($bancoHorasMin != 0) {
                    $bankHours[] = [
                        'employee' => $emp,
                        'balance_min' => $bancoHorasMin,
                        // Mais de 40h (+2400 min) ou devendo 20h (-1200 min)
                        'critical_positive' => $bancoHorasMin > 2400,
                        'critical_negative' => $bancoHorasMin < -1200, 
                        'formatted' => ($bancoHorasMin < 0 ? '-' : '+') . sprintf('%02d:%02d', abs(intdiv($bancoHorasMin, 60)), abs($bancoHorasMin % 60))
                    ];
                }
            }

            // Ordenações
            usort($delays, fn($a, $b) => $b['qtd'] <=> $a['qtd']);
            usort($absences, fn($a, $b) => $b['days'] <=> $a['days']);
            usort($bankHours, fn($a, $b) => $a['balance_min'] <=> $b['balance_min']); // Do mais negativo pro mais positivo

            return [
                'delays' => array_slice($delays, 0, 10), // Pega o Top 10
                'absences' => array_slice($absences, 0, 10),
                'bankHours' => array_slice($bankHours, 0, 10)
            ];
        });
    }
}