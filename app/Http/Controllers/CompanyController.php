<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;

class CompanyController extends Controller
{
    public function create()
    {
        return view('company.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'cnpj' => 'required|string|max:20|unique:companies,cnpj',
        ]);

        // 1. Cria a nova Empresa
        $company = Company::create([
            'name' => $request->name,
            'cnpj' => $request->cnpj,
        ]);

        // 2. Vincula o usuário logado à empresa e dá o nível de 'admin'
        $user = Auth::user();
        $user->company_id = $company->id;
        $user->role = 'admin';
        $user->save();

        return redirect()->route('dashboard')->with('success', 'Empresa cadastrada! Bem-vindo ao sistema.');
    }
}