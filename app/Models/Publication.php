<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Publication extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'type',
        'year',
        'pdf_paths',
        'url',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'pdf_paths' => 'array',
        'is_active' => 'boolean',
        'year'      => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($publication) {
            $publication->slug = Str::slug($publication->title) . '-' . uniqid();
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
