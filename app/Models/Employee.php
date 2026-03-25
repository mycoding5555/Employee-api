<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = ['name', 'sex', 'title_id', 'department_id', 'photo'];

    public function title()
    {
        return $this->belongsTo(Title::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
