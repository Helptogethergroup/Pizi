@extends('layouts.tenant')
@section('title', 'My Room')
@section('content')

<h1 class="font-display font-black text-3xl text-ink-950">My Room</h1>
<p class="text-ink-900/60 mt-1">Room details aur roommates info</p>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">

    {{-- PG INFO --}}
    <div class="lg:col-span-2 bg-white rounded-2xl border border-ink-900/10 p-6">
        <h2 class="font-display font-bold text-xl mb-4">🏠 My PG</h2>
        
        @if($tenant->property)
            <div class="flex gap-4 flex-wrap">
                @if($tenant->property->cover_image)
                    <img src="{{ asset('storage/' . $tenant->property->cover_image) }}" class="w-40 h-40 rounded-xl object-cover">
                @endif
                <div class="flex-1 min-w-[200px]">
                    <h3 class="font-bold text-xl">{{ $tenant->property->name }}</h3>
                    <p class="text-sm text-ink-900/60 mt-1">{{ $tenant->property->address_line }}</p>
                    <p class="text-sm text-ink-900/60">{{ $tenant->property->locality?->name }}, {{ $tenant->property->city?->name }} - {{ $tenant->property->pincode }}</p>
                    
                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ route('property.show', $tenant->property->slug) }}" target="_blank" class="px-4 py-2 bg-blue-500 text-white rounded-lg text-sm font-bold">View Property</a>
                        @if($tenant->property->latitude && $tenant->property->longitude)
                            <a href="https://www.google.com/maps?q={{ $tenant->property->latitude }},{{ $tenant->property->longitude }}" target="_blank" class="px-4 py-2 bg-emerald-500 text-white rounded-lg text-sm font-bold">🗺️ Get Directions</a>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- ROOM DETAILS --}}
    <div class="bg-white rounded-2xl border border-ink-900/10 p-6">
        <h2 class="font-display font-bold text-xl mb-4">🛏️ Room Details</h2>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between border-b border-ink-900/5 pb-2">
                <span class="text-ink-900/60">Room Number</span>
                <strong>{{ $tenant->room_number ?? 'Not assigned' }}</strong>
            </div>
            <div class="flex justify-between border-b border-ink-900/5 pb-2">
                <span class="text-ink-900/60">Bed Number</span>
                <strong>{{ $tenant->bed_number ?? '-' }}</strong>
            </div>
            <div class="flex justify-between border-b border-ink-900/5 pb-2">
                <span class="text-ink-900/60">Monthly Rent</span>
                <strong>₹{{ number_format($tenant->monthly_rent) }}</strong>
            </div>
            <div class="flex justify-between border-b border-ink-900/5 pb-2">
                <span class="text-ink-900/60">Security Deposit</span>
                <strong>₹{{ number_format($tenant->security_deposit) }}</strong>
            </div>
            <div class="flex justify-between border-b border-ink-900/5 pb-2">
                <span class="text-ink-900/60">Move-in Date</span>
                <strong>{{ $tenant->move_in_date?->format('d M Y') ?? '-' }}</strong>
            </div>
            <div class="flex justify-between">
                <span class="text-ink-900/60">Status</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold uppercase
                    {{ $tenant->status === 'active' ? 'bg-emerald-100 text-emerald-700' : '' }}
                    {{ $tenant->status === 'notice_period' ? 'bg-amber-100 text-amber-700' : '' }}
                    {{ $tenant->status === 'left' ? 'bg-rose-100 text-rose-700' : '' }}
                ">{{ str_replace('_', ' ', $tenant->status) }}</span>
            </div>
        </div>
    </div>
</div>

{{-- ROOMMATES --}}
<div class="mt-6 bg-white rounded-2xl border border-ink-900/10 p-6">
    <h2 class="font-display font-bold text-xl mb-4">👥 My Roommates ({{ $roommates->count() }})</h2>
    
    @if($roommates->count())
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($roommates as $mate)
                <div class="border border-ink-900/10 rounded-xl p-4 flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-coral-500 text-white flex items-center justify-center font-bold text-lg">
                        {{ strtoupper(substr($mate->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-bold truncate">{{ $mate->name }}</div>
                        <div class="text-xs text-ink-900/60">Bed: {{ $mate->bed_number ?? '-' }}</div>
                        @if($mate->occupation)
                            <div class="text-xs text-ink-900/60">{{ $mate->occupation }}</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-ink-900/50 text-center py-8">No roommates - you have the room all to yourself! 🎉</p>
    @endif
</div>

@endsection