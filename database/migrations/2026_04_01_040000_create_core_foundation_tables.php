<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('group')->index();
            $table->string('key');
            $table->json('value')->nullable();
            $table->string('type')->default('string');
            $table->boolean('is_public')->default(false)->index();
            $table->timestamps();

            $table->unique(['group', 'key']);
        });

        Schema::create('company_profiles', function (Blueprint $table): void {
            $table->id();
            $table->json('name_translations');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->json('address_translations')->nullable();
            $table->string('tax_number')->nullable()->index();
            $table->string('currency_code', 3)->default('EGP');
            $table->string('timezone')->default('Africa/Cairo');
            $table->timestamps();
        });

        Schema::create('theme_presets', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('mode')->default('system');
            $table->json('palette');
            $table->boolean('is_default')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('user_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5)->default('en');
            $table->string('theme_mode')->default('system');
            $table->string('theme_key')->default('amber');
            $table->boolean('sidebar_collapsed')->default(false);
            $table->timestamps();

            $table->unique('user_id');
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('auditable');
            $table->string('event')->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('user_preferences');
        Schema::dropIfExists('theme_presets');
        Schema::dropIfExists('company_profiles');
        Schema::dropIfExists('settings');
    }
};
