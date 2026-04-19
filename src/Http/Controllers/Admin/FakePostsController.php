<?php

/**
 * Fake Posts Generator — Contensio plugin.
 * https://contensio.com
 *
 * @copyright   Copyright (c) 2026 Iosif Gabriel Chimilevschi
 * @license     https://www.gnu.org/licenses/agpl-3.0.txt  AGPL-3.0-or-later
 */

namespace Contensio\FakePosts\Http\Controllers\Admin;

use Contensio\FakePosts\Support\FakePostsGenerator;
use Contensio\Models\ContentType;
use Contensio\Models\Language;
use Contensio\Models\Taxonomy;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class FakePostsController extends Controller
{
    public function __construct(private readonly FakePostsGenerator $generator) {}

    public function index()
    {
        $defaultLanguage = Language::where('is_default', true)->first()
            ?? Language::first();

        $contentTypes = ContentType::orderBy('name')->get();

        $languages = Language::where('status', 'active')
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();

        // Terms grouped by taxonomy, using default language names
        $taxonomies = Taxonomy::with([
            'terms.translations' => fn($q) => $q->where('language_id', $defaultLanguage?->id),
        ])->whereHas('terms')->get();

        $fakeCount = $this->generator->count();

        return view('fake-posts::admin.index', compact(
            'contentTypes',
            'languages',
            'defaultLanguage',
            'taxonomies',
            'fakeCount',
        ));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'content_type_id' => 'required|integer|exists:content_types,id',
            'language_id'     => 'required|integer|exists:languages,id',
            'count'           => 'required|integer|min:1|max:50',
            'term_ids'        => 'nullable|array',
            'term_ids.*'      => 'integer',
            'with_images'     => 'boolean',
        ]);

        $created = $this->generator->generate(
            contentTypeId: (int) $request->input('content_type_id'),
            languageId:    (int) $request->input('language_id'),
            count:         (int) $request->input('count'),
            termIds:       $request->input('term_ids', []),
            withImages:    $request->boolean('with_images', true),
        );

        return back()->with('success', "Generated {$created} fake post(s) successfully.");
    }

    public function deleteAll()
    {
        $result = $this->generator->deleteAll();

        return back()->with('success',
            "Deleted {$result['posts']} fake post(s) and {$result['media']} image(s)."
        );
    }
}
