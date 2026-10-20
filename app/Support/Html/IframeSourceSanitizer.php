<?php

namespace App\Support\Html;

use App\Support\VideoEmbed;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/**
 * Restricts iframe sources to the approved HTTPS video players.
 */
final class IframeSourceSanitizer implements AttributeSanitizerInterface
{
    public function getSupportedElements(): ?array
    {
        return ['iframe'];
    }

    public function getSupportedAttributes(): ?array
    {
        return ['src'];
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        $parts = parse_url($value);

        if (($parts['scheme'] ?? null) !== 'https' || ! in_array(strtolower($parts['host'] ?? ''), VideoEmbed::ALLOWED_HOSTS, true)) {
            return null;
        }

        return $value;
    }
}
