<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ControlIdController;

// Rota padrão do Laravel para usuário autenticado (pode deixar se quiser)
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// --- A ROTA DOS RELÓGIOS CONTROL iD ---
// Usamos 'match' para aceitar tanto GET quanto POST, garantindo compatibilidade com os pings do relógio
Route::match(['get', 'post'], '/controlid/push', [ControlIdController::class, 'handlePush']);