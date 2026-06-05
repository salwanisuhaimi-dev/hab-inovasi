<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Submission extends Model
{
    use HasFactory;

    /**
     * 
     */
    protected $fillable = [
        'program_id',
        'user_id',
        'project_title',
        'project_description',
        'group_name',
        'department_id',
        'file_path',
        'status',
    ];

    /**
     * Relationship
     * $submission->program->title
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**D
     * Relationship
     * $submission->user->name
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}