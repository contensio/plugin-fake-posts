<?php

/**
 * Fake Posts Generator — Contensio plugin.
 * https://contensio.com
 *
 * @copyright   Copyright (c) 2026 Iosif Gabriel Chimilevschi
 * @license     https://www.gnu.org/licenses/agpl-3.0.txt  AGPL-3.0-or-later
 */

namespace Contensio\FakePosts;

use Contensio\Support\Hook;
use Contensio\FakePosts\Console\SeedFakePostsCommand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class FakePostsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'fake-posts');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->commands([SeedFakePostsCommand::class]);
        }

        Hook::add('contensio/admin/settings-cards', function () {
            return view('fake-posts::partials.settings-hub-card')->render();
        });

        // Badge in content/posts listing rows
        Hook::add('contensio/admin/content-row-badges', function ($item) {
            $isFake = DB::table('content_meta')
                ->where('content_id', $item->id)
                ->where('meta_key', '_fake_post')
                ->where('meta_value', '1')
                ->exists();

            if (! $isFake) {
                return '';
            }

            return '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200">'
                . '<i class="bi bi-magic"></i> Fake</span>';
        });
    }
}
