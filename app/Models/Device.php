<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Device extends Model {
    protected $fillable = ['company_id', 'name', 'serial_number'];
}