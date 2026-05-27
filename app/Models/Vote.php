<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    //
    protected $table = 'votes';
    protected $fillable = [
        'poll_uuid',
        'option_id',
        'ip_hash',
    ];

    public function option()
    {
        return $this->belongsTo(Option::class, 'option_id');
    }

    public function poll()
    {
        return $this->belongsTo(Poll::class, 'poll_uuid');
    }
}
