<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Pusher\Pusher;

class AgentBroadcastAuthController extends Controller
{
    /**
     * Laravel's automatic `/broadcasting/auth` route (registered by passing
     * `channels:` to withRouting()) runs on the **web** middleware group —
     * it authenticates via the session cookie, not a Sanctum bearer token.
     * The dashboard is a pure token client (see AuthController::login's
     * docblock), so that route never sees it as logged in and every
     * private-channel subscription silently fails auth, which is why
     * realtime updates don't arrive. This endpoint does the same job but
     * sits behind `auth:sanctum`, so it works with the same bearer token
     * every other dashboard request already uses.
     */
    public function authorizeChannel(Request $request): JsonResponse
    {
        $user = $request->user();
        $channelName = $request->input('channel_name', '');
        $socketId = $request->input('socket_id');

        if (! $this->userCanAccessChannel($user, $channelName)) {
            return response()->json(['message' => 'Forbidden channel.'], 403);
        }

        $pusher = new Pusher(
            Config::get('broadcasting.connections.pusher.key'),
            Config::get('broadcasting.connections.pusher.secret'),
            Config::get('broadcasting.connections.pusher.app_id'),
            Config::get('broadcasting.connections.pusher.options', [])
        );

        $auth = $pusher->authorizeChannel($channelName, $socketId);

        return response()->json(json_decode($auth, true));
    }

    /**
     * Mirrors the authorization rules in routes/channels.php — kept as a
     * single source of truth would be nicer, but channels.php callbacks
     * are wired to the guard-based resolver we're deliberately bypassing,
     * so the three patterns are re-checked directly against $user here.
     */
    protected function userCanAccessChannel($user, string $channelName): bool
    {
        if (preg_match('/^private-site\.(\d+)\.queue$/', $channelName, $m)) {
            $siteId = (int) $m[1];

            return $user->isSuperAdmin() || $user->sites()->where('sites.id', $siteId)->exists();
        }

        if (preg_match('/^private-chat\.session\.(.+)$/', $channelName, $m)) {
            $session = ChatSession::where('session_token', $m[1])->first();

            if (! $session) {
                return false;
            }

            return $user->isSuperAdmin() || $session->assigned_agent_id === $user->id;
        }

        if (preg_match('/^private-agent\.(\d+)\.notifications$/', $channelName, $m)) {
            return $user->id === (int) $m[1];
        }

        return false;
    }
}
