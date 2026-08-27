<?php

namespace App\Http\Controllers\Api\V1\Widget;

use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Pusher\Pusher;

class WidgetBroadcastAuthController extends Controller
{
    /**
     * Visitors aren't Laravel `User` models, so they can't go through the
     * normal Sanctum-backed `Broadcast::channel()` resolution the agent
     * dashboard uses. Instead: VerifyWidgetSite has already proven the
     * site_key + domain are legitimate; here we additionally check that
     * the requested channel's session_token actually belongs to that
     * site, then sign the channel auth ourselves with the Pusher SDK.
     *
     * A visitor can therefore only ever authorize their own
     * chat.session.{token} channel — never another visitor's, and never
     * the agent-only site.{id}.queue or agent.{id}.notifications channels.
     */
    public function authorizeChannel(Request $request): JsonResponse
    {
        $site = $request->attributes->get('site');
        $channelName = $request->input('channel_name', '');
        $socketId = $request->input('socket_id');

        if (! str_starts_with($channelName, 'private-chat.session.')) {
            return response()->json(['message' => 'Forbidden channel.'], 403);
        }

        $token = substr($channelName, strlen('private-chat.session.'));

        $belongsToSite = ChatSession::where('site_id', $site->id)
            ->where('session_token', $token)
            ->exists();

        if (! $belongsToSite) {
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
}
