<?php

namespace App\Http\Controllers;

use App\Models\Vacation;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class VacationController extends Controller
{
    public function index()
    {
        $companyId = Auth::user()->company_id;

        // Puxa os servidores para o select (respeitando nível de acesso)
        $employeesQuery = Employee::where('company_id', $companyId)->where('is_active', true)->orderBy('name');
        if (!Auth::user()->isAdmin() && Auth::user()->isOperator()) {
            $employeesQuery->where('department_id', Auth::user()->department_id);
        }
        $employees = $employeesQuery->get();

        // Puxa as férias já cadastradas
        $vacationsQuery = Vacation::with('employee.department')
            ->whereHas('employee', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->orderBy('start_date', 'desc');

        if (!Auth::user()->isAdmin() && Auth::user()->isOperator()) {
            $vacationsQuery->whereHas('employee', function ($q) {
                $q->where('department_id', Auth::user()->department_id);
            });
        }
        $vacations = $vacationsQuery->get();

        return view('vacations.index', compact('employees', 'vacations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'observation' => 'nullable|string|max:255'
        ]);

        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);

        // CÁLCULO DE DIAS (Se for de 01 a 30, são 30 dias)
        $totalDays = $start->diffInDays($end) + 1;

        if ($totalDays > 30) {
            return back()->withErrors(['end_date' => "O período de férias não pode ultrapassar 30 dias. Você selecionou {$totalDays} dias."])->withInput();
        }

        // Verifica se o servidor já tem férias marcadas que conflitam com estas datas
        $overlap = Vacation::where('employee_id', $request->employee_id)
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('start_date', '<=', $start)->where('end_date', '>=', $end);
                    });
            })->exists();

        if ($overlap) {
            return back()->withErrors(['start_date' => 'Este servidor já possui férias ou recesso marcados neste período.'])->withInput();
        }

        Vacation::create($request->all());
        Cache::flush(); // Atualiza Dashboard na hora

        return back()->with('success', "Férias de {$totalDays} dias agendadas com sucesso!");
    }

    public function destroy(Vacation $vacation)
    {
        if ($vacation->employee->company_id !== Auth::user()->company_id) {
            abort(403);
        }

        $vacation->delete();
        Cache::flush();

        return back()->with('success', 'Agendamento de férias excluído. Os saldos voltarão ao normal.');
    }
}
