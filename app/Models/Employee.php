<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'name',
        'pis',
        'registration_number'
    ];

    // Relacionamento: Um funcionário possui várias batidas de ponto
    public function punchLogs()
    {
        return $this->hasMany(PunchLog::class);
    }
}