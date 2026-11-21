<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Tenant details - {{ $tenant->name }}</title>
<style>
    @page { margin: 22mm 16mm 18mm 16mm; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #1b2433; line-height: 1.45; }
    .head { border-bottom: 2px solid #0f3d5e; padding-bottom: 10px; margin-bottom: 14px; }
    .brand { font-size: 20px; font-weight: bold; color: #0f3d5e; }
    .copy { float: right; font-size: 10px; font-weight: bold; color: #ffffff; background: {{ $forOwner ? '#0f3d5e' : '#2f7d5b' }}; padding: 4px 10px; border-radius: 10px; }
    h1 { font-size: 17px; margin: 6px 0 2px 0; }
    .muted { color: #667085; }
    h2 { font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.6px; color: #0f3d5e; margin: 16px 0 5px 0; border-bottom: 1px solid #d9dee7; padding-bottom: 3px; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 4px 6px; vertical-align: top; border-bottom: 1px solid #eef0f4; }
    td.k { width: 36%; color: #667085; }
    td.v { font-weight: bold; }
    .pill { display: inline-block; padding: 2px 8px; border-radius: 8px; font-size: 9.5px; font-weight: bold; }
    .good { background: #dcf3e8; color: #1c6b46; }
    .warn { background: #fdecc8; color: #8a5a00; }
    .bad { background: #fbd9d9; color: #9b1c1c; }
    .mute { background: #e9ecf1; color: #4b5563; }
    .note { background: #f6f8fb; border: 1px solid #e1e6ee; border-radius: 6px; padding: 8px 10px; margin-top: 6px; white-space: pre-line; }
    .foot { margin-top: 22px; border-top: 1px solid #d9dee7; padding-top: 8px; font-size: 9.5px; color: #667085; }
    .app { margin-top: 14px; background: #eef6f1; border: 1px solid #cfe6d9; border-radius: 6px; padding: 9px 12px; }
</style>
</head>
<body>
@php
    $show = fn ($x) => filled($x) ? e($x) : '-';
    $date = function ($x) {
        if (empty($x)) return null;
        try { return \Carbon\Carbon::parse($x)->format('d M Y'); } catch (\Throwable $e) { return (string) $x; }
    };
    $money = fn ($x) => $x !== null && $x !== '' ? 'Rs. ' . number_format((float) $x) : null;
    $kyc = $tenant->kyc_status ?: 'pending';
    $police = $tenant->police_verification_status ?: 'pending';
    $aadhaarOk = !empty($tenant->aadhaar_verified_at);
    $agreementOk = !empty($tenant->agreement_signed_at);
    $pill = fn (string $text, string $tone) => '<span class="pill ' . $tone . '">' . e($text) . '</span>';
    $docLabels = [
        'aadhaar_front' => 'Aadhaar (front)', 'aadhaar_back' => 'Aadhaar (back)', 'pan' => 'PAN card', 'photo' => 'Photo',
        'id_card' => 'College / office ID', 'employment_id' => 'Employment ID', 'student_id' => 'Student ID',
        'address_proof' => 'Address proof', 'police_verification' => 'Police verification', 'other' => 'Other',
    ];
    $email = $tenant->email && !str_ends_with($tenant->email, '@temp.pizi.in') ? $tenant->email : null;
    $pg = $tenant->property;
    $pgPlace = collect([$pg?->locality?->name, $pg?->city?->name])->filter()->implode(', ');
@endphp

<div class="head">
    <span class="copy">{{ $forOwner ? 'OWNER COPY' : 'TENANT COPY' }}</span>
    <div class="brand">Pizi</div>
    <h1>{{ $tenant->name }}</h1>
    <div class="muted">
        {{ $pg?->name }}@if($pgPlace), {{ $pgPlace }}@endif<br>
        Prepared on {{ now()->format('d M Y') }}
    </div>
</div>

<h2>Personal details</h2>
<table>
    <tr><td class="k">Full name</td><td class="v">{!! $show($tenant->name) !!}</td></tr>
    <tr><td class="k">Phone</td><td class="v">{!! $show($tenant->phone) !!}</td></tr>
    <tr><td class="k">Email</td><td class="v">{!! $show($email) !!}</td></tr>
    <tr><td class="k">Date of birth</td><td class="v">{!! $show($date($tenant->dob)) !!}</td></tr>
    <tr><td class="k">Gender</td><td class="v">{!! $show($tenant->gender ? ucfirst($tenant->gender) : null) !!}</td></tr>
    <tr><td class="k">Occupation</td><td class="v">{!! $show($tenant->occupation) !!}</td></tr>
    <tr><td class="k">Company / college</td><td class="v">{!! $show($tenant->company_college) !!}</td></tr>
</table>

<h2>Address</h2>
<table>
    <tr><td class="k">Address</td><td class="v">{!! $show($tenant->address_line) !!}</td></tr>
    <tr><td class="k">City</td><td class="v">{!! $show($tenant->city) !!}</td></tr>
    <tr><td class="k">State</td><td class="v">{!! $show($tenant->state) !!}</td></tr>
    <tr><td class="k">Pincode</td><td class="v">{!! $show($tenant->pincode) !!}</td></tr>
</table>

<h2>Emergency contact</h2>
<table>
    <tr><td class="k">Name</td><td class="v">{!! $show($tenant->emergency_name) !!}</td></tr>
    <tr><td class="k">Phone</td><td class="v">{!! $show($tenant->emergency_phone) !!}</td></tr>
    <tr><td class="k">Relation</td><td class="v">{!! $show($tenant->emergency_relation) !!}</td></tr>
</table>

<h2>Stay</h2>
<table>
    <tr><td class="k">PG</td><td class="v">{!! $show($pg?->name) !!}</td></tr>
    <tr><td class="k">Room / bed</td><td class="v">{!! $show(trim(($tenant->room_number ?? '') . ($tenant->bed_number ? ' / ' . $tenant->bed_number : ''))) !!}</td></tr>
    <tr><td class="k">Monthly rent</td><td class="v">{!! $show($money($tenant->monthly_rent)) !!}</td></tr>
    <tr><td class="k">Security deposit</td><td class="v">{!! $show($money($tenant->security_deposit)) !!}</td></tr>
    <tr><td class="k">Move-in date</td><td class="v">{!! $show($date($tenant->move_in_date)) !!}</td></tr>
    <tr><td class="k">Status</td><td class="v">{!! $show(ucfirst(str_replace('_', ' ', (string) $tenant->status))) !!}</td></tr>
</table>
@if($forOwner && filled($tenant->notes))
    <div class="note">{{ $tenant->notes }}</div>
@endif

<h2>Verification</h2>
<table>
    <tr><td class="k">KYC</td><td>{!! $pill(ucfirst($kyc), $kyc === 'approved' ? 'good' : ($kyc === 'rejected' ? 'bad' : 'warn')) !!}</td></tr>
    <tr><td class="k">Aadhaar</td><td>@if($aadhaarOk){{ $tenant->aadhaar_number_masked }} {!! $pill('Verified', 'good') !!}@else{!! $pill('Not verified', 'mute') !!}@endif</td></tr>
    <tr><td class="k">Police verification</td><td>{!! $pill($police === 'done' ? 'Done' : 'Pending', $police === 'done' ? 'good' : 'warn') !!}</td></tr>
    <tr><td class="k">Rental agreement</td><td>@if($agreementOk){!! $pill('Signed ' . $date($tenant->agreement_signed_at), 'good') !!}@else{!! $pill('Not signed', 'mute') !!}@endif</td></tr>
</table>

<h2>Documents</h2>
@if($tenant->documents->isEmpty())
    <p class="muted">No documents uploaded.@if($aadhaarOk) Aadhaar is verified, so none are needed.@endif</p>
@else
    <table>
        @foreach($tenant->documents as $doc)
            <tr><td class="k">{{ $docLabels[$doc->document_type] ?? 'Document' }}</td><td class="v">Uploaded {{ $date($doc->created_at) }}</td></tr>
        @endforeach
    </table>
@endif

@unless($forOwner)
    <div class="app">
        <b>Get the Pizi app</b><br>
        Pay your rent, see your bills, raise complaints and review your PG, and find new PGs, all in one app.<br>
        Download it from the Google Play Store: https://play.google.com/store/apps/details?id=com.pizi.india
    </div>
@endunless

<div class="foot">
    @if($forOwner)
        Owner copy, kept for your records. Only the last 4 digits of the Aadhaar are stored, never the full number.
    @else
        This sheet lists the details your PG owner has on record for you. If anything is wrong, please tell your PG owner.
    @endif
</div>
</body>
</html>
