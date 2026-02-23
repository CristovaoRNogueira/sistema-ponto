<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommandQueue extends Model
{
    protected $fillable = [
        'device_id', 
        'command_type', // Ex: 'user_set', 'template_set' (biometria)
        'payload',      // O JSON com os dados que o relógio precisa
        'status'        // 'pending', 'processing', 'success', 'failed'
    ];

    // O Laravel 12 faz o cast automático de JSON para Array no PHP
    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}