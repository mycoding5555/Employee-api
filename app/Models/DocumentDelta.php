<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentDelta extends Model
{
    protected $table = 'document_deltas';

    protected $fillable = [
        'document_id',
        'civil_servant_id',
        'reference_id',
        'main_index',
        'index',
        'description',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Civilservant_Id::class, 'document_id');
    }

    public function civilServant(): BelongsTo
    {
        return $this->belongsTo(CivilServant::class, 'civil_servant_id');
    }
}
