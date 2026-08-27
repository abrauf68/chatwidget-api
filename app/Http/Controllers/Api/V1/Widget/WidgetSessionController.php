<?php

namespace App\Http\Controllers\Api\V1\Widget;

use App\Actions\Widget\StartWidgetSession;
use App\Http\Controllers\Controller;
use App\Http\Requests\Widget\StartSessionRequest;
use App\Http\Resources\ChatSessionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WidgetSessionController extends Controller
{
    public function store(StartSessionRequest $request, StartWidgetSession $action): JsonResponse
    {
        $site = $request->attributes->get('site');

        $session = $action->handle($site, $request->validated());

        return response()->json(['data' => new ChatSessionResource($session)], 201);
    }

    /**
     * GET /api/v1/widget/session/{token} — full history for the widget to
     * hydrate on page reload. Realtime (Pusher) only ever delivers *new*
     * messages; history always comes from here (architecture doc 13.4).
     */
    public function show(Request $request, string $token): JsonResponse
    {
        $site = $request->attributes->get('site');

        $session = $site->chatSessions()
            ->with(['messages' => fn ($q) => $q->orderBy('created_at')])
            ->where('session_token', $token)
            ->firstOrFail();

        return response()->json(['data' => new ChatSessionResource($session)]);
    }
}
