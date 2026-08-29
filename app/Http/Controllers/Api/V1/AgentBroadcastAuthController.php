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

            return $user->isSuperAdmin() || $user->sites()->where('sites.id', $session->site_id)->exists();
        }

        if (preg_match('/^private-agent\.(\d+)\.notifications$/', $channelName, $m)) {
            return $user->id === (int) $m[1];
        }

        return false;
    }
}
