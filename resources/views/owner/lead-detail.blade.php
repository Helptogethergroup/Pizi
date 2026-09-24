@extends('layouts.dashboard')
@section('title', 'Lead — ' . $lead->name)
@section('content')

<div class="max-w-5xl mx-auto">

    <a href="{{ route('owner.leads.index') }}" class="text-coral-600 font-semibold text-sm mb-4 inline-block">← Back to Leads</a>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-900 px-4 py-3 rounded-xl text-sm mb-4">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-900 px-4 py-3 rounded-xl text-sm mb-4">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- LEFT: Lead details + status + remark form -->
        <div class="md:col-span-2 space-y-6">

            <!-- Lead Details -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $lead->name }}</h1>
                        <p class="text-sm text-gray-500 mt-1">{{ $lead->source ?? 'website' }} · Lead #{{ $lead->id }}</p>
                    </div>
                    @php
                        $badgeClass = match($lead->status) {
                            'new_lead' => 'bg-blue-100 text-blue-700',
                            'open' => 'bg-sky-100 text-sky-700',
                            'contacted' => 'bg-amber-100 text-amber-700',
                            'connected' => 'bg-teal-100 text-teal-700',
                            'not_connected' => 'bg-gray-200 text-gray-700',
                            'follow_up' => 'bg-purple-100 text-purple-700',
                            'visit_scheduled' => 'bg-indigo-100 text-indigo-700',
                            'visit_completed' => 'bg-cyan-100 text-cyan-700',
                            'deal_closed' => 'bg-emerald-100 text-emerald-700',
                            'lost' => 'bg-rose-100 text-rose-700',
                            'cancelled' => 'bg-red-100 text-red-700',
                            default => 'bg-blue-100 text-blue-700',
                        };
                    @endphp
                    <span id="statusBadge" class="px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wide {{ $badgeClass }}">
                        {{ str_replace('_', ' ', $lead->status ?? 'new_lead') }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Phone</p>
                        <p class="font-semibold text-gray-900">{{ $lead->phone ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Email</p>
                        <p class="font-semibold text-gray-900">{{ $lead->email ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Preferred Locality</p>
                        <p class="font-semibold text-gray-900">{{ $lead->preferred_locality ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Budget</p>
                        <p class="font-semibold text-gray-900">
                            @if($lead->budget_min || $lead->budget_max)
                                ₹{{ number_format($lead->budget_min ?? 0) }} – ₹{{ number_format($lead->budget_max ?? 0) }}
                            @else — @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-500">Move-in Date</p>
                        <p class="font-semibold text-gray-900">{{ $lead->move_in_date ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Next Follow-up</p>
                        <p class="font-semibold text-gray-900" id="followUpDisplay">{{ $lead->next_follow_up_at ?? 'Not set' }}</p>
                    </div>
                </div>

                @if($lead->owner_safe_message)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-gray-500 text-sm mb-1">Message from lead</p>
                        <p class="text-gray-800 text-sm italic">"{{ $lead->owner_safe_message }}"</p>
                    </div>
                @endif

                <div class="mt-4 flex gap-2">
                    <a href="tel:{{ $lead->phone }}" class="flex-1 text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg text-sm transition"><i class="fa-solid fa-phone fa-fw"></i> Call</a>
                    <a href="https://wa.me/91{{ $lead->phone }}" target="_blank" class="flex-1 text-center bg-emerald-500 hover:bg-emerald-600 text-white font-semibold py-2.5 rounded-lg text-sm transition"><i class="fa-solid fa-comment-dots fa-fw"></i> WhatsApp</a>
                </div>
            </div>

            <!-- Correct Inquiry Type -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h2 class="font-bold text-gray-900 mb-1">Lead Type</h2>
                <p class="text-xs text-gray-500 mb-4">Automatically guessed — fix it if it's wrong (e.g. the message shows they're actually an owner, not a tenant).</p>
                <form method="POST" action="{{ route('owner.leads.updateInquiryType', $lead) }}" class="flex flex-wrap gap-3 items-end">
                    @csrf @method('PATCH')
                    <select name="inquiry_type" class="px-4 py-2.5 border border-gray-300 rounded-lg text-sm">
                        <option value="tenant" @selected($lead->inquiry_type === 'tenant')>Tenant — looking for a PG</option>
                        <option value="owner" @selected($lead->inquiry_type === 'owner')>Owner — wants to list a PG</option>
                        <option value="unknown" @selected($lead->inquiry_type === 'unknown')>Unknown</option>
                    </select>
                    <button class="bg-ink-950 text-white font-bold px-5 py-2.5 rounded-lg text-sm">Save</button>
                </form>
            </div>

            <!-- Change Status -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h2 class="font-bold text-gray-900 mb-4">Update Status</h2>

                <div id="statusError" class="hidden bg-red-50 text-red-700 text-sm px-3 py-2 rounded-lg mb-3"></div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1.5">New Status</label>
                        <select id="statusSelect" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-coral-500">
                            @foreach(['new_lead'=>'New Lead','open'=>'Open','contacted'=>'Contacted','connected'=>'Connected','not_connected'=>'Not Connected','follow_up'=>'Follow Up','visit_scheduled'=>'Visit Scheduled','visit_completed'=>'Visit Completed','deal_closed'=>'Deal Closed','lost'=>'Lost','cancelled'=>'Cancelled'] as $val => $label)
                                <option value="{{ $val }}" @selected($lead->status === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1.5">Follow-up Date (optional)</label>
                        <input type="date" id="followUpInput" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-coral-500">
                    </div>
                </div>

                <textarea id="statusRemark" rows="3" placeholder="Add a remark for this status change..." class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-coral-500 mb-4"></textarea>

                <button onclick="submitStatusUpdate()" id="statusBtn" class="bg-coral-500 hover:bg-coral-600 text-white font-bold px-6 py-2.5 rounded-lg text-sm transition">
                    Update Status
                </button>
            </div>

            <!-- Add standalone remark -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h2 class="font-bold text-gray-900 mb-4">Add Follow-up Note</h2>
                <div id="remarkError" class="hidden bg-red-50 text-red-700 text-sm px-3 py-2 rounded-lg mb-3"></div>
                <textarea id="quickRemark" rows="2" placeholder="e.g. Called, will confirm visit tomorrow" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-coral-500 mb-3"></textarea>
                <button onclick="submitRemark()" id="remarkBtn" class="bg-gray-800 hover:bg-gray-900 text-white font-semibold px-6 py-2.5 rounded-lg text-sm transition">
                    Add Note
                </button>
            </div>

        </div>

        <!-- RIGHT: Activity timeline -->
        <div>
            <div class="bg-white rounded-2xl border border-gray-200 p-6 sticky top-6">
                <h2 class="font-bold text-gray-900 mb-4">Activity Timeline</h2>
                <div id="timelineList" class="space-y-4 max-h-[600px] overflow-y-auto pr-1">
                    @forelse($history as $h)
                        <div class="border-l-2 border-coral-300 pl-3 pb-1">
                            <p class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($h->created_at)->format('d M Y, h:i A') }} · {{ $h->updated_by_name ?? 'System' }}</p>
                            @if($h->old_status !== $h->new_status)
                                <p class="text-sm font-semibold text-gray-900 mt-0.5">
                                    {{ str_replace('_',' ',$h->old_status ?? 'none') }} → {{ str_replace('_',' ',$h->new_status) }}
                                </p>
                            @else
                                <p class="text-sm font-semibold text-gray-900 mt-0.5">Note added</p>
                            @endif
                            @if($h->remark)
                                <p class="text-sm text-gray-600 mt-1">{{ $h->remark }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">No activity yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    const leadId = {{ $lead->id }};
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

    async function submitStatusUpdate() {
        const status = document.getElementById('statusSelect').value;
        const remark = document.getElementById('statusRemark').value.trim();
        const followUp = document.getElementById('followUpInput').value;
        const btn = document.getElementById('statusBtn');
        const errBox = document.getElementById('statusError');
        errBox.classList.add('hidden');

        btn.disabled = true;
        btn.textContent = 'Updating...';

        try {
            const res = await fetch(`/owner/leads/${leadId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status, remark: remark || null, follow_up_date: followUp || null })
            });
            const data = await res.json();

            if (data.success) {
                if (status === 'deal_closed' && data.redirect_to_tenant) {
                    window.location.href = data.redirect_to_tenant;
                } else {
                    location.reload();
                }
            } else {
                errBox.textContent = data.message || 'Something went wrong';
                errBox.classList.remove('hidden');
                btn.disabled = false;
                btn.textContent = 'Update Status';
            }
        } catch (e) {
            errBox.textContent = 'Network error, please try again';
            errBox.classList.remove('hidden');
            btn.disabled = false;
            btn.textContent = 'Update Status';
        }
    }

    async function submitRemark() {
        const remark = document.getElementById('quickRemark').value.trim();
        const btn = document.getElementById('remarkBtn');
        const errBox = document.getElementById('remarkError');
        errBox.classList.add('hidden');

        if (!remark) {
            errBox.textContent = 'Please write a note';
            errBox.classList.remove('hidden');
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Saving...';

        try {
            const res = await fetch(`/owner/leads/${leadId}/remark`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ remark })
            });
            const data = await res.json();

            if (data.success) {
                location.reload();
            } else {
                errBox.textContent = data.message || 'Something went wrong';
                errBox.classList.remove('hidden');
                btn.disabled = false;
                btn.textContent = 'Add Note';
            }
        } catch (e) {
            errBox.textContent = 'Network error, please try again';
            errBox.classList.remove('hidden');
            btn.disabled = false;
            btn.textContent = 'Add Note';
        }
    }
</script>

@endsection