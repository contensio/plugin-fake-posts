{{-- Settings hub card for the Fake Posts Generator plugin --}}

@php
    $fakeCount = \Illuminate\Support\Facades\DB::table('content_meta')
        ->where('meta_key', '_fake_post')
        ->where('meta_value', '1')
        ->count();
@endphp

<a href="{{ route('fake-posts.index') }}"
   class="block bg-white border border-gray-200 rounded-xl p-5 hover:border-ember-300 hover:shadow-sm transition group">
    <div class="flex items-start justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-500 flex items-center justify-center text-lg">
                <i class="bi bi-magic"></i>
            </div>
            <div>
                <div class="font-semibold text-ink-900 group-hover:text-ember-600 transition-colors">Fake Posts</div>
                <div class="text-sm text-ink-500 mt-0.5">
                    @if($fakeCount > 0)
                        {{ $fakeCount }} fake {{ Str::plural('post', $fakeCount) }} in database
                    @else
                        No fake posts generated
                    @endif
                </div>
            </div>
        </div>
        <i class="bi bi-chevron-right text-gray-400 group-hover:text-ember-500 transition-colors mt-1"></i>
    </div>
</a>
