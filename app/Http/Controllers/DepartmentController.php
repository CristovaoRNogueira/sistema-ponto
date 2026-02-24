<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
{
    public function index() {
        // Pega as Secretarias (que não têm pai) e puxa os Departamentos filhos delas
        $secretariats = Department::where('company_id', Auth::user()->company_id)
                                  ->whereNull('parent_id')
                                  ->with('children')
                                  ->orderBy('name')
                                  ->get();
                                  
        return view('departments.index', compact('secretariats'));
    }

    public function store(Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:departments,id'
        ]);

        Department::create([
            'name' => $request->name,
            'parent_id' => $request->parent_id,
            'company_id' => Auth::user()->company_id
        ]);

        return back()->with('success', 'Estrutura adicionada com sucesso!');
    }

    public function destroy(Department $department) {
        if($department->company_id === Auth::user()->company_id) {
            $department->delete();
        }
        return back()->with('success', 'Removido com sucesso!');
    }
}