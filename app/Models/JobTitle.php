<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class JobTitle extends Model {
    protected $fillable = ['company_id', 'name', 'cbo'];
}