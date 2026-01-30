<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Conversation\StoreConversationRequest;
use App\Http\Resources\ConversationResource;
use App\Services\ConversationService;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request, ConversationService $conversationService)
    {
        $conversations = $conversationService->listForUser($request->user());

        return ConversationResource::collection($conversations);
    }

    public function store(
        StoreConversationRequest $request,
        ConversationService $conversationService
    ) {
        $conversation = $conversationService->create(
            $request->user(),
            (int) $request->validated('listing_id')
        );

        return ConversationResource::make($conversation)->response()->setStatusCode(201);
    }
}
