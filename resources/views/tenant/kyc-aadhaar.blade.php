@extends('layouts.tenant')
@section('title', 'Aadhaar Verification')
@section('content')

<div class="max-w-lg mx-auto">
    @if(session('error'))<div class="mb-4 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm">{{ session('error') }}</div>@endif

    <div class="bg-white rounded-2xl border border-ink-900/10 p-6 lg:p-8">
        <div class="text-center mb-6">
            <div class="w-16 h-16 rounded-full bg-coral-50 text-3xl flex items-center justify-center mx-auto"><i class="fa-solid fa-id-card fa-fw"></i></div>
            <h1 class="font-display font-black text-2xl mt-4">Verify with Aadhaar</h1>
            <p class="text-ink-900/60 text-sm mt-2">Through DigiLocker, the government's own app. Takes about a minute.</p>
        </div>

        <ul class="space-y-3 text-sm text-ink-900/80">
            <li class="flex gap-3"><i class="fa-solid fa-hand-pointer fa-fw text-coral-500 mt-0.5"></i> You sign in to DigiLocker and tap <b>Allow</b>. That is all.</li>
            <li class="flex gap-3"><i class="fa-solid fa-lock fa-fw text-coral-500 mt-0.5"></i> Pizi never sees or saves your Aadhaar number.</li>
            <li class="flex gap-3"><i class="fa-solid fa-id-badge fa-fw text-coral-500 mt-0.5"></i> Only your name and the last 4 digits are kept.</li>
            <li class="flex gap-3"><i class="fa-solid fa-link fa-fw text-coral-500 mt-0.5"></i> Your Aadhaar must be linked with your DigiLocker account.</li>
        </ul>

        <form method="POST" action="{{ route('tenant.kyc.aadhaar.sendotp') }}" class="mt-6">
            @csrf
            <button class="w-full px-5 py-3.5 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold">Continue to DigiLocker →</button>
        </form>

        <p class="text-center text-xs text-ink-900/50 mt-4">
            Prefer not to? <a href="{{ route('tenant.kyc') }}" class="font-bold text-coral-600 underline">Upload your documents instead</a>.
        </p>
    </div>
</div>

@endsection
