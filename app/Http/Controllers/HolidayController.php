<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HolidayController extends Controller
{
    public function index()
    {
        $companyId = Auth::user()->company_id;

        // Puxa os feriados Nacionais (company_id = null) e os Municipais da prefeitura atual
        $holidays = Holiday::whereNull('company_id')
            ->orWhere('company_id', $companyId)
            ->orderBy('date', 'desc')
            ->paginate(15);

        return view('holidays.index', compact('holidays'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'type' => 'required|in:municipal,national'
        ]);

        $companyId = Auth::user()->company_id;

        Holiday::create([
            'name' => $request->name,
            'date' => $request->date,
            // Se for nacional, deixa null (vale para todos). Se for municipal, amarra ao ID da prefeitura.
            'company_id' => $request->type === 'national' ? null : $companyId
        ]);

        return back()->with('success', 'Feriado cadastrado com sucesso! O sistema de ponto já está ciente desta data.');
    }

    public function destroy(Holiday $holiday)
    {
        $companyId = Auth::user()->company_id;

        // Garante que só apaga se for da própria prefeitura ou se for um feriado global
        if ($holiday->company_id === $companyId || is_null($holiday->company_id)) {
            $holiday->delete();
            return back()->with('success', 'Feriado removido do calendário.');
        }

        abort(403, 'Acesso negado.');
    }
}