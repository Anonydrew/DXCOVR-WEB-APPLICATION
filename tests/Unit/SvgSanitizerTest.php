<?php

namespace Tests\Unit;

use App\Support\Media\SvgSanitizer;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SvgSanitizerTest extends TestCase
{
    public function test_a_typical_logo_keeps_its_drawing_and_dimensions(): void
    {
        $logo = <<<'SVG'
            <svg width="166" height="73" viewBox="0 0 166 73" fill="none" xmlns="http://www.w3.org/2000/svg">
            <g clip-path="url(#clip0)">
            <path d="M16.1 17.1H27.4L43.4 55.6H31.4Z" fill="black" style="fill:black;fill-opacity:1;"/>
            </g>
            <defs><clipPath id="clip0"><rect width="166" height="73" fill="white"/></clipPath></defs>
            </svg>
            SVG;

        $result = SvgSanitizer::sanitize($logo);

        $this->assertSame(166, $result['width']);
        $this->assertSame(73, $result['height']);
        $this->assertStringContainsString('<path d="M16.1 17.1H27.4L43.4 55.6H31.4Z" fill="black" style="fill:black;fill-opacity:1;"/>', $result['svg']);
        $this->assertStringContainsString('clip-path="url(#clip0)"', $result['svg']);
        $this->assertStringContainsString('<clipPath id="clip0">', $result['svg']);
    }

    public function test_a_logo_without_a_namespace_gets_one(): void
    {
        $result = SvgSanitizer::sanitize('<svg viewBox="0 0 10 10"><rect width="10" height="10"/></svg>');

        $this->assertStringContainsString('xmlns="http://www.w3.org/2000/svg"', $result['svg']);
        $this->assertSame(10, $result['width']);
    }

    public function test_illustrator_style_classes_are_kept(): void
    {
        $result = SvgSanitizer::sanitize('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><style>.cls-1{fill:#0b6e4f}</style><rect class="cls-1" width="10" height="10"/></svg>');

        $this->assertStringContainsString('.cls-1{fill:#0b6e4f}', $result['svg']);
        $this->assertStringContainsString('class="cls-1"', $result['svg']);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function attacks(): array
    {
        $wrap = fn (string $inner, string $rootAttributes = '') => '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 10 10"'.$rootAttributes.'>'.$inner.'</svg>';

        return [
            'script element' => [$wrap('<script>alert(1)</script><rect width="1" height="1"/>'), 'alert'],
            'event handler' => [$wrap('<rect width="1" height="1" onclick="alert(1)"/>'), 'onclick'],
            'root onload' => [$wrap('<rect width="1" height="1"/>', ' onload="alert(1)"'), 'onload'],
            'javascript link' => [$wrap('<a href="javascript:alert(1)"><rect width="1" height="1"/></a>'), 'javascript'],
            'external use' => [$wrap('<use xlink:href="https://evil.example/x.svg#a"/>'), 'evil.example'],
            'foreign object' => [$wrap('<foreignObject><iframe src="https://evil.example"></iframe></foreignObject>'), 'iframe'],
            'animate set href' => [$wrap('<set attributeName="href" to="javascript:alert(1)"/>'), 'javascript'],
            'external css' => [$wrap('<style>@import url(https://evil.example/a.css); rect{fill:url(https://evil.example/x)}</style>'), 'evil.example'],
            'style attribute url' => [$wrap('<rect width="1" height="1" style="fill:url(https://evil.example/x)"/>'), 'evil.example'],
            'external image' => [$wrap('<image href="https://evil.example/track.png" width="1" height="1"/>'), 'evil.example'],
            'svg data image' => [$wrap('<image href="data:image/svg+xml;base64,PHN2Zy8+" width="1" height="1"/>'), 'data:image/svg'],
            'processing instruction' => [$wrap('<?xml-stylesheet href="https://evil.example/x.xsl"?><rect width="1" height="1"/>'), 'evil.example'],
        ];
    }

    #[DataProvider('attacks')]
    public function test_dangerous_content_is_removed(string $svg, string $forbidden): void
    {
        $this->assertStringNotContainsStringIgnoringCase($forbidden, SvgSanitizer::sanitize($svg)['svg']);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function rejected(): array
    {
        return [
            'entity expansion' => ['<?xml version="1.0"?><!DOCTYPE svg [<!ENTITY a "aaaa">]><svg xmlns="http://www.w3.org/2000/svg">&a;</svg>'],
            'external entity' => ['<!DOCTYPE svg [<!ENTITY x SYSTEM "file:///etc/passwd">]><svg>&x;</svg>'],
            'html document' => ['<html><body><script>alert(1)</script></body></html>'],
            'not xml' => ['just some text'],
        ];
    }

    #[DataProvider('rejected')]
    public function test_unsafe_or_invalid_files_are_rejected(string $svg): void
    {
        $this->expectException(InvalidArgumentException::class);

        SvgSanitizer::sanitize($svg);
    }

    public function test_embedded_raster_images_are_allowed(): void
    {
        $result = SvgSanitizer::sanitize('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"><image href="data:image/png;base64,iVBORw0KGgo=" width="1" height="1"/></svg>');

        $this->assertStringContainsString('data:image/png;base64,iVBORw0KGgo=', $result['svg']);
    }
}
