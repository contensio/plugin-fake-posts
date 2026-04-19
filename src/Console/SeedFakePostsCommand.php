<?php

/**
 * Fake Posts Generator — Contensio plugin.
 * https://contensio.com
 *
 * @copyright   Copyright (c) 2026 Iosif Gabriel Chimilevschi
 * @license     https://www.gnu.org/licenses/agpl-3.0.txt  AGPL-3.0-or-later
 */

namespace Contensio\FakePosts\Console;

use Contensio\FakePosts\Support\FakePostsGenerator;
use Contensio\Models\ContentType;
use Contensio\Models\Language;
use Illuminate\Console\Command;

class SeedFakePostsCommand extends Command
{
    protected $signature = 'contensio:fake-posts
                            {--count=10       : Number of posts to generate}
                            {--type=          : Content type name (e.g. post). Defaults to first available type.}
                            {--terms=         : Comma-separated term IDs to attach}
                            {--no-images      : Skip downloading images from Picsum}
                            {--delete         : Delete all existing fake posts instead of generating}';

    protected $description = 'Generate fake posts for development and testing';

    public function handle(FakePostsGenerator $generator): int
    {
        if ($this->option('delete')) {
            $this->info('Deleting all fake posts...');
            $result = $generator->deleteAll();
            $this->info("Deleted {$result['posts']} post(s) and {$result['media']} image(s).");
            return self::SUCCESS;
        }

        // Resolve content type
        $typeName = $this->option('type');
        $type = $typeName
            ? ContentType::where('name', $typeName)->firstOrFail()
            : ContentType::first();

        if (! $type) {
            $this->error('No content types found. Create one in the admin panel first.');
            return self::FAILURE;
        }

        // Resolve language
        $language = Language::where('is_default', true)->first() ?? Language::first();

        if (! $language) {
            $this->error('No languages found.');
            return self::FAILURE;
        }

        $count      = max(1, min(200, (int) $this->option('count')));
        $termIds    = $this->option('terms')
            ? array_map('intval', explode(',', $this->option('terms')))
            : [];
        $withImages = ! $this->option('no-images');

        $this->info("Generating {$count} fake post(s) of type '{$type->name}'...");
        $this->info($withImages ? 'Fetching images from Picsum Photos (1600×900)...' : 'Skipping images.');

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        // Run in a loop so the progress bar updates
        $created = 0;
        for ($i = 0; $i < $count; $i++) {
            $created += $generator->generate(
                contentTypeId: $type->id,
                languageId:    $language->id,
                count:         1,
                termIds:       $termIds,
                withImages:    $withImages,
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done. {$created} post(s) created.");

        return self::SUCCESS;
    }
}
