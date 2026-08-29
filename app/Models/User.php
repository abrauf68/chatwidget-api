<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(Site::class, 'site_user')->withTimestamps();
    }

    /**
     * Points the reset link at the Vue dashboard's /reset-password route
     * (with a query string) instead of Laravel's default Blade-route URL,
     * since there is no server-rendered "password.reset" route here.
     */
    public function sendPasswordResetNotification($token): void
    {
        $frontendUrl = rtrim(config('app.frontend_url'), '/');
        $url = $frontendUrl.'/reset-password?token='.$token.'&email='.urlencode($this->email);

        $this->notify(new \App\Notifications\ResetPasswordNotification($url));
    }

    public function assignedChatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class, 'assigned_agent_id');
    }

    /**
     * Super admins bypass the site_user pivot and can see every site.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Site IDs this agent is explicitly allowed to access.
     * Super admins are handled separately in policies (they bypass this list).
     */
    public function accessibleSiteIds(): array
    {
        return $this->isSuperAdmin()
            ? Site::query()->pluck('id')->all()
            : $this->sites()->pluck('sites.id')->all();
    }
}
