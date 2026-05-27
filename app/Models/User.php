<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $table = 'users';

    protected $primaryKey = 'id';

    protected $fillable =
        [
            'first_name',
            'last_name',
            'email',
            'password',
        ];

    protected $hidden =
        [
            'password',
            'remember_token',
        ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function personalAccessTokens()
    {
        return $this->hasMany(PersonalAccessToken::class);
    }

    public function polls()
    {
        return $this->hasMany(Poll::class);
    }
}
