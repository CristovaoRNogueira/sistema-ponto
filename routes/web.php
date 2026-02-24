<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    // Setup da Empresa
    Route::get('/company/setup', [CompanyController::class, 'create'])->name('company.create');
    Route::post('/company/setup', [CompanyController::class, 'store'])->name('company.store');

    // Sistema Principal
    Route::middleware('company')->group(function () {
        
        // 1. Dashboard (Apenas Gráficos)
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        
        // 2. Gestão de Servidores (Telas Separadas)
        Route::get('/admin/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/admin/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/admin/employees', [EmployeeController::class, 'store'])->name('employees.store');
        
        // --- NOVAS ROTAS ADICIONADAS AQUI ---
        Route::get('/admin/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('/admin/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::delete('/admin/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
        
        // 3. Ações do Relógio e Ponto
        Route::post('/admin/employees/{employee}/sync', [AdminController::class, 'syncEmployeeToDevice'])->name('admin.employees.sync');
        Route::post('/admin/absences', [AdminController::class, 'storeAbsence'])->name('admin.absences.store');
        Route::post('/admin/shift-exceptions', [AdminController::class, 'storeShiftSwap'])->name('admin.shift_exceptions.store');
        Route::get('/admin/employees/{employee}/timesheet', [AdminController::class, 'reportTimesheet'])->name('admin.timesheet.report');

        // ----- CADASTROS BASE -----
        Route::resource('/admin/departments', App\Http\Controllers\DepartmentController::class)->only(['index', 'store', 'destroy']);
        Route::resource('/admin/job-titles', App\Http\Controllers\JobTitleController::class)->only(['index', 'store', 'destroy']);
        Route::resource('/admin/shifts', App\Http\Controllers\ShiftController::class)->only(['index', 'store', 'destroy']);
        Route::resource('/admin/devices', App\Http\Controllers\DeviceController::class)->only(['index', 'store', 'destroy']);
    });

    // Perfil do Breeze
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';