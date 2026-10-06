<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Keeps the rich-text tags produced by the meeting editor and drops everything else.
 */
final class MeetingHtmlSanitizer
{
    /** @var list<string> */
    private const ALLOWED = [
        'p', 'br', 'div', 'span', 'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'del',
        'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'blockquote', 'hr', 'font',
    ];

    /** @var list<string> */
    private const DROP_WITH_CONTENT = [
        'script', 'style', 'iframe', 'object', 'embed', 'link', 'meta', 'svg', 'math',
        'form', 'input', 'button', 'textarea', 'select', 'option', 'video', 'audio',
        'img', 'canvas', 'noscript', 'template', 'base', 'applet', 'frame', 'frameset',
    ];

    /** @var list<string> */
    private const NAMED_COLORS = [
        'black', 'white', 'silver', 'gray', 'grey', 'red', 'maroon', 'yellow', 'olive',
        'lime', 'green', 'aqua', 'teal', 'blue', 'navy', 'fuchsia', 'purple', 'orange',
        'transparent',
    ];

    public function sanitize(string $html): string
    {
        $html = str_replace("\0", '', $html);
        if (trim($html) === '') {
            return '';
        }

        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $wrapped = '<div id="__meeting_sanitizer_root">' . $html . '</div>';
        $dom->loadHTML('<?xml encoding="UTF-8">' . $wrapped, LIBXML_HTML_NODEFDTD | LIBXML_NONET);

        $root = null;
        foreach ($dom->getElementsByTagName('div') as $div) {
            if ($div->getAttribute('id') === '__meeting_sanitizer_root') {
                $root = $div;
                break;
            }
        }

        if (!$root instanceof \DOMElement) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);

            return '';
        }

        $this->sanitizeChildren($root);
        $root->removeAttribute('id');

        $clean = '';
        foreach ($root->childNodes as $child) {
            $piece = $dom->saveHTML($child);
            if (is_string($piece)) {
                $clean .= $piece;
            }
        }

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return trim($clean);
    }

    private function sanitizeChildren(\DOMNode $parent): void
    {
        $children = [];
        foreach ($parent->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child instanceof \DOMText) {
                continue;
            }

            if (!$child instanceof \DOMElement) {
                $child->parentNode?->removeChild($child);
                continue;
            }

            $tag = strtolower($child->tagName);
            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $child->parentNode?->removeChild($child);
                continue;
            }

            if (!in_array($tag, self::ALLOWED, true)) {
                $this->sanitizeChildren($child);
                $this->unwrap($child);
                continue;
            }

            $this->filterAttributes($child);
            $this->sanitizeChildren($child);
        }
    }

    private function unwrap(\DOMElement $element): void
    {
        $parent = $element->parentNode;
        if ($parent === null) {
            return;
        }

        while ($element->firstChild !== null) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }

    private function filterAttributes(\DOMElement $element): void
    {
        $tag = strtolower($element->tagName);
        $allowed = [];

        if ($element->hasAttribute('style')) {
            $style = $this->sanitizeStyle($element->getAttribute('style'));
            if ($style !== '') {
                $allowed['style'] = $style;
            }
        }

        if (in_array($tag, ['div', 'p', 'h1', 'h2', 'h3', 'h4', 'li', 'blockquote'], true) && $element->hasAttribute('align')) {
            $align = strtolower(trim($element->getAttribute('align')));
            if (in_array($align, ['left', 'center', 'right', 'justify', 'start', 'end'], true)) {
                $style = isset($allowed['style']) ? $allowed['style'] . '; ' : '';
                $allowed['style'] = $style . 'text-align: ' . $align;
            }
        }

        if ($tag === 'font') {
            $color = trim($element->getAttribute('color'));
            if ($color !== '' && $this->isSafeColor($color)) {
                $allowed['color'] = $color;
            }

            $size = trim($element->getAttribute('size'));
            if (preg_match('/^[1-7]$/', $size) === 1) {
                $allowed['size'] = $size;
            }
        }

        while ($element->attributes->length > 0) {
            $name = $element->attributes->item(0)?->name;
            if ($name === null) {
                break;
            }
            $element->removeAttribute($name);
        }

        foreach ($allowed as $name => $value) {
            $element->setAttribute($name, $value);
        }
    }

    private function sanitizeStyle(string $style): string
    {
        $kept = [];
        foreach (explode(';', $style) as $declaration) {
            if (!str_contains($declaration, ':')) {
                continue;
            }

            [$property, $value] = array_map('trim', explode(':', $declaration, 2));
            $property = strtolower($property);
            $value = trim((string) preg_replace('/\s*!important\s*$/i', '', $value));
            if ($value === '' || preg_match('/[\\\\@]|\/\*|\*\//', $value) === 1) {
                continue;
            }

            $clean = match ($property) {
                'color', 'background-color' => $this->isSafeColor($value) ? strtolower($value) : null,
                'font-size' => $this->safeFontSize($value),
                'font-weight' => preg_match('/^(normal|bold|bolder|lighter|[1-9]00)$/', strtolower($value)) === 1 ? strtolower($value) : null,
                'font-style' => preg_match('/^(normal|italic|oblique)$/', strtolower($value)) === 1 ? strtolower($value) : null,
                'text-decoration', 'text-decoration-line' => $this->safeTextDecoration($value),
                'text-align' => in_array(strtolower($value), ['left', 'center', 'right', 'justify', 'start', 'end'], true) ? strtolower($value) : null,
                default => null,
            };

            if ($clean === null) {
                continue;
            }

            if ($property === 'text-decoration-line') {
                $property = 'text-decoration';
            }

            $kept[$property] = $property . ': ' . $clean;
        }

        return implode('; ', array_values($kept));
    }

    private function safeFontSize(string $value): ?string
    {
        $value = strtolower($value);
        $keywords = ['xx-small', 'x-small', 'small', 'medium', 'large', 'x-large', 'xx-large', 'xxx-large', 'smaller', 'larger'];
        if (in_array($value, $keywords, true)) {
            return $value;
        }

        if (preg_match('/^(\d{1,3}(?:\.\d{1,2})?)(px|pt|em|rem|%)$/', $value, $matches) !== 1) {
            return null;
        }

        $size = (float) $matches[1];
        $ok = match ($matches[2]) {
            'px', 'pt' => $size >= 8 && $size <= 72,
            'em', 'rem' => $size >= 0.5 && $size <= 4,
            '%' => $size >= 50 && $size <= 300,
            default => false,
        };

        return $ok ? $value : null;
    }

    private function safeTextDecoration(string $value): ?string
    {
        $value = strtolower($value);
        $found = [];
        foreach (['underline', 'line-through', 'overline'] as $keyword) {
            if (str_contains($value, $keyword)) {
                $found[] = $keyword;
            }
        }

        if ($found !== []) {
            return implode(' ', $found);
        }

        return str_contains($value, 'none') ? 'none' : null;
    }

    private function isSafeColor(string $value): bool
    {
        $value = strtolower(trim($value));
        if (in_array($value, self::NAMED_COLORS, true)) {
            return true;
        }

        if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/', $value) === 1) {
            return true;
        }

        if (preg_match('/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/', $value, $matches) === 1) {
            return $this->inByte($matches[1]) && $this->inByte($matches[2]) && $this->inByte($matches[3]);
        }

        if (preg_match('/^rgba\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(0|1|0?\.\d+)\s*\)$/', $value, $matches) === 1) {
            return $this->inByte($matches[1]) && $this->inByte($matches[2]) && $this->inByte($matches[3]);
        }

        if (preg_match('/^hsl\(\s*(\d{1,3})\s*,\s*(\d{1,3})%\s*,\s*(\d{1,3})%\s*\)$/', $value, $matches) === 1) {
            return (int) $matches[1] <= 360 && (int) $matches[2] <= 100 && (int) $matches[3] <= 100;
        }

        if (preg_match('/^hsla\(\s*(\d{1,3})\s*,\s*(\d{1,3})%\s*,\s*(\d{1,3})%\s*,\s*(0|1|0?\.\d+)\s*\)$/', $value, $matches) === 1) {
            return (int) $matches[1] <= 360 && (int) $matches[2] <= 100 && (int) $matches[3] <= 100;
        }

        return false;
    }

    private function inByte(string $value): bool
    {
        $number = (int) $value;

        return $number >= 0 && $number <= 255;
    }
}
