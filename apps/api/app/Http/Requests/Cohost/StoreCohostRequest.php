<?php

namespace App\Http\Requests\Cohost;

use Illuminate\Foundation\Http\FormRequest;

class StoreCohostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cohost_user_id' => ['required', 'integer', 'exists:users,id'],
            'can_read_conversations' => ['sometimes', 'boolean'],
            'can_reply_messages' => ['sometimes', 'boolean'],
            'can_edit_listings' => ['sometimes', 'boolean'],
        ];
    }
}
