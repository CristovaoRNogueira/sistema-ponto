<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Department;
use Illuminate\Support\Facades\Auth;

class CompanyController extends Controller
{
    public function create()
    {
        // Busca as empresas e seus respectivos departamentos para popular os selects
        $companies = Company::with('departments')->orderBy('name')->get();
        return view('company.create', compact('companies'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'setup_type' => 'required|in:new,existing',

            // Regras se for Nova Instituição
            'name' => 'required_if:setup_type,new|nullable|string|max:255',
            'cnpj' => 'required_if:setup_type,new|nullable|string|max:20|unique:companies,cnpj',

            // Regras se for Vincular Existente
            'company_id' => 'required_if:setup_type,existing|nullable|exists:companies,id',
            'department_id' => 'nullable|exists:departments,id',
            'role' => 'required_if:setup_type,existing|nullable|in:operator,employee',
        ]);

        $user = Auth::user();

        if ($request->setup_type === 'new') {
            // Cria a nova Empresa e torna o usuário Administrador Global
            $company = Company::create([
                'name' => $request->name,
                'cnpj' => $request->cnpj,
            ]);
            $user->company_id = $company->id;
            $user->role = 'admin';
        } else {
            // Vincula à base existente
            $user->company_id = $request->company_id;
            $user->department_id = $request->department_id;
            $user->role = $request->role;
        }

        $user->save();

        return redirect()->route('dashboard')->with('success', 'Perfil configurado e vinculado com sucesso!');
    }
}
