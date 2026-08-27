<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ChatSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'session_token',
        'visitor_name',
        'visitor_email',
        'assigned_agent_id',
        'status',
        'source_page_url',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public static function generateToken(): string
    {
        do {
            $token = (string) Str::uuid();
        } while (static::where('session_token', $token)->exists());

        return $token;
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->orderBy('created_at');
    }

    public function latestMessage(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->latestOfMany();
    }

    public function isAssigned(): bool
    {
        return ! is_null($this->assigned_agent_id);
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /** Realtime channel name used for private-chat.{session_token} */
    public function channelName(): string
    {
        return "chat.session.{$this->session_token}";
    }
}
