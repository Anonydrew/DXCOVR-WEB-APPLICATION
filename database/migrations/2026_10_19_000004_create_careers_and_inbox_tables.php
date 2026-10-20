<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('job_listings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('location');
            $table->string('work_arrangement', 20);
            $table->string('employment_type', 20);
            $table->string('employment_type_label', 80)->nullable();
            $table->text('summary');
            $table->longText('description')->nullable();
            $table->longText('responsibilities')->nullable();
            $table->longText('requirements')->nullable();
            $table->longText('nice_to_have')->nullable();
            $table->longText('benefits')->nullable();
            $table->string('salary_range', 120)->nullable();
            $table->timestamp('application_deadline')->nullable();
            $table->boolean('auto_close')->default(true);
            $table->string('application_method', 20)->default('form');
            $table->string('application_email')->nullable();
            $table->string('application_url', 500)->nullable();
            $table->text('application_instructions')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });

        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->ulid('reference')->unique();
            $table->foreignId('job_listing_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->text('cover_letter');
            $table->string('portfolio_url', 500)->nullable();
            $table->string('cv_disk', 32);
            $table->string('cv_path');
            $table->string('cv_original_name');
            $table->string('cv_mime', 100);
            $table->unsignedBigInteger('cv_size');
            $table->timestamp('consent_given_at');
            $table->string('stage', 20)->default('received');
            $table->timestamp('stage_changed_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index(['job_listing_id', 'stage']);
            $table->index(['stage', 'created_at']);
        });

        Schema::create('application_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('application_stage_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_stage', 20)->nullable();
            $table->string('to_stage', 20);
            $table->text('note')->nullable();
            $table->boolean('applicant_notified')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->ulid('reference')->unique();
            $table->string('type', 20)->default('project');
            $table->string('name');
            $table->string('organisation')->nullable();
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('project_type', 80)->nullable();
            $table->string('budget_range', 40)->nullable();
            $table->string('timeframe', 40)->nullable();
            $table->text('message');
            $table->timestamp('consent_given_at');
            $table->string('status', 20)->default('new');
            $table->timestamp('read_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('internal_notes')->nullable();
            $table->string('source_url', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('read_at');
        });

        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->string('token', 64)->unique();
            $table->timestamp('consent_given_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('source', 120)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
        Schema::dropIfExists('enquiries');
        Schema::dropIfExists('application_stage_changes');
        Schema::dropIfExists('application_notes');
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_listings');
        Schema::dropIfExists('departments');
    }
};
