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
            // True unread total (not just whatever the latest-message-only
            // eager load below would give us) — ChatSessionResource prefers
            // this attribute when it's present.
            ->withCount(['messages as unread_count' => function ($q) {
                $q->where('sender_type', 'visitor')->where('is_read', false);
            }])
            ->with(['assignedAgent', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->orderByDesc('last_message_at');

        // A claimed chat belongs to exactly one agent — once it's assigned,
        // nobody else on the site sees it in their inbox at all (matches
        // ChatSessionPolicy::view). Unclaimed chats stay visible to
        // everyone so the team can pick them up. Admins see everything.
        if (! $request->user()->isSuperAdmin()) {
            $query->where(function ($q) use ($request) {
                $q->whereNull('assigned_agent_id')->orWhere('assigned_agent_id', $request->user()->id);
            });
        }

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

        // Opening a conversation is how an agent "reads" it — flip any
        // unread visitor messages over so the unread badges (list item +
        // sidebar) clear immediately for this session.
        $chat->messages()->where('sender_type', 'visitor')->where('is_read', false)->update(['is_read' => true]);

        $chat->load(['site', 'assignedAgent', 'messages.sender']);

        return response()->json(['data' => new ChatSessionResource($chat)]);
    }

    public function reply(
        ReplyToChatRequest $request,
        ChatSession $chat,
        SendMessage $action,
        ClaimChatSession $claim,
    ): JsonResponse {
        // First reply from any agent silently claims an unassigned chat —
        // no separate "Claim" click required (see architecture doc 13.2 for
        // why the DB-level whereNull() claim is still used underneath, so
        // this stays race-safe if two agents reply at the same instant).
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
