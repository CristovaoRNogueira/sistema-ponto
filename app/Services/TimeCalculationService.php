<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Absence;
use App\Models\ShiftException;
use App\Models\Holiday;
use Carbon\Carbon;

class TimeCalculationService
{
    public function calculateDailyTimesheet(Employee $employee, string $date): array
    {
        $dateObj = Carbon::parse($date);
        $isWeekend = $dateObj->isWeekend();
        
        // 1. Pega as batidas físicas do relógio neste dia
        $punches = $employee->punchLogs()
            ->whereDate('punch_time', $dateObj->format('Y-m-d'))
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

        // 2. Verifica Atestados / Ausências
        $absence = Absence::where('employee_id', $employee->id)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->where('waive_hours', true)
            ->first();

        if ($absence) {
            return $this->buildResult($dateObj, $punchTimes, $workedMinutes, 0, 'justified', 'Atestado/Licença', $isWeekend);
        }

        // 3. Verifica Feriados
        $holiday = Holiday::where(function($query) use ($employee) {
                $query->where('company_id', $employee->company_id)
                      ->orWhereNull('company_id');
            })
            ->whereDate('date', $date)
            ->first();

        // 4. Verifica Exceções / Trocas de Plantão
        $exception = ShiftException::where('employee_id', $employee->id)
            ->whereDate('exception_date', $date)
            ->first();

        // 5. Define o que era "Esperado" e a Tolerância da Jornada
        $expectedMinutes = 0;
        $tolerance = 15; // Margem padrão
        $observation = '';

        $effectiveShift = $employee->shift ?? $employee->department?->shift ?? $employee->department?->parent?->shift;

        if ($holiday) {
            $expectedMinutes = 0; 
            $observation = 'Feriado: ' . $holiday->name;
        } elseif ($exception) {
            if ($exception->type === 'day_off') {
                $expectedMinutes = 0; 
                $observation = 'Folga/Troca compensada';
            } else {
                $expectedMinutes = $exception->daily_work_minutes; 
                $observation = 'Plantão de Exceção';
            }
        } elseif ($effectiveShift) {
            if ($isWeekend) {
                $expectedMinutes = 0; 
            } else {
                $expectedMinutes = $effectiveShift->daily_work_minutes;
                // AQUI: Pegamos a tolerância exata cadastrada na jornada!
                $tolerance = $effectiveShift->tolerance_minutes;
            }
        } else {
            $observation = 'Sem Jornada Vinculada';
        }

        // 6. SEPARAÇÃO DEFINITIVA DE SALDO, ATRASO E FALTA
        $diff = $workedMinutes - $expectedMinutes;
        $status = 'normal';
        $balanceMinutes = 0;

        if ($isDivergent) {
            $status = 'divergent'; // Batida ímpar (Esqueceu de bater a saída)
        } elseif ($holiday && $workedMinutes == 0) {
            $status = 'holiday'; // É feriado e ficou em casa (Correto)
        } elseif ($expectedMinutes == 0 && $workedMinutes == 0) {
            $status = 'day_off'; // Era fim de semana ou folga (Correto)
            
        } elseif ($expectedMinutes > 0 && $workedMinutes == 0) {
            // NOVA REGRA: Era pra trabalhar, mas tem ZERO horas = FALTA INTEGRAL
            $status = 'absence'; 
            $balanceMinutes = -$expectedMinutes;
            
        } else {
            // NOVA REGRA: A pessoa foi trabalhar. Vamos aplicar a Tolerância de Atraso.
            if (abs($diff) > $tolerance) {
                // Passou da tolerância! Fica com o saldo negativo ou positivo
                $balanceMinutes = $diff;
                $status = $diff > 0 ? 'overtime' : 'delay'; // 'delay' aqui significa atraso em horas devidas
            } else {
                // Atraso ou Extra foi tão pequeno que ficou DENTRO da tolerância da Jornada
                $balanceMinutes = 0;
                $status = 'normal';
            }
        }

        return $this->buildResult($dateObj, $punchTimes, $workedMinutes, $balanceMinutes, $status, $observation, $isWeekend);
    }

    private function buildResult($dateObj, $punches, $workedMin, $balanceMin, $status, $obs, $isWeekend): array
    {
        $dayName = ucfirst($dateObj->locale('pt_BR')->translatedFormat('l'));

        return [
            'date' => $dateObj->format('d/m/Y'),
            'day_name' => $dayName,
            'is_weekend' => $isWeekend,
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