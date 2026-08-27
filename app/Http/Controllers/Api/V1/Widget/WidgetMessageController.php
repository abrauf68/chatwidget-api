<?php

namespace App\Http\Controllers\Api\V1\Widget;

use App\Actions\Chat\SendMessage;
use App\Actions\Chat\StoreChatAttachment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Widget\SendWidgetMessageRequest;
use App\Http\Requests\Widget\UploadWidgetAttachmentRequest;
use App\Http\Resources\ChatMessageResource;
use Illuminate\Http\JsonResponse;

class WidgetMessageController extends Controller
{
    public function store(SendWidgetMessageRequest $request, SendMessage $action): JsonResponse
    {
        $site = $request->attributes->get('site');

        $session = $site->chatSessions()
            ->where('session_token', $request->validated('session_token'))
            ->firstOrFail();

        // Deliberately no "session is closed" guard here: ChatMessageObserver
        // re-opens a closed session to 'pending' the moment a visitor message
        // lands, by design (a visitor should always be able to pick a
        // conversation back up). Blocking the message here would contradict
        // that and permanently lock the visitor out once an agent closes.
        $message = $action->handle($session, 'visitor', $request->validated('message'));

        return response()->json(['data' => new ChatMessageResource($message)], 201);
    }

    public function uploadAttachment(
        UploadWidgetAttachmentRequest $request,
        StoreChatAttachment $store,
        SendMessage $send,
    ): JsonResponse {
        $site = $request->attributes->get('site');

        $session = $site->chatSessions()
            ->where('session_token', $request->validated('session_token'))
            ->firstOrFail();

        $attachment = $store->handle($session, $request->file('attachment'));

        $message = $send->handle($session, 'visitor', (string) $request->validated('message', ''), null, $attachment);

        return response()->json(['data' => new ChatMessageResource($message)], 201);
    }
}
