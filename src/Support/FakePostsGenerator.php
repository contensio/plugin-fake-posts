<?php

/**
 * Fake Posts Generator — Contensio plugin.
 * https://contensio.com
 *
 * @copyright   Copyright (c) 2026 Iosif Gabriel Chimilevschi
 * @license     https://www.gnu.org/licenses/agpl-3.0.txt  AGPL-3.0-or-later
 */

namespace Contensio\FakePosts\Support;

use Contensio\Models\Content;
use Contensio\Models\ContentTranslation;
use Contensio\Models\Language;
use Contensio\Models\Media;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FakePostsGenerator
{
    /**
     * Image dimensions fetched from Picsum Photos.
     * 1600×900 — 16:9, large editorial format.
     */
    const IMAGE_WIDTH  = 1600;
    const IMAGE_HEIGHT = 900;

    const META_KEY = '_fake_post';

    // ── Public API ─────────────────────────────────────────────────────────────

    /**
     * Generate fake posts.
     *
     * @param  int    $contentTypeId
     * @param  int    $languageId
     * @param  int    $count
     * @param  array  $termIds        Term IDs to attach
     * @param  bool   $withImages     Download and attach Picsum images
     * @return int    Number of posts successfully created
     */
    public function generate(
        int $contentTypeId,
        int $languageId,
        int $count,
        array $termIds = [],
        bool $withImages = true
    ): int {
        $faker   = Faker::create();
        $created = 0;

        for ($i = 0; $i < $count; $i++) {
            try {
                $mediaId = null;

                if ($withImages) {
                    $mediaId = $this->downloadImage($languageId, $faker);
                }

                // Base content record
                $content = Content::create([
                    'code'            => Str::random(16),
                    'content_type_id' => $contentTypeId,
                    'author_id'       => auth()->id(),
                    'status'          => 'published',
                    'featured_image_id' => $mediaId,
                    'published_at'    => now()->subDays(rand(0, 365))->subHours(rand(0, 23)),
                    'blocks'          => [],
                ]);

                // Translation
                $title = rtrim($faker->sentence(rand(5, 12)), '.');
                $slug  = $this->uniqueSlug(Str::slug($title), $languageId);
                $body  = implode('', array_map(
                    fn($p) => "<p>{$p}</p>",
                    $faker->paragraphs(rand(3, 7))
                ));

                ContentTranslation::create([
                    'content_id'       => $content->id,
                    'language_id'      => $languageId,
                    'title'            => $title,
                    'slug'             => $slug,
                    'excerpt'          => $faker->paragraph(2),
                    'body'             => $body,
                    'meta_title'       => null,
                    'meta_description' => null,
                ]);

                // Terms
                if (! empty($termIds)) {
                    DB::table('content_terms')->insert(
                        array_map(fn($termId) => [
                            'content_id' => $content->id,
                            'term_id'    => (int) $termId,
                        ], $termIds)
                    );
                }

                // Mark as fake so it can be bulk-deleted
                DB::table('content_meta')->insert([
                    'content_id'  => $content->id,
                    'meta_key'    => self::META_KEY,
                    'meta_value'  => '1',
                    'language_id' => null,
                ]);

                $created++;
            } catch (\Throwable $e) {
                Log::warning("[FakePosts] Failed to create post #{$i}: " . $e->getMessage());
            }
        }

        return $created;
    }

    /**
     * Delete all fake posts and their associated media.
     *
     * @return array{posts: int, media: int}
     */
    public function deleteAll(): array
    {
        $fakeIds = DB::table('content_meta')
            ->where('meta_key', self::META_KEY)
            ->where('meta_value', '1')
            ->pluck('content_id')
            ->all();

        if (empty($fakeIds)) {
            return ['posts' => 0, 'media' => 0];
        }

        // Collect media IDs before deleting content
        $mediaIds = Content::whereIn('id', $fakeIds)
            ->whereNotNull('featured_image_id')
            ->pluck('featured_image_id')
            ->all();

        // Delete content (cascades to translations, meta, terms, etc.)
        $postCount = Content::whereIn('id', $fakeIds)->delete();

        // Delete media files and records
        $mediaCount = 0;
        foreach (Media::whereIn('id', $mediaIds)->get() as $media) {
            try {
                Storage::disk($media->disk ?? 'public')->delete($media->file_path);
                $media->delete();
                $mediaCount++;
            } catch (\Throwable $e) {
                Log::warning("[FakePosts] Failed to delete media #{$media->id}: " . $e->getMessage());
            }
        }

        return ['posts' => $postCount, 'media' => $mediaCount];
    }

    /**
     * Count how many fake posts currently exist.
     */
    public function count(): int
    {
        return DB::table('content_meta')
            ->where('meta_key', self::META_KEY)
            ->where('meta_value', '1')
            ->count();
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    /**
     * Download a random image from Picsum Photos and save it to the media library.
     * Returns the new Media record's ID, or null on failure.
     */
    private function downloadImage(int $languageId, \Faker\Generator $faker): ?int
    {
        try {
            $seed = Str::random(8);
            $url  = 'https://picsum.photos/seed/' . $seed . '/' . self::IMAGE_WIDTH . '/' . self::IMAGE_HEIGHT;

            $response = Http::timeout(15)->get($url);

            if (! $response->successful()) {
                return null;
            }

            $imageData = $response->body();

            $year   = now()->format('Y');
            $month  = now()->format('m');
            $uuid   = (string) Str::uuid();
            $folder = "uploads/{$year}/{$month}";
            $fileName = "{$uuid}.jpg";
            $filePath = "{$folder}/{$fileName}";

            Storage::disk('public')->put($filePath, $imageData);

            $media = Media::create([
                'code'      => Str::random(16),
                'user_id'   => auth()->id(),
                'file_name' => $fileName,
                'file_path' => $filePath,
                'folder'    => $folder,
                'disk'      => 'public',
                'mime_type' => 'image/jpeg',
                'file_size' => strlen($imageData),
                'width'     => self::IMAGE_WIDTH,
                'height'    => self::IMAGE_HEIGHT,
            ]);

            // Add alt text translation
            DB::table('media_translations')->insert([
                'media_id'    => $media->id,
                'language_id' => $languageId,
                'alt_text'    => rtrim($faker->sentence(4), '.'),
                'title'       => null,
                'caption'     => null,
            ]);

            return $media->id;
        } catch (\Throwable $e) {
            Log::warning('[FakePosts] Image download failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate a unique slug within content_translations for the given language.
     */
    private function uniqueSlug(string $slug, int $languageId): string
    {
        if (empty($slug)) {
            $slug = 'post-' . Str::random(6);
        }

        $base = $slug;
        $i    = 1;

        while (DB::table('content_translations')
            ->where('slug', $slug)
            ->where('language_id', $languageId)
            ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
