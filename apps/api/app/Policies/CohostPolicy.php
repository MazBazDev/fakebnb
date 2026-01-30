<?php

namespace App\Policies;

use App\Models\Cohost;
use App\Models\User;

class CohostPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isHost($user);
    }

    public function create(User $user): bool
    {
        return $this->isHost($user);
    }

    public function update(User $user, Cohost $cohost): bool
    {
        return $this->isHost($user) && $cohost->host_user_id === $user->id;
    }

    public function delete(User $user, Cohost $cohost): bool
    {
        return $this->isHost($user) && $cohost->host_user_id === $user->id;
    }

    private function isHost(User $user): bool
    {
        return $user->roles()->where('name', 'host')->exists();
    }
}
