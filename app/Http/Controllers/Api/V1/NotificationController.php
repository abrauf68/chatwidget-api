<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChatSessionResource;
use App\Models\ChatSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Powers the red "unread" badge on the Inbox sidebar link and the
     * notifications panel. Scope, deliberately: sessions on a site this
     * agent can access, that are either assigned to them or still
     * unclaimed (super admins effectively see every site's unread chats).
     * Closed chats are excluded — nothing more to action there.
     */
    public function unread(Request $request): JsonResponse
    {
        $user = $request->user();
        $siteIds = $user->accessibleSiteIds();

        $sessions = ChatSession::query()
            ->whereIn('site_id', $siteIds)
            ->where('status', '!=', 'closed')
            ->where(function ($q) use ($user) {
                $q->whereNull('assigned_agent_id')->orWhere('assigned_agent_id', $user->id);
            })
            ->withCount(['messages as unread_count' => function ($q) {
                $q->where('sender_type', 'visitor')->where('is_read', false);
            }])
            ->having('unread_count', '>', 0)
            ->with(['site', 'assignedAgent', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->orderByDesc('last_message_at')
            ->limit(20)
            ->get();

        return response()->json([
            'data' => [
                'count' => (int) $sessions->sum('unread_count'),
                'sessions' => ChatSessionResource::collection($sessions)->collection,
            ],
        ]);
    }
}
