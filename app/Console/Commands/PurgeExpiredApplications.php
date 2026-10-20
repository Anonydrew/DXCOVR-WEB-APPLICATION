<?php

namespace App\Console\Commands;

use App\Models\JobApplication;
use App\Services\Recruitment;
use App\Services\SiteSettings;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('applications:purge-expired {--dry-run : List what would be deleted without deleting anything}')]
#[Description('Delete job applications (and their CVs) that are past the configured retention period')]
class PurgeExpiredApplications extends Command
{
    public function handle(SiteSettings $settings, Recruitment $recruitment): int
    {
        $days = max(30, (int) $settings->get('careers.retention_days', 365));
        $query = JobApplication::query()->beyondRetention($days);
        $count = (clone $query)->count();

        if ($this->option('dry-run')) {
            $this->components->info("{$count} application(s) are older than the {$days}-day retention period.");

            return self::SUCCESS;
        }

        $query->chunkById(100, function ($applications) use ($recruitment): void {
            foreach ($applications as $application) {
                $recruitment->delete($application, 'Retention period elapsed');
            }
        });

        $this->components->info("Deleted {$count} application(s) past the {$days}-day retention period.");

        return self::SUCCESS;
    }
}
