<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\JobTitleController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\DeviceController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HolidayController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    // Setup da Empresa
    Route::get('/company/setup', [CompanyController::class, 'create'])->name('company.create');
    Route::post('/company/setup', [CompanyController::class, 'store'])->name('company.store');

    // Sistema Principal
    Route::middleware('company')->group(function () {

        // 1. Dashboard (Apenas Gráficos)
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/admin/export/monthly', [AdminController::class, 'exportMonthlyClosing'])->name('admin.export.monthly');

        // 2. Gestão de Servidores (Telas Separadas)
        Route::get('/admin/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/admin/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/admin/employees', [EmployeeController::class, 'store'])->name('employees.store');

        // --- NOVA ROTA DE LOTE AQUI (Sempre antes das rotas com {employee}) ---
        Route::post('/admin/employees/bulk-department', [EmployeeController::class, 'bulkUpdateDepartment'])->name('employees.bulk_department');

        Route::get('/admin/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('/admin/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::delete('/admin/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');

        // 3. Ações do Relógio e Ponto
        Route::post('/admin/employees/{employee}/sync', [AdminController::class, 'syncEmployeeToDevice'])->name('admin.employees.sync');
        Route::post('/admin/absences', [AdminController::class, 'storeAbsence'])->name('admin.absences.store');
        Route::post('/admin/shift-exceptions', [AdminController::class, 'storeShiftSwap'])->name('admin.shift_exceptions.store');
        Route::get('/admin/employees/{employee}/timesheet', [AdminController::class, 'reportTimesheet'])->name('admin.timesheet.report');

        // ----- CADASTROS BASE -----
        Route::resource('/admin/departments', DepartmentController::class)->except(['create', 'show']);
        Route::resource('/admin/job-titles', JobTitleController::class)->only(['index', 'store', 'destroy']);
        Route::resource('/admin/shifts', ShiftController::class)->only(['index', 'store', 'destroy']);
        Route::resource('/admin/devices', DeviceController::class)->only(['index', 'store', 'destroy']);
        Route::resource('/admin/holidays', HolidayController::class)->only(['index', 'store', 'destroy']);

        // Rotas de Tratamento de Ponto
        Route::post('/timesheet/{employee}/manual-punch', [EmployeeController::class, 'storeManualPunch'])->name('timesheet.manual-punch');
        Route::post('/timesheet/{employee}/absence', [EmployeeController::class, 'storeAbsence'])->name('timesheet.absence');

        // Rota de Bulk Sync e Importação (Sincronização do Relógio)
        Route::post('/devices/{device}/sync', [DeviceController::class, 'syncEmployees'])->name('devices.sync');
        Route::post('/devices/{device}/import', [DeviceController::class, 'importEmployees'])->name('devices.import');
    });

    // Perfil do Breeze
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
