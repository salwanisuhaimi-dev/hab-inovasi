<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Archive extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'department_id',
        'project_name',
        'group_name',
        'year',
        'track',
        'competition',
        'achievement',
        'thumbnail',
        'video_link',
        'description',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function competitions()
    {
        return $this->belongsToMany(Competition::class)
                ->withPivot('achievement', 'year') 
                ->withTimestamps();
    }
}
