@extends('layouts.tenant')
@section('title', 'KYC Verification')
@section('content')

@php
    use App\Models\TenantDocument;
    $docs = TenantDocument::where('tenant_id', $tenant->id)->get()->keyBy('document_type');
    $required = [
        'aadhaar_front' => ['label' => 'Aadhaar — Front', 'icon' => '🆔', 'required' => true],
        'aadhaar_back'  => ['label' => 'Aadhaar — Back',  'icon' => '🆔', 'required' => true],
        'pan'           => ['label' => 'PAN Card',         'icon' => '💳', 'required' => true],
        'photo'         => ['label' => 'Passport Photo',   'icon' => '📷', 'required' => true],
        'address_proof' => ['label' => 'Address Proof',    'icon' => '📍', 'required' => false],
    ];
    $uploadedCount = $docs->count();
    $status = $tenant->kyc_status ?? 'pending';
@endphp

<div class="max-w-4xl mx-auto px-4 py-6">

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="font-display font-black text-3xl text-ink-900">KYC Verification</h1>
        <p class="text-ink-900/60 text-sm mt-1">Upload your identity documents to complete verification.</p>
    </div>

    {{-- Status banner --}}
    <div class="mb-6 rounded-2xl p-5 border
        @if($status === 'approved') bg-emerald-50 border-emerald-200
        @elseif($status === 'submitted') bg-amber-50 border-amber-200
        @elseif($status === 'rejected') bg-rose-50 border-rose-200
        @else bg-cream border-ink-900/10 @endif">

        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div>
                <div class="text-xs font-bold uppercase text-ink-900/50">Current Status</div>
                <div class="font-display font-bold text-xl mt-1">
                    @if($status === 'approved')
                        <span class="text-emerald-700">✅ Approved</span>
                    @elseif($status === 'submitted')
                        <span class="text-amber-700">⏳ Submitted — Awaiting approval</span>
                    @elseif($status === 'rejected')
                        <span class="text-rose-700">❌ Rejected — Please re-upload</span>
                    @else
                        <span class="text-ink-900/70">📄 Pending — Upload documents below</span>
                    @endif
                </div>
            </div>
            <div class="text-right">
                <div class="text-xs font-bold uppercase text-ink-900/50">Uploaded</div>
                <div class="font-display font-bold text-2xl">{{ $uploadedCount }} / {{ count($required) }}</div>
            </div>
        </div>

        @if($status === 'approved')
            <p class="text-sm text-emerald-700 mt-3">Your documents have been verified. The owner will assign your room shortly.</p>
        @elseif($status === 'submitted')
            <p class="text-sm text-amber-700 mt-3">Documents submitted. Our team will review and approve within 24 hours.</p>
        @elseif($uploadedCount >= 3)
            <p class="text-sm text-ink-900/70 mt-3">You have uploaded enough documents. Submit for verification when ready.</p>
        @endif
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm">{{ session('error') }}</div>
    @endif

    {{-- Documents grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($required as $type => $info)
            @php $doc = $docs[$type] ?? null; @endphp

            <div class="bg-white rounded-2xl border border-ink-900/10 p-5">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="font-display font-bold text-lg flex items-center gap-2">
                            <span>{{ $info['icon'] }}</span>
                            {{ $info['label'] }}
                            @if($info['required'])
                                <span class="text-coral-500 text-xs">*</span>
                            @endif
                        </h3>
                        @unless($info['required'])
                            <p class="text-xs text-ink-900/50">Optional</p>
                        @endunless
                    </div>
                    @if($doc)
                        <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold">✓ UPLOADED</span>
                    @else
                        <span class="px-2.5 py-1 rounded-full bg-ink-900/5 text-ink-900/50 text-xs font-bold">NOT UPLOADED</span>
                    @endif
                </div>

                @if($doc)
                    {{-- Preview + delete --}}
                    <div class="rounded-xl border border-ink-900/10 overflow-hidden mb-3 bg-cream">
                        @php
                            $url = asset('storage/' . $doc->file_path);
                            $isImg = preg_match('/\.(jpe?g|png|webp|gif)$/i', $doc->file_path);
                        @endphp
                        @if($isImg)
                            <a href="{{ $url }}" target="_blank" class="block">
                                <img src="{{ $url }}" alt="{{ $info['label'] }}" class="w-full h-48 object-cover hover:opacity-90 transition">
                            </a>
                        @else
                            <a href="{{ $url }}" target="_blank" class="flex items-center justify-center h-48 text-ink-900/70 hover:bg-ink-900/5 transition">
                                <div class="text-center">
                                    <div class="text-4xl mb-2">📄</div>
                                    <div class="text-sm font-bold">View PDF</div>
                                </div>
                            </a>
                        @endif
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ $url }}" target="_blank" class="flex-1 text-center py-2 bg-ink-900/5 hover:bg-ink-900/10 text-ink-900 rounded-lg text-sm font-bold">
                            👁 View
                        </a>
                        @unless($status === 'approved')
                            <form method="POST" action="{{ route('tenant.kyc.delete', $type) }}" class="flex-1"
                                  onsubmit="return confirm('Delete this document?')">
                                @csrf
                                @method('DELETE')
                                <button class="w-full py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-sm font-bold border border-rose-200">
                                    🗑 Delete
                                </button>
                            </form>
                        @endunless
                    </div>

                    @unless($status === 'approved')
                        {{-- Replace --}}
                        <form method="POST" action="{{ route('tenant.kyc.upload') }}" enctype="multipart/form-data" class="mt-3">
                            @csrf
                            <label class="block cursor-pointer">
                                <input type="file" name="{{ $type }}" accept="image/*,.pdf" class="hidden" onchange="this.form.submit()">
                                <div class="text-center py-2 border border-dashed border-ink-900/20 hover:border-coral-500 rounded-lg text-xs text-ink-900/60 hover:text-coral-600">
                                    🔄 Replace document
                                </div>
                            </label>
                        </form>
                    @endunless

                @else
                    {{-- Upload --}}
                    @unless($status === 'approved')
                        <form method="POST" action="{{ route('tenant.kyc.upload') }}" enctype="multipart/form-data">
                            @csrf
                            <label class="block cursor-pointer">
                                <input type="file" name="{{ $type }}" accept="image/*,.pdf" class="hidden" required onchange="this.form.submit()">
                                <div class="text-center py-8 border-2 border-dashed border-ink-900/15 hover:border-coral-500 rounded-xl text-ink-900/60 hover:text-coral-600 transition">
                                    <div class="text-3xl mb-2">📤</div>
                                    <div class="font-bold text-sm">Click to upload</div>
                                    <div class="text-xs mt-1">Image or PDF · max 2MB</div>
                                </div>
                            </label>
                        </form>
                    @endunless
                @endif
            </div>
        @endforeach
    </div>

    {{-- Submit for review --}}
    @if($status !== 'approved' && $status !== 'submitted' && $uploadedCount >= 3)
        <div class="mt-6 bg-coral-50 border border-coral-200 rounded-2xl p-5 text-center">
            <h3 class="font-display font-bold text-lg text-ink-900">Ready to submit?</h3>
            <p class="text-sm text-ink-900/70 mt-1">Your documents will be sent for verification.</p>
            <form method="POST" action="{{ route('tenant.kyc.submit') }}" class="mt-4">
                @csrf
                <button class="px-8 py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold">
                    Submit for Verification →
                </button>
            </form>
        </div>
    @endif

    {{-- Help note --}}
    <div class="mt-6 text-xs text-ink-900/50 bg-ink-900/5 rounded-xl p-4">
        <strong class="text-ink-900/70">Note:</strong>
        Documents are private and only visible to you and the PG owner.
        Acceptable formats: JPG, PNG, PDF (max 2MB each).
        Aadhaar number is automatically masked on display.
    </div>

</div>

@endsection