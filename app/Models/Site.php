<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Site extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'site_key',
        'site_secret',
        'allowed_domain',
        'widget_mode',
        'widget_color',
        'widget_logo_url',
        'widget_company_name',
        'widget_company_details',
        'widget_greeting',
        'widget_suggested_questions',
        'widget_position',
        'status',
    ];

    protected $hidden = [
        'site_secret',
    ];

    protected function casts(): array
    {
        return [
            'widget_suggested_questions' => 'array',
        ];
    }

    /**
     * Generate a unique site_key / site_secret pair for a new site.
     * Called from the CreateSite action rather than a static boot hook,
     * so the action stays the single source of truth for site creation.
     */
    public static function generateKey(): string
    {
        do {
            $key = Str::random(24);
        } while (static::where('site_key', $key)->exists());

        return $key;
    }

    public static function generateSecret(): string
    {
        return Str::random(40);
    }

    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'site_user')->withTimestamps();
    }

    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
