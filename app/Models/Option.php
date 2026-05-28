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

/**
 * TODO: IMPLEMENT IMAGE SUPPORT FOR POLL OPTIONS
 * * DB / BACKEND:
 * [ ] Add `image_path` (VARCHAR 255, nullable) to `options` table.
 * [ ] Create an upload endpoint/logic to handle Multipart/Form-Data.
 * [ ] Update the Poll creation/update API to map image paths to options.
 * * FRONTEND:
 * [ ] Update `Option` interface in `poll.ts` to include `image_path?: string`.
 * [ ] Modify `PollTabs` form to include a file input for each option.
 * [ ] Update `usePollMutations` to handle FormData if uploading files directly,
 * otherwise ensure image URLs are sent in the payload.
 * [ ] Update `PollTable` or create a new `PollPreview` to render option images.
 */
