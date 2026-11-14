{{-- One page with everything about a tenant, laid out like the Add Tenant form. Used by the owner and by the tenant. --}}
@php
    $show = fn ($x) => filled($x) ? e($x) : '<span class="text-ink-900/30">-</span>';
    $date = function ($x) {
        if (empty($x)) return null;
        try { return \Carbon\Carbon::parse($x)->format('d M Y'); } catch (\Throwable $e) { return (string) $x; }
    };
    $money = fn ($x) => $x !== null && $x !== '' ? '₹' . number_format((float) $x) : null;

    $kyc = $tenant->kyc_status ?: 'pending';
    $police = $tenant->police_verification_status ?: 'pending';
    $aadhaarOk = !empty($tenant->aadhaar_verified_at);
    $agreementOk = !empty($tenant->agreement_signed_at);
    $policeDoc = $tenant->documents->firstWhere('document_type', 'police_verification');

    $pill = function (string $text, string $tone) {
        $tones = ['good' => 'bg-emerald-100 text-emerald-700', 'warn' => 'bg-amber-100 text-amber-700', 'bad' => 'bg-rose-100 text-rose-700', 'mute' => 'bg-ink-100 text-ink-600'];
        return '<span class="px-3 py-1 rounded-full text-xs font-bold ' . ($tones[$tone] ?? $tones['mute']) . '">' . e($text) . '</span>';
    };
    $docLabels = [
        'aadhaar_front' => 'Aadhaar (front)', 'aadhaar_back' => 'Aadhaar (back)', 'pan' => 'PAN card', 'photo' => 'Photo',
        'id_card' => 'College / office ID', 'employment_id' => 'Employment ID', 'student_id' => 'Student ID',
        'address_proof' => 'Address proof', 'police_verification' => 'Police verification', 'other' => 'Other',
    ];
@endphp

<div class="max-w-4xl space-y-5" id="tenantOverview">

    {{-- who --}}
    <div class="bg-white p-6 rounded-2xl border border-ink-100">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="font-display font-black text-2xl">{{ $tenant->name }}</h2>
                <div class="text-ink-900/60 mt-1">{{ $tenant->phone }}@if($tenant->email && !str_ends_with($tenant->email, '@temp.pizi.in')) · {{ $tenant->email }}@endif</div>
            </div>
            <div class="flex flex-wrap gap-2">
                {!! $pill($kyc === 'approved' ? 'KYC approved' : 'KYC ' . $kyc, $kyc === 'approved' ? 'good' : ($kyc === 'rejected' ? 'bad' : 'warn')) !!}
                {!! $pill($aadhaarOk ? 'Aadhaar verified' : 'Aadhaar not verified', $aadhaarOk ? 'good' : 'mute') !!}
                {!! $pill($police === 'done' ? 'Police verification done' : 'Police verification pending', $police === 'done' ? 'good' : 'warn') !!}
                {!! $pill($agreementOk ? 'Agreement signed' : 'Agreement not signed', $agreementOk ? 'good' : 'mute') !!}
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div class="bg-white p-6 rounded-2xl border border-ink-100">
            <h3 class="font-display font-bold text-lg mb-3"><i class="fa-solid fa-user fa-fw"></i> Personal details</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-ink-900/60">Full name</dt><dd class="font-semibold text-right">{!! $show($tenant->name) !!}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-900/60">Phone</dt><dd class="font-semibold text-right">{!! $show($tenant->phone) !!}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-900/60">Email</dt><dd class="font-semibold text-right">{!! $show($tenant->email && !str_ends_with($tenant->email, '@temp.pizi.in') ? $tenant->email : null) !!}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-900/60">Date of birth</dt><dd class="font-semibold text-right">{!! $show($date($tenant->dob)) !!}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-900/60">Gender</dt><dd class="font-semibold text-right">{!! $show($tenant->gender ? ucfirst($tenant->gender) : null) !!}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-900/60">Occupation</dt><dd class="font-semibold text-right">{!! $show($tenant->occupation) !!}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-900/60">Company / college</dt><dd class="font-semibold text-right">{!! $show($tenant->company_college) !!}</dd></div>
            </dl>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-ink-100">
            <h3 class="font-display font-bold text-lg mb-3"><i class="fa-solid fa-location-dot fa-fw"></i> Address</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-ink-900/60">Address</dt><dd class="font-semibold text-right">{!! $show($tenant->address_line) !!}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-900/60">City</dt><dd class="font-semibold text-right">{!! $show($tenant->city) !!}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-900/60">State</dt><dd class="font-semibold text-right">{!! $show($tenant->state) !!}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-900/60">Pincode</dt><dd class="font-semibold text-right">{!! $show($tenant->pincode) !!}</dd></div>
            </dl>
            <h3 class="font-display font-bold text-lg mt-6 mb-3"><i class="fa-solid fa-phone-volume fa-fw"></i> Emergency contact</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-ink-900/60">Name</dt><dd class="font-semibold text-right">{!! $show($tenant->emergency_name) !!}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-900/60">Phone</dt><dd class="font-semibold text-right">{!! $show($tenant->emergency_phone) !!}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-900/60">Relation</dt><dd class="font-semibold text-right">{!! $show($tenant->emergency_relation) !!}</dd></div>
            </dl>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-ink-100">
            <h3 class="font-display font-bold text-lg mb-3"><i class="fa-solid fa-bed fa-fw"></i> Stay</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-ink-900/60">PG</dt><dd class="font-semibold text-right">{!! $show($tenant->property?->name) !!}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-900/60">Room / bed</dt><dd class="font-semibold text-right">{!! $show(trim(($tenant->room_number ?? '') . ($tenant->bed_number ? ' / ' . $tenant->bed_number : ''))) !!}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-900/60">Monthly rent</dt><dd class="font-semibold text-right">{!! $show($money($tenant->monthly_rent)) !!}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-900/60">Security deposit</dt><dd class="font-semibold text-right">{!! $show($money($tenant->security_deposit)) !!}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-900/60">Move-in date</dt><dd class="font-semibold text-right">{!! $show($date($tenant->move_in_date)) !!}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-900/60">Status</dt><dd class="font-semibold text-right">{!! $show(ucfirst(str_replace('_', ' ', (string) $tenant->status))) !!}</dd></div>
                @if(!empty($forOwner) && filled($tenant->notes))
                    <div class="pt-2 text-ink-900/70 whitespace-pre-line">{{ $tenant->notes }}</div>
                @endif
            </dl>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-ink-100">
            <h3 class="font-display font-bold text-lg mb-3"><i class="fa-solid fa-shield-halved fa-fw"></i> Verification</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-ink-900/60">KYC</dt><dd>{!! $pill(ucfirst($kyc), $kyc === 'approved' ? 'good' : ($kyc === 'rejected' ? 'bad' : 'warn')) !!}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-900/60">Aadhaar</dt><dd class="font-semibold text-right">@if($aadhaarOk){{ $tenant->aadhaar_number_masked }} {!! $pill('Verified', 'good') !!}@else {!! $pill('Not verified', 'mute') !!}@endif</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-900/60">Police verification</dt><dd>{!! $pill($police === 'done' ? 'Done' : 'Pending', $police === 'done' ? 'good' : 'warn') !!}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-ink-900/60">Rental agreement</dt><dd>@if($agreementOk){!! $pill('Signed ' . $date($tenant->agreement_signed_at), 'good') !!}@else{!! $pill('Not signed', 'mute') !!}@endif</dd></div>
            </dl>
            @if($police !== 'done')
                <p class="mt-3 text-xs bg-amber-50 border border-amber-200 text-amber-800 rounded-xl px-3 py-2">Police verification is pending until its document is uploaded.</p>
            @endif
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-ink-100">
        <h3 class="font-display font-bold text-lg mb-3"><i class="fa-solid fa-file-lines fa-fw"></i> Documents</h3>
        @forelse($tenant->documents as $doc)
            <div class="flex items-center justify-between gap-3 py-2 border-b border-ink-100 last:border-0 text-sm">
                <div><span class="font-semibold">{{ $docLabels[$doc->document_type] ?? 'Document' }}</span>
                    <span class="text-ink-900/50"> · {{ $date($doc->created_at) }}</span></div>
                @if(!empty($forOwner))
                    <a href="{{ $doc->url }}" target="_blank" rel="noopener" class="font-bold text-coral-600 underline">View</a>
                @else
                    <span class="text-emerald-700 font-bold">Uploaded</span>
                @endif
            </div>
        @empty
            <p class="text-sm text-ink-900/60">No documents uploaded.@if($aadhaarOk) Aadhaar is verified, so none are needed.@endif</p>
        @endforelse
    </div>
</div>
