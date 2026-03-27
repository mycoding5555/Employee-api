<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Civil_servants extends Model
{
    protected $table = 'civil_servants';

    /**
     * Scope all queries to អគ្គលេខាធិការដ្ឋាន and its child នាយកដ្ឋាន
     */
    protected static function booted(): void
    {
        static::addGlobalScope('department', function (Builder $builder) {
            $childIds = Departments::where('parent_id', 7)->pluck('id');
            $grandchildIds = Departments::whereIn('parent_id', $childIds)->pluck('id');
            $departmentIds = $childIds->merge($grandchildIds)->push(7)->toArray();
            $builder->whereIn('civil_servants.department_id', $departmentIds);
        });

        static::addGlobalScope('active', function (Builder $builder) {
            $builder->where('civil_servants.status_type_id', 1);
        });
    }

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'entity_id',
        'title_id',
        'entity_type',
        'org_type',
        'org_code',
        'mef_code',
        'gov_code',
        'last_name_kh',
        'first_name_kh',
        'last_name_en',
        'first_name_en',
        'dob',
        'gender_id',
        'department_id',
        'position_id',
        'equal_position_id',
        'base_salary_id',
        'status_type_id',
        'status_type_date',
        'degree_id',
        'marital_status_id',
        'sort',
    ];

    protected $casts = [
        'dob' => 'date',
        'status_type_date' => 'date',
    ];

    public function position()
    {
        return $this->belongsTo(Positions::class, 'position_id');
    }

    public function department()
    {
        return $this->belongsTo(Departments::class, 'department_id');
    }

    public function images()
    {
        return $this->hasMany(Image::class, 'civil_servant_id');
    }
}
