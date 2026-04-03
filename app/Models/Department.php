<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
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

    public function civilServants(): HasMany
    {
        return $this->hasMany(CivilServant::class, 'department_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_id');
    }
}
