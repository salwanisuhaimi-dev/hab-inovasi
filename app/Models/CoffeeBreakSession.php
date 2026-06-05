<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CoffeeBreakSession extends Model
{
    protected $fillable = [
        'created_by',
        'date_created',
        'location',
        'start_time',
        'end_time',
        'status',
        'department_id',
        'image_paths',
    ];

    protected $casts = [
        'image_paths' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function ideas(): HasMany
    {
        return $this->hasMany(CoffeeBreakIdea::class, 'coffee_break_session_id');
    }


}
