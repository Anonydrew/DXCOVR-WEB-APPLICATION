<?php

namespace App\Support\Media;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMNode;
use InvalidArgumentException;

/**
 * Makes an uploaded SVG safe to publish by rebuilding it from an allowlist:
 * only drawing elements and presentation attributes survive. Scripts, event
 * handlers, foreign content, external references, entities and DOCTYPEs are
 * removed or rejected, so the stored file contains nothing executable.
 */
final class SvgSanitizer
{
    public const MAX_BYTES = 2 * 1024 * 1024;

    private const SVG_NAMESPACE = 'http://www.w3.org/2000/svg';

    private const XLINK_NAMESPACE = 'http://www.w3.org/1999/xlink';

    /**
     * Element names are case-sensitive in SVG.
     */
    private const ELEMENTS = [
        'svg', 'g', 'defs', 'symbol', 'use', 'title', 'desc', 'style',
        'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon',
        'text', 'tspan', 'clipPath', 'mask', 'pattern', 'marker', 'image',
        'linearGradient', 'radialGradient', 'stop',
        'filter', 'feBlend', 'feColorMatrix', 'feComposite', 'feDropShadow', 'feFlood', 'feGaussianBlur',
        'feMerge', 'feMergeNode', 'feMorphology', 'feOffset',
    ];

    private const ATTRIBUTES = [
        'id', 'class', 'style', 'transform', 'viewbox', 'preserveaspectratio', 'version', 'width', 'height',
        'x', 'y', 'x1', 'y1', 'x2', 'y2', 'cx', 'cy', 'r', 'rx', 'ry', 'fx', 'fy', 'dx', 'dy', 'd', 'points', 'pathlength',
        'fill', 'fill-opacity', 'fill-rule', 'clip-rule', 'clip-path', 'mask', 'opacity', 'color', 'display', 'visibility',
        'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'stroke-miterlimit', 'stroke-dasharray',
        'stroke-dashoffset', 'stroke-opacity', 'vector-effect', 'shape-rendering', 'paint-order', 'mix-blend-mode', 'isolation',
        'offset', 'stop-color', 'stop-opacity', 'gradientunits', 'gradienttransform', 'spreadmethod',
        'clippathunits', 'maskunits', 'maskcontentunits', 'patternunits', 'patterncontentunits', 'patterntransform',
        'markerwidth', 'markerheight', 'refx', 'refy', 'orient', 'markerunits', 'marker-start', 'marker-mid', 'marker-end',
        'font-family', 'font-size', 'font-weight', 'font-style', 'text-anchor', 'dominant-baseline', 'letter-spacing',
        'word-spacing', 'text-decoration', 'filter', 'flood-color', 'flood-opacity', 'stddeviation', 'in', 'in2', 'result',
        'mode', 'operator', 'k1', 'k2', 'k3', 'k4', 'values', 'type', 'radius', 'filterunits', 'primitiveunits',
        'color-interpolation-filters', 'enable-background', 'href', 'xml:space',
    ];

    /**
     * @return array{svg: string, width: ?int, height: ?int}
     */
    public static function sanitize(string $contents): array
    {
        if (strlen($contents) > self::MAX_BYTES) {
            throw new InvalidArgumentException('SVG files must be 2 MB or smaller.');
        }

        if (preg_match('/<!(DOCTYPE|ENTITY)/i', $contents)) {
            throw new InvalidArgumentException('SVG files with a DOCTYPE or entities are not accepted.');
        }

        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument;
        $loaded = $document->loadXML($contents, LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->documentElement;

        if (! $loaded || ! $root instanceof DOMElement || $root->localName !== 'svg' || ! in_array($root->namespaceURI, [null, self::SVG_NAMESPACE], true)) {
            throw new InvalidArgumentException('The file is not a valid SVG image.');
        }

        foreach (iterator_to_array($document->childNodes) as $node) {
            if ($node !== $root) {
                $document->removeChild($node);
            }
        }

        self::clean($root);

        if ($root->namespaceURI === null) {
            $root->setAttribute('xmlns', self::SVG_NAMESPACE);
        }

        [$width, $height] = self::dimensions($root);

        return ['svg' => '<?xml version="1.0" encoding="UTF-8"?>'."\n".$document->saveXML($root), 'width' => $width, 'height' => $height];
    }

    private static function clean(DOMElement $element): void
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            if (! self::keepAttribute($element, $attribute)) {
                $element->removeAttributeNode($attribute);
            }
        }

        if ($element->hasAttribute('style')) {
            $element->setAttribute('style', self::css($element->getAttribute('style')));
        }

        foreach (iterator_to_array($element->childNodes) as $child) {
            self::cleanChild($element, $child);
        }
    }

    private static function cleanChild(DOMElement $parent, DOMNode $child): void
    {
        if ($child instanceof DOMElement) {
            $allowed = in_array($child->localName, self::ELEMENTS, true) && in_array($child->namespaceURI, [null, self::SVG_NAMESPACE], true);

            if (! $allowed) {
                $parent->removeChild($child);

                return;
            }

            if ($child->localName === 'style') {
                $css = self::css($child->textContent);

                while ($child->firstChild) {
                    $child->removeChild($child->firstChild);
                }

                $child->appendChild($child->ownerDocument->createTextNode($css));
            }

            self::clean($child);

            return;
        }

        if (in_array($child->nodeType, [XML_TEXT_NODE, XML_CDATA_SECTION_NODE], true) && in_array($parent->localName, ['text', 'tspan', 'title', 'desc', 'style'], true)) {
            return;
        }

        if ($child->nodeType === XML_TEXT_NODE && trim((string) $child->nodeValue) === '') {
            return;
        }

        $parent->removeChild($child);
    }

    private static function keepAttribute(DOMElement $element, DOMAttr $attribute): bool
    {
        $name = strtolower($attribute->nodeName);
        $value = trim($attribute->value);

        if (str_starts_with($name, 'on') || ! in_array(preg_replace('/^xlink:/', '', $name), self::ATTRIBUTES, true)) {
            return false;
        }

        if ($attribute->namespaceURI !== null && ! in_array($attribute->namespaceURI, [self::XLINK_NAMESPACE, 'http://www.w3.org/XML/1998/namespace'], true)) {
            return false;
        }

        if (in_array($name, ['href', 'xlink:href'], true)) {
            // Internal references only; embedded raster images are allowed on <image>.
            return str_starts_with($value, '#')
                || ($element->localName === 'image' && preg_match('#^data:image/(png|jpe?g|gif|webp);base64,[a-z0-9+/=\s]+$#i', $value) === 1);
        }

        return ! self::unsafeValue($value);
    }

    private static function css(string $css): string
    {
        $css = str_replace(['<', '>'], '', $css);
        $css = (string) preg_replace('/@import[^;]*;?/i', '', $css);
        $css = (string) preg_replace('/url\(\s*[\'"]?\s*(?!#)[^)]*\)/i', 'none', $css);

        return self::unsafeValue($css) ? '' : $css;
    }

    private static function unsafeValue(string $value): bool
    {
        $compact = strtolower((string) preg_replace('/\s+/', '', $value));

        return str_contains($compact, 'javascript:')
            || str_contains($compact, 'vbscript:')
            || str_contains($compact, 'expression(')
            || str_contains($compact, '-moz-binding')
            || preg_match('/url\((?![\'"]?#)/', $compact) === 1;
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    private static function dimensions(DOMElement $root): array
    {
        $viewBox = preg_split('/[\s,]+/', trim($root->getAttribute('viewBox')));

        if (is_array($viewBox) && count($viewBox) === 4 && is_numeric($viewBox[2]) && is_numeric($viewBox[3]) && $viewBox[2] > 0 && $viewBox[3] > 0) {
            return [(int) round((float) $viewBox[2]), (int) round((float) $viewBox[3])];
        }

        $width = (float) $root->getAttribute('width');
        $height = (float) $root->getAttribute('height');

        return $width > 0 && $height > 0 ? [(int) round($width), (int) round($height)] : [null, null];
    }
}
