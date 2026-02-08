<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Conversation\StoreConversationRequest;
use App\Http\Resources\ConversationResource;
use App\Services\ConversationService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;

#[Group('Conversations', 'Conversations liées aux annonces')]
class ConversationController extends Controller
{
    /**
     * Liste des conversations.
     */
    public function index(Request $request, ConversationService $conversationService)
    {
        $conversations = $conversationService->listForGuest($request->user());

        return ConversationResource::collection($conversations);
    }

    /**
     * Liste des conversations côté hôte.
     */
    public function hostIndex(Request $request, ConversationService $conversationService)
    {
        $listingId = $request->integer('listing_id');
        $conversations = $conversationService->listForHost($request->user(), $listingId ?: null);

        return ConversationResource::collection($conversations);
    }

    /**
     * Créer une conversation.
     */
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
