<?php

namespace App\Http\Requests\Cohost;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCohostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'can_read_conversations' => ['sometimes', 'boolean'],
            'can_reply_messages' => ['sometimes', 'boolean'],
            'can_edit_listings' => ['sometimes', 'boolean'],
        ];
    }
}
