<?php

namespace Database\Seeders;

use App\Enums\ApplicationMethod;
use App\Enums\DeliveryStatus;
use App\Enums\EmploymentType;
use App\Enums\ProjectType;
use App\Enums\PublicationStatus;
use App\Enums\WorkArrangement;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Department;
use App\Models\JobListing;
use App\Models\Page;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Service;
use App\Models\Tag;
use App\Models\Technology;
use App\Services\MediaLibrary;
use GdImage;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Clearly labelled DEMONSTRATION content for local development and design
 * review only. Every record is marked "Demo" so it cannot be mistaken for
 * real DXCOVR work. Refuses to run in production.
 */
class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DemoContentSeeder must not be run in production.');
        }

        $this->call(SiteContentSeeder::class);

        $this->articles();
        $this->projects();
        $this->job();
        $this->sampleLogos();
    }

    /**
     * Abstract placeholder marks (no real organisation) so the hero logo row
     * can be previewed. Only added while the hero has no logos of its own.
     */
    protected function sampleLogos(): void
    {
        $hero = Page::system('home')?->sections()->where('type', 'hero')->first();

        if ($hero === null || ! empty($hero->data['logos'] ?? [])) {
            return;
        }

        $library = app(MediaLibrary::class);
        $logos = [];

        foreach (['circle', 'square', 'diamond', 'ring', 'triangle', 'pair'] as $index => $shape) {
            $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'dxcovr-sample-logo-'.bin2hex(random_bytes(6)).'.png';
            imagepng($this->drawSampleLogo($shape, $index), $path);

            $media = $library->store(new UploadedFile($path, 'demo-sample-logo-'.($index + 1).'.png', 'image/png', null, true), [
                'title' => 'Demo: sample logo '.($index + 1),
                'alt' => 'Sample logo '.($index + 1),
                'folder' => 'demo',
            ]);

            @unlink($path);
            $logos[] = ['media_id' => $media->id, 'name' => 'Sample logo '.($index + 1)];
        }

        $hero->update(['data' => array_merge($hero->data, ['logos' => $logos])]);
    }

    /**
     * Draws a black mark and "wordmark" bars on a transparent canvas.
     */
    protected function drawSampleLogo(string $shape, int $seed): GdImage
    {
        $image = imagecreatetruecolor(360, 120);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
        imagealphablending($image, true);

        $ink = imagecolorallocate($image, 17, 17, 17);
        $clear = imagecolorallocatealpha($image, 0, 0, 0, 127);

        match ($shape) {
            'circle' => imagefilledellipse($image, 60, 60, 72, 72, $ink),
            'square' => imagefilledrectangle($image, 26, 26, 94, 94, $ink),
            'diamond' => imagefilledpolygon($image, [60, 20, 100, 60, 60, 100, 20, 60], $ink),
            'triangle' => imagefilledpolygon($image, [60, 22, 102, 98, 18, 98], $ink),
            'pair' => [imagefilledellipse($image, 44, 60, 56, 56, $ink), imagefilledellipse($image, 78, 60, 56, 56, $ink)],
            default => [imagefilledellipse($image, 60, 60, 76, 76, $ink), imagealphablending($image, false), imagefilledellipse($image, 60, 60, 40, 40, $clear), imagealphablending($image, true)],
        };

        $lengths = [[190, 120], [150, 0], [210, 90], [170, 140], [130, 0], [200, 110]][$seed % 6];
        $this->drawBar($image, 124, 36, $lengths[0], 22, $ink);

        if ($lengths[1] > 0) {
            $this->drawBar($image, 124, 70, $lengths[1], 14, $ink);
        }

        return $image;
    }

    protected function drawBar(GdImage $image, int $x, int $y, int $width, int $height, int $colour): void
    {
        $radius = intdiv($height, 2);
        imagefilledrectangle($image, $x + $radius, $y, $x + $width - $radius, $y + $height, $colour);
        imagefilledellipse($image, $x + $radius, $y + $radius, $height, $height, $colour);
        imagefilledellipse($image, $x + $width - $radius, $y + $radius, $height, $height, $colour);
    }

    protected function articles(): void
    {
        $articles = [
            [
                'title' => 'Demo: How we decide when AI is the right tool',
                'category' => 'Artificial intelligence',
                'tags' => 'AI, RAG, Product strategy',
                'excerpt' => 'Demonstration article. A practical checklist for deciding whether a problem really benefits from AI, and what to do when it does not.',
                'body' => '<p>This is demonstration content used to preview the article layout.</p><h2>Start with the workflow</h2><p>Before choosing a model, describe the task as it happens today: who does it, how often, and what a good result looks like.</p><h2>Check the data</h2><p>AI systems are only as useful as the information they can reach. Retrieval-augmented generation helps when the answers already exist in your documents.</p><h3>Signals that RAG will help</h3><ul><li>Questions are answered from policies or manuals.</li><li>Answers must cite their sources.</li><li>The content changes regularly.</li></ul><h2>Measure before rollout</h2><p>Agree on a small set of real examples and evaluate against them. Keep people in the loop for consequential decisions.</p><blockquote>The best AI feature is often the one users barely notice, because it simply removes a step.</blockquote><h2>When to choose something simpler</h2><p>If the rules are clear and stable, a conventional automation is cheaper, faster and easier to audit.</p><pre><code>if (rulesAreStable &amp;&amp; inputsAreStructured) {'."\n".'  return "automate with rules";'."\n".'}</code></pre>',
            ],
            [
                'title' => 'Demo: Immersive experiences without the app store',
                'category' => 'AR, VR and WebXR',
                'tags' => 'WebXR, 3D, Performance',
                'excerpt' => 'Demonstration article. Why WebXR is a practical way to put 3D and mixed reality in front of people, and how to make it fast.',
                'body' => '<p>This is demonstration content used to preview the article layout.</p><h2>Removing the install step</h2><p>Experiences that open from a link reach more people than those that require an app download.</p><h2>Designing for everyday devices</h2><p>Optimised models, compressed textures and careful lighting keep frame rates smooth on phones and laptops.</p><h2>Graceful fallbacks</h2><p>Where immersive features are unavailable, the same content should still work as an interactive 3D view.</p>',
            ],
            [
                'title' => 'Demo: Designing software around real workflows',
                'category' => 'Software engineering',
                'tags' => 'ERP, UX, Architecture',
                'excerpt' => 'Demonstration article. Business systems succeed when they reflect how work actually happens. Here is how we map a process before writing code.',
                'body' => '<p>This is demonstration content used to preview the article layout.</p><h2>Observe first</h2><p>Spend time with the people doing the work. Spreadsheets and workarounds reveal what the official process misses.</p><h2>Model the data</h2><p>A clear data model makes everything else simpler: permissions, reporting and integrations.</p><h2>Ship in slices</h2><p>Deliver one complete workflow at a time so the team can start benefiting early.</p>',
            ],
        ];

        foreach ($articles as $index => $definition) {
            if (Article::query()->where('title', $definition['title'])->exists()) {
                continue;
            }

            $article = new Article;
            $article->fill([
                'title' => $definition['title'],
                'excerpt' => $definition['excerpt'],
                'body' => $definition['body'],
                'byline' => 'Demo content',
                'show_author' => true,
                'show_toc' => true,
                'is_featured' => $index === 0,
            ]);
            $article->reading_time = 4;
            $article->status = PublicationStatus::Published;
            $article->published_at = now()->subDays(($index + 1) * 6);
            $article->save();

            $article->categories()->sync(ArticleCategory::query()->where('name', $definition['category'])->pluck('id'));
            $article->tags()->sync(Tag::idsFromNames($definition['tags']));
        }
    }

    protected function projects(): void
    {
        $projects = [
            [
                'title' => 'Demo: Virtual showroom concept',
                'type' => ProjectType::Concept,
                'category' => 'AR, VR and WebXR',
                'service' => 'webxr-and-immersive-web',
                'technologies' => 'WebXR, Three.js, Blender',
                'summary' => 'Demonstration content. A concept for a browser-based showroom where visitors explore products in 3D and place them in their space with AR.',
            ],
            [
                'title' => 'Demo: Environmental sensor prototype',
                'type' => ProjectType::Prototype,
                'category' => 'Hardware and IoT',
                'service' => 'hardware-programming-and-embedded-systems',
                'technologies' => 'ESP32, MQTT, Laravel',
                'summary' => 'Demonstration content. A prototype that streams temperature and humidity readings from a microcontroller to a live dashboard.',
            ],
            [
                'title' => 'Demo: Operations dashboard',
                'type' => ProjectType::Internal,
                'category' => 'Software and SaaS',
                'service' => 'software-engineering-and-business-systems',
                'technologies' => 'Laravel, MySQL, Tailwind CSS',
                'summary' => 'Demonstration content. An internal dashboard that brings scheduling, records and reporting into one view.',
            ],
        ];

        foreach ($projects as $index => $definition) {
            if (Project::query()->where('title', $definition['title'])->exists()) {
                continue;
            }

            $project = new Project;
            $project->fill([
                'title' => $definition['title'],
                'summary' => $definition['summary'],
                'project_type' => $definition['type'],
                'delivery_status' => DeliveryStatus::Completed,
                'project_category_id' => ProjectCategory::query()->where('name', $definition['category'])->value('id'),
                'year' => (int) now()->format('Y'),
                'challenge' => '<p>Demonstration content describing the challenge.</p>',
                'approach' => '<p>Demonstration content describing the approach, from discovery to prototype.</p>',
                'solution' => '<p>Demonstration content describing the solution and how it works.</p>',
                'features' => [
                    ['title' => 'Demo feature one', 'body' => 'Short description of a capability.'],
                    ['title' => 'Demo feature two', 'body' => 'Short description of a capability.'],
                    ['title' => 'Demo feature three', 'body' => 'Short description of a capability.'],
                ],
                'is_featured' => true,
                'sort_order' => $index,
            ]);
            $project->status = PublicationStatus::Published;
            $project->published_at = now()->subDays($index + 1);
            $project->save();

            $project->services()->sync(Service::query()->where('slug', $definition['service'])->pluck('id'));
            $project->technologies()->sync(Technology::idsFromNames($definition['technologies']));
        }
    }

    protected function job(): void
    {
        if (JobListing::query()->where('title', 'Demo: Junior Software Engineer')->exists()) {
            return;
        }

        $job = new JobListing;
        $job->fill([
            'title' => 'Demo: Junior Software Engineer',
            'department_id' => Department::query()->where('name', 'Engineering')->value('id'),
            'location' => 'Accra, Ghana',
            'work_arrangement' => WorkArrangement::Hybrid,
            'employment_type' => EmploymentType::FullTime,
            'summary' => 'Demonstration job listing used to preview the careers section and application form.',
            'description' => '<p>Demonstration content describing the role.</p>',
            'responsibilities' => '<ul><li>Build and test features in web applications.</li><li>Collaborate with designers and engineers.</li></ul>',
            'requirements' => '<ul><li>Familiarity with a modern web framework.</li><li>Clear written communication.</li></ul>',
            'application_deadline' => now()->addDays(30)->setTime(17, 0),
            'auto_close' => true,
            'application_method' => ApplicationMethod::Form,
        ]);
        $job->status = PublicationStatus::Published;
        $job->published_at = now()->subDay();
        $job->save();
    }
}
