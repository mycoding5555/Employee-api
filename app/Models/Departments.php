<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Departments extends Model
{
    protected $fillable = [
        'prefix',
        'code',
        'mcs_id',
        'name_kh',
        'name_en',
        'abbreviation',
        'description',
        'description_reverse',
        'name_short',
        'abb',
        'parent_id',
        'department_type_id',
        'sort',
        'active',
        'lft',
        'rght',
        'parent_array',
        'parent_type',
        'manage_id',
        'manage_type',
        'division_id',
        'doc_array',
        'province_id',
    ];

    public function civilServants()
    {
        return $this->hasMany(Civil_servants_Photo::class, 'department_id');
    }

    public function parent()
    {
        return $this->belongsTo(Departments::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Departments::class, 'parent_id');
    }
}
