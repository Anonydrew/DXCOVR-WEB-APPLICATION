<?php

namespace App\Services;

use App\Support\Html\IframeSourceSanitizer;
use App\Support\VideoEmbed;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer as SymfonySanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Allow-list sanitisation for administrator-authored rich text.
 *
 * Everything not explicitly allowed (scripts, styles, event handlers,
 * javascript: URLs, arbitrary iframes) is removed before storage.
 */
class HtmlSanitizer
{
    protected ?SymfonySanitizer $rich = null;

    protected ?SymfonySanitizer $basic = null;

    /**
     * Full editorial markup: headings, lists, quotes, code, images and approved video embeds.
     */
    public function rich(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $clean = trim(($this->rich ??= new SymfonySanitizer($this->richConfig()))->sanitize($html));

        return $clean === '' || $clean === '<p></p>' ? null : $clean;
    }

    /**
     * Inline formatting, paragraphs, lists and links only (FAQ answers, short bios).
     */
    public function basic(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $clean = trim(($this->basic ??= new SymfonySanitizer($this->basicConfig()))->sanitize($html));

        return $clean === '' || $clean === '<p></p>' ? null : $clean;
    }

    protected function basicConfig(): HtmlSanitizerConfig
    {
        return (new HtmlSanitizerConfig)
            ->allowElement('p')
            ->allowElement('br')
            ->allowElement('strong')
            ->allowElement('b')
            ->allowElement('em')
            ->allowElement('i')
            ->allowElement('u')
            ->allowElement('s')
            ->allowElement('ul')
            ->allowElement('ol', ['start'])
            ->allowElement('li')
            ->allowElement('a', ['href', 'title', 'target'])
            ->allowLinkSchemes(['https', 'http', 'mailto', 'tel'])
            ->allowRelativeLinks()
            ->forceAttribute('a', 'rel', 'noopener noreferrer')
            ->withMaxInputLength(200_000);
    }

    protected function richConfig(): HtmlSanitizerConfig
    {
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        return $this->basicConfig()
            ->allowElement('h2', ['id'])
            ->allowElement('h3', ['id'])
            ->allowElement('h4', ['id'])
            ->allowElement('blockquote')
            ->allowElement('pre')
            ->allowElement('code', ['class'])
            ->allowElement('hr')
            ->allowElement('figure')
            ->allowElement('figcaption')
            ->allowElement('img', ['src', 'alt', 'title', 'width', 'height', 'loading'])
            ->allowElement('div', ['data-youtube-video', 'data-embed'])
            ->allowElement('iframe', ['src', 'width', 'height', 'title', 'allowfullscreen', 'allow', 'loading'])
            ->allowMediaSchemes(['https', 'http'])
            ->allowMediaHosts(array_values(array_filter(array_merge(
                [$appHost],
                $this->storageHosts(),
                config('dxcovr.media.extra_hosts', []),
                VideoEmbed::ALLOWED_HOSTS,
            ))))
            ->allowRelativeMedias()
            ->withAttributeSanitizer(new IframeSourceSanitizer)
            ->withMaxInputLength(2_000_000);
    }

    /**
     * Hosts that serve files from the configured media disk.
     *
     * @return list<string>
     */
    protected function storageHosts(): array
    {
        $disk = config('dxcovr.media.disk', 'public');
        $url = config("filesystems.disks.{$disk}.url");

        return array_values(array_filter([is_string($url) ? parse_url($url, PHP_URL_HOST) : null]));
    }
}
