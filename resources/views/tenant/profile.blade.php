@extends('layouts.tenant')
@section('title', 'My Profile')
@section('content')

<div class="max-w-4xl">
    <h1 class="font-display font-black text-3xl text-ink-950">My Profile</h1>
    <p class="text-ink-900/60 mt-1">Please fill in your personal information</p>



    {{-- PERSONAL INFO --}}
    <div class="mt-6 bg-white rounded-2xl border border-ink-900/10 p-6">
        <h2 class="font-display font-bold text-xl mb-5"><i class="fa-solid fa-pen-to-square fa-fw"></i> Personal Information</h2>
        
        <form method="POST" action="{{ route('tenant.profile.update') }}" class="space-y-4">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-bold uppercase text-ink-900/60">Full Name *</label>
                    <input name="name" required value="{{ old('name', $user->name) }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none">
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-ink-900/60">Phone *</label>
                    <input name="phone" required value="{{ old('phone', $user->phone) }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none">
                </div>
           <div>
    <label class="text-xs font-bold uppercase text-ink-900/60">Email</label>
    <input type="email" name="email" value="{{ old('email', $displayEmail) }}" placeholder="Add your email" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none">
</div>
                <div>
                    <label class="text-xs font-bold uppercase text-ink-900/60">Occupation</label>
                    <input name="occupation" value="{{ old('occupation', $tenant->occupation ?? '') }}" placeholder="Student / Software Engineer / etc." class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-bold uppercase text-ink-900/60">Company / College</label>
                    <input name="company_college" value="{{ old('company_college', $tenant->company_college ?? '') }}" placeholder="e.g. Infosys, IIT Delhi" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none">
                </div>
            </div>

            <div class="border-t border-ink-900/10 pt-4 mt-4">
                <h3 class="font-bold mb-3"><i class="fa-solid fa-triangle-exclamation fa-fw"></i> Emergency Contact</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-xs font-bold uppercase text-ink-900/60">Name</label>
                        <input name="emergency_name" value="{{ old('emergency_name', $tenant->emergency_name ?? '') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none">
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase text-ink-900/60">Phone</label>
                        <input name="emergency_phone" value="{{ old('emergency_phone', $tenant->emergency_phone ?? '') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none">
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase text-ink-900/60">Relation</label>
                        <input name="emergency_relation" value="{{ old('emergency_relation', $tenant->emergency_relation ?? '') }}" placeholder="Father / Mother / Brother" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none">
                    </div>
                </div>
            </div>

            <button type="submit" class="px-8 py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold">
                <i class="fa-solid fa-floppy-disk fa-fw"></i> Save Changes
            </button>
        </form>
    </div>

    {{-- PASSWORD LOGIN SETUP --}}
    <div class="mt-6 bg-white rounded-2xl border border-ink-900/10 p-6">
        @php
            $hasRealEmail = $user->email && !str_ends_with($user->email, '@temp.pizi.in');
        @endphp

        @if($hasRealEmail && $user->password)
            <div class="flex items-start gap-3">
                <div class="text-2xl"><i class="fa-solid fa-circle-check fa-fw"></i></div>
                <div>
                    <h2 class="font-display font-bold text-xl">Password Login is Set Up</h2>
                    <p class="text-sm text-ink-900/60 mt-1">You can log in anytime with your email ({{ $user->email }}) and password — no need to wait for OTP every time.</p>
                    <button type="button" onclick="document.getElementById('passwordSetupForm').classList.toggle('hidden')" class="text-coral-600 text-sm font-bold mt-2">Change password →</button>
                </div>
            </div>
        @else
            <h2 class="font-display font-bold text-xl mb-1"><i class="fa-solid fa-lock fa-fw"></i> Set Up Password Login</h2>
            <p class="text-sm text-ink-900/60 mb-5">Tired of waiting for an OTP every time? Set an email + password once, and log in instantly next time — OTP will still work too if you prefer it.</p>
        @endif

        <form id="passwordSetupForm" method="POST" action="{{ route('tenant.profile.setup-password') }}" class="space-y-4 {{ ($hasRealEmail && $user->password) ? 'hidden mt-4' : '' }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-bold uppercase text-ink-900/60">Email *</label>
                    <input type="email" name="email" required value="{{ old('email', $hasRealEmail ? $user->email : $displayEmail) }}" placeholder="you@example.com" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none">
                </div>
                <div></div>
                <div>
                    <label class="text-xs font-bold uppercase text-ink-900/60">New Password *</label>
                    <input type="password" name="password" required minlength="6" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none">
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-ink-900/60">Confirm Password *</label>
                    <input type="password" name="password_confirmation" required minlength="6" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none">
                </div>
            </div>
            <button type="submit" class="px-8 py-3 bg-ink-950 hover:bg-ink-900 text-white rounded-xl font-bold">
                <i class="fa-solid fa-lock fa-fw"></i> {{ ($hasRealEmail && $user->password) ? 'Update Password' : 'Set Up Password Login' }}
            </button>
        </form>
    </div>

    <!--{{-- KYC DOCUMENTS UPLOAD --}}-->
    <!--<div id="kyc-section" class="mt-6 bg-white rounded-2xl border border-ink-900/10 p-6">-->
    <!--    <h2 class="font-display font-bold text-xl mb-2"><i class="fa-solid fa-file-lines fa-fw"></i> KYC Documents</h2>-->
    <!--    <p class="text-sm text-ink-900/60 mb-5">Upload kare verify karne ke liye — Owner approve karega.</p>-->

    <!--    @if($tenant && $tenant->documents->count())-->
    <!--        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-6">-->
    <!--            @foreach($tenant->documents as $doc)-->
    <!--                <div class="border border-ink-900/10 rounded-xl p-3">-->
    <!--                    <div class="text-xs font-bold uppercase text-ink-900/60">{{ str_replace('_', ' ', $doc->document_type) }}</div>-->
    <!--                    <div class="mt-2 aspect-video bg-cream rounded-lg flex items-center justify-center text-2xl"><i class="fa-solid fa-file-lines fa-fw"></i></div>-->
    <!--                    <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="block text-center text-xs mt-2 text-coral-600 font-bold">View</a>-->
    <!--                </div>-->
    <!--            @endforeach-->
    <!--        </div>-->
    <!--    @endif-->

    <!--    <form method="POST" action="{{ route('tenant.kyc.upload') }}" enctype="multipart/form-data" class="space-y-3">-->
    <!--        @csrf-->
    <!--        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">-->
    <!--            <div>-->
    <!--                <label class="text-xs font-bold uppercase text-ink-900/60">Aadhaar Card (Front) *</label>-->
    <!--                <input type="file" name="aadhaar_front" accept="image/*,.pdf" class="w-full mt-1 text-sm">-->
    <!--            </div>-->
    <!--            <div>-->
    <!--                <label class="text-xs font-bold uppercase text-ink-900/60">Aadhaar Card (Back)</label>-->
    <!--                <input type="file" name="aadhaar_back" accept="image/*,.pdf" class="w-full mt-1 text-sm">-->
    <!--            </div>-->
    <!--            <div>-->
    <!--                <label class="text-xs font-bold uppercase text-ink-900/60">PAN Card *</label>-->
    <!--                <input type="file" name="pan" accept="image/*,.pdf" class="w-full mt-1 text-sm">-->
    <!--            </div>-->
    <!--            <div>-->
    <!--                <label class="text-xs font-bold uppercase text-ink-900/60">Profile Photo *</label>-->
    <!--                <input type="file" name="photo" accept="image/*" class="w-full mt-1 text-sm">-->
    <!--            </div>-->
    <!--            <div>-->
    <!--                <label class="text-xs font-bold uppercase text-ink-900/60">College ID / Office ID</label>-->
    <!--                <input type="file" name="id_card" accept="image/*,.pdf" class="w-full mt-1 text-sm">-->
    <!--            </div>-->
    <!--            <div>-->
    <!--                <label class="text-xs font-bold uppercase text-ink-900/60">Address Proof</label>-->
    <!--                <input type="file" name="address_proof" accept="image/*,.pdf" class="w-full mt-1 text-sm">-->
    <!--            </div>-->
    <!--        </div>-->

    <!--        <p class="text-xs text-ink-900/50">Max 2MB per file. Accepted: JPG, PNG, PDF.</p>-->

    <!--        <button type="submit" class="px-8 py-3 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-bold">-->
    <!--            📤 Upload Documents-->
    <!--        </button>-->
    <!--    </form>-->
    <!--</div>-->
</div>

@endsection