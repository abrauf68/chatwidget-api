<?php

namespace App\Actions\Chat;

use App\Models\ChatSession;
use Illuminate\Http\UploadedFile;

class StoreChatAttachment
{
    /**
     * @return array{path: string, name: string, mime: string, size: int}
     */
    public function handle(ChatSession $session, UploadedFile $file): array
    {
        $path = $file->store("chat-attachments/{$session->id}", 'public');

        return [
            'path' => $path,
            'name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ];
    }
}
