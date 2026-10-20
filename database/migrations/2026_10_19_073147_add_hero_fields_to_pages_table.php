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
        Schema::table('pages', function (Blueprint $table) {
            $table->foreignId('hero_media_id')->nullable()->after('body')->constrained('media')->nullOnDelete();
            $table->string('hero_eyebrow', 60)->nullable()->after('hero_media_id');
            $table->string('hero_cta_label', 40)->nullable()->after('hero_eyebrow');
            $table->string('hero_cta_url', 500)->nullable()->after('hero_cta_label');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hero_media_id');
            $table->dropColumn(['hero_eyebrow', 'hero_cta_label', 'hero_cta_url']);
        });
    }
};
