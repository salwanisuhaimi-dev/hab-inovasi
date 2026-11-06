<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramAgenda extends Model
{
    protected $fillable = [
        'program_id',
        'title',
        'description',
        'start_time',
        'end_time',
        'order',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
