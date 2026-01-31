<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Message\StoreMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\MessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MessageController extends Controller
{
    public function index(Request $request, Conversation $conversation, MessageService $messageService)
    {
        $messages = $messageService->listForConversation($request->user(), $conversation);
        $canReply = Gate::allows('create', [Message::class, $conversation]);

        return MessageResource::collection($messages)->additional([
            'meta' => [
                'can_reply' => $canReply,
            ],
        ]);
    }

    public function store(
        StoreMessageRequest $request,
        Conversation $conversation,
        MessageService $messageService
    ) {
        $message = $messageService->create(
            $request->user(),
            $conversation,
            $request->validated('body')
        );

        return MessageResource::make($message)->response()->setStatusCode(201);
    }
}
