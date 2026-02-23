<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftException extends Model
{
    protected $fillable = [
        'employee_id', 'exception_date', 'type', 
        'in_1', 'out_1', 'in_2', 'out_2', 
        'daily_work_minutes', 'observation'
    ];

    protected function casts(): array
    {
        return [
            'exception_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}