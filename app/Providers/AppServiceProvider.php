<?php

namespace App\Providers;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Site;
use App\Observers\ChatMessageObserver;
use App\Policies\ChatSessionPolicy;
use App\Policies\SitePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(ChatSession::class, ChatSessionPolicy::class);
        Gate::policy(Site::class, SitePolicy::class);

        ChatMessage::observe(ChatMessageObserver::class);
    }
}
