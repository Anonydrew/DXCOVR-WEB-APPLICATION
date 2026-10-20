<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

final class Text
{
    /**
     * Escape text, then turn *wrapped words* into accent-coloured emphasis.
     * Lets editors highlight words in headings without entering HTML.
     */
    public static function emphasis(?string $text, string $class = 'accent'): HtmlString
    {
        $escaped = e((string) $text);

        return new HtmlString((string) preg_replace('/\*(.+?)\*/u', '<span class="'.e($class).'">$1</span>', $escaped));
    }

    /**
     * Remove emphasis markers for plain-text contexts such as meta tags.
     */
    public static function plain(?string $text): string
    {
        return str_replace('*', '', (string) $text);
    }

    /**
     * Escape text and convert line breaks into paragraphs.
     */
    public static function paragraphs(?string $text): HtmlString
    {
        $paragraphs = preg_split('/\R{2,}/', trim((string) $text)) ?: [];

        return new HtmlString(collect($paragraphs)
            ->filter(fn (string $paragraph) => trim($paragraph) !== '')
            ->map(fn (string $paragraph) => '<p>'.nl2br(e(trim($paragraph))).'</p>')
            ->implode(''));
    }
}
