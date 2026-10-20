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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 32);
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->string('extension', 10);
            $table->string('kind', 20)->index();
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('title')->nullable();
            $table->string('alt', 300)->nullable();
            $table->text('caption')->nullable();
            $table->string('folder', 80)->nullable()->index();
            $table->json('variants')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('password');
            $table->string('job_title')->nullable()->after('name');
            $table->text('bio')->nullable()->after('job_title');
            $table->foreignId('avatar_media_id')->nullable()->after('bio')->constrained('media')->nullOnDelete();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('avatar_media_id');
            $table->dropColumn(['is_active', 'job_title', 'bio', 'last_login_at', 'last_login_ip']);
        });

        Schema::dropIfExists('media');
    }
};
