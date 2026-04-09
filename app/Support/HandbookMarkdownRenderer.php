<?php

namespace App\Support;

use App\Models\Handbook;
use Illuminate\Support\Str;

class HandbookMarkdownRenderer
{
    public function render(Handbook $handbook, string $markdown): string
    {
        $html = Str::markdown($markdown);
        $html = $this->withHeadingAnchors($html);

        $html = preg_replace_callback(
            '/(<img\b[^>]*\ssrc=["\'])([^"\']+)(["\'][^>]*>)/i',
            fn (array $matches): string => $matches[1].$this->resolvedImageSource($handbook, $matches[2]).$matches[3],
            $html,
        ) ?? $html;

        return preg_replace_callback(
            '/(<a\b[^>]*\shref=["\'])([^"\']+)(["\'][^>]*>)/i',
            fn (array $matches): string => $matches[1].$this->resolvedPageHref($handbook, $matches[2]).$matches[3],
            $html,
        ) ?? $html;
    }

    private function withHeadingAnchors(string $html): string
    {
        $usedIds = [];

        return preg_replace_callback(
            '/<h([1-6])([^>]*)>(.*?)<\/h\1>/is',
            function (array $matches) use (&$usedIds): string {
                [$fullMatch, $level, $attributes, $contents] = $matches;

                if (preg_match('/\sid=["\'][^"\']+["\']/i', $attributes) === 1) {
                    return $fullMatch;
                }

                $baseId = Str::slug(html_entity_decode(strip_tags($contents), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                if ($baseId === '') {
                    return $fullMatch;
                }

                $headingId = $baseId;
                $suffix = 2;

                while (in_array($headingId, $usedIds, true)) {
                    $headingId = "{$baseId}-{$suffix}";
                    $suffix++;
                }

                $usedIds[] = $headingId;

                return sprintf('<h%s%s id="%s">%s</h%s>', $level, $attributes, $headingId, $contents, $level);
            },
            $html,
        ) ?? $html;
    }

    private function resolvedImageSource(Handbook $handbook, string $source): string
    {
        if ($this->isRelativeHandbookImage($source)) {
            return "/storage/handbooks/{$handbook->id}/images/".ltrim($source, '/');
        }

        return $source;
    }

    private function isRelativeHandbookImage(string $source): bool
    {
        if ($source === '' || str_starts_with($source, '#') || str_starts_with($source, '/')) {
            return false;
        }

        if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $source) === 1) {
            return false;
        }

        return ! str_contains($source, '../');
    }

    private function resolvedPageHref(Handbook $handbook, string $href): string
    {
        if ($this->isCrossHandbookPageLinkShorthand($href)) {
            return $this->resolvedCrossHandbookHref($href);
        }

        if (! $this->isRelativeHandbookPageLink($href)) {
            return $href;
        }

        [$pageSlug, $fragment] = array_pad(explode('#', $href, 2), 2, null);
        $pageSlug = trim($pageSlug, '/');

        $resolvedHref = route('handbooks.show', [
            'handbook' => $handbook,
            'page' => $pageSlug,
        ], false);

        if (filled($fragment)) {
            $resolvedHref .= "#{$fragment}";
        }

        return $resolvedHref;
    }

    private function resolvedCrossHandbookHref(string $href): string
    {
        [$path, $fragment] = array_pad(explode('#', $href, 2), 2, null);

        $segments = collect(explode('/', trim($path, '/')))
            ->filter(fn (string $segment): bool => $segment !== '')
            ->values();

        $handbookSlug = $segments->get(0);
        $pageSlug = $segments->get(1);

        $resolvedHref = route('handbooks.show', [
            'handbook' => $handbookSlug,
            'page' => $pageSlug,
        ], false);

        if (filled($fragment)) {
            $resolvedHref .= "#{$fragment}";
        }

        return $resolvedHref;
    }

    private function isRelativeHandbookPageLink(string $href): bool
    {
        if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, '/')) {
            return false;
        }

        if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $href) === 1) {
            return false;
        }

        if (str_contains($href, '../') || str_contains($href, '?')) {
            return false;
        }

        return true;
    }

    private function isCrossHandbookPageLinkShorthand(string $href): bool
    {
        if (! str_starts_with($href, '/')) {
            return false;
        }

        if (str_starts_with($href, '//') || str_contains($href, '?')) {
            return false;
        }

        [$path] = explode('#', $href, 2);

        $segments = collect(explode('/', trim($path, '/')))
            ->filter(fn (string $segment): bool => $segment !== '')
            ->values();

        if ($segments->count() !== 2) {
            return false;
        }

        return $segments->every(
            fn (string $segment): bool => preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/i', $segment) === 1,
        );
    }
}
