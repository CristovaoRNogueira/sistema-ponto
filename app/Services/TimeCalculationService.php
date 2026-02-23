<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Absence;
use App\Models\ShiftException;
use Carbon\Carbon;

class TimeCalculationService
{
    public function calculateDailyTimesheet(Employee $employee, string $date): array
    {
        $dateObj = Carbon::parse($date);
        
        // 1. Pega as batidas físicas do relógio neste dia
        $punches = $employee->punchLogs()
            ->whereDate('punch_time', $date) // Ajuste se a sua coluna chamar 'timestamp'
            ->orderBy('punch_time', 'asc')
            ->get();

        $punchTimes = $punches->map(fn($p) => Carbon::parse($p->punch_time)->format('H:i'))->toArray();
        $workedMinutes = 0;
        $isDivergent = $punches->count() % 2 !== 0;

        // Calcula minutos reais trabalhados
        if (!$isDivergent && $punches->count() > 0) {
            for ($i = 0; $i < $punches->count(); $i += 2) {
                $in = Carbon::parse($punches[$i]->punch_time);
                $out = Carbon::parse($punches[$i + 1]->punch_time);
                $workedMinutes += $in->diffInMinutes($out);
            }
        }

        // 2. Verifica Atestados / Ausências (Prioridade Máxima)
        $absence = Absence::where('employee_id', $employee->id)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->where('waive_hours', true)
            ->first();

        if ($absence) {
            return $this->buildResult($dateObj, $punchTimes, $workedMinutes, 0, 'justified', 'Atestado/Licença');
        }

        // 3. Verifica Exceções / Trocas de Plantão (Prioridade 2)
        $exception = ShiftException::where('employee_id', $employee->id)
            ->whereDate('exception_date', $date)
            ->first();

        // 4. Define o que era "Esperado" para hoje
        $expectedMinutes = 0;
        $tolerance = 15; // Margem de atraso padrão da prefeitura
        $observation = '';

        if ($exception) {
            if ($exception->type === 'day_off') {
                $expectedMinutes = 0; // Folga
                $observation = 'Folga/Troca compensada';
            } else {
                $expectedMinutes = $exception->daily_work_minutes; // Plantão Extra ou Trocado
                $observation = 'Plantão de Exceção';
            }
        } elseif ($employee->shift) {
            // Regra Padrão (Sem exceção)
            
            // Se for final de semana e não houver plantão, a expectativa é zero
            if ($dateObj->isWeekend()) {
                $expectedMinutes = 0;
            } else {
                $expectedMinutes = $employee->shift->daily_work_minutes;
                $tolerance = $employee->shift->tolerance_minutes;
            }
        }

        // 5. Calcula o Saldo e Status
        $diff = $workedMinutes - $expectedMinutes;
        $status = 'normal';
        $balanceMinutes = 0;

        if ($isDivergent) {
            $status = 'divergent'; // Faltou bater a saída
        } elseif ($expectedMinutes == 0 && $workedMinutes == 0) {
            $status = 'day_off'; // Era folga e não trabalhou, tudo certo
        } elseif (abs($diff) > $tolerance) {
            $balanceMinutes = $diff;
            $status = $diff > 0 ? 'overtime' : 'delay';
        }

        return $this->buildResult($dateObj, $punchTimes, $workedMinutes, $balanceMinutes, $status, $observation);
    }

    private function buildResult($dateObj, $punches, $workedMin, $balanceMin, $status, $obs): array
    {
        return [
            'date' => $dateObj->format('d/m/Y'),
            'punches' => $punches,
            'worked_formatted' => $this->formatMinutes($workedMin),
            'balance_formatted' => $this->formatMinutes(abs($balanceMin)),
            'balance_minutes' => $balanceMin,
            'status' => $status,
            'observation' => $obs
        ];
    }

    private function formatMinutes(int $minutes): string
    {
        return sprintf('%02d:%02d', floor($minutes / 60), $minutes % 60);
    }
}