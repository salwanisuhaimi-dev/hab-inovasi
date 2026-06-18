<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vote extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pitch_id',
    ];

    /**
    * Get the user who cast this vote.
    */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
    * Get the pitch that this vote belongs to.
    */
    public function pitch(): BelongsTo
    {
        return $this->belongsTo(Pitch::class);
    }
}
