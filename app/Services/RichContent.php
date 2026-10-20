<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Str;

/**
 * Prepares sanitised rich text for display: heading anchors, a table of
 * contents, lazy-loaded images and responsive video wrappers.
 */
class RichContent
{
    /**
     * @return array{html: string, toc: list<array{id: string, text: string, level: int}>}
     */
    public function prepare(?string $html): array
    {
        if (blank($html)) {
            return ['html' => '', 'toc' => []];
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><div id="__root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($document);
        $toc = [];
        $usedIds = [];

        /** @var DOMElement $heading */
        foreach ($xpath->query('//h2|//h3') as $heading) {
            $text = trim($heading->textContent);

            if ($text === '') {
                continue;
            }

            $id = Str::slug($heading->getAttribute('id') ?: $text) ?: 'section';
            $base = $id;
            $counter = 2;

            while (in_array($id, $usedIds, true)) {
                $id = $base.'-'.$counter++;
            }

            $usedIds[] = $id;
            $heading->setAttribute('id', $id);
            $toc[] = ['id' => $id, 'text' => $text, 'level' => (int) substr($heading->nodeName, 1)];
        }

        /** @var DOMElement $image */
        foreach ($xpath->query('//img') as $image) {
            $image->setAttribute('loading', 'lazy');
            $image->setAttribute('decoding', 'async');
        }

        /** @var DOMElement $iframe */
        foreach ($xpath->query('//iframe') as $iframe) {
            $iframe->setAttribute('loading', 'lazy');
            $iframe->setAttribute('allowfullscreen', 'allowfullscreen');

            if (! $iframe->hasAttribute('title')) {
                $iframe->setAttribute('title', 'Embedded video');
            }

            $parent = $iframe->parentNode;

            if (! $parent instanceof DOMElement || ! $parent->hasAttribute('data-embed')) {
                $wrapper = $document->createElement('div');
                $wrapper->setAttribute('data-embed', 'video');
                $parent?->replaceChild($wrapper, $iframe);
                $wrapper->appendChild($iframe);
            }
        }

        $root = $document->getElementById('__root');
        $output = '';

        foreach ($root?->childNodes ?? [] as $child) {
            $output .= $document->saveHTML($child);
        }

        return ['html' => $output, 'toc' => $toc];
    }

    public function readingTime(?string $html, int $wordsPerMinute = 220): int
    {
        $words = str_word_count(strip_tags((string) $html));

        return max(1, (int) ceil($words / $wordsPerMinute));
    }
}
