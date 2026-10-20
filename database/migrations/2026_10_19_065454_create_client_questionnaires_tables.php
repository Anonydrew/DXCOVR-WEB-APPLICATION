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
        Schema::create('client_questionnaires', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 26)->unique();
            $table->string('client_name');
            $table->string('client_email')->nullable();
            $table->string('client_company')->nullable();
            $table->string('project_name');
            $table->text('intro_message')->nullable();
            $table->json('sections');
            $table->foreignId('enquiry_id')->nullable()->constrained()->nullOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->text('token');
            $table->string('status', 20)->default('pending')->index();
            $table->json('answers')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('first_opened_at')->nullable();
            $table->timestamp('last_saved_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('internal_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('client_questionnaire_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_questionnaire_id')->constrained()->cascadeOnDelete();
            $table->string('disk', 40);
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_questionnaire_files');
        Schema::dropIfExists('client_questionnaires');
    }
};
