<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
{
    public function index() {
        $companyId = Auth::user()->company_id;

        $secretariats = Department::where('company_id', $companyId)
                                  ->whereNull('parent_id')
                                  ->with(['children.shift', 'shift']) 
                                  ->orderBy('name')
                                  ->get();
                                  
        $shifts = Shift::where('company_id', $companyId)->orderBy('name')->get();
                                  
        return view('departments.index', compact('secretariats', 'shifts'));
    }

    public function store(Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:departments,id',
            'shift_id' => 'nullable|exists:shifts,id'
        ]);

        Department::create([
            'name' => $request->name,
            'parent_id' => $request->parent_id,
            'shift_id' => $request->shift_id,
            'company_id' => Auth::user()->company_id
        ]);

        return back()->with('success', 'Estrutura e Jornada adicionadas com sucesso!');
    }

    // NOVO: Abre a tela de edição
    public function edit(Department $department) {
        // Proteção para garantir que o departamento pertence à empresa atual
        if($department->company_id !== Auth::user()->company_id) {
            abort(403);
        }

        $companyId = Auth::user()->company_id;
        
        // Puxa secretarias para o campo de vínculo (excluindo o próprio departamento para não causar um loop infinito)
        $secretariats = Department::where('company_id', $companyId)
                                  ->whereNull('parent_id')
                                  ->where('id', '!=', $department->id) 
                                  ->orderBy('name')
                                  ->get();
                                  
        $shifts = Shift::where('company_id', $companyId)->orderBy('name')->get();

        return view('departments.edit', compact('department', 'secretariats', 'shifts'));
    }

    // NOVO: Salva os dados atualizados
    public function update(Request $request, Department $department) {
        if($department->company_id !== Auth::user()->company_id) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:departments,id',
            'shift_id' => 'nullable|exists:shifts,id'
        ]);

        // Impede que um departamento seja colocado como "filho" dele mesmo
        if ($request->parent_id == $department->id) {
            return back()->withErrors(['parent_id' => 'Um departamento não pode ser pai dele mesmo.']);
        }

        $department->update([
            'name' => $request->name,
            'parent_id' => $request->parent_id,
            'shift_id' => $request->shift_id,
        ]);

        return redirect()->route('departments.index')->with('success', 'Estrutura atualizada com sucesso!');
    }

    public function destroy(Department $department) {
        if($department->company_id === Auth::user()->company_id) {
            $department->delete();
        }
        return back()->with('success', 'Removido com sucesso!');
    }
}