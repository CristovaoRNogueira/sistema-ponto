<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    // Todos os campos permitidos para salvamento no banco de dados
    protected $fillable = [
        'company_id',
        'name',
        'serial_number',
        'ip_address',
        'username',
        'password',
        'last_seen_at', // Radar: Data do último ping
        'is_online',    // Radar: Status de conexão
        'paper_status', // Radar: Nível da bobina
        'last_error',   // Radar: Mensagem de erro
        'last_nsr'      // Sincronização: Memória da última batida lida
    ];

    // Converte automaticamente os tipos de dados
    protected function casts(): array
    {
        return [
            'is_online' => 'boolean',
            'last_seen_at' => 'datetime',
            'last_nsr' => 'integer',
        ];
    }

    // --- Relacionamentos do Banco de Dados ---

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function punchLogs()
    {
        return $this->hasMany(PunchLog::class);
    }

    public function commandQueues()
    {
        return $this->hasMany(CommandQueue::class);
    }
}
