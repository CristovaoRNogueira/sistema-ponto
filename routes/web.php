<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;

// Rota para a tela inicial
Route::get('/', [AdminController::class, 'index'])->name('dashboard');

// Rota para receber o formulário de cadastro
Route::post('/servidores/cadastrar', [AdminController::class, 'storeEmployee'])->name('employee.store');
Route::post('/admin/absences', [AdminController::class, 'storeAbsence'])->name('admin.absences.store');
Route::post('/admin/shift-exceptions', [AdminController::class, 'storeShiftSwap'])->name('admin.shift_exceptions.store');