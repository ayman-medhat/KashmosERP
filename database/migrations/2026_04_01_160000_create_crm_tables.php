<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_accounts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_profile_id')->nullable()->constrained('company_profiles')->nullOnDelete();
            $table->string('code', 50)->unique();
            $table->json('name_translations');
            $table->string('industry', 100)->nullable()->index();
            $table->string('website')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone', 50)->nullable()->index();
            $table->json('address_translations')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->timestamp('next_follow_up_at')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('crm_contacts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_profile_id')->nullable()->constrained('company_profiles')->nullOnDelete();
            $table->foreignId('crm_account_id')->nullable()->constrained('crm_accounts')->nullOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100)->nullable();
            $table->string('job_title', 150)->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone', 50)->nullable()->index();
            $table->json('address_translations')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->timestamp('next_follow_up_at')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('crm_lead_sources', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 50)->unique();
            $table->json('name_translations');
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_system')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('crm_pipeline_stages', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 50)->unique();
            $table->json('name_translations');
            $table->unsignedInteger('stage_order')->index();
            $table->string('color', 20)->default('#3B82F6');
            $table->unsignedTinyInteger('default_probability')->default(0);
            $table->boolean('is_won_stage')->default(false)->index();
            $table->boolean('is_lost_stage')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_system')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('crm_leads', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_profile_id')->nullable()->constrained('company_profiles')->nullOnDelete();
            $table->string('lead_no', 50)->unique();
            $table->string('name', 255);
            $table->string('email')->nullable()->index();
            $table->string('phone', 50)->nullable()->index();
            $table->foreignId('crm_account_id')->nullable()->constrained('crm_accounts')->nullOnDelete();
            $table->foreignId('crm_contact_id')->nullable()->constrained('crm_contacts')->nullOnDelete();
            $table->foreignId('crm_lead_source_id')->nullable()->constrained('crm_lead_sources')->nullOnDelete();
            $table->string('status', 50)->default('new')->index();
            $table->decimal('expected_value', 19, 4)->nullable();
            $table->date('expected_close_date')->nullable()->index();
            $table->timestamp('qualified_at')->nullable();
            $table->timestamp('disqualified_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->unsignedBigInteger('converted_crm_opportunity_id')->nullable()->index();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->timestamp('next_follow_up_at')->nullable()->index();
            $table->json('details')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index('owner_id');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('crm_opportunities', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_profile_id')->nullable()->constrained('company_profiles')->nullOnDelete();
            $table->string('opportunity_no', 50)->unique();
            $table->string('name', 255);
            $table->foreignId('crm_account_id')->nullable()->constrained('crm_accounts')->nullOnDelete();
            $table->foreignId('crm_contact_id')->nullable()->constrained('crm_contacts')->nullOnDelete();
            $table->foreignId('crm_lead_id')->nullable()->constrained('crm_leads')->nullOnDelete();
            $table->foreignId('crm_pipeline_stage_id')->constrained('crm_pipeline_stages');
            $table->string('status', 50)->default('open')->index();
            $table->unsignedTinyInteger('probability')->default(0);
            $table->decimal('expected_value', 19, 4)->default(0);
            $table->date('expected_close_date')->nullable()->index();
            $table->timestamp('won_at')->nullable();
            $table->timestamp('lost_at')->nullable();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->timestamp('next_follow_up_at')->nullable()->index();
            $table->json('details')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index('owner_id');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('crm_activities', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_profile_id')->nullable()->constrained('company_profiles')->nullOnDelete();
            $table->morphs('subject');
            $table->foreignId('crm_contact_id')->nullable()->constrained('crm_contacts')->nullOnDelete();
            $table->string('title', 255);
            $table->string('activity_type', 50)->default('task')->index();
            $table->string('status', 50)->default('scheduled')->index();
            $table->string('priority', 20)->default('normal')->index();
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable()->index();
            $table->json('details')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index('owner_id');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('crm_tasks', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_profile_id')->nullable()->constrained('company_profiles')->nullOnDelete();
            $table->morphs('subject');
            $table->foreignId('crm_contact_id')->nullable()->constrained('crm_contacts')->nullOnDelete();
            $table->string('title', 255);
            $table->longText('description')->nullable();
            $table->string('status', 50)->default('open')->index();
            $table->string('priority', 20)->default('normal')->index();
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable()->index();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index('owner_id');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('crm_notes', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_profile_id')->nullable()->constrained('company_profiles')->nullOnDelete();
            $table->morphs('subject');
            $table->longText('note');
            $table->string('visibility', 20)->default('internal')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index('created_by');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('crm_attachments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_profile_id')->nullable()->constrained('company_profiles')->nullOnDelete();
            $table->morphs('subject');
            $table->string('disk', 50)->default('public');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index('created_by');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('crm_emails', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_profile_id')->nullable()->constrained('company_profiles')->nullOnDelete();
            $table->morphs('subject');
            $table->foreignId('crm_contact_id')->nullable()->constrained('crm_contacts')->nullOnDelete();
            $table->string('direction', 20)->default('outbound')->index();
            $table->string('status', 50)->default('sent')->index();
            $table->string('subject_line');
            $table->longText('body')->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index('owner_id');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('crm_calls', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_profile_id')->nullable()->constrained('company_profiles')->nullOnDelete();
            $table->morphs('subject');
            $table->foreignId('crm_contact_id')->nullable()->constrained('crm_contacts')->nullOnDelete();
            $table->string('direction', 20)->default('outbound')->index();
            $table->string('status', 50)->default('completed')->index();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('ended_at')->nullable()->index();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->text('summary')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index('owner_id');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('crm_tags', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 100)->unique();
            $table->string('color', 20)->default('#6B7280');
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('crm_taggables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_tag_id')->constrained('crm_tags')->cascadeOnDelete();
            $table->morphs('taggable');
            $table->timestamps();

            $table->unique(['crm_tag_id', 'taggable_type', 'taggable_id'], 'crm_taggables_unique');
        });

        Schema::create('crm_assignment_rules', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('entity_type', 50)->index();
            $table->unsignedInteger('priority')->default(100)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('conditions')->nullable();
            $table->string('assignment_strategy', 50)->default('round_robin');
            $table->json('assigned_user_ids')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('crm_stage_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_opportunity_id')->constrained('crm_opportunities')->cascadeOnDelete();
            $table->foreignId('from_crm_pipeline_stage_id')->nullable()->constrained('crm_pipeline_stages')->nullOnDelete();
            $table->foreignId('to_crm_pipeline_stage_id')->constrained('crm_pipeline_stages');
            $table->unsignedTinyInteger('from_probability')->nullable();
            $table->unsignedTinyInteger('to_probability')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at')->index();
            $table->timestamps();
        });

        Schema::create('crm_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->morphs('subject');
            $table->string('action', 100)->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('context')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('logged_at')->index();
            $table->timestamps();
        });

        Schema::table('crm_leads', function (Blueprint $table): void {
            $table->foreign('converted_crm_opportunity_id')
                ->references('id')
                ->on('crm_opportunities')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('crm_leads', function (Blueprint $table): void {
            $table->dropForeign(['converted_crm_opportunity_id']);
        });

        Schema::dropIfExists('crm_activity_logs');
        Schema::dropIfExists('crm_stage_history');
        Schema::dropIfExists('crm_assignment_rules');
        Schema::dropIfExists('crm_taggables');
        Schema::dropIfExists('crm_tags');
        Schema::dropIfExists('crm_calls');
        Schema::dropIfExists('crm_emails');
        Schema::dropIfExists('crm_attachments');
        Schema::dropIfExists('crm_notes');
        Schema::dropIfExists('crm_tasks');
        Schema::dropIfExists('crm_activities');
        Schema::dropIfExists('crm_opportunities');
        Schema::dropIfExists('crm_leads');
        Schema::dropIfExists('crm_pipeline_stages');
        Schema::dropIfExists('crm_lead_sources');
        Schema::dropIfExists('crm_contacts');
        Schema::dropIfExists('crm_accounts');
    }
};
