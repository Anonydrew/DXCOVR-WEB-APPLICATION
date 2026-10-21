<?php

namespace Tests\Feature;

use App\Enums\QuestionnaireStatus;
use App\Models\ClientQuestionnaire;
use App\Models\QuestionnaireQuestion;
use App\Models\QuestionnaireSection;
use App\Support\Onboarding\QuestionnaireForm;
use App\Support\Onboarding\QuestionnaireTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionnaireBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->syncPermissions();
    }

    protected function section(string $key): QuestionnaireSection
    {
        return QuestionnaireSection::query()->where('key', $key)->firstOrFail();
    }

    protected function question(string $section, string $key): QuestionnaireQuestion
    {
        return $this->section($section)->questions()->where('key', $key)->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    protected function newSectionWithChoiceQuestion(): array
    {
        $this->post(route('admin.onboarding.sections.store'), ['title' => 'Hosting & support', 'intro' => 'After launch.', 'always_included' => '0'])->assertRedirect();
        $section = $this->section('hosting_support');

        $this->post(route('admin.onboarding.questions.store'), [
            'questionnaire_section_id' => $section->id,
            'type' => 'checkboxes',
            'label' => 'Which support do you need?',
            'is_required' => '1',
            'max_choices' => 2,
            'options' => [['label' => 'Hosting', 'value' => ''], ['label' => 'Training', 'value' => ''], ['label' => '', 'value' => ''], ['label' => 'Monthly updates', 'value' => '']],
        ])->assertRedirect();

        return ['section' => $section, 'question' => $section->questions()->sole()];
    }

    public function test_the_builder_starts_with_the_original_questionnaire(): void
    {
        $this->assertSame(QuestionnaireTemplate::fingerprint(QuestionnaireForm::defaults()), QuestionnaireTemplate::fingerprint(QuestionnaireTemplate::definition()));

        $this->actingAsRole('Administrator');
        $this->get(route('admin.onboarding.questions.index'))
            ->assertOk()
            ->assertSee('Look &amp; feel', false)
            ->assertSee('Which styles feel closest to what you want?')
            ->assertSee('Built in');
    }

    public function test_editor_screens_render(): void
    {
        $this->actingAsRole('Administrator');
        $project = $this->section('project');

        $this->get(route('admin.onboarding.questions.create', ['section' => $project->id]))->assertOk()->assertSee('Answer type');
        $this->get(route('admin.onboarding.questions.edit', $this->question('project', 'types')))->assertOk()->assertSee('Web application')->assertSee('Add option');
        $this->get(route('admin.onboarding.questions.edit', $this->question('final', 'consent')))->assertOk()->assertSee('You can reword it, but not delete it');
        $this->get(route('admin.onboarding.sections.create'))->assertOk();
        $this->get(route('admin.onboarding.sections.edit', $project))->assertOk()->assertSee('Always include this section');
        $this->get(route('admin.onboarding.create'))->assertOk()->assertSee('“About you” and “Anything else” are always included.', false);
    }

    public function test_roles_without_onboarding_management_cannot_edit_questions(): void
    {
        $this->actingAsRole('Editor');

        $this->get(route('admin.onboarding.questions.index'))->assertForbidden();
        $this->post(route('admin.onboarding.sections.store'), ['title' => 'X'])->assertForbidden();
        $this->delete(route('admin.onboarding.questions.destroy', $this->question('project', 'goals')))->assertForbidden();
    }

    public function test_new_questions_reach_new_questionnaires_and_are_validated(): void
    {
        $this->actingAsRole('Administrator');
        ['question' => $question] = $this->newSectionWithChoiceQuestion();

        $this->assertSame('which_support_do_you_need', $question->key);
        $this->assertSame([['value' => 'hosting', 'label' => 'Hosting'], ['value' => 'training', 'label' => 'Training'], ['value' => 'monthly_updates', 'label' => 'Monthly updates']], $question->options);

        $questionnaire = ClientQuestionnaire::factory()->create(['sections' => array_keys((new ClientQuestionnaire)->optionalSections())]);
        $this->assertArrayHasKey('hosting_support', $questionnaire->formSections());

        $this->get(route('onboarding.show', ['token' => $questionnaire->token, 'step' => 'hosting_support']))
            ->assertOk()
            ->assertSee('Which support do you need?')
            ->assertSee('Monthly updates');

        $this->post(route('onboarding.update', $questionnaire->token), ['answers' => ['hosting_support' => ['which_support_do_you_need' => ['hosting', 'free_lunch']]]])
            ->assertSessionHasErrors('answers.hosting_support.which_support_do_you_need.1');
        $this->post(route('onboarding.update', $questionnaire->token), ['answers' => ['hosting_support' => ['which_support_do_you_need' => ['hosting', 'training', 'monthly_updates']]]])
            ->assertSessionHasErrors('answers.hosting_support.which_support_do_you_need');

        $this->post(route('onboarding.update', $questionnaire->token), [
            'action' => 'submit',
            'answers' => [
                'about' => ['contact_name' => 'Ama', 'contact_email' => 'ama@example.com'],
                'project' => ['summary' => 'A website.'],
                'final' => ['consent' => '1'],
            ],
        ])->assertSessionHasErrors('answers.hosting_support.which_support_do_you_need');

        $this->post(route('onboarding.update', $questionnaire->token), ['answers' => ['hosting_support' => ['which_support_do_you_need' => ['training']]]])->assertSessionHasNoErrors();
        $this->assertSame(['training'], $questionnaire->fresh()->answer('hosting_support.which_support_do_you_need'));
    }

    public function test_sent_questionnaires_keep_their_questions_until_updated(): void
    {
        $this->actingAsRole('Administrator');
        $questionnaire = ClientQuestionnaire::factory()->create([
            'sections' => ['project', 'audience'],
            'answers' => ['project' => ['summary' => 'Keep me', 'goals' => ['sell']], 'audience' => ['regions' => 'Accra']],
        ]);

        // Reword a question, delete another and add a new optional section.
        $summary = $this->question('project', 'summary');
        $this->put(route('admin.onboarding.questions.update', $summary), ['questionnaire_section_id' => $summary->questionnaire_section_id, 'type' => 'textarea', 'label' => 'Tell us about the project', 'is_required' => '1'])->assertRedirect();
        $this->delete(route('admin.onboarding.questions.destroy', $this->question('project', 'goals')))->assertRedirect();
        $this->newSectionWithChoiceQuestion();

        $questionnaire->refresh();
        $this->assertSame('Describe the project in a few sentences', $questionnaire->definition()['project']['fields']['summary']['label']);
        $this->assertArrayHasKey('goals', $questionnaire->formSections()['project']['fields']);
        $this->assertTrue($questionnaire->usesOutdatedQuestions());

        $this->get(route('admin.onboarding.show', $questionnaire))->assertSee('The questions have changed since this questionnaire was created');

        $this->post(route('admin.onboarding.refresh-questions', $questionnaire))->assertRedirect();
        $questionnaire->refresh();

        $this->assertFalse($questionnaire->usesOutdatedQuestions());
        $this->assertSame('Tell us about the project', $questionnaire->formSections()['project']['fields']['summary']['label']);
        $this->assertSame(['project' => ['summary' => 'Keep me'], 'audience' => ['regions' => 'Accra']], $questionnaire->answers);
        // Sections the client was not given stay off; sections added since are included.
        $this->assertSame(['project', 'audience', 'hosting_support'], $questionnaire->sections);
        $this->assertDatabaseHas('audit_logs', ['action' => 'onboarding.questions_refreshed']);
    }

    public function test_submitted_questionnaires_are_not_switched_to_new_questions(): void
    {
        $this->actingAsRole('Administrator');
        $questionnaire = ClientQuestionnaire::factory()->submitted()->create();
        $this->delete(route('admin.onboarding.questions.destroy', $this->question('project', 'goals')));

        $this->post(route('admin.onboarding.refresh-questions', $questionnaire))->assertSessionHas('error');
        $this->assertArrayHasKey('goals', $questionnaire->fresh()->definition()['project']['fields']);
        $this->assertSame(QuestionnaireStatus::Submitted, $questionnaire->fresh()->status);
    }

    public function test_rewording_keeps_question_keys_and_option_values(): void
    {
        $this->actingAsRole('Administrator');
        $question = $this->question('logistics', 'budget');
        $options = $question->options;
        $options[0]['label'] = 'Under GH₵ 10,000';

        $this->put(route('admin.onboarding.questions.update', $question), [
            'questionnaire_section_id' => $question->questionnaire_section_id,
            'type' => 'radio',
            'label' => 'Roughly what can you invest?',
            'options' => $options,
        ])->assertRedirect();

        $question->refresh();
        $this->assertSame('budget', $question->key);
        $this->assertSame($options, $question->options);
        $this->assertSame('Roughly what can you invest?', QuestionnaireTemplate::definition()['logistics']['fields']['budget']['label']);
    }

    public function test_built_in_sections_and_questions_cannot_be_removed_or_retyped(): void
    {
        $this->actingAsRole('Administrator');
        $email = $this->question('about', 'contact_email');

        $this->delete(route('admin.onboarding.questions.destroy', $email))->assertSessionHas('error');
        $this->delete(route('admin.onboarding.sections.destroy', $this->section('final')))->assertSessionHas('error');
        $this->put(route('admin.onboarding.sections.update', $this->section('about')), ['title' => 'Who you are', 'always_included' => '0'])->assertRedirect();

        $this->put(route('admin.onboarding.questions.update', $email), [
            'questionnaire_section_id' => $this->section('project')->id,
            'type' => 'text',
            'label' => 'Your best email address',
        ])->assertRedirect();

        $email->refresh();
        $this->assertSame(['email', 'about', 'Your best email address'], [$email->type, $email->section->key, $email->label]);
        $this->assertTrue($this->section('about')->always_included);
        $this->assertSame('Who you are', $this->section('about')->title);
        $this->assertDatabaseHas('questionnaire_sections', ['key' => 'final']);
    }

    public function test_question_rules_depend_on_the_type(): void
    {
        $this->actingAsRole('Administrator');
        $section = $this->section('project');

        $this->post(route('admin.onboarding.questions.store'), ['questionnaire_section_id' => $section->id, 'type' => 'radio', 'label' => 'Pick one', 'options' => [['label' => 'Only one', 'value' => '']]])
            ->assertSessionHasErrors('options');
        $this->post(route('admin.onboarding.questions.store'), ['questionnaire_section_id' => $section->id, 'type' => 'uploads', 'label' => 'More files'])
            ->assertSessionHasErrors('type');
        $this->post(route('admin.onboarding.questions.store'), ['questionnaire_section_id' => $section->id, 'type' => 'styles', 'label' => 'Styles again', 'max_choices' => 99])
            ->assertSessionHasErrors('max_choices');

        $this->assertSame(5, $section->questions()->count());
    }

    public function test_deleting_a_section_removes_its_questions_and_sections_can_be_reordered(): void
    {
        $this->actingAsRole('Administrator');
        $audience = $this->section('audience');

        $this->delete(route('admin.onboarding.sections.destroy', $audience))->assertRedirect(route('admin.onboarding.questions.index'));
        $this->assertDatabaseMissing('questionnaire_sections', ['key' => 'audience']);
        $this->assertDatabaseMissing('questionnaire_questions', ['questionnaire_section_id' => $audience->id]);

        $order = QuestionnaireSection::query()->orderBy('sort_order')->pluck('id')->reverse()->values()->all();
        $this->postJson(route('admin.reorder', 'questionnaire-sections'), ['ids' => $order])->assertOk();
        $this->assertSame(['final', 'logistics', 'features', 'inspiration', 'look', 'project', 'about'], array_keys(QuestionnaireTemplate::definition()));
    }

    public function test_preview_shows_the_current_questions_without_saving(): void
    {
        $this->actingAsRole('Administrator');
        $this->newSectionWithChoiceQuestion();

        $this->get(route('admin.onboarding.questions.preview', ['step' => 'hosting_support']))
            ->assertOk()
            ->assertSee('Nothing you enter here is saved')
            ->assertSee('data-preview', false)
            ->assertSee('Which support do you need?');

        $this->assertSame(0, ClientQuestionnaire::query()->count());
    }
}
