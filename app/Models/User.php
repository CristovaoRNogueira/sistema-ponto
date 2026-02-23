<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',
        'role', // 'admin', 'operator', 'employee'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * O usuário pertence a uma Empresa (Company)
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // ==========================================
    // HELPERS DE PERMISSÃO (ROLES)
    // ==========================================

    /**
     * Verifica se o usuário é um Administrador Global
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Verifica se o usuário tem nível de Operador (RH) ou superior
     */
    public function isOperator(): bool
    {
        return $this->role === 'operator' || $this->role === 'admin';
    }

    /**
     * Verifica se o usuário é apenas um Funcionário comum
     */
    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }
}