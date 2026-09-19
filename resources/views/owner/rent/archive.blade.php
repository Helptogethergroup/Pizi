@extends('layouts.dashboard')
@section('title', 'Bill Archive')
@section('content')

<div class="mb-6">
    <a href="{{ route('owner.rent.index') }}" class="text-coral-500 font-bold">← Back to Rent Collection</a>
    <h1 class="font-display font-black text-3xl mt-2"><i class="fa-solid fa-box-archive fa-fw"></i> Bill Archive</h1>
    <p class="text-ink-900/60 mt-1">30+ din purane aur deleted bills — sirf owner ke liye visible.</p>
</div>

{{-- Deleted Bills --}}
<div class="mb-8">
    <h2 class="font-display font-bold text-xl mb-3 text-rose-700"><i class="fa-solid fa-trash-can fa-fw"></i> Deleted Bills ({{ $deletedBills->total() }})</h2>
    @if($deletedBills->isEmpty())
        <div class="bg-white p-6 rounded-2xl border border-ink-100 text-center text-ink-700">Koi deleted bill nahi hai.</div>
    @else
        <div class="space-y-3">
            @foreach($deletedBills as $bill)
                <div class="bg-rose-50 p-4 rounded-2xl border border-rose-200">
                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <div>
                            <div class="font-bold">{{ $bill->tenant?->name ?? 'Unknown tenant' }} · {{ $bill->month_label }}</div>
                            <div class="text-xs text-ink-700 mt-1">
                                #{{ $bill->bill_number }} · ₹{{ number_format($bill->total_amount, 0) }}
                                · Deleted on {{ $bill->deleted_at->format('d M Y, h:i A') }}
                            </div>
                        </div>
                        <form method="POST" action="{{ route('owner.rent.restore', $bill->id) }}">
                            @csrf
                            <button class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg text-xs font-bold">↺ Restore</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $deletedBills->links() }}</div>
    @endif
</div>

{{-- Old Bills (30+ days) --}}
<div>
    <h2 class="font-display font-bold text-xl mb-3"><i class="fa-solid fa-calendar-days fa-fw"></i> Bills Older Than 30 Days ({{ $oldBills->total() }})</h2>
    @if($oldBills->isEmpty())
        <div class="bg-white p-6 rounded-2xl border border-ink-100 text-center text-ink-700">Koi purana bill nahi hai.</div>
    @else
        <div class="space-y-3">
            @foreach($oldBills as $bill)
                <div class="bg-white p-4 rounded-2xl border border-ink-100">
                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <div>
                            <div class="font-bold">{{ $bill->tenant?->name }} · {{ $bill->month_label }}</div>
                            <div class="text-xs text-ink-700 mt-1">
                                #{{ $bill->bill_number }} · ₹{{ number_format($bill->total_amount, 0) }}
                                · Created {{ $bill->created_at->format('d M Y') }}
                                · <span class="capitalize font-bold">{{ $bill->status }}</span>
                            </div>
                        </div>
                        <a href="{{ route('owner.rent.show', $bill) }}" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-xs font-bold">View</a>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $oldBills->links() }}</div>
    @endif
</div>

@endsection