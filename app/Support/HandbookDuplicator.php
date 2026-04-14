<?php

namespace App\Support;

use App\Models\Handbook;
use App\Models\HandbookPagePosition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HandbookDuplicator
{
    public function duplicate(Handbook $sourceHandbook, int $ownerId, string $title): Handbook
    {
        $sourceHandbook->loadMissing([
            'positions.page.handbook',
            'images',
        ]);

        return DB::transaction(function () use ($ownerId, $sourceHandbook, $title): Handbook {
            $copiedHandbook = Handbook::create([
                'user_id' => $ownerId,
                'title' => $title,
                'slug' => $this->uniqueHandbookSlug($title),
                'description' => $sourceHandbook->description,
                'is_listed' => $sourceHandbook->is_listed,
            ]);

            foreach ($sourceHandbook->images as $image) {
                $copiedPath = "handbooks/{$copiedHandbook->id}/images/{$image->name}";
                $copiedImagePath = $this->isStoredImagePath($image->path) ? $copiedPath : $image->path;

                if ($this->isStoredImagePath($image->path) && Storage::disk($image->disk)->exists($image->path)) {
                    Storage::disk($image->disk)->copy($image->path, $copiedPath);
                }

                $copiedHandbook->images()->create([
                    'disk' => $image->disk,
                    'path' => $copiedImagePath,
                    'name' => $image->name,
                    'alt_text' => $image->alt_text,
                    'mime_type' => $image->mime_type,
                    'size' => $image->size,
                ]);
            }

            foreach ($sourceHandbook->positions->sortBy('position')->values() as $position) {
                $page = $position->page;

                if ($page->isEditableIn($sourceHandbook)) {
                    $copiedPage = $copiedHandbook->pages()->create([
                        'title' => $page->title,
                        'slug' => $this->uniquePageSlug($copiedHandbook, $page->title),
                        'position' => $position->position,
                        'body' => $this->copiedPageBody($page->body, $sourceHandbook, $copiedHandbook),
                        'is_shareable' => $page->is_shareable,
                    ]);

                    $copiedPage->positions()
                        ->where('handbook_id', $copiedHandbook->id)
                        ->update(['position' => $position->position]);

                    continue;
                }

                HandbookPagePosition::query()->create([
                    'handbook_id' => $copiedHandbook->id,
                    'handbook_page_id' => $page->id,
                    'position' => $position->position,
                ]);
            }

            return $copiedHandbook;
        });
    }

    private function uniqueHandbookSlug(string $title): string
    {
        $slug = str($title)->slug()->toString();
        $suffix = 1;
        $candidate = $slug;

        while (Handbook::query()->where('slug', $candidate)->exists()) {
            $candidate = "{$slug}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    private function uniquePageSlug(Handbook $handbook, string $title): string
    {
        $slug = str($title)->slug()->toString();
        $candidate = $slug;
        $suffix = 1;

        while ($handbook->pages()->where('slug', $candidate)->exists()) {
            $candidate = "{$slug}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    private function copiedPageBody(string $body, Handbook $sourceHandbook, Handbook $copiedHandbook): string
    {
        return str_replace(
            "/storage/handbooks/{$sourceHandbook->id}/images/",
            "/storage/handbooks/{$copiedHandbook->id}/images/",
            $body,
        );
    }

    private function isStoredImagePath(string $path): bool
    {
        if (Str::startsWith($path, 'data:')) {
            return false;
        }

        return filter_var($path, FILTER_VALIDATE_URL) === false;
    }
}
