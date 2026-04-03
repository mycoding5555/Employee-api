<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CivilServant extends Model
{
    protected $table = 'civil_servants';

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

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(Image::class, 'civil_servant_id');
    }
}
