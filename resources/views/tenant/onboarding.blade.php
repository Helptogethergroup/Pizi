@extends('layouts.tenant')
@section('title', 'Verification Steps')
@section('content')



{{-- Signed Agreement Download --}}
@if(session('signed_doc_url'))
<div class="max-w-4xl mx-auto mb-4">
    <div class="bg-emerald-50 border border-emerald-300 rounded-2xl p-4 d-flex align-items-center justify-content-between">
        <div>
            <div class="font-bold text-emerald-800">✅ Agreement Signed Successfully!</div>
            <div class="text-sm text-emerald-700 mt-1">Your signed agreement is ready to download.</div>
        </div>
        <a href="{{ session('signed_doc_url') }}" target="_blank" 
           class="inline-block px-5 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-bold text-sm ms-4">
            ⬇ Download PDF
        </a>
    </div>
</div>
@endif

@php
    $completedCount = collect($steps)->where('completed', true)->count();
    $totalSteps = count($steps);
    $percentage = round(($completedCount / $totalSteps) * 100);
@endphp

<div class="max-w-4xl mx-auto">
    
    {{-- HEADER --}}
    <div class="bg-gradient-to-br from-ink-950 to-ink-900 text-cream rounded-3xl p-6 lg:p-8">
        <h1 class="font-display font-black text-3xl lg:text-4xl">Hi {{ auth()->user()->name }} 👋</h1>
        <p class="text-cream/70 mt-2">Complete your verification to unlock your dashboard</p>
        
        {{-- Progress bar --}}
        <div class="mt-6">
            <div class="flex justify-between text-sm mb-2">
                <span class="font-bold">{{ $completedCount }} of {{ $totalSteps }} steps complete</span>
                <span class="font-bold">{{ $percentage }}%</span>
            </div>
            <div class="h-3 bg-cream/20 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-coral-500 to-emerald-500 rounded-full transition-all duration-500" style="width: {{ $percentage }}%"></div>
            </div>
        </div>

        @if($completedCount >= $totalSteps)
            <div class="mt-6 p-4 bg-emerald-500/20 border border-emerald-400 rounded-xl">
                <div class="font-bold text-lg">🎉 All steps complete!</div>
                <a href="{{ route('tenant.dashboard') }}" class="inline-block mt-3 px-6 py-3 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-bold">
                    Go to Dashboard →
                </a>
            </div>
        @endif
        
        @if($agreement && $agreement->signed_doc_url)
<a href="{{ $agreement->signed_doc_url }}" target="_blank" 
   class="inline-block mt-2 px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-xl font-bold ms-2">
    ⬇ Download Signed Agreement
</a>
@endif
    </div>

    {{-- STEPS --}}
    <div class="mt-8 space-y-4">
        @foreach($steps as $index => $step)
            <div class="bg-white rounded-2xl border-2 
                {{ $step['completed'] ? 'border-emerald-300' : '' }}
                {{ !$step['completed'] && $step['unlocked'] ? 'border-coral-300' : '' }}
                {{ !$step['unlocked'] ? 'border-ink-900/10 opacity-60' : '' }}
                p-5 lg:p-6">
                
                <div class="flex items-start gap-4">
                    {{-- Step number / icon --}}
                    <div class="flex-shrink-0">
                        @if($step['completed'])
                            <div class="w-12 h-12 lg:w-14 lg:h-14 rounded-full bg-emerald-500 text-white flex items-center justify-center text-2xl font-bold">✓</div>
                        @elseif($step['unlocked'])
                            <div class="w-12 h-12 lg:w-14 lg:h-14 rounded-full bg-coral-500 text-white flex items-center justify-center text-2xl">{{ $step['icon'] }}</div>
                        @else
                            <div class="w-12 h-12 lg:w-14 lg:h-14 rounded-full bg-ink-100 text-ink-400 flex items-center justify-center text-2xl">🔒</div>
                        @endif
                    </div>

                    {{-- Step content --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-xs font-bold text-ink-900/40 uppercase">Step {{ $step['id'] }}</span>
                            @if($step['completed'])
                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-xs rounded-full font-bold uppercase">✓ Completed</span>
                            @elseif($step['unlocked'])
                                <span class="px-2 py-0.5 bg-coral-100 text-coral-700 text-xs rounded-full font-bold uppercase">In Progress</span>
                            @else
                                <span class="px-2 py-0.5 bg-ink-100 text-ink-500 text-xs rounded-full font-bold uppercase">🔒 Locked</span>
                            @endif
                        </div>
                        <h3 class="font-bold text-lg lg:text-xl mt-1">{{ $step['title'] }}</h3>
                        <p class="text-sm text-ink-900/60 mt-1">{{ $step['desc'] }}</p>
                        
                        @if(!$step['completed'] && $step['unlocked'] && $step['cta_route'])
                            <a href="{{ route($step['cta_route']) }}" class="inline-block mt-3 px-5 py-2.5 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold text-sm">
                                {{ $step['cta'] }} →
                            </a>
                        @elseif(!$step['completed'] && $step['unlocked'] && !$step['cta_route'])
                            <div class="inline-block mt-3 px-5 py-2.5 bg-amber-100 text-amber-800 rounded-xl font-bold text-sm">
                                ⏳ {{ $step['cta'] }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- HELP --}}
    <div class="mt-8 p-5 bg-cream rounded-2xl border border-ink-900/10 text-center">
        <p class="text-sm text-ink-900/70">Need help? Contact us on WhatsApp</p>
        <a href="https://wa.me/918006680092" target="_blank" class="inline-block mt-2 px-5 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-full text-sm font-bold">
            💬 Chat with us
        </a>
    </div>
</div>

@endsection