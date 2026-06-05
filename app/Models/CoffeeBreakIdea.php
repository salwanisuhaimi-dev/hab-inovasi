<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoffeeBreakIdea extends Model
{
    protected $fillable = [
        'coffee_break_session_id',
        'category',
        'is_digital',
        'title',
        'suggestion',
        'action_taken',
    ];

    public function session()
    {
        return $this->belongsTo(CoffeeBreakSession::class, 'coffee_break_session_id');
    }
}
