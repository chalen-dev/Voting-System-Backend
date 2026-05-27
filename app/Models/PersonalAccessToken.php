<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalAccessToken extends Model
{
    protected $table = 'personal_access_tokens';
    protected $primaryKey = 'id';
    protected $fillable =
        [
            'user_id',
            'token',
            'last_used',
        ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function casts()
    {
        return [
            'last_used' => 'datetime',
        ];
    }
}
