<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'department_id',
        'position',
        'grade',
        'telephone_num',
        'office_num',
        'password',
        'google_id',
        'role',
        'credits',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function ideas(): HasMany
    {
        return $this->hasMany(CoffeeBreakIdea::class);
    }

    public function sessions() {
        return $this->hasMany(CoffeeBreakSession::class, 'created_by');
    }

    public function submissions() {
        return $this->hasMany(Submission::class);
    }

    public function pitches(): HasMany
    {
        return $this->hasMany(Pitch::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

}
