<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    // 1. Liberar todos os campos para salvamento em massa
    protected $fillable = [
        'company_id',
        'name',
        'cpf',
        'rg',
        'pis',
        'registration_number',
        'department_id',
        'shift_id',          // <-- O grande responsável por não salvar a jornada!
        'job_title_id',
        'cost_center_id',
        'mobile_access',
        'app_password',
        'is_active',
        'dismissal_reason_id',
        'dismissal_date',
        'address'
    ];

    // 2. Relacionamento: Batidas de Ponto
    public function punchLogs()
    {
        return $this->hasMany(PunchLog::class);
    }

    // 3. Relacionamento: Jornada de Trabalho (Essencial para o Espelho)
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    // 4. Relacionamento: Órgão / Empresa
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // 5. Relacionamento: Departamento / Secretaria
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    // 6. Relacionamentos Auxiliares (Cargo e Centro de Custo)
    public function jobTitle()
    {
        return $this->belongsTo(JobTitle::class);
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }
}