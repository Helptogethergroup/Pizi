{{-- Adjust @extends to match your admin layout --}}
@extends('layouts.dashboard')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Review Moderation</h1>

    @if (session('admin_success'))
        <div class="mb-5 rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">
            {{ session('admin_success') }}
        </div>
    @endif

    {{-- Status tabs --}}
    <div class="flex flex-wrap gap-2 mb-4">
        @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'spam' => 'Spam', 'all' => 'All'] as $key => $label)
            <a href="{{ route('admin.reviews.index', ['status' => $key, 'q' => $q]) }}"
               class="px-3 py-1.5 rounded-full text-sm font-medium {{ $status === $key ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                {{ $label }}
                @if (isset($counts[$key]) && $counts[$key] > 0)
                    <span class="ml-1 text-xs opacity-80">({{ $counts[$key] }})</span>
                @endif
            </a>
        @endforeach
    </div>

    {{-- Search --}}
    <form method="GET" action="{{ route('admin.reviews.index') }}" class="flex gap-2 mb-5">
        <input type="hidden" name="status" value="{{ $status }}">
        <input type="text" name="q" value="{{ $q }}" placeholder="Search name, title, comment..."
            class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <button class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm font-semibold">Search</button>
    </form>

    {{-- Bulk form --}}
    <form method="POST" action="{{ route('admin.reviews.bulk') }}" id="bulkForm">
        @csrf
        <div class="flex items-center gap-2 mb-3">
            <select name="action" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="approved">Approve</option>
                <option value="rejected">Reject</option>
                <option value="spam">Mark Spam</option>
                <option value="delete">Delete</option>
            </select>
            <button class="px-4 py-2 rounded-lg bg-rose-600 text-white text-sm font-semibold hover:bg-rose-700"
                onclick="return confirm('Apply to selected reviews?')">Apply to Selected</button>
        </div>

        <div class="space-y-3">
            @forelse ($reviews as $r)
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                    <div class="flex items-start gap-3">
                        <input type="checkbox" name="ids[]" value="{{ $r->id }}" class="mt-1.5">
                        <div class="flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <span class="font-semibold text-gray-900">{{ $r->reviewer_name ?: 'Guest' }}</span>
                                    <span class="text-sm text-gray-500">· {{ $r->property->name ?? 'Property #' . $r->property_id }}</span>
                                </div>
                                <div class="text-amber-400">
                                    @for ($i = 1; $i <= 5; $i++){!! $i <= $r->rating ? '&#9733;' : '<span class="text-gray-300">&#9733;</span>' !!}@endfor
                                </div>
                            </div>
                            @if ($r->title)<div class="font-medium text-gray-800 mt-1">{{ $r->title }}</div>@endif
                            <p class="text-sm text-gray-700 mt-1">{{ $r->comment }}</p>
                            <div class="text-xs text-gray-400 mt-1">{{ $r->created_at?->format('d M Y, h:i A') }} · {{ $r->reviewer_phone ?: 'no phone' }}</div>
                        </div>
                    </div>

                    {{-- per-row quick actions --}}
                    <div class="flex flex-wrap gap-2 mt-3 pl-7">
                        @foreach (['approved' => 'bg-green-600', 'rejected' => 'bg-gray-500', 'spam' => 'bg-red-600'] as $st => $color)
                            <button type="submit" formaction="{{ route('admin.reviews.status', $r->id) }}" formmethod="POST"
                                name="status" value="{{ $st }}"
                                class="px-3 py-1 rounded text-xs font-semibold text-white {{ $color }} {{ $r->status === $st ? 'opacity-50' : '' }}">
                                {{ ucfirst($st) }}
                            </button>
                        @endforeach
                        <span class="text-xs text-gray-400 self-center">Current: {{ ucfirst($r->status) }}</span>
                    </div>
                </div>
            @empty
                <div class="text-center text-gray-500 py-10 bg-white rounded-xl border border-gray-100">No reviews in "{{ $status }}".</div>
            @endforelse
        </div>
    </form>

    <div class="mt-6">{{ $reviews->links() }}</div>
</div>

<script>
    // "select all" via checkbox in header could be added; per-row buttons need their own status hidden field.
    // Each quick-action button submits the bulkForm with formaction override + a status value.
</script>
@endsection
