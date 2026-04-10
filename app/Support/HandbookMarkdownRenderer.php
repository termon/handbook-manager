<?php

namespace App\Support;

use App\Models\Handbook;
use App\Models\HandbookImage;
use App\Models\HandbookPage;
use Illuminate\Support\Str;

class HandbookMarkdownRenderer
{
    public function render(Handbook $displayHandbook, HandbookPage $page): string
    {
        $html = Str::markdown($page->body);
        $html = $this->withHeadingAnchors($html);

        $html = preg_replace_callback(
            '/(<img\b[^>]*\ssrc=["\'])([^"\']+)(["\'][^>]*>)/i',
            fn (array $matches): string => $matches[1].$this->resolvedImageSource($page->handbook, $matches[2]).$matches[3],
            $html,
        ) ?? $html;

        return preg_replace_callback(
            '/(<a\b[^>]*\shref=["\'])([^"\']+)(["\'][^>]*>)/i',
            fn (array $matches): string => $matches[1].$this->resolvedPageHref($displayHandbook, $page, $matches[2]).$matches[3],
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
            $image = $this->handbookImageByName($handbook, ltrim($source, '/'));

            if ($image !== null) {
                return $image->relativeUrl();
            }

            return "/storage/handbooks/{$handbook->id}/images/".ltrim($source, '/');
        }

        return $source;
    }

    private function handbookImageByName(Handbook $handbook, string $name): ?HandbookImage
    {
        if ($name === '') {
            return null;
        }

        if ($handbook->relationLoaded('images')) {
            /** @var HandbookImage|null $image */
            $image = $handbook->images->firstWhere('name', $name);

            return $image;
        }

        return $handbook->images()->where('name', $name)->first();
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

    private function resolvedPageHref(Handbook $displayHandbook, HandbookPage $page, string $href): string
    {
        if ($this->isCrossHandbookPageLinkShorthand($href)) {
            return $this->resolvedCrossHandbookHref($href);
        }

        if (! $this->isRelativeHandbookPageLink($href)) {
            return $href;
        }

        [$pageSlug, $fragment] = array_pad(explode('#', $href, 2), 2, null);
        $pageSlug = trim($pageSlug, '/');

        $resolvedHandbook = $this->relativeLinkHandbook($displayHandbook, $page, $pageSlug);
        $resolvedHref = route('handbooks.show', [
            'handbook' => $resolvedHandbook,
            'pageSlug' => $pageSlug,
        ], false);

        if (filled($fragment)) {
            $resolvedHref .= "#{$fragment}";
        }

        return $resolvedHref;
    }

    private function relativeLinkHandbook(Handbook $displayHandbook, HandbookPage $page, string $pageSlug): Handbook
    {
        if ($displayHandbook->positions()
            ->whereHas('page', fn ($query) => $query->where('slug', $pageSlug))
            ->exists()) {
            return $displayHandbook;
        }

        $sourceHandbook = $page->handbook;

        if ($sourceHandbook->isNot($displayHandbook)
            && $sourceHandbook->pages()->where('slug', $pageSlug)->exists()) {
            return $sourceHandbook;
        }

        return $displayHandbook;
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
            'pageSlug' => $pageSlug,
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
