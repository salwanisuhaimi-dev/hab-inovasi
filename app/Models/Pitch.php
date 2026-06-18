<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Pitch extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'method',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function votes():HasMany
    {
        return $this->hasMany(Vote::class);
    }
}
