<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = ['name', 'sex', 'department_id', 'photo'];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
