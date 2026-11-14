@extends('layouts.dashboard')
@section('title', 'Add Tenant')
@section('content')

<div class="mb-6">
    <a href="{{ route('owner.tenants.index') }}" class="text-coral-500 font-bold">← Back to tenants</a>
    <h1 class="font-display font-black text-3xl mt-2">Add New Tenant</h1>
    <p class="text-ink-900/60 mt-1">Fill basic details. KYC documents can be uploaded next.</p>
</div>

<form method="POST" action="{{ route('owner.tenants.store') }}" class="space-y-5 max-w-4xl" id="tenantForm">
    @csrf

    <div class="bg-white p-6 rounded-2xl border border-ink-100">
        <h2 class="font-display font-bold text-lg mb-4"><i class="fa-solid fa-house fa-fw"></i> Property &amp; Room Assignment</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-3">
                <label class="text-xs font-bold uppercase text-ink-900/60">Select PG *</label>
                <select name="property_id" id="propertySelect" required class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
                    <option value="">— Select property —</option>
                    @foreach($properties as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->locality?->name }})</option>
                    @endforeach
                </select>
            </div>

            <!-- BED DROPDOWN (default mode) -->
            <div class="md:col-span-2" id="bedDropdownWrap">
                <label class="text-xs font-bold uppercase text-ink-900/60">Available Bed</label>
                <select name="bed_id" id="bedSelect" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200" disabled>
                    <option value="">— Select property first —</option>
                </select>
                <p class="text-xs text-ink-900/40 mt-1" id="bedHint"></p>
                <button type="button" id="manualEntryToggle" class="text-xs text-coral-500 font-bold mt-1 underline">
                    Room/bed not set up yet? Enter manually
                </button>
            </div>

            <!-- MANUAL ENTRY FALLBACK (hidden by default) -->
            <div id="manualEntryWrap" class="hidden md:col-span-2 grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-bold uppercase text-ink-900/60">Room Number</label>
                    <input name="room_number" id="roomNumberInput" placeholder="e.g. 101" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-ink-900/60">Bed Number</label>
                    <input name="bed_number" id="bedNumberInput" placeholder="e.g. A" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
                </div>
                <div class="col-span-2">
                    <button type="button" id="dropdownEntryToggle" class="text-xs text-coral-500 font-bold underline">
                        ← Use available beds dropdown instead
                    </button>
                </div>
            </div>

            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Monthly Rent (₹)</label>
                <input name="monthly_rent" id="monthlyRentInput" type="number" min="0" placeholder="8000" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
                     <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Security Deposit (₹)</label>
                <input name="security_deposit" id="securityDepositInput" type="number" min="0" placeholder="16000" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Security Deposit Status</label>
                <select name="security_deposit_status" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
                    <option value="">— Select —</option>
                    <option value="full">Full Paid</option>
                    <option value="half">◐ Half Paid</option>
                    <option value="pending">Pending</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Move-in Date</label>
                <input name="move_in_date" type="date" value="{{ date('Y-m-d') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>

            <div class="md:col-span-3 border-t border-ink-100 pt-4 mt-1">
                <h3 class="text-sm font-bold text-ink-900/70 mb-3"><i class="fa-solid fa-sack-dollar fa-fw"></i> Advance Rent (Optional)</h3>
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Advance Rent (Months)</label>
                <select name="advance_rent_months" id="advanceMonthsSelect" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
                    <option value="0">None</option>
                    <option value="1">1 Month</option>
                    <option value="2">2 Months</option>
                    <option value="3">3 Months</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="text-xs font-bold uppercase text-ink-900/60">Advance Amount Collected (₹)</label>
                <input name="advance_rent_amount" id="advanceAmountInput" type="number" min="0" placeholder="0" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
                <p class="text-xs text-ink-900/40 mt-1">Auto-calculated from monthly rent × months selected — edit if different.</p>
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl border-2 border-emerald-200 relative overflow-hidden">
        <h2 class="font-display font-bold text-lg mb-1"><i class="fa-solid fa-id-badge fa-fw"></i> Aadhaar Verification (Instant KYC)</h2>
        <p class="text-xs text-ink-900/50 mb-4">DigiLocker se: aap Start dabao, tenant apne phone par link kholkar Allow dabata hai. Naam, DOB, gender, address neeche khud bhar jayenge. Koi document upload nahi karna padega.</p>

        <div id="aadhaarStep0">
            <button type="button" id="aadhaarStartBtn" class="px-5 py-3 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-bold">Start Aadhaar Verification</button>
        </div>

        <div id="aadhaarStepLink" class="hidden mt-3">
            <p class="text-sm text-ink-900/70">Tenant apne phone par ye link kholega, DigiLocker mein sign in karke <b>Allow</b> dabayega. Aap yahin ruko, details apne aap bhar jayengi.</p>
            <div class="mt-2 flex flex-wrap gap-2 items-center">
                <input type="text" id="aadhaarLink" readonly class="flex-1 min-w-[220px] px-3 py-2.5 rounded-xl border border-ink-200 text-xs">
                <button type="button" id="aadhaarCopyBtn" class="px-4 py-2.5 bg-ink-900 text-white rounded-xl text-sm font-bold">Copy link</button>
                <a id="aadhaarWaBtn" href="#" target="_blank" rel="noopener" class="px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl text-sm font-bold">WhatsApp par bhejo</a>
                <a id="aadhaarOpenBtn" href="#" target="_blank" rel="noopener" class="px-4 py-2.5 border border-ink-200 rounded-xl text-sm font-bold">Isi phone par kholo</a>
            </div>
            <p class="text-xs text-ink-900/50 mt-2"><i class="fa-solid fa-spinner fa-spin fa-fw"></i> Tenant ke Allow karne ka intezaar...</p>
        </div>

        <p id="aadhaarStatus" class="text-xs mt-2"></p>

        <div id="aadhaarVerifiedBadge" class="hidden mt-2 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-2 text-emerald-700 text-sm font-bold">
            <i class="fa-solid fa-circle-check fa-fw"></i> Aadhaar verified — details below auto-filled. KYC will be marked Approved automatically.
        </div>

        <input type="hidden" name="aadhaar_verified" id="aadhaarVerifiedInput" value="0">
        <input type="hidden" name="aadhaar_number_masked" id="aadhaarMaskedInput" value="">
        <input type="hidden" name="aadhaar_name" id="aadhaarNameInput" value="">
        <input type="hidden" name="aadhaar_photo_base64" id="aadhaarPhotoInput" value="">
    </div>

    <div class="bg-white p-6 rounded-2xl border border-ink-100">
        <h2 class="font-display font-bold text-lg mb-4"><i class="fa-solid fa-user fa-fw"></i> Personal Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Full Name *</label>
                <input name="name" required class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Phone Number *</label>
                <input name="phone" required class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200" placeholder="10-digit mobile — used for tenant's OTP login">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Email</label>
                <input name="email" type="email" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Date of Birth</label>
                <input name="dob" type="date" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Gender</label>
                <select name="gender" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
                    <option value="">— Select —</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Occupation</label>
                <input name="occupation" placeholder="Student / Working Professional" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
            <div class="md:col-span-2">
                <label class="text-xs font-bold uppercase text-ink-900/60">Company / College</label>
                <input name="company_college" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-ink-100">
        <h2 class="font-display font-bold text-lg mb-4"><i class="fa-solid fa-location-dot fa-fw"></i> Permanent Address</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="text-xs font-bold uppercase text-ink-900/60">Address</label>
                <textarea name="address_line" rows="2" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200"></textarea>
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">City</label>
                <input name="city" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">State</label>
                <input name="state" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Pincode</label>
                <input name="pincode" maxlength="6" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-ink-100">
        <h2 class="font-display font-bold text-lg mb-4"><i class="fa-solid fa-triangle-exclamation fa-fw"></i> Emergency Contact</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Contact Name</label>
                <input name="emergency_name" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Contact Phone</label>
                <input name="emergency_phone" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-ink-900/60">Relation</label>
                <input name="emergency_relation" placeholder="Father / Mother / Spouse" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-ink-100">
        <h2 class="font-display font-bold text-lg mb-4"><i class="fa-solid fa-circle-check fa-fw"></i> KYC (Walk-in Verification)</h2>
        <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" name="kyc_verified_now" value="1" id="kycCheckbox" class="mt-1 w-5 h-5">
            <span class="text-sm text-ink-900/80">
                Main (owner) ne is tenant ka Aadhaar/PAN abhi khud verify kar liya hai.
                <span class="block text-ink-900/50 text-xs mt-0.5">KYC status turant "Approved" ho jaayega. Documents baad mein bhi upload kar sakte ho tenant profile page se.</span>
            </span>
        </label>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="px-8 py-4 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold text-lg shadow-lg shadow-coral-500/30">✓ Create Tenant</button>
        <a href="{{ route('owner.tenants.index') }}" class="px-8 py-4 border border-ink-200 rounded-xl font-bold">Cancel</a>
    </div>
</form>

<script>
(function () {
    const propertySelect   = document.getElementById('propertySelect');
    const bedSelect        = document.getElementById('bedSelect');
    const bedHint           = document.getElementById('bedHint');
    const monthlyRentInput = document.getElementById('monthlyRentInput');
    const roomNumberInput  = document.getElementById('roomNumberInput');
    const bedNumberInput   = document.getElementById('bedNumberInput');

    const bedDropdownWrap   = document.getElementById('bedDropdownWrap');
    const manualEntryWrap   = document.getElementById('manualEntryWrap');
    const manualEntryToggle = document.getElementById('manualEntryToggle');
    const dropdownEntryToggle = document.getElementById('dropdownEntryToggle');

    // Route template with placeholder — Laravel route() needs a real id,
    // so we generate it with a dummy value and swap it at runtime.
    const vacantBedsUrlTemplate = "{{ route('owner.tenants.vacant-beds', ['property' => '__PROP_ID__']) }}";

    propertySelect.addEventListener('change', function () {
        const propertyId = this.value;

        bedSelect.innerHTML = '<option value="">Loading...</option>';
        bedSelect.disabled = true;
        bedHint.textContent = '';

        if (!propertyId) {
            bedSelect.innerHTML = '<option value="">— Select property first —</option>';
            return;
        }

        const url = vacantBedsUrlTemplate.replace('__PROP_ID__', propertyId);

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(beds => {
                if (!beds.length) {
                    bedSelect.innerHTML = '<option value="">No vacant beds — set up rooms first</option>';
                    bedHint.textContent = 'Is property mein koi vacant bed nahi mili. Rooms & Beds section se pehle room/bed banao, ya niche "Enter manually" use karo.';
                    return;
                }

                let html = '<option value="">— Select a vacant bed —</option>';
                beds.forEach(bed => {
                    html += `<option value="${bed.id}" data-rent="${bed.monthly_rent ?? ''}">${bed.label}</option>`;
                });
                bedSelect.innerHTML = html;
                bedSelect.disabled = false;
                bedHint.textContent = beds.length + ' vacant bed(s) available.';
            })
            .catch(() => {
                bedSelect.innerHTML = '<option value="">Error loading beds — try again</option>';
                bedHint.textContent = 'Kuch gadbad ho gayi. Page refresh karke dobara try karo.';
            });
    });

    // Auto-fill rent when a bed is selected
    bedSelect.addEventListener('change', function () {
        const selected = this.options[this.selectedIndex];
        const rent = selected ? selected.getAttribute('data-rent') : '';
        if (rent) {
            monthlyRentInput.value = rent;
        }
    });

    // Toggle: dropdown mode -> manual entry mode
    manualEntryToggle.addEventListener('click', function () {
        bedDropdownWrap.classList.add('hidden');
        manualEntryWrap.classList.remove('hidden');
        bedSelect.value = ''; // clear bed_id so backend doesn't get stale value
    });

    // Toggle: manual entry mode -> dropdown mode
    dropdownEntryToggle.addEventListener('click', function () {
        manualEntryWrap.classList.add('hidden');
        bedDropdownWrap.classList.remove('hidden');
        roomNumberInput.value = '';
        bedNumberInput.value = '';
    });
    
})();

    // Advance rent auto-calculate
    const advanceMonthsSelect = document.getElementById('advanceMonthsSelect');
    const advanceAmountInput = document.getElementById('advanceAmountInput');

    function recalcAdvance() {
        const months = parseInt(advanceMonthsSelect.value) || 0;
        const rent = parseFloat(monthlyRentInput.value) || 0;
        if (months > 0 && rent > 0) {
            advanceAmountInput.value = months * rent;
        }
    }

    advanceMonthsSelect.addEventListener('change', recalcAdvance);
    monthlyRentInput.addEventListener('input', recalcAdvance);

    // ---- Aadhaar auto-KYC through DigiLocker: the tenant opens a link on their own phone ----
    (function () {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

        const startBtn = document.getElementById('aadhaarStartBtn');
        const step0 = document.getElementById('aadhaarStep0');
        const stepLink = document.getElementById('aadhaarStepLink');
        const linkInput = document.getElementById('aadhaarLink');
        const copyBtn = document.getElementById('aadhaarCopyBtn');
        const waBtn = document.getElementById('aadhaarWaBtn');
        const openBtn = document.getElementById('aadhaarOpenBtn');
        const status = document.getElementById('aadhaarStatus');
        const badge = document.getElementById('aadhaarVerifiedBadge');

        let timer = null;
        let giveUpAt = 0;

        function setStatus(msg, ok) {
            status.textContent = msg;
            status.className = 'text-xs mt-2 ' + (ok ? 'text-emerald-600' : 'text-rose-600');
        }

        function post(url, body) {
            return fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify(body),
            }).then(r => r.json());
        }

        function stopPolling() {
            if (timer) { clearInterval(timer); timer = null; }
        }

        function resetToStart(msg) {
            stopPolling();
            stepLink.classList.add('hidden');
            step0.classList.remove('hidden');
            startBtn.disabled = false;
            startBtn.textContent = 'Start Aadhaar Verification';
            if (msg) setStatus(msg, false);
        }

        function fill(data) {
            document.querySelector('[name="name"]').value = data.name || '';
            document.querySelector('[name="dob"]').value = data.dob || '';
            if (data.gender) document.querySelector('[name="gender"]').value = data.gender;
            document.querySelector('[name="address_line"]').value = data.address || '';
            if (data.city) document.querySelector('[name="city"]').value = data.city;
            if (data.state) document.querySelector('[name="state"]').value = data.state;
            if (data.pincode) document.querySelector('[name="pincode"]').value = data.pincode;

            document.getElementById('aadhaarVerifiedInput').value = '1';
            document.getElementById('aadhaarMaskedInput').value = 'XXXX-XXXX-' + (data.aadhaar_last4 || '');
            document.getElementById('aadhaarNameInput').value = data.name || '';

            const kycCheckbox = document.getElementById('kycCheckbox');
            if (kycCheckbox) kycCheckbox.checked = true;

            stepLink.classList.add('hidden');
            status.textContent = '';
            badge.classList.remove('hidden');
        }

        function poll() {
            if (Date.now() > giveUpAt) {
                return resetToStart('Bahut der ho gayi, link expire ho gaya. Dobara Start dabao.');
            }
            post('{{ route("owner.tenants.aadhaar.verifycaptcha") }}', {})
                .then(d => {
                    if (!d.success) { return resetToStart(d.message || 'Verification fail ho gaya.'); }
                    if (d.pending) { return; }
                    stopPolling();
                    fill(d.data);
                })
                .catch(() => { /* a hiccup in the network: try again on the next tick */ });
        }

        startBtn.addEventListener('click', function () {
            startBtn.disabled = true;
            startBtn.textContent = 'Loading...';
            setStatus('', true);

            post('{{ route("owner.tenants.aadhaar.start") }}', {})
                .then(d => {
                    if (!d.success) {
                        startBtn.disabled = false;
                        startBtn.textContent = 'Start Aadhaar Verification';
                        setStatus(d.message || 'Could not start verification.', false);
                        return;
                    }

                    linkInput.value = d.url;
                    openBtn.href = d.url;
                    const phone = (document.querySelector('[name="phone"]')?.value || '').replace(/\D/g, '').slice(-10);
                    const text = encodeURIComponent('Pizi: apna Aadhaar verify karne ke liye ye link kholo aur Allow dabao: ' + d.url);
                    waBtn.href = 'https://wa.me/' + (phone.length === 10 ? '91' + phone : '') + '?text=' + text;

                    step0.classList.add('hidden');
                    stepLink.classList.remove('hidden');
                    giveUpAt = Date.now() + 10 * 60 * 1000;
                    stopPolling();
                    timer = setInterval(poll, 3000);
                })
                .catch(() => {
                    startBtn.disabled = false;
                    startBtn.textContent = 'Start Aadhaar Verification';
                    setStatus('Kuch gadbad ho gayi, dobara try karo.', false);
                });
        });

        copyBtn.addEventListener('click', function () {
            linkInput.select();
            try { navigator.clipboard.writeText(linkInput.value); } catch (e) { document.execCommand('copy'); }
            copyBtn.textContent = 'Copied';
            setTimeout(() => { copyBtn.textContent = 'Copy link'; }, 1500);
        });
    })();

</script>

@endsection