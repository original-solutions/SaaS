<?php

namespace App\Support;

class RichTextSanitiser
{
    /**
     * Allowed HTML tags.
     *
     * @var array<int, string>
     */
    protected static array $allowedTags = [
        'p', 'br', 'b', 'strong', 'i', 'em', 'u', 'a', 'ul', 'ol', 'li',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'pre', 'code',
        'img', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'hr', 'span',
        'div', 'sub', 'sup',
    ];

    /**
     * Allowed HTML attributes per tag.
     *
     * @var array<string, array<int, string>>
     */
    protected static array $allowedAttributes = [
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'width', 'height'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan'],
        'span' => ['class'],
        'div' => ['class'],
    ];

    /**
     * Sanitise HTML input by stripping disallowed tags and attributes.
     */
    public static function sanitise(string $html): string
    {
        // Strip all tags except allowed ones
        $allowedTagString = implode('', array_map(fn ($tag) => "<{$tag}>", static::$allowedTags));
        $sanitised = strip_tags($html, $allowedTagString);

        // Remove event handlers and dangerous attributes
        $sanitised = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $sanitised);
        $sanitised = preg_replace('/\s*on\w+\s*=\s*\S+/i', '', $sanitised);

        // Remove javascript: URIs
        $sanitised = preg_replace('/href\s*=\s*["\']javascript:[^"\']*["\']/i', 'href="#"', $sanitised);
        $sanitised = preg_replace('/src\s*=\s*["\']javascript:[^"\']*["\']/i', 'src=""', $sanitised);

        return $sanitised;
    }
}
