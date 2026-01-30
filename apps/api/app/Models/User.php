<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'address',
        'profile_photo_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (! $this->profile_photo_path) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->profile_photo_path);
    }

    public function apiTokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function hostedCohosts(): HasMany
    {
        return $this->hasMany(Cohost::class, 'host_user_id');
    }

    public function cohostedBy(): HasMany
    {
        return $this->hasMany(Cohost::class, 'cohost_user_id');
    }

    public function hostedConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'host_user_id');
    }

    public function guestConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'guest_user_id');
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_user_id');
    }
}
