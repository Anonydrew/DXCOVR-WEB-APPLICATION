<?php

namespace App\Console\Commands;

use App\Models\JobListing;
use App\Services\AuditLogger;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('careers:close-expired')]
#[Description('Close job listings whose application deadline has passed (when auto-close is enabled)')]
class CloseExpiredJobListings extends Command
{
    public function handle(AuditLogger $audit): int
    {
        $count = 0;

        JobListing::query()->dueForClosing()->each(function (JobListing $job) use ($audit, &$count): void {
            $job->closed_at = now();
            $job->saveQuietly();
            $audit->record('careers.auto_closed', $job, 'Applications closed automatically for “'.$job->title.'” after the deadline');
            $count++;
        });

        $this->components->info("Closed {$count} job listing(s).");

        return self::SUCCESS;
    }
}
