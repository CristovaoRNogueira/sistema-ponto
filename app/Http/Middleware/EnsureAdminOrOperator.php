<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminOrOperator
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // 1. Se não estiver logado, manda pro login
        if (!$user) {
            return redirect()->route('login');
        }

        // 2. Se for Admin ou Operador, DEIXA PASSAR
        if ($user->isAdmin() || $user->isOperator()) {
            return $next($request);
        }

        // 3. Se for Funcionário Comum tentando acessar área restrita:
        // Redireciona para o painel dele (Meu Ponto)
        return redirect()->route('employee.timesheet')->with('error', 'Acesso restrito a administradores.');
    }
}
