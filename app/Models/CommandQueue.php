<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommandQueue extends Model
{
    protected $fillable = [
        'device_id',
        'endpoint',
        'payload',
        'is_executed'
    ];

    // Converte automaticamente o JSON do banco para Array no PHP, e vice-versa
    protected $casts = [
        'payload' => 'array',
        'is_executed' => 'boolean',
    ];

    // Relacionamento: Este comando é destinado a um relógio específico
    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}