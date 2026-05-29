<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Option extends Model
{
    protected $table = 'options';
    protected $primaryKey = 'id';

    protected $fillable = [
        'poll_uuid',
        'value',
        'image_path',
    ];

    // Automatically append the custom 'image_url' attribute to JSON responses
    protected $appends = ['image_url'];

    public function poll()
    {
        return $this->belongsTo(Poll::class, 'poll_uuid');
    }

    public function votes()
    {
        return $this->hasMany(Vote::class, 'option_id');
    }

    /**
     * Get the full URL path to the image.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                // If image_path exists, return the full public URL, otherwise null
                return !empty($attributes['image_path'])
                    ? asset('storage/' . $attributes['image_path'])
                    : null;
            }
        );
    }
}
