<?php

namespace Tests\Unit;

use App\Rules\SafeUrl;
use App\Services\HtmlSanitizer;
use App\Services\RichContent;
use App\Support\CsvExport;
use App\Support\Text;
use App\Support\VideoEmbed;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SupportTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function urls(): array
    {
        return [
            'relative path' => ['/contact', true],
            'anchor' => ['#start', true],
            'https' => ['https://dxcovr.example/work', true],
            'mailto' => ['mailto:hello@dxcovr.example', true],
            'tel' => ['tel:+233200000000', true],
            'javascript' => ['javascript:alert(1)', false],
            'javascript mixed case' => ['JaVaScRiPt:alert(1)', false],
            'data uri' => ['data:text/html;base64,PHNjcmlwdD4=', false],
            'protocol relative' => ['//evil.example', false],
            'vbscript' => ['vbscript:msgbox(1)', false],
            'quotes' => ['/x" onmouseover="alert(1)', false],
        ];
    }

    #[DataProvider('urls')]
    public function test_safe_url_rule(string $url, bool $valid): void
    {
        $this->assertSame($valid, Validator::make(['url' => $url], ['url' => [new SafeUrl]])->passes(), $url);
    }

    public function test_video_embeds_only_allow_known_providers(): void
    {
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', VideoEmbed::fromUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ')['src']);
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', VideoEmbed::fromUrl('https://youtu.be/dQw4w9WgXcQ')['src']);
        $this->assertSame('https://player.vimeo.com/video/123456789', VideoEmbed::fromUrl('https://vimeo.com/123456789')['src']);
        $this->assertNull(VideoEmbed::fromUrl('https://evil.example/watch?v=dQw4w9WgXcQ'));
        $this->assertNull(VideoEmbed::fromUrl('javascript:alert(1)'));
        $this->assertNull(VideoEmbed::fromUrl('https://www.youtube.com/watch?v=<script>'));
    }

    public function test_html_sanitizer_keeps_editorial_markup_and_removes_dangerous_code(): void
    {
        $sanitizer = app(HtmlSanitizer::class);

        $clean = $sanitizer->rich(
            '<h2>Title</h2><p><strong>Bold</strong> <a href="https://example.org" target="_blank">link</a></p>'.
            '<pre><code class="language-php">echo 1;</code></pre><blockquote>Quote</blockquote>'.
            '<img src="/storage/media/a.webp" alt="A" onerror="x()"><style>body{}</style><form action="/x"><input></form>'.
            '<h1>Not allowed</h1><a href="javascript:alert(1)">bad</a>'
        );

        $this->assertStringContainsString('<h2>Title</h2>', $clean);
        $this->assertStringContainsString('<strong>Bold</strong>', $clean);
        $this->assertStringContainsString('rel="noopener noreferrer"', $clean);
        $this->assertStringContainsString('<code class="language-php">', $clean);
        $this->assertStringContainsString('alt="A"', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('<style', $clean);
        $this->assertStringNotContainsString('<form', $clean);
        $this->assertStringNotContainsString('<h1', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);

        $this->assertNull($sanitizer->rich('<p></p>'));
        $basic = $sanitizer->basic('<p>Hi</p><h2>No headings</h2><img src="/x.webp" alt="">');
        $this->assertStringContainsString('<p>Hi</p>', $basic);
        $this->assertStringNotContainsString('<h2', $basic);
        $this->assertStringNotContainsString('<img', $basic);
    }

    public function test_rich_content_builds_unique_heading_anchors_and_toc(): void
    {
        $prepared = app(RichContent::class)->prepare('<h2>Overview</h2><p>a</p><h3>Detail</h3><h2>Overview</h2><img src="/x.webp" alt=""><iframe src="https://www.youtube-nocookie.com/embed/abcdefghijk"></iframe>');

        $this->assertSame(['overview', 'detail', 'overview-2'], array_column($prepared['toc'], 'id'));
        $this->assertSame([2, 3, 2], array_column($prepared['toc'], 'level'));
        $this->assertStringContainsString('id="overview-2"', $prepared['html']);
        $this->assertStringContainsString('loading="lazy"', $prepared['html']);
        $this->assertStringContainsString('data-embed="video"', $prepared['html']);
    }

    public function test_reading_time_has_a_minimum_of_one_minute(): void
    {
        $content = app(RichContent::class);

        $this->assertSame(1, $content->readingTime('<p>Short.</p>'));
        $this->assertSame(3, $content->readingTime('<p>'.str_repeat('word ', 600).'</p>'));
    }

    public function test_csv_cells_are_protected_against_formula_injection(): void
    {
        $this->assertSame("'=SUM(A1:A2)", CsvExport::cell('=SUM(A1:A2)'));
        $this->assertSame("'+44 20", CsvExport::cell('+44 20'));
        $this->assertSame("'@cmd", CsvExport::cell('@cmd'));
        $this->assertSame('Plain text', CsvExport::cell('Plain text'));
    }

    public function test_heading_emphasis_escapes_html(): void
    {
        $this->assertSame('We build <span class="accent">things</span> &lt;b&gt;', (string) Text::emphasis('We build *things* <b>'));
        $this->assertSame('<p>One</p><p>Two<br />
lines</p>', (string) Text::paragraphs("One\n\nTwo\nlines"));
    }
}
