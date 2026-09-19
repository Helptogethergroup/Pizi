@extends('layouts.tenant')
@section('title', 'My Agreement')
@section('content')

<h1 class="font-display font-black text-3xl text-ink-950">My Agreement</h1>
<p class="text-ink-900/60 mt-1">Rent agreement view aur download</p>

<div class="mt-6 space-y-4">
    @if(isset($agreements) && $agreements->count())
        @foreach($agreements as $a)
            <div class="bg-white rounded-2xl border border-ink-900/10 p-6">
                <div class="flex justify-between items-start gap-3 flex-wrap">
                    <div>
                        <h3 class="font-bold text-lg">Rent Agreement #{{ $a->id }}</h3>
                        <p class="text-sm text-ink-900/60 mt-1">
                            Start: {{ \Carbon\Carbon::parse($a->start_date ?? $a->created_at)->format('d M Y') }}
                            @if(!empty($a->end_date))
                                — End: {{ \Carbon\Carbon::parse($a->end_date)->format('d M Y') }}
                            @endif
                        </p>
                        <span class="inline-block mt-2 text-xs px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full uppercase font-bold">{{ $a->status ?? 'active' }}</span>
                    </div>
                                      <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <a href="{{ route('tenant.agreement.preview', $a->id) }}"
                           target="_blank"
                           style="display:inline-flex; align-items:center; gap:6px; padding:8px 16px; background:#0f2748; color:white; border-radius:10px; font-weight:700; font-size:13px; text-decoration:none;">
                            <i class="fa-solid fa-eye fa-fw"></i> View
                        </a>
                        <a href="{{ route('tenant.agreement.download', $a->id) }}"
                           style="display:inline-flex; align-items:center; gap:6px; padding:8px 16px; background:#ff6b5b; color:white; border-radius:10px; font-weight:700; font-size:13px; text-decoration:none;">
                            <i class="fa-solid fa-arrow-down fa-fw"></i> Download PDF
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    @else
        <div class="bg-white rounded-2xl border border-ink-900/10 p-12 text-center">
            <div class="text-6xl mb-3"><i class="fa-solid fa-file-lines fa-fw"></i></div>
            <h3 class="font-bold text-lg">No Agreement yet</h3>
            <p class="text-ink-900/60 text-sm mt-1">
                @if(!$tenant->property_id)
                    Pehle PG select karo — phir owner aapka rent agreement create karega.
                @else
                    Owner aapka rent agreement abhi tak create nahi kiya hai. Unse contact karo.
                @endif
            </p>
            @if(!$tenant->property_id)
                <a href="{{ route('search') }}" class="inline-block mt-4 px-6 py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold"><i class="fa-solid fa-magnifying-glass fa-fw"></i> Browse PGs</a>
            @endif
        </div>
    @endif
</div>

@endsection