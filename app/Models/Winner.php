<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Winner extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'label',
        'position'
    ];

    /**
    * Get the user who cast this vote.
    */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

}
