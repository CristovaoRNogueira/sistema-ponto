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

        $punches = $employee->punchLogs()
            ->whereDate('punch_time', $dateObj->format('Y-m-d'))
            ->orderBy('punch_time', 'asc')
            ->get();

        $absence = Absence::where('employee_id', $employee->id)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->where('waive_hours', true)
            ->first();

        if ($absence) {
            $punchTimes = $punches->map(fn($p) => Carbon::parse($p->punch_time)->format('H:i'))->toArray();
            // Atestado: Carga Esperada = 0 (abona o dia)
            return $this->buildResult($dateObj, $punchTimes, 0, 0, 0, 'justified', 'Atestado/Licença', $isWeekend);
        }

        $holiday = Holiday::where(function ($query) use ($employee) {
            $query->where('company_id', $employee->company_id)
                ->orWhereNull('company_id');
        })
            ->whereDate('date', $date)
            ->first();

        $exception = ShiftException::where('employee_id', $employee->id)
            ->whereDate('exception_date', $date)
            ->first();

        $effectiveShift = $employee->shift ?? $employee->department?->shift ?? $employee->department?->parent?->shift;

        $expectedMinutes = 0;
        $tolerance = 0;
        $observation = '';
        $expectedPunchesCount = 0;
        $shiftIn1 = null;

        if ($holiday) {
            $observation = 'Feriado: ' . $holiday->name;
            $expectedMinutes = 0; // Feriado reduz a carga mensal
        } elseif ($exception) {
            if ($exception->type === 'day_off') {
                $observation = 'Folga/Troca compensada';
                $expectedMinutes = 0;
            } else {
                $expectedMinutes = $exception->daily_work_minutes;
                $observation = 'Plantão de Exceção';
                $expectedPunchesCount = 2;
            }
        } elseif ($effectiveShift && !$isWeekend) {
            $expectedMinutes = $effectiveShift->daily_work_minutes;
            $tolerance = $effectiveShift->tolerance_minutes;
            $shiftIn1 = $effectiveShift->in_1;
            $expectedPunchesCount = (!empty($effectiveShift->in_2) && !empty($effectiveShift->out_2)) ? 4 : 2;
        } elseif ($isWeekend) {
            $observation = 'Final de Semana';
        } else {
            $observation = 'Sem Jornada Vinculada';
        }

        $punchObjects = [];
        foreach ($punches as $p) {
            $punchObjects[] = Carbon::parse($p->punch_time);
        }

        $punchTimes = [];
        foreach ($punchObjects as $po) {
            $punchTimes[] = $po->format('H:i');
        }

        $calcPunchObjects = [];
        foreach ($punchObjects as $po) {
            $calcPunchObjects[] = clone $po;
        }

        // Tolerância na entrada 1 só corta se chegou cedo (não gera hora extra indevida)
        if (count($calcPunchObjects) > 0 && !empty($shiftIn1) && $tolerance > 0) {
            $expectedIn1 = Carbon::parse($dateObj->format('Y-m-d') . ' ' . $shiftIn1);
            $firstPunch = $calcPunchObjects[0];

            if ($firstPunch->lt($expectedIn1)) {
                $diffEarly = $firstPunch->diffInMinutes($expectedIn1);
                if ($diffEarly <= $tolerance) {
                    $calcPunchObjects[0] = clone $expectedIn1;
                }
            }
        }

        $workedMinutes = 0;
        $numPunches = count($calcPunchObjects);

        for ($i = 0; $i < $numPunches - 1; $i += 2) {
            $in = $calcPunchObjects[$i];
            $out = $calcPunchObjects[$i + 1];
            $workedMinutes += $in->diffInMinutes($out);
        }

        // Desconto Inteligente de Almoço (se bateu 2 e deveria bater 4)
        if ($expectedPunchesCount == 4 && $numPunches == 2 && !empty($effectiveShift->out_1) && !empty($effectiveShift->in_2)) {
            $out1Obj = Carbon::parse($dateObj->format('Y-m-d') . ' ' . $effectiveShift->out_1);
            $in2Obj = Carbon::parse($dateObj->format('Y-m-d') . ' ' . $effectiveShift->in_2);

            $pIn = $calcPunchObjects[0];
            $pOut = $calcPunchObjects[1];

            $overlapStart = $pIn->max($out1Obj);
            $overlapEnd = $pOut->min($in2Obj);

            if ($overlapStart->lt($overlapEnd)) {
                $workedMinutes -= $overlapStart->diffInMinutes($overlapEnd);
            }
        }

        $diff = $workedMinutes - $expectedMinutes;
        $balanceMinutes = $diff;
        $status = 'normal';

        if ($expectedMinutes > 0 && $numPunches == 0) {
            $status = 'absence';
        } elseif ($holiday && $numPunches == 0) {
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
            if ($diff == 0) {
                $status = 'normal';
            } elseif ($diff > 0) {
                $status = 'overtime';
            } else {
                $status = 'delay';
            }
        }

        // NOVO: Passando o $expectedMinutes
        return $this->buildResult($dateObj, $punchTimes, $expectedMinutes, $workedMinutes, $balanceMinutes, $status, $observation, $isWeekend);
    }

    private function buildResult($dateObj, $punches, $expectedMin, $workedMin, $balanceMin, $status, $obs, $isWeekend): array
    {
        $dayName = ucfirst($dateObj->locale('pt_BR')->translatedFormat('l'));

        return [
            'date' => $dateObj->format('d/m/Y'),
            'day_name' => $dayName,
            'is_weekend' => $isWeekend,
            'punches' => $punches,
            'expected_minutes' => $expectedMin, // NOVO
            'worked_minutes' => $workedMin,     // NOVO
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
