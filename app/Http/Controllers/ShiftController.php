<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ShiftController extends Controller
{
    public function index()
    {
        $shifts = Shift::where('company_id', Auth::user()->company_id)->get();
        return view('shifts.index', compact('shifts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'in_1' => 'required', 
            'out_1' => 'required',
            'in_2' => 'nullable', 
            'out_2' => 'nullable',
            'tolerance_minutes' => 'nullable|integer'
        ]);

        $data['company_id'] = Auth::user()->company_id;
        $data['tolerance_minutes'] = $data['tolerance_minutes'] ?? 10;

        // --- CÁLCULO AUTOMÁTICO DOS MINUTOS DIÁRIOS ---
        $minutes = 0;

        // Calcula o primeiro turno
        $in1 = Carbon::createFromFormat('H:i', $data['in_1']);
        $out1 = Carbon::createFromFormat('H:i', $data['out_1']);
        
        // Se a saída for menor que a entrada, significa que virou a noite
        if ($out1->lt($in1)) {
            $out1->addDay();
        }
        $minutes += $in1->diffInMinutes($out1);

        // Calcula o segundo turno (se existir)
        if (!empty($data['in_2']) && !empty($data['out_2'])) {
            $in2 = Carbon::createFromFormat('H:i', $data['in_2']);
            $out2 = Carbon::createFromFormat('H:i', $data['out_2']);
            
            if ($out2->lt($in2)) {
                $out2->addDay();
            }
            $minutes += $in2->diffInMinutes($out2);
        }

        $data['daily_work_minutes'] = $minutes;
        // ----------------------------------------------

        Shift::create($data);

        return back()->with('success', 'Jornada de trabalho adicionada com sucesso!');
    }

    public function destroy(Shift $shift)
    {
        if ($shift->company_id === Auth::user()->company_id) {
            $shift->delete();
        }
        return back()->with('success', 'Jornada removida!');
    }
}