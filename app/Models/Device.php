<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    // Permite que estes campos sejam preenchidos ao criar um registro
    protected $fillable = [
        'name',
        'serial_number'
    ];

    // Relacionamento: Um relógio possui várias batidas de ponto
    public function punchLogs()
    {
        return $this->hasMany(PunchLog::class);
    }
}