<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cohost extends Model
{
    protected $fillable = [
        'host_user_id',
        'cohost_user_id',
        'listing_id',
        'can_read_conversations',
        'can_reply_messages',
        'can_edit_listings',
    ];

    protected $casts = [
        'can_read_conversations' => 'boolean',
        'can_reply_messages' => 'boolean',
        'can_edit_listings' => 'boolean',
    ];

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }

    public function cohost(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cohost_user_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
