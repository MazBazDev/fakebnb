<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Listing extends Model
{
    protected $fillable = [
        'host_user_id',
        'title',
        'description',
        'city',
        'address',
        'full_address',
        'latitude',
        'longitude',
        'guest_capacity',
        'price_per_night',
        'rules',
        'amenities',
    ];

    protected $casts = [
        'amenities' => 'array',
    ];

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ListingImage::class)->orderBy('position');
    }

    public function cohosts(): HasMany
    {
        return $this->hasMany(Cohost::class);
    }
}
