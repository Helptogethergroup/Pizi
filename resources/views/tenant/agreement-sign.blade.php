@extends('layouts.tenant')
@section('title', 'Sign Agreement')
@section('content')
<div class="min-vh-100 d-flex align-items-center justify-content-center py-5" style="background:#f0f4f8;">
<div class="col-12 col-md-8 col-lg-6">

@if(session('error'))
<div class="alert alert-danger rounded-3 mb-4">{{ session('error') }}</div>
@endif

@if($agreement)
<div class="text-center mb-4">
    <div style="width:56px;height:56px;background:#0F2748;border-radius:16px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px;">
        <svg width="28" height="28" fill="none" viewBox="0 0 24 24"><path d="M9 12h6M9 16h6M13 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V9l-7-7z" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    <h4 class="fw-bold mb-1" style="color:#0F2748;">Sign Rental Agreement</h4>
    <p class="text-muted small mb-0">Review details and sign securely with Aadhaar OTP</p>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body p-4">
        <div class="d-flex align-items-center mb-3">
            <span class="badge rounded-pill px-3 py-2" style="background:#e8f5e9;color:#2e7d32;font-size:11px;">● Active Agreement</span>
            <span class="ms-auto text-muted small">ID: {{ $agreement->agreement_number }}</span>
        </div>
        <div class="row g-3">
            <div class="col-6">
                <div class="p-3 rounded-3" style="background:#f8f9fa;">
                    <div class="text-muted" style="font-size:10px;text-transform:uppercase;letter-spacing:.06em;">Tenant</div>
                    <div class="fw-semibold mt-1" style="color:#0F2748;">{{ $tenant->name ?? $user->name }}</div>
                </div>
            </div>
            <div class="col-6">
                <div class="p-3 rounded-3" style="background:#f8f9fa;">
                    <div class="text-muted" style="font-size:10px;text-transform:uppercase;letter-spacing:.06em;">Monthly Rent</div>
                    <div class="fw-semibold mt-1" style="color:#0F2748;">₹{{ number_format($agreement->monthly_rent ?? 0) }}</div>
                </div>
            </div>
            <div class="col-6">
                <div class="p-3 rounded-3" style="background:#f8f9fa;">
                    <div class="text-muted" style="font-size:10px;text-transform:uppercase;letter-spacing:.06em;">Start Date</div>
                    <div class="fw-semibold mt-1" style="color:#0F2748;">{{ $agreement->start_date ? \Carbon\Carbon::parse($agreement->start_date)->format('d M Y') : '—' }}</div>
                </div>
            </div>
            <div class="col-6">
                <div class="p-3 rounded-3" style="background:#f8f9fa;">
                    <div class="text-muted" style="font-size:10px;text-transform:uppercase;letter-spacing:.06em;">Security Deposit</div>
                    <div class="fw-semibold mt-1" style="color:#0F2748;">₹{{ number_format($agreement->security_deposit ?? 0) }}</div>
                </div>
            </div>
            <div class="col-6">
                <div class="p-3 rounded-3" style="background:#f8f9fa;">
                    <div class="text-muted" style="font-size:10px;text-transform:uppercase;letter-spacing:.06em;">Lock-in Period</div>
                    <div class="fw-semibold mt-1" style="color:#0F2748;">{{ $agreement->lock_in_months ?? '—' }} months</div>
                </div>
            </div>
            <div class="col-6">
                <div class="p-3 rounded-3" style="background:#f8f9fa;">
                    <div class="text-muted" style="font-size:10px;text-transform:uppercase;letter-spacing:.06em;">End Date</div>
                    <div class="fw-semibold mt-1" style="color:#0F2748;">{{ $agreement->end_date ? \Carbon\Carbon::parse($agreement->end_date)->format('d M Y') : '—' }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 rounded-4 mb-3" style="background:#fff8e1;">
    <div class="card-body p-3 d-flex gap-3 align-items-start">
        <span style="font-size:20px;"><i class="fa-solid fa-lock fa-fw"></i></span>
        <div>
            <div class="fw-semibold" style="color:#92400e;font-size:13px;">Aadhaar-based eSign</div>
            <div class="text-muted" style="font-size:12px;">You will receive an OTP on your Aadhaar-linked mobile. This signature is legally valid under IT Act 2000.</div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="agreeTerms" style="width:18px;height:18px;margin-top:2px;">
            <label class="form-check-label ms-2" for="agreeTerms" style="font-size:13px;color:#444;">
                I have read and agree to all terms and conditions of this rental agreement and consent to signing with Aadhaar-based eSignature.
            </label>
        </div>
    </div>
</div>

<button id="signBtn" class="btn w-100 fw-semibold py-3 rounded-3 mb-2" style="background:#FF6B5B;color:#fff;border:none;font-size:15px;" onclick="initiateEsign()" disabled>
    <span id="btnText"><i class="fa-solid fa-lock fa-fw"></i> Sign with Aadhaar eSign</span>
    <span id="btnSpinner" class="d-none">
        <span class="spinner-border spinner-border-sm me-2"></span>Preparing document...
    </span>
</button>
<a href="{{ route('tenant.onboarding') }}" class="btn w-100 btn-outline-secondary rounded-3 py-2">← Back to Onboarding</a>

@else
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-5 text-center">
        <div style="font-size:52px;"><i class="fa-solid fa-clipboard-list fa-fw"></i></div>
        <h5 class="mt-3 fw-bold" style="color:#0F2748;">No Agreement Found</h5>
        <p class="text-muted">Your rental agreement has not been created yet. The PG owner will create it once your profile is reviewed.</p>
        <a href="{{ route('tenant.onboarding') }}" class="btn btn-outline-secondary mt-2 rounded-3">← Back to Onboarding</a>
    </div>
</div>
@endif

</div>
</div>

@if($agreement)
<script>
document.getElementById('agreeTerms').addEventListener('change', function() {
    document.getElementById('signBtn').disabled = !this.checked;
});
async function initiateEsign() {
    const btn = document.getElementById('signBtn');
    const btnText = document.getElementById('btnText');
    const spinner = document.getElementById('btnSpinner');
    btn.disabled = true;
    btnText.classList.add('d-none');
    spinner.classList.remove('d-none');
    try {
        const res = await fetch('{{ route("tenant.agreement.sign.submit") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ agreement_id: {{ $agreement->id }} }),
        });
        const data = await res.json();
        if (!res.ok || !data.success) throw new Error(data.message || data.error || 'Something went wrong.');
        window.location.href = data.signer_url;
    } catch (err) {
        spinner.classList.add('d-none');
        btnText.classList.remove('d-none');
        btn.disabled = false;
        alert('Error: ' + err.message);
    }
}
</script>
@endif
@endsection