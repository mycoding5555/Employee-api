<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Positions extends Model
{
    public $timestamps = false;

    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'code',
        'mcs_id',
        'name_kh',
        'name_en',
        'name_short',
        'abb',
        'equal_position_id',
        'type',
        'sort',
        'active',
        'user_id',
        'manage',
    ];

    public function civilServants()
    {
        return $this->hasMany(Civil_servants::class, 'position_id');
    }
}
