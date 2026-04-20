@extends('contensio::admin.layout')

@section('title', 'Fake Posts Generator')

@section('content')
<div class="p-6 max-w-3xl">

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Fake Posts Generator</h1>
        <p class="mt-1 text-gray-500">Generate realistic test content using Faker text and Picsum Photos images (1600×900).</p>
    </div>

    {{-- ENV warning --}}
    @if(! in_array(app()->environment(), ['local', 'testing', 'development']))
    <div class="mb-6 flex items-start gap-3 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-800 text-sm">
        <i class="bi bi-exclamation-triangle-fill mt-0.5 shrink-0"></i>
        <div>
            <strong>Warning:</strong> You are running in the <code class="bg-red-100 px-1 rounded">{{ app()->environment() }}</code> environment.
            Fake content should only be generated in local or testing environments.
        </div>
    </div>
    @endif

    {{-- Flash --}}
    @if(session('success'))
    <div class="mb-6 flex items-center gap-2 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-green-800 text-sm">
        <i class="bi bi-check-circle-fill shrink-0"></i>
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="mb-6 flex items-center gap-2 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-800 text-sm">
        <i class="bi bi-x-circle-fill shrink-0"></i>
        {{ session('error') }}
    </div>
    @endif

    {{-- Existing fake posts stats --}}
    <div class="mb-8 flex items-center justify-between rounded-xl border border-gray-200 bg-white px-5 py-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                <i class="bi bi-file-earmark-text text-lg"></i>
            </div>
            <div>
                <div class="font-semibold text-gray-900">
                    {{ $fakeCount }} fake {{ Str::plural('post', $fakeCount) }} in database
                </div>
                <div class="text-sm text-gray-500">Tagged with <code class="bg-gray-100 px-1 rounded text-xs">_fake_post</code> meta key</div>
            </div>
        </div>
        @if($fakeCount > 0)
        <form method="POST" action="{{ route('contensio-fake-posts.delete-all') }}"
              onsubmit="return confirm('Delete all {{ $fakeCount }} fake post(s) and their images? This cannot be undone.')">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 font-semibold text-sm px-4 py-2 rounded-lg transition-colors">
                <i class="bi bi-trash3"></i>
                Delete all fake posts
            </button>
        </form>
        @endif
    </div>

    {{-- Generate form --}}
    <form method="POST" action="{{ route('contensio-fake-posts.generate') }}" id="generate-form">
        @csrf

        <div class="rounded-xl border border-gray-200 bg-white divide-y divide-gray-100">

            {{-- Content type --}}
            <div class="px-5 py-4 flex items-start gap-4">
                <label class="w-40 shrink-0 pt-2 text-sm font-semibold text-gray-700">Content type</label>
                <div class="flex-1">
                    <select name="content_type_id" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ember-500">
                        @foreach($contentTypes as $type)
                        <option value="{{ $type->id }}">{{ Str::headline($type->name) }}</option>
                        @endforeach
                    </select>
                    @error('content_type_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Language --}}
            <div class="px-5 py-4 flex items-start gap-4">
                <label class="w-40 shrink-0 pt-2 text-sm font-semibold text-gray-700">Language</label>
                <div class="flex-1">
                    <select name="language_id" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ember-500">
                        @foreach($languages as $language)
                        <option value="{{ $language->id }}" @selected($language->is_default)>
                            {{ $language->name ?? 'Language #' . $language->id }}
                            @if($language->is_default) (default) @endif
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Number of posts --}}
            <div class="px-5 py-4 flex items-start gap-4">
                <label class="w-40 shrink-0 pt-2 text-sm font-semibold text-gray-700">Number of posts</label>
                <div class="flex-1">
                    <input type="number" name="count" value="{{ old('count', 10) }}"
                           min="1" max="50" required
                           class="w-32 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ember-500">
                    <p class="mt-1 text-xs text-gray-400">Max 50 per batch to avoid timeouts.</p>
                    @error('count')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Fetch images --}}
            <div class="px-5 py-4 flex items-start gap-4">
                <label class="w-40 shrink-0 pt-2 text-sm font-semibold text-gray-700">Images</label>
                <div class="flex-1">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="with_images" value="0">
                        <input type="checkbox" name="with_images" value="1" checked
                               class="w-4 h-4 accent-ember-500">
                        <span class="text-sm text-gray-700">
                            Fetch featured images from
                            <a href="https://picsum.photos" target="_blank" class="text-ember-600 hover:underline">Picsum Photos</a>
                            (1600×900 px)
                        </span>
                    </label>
                </div>
            </div>

            {{-- Categories / terms --}}
            @if($taxonomies->isNotEmpty())
            <div class="px-5 py-4 flex items-start gap-4">
                <label class="w-40 shrink-0 pt-1.5 text-sm font-semibold text-gray-700">Categories</label>
                <div class="flex-1 space-y-4">
                    @foreach($taxonomies as $taxonomy)
                    @php
                        $taxName = $taxonomy->translations->first()?->labels['plural']
                            ?? $taxonomy->translations->first()?->labels['singular']
                            ?? Str::headline($taxonomy->name);
                    @endphp
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">{{ $taxName }}</p>
                        <div class="grid grid-cols-2 gap-1.5">
                            @foreach($taxonomy->terms as $term)
                            @php $termName = $term->translations->first()?->name ?? 'Term #' . $term->id @endphp
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" name="term_ids[]" value="{{ $term->id }}"
                                       class="w-4 h-4 accent-ember-500">
                                {{ $termName }}
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                    <p class="text-xs text-gray-400">Leave all unchecked to generate posts without categories.</p>
                </div>
            </div>
            @endif

        </div>

        {{-- Submit --}}
        <div class="mt-5 flex items-center gap-3">
            <button type="submit" id="generate-btn"
                    class="inline-flex items-center gap-2 bg-ember-500 hover:bg-ember-600 text-white font-semibold text-sm px-5 py-2.5 rounded-lg transition-colors">
                <i class="bi bi-magic" id="generate-icon"></i>
                <span id="generate-label">Generate fake posts</span>
            </button>
            <span class="text-xs text-gray-400" id="generate-hint">Posts will be published immediately and tagged for easy cleanup.</span>
        </div>

    </form>

    <script>
    document.getElementById('generate-form').addEventListener('submit', function () {
        var btn   = document.getElementById('generate-btn');
        var icon  = document.getElementById('generate-icon');
        var label = document.getElementById('generate-label');
        var hint  = document.getElementById('generate-hint');

        btn.disabled = true;
        btn.classList.remove('hover:bg-ember-600');
        btn.classList.add('opacity-75', 'cursor-not-allowed');

        icon.className = '';
        icon.innerHTML = '<svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>';

        label.textContent = 'Generating content, please wait\u2026';
        hint.textContent  = 'This may take a few seconds depending on the number of posts and images.';
    });
    </script>

    {{-- Artisan tip --}}
    <div class="mt-8 rounded-lg bg-gray-50 border border-gray-200 px-4 py-3">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Artisan command</p>
        <code class="text-sm text-gray-700 font-mono">php artisan contensio-fake-posts:generate --count=20 --no-images</code>
        <p class="mt-1.5 text-xs text-gray-400">
            Options: <code class="bg-gray-200 px-1 rounded">--count=N</code>
            <code class="bg-gray-200 px-1 rounded">--type=post</code>
            <code class="bg-gray-200 px-1 rounded">--terms=1,2,3</code>
            <code class="bg-gray-200 px-1 rounded">--no-images</code>
            <code class="bg-gray-200 px-1 rounded">--delete</code>
        </p>
    </div>

</div>
@endsection
