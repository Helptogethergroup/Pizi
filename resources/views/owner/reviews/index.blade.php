{{-- Adjust @extends to match your dashboard layout (e.g. 'dashboard' or 'layouts.dashboard') --}}
@extends('layouts.dashboard')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">My Reviews</h1>

    @if (session('owner_success'))
        <div class="mb-5 rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">
            {{ session('owner_success') }}
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <div class="text-3xl font-bold text-amber-500">{{ $stats['avg'] }} <span class="text-base">&#9733;</span></div>
            <div class="text-sm text-gray-500 mt-1">Average Rating</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <div class="text-3xl font-bold text-gray-900">{{ $stats['total'] }}</div>
            <div class="text-sm text-gray-500 mt-1">Total Reviews</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <div class="text-3xl font-bold text-orange-500">{{ $stats['pending'] }}</div>
            <div class="text-sm text-gray-500 mt-1">Pending Approval</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <div class="text-3xl font-bold text-rose-500">{{ $stats['no_response'] }}</div>
            <div class="text-sm text-gray-500 mt-1">Awaiting Reply</div>
        </div>
    </div>

    {{-- List --}}
    <div class="space-y-5">
        @forelse ($reviews as $r)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="font-semibold text-gray-900">{{ $r->reviewer_name ?: 'Guest' }}</div>
                        <div class="text-sm text-gray-500">{{ $r->property->name ?? 'Property #' . $r->property_id }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-amber-400 text-lg">
                            @for ($i = 1; $i <= 5; $i++){!! $i <= $r->rating ? '&#9733;' : '<span class="text-gray-300">&#9733;</span>' !!}@endfor
                        </div>
                        @php
                            $badge = ['pending' => 'bg-orange-100 text-orange-700', 'approved' => 'bg-green-100 text-green-700', 'rejected' => 'bg-gray-200 text-gray-600', 'spam' => 'bg-red-100 text-red-700'][$r->status] ?? 'bg-gray-100 text-gray-600';
                        @endphp
                        <span class="inline-block mt-1 text-[11px] font-medium px-2 py-0.5 rounded-full {{ $badge }}">{{ ucfirst($r->status) }}</span>
                    </div>
                </div>

                @if ($r->title)<h4 class="font-semibold text-gray-800 mt-3">{{ $r->title }}</h4>@endif
                <p class="text-gray-700 text-sm mt-1">{{ $r->comment }}</p>

                @if ($r->owner_response)
                    <div class="mt-4 ml-3 pl-3 border-l-2 border-rose-200 bg-rose-50/50 rounded-r p-3">
                        <div class="text-xs font-semibold text-rose-700">Your response</div>
                        <p class="text-sm text-gray-700 mt-1">{{ $r->owner_response }}</p>
                    </div>
                @else
                    <form method="POST" action="{{ route('owner.reviews.respond', $r->id) }}" class="mt-4 flex gap-2">
                        @csrf
                        <input type="text" name="owner_response" placeholder="Write a reply..." required
                            class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        <button class="px-4 py-2 rounded-lg bg-rose-600 text-white text-sm font-semibold hover:bg-rose-700">Reply</button>
                    </form>
                @endif
            </div>
        @empty
            <div class="text-center text-gray-500 py-10 bg-white rounded-2xl border border-gray-100">No reviews yet.</div>
        @endforelse
    </div>

    <div class="mt-6">{{ $reviews->links() }}</div>
</div>
@endsection
