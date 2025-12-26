<?php

namespace App\Services;

use Stevebauman\Purify\Facades\Purify;

/**
 * HTML Sanitization Service
 *
 * Provides allowlist-based HTML sanitization to prevent XSS attacks.
 * Uses stevebauman/purify library for comprehensive HTML cleaning.
 *
 * SEC-001: HTML Sanitization
 */
class HtmlSanitizationService
{
    /**
     * Default allowed HTML tags for general content.
     */
    protected const DEFAULT_ALLOWED_TAGS = [
        'p', 'br', 'strong', 'em', 'u', 's', 'b', 'i',
        'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'a', 'blockquote', 'code', 'pre',
    ];

    /**
     * Default allowed attributes per tag.
     */
    protected const DEFAULT_ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'title', 'rel'],
        'code' => ['class'],
        'pre' => ['class'],
    ];

    /**
     * Sanitize HTML content with default allowlist.
     *
     * @param  string  $html  The HTML content to sanitize
     * @param  array  $config  Optional custom configuration
     * @return string Sanitized HTML
     */
    public function sanitize(string $html, array $config = []): string
    {
        if (empty($html)) {
            return '';
        }

        // Default configuration
        $defaultConfig = [
            'HTML.Allowed' => implode(',', self::DEFAULT_ALLOWED_TAGS),
            'HTML.AllowedAttributes' => $this->buildAllowedAttributes(),
            'AutoFormat.Linkify' => true,
            'AutoFormat.RemoveEmpty' => true,
            'HTML.TargetBlank' => true,
        ];

        // Merge custom config
        $purifyConfig = array_merge($defaultConfig, $config);

        return Purify::config($purifyConfig)->clean($html);
    }

    /**
     * Sanitize HTML for rich text editor content.
     * Allows more formatting options.
     *
     * @param  string  $html  The HTML content to sanitize
     * @return string Sanitized HTML
     */
    public function sanitizeRichText(string $html): string
    {
        $richTextTags = array_merge(self::DEFAULT_ALLOWED_TAGS, [
            'div', 'span', 'img', 'table', 'thead', 'tbody', 'tr', 'td', 'th',
            'hr', 'sub', 'sup', 'strike', 'del',
        ]);

        return $this->sanitize($html, [
            'HTML.Allowed' => implode(',', $richTextTags),
            'HTML.AllowedAttributes' => $this->buildAllowedAttributes([
                'img' => ['src', 'alt', 'title', 'width', 'height', 'class'],
                'div' => ['class'],
                'span' => ['class'],
                'table' => ['class', 'border'],
                'td' => ['colspan', 'rowspan'],
                'th' => ['colspan', 'rowspan'],
            ]),
        ]);
    }

    /**
     * Sanitize HTML for comments/messages.
     * Very restrictive - only basic formatting.
     *
     * @param  string  $html  The HTML content to sanitize
     * @return string Sanitized HTML
     */
    public function sanitizeComment(string $html): string
    {
        return $this->sanitize($html, [
            'HTML.Allowed' => 'p,br,strong,em,u,a',
            'HTML.AllowedAttributes' => 'a.href,a.title,a.rel',
        ]);
    }

    /**
     * Sanitize plain text (strip all HTML).
     *
     * @param  string  $html  The HTML content to sanitize
     * @return string Plain text
     */
    public function sanitizePlainText(string $html): string
    {
        return Purify::config([
            'HTML.Allowed' => '',
        ])->clean($html);
    }

    /**
     * Build allowed attributes configuration string.
     *
     * @param  array  $additional  Additional attributes to allow
     * @return string Configuration string
     */
    protected function buildAllowedAttributes(array $additional = []): string
    {
        $attributes = array_merge(self::DEFAULT_ALLOWED_ATTRIBUTES, $additional);
        $config = [];

        foreach ($attributes as $tag => $attrs) {
            foreach ($attrs as $attr) {
                $config[] = "{$tag}.{$attr}";
            }
        }

        return implode(',', $config);
    }

    /**
     * Validate and sanitize URL in href attributes.
     *
     * @param  string  $url  The URL to validate
     * @return string|null Validated URL or null if invalid
     */
    public function validateUrl(string $url): ?string
    {
        // Remove dangerous protocols
        $dangerousProtocols = ['javascript:', 'data:', 'vbscript:', 'file:'];
        foreach ($dangerousProtocols as $protocol) {
            if (stripos($url, $protocol) === 0) {
                return null;
            }
        }

        // Validate URL format
        $filtered = filter_var($url, FILTER_VALIDATE_URL);
        if ($filtered === false) {
            return null;
        }

        // Only allow http, https, mailto, tel
        $scheme = parse_url($filtered, PHP_URL_SCHEME);
        $allowedSchemes = ['http', 'https', 'mailto', 'tel'];
        if (! in_array(strtolower($scheme), $allowedSchemes)) {
            return null;
        }

        return $filtered;
    }
}
