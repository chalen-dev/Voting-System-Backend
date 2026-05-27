<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    protected $table = 'options';
    protected $primaryKey = 'id';
    protected $fillable = [
        'poll_uuid',
        'value',
    ];

    public function poll()
    {
        return $this->belongsTo(Poll::class, 'poll_uuid');
    }

    public function votes()
    {
        return $this->hasMany(Vote::class, 'option_id');
    }
}
