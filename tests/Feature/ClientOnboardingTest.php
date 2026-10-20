<?php

namespace Tests\Feature;

use App\Enums\QuestionnaireStatus;
use App\Models\ClientQuestionnaire;
use App\Models\Enquiry;
use App\Notifications\QuestionnaireSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->syncPermissions();
    }

    protected function pngUpload(string $name = 'moodboard.png'): UploadedFile
    {
        $image = imagecreatetruecolor(40, 30);
        imagefill($image, 0, 0, imagecolorallocate($image, 30, 120, 200));
        $path = tempnam(sys_get_temp_dir(), 'ob');
        imagepng($image, $path);

        return new UploadedFile($path, $name, null, null, true);
    }

    protected function textUpload(string $name, string $contents): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'ob');
        file_put_contents($path, $contents);

        return new UploadedFile($path, $name, null, null, true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function completeAnswers(): array
    {
        return [
            'about' => ['contact_name' => 'Ama Mensah', 'contact_email' => 'ama@example.com', 'company' => 'Mensah Clinics'],
            'project' => ['types' => ['website', 'business_system'], 'summary' => 'A booking website connected to our clinic system.'],
            'look' => ['styles' => ['minimal', 'warm'], 'palettes' => ['ocean'], 'typography' => 'modern', 'mood' => ['friendly']],
            'inspiration' => ['references' => [['url' => 'https://example.com', 'note' => 'Clear booking flow'], ['url' => '', 'note' => '']]],
            'final' => ['consent' => '1'],
        ];
    }

    public function test_staff_with_permission_create_a_questionnaire_with_a_private_link(): void
    {
        $this->actingAsRole('Administrator');

        $this->post(route('admin.onboarding.store'), [
            'client_name' => 'Ama Mensah',
            'client_email' => 'ama@example.com',
            'project_name' => 'Clinic website',
            'sections' => ['project', 'look', 'inspiration'],
        ])->assertRedirect();

        $questionnaire = ClientQuestionnaire::query()->sole();
        $this->assertSame(['project', 'look', 'inspiration'], $questionnaire->sections);
        $this->assertSame(['about', 'project', 'look', 'inspiration', 'final'], array_keys($questionnaire->formSections()));
        $this->assertSame(48, strlen($questionnaire->token));
        $this->assertSame(hash('sha256', $questionnaire->token), $questionnaire->token_hash);
        $this->assertDatabaseMissing('client_questionnaires', ['token' => $questionnaire->token]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'onboarding.created']);

        $this->get(route('admin.onboarding.show', $questionnaire))->assertOk()->assertSee($questionnaire->shareUrl());
    }

    public function test_questionnaire_can_be_prefilled_from_an_enquiry(): void
    {
        $this->actingAsRole('Administrator');
        $enquiry = Enquiry::factory()->create(['name' => 'Kofi Boateng', 'email' => 'kofi@example.com']);

        $this->get(route('admin.onboarding.create', ['enquiry' => $enquiry->id]))
            ->assertOk()
            ->assertSee('value="Kofi Boateng"', false)
            ->assertSee('value="kofi@example.com"', false);
    }

    public function test_roles_without_onboarding_permission_are_refused(): void
    {
        $questionnaire = ClientQuestionnaire::factory()->create();
        $this->actingAsRole('Editor');

        $this->get(route('admin.onboarding.index'))->assertForbidden();
        $this->get(route('admin.onboarding.show', $questionnaire))->assertForbidden();
        $this->post(route('admin.onboarding.store'), ['client_name' => 'X', 'project_name' => 'Y'])->assertForbidden();
    }

    public function test_client_opens_the_link_without_signing_in(): void
    {
        $questionnaire = ClientQuestionnaire::factory()->create(['project_name' => 'Clinic website', 'intro_message' => 'Hello from the team']);

        $this->get(route('onboarding.show', $questionnaire->token))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertSee('Let’s shape Clinic website')
            ->assertSee('Hello from the team')
            ->assertSee('Dark &amp; premium', false)
            ->assertSee('Kente bold');

        $this->assertNotNull($questionnaire->fresh()->first_opened_at);
    }

    public function test_unknown_and_expired_links_are_not_usable(): void
    {
        $this->get('/onboarding/'.str_repeat('a', 48))->assertNotFound();

        $expired = ClientQuestionnaire::factory()->expired()->create();
        $this->get(route('onboarding.show', $expired->token))->assertStatus(410)->assertSee('This link has expired');
        $this->post(route('onboarding.update', $expired->token), ['answers' => $this->completeAnswers()])->assertRedirect();
        $this->assertNull($expired->fresh()->answers);
    }

    public function test_client_saves_partial_answers_and_unknown_fields_are_dropped(): void
    {
        $questionnaire = ClientQuestionnaire::factory()->create();

        $this->post(route('onboarding.update', $questionnaire->token), [
            'step' => 'look',
            'action' => 'save',
            'answers' => [
                'about' => ['contact_name' => '  Ama  ', 'injected' => 'nope'],
                'look' => ['styles' => ['minimal'], 'palettes' => ['ocean']],
                'unknown_section' => ['x' => 'y'],
            ],
        ])->assertRedirect(route('onboarding.show', ['token' => $questionnaire->token, 'step' => 'look']));

        $questionnaire->refresh();
        $this->assertSame(QuestionnaireStatus::InProgress, $questionnaire->status);
        $this->assertSame(['about' => ['contact_name' => 'Ama'], 'look' => ['styles' => ['minimal'], 'palettes' => ['ocean']]], $questionnaire->answers);
    }

    public function test_invalid_choices_and_unsafe_links_are_rejected(): void
    {
        $questionnaire = ClientQuestionnaire::factory()->create();

        $this->post(route('onboarding.update', $questionnaire->token), [
            'answers' => [
                'look' => ['styles' => ['minimal', 'bold', 'dark', 'warm'], 'typography' => 'comic-sans'],
                'inspiration' => ['references' => [['url' => 'javascript:alert(1)', 'note' => 'x']]],
            ],
        ])->assertSessionHasErrors([
            'answers.look.styles',
            'answers.look.typography',
            'answers.inspiration.references.0.url',
        ]);

        $this->assertNull($questionnaire->fresh()->answers);
    }

    public function test_submitting_requires_the_essentials_and_notifies_the_team(): void
    {
        Notification::fake();
        $this->setting(['notifications.onboarding_recipients' => ['team@dxcovr.test']]);
        $questionnaire = ClientQuestionnaire::factory()->create();

        $this->post(route('onboarding.update', $questionnaire->token), ['action' => 'submit', 'answers' => ['about' => ['contact_name' => 'Ama']]])
            ->assertRedirect(route('onboarding.show', ['token' => $questionnaire->token, 'step' => 'about']))
            ->assertSessionHasErrors(['answers.about.contact_email', 'answers.project.summary', 'answers.final.consent']);
        $this->assertSame(QuestionnaireStatus::Pending, $questionnaire->fresh()->status);

        $this->post(route('onboarding.update', $questionnaire->token), ['action' => 'submit', 'answers' => $this->completeAnswers()])
            ->assertRedirect(route('onboarding.show', $questionnaire->token));

        $questionnaire->refresh();
        $this->assertSame(QuestionnaireStatus::Submitted, $questionnaire->status);
        $this->assertNotNull($questionnaire->submitted_at);
        $this->assertSame([['url' => 'https://example.com', 'note' => 'Clear booking flow']], $questionnaire->answer('inspiration.references'));
        $this->assertTrue($questionnaire->answer('final.consent'));

        Notification::assertSentOnDemand(QuestionnaireSubmitted::class, fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'team@dxcovr.test');
        $this->get(route('onboarding.show', $questionnaire->token))->assertOk()->assertSee('Thank you, Ama');
    }

    public function test_submitted_questionnaires_are_locked_until_reopened(): void
    {
        $questionnaire = ClientQuestionnaire::factory()->submitted()->create();
        $original = $questionnaire->answers;

        $this->post(route('onboarding.update', $questionnaire->token), ['answers' => ['about' => ['contact_name' => 'Changed']]]);
        $this->assertSame($original, $questionnaire->fresh()->answers);

        $this->actingAsRole('Administrator');
        $this->post(route('admin.onboarding.reopen', $questionnaire))->assertRedirect();
        $this->assertSame(QuestionnaireStatus::InProgress, $questionnaire->fresh()->status);
    }

    public function test_regenerating_the_link_disables_the_old_one(): void
    {
        $questionnaire = ClientQuestionnaire::factory()->create();
        $oldToken = $questionnaire->token;
        $this->actingAsRole('Administrator');

        $this->post(route('admin.onboarding.regenerate', $questionnaire))->assertRedirect();

        auth()->logout();
        $this->get(route('onboarding.show', $oldToken))->assertNotFound();
        $this->get(route('onboarding.show', $questionnaire->fresh()->token))->assertOk();
    }

    public function test_autosave_returns_json(): void
    {
        $questionnaire = ClientQuestionnaire::factory()->create();

        $this->postJson(route('onboarding.update', $questionnaire->token), ['answers' => ['about' => ['contact_name' => 'Ama']]])
            ->assertOk()
            ->assertJsonStructure(['saved_at']);

        $this->postJson(route('onboarding.update', $questionnaire->token), ['answers' => ['about' => ['contact_email' => 'not-an-email']]])
            ->assertUnprocessable();
    }

    public function test_uploads_are_private_and_only_served_through_authorised_routes(): void
    {
        $questionnaire = ClientQuestionnaire::factory()->create();

        $response = $this->postJson(route('onboarding.files.store', $questionnaire->token), ['file' => $this->pngUpload()])
            ->assertCreated()
            ->assertJsonPath('name', 'moodboard.png')
            ->assertJsonPath('is_image', true);

        $file = $questionnaire->files()->sole();
        Storage::disk('local')->assertExists($file->path);
        $this->assertStringStartsWith('onboarding/'.$questionnaire->id.'/', $file->path);
        $this->assertStringNotContainsString('moodboard', $file->path);

        $this->get($response->json('url'))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        // Another questionnaire's link cannot reach this file.
        $other = ClientQuestionnaire::factory()->create();
        $this->get(route('onboarding.files.show', [$other->token, $file->id]))->assertNotFound();

        $this->actingAsRole('Editor');
        $this->get(route('admin.onboarding.files.show', [$questionnaire, $file->id]))->assertForbidden();

        $this->actingAsRole('Administrator');
        $this->get(route('admin.onboarding.files.show', [$questionnaire, $file->id]).'?download=1')->assertOk()->assertDownload('moodboard.png');
    }

    public function test_disguised_files_are_rejected(): void
    {
        $questionnaire = ClientQuestionnaire::factory()->create();

        $this->postJson(route('onboarding.files.store', $questionnaire->token), ['file' => $this->textUpload('photo.png', 'just some text pretending to be an image')])
            ->assertUnprocessable();

        $this->postJson(route('onboarding.files.store', $questionnaire->token), ['file' => $this->textUpload('page.html', '<html><body>hi</body></html>')])
            ->assertUnprocessable();

        $this->assertSame(0, $questionnaire->files()->count());
    }

    public function test_files_can_be_uploaded_with_the_form_and_removed(): void
    {
        $questionnaire = ClientQuestionnaire::factory()->create();

        $this->post(route('onboarding.update', $questionnaire->token), ['files' => [$this->pngUpload('sketch.png')], 'answers' => []])->assertRedirect();
        $file = $questionnaire->files()->sole();

        $this->post(route('onboarding.update', $questionnaire->token), ['remove_file' => $file->id, 'answers' => []])->assertRedirect();
        $this->assertSame(0, $questionnaire->files()->count());
        Storage::disk('local')->assertMissing($file->path);
    }

    public function test_upload_limit_is_enforced(): void
    {
        config(['dxcovr.onboarding.max_files' => 1]);
        $questionnaire = ClientQuestionnaire::factory()->create();

        $this->postJson(route('onboarding.files.store', $questionnaire->token), ['file' => $this->pngUpload()])->assertCreated();
        $this->postJson(route('onboarding.files.store', $questionnaire->token), ['file' => $this->pngUpload()])->assertUnprocessable();
    }

    public function test_admin_review_shows_the_answers_visually_and_marks_them_reviewed(): void
    {
        $questionnaire = ClientQuestionnaire::factory()->submitted()->create();
        $this->actingAsRole('Administrator');

        $this->get(route('admin.onboarding.index'))->assertOk()->assertSee($questionnaire->client_name)->assertSee('Not yet reviewed');

        $this->get(route('admin.onboarding.show', $questionnaire))
            ->assertOk()
            ->assertSee('Minimal &amp; clean', false)
            ->assertSee('ob-style-preview', false)
            ->assertSee('Ocean trust')
            ->assertSee('https://example.com')
            ->assertSee('rel="noopener noreferrer nofollow"', false);

        $this->assertNotNull($questionnaire->fresh()->reviewed_at);
    }

    public function test_deleting_removes_answers_and_files(): void
    {
        $questionnaire = ClientQuestionnaire::factory()->create();
        $this->postJson(route('onboarding.files.store', $questionnaire->token), ['file' => $this->pngUpload()])->assertCreated();
        $path = $questionnaire->files()->sole()->path;

        $this->actingAsRole('Administrator');
        $this->delete(route('admin.onboarding.destroy', $questionnaire))->assertRedirect(route('admin.onboarding.index'));

        $this->assertModelMissing($questionnaire);
        $this->assertDatabaseCount('client_questionnaire_files', 0);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_robots_txt_disallows_questionnaire_links(): void
    {
        $this->get('/robots.txt')->assertSee('Disallow: /onboarding/');
    }
}
