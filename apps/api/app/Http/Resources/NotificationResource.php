<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = $this->data ?? [];

        return [
            'id' => $this->id,
            'type' => $data['type'] ?? $this->type,
            'title' => $data['title'] ?? null,
            'body' => $data['body'] ?? null,
            'action_url' => $data['action_url'] ?? null,
            'data' => $data,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'is_read' => (bool) $this->read_at,
        ];
    }
}
