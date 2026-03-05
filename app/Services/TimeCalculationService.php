<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Absence;
use App\Models\ShiftException;
use App\Models\DepartmentShiftException;
use App\Models\Holiday;
use Carbon\Carbon;
use App\Models\Vacation;

class TimeCalculationService
{
    public function calculateDailyTimesheet(Employee $employee, string $date): array
    {
        $dateObj = Carbon::parse($date);
        $isWeekend = $dateObj->isWeekend();

        // 1. Identificar a Jornada Base para saber se é Plantão Noturno
        $empShift = $employee->shift;
        $deptShift = $employee->department?->shift ?? $employee->department?->parent?->shift;
        $baseShift = $empShift ?? $deptShift;

        // ==========================================
        // JANELA DE BUSCA INTELIGENTE (VIRADA DE NOITE)
        // ==========================================
        $isNightShift = false;
        if ($baseShift && !empty($baseShift->in_1) && !empty($baseShift->out_1)) {
            $inTime = Carbon::parse($baseShift->in_1);
            $outTime = Carbon::parse($baseShift->out_1);

            // Se a saída (08:00) for menor que a entrada (20:00), vira a noite.
            if ($outTime->lt($inTime)) {
                $isNightShift = true;
            }
        }

        $startTime = $dateObj->copy()->startOfDay(); // Padrão: 00:00:00
        $endTime = $dateObj->copy()->endOfDay();     // Padrão: 23:59:59

        if ($isNightShift) {
            // Se for plantão noturno, o "Dia Lógico" começa ao meio-dia 
            // do dia do plantão e vai até o meio-dia do dia seguinte (cobrinha a saída das 08:00)
            $startTime = $dateObj->copy()->setHour(12)->setMinute(0)->setSecond(0);
            $endTime = $dateObj->copy()->addDay()->setHour(11)->setMinute(59)->setSecond(59);
        }

        // Busca as batidas apenas dentro da janela lógica
        $punches = $employee->punchLogs()
            ->whereBetween('punch_time', [$startTime, $endTime])
            ->orderBy('punch_time', 'asc')
            ->get();

        // ==========================================
        // PRIORIDADE SUPREMA: FÉRIAS
        // ==========================================
        $vacation = Vacation::where('employee_id', $employee->id)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();

        if ($vacation) {
            $punchTimes = $punches->map(fn($p) => Carbon::parse($p->punch_time)->format('H:i'))->toArray();
            return $this->buildResult($dateObj, $punchTimes, 0, 0, 0, 'vacation', '', $isWeekend);
        }

        // ==========================================
        // PRIORIDADE 0: ATESTADOS E AUSÊNCIAS (Soberano)
        // ==========================================
        $absence = Absence::where('employee_id', $employee->id)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->where('waive_hours', true)
            ->first();

        if ($absence) {
            $punchTimes = $punches->map(fn($p) => Carbon::parse($p->punch_time)->format('H:i'))->toArray();
            return $this->buildResult($dateObj, $punchTimes, 0, 0, 0, 'justified', 'Atestado/Licença', $isWeekend);
        }

        // ==========================================
        // BUSCA DAS EXCEÇÕES E FERIADOS (DB)
        // ==========================================
        $empException = ShiftException::where('employee_id', $employee->id)->whereDate('exception_date', $date)->first();

        $deptIds = array_filter([$employee->department_id, $employee->department?->parent_id]);
        $deptException = DepartmentShiftException::whereIn('department_id', $deptIds)
            ->whereDate('exception_date', $date)
            ->orderByRaw($deptIds ? "FIELD(department_id, " . implode(',', array_reverse($deptIds)) . ") DESC" : "id")
            ->first();

        $holiday = Holiday::where(function ($query) use ($employee) {
            $query->where('company_id', $employee->company_id)->orWhereNull('company_id');
        })->whereDate('date', $date)->first();

        $tolerance = $baseShift ? $baseShift->tolerance_minutes : 0;

        // ==========================================
        // LÓGICA DE ESCALA 12x36 (MOTOR INTELIGENTE CORRIGIDO)
        // ==========================================
        $is12x36 = $baseShift && $baseShift->is_12x36;
        $isWorkDay12x36 = false;

        if ($is12x36 && $employee->scale_start_date) {
            // Zera as horas para garantir que o diffInDays conte os dias absolutos do calendário
            $startDateObj = Carbon::parse($employee->scale_start_date)->startOfDay();
            $currentDateObj = Carbon::parse($dateObj->format('Y-m-d'))->startOfDay();

            // diffInDays retorna o número inteiro de dias entre as duas datas
            $diffInDays = $currentDateObj->diffInDays($startDateObj);

            // Se a diferença de dias for par (0, 2, 4...), é dia de plantão. Ímpar (1, 3, 5...) é folga.
            if (abs($diffInDays) % 2 == 0) {
                $isWorkDay12x36 = true;
            }
        }

        $expectedMinutes = 0;
        $observation = '';
        $expectedPunchesCount = 0;
        $shiftIn1 = null;
        $effectiveShiftForLunch = null;

        // ==========================================
        // HIERARQUIA RESTRITA DE REGRAS DE NEGÓCIO
        // ==========================================
        if ($empException) {
            $effectiveShiftForLunch = $empException;
            if ($empException->type === 'day_off') {
                $expectedMinutes = 0;
                $observation = 'Folga/Troca (Servidor)';
            } else {
                $expectedMinutes = $empException->daily_work_minutes;
                $observation = 'Exceção de Jornada (Servidor)';
                $expectedPunchesCount = (!empty($empException->in_2) && !empty($empException->out_2)) ? 4 : 2;
                $shiftIn1 = $empException->in_1;
            }
        } elseif ($deptException) {
            $effectiveShiftForLunch = $deptException;
            if ($deptException->type === 'day_off') {
                $expectedMinutes = 0;
                $observation = 'Recesso do Departamento';
            } else {
                $expectedMinutes = $deptException->daily_work_minutes;
                $observation = 'Funcionamento Parcial (Departamento)';
                $expectedPunchesCount = (!empty($deptException->in_2) && !empty($deptException->out_2)) ? 4 : 2;
                $shiftIn1 = $deptException->in_1;
            }
        } elseif ($is12x36) {
            // REGRA 12x36 (SOBREPÕE Feriados e Finais de Semana)
            if (!$employee->scale_start_date) {
                $expectedMinutes = 0;
                $observation = 'Erro: 12x36 sem Data Base configurada';
            } elseif ($isWorkDay12x36) {
                $effectiveShiftForLunch = $baseShift;
                $expectedMinutes = $baseShift->daily_work_minutes > 0 ? $baseShift->daily_work_minutes : 720;
                $observation = 'Plantão 12x36';
                $expectedPunchesCount = (!empty($baseShift->in_2) && !empty($baseShift->out_2)) ? 4 : 2;
                $shiftIn1 = $baseShift->in_1 ?? '07:00';
            } else {
                $expectedMinutes = 0;
                $observation = 'Folga Escala 12x36';
            }
        } elseif ($holiday) {
            $expectedMinutes = 0;
            $observation = 'Feriado: ' . $holiday->name;
        } elseif ($empShift && !$isWeekend) {
            $effectiveShiftForLunch = $empShift;
            $expectedMinutes = $empShift->daily_work_minutes;
            $observation = '';
            $expectedPunchesCount = (!empty($empShift->in_2) && !empty($empShift->out_2)) ? 4 : 2;
            $shiftIn1 = $empShift->in_1;
        } elseif ($deptShift && !$isWeekend) {
            $effectiveShiftForLunch = $deptShift;
            $expectedMinutes = $deptShift->daily_work_minutes;
            $observation = '';
            $expectedPunchesCount = (!empty($deptShift->in_2) && !empty($deptShift->out_2)) ? 4 : 2;
            $shiftIn1 = $deptShift->in_1;
        } elseif ($isWeekend) {
            $expectedMinutes = 0;
            $observation = 'Final de Semana';
        } else {
            $expectedMinutes = 0;
            $observation = 'Sem Jornada Vinculada';
        }

        // ==========================================
        // MATEMÁTICA DE BATIDAS E SALDO DIÁRIO
        // ==========================================
        $calcPunchObjects = [];
        $punchTimes = [];
        foreach ($punches as $p) {
            $po = Carbon::parse($p->punch_time);
            $punchTimes[] = $po->format('H:i'); // Extrai só a hora para exibir no espelho
            $calcPunchObjects[] = clone $po;    // Guarda o objeto de data completo para o cálculo correto!
        }

        // Tolerância de Chegada
        if (count($calcPunchObjects) > 0 && !empty($shiftIn1) && $tolerance > 0) {
            $expectedIn1 = Carbon::parse($dateObj->format('Y-m-d') . ' ' . $shiftIn1);
            $firstPunch = $calcPunchObjects[0];

            if ($firstPunch->lt($expectedIn1) && $firstPunch->diffInMinutes($expectedIn1) <= $tolerance) {
                $calcPunchObjects[0] = clone $expectedIn1;
            }
        }

        $workedMinutes = 0;
        $numPunches = count($calcPunchObjects);

        for ($i = 0; $i < $numPunches - 1; $i += 2) {
            // O cálculo acontece aqui: como guardamos os objetos originais, 
            // a batida do dia seguinte (08:00) entende que se passaram 12h desde as 20:00!
            $workedMinutes += $calcPunchObjects[$i]->diffInMinutes($calcPunchObjects[$i + 1]);
        }

        // Desconto de Almoço (se houver)
        if ($expectedPunchesCount == 4 && $numPunches == 2 && $effectiveShiftForLunch && !empty($effectiveShiftForLunch->out_1) && !empty($effectiveShiftForLunch->in_2)) {
            $out1Obj = Carbon::parse($dateObj->format('Y-m-d') . ' ' . $effectiveShiftForLunch->out_1);
            $in2Obj = Carbon::parse($dateObj->format('Y-m-d') . ' ' . $effectiveShiftForLunch->in_2);

            $overlapStart = $calcPunchObjects[0]->max($out1Obj);
            $overlapEnd = $calcPunchObjects[1]->min($in2Obj);

            if ($overlapStart->lt($overlapEnd)) {
                $workedMinutes -= $overlapStart->diffInMinutes($overlapEnd);
            }
        }

        $balanceMinutes = $workedMinutes - $expectedMinutes;
        $status = 'normal';

        if ($expectedMinutes > 0 && $numPunches == 0) {
            $status = 'absence';
        } elseif (($holiday || ($deptException && $deptException->type === 'day_off')) && $numPunches == 0 && !$is12x36) {
            $status = 'holiday';
            $balanceMinutes = 0;
        } elseif ($expectedMinutes == 0 && $numPunches == 0) {
            $status = 'day_off';
            $balanceMinutes = 0;
        } elseif ($numPunches % 2 !== 0) {
            $status = 'divergent';
        } elseif ($expectedPunchesCount > 0 && $numPunches > 0 && $numPunches < $expectedPunchesCount) {
            $status = 'incomplete';
        } else {
            if ($balanceMinutes == 0) {
                $status = 'normal';
            } elseif ($balanceMinutes > 0) {
                $status = 'overtime';
            } else {
                $status = 'delay';
            }
        }

        return $this->buildResult($dateObj, $punchTimes, $expectedMinutes, $workedMinutes, $balanceMinutes, $status, $observation, $isWeekend);
    }

    private function buildResult($dateObj, $punches, $expectedMin, $workedMin, $balanceMin, $status, $obs, $isWeekend): array
    {
        return [
            'date' => $dateObj->format('d/m/Y'),
            'day_name' => ucfirst($dateObj->locale('pt_BR')->translatedFormat('l')),
            'is_weekend' => $isWeekend,
            'punches' => $punches,
            'expected_minutes' => $expectedMin,
            'worked_minutes' => $workedMin,
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
