<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model {
    protected $fillable = ['company_id', 'name', 'in_1', 'out_1', 'in_2', 'out_2', 'daily_work_minutes', 'tolerance_minutes'];
}