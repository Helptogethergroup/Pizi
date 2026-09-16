@extends('layouts.dashboard')
@section('title', 'My Leads')
@section('content')

<div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-3">
        <h1 class="font-display font-black text-3xl">My leads</h1>
        <a href="{{ route('leads.manual.create') }}" class="px-4 py-2 bg-coral-500 text-white rounded-lg font-semibold text-sm">+ Add Lead</a>
    </div>
    <form class="flex gap-2">
        <input name="search" value="{{ request('search') }}" placeholder="Name / phone…" class="px-3 py-2 rounded-lg border border-ink-900/15">
        <select name="status" class="px-3 py-2 rounded-lg border border-ink-900/15">
            <option value="">All</option>
            @foreach(['new','contacted','interested','follow_up','visit_scheduled','visit_done','closed_won','closed_lost','junk'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ str_replace('_',' ',$s) }}</option>
            @endforeach
        </select>
        <select name="lead_type" class="px-3 py-2 rounded-lg border border-ink-900/15">
            <option value="">All Types</option>
            <option value="verified" @selected(request('lead_type') == 'verified')>Verified</option>
            <option value="manual" @selected(request('lead_type') == 'manual')>Direct</option>
        </select>
        <button class="px-4 py-2 bg-ink-900 text-cream rounded-lg">Filter</button>
    </form>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <a href="{{ route('telecaller.leads.index', array_merge(request()->except('page'), ['inquiry_type' => 'tenant'])) }}"
       class="rounded-2xl border-2 p-5 flex items-center justify-between transition {{ request('inquiry_type') === 'tenant' ? 'border-coral-500 bg-coral-50' : 'border-ink-900/10 bg-white hover:border-coral-300' }}">
        <div>
            <div class="text-xs uppercase font-bold text-ink-900/50">Looking for a PG</div>
            <div class="font-display font-black text-2xl">🧳 Tenant Leads</div>
        </div>
        <div class="text-3xl font-black text-coral-500">{{ $tenantCount }}</div>
    </a>
    <a href="{{ route('telecaller.leads.index', array_merge(request()->except('page'), ['inquiry_type' => 'owner'])) }}"
       class="rounded-2xl border-2 p-5 flex items-center justify-between transition {{ request('inquiry_type') === 'owner' ? 'border-coral-500 bg-coral-50' : 'border-ink-900/10 bg-white hover:border-coral-300' }}">
        <div>
            <div class="text-xs uppercase font-bold text-ink-900/50">Wants to list their PG</div>
            <div class="font-display font-black text-2xl">🏠 Owner Leads</div>
        </div>
        <div class="text-3xl font-black text-coral-500">{{ $ownerCount }}</div>
    </a>
</div>

@if(request()->filled('inquiry_type'))
    <div class="mb-4">
        <a href="{{ route('telecaller.leads.index', request()->except(['inquiry_type', 'page'])) }}" class="text-sm text-ink-900/60 hover:text-ink-900">← Show all leads (Tenant + Owner)</a>
    </div>
@endif

<div class="bg-white rounded-2xl border border-ink-900/10 overflow-x-auto">
    <table class="w-full text-sm min-w-[640px]">
        <thead class="bg-ink-900/5 text-left text-ink-900/60 text-xs uppercase">
            <tr>
                <th class="px-4 py-3">Lead</th>
                <th>Type</th>
                <th>Source</th>
                <th>Property</th>
                <th>Status</th>
                <th>Lock</th>
                <th>When</th>
                <th>Last contact</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($leads as $lead)
            <tr class="border-t border-ink-900/5 hover:bg-ink-900/5">
                <td class="px-4 py-3">
                    <div class="font-semibold">{{ $lead->name }}</div>
                    <div class="text-xs text-ink-900/50">📞 {{ $lead->phone }}</div>
                </td>
                <td>
                    @php $ib = $lead->inquiryTypeBadge(); @endphp
                    <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $ib['class'] }}">{{ $ib['label'] }}</span>
                </td>
                <td>
                    @php $sb = $lead->sourceBadge(); @endphp
                    <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $sb['class'] }}">{{ $sb['label'] }}</span>
                </td>
                <td class="text-xs">{{ $lead->property?->name ?? 'General' }}</td>
                <td>
                    <span class="px-2 py-1 rounded-full text-xs {{ $lead->statusBadge() }}">
                        {{ str_replace('_',' ',$lead->status) }}
                    </span>
                </td>
                <td class="text-xs text-ink-900/60">
                    {{ $lead->created_at->diffForHumans() }}
                    <div class="text-ink-900/40">{{ $lead->created_at->format('D, d M Y · h:i A') }}</div>
                </td>
                <td class="text-xs text-ink-900/60">{{ $lead->last_contacted_at?->diffForHumans() ?? '—' }}</td>
                <td class="px-4 py-3">
                    <div class="flex gap-1 flex-wrap">
                        <button
                            onclick="openEditModal({{ $lead->id }})"
                            class="text-xs px-2 py-1 rounded bg-blue-500 text-white font-semibold hover:bg-blue-600"
                        >
                            ✏️ Edit
                        </button>

                        <button
                            onclick="openRemarkModal({{ $lead->id }})"
                            class="text-xs px-2 py-1 rounded bg-purple-500 text-white font-semibold hover:bg-purple-600"
                        >
                            💬 Remark
                        </button>

                        <a href="{{ route('telecaller.leads.show', $lead) }}" class="text-xs px-2 py-1 rounded bg-ink-900 text-cream font-semibold">Open</a>

                        <a href="tel:{{ $lead->phone }}" class="text-xs px-2 py-1 rounded bg-emerald-500 text-white font-semibold">📞 Call</a>
                    </div>
                </td>
                <td>
                    @php
                        $ownerClaim = null;
                        if ($lead->is_locked && $lead->locked_by_user_id) {
                            $lockUser = \App\Models\User::find($lead->locked_by_user_id);
                            $ownerClaim = ($lockUser && $lockUser->role === 'owner') ? $lockUser->name : null;
                        }
                    @endphp
                    @if($ownerClaim)
                        <span class="text-xs px-2 py-1 rounded-full bg-emerald-100 text-emerald-700 font-semibold">🔒 Owner claimed</span>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="mt-6">{{ $leads->links() }}</div>

<!-- ===== EDIT MODAL ===== -->
<div id="editModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 w-96 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-display font-black text-2xl">Edit Lead</h2>
            <button onclick="closeEditModal()" class="text-2xl text-ink-900/50 hover:text-ink-900">×</button>
        </div>

        <form id="editForm" class="space-y-3">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs uppercase text-ink-900/60 mb-1">Name</label>
                <input type="text" id="editName" name="name" required class="w-full px-3 py-2 rounded-lg border border-ink-900/15">
            </div>

            <div>
                <label class="block text-xs uppercase text-ink-900/60 mb-1">Phone</label>
                <input type="text" id="editPhone" name="phone" required class="w-full px-3 py-2 rounded-lg border border-ink-900/15">
            </div>

            <div>
                <label class="block text-xs uppercase text-ink-900/60 mb-1">Email</label>
                <input type="email" id="editEmail" name="email" class="w-full px-3 py-2 rounded-lg border border-ink-900/15">
            </div>

            <div>
                <label class="block text-xs uppercase text-ink-900/60 mb-1">Inquiry Type</label>
                <select id="editInquiryType" name="inquiry_type" class="w-full px-3 py-2 rounded-lg border border-ink-900/15">
                    <option value="tenant">🧳 Tenant — looking for a PG</option>
                    <option value="owner">🏠 Owner — wants to list a PG</option>
                    <option value="unknown">❓ Unknown</option>
                </select>
            </div>

            <div>
                <label class="block text-xs uppercase text-ink-900/60 mb-1">Status</label>
                <select id="editStatus" name="status" class="w-full px-3 py-2 rounded-lg border border-ink-900/15">
                    @foreach(['new','contacted','interested','follow_up','visit_scheduled','visit_done','closed_won','closed_lost','junk'] as $s)
                        <option value="{{ $s }}">{{ str_replace('_',' ',$s) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs uppercase text-ink-900/60 mb-1">City</label>
                <input type="text" id="editCity" name="preferred_city" class="w-full px-3 py-2 rounded-lg border border-ink-900/15">
            </div>

            <div>
                <label class="block text-xs uppercase text-ink-900/60 mb-1">Budget Min</label>
                <input type="number" id="editBudgetMin" name="budget_min" class="w-full px-3 py-2 rounded-lg border border-ink-900/15">
            </div>

            <div>
                <label class="block text-xs uppercase text-ink-900/60 mb-1">Budget Max</label>
                <input type="number" id="editBudgetMax" name="budget_max" class="w-full px-3 py-2 rounded-lg border border-ink-900/15">
            </div>

            <div>
                <label class="block text-xs uppercase text-ink-900/60 mb-1">Notes</label>
                <textarea id="editNotes" name="notes" rows="2" class="w-full px-3 py-2 rounded-lg border border-ink-900/15"></textarea>
            </div>

            <div class="flex gap-2 mt-6">
                <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-2 rounded-lg border border-ink-900/15 text-ink-900 font-semibold hover:bg-ink-900/5">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-2 rounded-lg bg-ink-900 text-cream font-semibold hover:bg-ink-900/90">
                    Update Lead
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== REMARK MODAL ===== -->
<div id="remarkModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 w-96 max-h-80 overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-display font-black text-2xl">Add Remark</h2>
            <button onclick="closeRemarkModal()" class="text-2xl text-ink-900/50 hover:text-ink-900">×</button>
        </div>

        <div class="space-y-3">
            <div>
                <label class="block text-xs uppercase text-ink-900/60 mb-2">Remark</label>
                <textarea 
                    id="remarkText" 
                    rows="3" 
                    class="w-full px-3 py-2 rounded-lg border border-ink-900/15 text-sm"
                    placeholder="Add your remark..."
                ></textarea>
            </div>

            <!-- Display Remarks -->
            <div id="remarksList" class="border-t border-ink-900/10 pt-3 max-h-40 overflow-y-auto">
                <p class="text-xs uppercase text-ink-900/60 mb-2">All Remarks</p>
                <div id="remarksContainer" class="space-y-2"></div>
            </div>

            <div class="flex gap-2 mt-4">
                <button 
                    type="button" 
                    onclick="closeRemarkModal()" 
                    class="flex-1 px-4 py-2 rounded-lg border border-ink-900/15 text-ink-900 font-semibold hover:bg-ink-900/5"
                >
                    Cancel
                </button>
                <button 
                    type="button" 
                    onclick="saveRemark()" 
                    class="flex-1 px-4 py-2 rounded-lg bg-green-500 text-white font-semibold hover:bg-green-600"
                >
                    + Add Remark
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let currentRemarkId = null;
    let currentEditId = null;
    const apiUrl = '/api/leads';

    // ===== EDIT MODAL FUNCTIONS =====
    function openEditModal(id) {
        fetch(`${apiUrl}/${id}/edit`)
        .then(r => r.json())
        .then(res => {
            if (res.locked) {
                alert(res.message);
                return;
            }
            if (res.success) {
                const lead = res.data;
                currentEditId = lead.id;

                document.getElementById('editName').value = lead.name || '';
                document.getElementById('editPhone').value = lead.phone || '';
                document.getElementById('editEmail').value = lead.email || '';
                document.getElementById('editStatus').value = lead.status || 'new';
                document.getElementById('editInquiryType').value = lead.inquiry_type || 'unknown';
                document.getElementById('editCity').value = lead.preferred_city || '';
                document.getElementById('editBudgetMin').value = lead.budget_min || '';
                document.getElementById('editBudgetMax').value = lead.budget_max || '';
                document.getElementById('editNotes').value = lead.notes || '';

                document.getElementById('editModal').classList.remove('hidden');
            }
        })
        .catch(e => alert('Error loading lead: ' + e));
    }

    function closeEditModal() {
        if (currentEditId) {
            fetch(`${apiUrl}/${currentEditId}/release-lock`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            });
        }
        document.getElementById('editModal').classList.add('hidden');
        currentEditId = null;
    }

    document.getElementById('editForm').addEventListener('submit', function (e) {
        e.preventDefault();

        if (!currentEditId) return;

        const formData = new FormData(this);
        const data = Object.fromEntries(formData);

        fetch(`${apiUrl}/${currentEditId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                alert('Lead updated successfully!');
                closeEditModal();
                location.reload();
            } else {
                alert('Error: ' + res.message);
            }
        })
        .catch(e => alert('Error updating lead: ' + e));
    });

    document.getElementById('editModal').addEventListener('click', function (e) {
        if (e.target === this) closeEditModal();
    });

    function openRemarkModal(id) {
        currentRemarkId = id;
        document.getElementById('remarkText').value = '';
        loadRemarks(id);
        document.getElementById('remarkModal').classList.remove('hidden');
    }

    function closeRemarkModal() {
        document.getElementById('remarkModal').classList.add('hidden');
        currentRemarkId = null;
    }

    function loadRemarks(leadId) {
        fetch(`${apiUrl}/${leadId}/remarks`)
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                const container = document.getElementById('remarksContainer');
                container.innerHTML = '';
                
                if(res.data.length === 0) {
                    container.innerHTML = '<p class="text-xs text-ink-900/60">No remarks yet</p>';
                    return;
                }
                
                res.data.forEach(remark => {
                    const date = new Date(remark.created_at).toLocaleDateString();
                    const time = new Date(remark.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    const html = `
                        <div class="text-xs p-2 bg-ink-900/5 rounded border border-ink-900/10">
                            <strong class="text-ink-900 uppercase">${remark.user_type}</strong> 
                            <span class="text-ink-900/60">${date} ${time}</span>
                            <p class="mt-1 text-ink-900">${remark.remark}</p>
                        </div>
                    `;
                    container.innerHTML += html;
                });
            }
        })
        .catch(e => console.error('Error:', e));
    }

    function saveRemark() {
        if(!currentRemarkId) return;
        
        const remark = document.getElementById('remarkText').value.trim();
        if(!remark) {
            alert('Please enter a remark');
            return;
        }

        fetch(`${apiUrl}/${currentRemarkId}/remark`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                remark: remark,
                user_type: 'telecaller'
            })
        })
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                document.getElementById('remarkText').value = '';
                loadRemarks(currentRemarkId);
                alert('Remark added!');
            } else {
                alert('Error: ' + res.message);
            }
        })
        .catch(e => alert('Error: ' + e));
    }

    document.getElementById('remarkModal').addEventListener('click', function(e) {
        if(e.target === this) closeRemarkModal();
    });
</script>

@endsection