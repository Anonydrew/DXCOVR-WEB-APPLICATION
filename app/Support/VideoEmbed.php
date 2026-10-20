<?php

namespace App\Support;

/**
 * Converts YouTube and Vimeo page URLs into privacy-friendly embed URLs.
 * Any other host is rejected so arbitrary iframes cannot be injected.
 */
final class VideoEmbed
{
    public const ALLOWED_HOSTS = ['www.youtube-nocookie.com', 'www.youtube.com', 'player.vimeo.com'];

    /**
     * @return array{src: string, provider: string}|null
     */
    public static function fromUrl(?string $url): ?array
    {
        if (blank($url)) {
            return null;
        }

        $parts = parse_url(trim($url));
        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';

        if (! in_array($parts['scheme'] ?? '', ['http', 'https'], true)) {
            return null;
        }

        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'www.youtube-nocookie.com'], true)) {
            $id = null;

            if ($host === 'youtu.be') {
                $id = trim($path, '/');
            } elseif (str_starts_with($path, '/embed/') || str_starts_with($path, '/shorts/')) {
                $id = explode('/', trim($path, '/'))[1] ?? null;
            } else {
                parse_str($parts['query'] ?? '', $query);
                $id = $query['v'] ?? null;
            }

            if (is_string($id) && preg_match('/^[A-Za-z0-9_-]{6,20}$/', $id)) {
                return ['src' => 'https://www.youtube-nocookie.com/embed/'.$id, 'provider' => 'YouTube'];
            }

            return null;
        }

        if (in_array($host, ['vimeo.com', 'www.vimeo.com', 'player.vimeo.com'], true)) {
            if (preg_match('~/(?:video/)?(\d{6,12})~', $path, $matches)) {
                return ['src' => 'https://player.vimeo.com/video/'.$matches[1], 'provider' => 'Vimeo'];
            }
        }

        return null;
    }

    public static function isSupported(?string $url): bool
    {
        return self::fromUrl($url) !== null;
    }
}
