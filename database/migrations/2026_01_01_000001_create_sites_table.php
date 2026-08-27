<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('site_key')->unique();
            $table->string('site_secret');
            $table->string('allowed_domain');
            $table->enum('widget_mode', ['bubble', 'full_page', 'both'])->default('bubble');
            $table->string('widget_color')->nullable();
            $table->string('widget_logo_url')->nullable();
            $table->string('widget_company_name')->nullable();
            $table->text('widget_company_details')->nullable();
            $table->string('widget_greeting')->nullable();
            $table->json('widget_suggested_questions')->nullable();
            $table->enum('widget_position', ['bottom-right', 'bottom-left'])->default('bottom-right');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
