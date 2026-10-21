<?php

use App\Support\Onboarding\QuestionnaireTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The editable questionnaire template, plus a copy of the questions on
     * each questionnaire so editing the template never changes a form that
     * has already been sent.
     */
    public function up(): void
    {
        Schema::create('questionnaire_sections', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->string('title', 120);
            $table->text('intro')->nullable();
            $table->boolean('always_included')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('questionnaire_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionnaire_section_id')->constrained()->cascadeOnDelete();
            $table->string('key', 60);
            $table->string('type', 20);
            $table->string('label', 500);
            $table->string('hint', 500)->nullable();
            $table->boolean('is_required')->default(false);
            $table->json('options')->nullable();
            $table->unsignedSmallInteger('max_length')->nullable();
            $table->unsignedTinyInteger('max_choices')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['questionnaire_section_id', 'key']);
        });

        // Text, not JSON: native JSON columns may reorder object keys, and the
        // order of sections and questions matters.
        Schema::table('client_questionnaires', function (Blueprint $table) {
            $table->longText('form')->nullable()->after('sections');
        });

        // Start the builder from the original questionnaire, and record it on
        // existing questionnaires, which were all created from it.
        QuestionnaireTemplate::seedDefaults();

        DB::table('client_questionnaires')->whereNull('form')->update([
            'form' => json_encode(QuestionnaireTemplate::definition()),
        ]);
    }

    public function down(): void
    {
        Schema::table('client_questionnaires', function (Blueprint $table) {
            $table->dropColumn('form');
        });

        Schema::dropIfExists('questionnaire_questions');
        Schema::dropIfExists('questionnaire_sections');
    }
};
