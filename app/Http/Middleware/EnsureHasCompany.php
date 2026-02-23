<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Se o usuário está logado, mas NÃO tem empresa, obrigamos o cadastro
        if ($user && !$user->company_id) {
            // Evita loop infinito se ele já estiver na página de criar empresa
            if (!$request->routeIs('company.create') && !$request->routeIs('company.store')) {
                return redirect()->route('company.create');
            }
        }

        return $next($request);
    }
}