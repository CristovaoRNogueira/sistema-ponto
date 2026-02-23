<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'name', 'fantasy_name', 'cnpj', 
        'legal_responsible_name', 'legal_responsible_cpf', 
        'legal_responsible_email', 'logo_path'
    ];

    public function users(): HasMany {
        return $this->hasMany(User::class);
    }

    public function employees(): HasMany {
        return $this->hasMany(Employee::class);
    }

    public function devices(): HasMany {
        return $this->hasMany(Device::class);
    }

    public function departments(): HasMany {
        return $this->hasMany(Department::class);
    }

    public function shifts(): HasMany {
        return $this->hasMany(Shift::class);
    }
}