@extends('layouts.dashboard')
@section('title', 'Agreement ' . $agreement->agreement_number)
@section('content')

<div class="mb-6">
    <a href="{{ route('admin.agreements.index') }}" class="text-coral-500 font-bold">← Back to all agreements</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 space-y-6">

        <div class="bg-white p-6 rounded-2xl border border-ink-100">
            <div class="flex items-center gap-2 flex-wrap mb-3">
                <span class="text-xs font-mono font-bold bg-cream px-2 py-1 rounded">{{ $agreement->agreement_number }}</span>
                @if($agreement->status === 'active')
                    <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-1 rounded-full font-bold">{{ $agreement->status_label }}</span>
                @else
                    <span class="text-xs bg-amber-100 text-amber-700 px-2 py-1 rounded-full font-bold">{{ $agreement->status_label }}</span>
                @endif
            </div>

            <h1 class="font-display font-black text-2xl">{{ $agreement->tenant?->name }}</h1>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-5 pt-5 border-t border-ink-100 text-sm">
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">Owner</div>
                    <div class="font-bold">{{ $agreement->owner?->name }}</div>
                </div>
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">Property</div>
                    <div class="font-bold">{{ $agreement->property?->name }}</div>
                </div>
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">Duration</div>
                    <div class="font-bold">{{ $agreement->duration_months }} months</div>
                </div>
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">Rent</div>
                    <div class="font-bold text-coral-600">₹{{ number_format($agreement->monthly_rent, 0) }}</div>
                </div>
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">Start</div>
                    <div class="font-bold">{{ $agreement->start_date->format('d M Y') }}</div>
                </div>
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">End</div>
                    <div class="font-bold">{{ $agreement->end_date->format('d M Y') }}</div>
                </div>
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">Deposit</div>
                    <div class="font-bold">₹{{ number_format($agreement->security_deposit, 0) }}</div>
                </div>
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">Lock-in</div>
                    <div class="font-bold">{{ $agreement->lock_in_months }} months</div>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-ink-100">
            <h2 class="font-display font-bold text-lg mb-4">Signatures</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 rounded-xl border-2 {{ $agreement->ownerSigned() ? 'border-emerald-300 bg-emerald-50' : 'border-amber-200 bg-amber-50' }}">
                    <div class="font-bold"><i class="fa-solid fa-house fa-fw"></i> Owner: {{ $agreement->owner?->name }}</div>
                    @if($agreement->ownerSigned())
                        @php $sig = $agreement->signatures->firstWhere('signer_type', 'owner'); @endphp
                        <div class="text-xs text-emerald-700 mt-1">✓ Signed: {{ $sig->signed_at->format('d M Y, h:i A') }}</div>
                    @else
                        <div class="text-xs text-amber-700 mt-1">Pending</div>
                    @endif
                </div>
                <div class="p-4 rounded-xl border-2 {{ $agreement->tenantSigned() ? 'border-emerald-300 bg-emerald-50' : 'border-amber-200 bg-amber-50' }}">
                    <div class="font-bold"><i class="fa-solid fa-user fa-fw"></i> Tenant: {{ $agreement->tenant?->name }}</div>
                    @if($agreement->tenantSigned())
                        @php $sig = $agreement->signatures->firstWhere('signer_type', 'tenant'); @endphp
                        <div class="text-xs text-emerald-700 mt-1">✓ Signed: {{ $sig->signed_at->format('d M Y, h:i A') }}</div>
                    @else
                        <div class="text-xs text-amber-700 mt-1">Pending</div>
                    @endif
                </div>
            </div>
        </div>

    </div>

    <div class="lg:col-span-1">
        <div class="lg:sticky lg:top-24 space-y-4">
            <a href="{{ route('admin.agreements.preview', $agreement) }}" target="_blank" class="block w-full text-center px-4 py-3 bg-blue-500 text-white rounded-xl font-bold"><i class="fa-solid fa-file-lines fa-fw"></i> Print / PDF</a>

            <div class="bg-amber-50 p-5 rounded-2xl border border-amber-200">
                <h3 class="font-display font-bold text-amber-900 mb-3"><i class="fa-solid fa-bolt fa-fw"></i> Admin Override</h3>
                <form method="POST" action="{{ route('admin.agreements.destroy', $agreement) }}" onsubmit="return confirm('Delete?')">
                    @csrf @method('DELETE')
                    <button class="w-full px-4 py-2 bg-rose-500 hover:bg-rose-600 text-white rounded-lg text-sm font-bold"><i class="fa-solid fa-trash-can fa-fw"></i> Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection