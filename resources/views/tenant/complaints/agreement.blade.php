@extends('layouts.tenant')
@section('title', 'My Agreement')
@section('content')

<h1 class="font-display font-black text-3xl text-ink-950">My Agreement</h1>
<p class="text-ink-900/60 mt-1">Digital agreement view aur download</p>

<div class="mt-6 space-y-4">
    @forelse($agreements as $a)
        <div class="bg-white rounded-2xl border border-ink-900/10 p-6">
            <div class="flex justify-between items-start gap-3 flex-wrap">
                <div>
                    <h3 class="font-bold text-lg">Rent Agreement #{{ $a->id }}</h3>
                    <p class="text-sm text-ink-900/60 mt-1">
                        Start: {{ \Carbon\Carbon::parse($a->start_date ?? $a->created_at)->format('d M Y') }}
                        @if($a->end_date) — End: {{ \Carbon\Carbon::parse($a->end_date)->format('d M Y') }}@endif
                    </p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <span class="text-xs px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full uppercase font-bold">{{ $a->status }}</span>
                    </div>
                </div>
                <div class="flex gap-2 flex-wrap">
                    @if($a->pdf_path ?? false)
                        <a href="{{ asset('storage/' . $a->pdf_path) }}" target="_blank" class="px-4 py-2 bg-blue-500 text-white rounded-lg text-sm font-bold"><i class="fa-solid fa-inbox fa-fw"></i> Download PDF</a>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-2xl border border-ink-900/10 p-12 text-center">
            <div class="text-6xl mb-3"><i class="fa-solid fa-file-lines fa-fw"></i></div>
            <h3 class="font-bold text-lg">No Agreement yet</h3>
            <p class="text-ink-900/60 text-sm mt-1">Owner aapka rent agreement abhi tak create nahi kiya hai.</p>
        </div>
    @endforelse
</div>

@endsection