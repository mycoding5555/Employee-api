<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    protected $fillable = [
        'civil_servant_id',
        'name',
    ];

    public function civilServant()
    {
        return $this->belongsTo(Civil_servants_Photo::class, 'civil_servant_id');
    }
}
