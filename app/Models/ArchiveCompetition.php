<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveCompetition extends Model
{
    use HasFactory;

    protected $table = 'archive_competition';

    protected $fillable = [
        'archive_id',
        'competition_id',
        'achievement',
        'year',
    ];

    public function archive(): BelongsTo
    {
        return $this->belongsTo(Archive::class);
    }


    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

}
