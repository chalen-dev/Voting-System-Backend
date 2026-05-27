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
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'status' => PollStatus::class,
            'start_time' => 'datetime',
            'end_time' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function options()
    {
        return $this->hasMany(Option::class, 'poll_uuid');
    }

    public function votes()
    {
        return $this->hasMany(Vote::class, 'poll_uuid');
    }
}