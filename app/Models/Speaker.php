<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Speaker extends Model
{
    protected $fillable = [
        'name',
        'position',
        'organization',
        'photo_path',
        'bio',
    ];
}
