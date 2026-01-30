<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Message\StoreMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Services\MessageService;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request, Conversation $conversation, MessageService $messageService)
    {
        $messages = $messageService->listForConversation($request->user(), $conversation);

        return MessageResource::collection($messages);
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
