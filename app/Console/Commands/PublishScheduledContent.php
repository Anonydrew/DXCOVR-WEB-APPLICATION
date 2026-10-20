<?php

namespace App\Console\Commands;

use App\Services\PublicationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('content:publish-scheduled')]
#[Description('Publish scheduled content whose publication time has arrived')]
class PublishScheduledContent extends Command
{
    public function handle(PublicationService $publication): int
    {
        $count = $publication->publishDue();

        $this->components->info($count === 0 ? 'No scheduled content is due.' : "Published {$count} scheduled item(s).");

        return self::SUCCESS;
    }
}
