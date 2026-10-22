@extends('layouts.dashboard')
@section('title', 'Lead reports — Admin')
@section('content')
    <div class="mb-6">
        <h1 class="font-display font-black text-3xl">Lead reports</h1>
        <p class="text-ink-900/60 mt-1">Leads that owners say were not real. Call the number if you need to, then approve (credits go back) or reject with a note the owner will see.</p>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-xl">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-rose-50 border border-rose-200 text-rose-700 text-sm px-4 py-3 rounded-xl">{{ session('error') }}</div>
    @endif

    <div class="flex gap-2 mb-4 text-sm">
        @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $key => $label)
            <a href="{{ route('admin.lead-reports.index', ['status' => $key]) }}"
               class="px-4 py-2 rounded-full font-semibold {{ $filter === $key ? 'bg-ink-950 text-cream' : 'bg-white border border-ink-900/10' }}">
                {{ $label }}@if(isset($counts[$key])) ({{ $counts[$key] }})@endif
            </a>
        @endforeach
    </div>

    <div class="space-y-3">
        @forelse($reports as $r)
            <div class="bg-white rounded-2xl border border-ink-900/10 p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="font-bold">{{ $r->lead?->name ?? 'Deleted lead' }}
                            <span class="text-ink-900/50 font-normal">· lead #{{ $r->lead_id }}</span>
                        </div>
                        <div class="text-sm mt-1">
                            <i class="fa-solid fa-phone fa-fw"></i>
                            <a class="font-mono font-bold text-blue-700" href="tel:{{ $r->lead?->phone }}">{{ $r->lead?->phone }}</a>
                            <span class="text-ink-900/50">· source: {{ $r->lead?->source ?? '—' }}</span>
                        </div>
                        <div class="text-sm text-ink-900/70 mt-1">
                            Reported by <b>{{ $r->owner?->name }}</b> ({{ $r->owner?->phone }})
                            @if($r->reported_by != $r->owner_id) via manager {{ $r->reporter?->name }} @endif
                            · {{ $r->created_at->diffForHumans() }} · <b>{{ $r->credits_spent }}</b> credits spent
                        </div>
                    </div>
                    <span class="text-xs px-3 py-1 rounded-full font-bold
                        {{ $r->status === 'pending' ? 'bg-amber-100 text-amber-800' : ($r->status === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800') }}">
                        {{ ucfirst($r->status) }}
                    </span>
                </div>

                <div class="mt-3 text-sm bg-cream rounded-xl p-3">
                    <div class="text-xs uppercase text-ink-900/50">What the owner says</div>
                    <div class="font-semibold mt-0.5">{{ $r->reasonLabel() }}</div>
                    @if($r->note)<div class="italic mt-1">“{{ $r->note }}”</div>@endif
                </div>

                @if($r->status === 'pending')
                    <div class="mt-3 grid md:grid-cols-2 gap-3">
                        <form method="POST" action="{{ route('admin.lead-reports.approve', $r) }}"
                              onsubmit="return confirm('Return {{ $r->credits_spent }} credits to the owner?')" class="flex gap-2">
                            @csrf
                            <input type="text" name="admin_note" placeholder="Note (optional)" class="flex-1 px-3 py-2 rounded-lg border border-ink-900/15 text-sm">
                            <button class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold">Approve &amp; refund</button>
                        </form>
                        <form method="POST" action="{{ route('admin.lead-reports.reject', $r) }}" class="flex gap-2">
                            @csrf
                            <input type="text" name="admin_note" required minlength="3" placeholder="Why not? (the owner sees this)" class="flex-1 px-3 py-2 rounded-lg border border-ink-900/15 text-sm">
                            <button class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-sm font-bold">Reject</button>
                        </form>
                    </div>
                @else
                    <div class="mt-3 text-sm text-ink-900/70">
                        Reviewed {{ $r->reviewed_at?->diffForHumans() }}
                        @if($r->status === 'approved') · {{ $r->credits_refunded }} credits returned @endif
                        @if($r->admin_note) · Note: {{ $r->admin_note }} @endif
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-ink-900/10 p-10 text-center text-ink-900/50">No reports here.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $reports->links() }}</div>
@endsection
