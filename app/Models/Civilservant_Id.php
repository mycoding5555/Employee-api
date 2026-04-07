<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Civilservant_Id extends Model
{
    protected $table = ['documents','document_deltas'];

    protected $fillable = [
        'code',
        'ref_number',
        'ref_note',
        'organization_id',
        'ref_date',
        'name',
        'description',
        'document_type_id',
        'content_type_id',
        'size',
        'user_id',
        'status',
        'is_public',
        'source_id',
    ];

    protected $casts = [
        'ref_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('civilservant_id', function (Builder $builder) {
            $builder->where('document_type_id', 10);
        });
    }
}
