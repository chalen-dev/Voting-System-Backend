<?php

namespace App\Models;

use App\Enums\PollStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Poll extends Model
{
    use HasUuids;
    protected $table = 'polls';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';


    protected $fillable = [
        'user_id',
        'title',
        'status',
    ];

    protected function casts()
    {
        return [
            'status' => PollStatus::class,
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
