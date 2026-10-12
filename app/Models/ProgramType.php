<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramType extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'category',
        'level',
        'description',
        'is_active',
        'submission_slug',
        'admin_submission_slug',
        'requires_submission',
        'has_winner_announcement',
        'editable'
    ];

    public function programs()
    {
        return $this->hasMany(Program::class, 'category_id');
    }
}
