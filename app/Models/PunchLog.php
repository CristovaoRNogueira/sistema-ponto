<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PunchLog extends Model
{
    protected $fillable = [
        'employee_id',
        'device_id',
        'nsr',
        'punch_time'
    ];

    // O Laravel converte automaticamente essa coluna para um objeto de Data (Carbon)
    protected $casts = [
        'punch_time' => 'datetime',
    ];

    // Relacionamento: Esta batida pertence a um funcionário
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // Relacionamento: Esta batida foi feita em um relógio específico
    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}