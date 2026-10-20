<?php

namespace Tests\Feature;

use App\Enums\ApplicationMethod;
use App\Enums\ApplicationStage;
use App\Enums\PublicationStatus;
use App\Models\JobApplication;
use App\Models\JobListing;
use App\Models\User;
use App\Notifications\ApplicationAcknowledgement;
use App\Notifications\ApplicationStageUpdated;
use App\Notifications\NewApplicationReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Testing\Fakes\NotificationFake;
use Tests\TestCase;
use ZipArchive;

class CareersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        Notification::fake();
        $this->syncPermissions();
    }

    /**
     * @return array<string, mixed>
     */
    protected function application(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ama Mensah',
            'email' => 'Ama@Example.com',
            'phone' => '+233 20 000 0000',
            'cover_letter' => str_repeat('I build reliable software and care about users. ', 3),
            'portfolio_url' => 'https://github.com/example',
            'cv' => UploadedFile::fake()->createWithContent('Ama CV.pdf', "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF"),
            'consent' => '1',
        ], $this->spamFields(), $overrides);
    }

    protected function docx(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>');
        $zip->addFromString('word/document.xml', '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"/>');
        $zip->close();

        return new UploadedFile($path, 'cv.docx', null, null, true);
    }

    protected function realUpload(string $name, string $contents): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($path, $contents);

        return new UploadedFile($path, $name, null, null, true);
    }

    public function test_careers_page_lists_only_open_roles(): void
    {
        $open = JobListing::factory()->published()->create(['title' => 'Open role']);
        JobListing::factory()->draft()->create(['title' => 'Draft role']);
        JobListing::factory()->published()->create(['title' => 'Expired role', 'application_deadline' => now()->subDay()]);
        JobListing::factory()->published()->create(['title' => 'Closed role', 'closed_at' => now()]);

        $this->get('/careers')->assertOk()->assertSee('Open role')->assertDontSee('Draft role')->assertDontSee('Expired role')->assertDontSee('Closed role');
        $this->get(route('careers.show', $open))->assertOk()->assertSee('Apply for this role')->assertSee('"@type":"JobPosting"', false);
    }

    public function test_empty_careers_page_has_a_helpful_message(): void
    {
        $this->get('/careers')->assertOk()->assertSee('No open roles right now');
    }

    public function test_candidate_can_apply_and_cv_is_stored_privately(): void
    {
        $job = JobListing::factory()->published()->create();

        $this->post(route('careers.apply', $job), $this->application())
            ->assertRedirect(route('careers.received'));

        $application = JobApplication::query()->firstOrFail();

        $this->assertSame('ama@example.com', $application->email);
        $this->assertSame(ApplicationStage::Received, $application->stage);
        $this->assertSame('local', $application->cv_disk);
        $this->assertStringStartsWith('applications/', $application->cv_path);
        Storage::disk('local')->assertExists($application->cv_path);
        Storage::disk('public')->assertMissing($application->cv_path);
        $this->assertDatabaseHas('application_stage_changes', ['job_application_id' => $application->id, 'to_stage' => 'received']);

        Notification::assertSentTo(new AnonymousNotifiable, ApplicationAcknowledgement::class);

        $this->get(route('careers.received'))->assertOk()->assertSee($application->reference);
    }

    public function test_team_is_notified_when_recipients_are_configured(): void
    {
        $this->setting(['notifications.application_recipients' => ['hr@dxcovr.test']]);
        $job = JobListing::factory()->published()->create();

        $this->post(route('careers.apply', $job), $this->application());

        Notification::assertSentOnDemand(NewApplicationReceived::class, fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'hr@dxcovr.test');
    }

    public function test_application_is_saved_even_if_notification_dispatch_fails(): void
    {
        Notification::swap(new class extends NotificationFake
        {
            public function sendNow($notifiables, $notification, ?array $channels = null): void
            {
                throw new \RuntimeException('SMTP unavailable');
            }

            public function send($notifiables, $notification): void
            {
                throw new \RuntimeException('SMTP unavailable');
            }
        });

        $job = JobListing::factory()->published()->create();

        $this->post(route('careers.apply', $job), $this->application())->assertRedirect(route('careers.received'));

        $this->assertDatabaseCount('job_applications', 1);
    }

    public function test_docx_cvs_are_accepted_after_content_verification(): void
    {
        $job = JobListing::factory()->published()->create();

        $this->post(route('careers.apply', $job), $this->application(['cv' => $this->docx()]))
            ->assertRedirect(route('careers.received'));

        $this->assertDatabaseCount('job_applications', 1);
    }

    public function test_disguised_and_disallowed_files_are_rejected(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $job = JobListing::factory()->published()->create();

        // Real files (not fakes) so the type is detected from the content, as in production.
        $php = $this->realUpload('cv.pdf', '<?php echo "pwned"; ?>');
        $this->post(route('careers.apply', $job), $this->application(['cv' => $php]))->assertSessionHasErrors('cv');

        $html = $this->realUpload('cv.docx', '<html><script>alert(1)</script></html>');
        $this->post(route('careers.apply', $job), $this->application(['cv' => $html]))->assertSessionHasErrors('cv');

        $exe = UploadedFile::fake()->create('cv.exe', 10, 'application/x-msdownload');
        $this->post(route('careers.apply', $job), $this->application(['cv' => $exe]))->assertSessionHasErrors('cv');

        $big = UploadedFile::fake()->create('cv.pdf', 6000, 'application/pdf');
        $this->post(route('careers.apply', $job), $this->application(['cv' => $big]))->assertSessionHasErrors('cv');

        $this->assertDatabaseCount('job_applications', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_application_requires_consent_and_valid_fields(): void
    {
        $job = JobListing::factory()->published()->create();

        $this->post(route('careers.apply', $job), $this->application(['consent' => null, 'email' => 'not-an-email', 'cover_letter' => 'short']))
            ->assertSessionHasErrors(['consent', 'email', 'cover_letter']);
    }

    public function test_spam_protection_blocks_bots(): void
    {
        $job = JobListing::factory()->published()->create();

        $this->post(route('careers.apply', $job), $this->application(['company_website' => 'http://spam.example']))->assertSessionHasErrors('form');
        $this->post(route('careers.apply', $job), $this->application(['_form_started' => 'tampered']))->assertSessionHasErrors('form');

        $this->assertDatabaseCount('job_applications', 0);
    }

    public function test_closed_or_external_roles_reject_form_applications(): void
    {
        $closed = JobListing::factory()->published()->create(['closed_at' => now()]);
        $external = JobListing::factory()->published()->create(['application_method' => ApplicationMethod::External, 'application_url' => 'https://jobs.example.org/1']);

        $this->post(route('careers.apply', $closed), $this->application())->assertSessionHasErrors('form');
        $this->post(route('careers.apply', $external), $this->application())->assertSessionHasErrors('form');
        $this->post(route('careers.apply', JobListing::factory()->draft()->create()), $this->application())->assertNotFound();

        $this->assertDatabaseCount('job_applications', 0);
    }

    public function test_applications_are_rate_limited(): void
    {
        $job = JobListing::factory()->published()->create();

        for ($i = 0; $i < 3; $i++) {
            $this->post(route('careers.apply', $job), $this->application());
        }

        $this->post(route('careers.apply', $job), $this->application())->assertStatus(429);
    }

    public function test_cv_download_is_restricted_to_authorised_users(): void
    {
        $job = JobListing::factory()->published()->create();
        $this->post(route('careers.apply', $job), $this->application());
        $application = JobApplication::query()->firstOrFail();

        $this->get(route('admin.applications.cv', $application))->assertRedirect(route('login'));

        $this->actingAsRole('Editor');
        $this->get(route('admin.applications.cv', $application))->assertForbidden();
        $this->get(route('admin.applications.show', $application))->assertForbidden();

        $this->actingAsRole('Read-only Analyst');
        $this->get(route('admin.applications.cv', $application))->assertForbidden();

        $this->actingAsRole('Careers Manager');
        $this->get(route('admin.applications.cv', $application))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertDownload();

        $this->assertDatabaseHas('audit_logs', ['action' => 'applications.cv_downloaded', 'auditable_id' => $application->id]);
    }

    public function test_stage_changes_are_recorded_and_optionally_emailed(): void
    {
        $application = JobApplication::factory()->create();
        $manager = $this->actingAsRole('Careers Manager');

        $this->patch(route('admin.applications.stage', $application), ['stage' => 'shortlisted', 'note' => 'Strong portfolio', 'notify_applicant' => '0'])
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();

        $this->patch(route('admin.applications.stage', $application), ['stage' => 'declined', 'notify_applicant' => '1', 'message_to_applicant' => 'Thank you.']);

        $application->refresh();
        $this->assertSame(ApplicationStage::Declined, $application->stage);
        $this->assertNotNull($application->decided_at);
        $this->assertDatabaseHas('application_stage_changes', ['job_application_id' => $application->id, 'to_stage' => 'shortlisted', 'user_id' => $manager->id, 'note' => 'Strong portfolio']);
        $this->assertDatabaseHas('application_stage_changes', ['to_stage' => 'declined', 'applicant_notified' => true]);
        Notification::assertSentOnDemand(ApplicationStageUpdated::class);
    }

    public function test_internal_notes_can_be_added(): void
    {
        $application = JobApplication::factory()->create();
        $this->actingAsRole('Careers Manager');

        $this->post(route('admin.applications.notes.store', $application), ['body' => 'Invite to technical interview'])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('application_notes', ['job_application_id' => $application->id, 'body' => 'Invite to technical interview']);
    }

    public function test_deleting_an_application_removes_the_cv_file(): void
    {
        $job = JobListing::factory()->published()->create();
        $this->post(route('careers.apply', $job), $this->application());
        $application = JobApplication::query()->firstOrFail();
        $path = $application->cv_path;

        $this->actingAsRole('Careers Manager');
        $this->delete(route('admin.applications.destroy', $application))->assertRedirect(route('admin.applications.index'));

        $this->assertModelMissing($application);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_jobs_with_applications_cannot_be_deleted(): void
    {
        $application = JobApplication::factory()->create();
        $this->actingAsRole('Careers Manager');

        $this->delete(route('admin.jobs.destroy', $application->jobListing))->assertSessionHas('error');
        $this->assertModelExists($application->jobListing);
    }

    public function test_listings_close_automatically_after_the_deadline(): void
    {
        $job = JobListing::factory()->published()->create(['application_deadline' => now()->addHour()]);
        $manual = JobListing::factory()->published()->create(['application_deadline' => now()->addHour(), 'auto_close' => false]);

        $this->travel(2)->hours();
        $this->artisan('careers:close-expired')->assertSuccessful();

        $this->assertNotNull($job->fresh()->closed_at);
        $this->assertNull($manual->fresh()->closed_at);
        $this->assertFalse($manual->fresh()->isAcceptingApplications(), 'A passed deadline always stops applications.');
    }

    public function test_recruitment_data_is_purged_after_the_retention_period(): void
    {
        $this->setting(['careers.retention_days' => 90]);

        $old = JobApplication::factory()->create(['stage' => 'declined', 'decided_at' => now()->subDays(120)]);
        Storage::disk('local')->put($old->cv_path, 'cv');
        $recent = JobApplication::factory()->create(['stage' => 'declined', 'decided_at' => now()->subDays(10)]);
        $abandoned = JobApplication::factory()->create(['created_at' => now()->subDays(200)]);

        $this->artisan('applications:purge-expired', ['--dry-run' => true])->expectsOutputToContain('2 application(s)')->assertSuccessful();
        $this->assertDatabaseCount('job_applications', 3);

        $this->artisan('applications:purge-expired')->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelMissing($abandoned);
        $this->assertModelExists($recent);
        Storage::disk('local')->assertMissing($old->cv_path);
    }

    public function test_careers_manager_can_create_and_publish_a_job(): void
    {
        $this->actingAsRole('Careers Manager');

        $this->post('/admin/jobs', [
            'title' => 'Embedded Systems Engineer',
            'location' => 'Accra, Ghana',
            'work_arrangement' => 'hybrid',
            'employment_type' => 'full_time',
            'summary' => 'Build firmware for connected devices.',
            'description' => '<p>Work on sensors and microcontrollers.</p>',
            'application_method' => 'form',
            'application_deadline' => now()->addMonth()->format('Y-m-d\TH:i'),
            'auto_close' => '1',
        ])->assertSessionHasNoErrors();

        $job = JobListing::query()->firstOrFail();
        $this->assertSame(PublicationStatus::Draft, $job->status);

        $this->post(route('admin.publication', ['job', $job->id]), ['action' => 'publish'])->assertSessionHasNoErrors();
        $this->get(route('careers.show', $job))->assertOk()->assertSee('Embedded Systems Engineer');
    }

    public function test_email_application_method_requires_an_address(): void
    {
        $this->actingAsRole('Careers Manager');

        $this->post('/admin/jobs', [
            'title' => 'Designer', 'location' => 'Remote', 'work_arrangement' => 'remote', 'employment_type' => 'contract',
            'summary' => 'Design things.', 'application_method' => 'email',
        ])->assertSessionHasErrors('application_email');
    }

    public function test_applications_can_be_exported_only_with_export_permission(): void
    {
        JobApplication::factory()->create(['name' => '=HYPERLINK("http://evil")']);

        $this->actingAsRole('Careers Manager');
        $response = $this->get(route('admin.applications.export'));
        $response->assertOk();
        $this->assertStringContainsString("'=HYPERLINK", $response->streamedContent(), 'Formula injection must be neutralised.');

        $user = User::factory()->create();
        $user->givePermissionTo(['dashboard.view', 'applications.view']);
        $this->actingAs($user);
        $this->get(route('admin.applications.export'))->assertForbidden();
    }
}
