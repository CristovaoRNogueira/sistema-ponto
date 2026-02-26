<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentShiftException extends Model
{
    protected $fillable = [
        'department_id',
        'exception_date',
        'type',
        'in_1',
        'out_1',
        'in_2',
        'out_2',
        'daily_work_minutes',
        'observation'
    ];

    protected function casts(): array
    {
        return ['exception_date' => 'date'];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
