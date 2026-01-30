<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    public function update(User $user, array $data, ?UploadedFile $photo): User
    {
        if ($photo) {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $path = Storage::disk('public')->putFile("profiles/{$user->id}", $photo);
            $data['profile_photo_path'] = $path;
        }

        $user->fill([
            'name' => $data['name'],
            'address' => $data['address'] ?? null,
            'profile_photo_path' => $data['profile_photo_path'] ?? $user->profile_photo_path,
        ])->save();

        return $user->fresh();
    }
}
