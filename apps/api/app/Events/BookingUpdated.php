<?php

namespace App\Events;

use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Cohost;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public Booking $booking)
    {
        $this->booking->loadMissing(['listing', 'guest', 'payment']);
    }

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('App.Models.User.' . $this->booking->guest_user_id),
            new PrivateChannel('App.Models.User.' . $this->booking->listing->host_user_id),
        ];

        $cohostIds = Cohost::query()
            ->where('listing_id', $this->booking->listing_id)
            ->where('can_edit_listings', true)
            ->pluck('cohost_user_id');

        foreach ($cohostIds as $cohostId) {
            $channels[] = new PrivateChannel('App.Models.User.' . $cohostId);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'BookingUpdated';
    }

    public function broadcastWith(): array
    {
        return BookingResource::make($this->booking)->resolve();
    }
}
