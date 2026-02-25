<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    // O ERRO ESTAVA AQUI: Faltava o 'shift_id' na lista abaixo!
    protected $fillable = ['company_id', 'name', 'parent_id', 'shift_id'];

    // A qual Secretaria (Pai) este departamento pertence
    public function parent(): BelongsTo {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    // Quais Departamentos (Filhos) pertencem a esta Secretaria
    public function children(): HasMany {
        return $this->hasMany(Department::class, 'parent_id');
    }

    public function employees(): HasMany {
        return $this->hasMany(Employee::class);
    }

    public function shift() {
        return $this->belongsTo(Shift::class);
    }
}