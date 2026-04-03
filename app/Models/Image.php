<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Image extends Model
{
    protected $fillable = [
        'civil_servant_id',
        'name',
    ];

    public function civilServant(): BelongsTo
    {
        return $this->belongsTo(CivilServant::class, 'civil_servant_id');
    }
}
