<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Program extends Model
{
    use HasFactory;

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    protected $fillable = [
        'title',
        'category_id',
        'description',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'location',
        'prize',
        'deadline',
        'status',
    ];

    public function category()
    {
        return $this->belongsTo(ProgramType::class, 'category_id');
    }
}