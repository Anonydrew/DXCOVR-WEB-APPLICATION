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
        Schema::create('article_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->foreignId('featured_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('byline')->nullable();
            $table->boolean('show_author')->default(true);
            $table->boolean('show_toc')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('reading_time')->default(1);
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });

        Schema::create('article_article_category', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_category_id')->constrained()->cascadeOnDelete();
            $table->primary(['article_id', 'article_category_id']);
        });

        Schema::create('article_tag', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['article_id', 'tag_id']);
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('icon', 40)->default('software');
            $table->text('short_description');
            $table->longText('body')->nullable();
            $table->foreignId('hero_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('hero_video_url', 500)->nullable();
            $table->json('capabilities')->nullable();
            $table->json('use_cases')->nullable();
            $table->json('process_steps')->nullable();
            $table->string('cta_heading')->nullable();
            $table->text('cta_body')->nullable();
            $table->string('cta_label', 80)->nullable();
            $table->string('cta_url', 500)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });

        Schema::create('project_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('technologies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary');
            $table->foreignId('project_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('project_type', 20)->default('client');
            $table->string('delivery_status', 20)->nullable();
            $table->string('client_name')->nullable();
            $table->boolean('is_confidential')->default(false);
            $table->string('anonymised_client_label')->nullable();
            $table->string('duration', 80)->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->foreignId('cover_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->json('video_urls')->nullable();
            $table->longText('challenge')->nullable();
            $table->longText('objectives')->nullable();
            $table->longText('approach')->nullable();
            $table->longText('solution')->nullable();
            $table->json('features')->nullable();
            $table->longText('role')->nullable();
            $table->longText('results')->nullable();
            $table->string('external_url', 500)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index(['is_featured', 'sort_order']);
        });

        Schema::create('project_service', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->primary(['project_id', 'service_id']);
        });

        Schema::create('project_technology', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technology_id')->constrained()->cascadeOnDelete();
            $table->primary(['project_id', 'technology_id']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->text('short_description');
            $table->longText('body')->nullable();
            $table->foreignId('logo_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('hero_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('product_status', 20)->default('in_development');
            $table->longText('audience')->nullable();
            $table->json('features')->nullable();
            $table->json('modules')->nullable();
            $table->json('integrations')->nullable();
            $table->string('documentation_url', 500)->nullable();
            $table->boolean('demo_requests_enabled')->default(true);
            $table->boolean('enquiries_enabled')->default(true);
            $table->boolean('pricing_visible')->default(false);
            $table->longText('pricing')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });

        Schema::create('product_service', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_id', 'service_id']);
        });

        Schema::create('product_project', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_id', 'project_id']);
        });

        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('role_title')->nullable();
            $table->text('short_bio')->nullable();
            $table->longText('bio')->nullable();
            $table->foreignId('photo_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->json('links')->nullable();
            $table->json('skills')->nullable();
            $table->json('expertise')->nullable();
            $table->json('experience')->nullable();
            $table->boolean('is_founder')->default(false)->index();
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('project_team_member', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_member_id')->constrained()->cascadeOnDelete();
            $table->primary(['project_id', 'team_member_id']);
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->text('quote');
            $table->string('author_name');
            $table->string('author_title')->nullable();
            $table->string('organisation')->nullable();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('photo_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->boolean('consent_confirmed')->default(false);
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->string('group', 40)->default('general')->index();
            $table->foreignId('service_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('project_team_member');
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('product_project');
        Schema::dropIfExists('product_service');
        Schema::dropIfExists('products');
        Schema::dropIfExists('project_technology');
        Schema::dropIfExists('project_service');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('technologies');
        Schema::dropIfExists('project_categories');
        Schema::dropIfExists('services');
        Schema::dropIfExists('article_tag');
        Schema::dropIfExists('article_article_category');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('article_categories');
    }
};
