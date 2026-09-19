<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $agreement->agreement_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f6f5f1; }
        h1, h2, h3 { font-family: 'Fraunces', serif; }
        .clause { text-align: justify; }
      @media print {
    body { background: white; }
    .no-print { display: none !important; }
    .doc { box-shadow: none !important; }
}
@media screen {
    .no-print { display: flex !important; }
}
    </style>
@include('partials.emoji-icons')
</head>
<body class="py-8">
    <div class="max-w-3xl mx-auto bg-white p-10 rounded-2xl shadow-lg doc">
        
        <div class="text-center pb-6 border-b-4 border-double border-ink-950">
            <h1 class="font-black text-3xl">RENT AGREEMENT</h1>
            <p class="text-sm text-ink-700 mt-2">Agreement No: <strong>{{ $agreement->agreement_number }}</strong></p>
            <p class="text-xs text-ink-500 mt-1">Issued at PIZI — Live Better. Stay Smarter.</p>
        </div>

        <div class="mt-8 clause">
            <p class="text-sm leading-relaxed">
                THIS RENT AGREEMENT is executed on <strong>{{ $agreement->start_date->format('d') }}</strong>th day of <strong>{{ $agreement->start_date->format('F Y') }}</strong> at New Delhi.
            </p>

            <p class="text-sm leading-relaxed mt-4"><strong>BETWEEN</strong></p>

            <p class="text-sm leading-relaxed mt-2">
                <strong>{{ $agreement->owner?->name }}</strong> 
                @if($agreement->owner?->phone), Phone: {{ $agreement->owner->phone }}@endif
                (hereinafter referred to as the "LICENSOR/OWNER")
            </p>

            <p class="text-sm leading-relaxed mt-3"><strong>AND</strong></p>

            <p class="text-sm leading-relaxed mt-2">
                <strong>{{ $agreement->tenant?->name }}</strong>, 
                S/o or D/o {{ $agreement->tenant?->emergency_name ?? '_________________' }}, 
                Phone: {{ $agreement->tenant?->phone }},
                @if($agreement->tenant?->address_line)
                    R/o {{ $agreement->tenant->address_line }}, {{ $agreement->tenant->city }}, {{ $agreement->tenant->state }} - {{ $agreement->tenant->pincode }}
                @endif
                (hereinafter referred to as the "LICENSEE/TENANT")
            </p>

            <p class="text-sm leading-relaxed mt-6">
                WHEREAS the Owner is the lawful possessor of the property situated at: 
                <strong>{{ $agreement->property?->name }}, {{ $agreement->property?->address_line }}, {{ $agreement->property?->locality?->name }}, {{ $agreement->property?->city?->name }}</strong>
                @if($agreement->property?->pincode) — Pincode: {{ $agreement->property->pincode }}@endif.
            </p>

            <p class="text-sm leading-relaxed mt-4">
                AND WHEREAS, the Tenant has approached the Owner with a request to grant license to use the said premises 
                — <strong>Room No. {{ $agreement->room_number ?? '___' }}{{ $agreement->bed_number ? ', Bed '.$agreement->bed_number : '' }}</strong> 
                — for residential purposes on the terms hereinafter appearing.
            </p>

            <h3 class="font-bold text-base mt-6 mb-2">NOW THIS AGREEMENT WITNESSETH AS FOLLOWS:</h3>

            <p class="text-sm leading-relaxed mt-3">
                <strong>1. DURATION:</strong> The license is granted for a period of <strong>{{ $agreement->duration_months }} months</strong> 
                commencing from <strong>{{ $agreement->start_date->format('d M Y') }}</strong> and ending on <strong>{{ $agreement->end_date->format('d M Y') }}</strong>.
            </p>

            <p class="text-sm leading-relaxed mt-3">
                <strong>2. MONTHLY RENT:</strong> The Tenant shall pay a monthly license fee (rent) of 
                <strong>₹{{ number_format($agreement->monthly_rent, 0) }}/-</strong> 
                (Rupees {{ ucfirst(\Illuminate\Support\Number::spell((int)$agreement->monthly_rent)) }} only) 
                payable on or before the <strong>{{ $agreement->rent_due_day }}th day of each month</strong>.
            </p>

            <p class="text-sm leading-relaxed mt-3">
                <strong>3. SECURITY DEPOSIT:</strong> The Tenant has paid a refundable security deposit of 
                <strong>₹{{ number_format($agreement->security_deposit, 0) }}/-</strong>, 
                to be refunded at the time of vacating, after deducting any damages or pending dues.
            </p>

            @if($agreement->maintenance_fee > 0)
            <p class="text-sm leading-relaxed mt-3">
                <strong>4. MAINTENANCE:</strong> A monthly maintenance fee of <strong>₹{{ number_format($agreement->maintenance_fee, 0) }}/-</strong> 
                shall be payable separately.
            </p>
            @endif

            <p class="text-sm leading-relaxed mt-3">
                <strong>{{ $agreement->maintenance_fee > 0 ? '5' : '4' }}. INCLUSIONS:</strong> The following are 
                @if(!$agreement->electricity_included && !$agreement->water_included && !$agreement->food_included)
                    NOT included in the rent.
                @else
                    included in the rent: 
                    @if($agreement->water_included) Water,@endif
                    @if($agreement->electricity_included) Electricity,@endif
                    @if($agreement->food_included) Food,@endif
                @endif
                Other utilities shall be borne by the Tenant.
            </p>

            <p class="text-sm leading-relaxed mt-3">
                <strong>{{ $agreement->maintenance_fee > 0 ? '6' : '5' }}. LOCK-IN PERIOD:</strong> 
                The Tenant agrees to a minimum lock-in period of <strong>{{ $agreement->lock_in_months }} months</strong>. 
                Early termination during lock-in will forfeit the security deposit.
            </p>

            <p class="text-sm leading-relaxed mt-3">
                <strong>{{ $agreement->maintenance_fee > 0 ? '7' : '6' }}. NOTICE PERIOD:</strong> 
                Either party may terminate this agreement after the lock-in period by giving 
                <strong>{{ $agreement->notice_period_days }} days written notice</strong>.
            </p>

            <p class="text-sm leading-relaxed mt-3">
                <strong>{{ $agreement->maintenance_fee > 0 ? '8' : '7' }}. USE OF PREMISES:</strong> 
                The premises shall be used solely for residential purposes. Sub-letting, commercial use, or alteration 
                of the property is strictly prohibited.
            </p>

            <p class="text-sm leading-relaxed mt-3">
                <strong>{{ $agreement->maintenance_fee > 0 ? '9' : '8' }}. UPKEEP:</strong> 
                The Tenant shall maintain the premises in good and tenantable condition. Any damage caused by negligence 
                shall be repaired at the Tenant's expense.
            </p>

            @if($agreement->house_rules)
            <p class="text-sm leading-relaxed mt-3">
                <strong>{{ $agreement->maintenance_fee > 0 ? '10' : '9' }}. HOUSE RULES:</strong>
            </p>
            <div class="ml-4 mt-1 text-sm whitespace-pre-line">{{ $agreement->house_rules }}</div>
            @endif

            @if($agreement->additional_terms)
            <p class="text-sm leading-relaxed mt-3">
                <strong>ADDITIONAL TERMS:</strong>
            </p>
            <div class="ml-4 mt-1 text-sm whitespace-pre-line">{{ $agreement->additional_terms }}</div>
            @endif

            <p class="text-sm leading-relaxed mt-4">
                IN WITNESS WHEREOF, the parties have set their respective hands on this Agreement on the date first above written.
            </p>
        </div>

        <div class="mt-12 grid grid-cols-2 gap-12">
            <div class="text-center">
                <div class="h-20 border-b-2 border-ink-950 mb-2"></div>
                <p class="text-xs text-ink-700">SIGNATURE OF OWNER</p>
                <p class="font-bold mt-1">{{ $agreement->owner?->name }}</p>
                @if($agreement->ownerSigned())
                    @php $sig = $agreement->signatures->firstWhere('signer_type', 'owner'); @endphp
                    <p class="text-xs text-emerald-700 mt-1">✓ Signed: {{ $sig->signed_at->format('d M Y, h:i A') }}</p>
                @endif
            </div>

            <div class="text-center">
                <div class="h-20 border-b-2 border-ink-950 mb-2"></div>
                <p class="text-xs text-ink-700">SIGNATURE OF TENANT</p>
                <p class="font-bold mt-1">{{ $agreement->tenant?->name }}</p>
                @if($agreement->tenantSigned())
                    @php $sig = $agreement->signatures->firstWhere('signer_type', 'tenant'); @endphp
                    <p class="text-xs text-emerald-700 mt-1">✓ Signed: {{ $sig->signed_at->format('d M Y, h:i A') }}</p>
                @endif
            </div>
        </div>

        <div class="mt-12 pt-6 border-t border-ink-100 text-center text-xs text-ink-500">
            <p>This is a digitally generated document via PIZI platform.</p>
            <p class="mt-1">Generated on: {{ now()->format('d M Y, h:i A') }}</p>
        </div>

                    <div class="no-print" style="margin-top:2rem; display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
                        <a href="{{ auth()->user()?->role === 'tenant' ? route('tenant.agreement.download', $agreement->id) : route('owner.agreements.download-pdf', $agreement) }}"
               style="display:inline-flex; align-items:center; gap:8px; padding:12px 24px; background:#0f2748; color:white; border-radius:12px; font-weight:700; font-size:14px; text-decoration:none;">
                <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                Download PDF
            </a>
            <button onclick="window.print()"
               style="display:inline-flex; align-items:center; gap:8px; padding:12px 24px; background:#ff6b5b; color:white; border:none; border-radius:12px; font-weight:700; font-size:14px; cursor:pointer;">
                <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z"/></svg>
                Print
            </button>
            <button onclick="window.close()"
               style="display:inline-flex; align-items:center; gap:8px; padding:12px 24px; background:white; color:#333; border:2px solid #ddd; border-radius:12px; font-weight:700; font-size:14px; cursor:pointer;">
                ✕ Close
            </button>
        </div>

        <script>
        function downloadPDF() {
            const btn = document.querySelectorAll('.no-print');
            btn.forEach(el => el.style.display = 'none');

            window.print();

            setTimeout(() => {
                btn.forEach(el => el.style.display = '');
            }, 1000);
        }
        </script>
    </div>
</body>
</html>