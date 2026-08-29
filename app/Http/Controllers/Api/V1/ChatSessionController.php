<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Chat\ClaimChatSession;
use App\Actions\Chat\CloseChatSession;
use App\Actions\Chat\SendMessage;
use App\Actions\Chat\StoreChatAttachment;
use App\Actions\Chat\TransferChatSession;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\ReplyToChatRequest;
use App\Http\Requests\Chat\TransferChatRequest;
use App\Http\Requests\Chat\UploadAttachmentRequest;
use App\Http\Resources\ChatMessageResource;
use App\Http\Resources\ChatSessionResource;
use App\Models\ChatSession;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatSessionController extends Controller
{
    /**
     * GET /sites/{site}/chats — list sessions for a site, scoped by
     * ChatSessionPolicy via the site access check on the site itself.
     */
    public function indexForSite(Request $request, Site $site): JsonResponse
    {
        $this->authorize('view', $site);

        $query = $site->chatSessions()
            ->with(['assignedAgent', 'messages' => fn ($q) => $q->latest()->limit(1)])
            // Real unread count (not just "is the single latest message
            // unread") — used for the sidebar badge and per-row indicator.
            ->withCount(['messages as unread_count' => function ($q) {
                $q->where('sender_type', 'visitor')->where('is_read', false);
            }])
            ->orderByDesc('last_message_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $sessions = $query->paginate($request->integer('per_page', 20));

        return response()->json([
            'data' => ChatSessionResource::collection($sessions)->collection,
            'meta' => [
                'current_page' => $sessions->currentPage(),
                'per_page' => $sessions->perPage(),
                'total' => $sessions->total(),
            ],
        ]);
    }

    public function show(ChatSession $chat): JsonResponse
    {
        $this->authorize('view', $chat);

        // Opening a conversation is what "reading" it means here — clear
        // its unread count so the sidebar/list badges reflect reality.
        $chat->messages()
            ->where('sender_type', 'visitor')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $chat->load(['site', 'assignedAgent', 'messages.sender']);

        return response()->json(['data' => new ChatSessionResource($chat)]);
    }

    public function reply(ReplyToChatRequest $request, ChatSession $chat, SendMessage $action, ClaimChatSession $claim): JsonResponse
    {
        // Replying to an unclaimed chat *is* how an agent claims it —
        // no separate "Claim" click needed. ChatSessionPolicy::reply
        // already guarantees the chat is either unassigned or already
        // this agent's, so this is a no-op once it's already claimed.
        if (! $chat->isAssigned()) {
            $claim->handle($chat, $request->user());
        }

        $message = $action->handle($chat, 'agent', $request->validated('message'), $request->user());

        return response()->json(['data' => new ChatMessageResource($message)], 201);
    }

    public function uploadAttachment(
        UploadAttachmentRequest $request,
        ChatSession $chat,
        StoreChatAttachment $store,
        SendMessage $send,
        ClaimChatSession $claim,
    ): JsonResponse {
        if (! $chat->isAssigned()) {
            $claim->handle($chat, $request->user());
        }

        $attachment = $store->handle($chat, $request->file('attachment'));

        $message = $send->handle(
            $chat,
            'agent',
            (string) $request->validated('message', ''),
            $request->user(),
            $attachment,
        );

        return response()->json(['data' => new ChatMessageResource($message)], 201);
    }

    public function claim(ChatSession $chat, ClaimChatSession $action): JsonResponse
    {
        $this->authorize('claim', $chat);

        $claimed = $action->handle($chat, request()->user());

        return $claimed
            ? response()->json(['data' => new ChatSessionResource($chat->fresh(['assignedAgent']))])
            : response()->json(['message' => 'This chat was already claimed by another agent.'], 409);
    }

    public function transfer(TransferChatRequest $request, ChatSession $chat, TransferChatSession $action): JsonResponse
    {
        $toAgent = User::findOrFail($request->validated('agent_id'));

        $session = $action->handle($chat, $request->user(), $toAgent);

        return response()->json(['data' => new ChatSessionResource($session->load('assignedAgent'))]);
    }

    public function close(ChatSession $chat, CloseChatSession $action): JsonResponse
    {
        $this->authorize('close', $chat);

        $session = $action->handle($chat);

        return response()->json(['data' => new ChatSessionResource($session)]);
    }
}
