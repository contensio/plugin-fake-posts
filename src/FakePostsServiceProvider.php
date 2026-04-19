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
    }
}
