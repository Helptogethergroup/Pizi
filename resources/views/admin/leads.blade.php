@extends('layouts.dashboard')
@section('title', 'Leads — Admin')
@section('content')

<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
     <div class="flex items-center gap-3">
        <h1 class="font-display font-black text-3xl">All leads</h1>
        <a href="{{ route('leads.manual.create') }}" class="px-4 py-2 bg-coral-500 text-white rounded-lg font-semibold text-sm">+ Add Manual</a>
        <a href="{{ route('admin.leads.export', request()->query()) }}" class="px-4 py-2 bg-emerald-600 text-white rounded-lg font-semibold text-sm"><i class="fa-solid fa-inbox fa-fw"></i> Export CSV</a>
    </div>
    <form class="flex gap-2 flex-wrap items-center">
        <input name="search" value="{{ request('search') }}" placeholder="Name / phone…" class="px-3 py-2 rounded-lg border border-ink-900/15">
        <select name="status" class="px-3 py-2 rounded-lg border border-ink-900/15">
            <option value="">All status</option>
            @foreach(['new','contacted','interested','follow_up','visit_scheduled','visit_done','closed_won','closed_lost','junk'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ str_replace('_',' ',$s) }}</option>
            @endforeach
        </select>
        <select name="lead_type" class="px-3 py-2 rounded-lg border border-ink-900/15">
            <option value="">All Types</option>
            <option value="verified" @selected(request('lead_type') == 'verified')>Verified</option>
            <option value="manual" @selected(request('lead_type') == 'manual')>Direct</option>
        </select>
        <select name="inquiry_type" class="px-3 py-2 rounded-lg border border-ink-900/15">
            <option value="">All Inquiries</option>
            <option value="tenant" @selected(request('inquiry_type') == 'tenant')>Tenant</option>
            <option value="owner" @selected(request('inquiry_type') == 'owner')>Owner</option>
            <option value="unknown" @selected(request('inquiry_type') == 'unknown')>Unknown</option>
        </select>
        <select name="source" class="px-3 py-2 rounded-lg border border-ink-900/15">
            <option value="">All Sources</option>
            @foreach($sourceCounts as $src => $cnt)
                <option value="{{ $src }}" @selected(request('source') === $src)>{{ $src }} ({{ $cnt }})</option>
            @endforeach
        </select>
        <select name="per_page" class="px-3 py-2 rounded-lg border border-ink-900/15">
            @foreach([25, 50, 100] as $pp)
                <option value="{{ $pp }}" @selected($perPage == $pp)>{{ $pp }} / page</option>
            @endforeach
        </select>
        <label class="flex items-center gap-1.5 text-sm px-2">
            <input type="checkbox" name="duplicates_only" value="1" @checked(request()->boolean('duplicates_only'))>
            <i class="fa-solid fa-repeat fa-fw"></i> Duplicates only
        </label>
        <button class="px-4 py-2 bg-ink-900 text-cream rounded-lg">Filter</button>
    </form>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <a href="{{ route('admin.leads.index', array_merge(request()->except('page'), ['inquiry_type' => 'tenant'])) }}"
       class="rounded-2xl border-2 p-5 flex items-center justify-between transition {{ request('inquiry_type') === 'tenant' ? 'border-coral-500 bg-coral-50' : 'border-ink-900/10 bg-white hover:border-coral-300' }}">
        <div>
            <div class="text-xs uppercase font-bold text-ink-900/50">Looking for a PG</div>
            <div class="font-display font-black text-2xl"><i class="fa-solid fa-suitcase-rolling fa-fw"></i> Tenant Leads</div>
        </div>
        <div class="text-3xl font-black text-coral-500">{{ $tenantCount }}</div>
    </a>
    <a href="{{ route('admin.leads.index', array_merge(request()->except('page'), ['inquiry_type' => 'owner'])) }}"
       class="rounded-2xl border-2 p-5 flex items-center justify-between transition {{ request('inquiry_type') === 'owner' ? 'border-coral-500 bg-coral-50' : 'border-ink-900/10 bg-white hover:border-coral-300' }}">
        <div>
            <div class="text-xs uppercase font-bold text-ink-900/50">Wants to list their PG</div>
            <div class="font-display font-black text-2xl"><i class="fa-solid fa-house fa-fw"></i> Owner Leads</div>
        </div>
        <div class="text-3xl font-black text-coral-500">{{ $ownerCount }}</div>
    </a>
</div>

@if(request()->filled('inquiry_type'))
    <div class="mb-4">
        <a href="{{ route('admin.leads.index', request()->except(['inquiry_type', 'page'])) }}" class="text-sm text-ink-900/60 hover:text-ink-900">← Show all leads (Tenant + Owner)</a>
    </div>
@endif

<div id="bulkBar" class="hidden mb-3 p-3 rounded-xl bg-ink-900 text-cream flex items-center gap-3 flex-wrap text-sm">
    <span id="bulkCount" class="font-semibold">0 selected</span>
    <select id="bulkTelecaller" class="px-2 py-1.5 rounded-lg text-ink-900 text-sm">
        <option value="">Assign to…</option>
        @foreach($telecallers as $tc)
            <option value="{{ $tc->id }}">{{ $tc->name }}</option>
        @endforeach
    </select>
    <button type="button" onclick="submitBulk('{{ route('admin.leads.bulk-assign') }}', true)" class="px-3 py-1.5 rounded-lg bg-blue-500 font-semibold">Assign</button>
    <button type="button" onclick="submitBulk('{{ route('admin.leads.bulk-verify') }}')" class="px-3 py-1.5 rounded-lg bg-emerald-500 font-semibold">✓ Verify</button>
    <button type="button" onclick="submitBulk('{{ route('admin.leads.bulk-junk') }}')" class="px-3 py-1.5 rounded-lg bg-amber-500 font-semibold"><i class="fa-solid fa-flag fa-fw"></i> Junk</button>
    <button type="button" onclick="if(confirm('Delete selected leads? This cannot be undone.')) submitBulk('{{ route('admin.leads.bulk-delete') }}')" class="px-3 py-1.5 rounded-lg bg-red-500 font-semibold"><i class="fa-solid fa-trash-can fa-fw"></i> Delete</button>
</div>

<form id="bulkForm" method="POST">
    @csrf
</form>

<div class="bg-white rounded-2xl border border-ink-900/10 overflow-hidden">
    <table class="w-full text-sm border-collapse">
        <thead class="bg-ink-900/5 text-left text-ink-900/60 text-xs uppercase">
            <tr>
                <th class="px-3 py-3 border border-ink-900/10"><input type="checkbox" onclick="toggleAll(this)"></th>
                <th class="px-4 py-3 border border-ink-900/10">Lead</th>
                <th class="px-3 py-3 border border-ink-900/10">Type</th>
                <th class="px-3 py-3 border border-ink-900/10">Property / City</th>
                <th class="px-3 py-3 border border-ink-900/10">
                    <a href="{{ route('admin.leads.index', array_merge(request()->except('page'), ['sort' => 'status', 'dir' => $sort === 'status' && $dir === 'asc' ? 'desc' : 'asc'])) }}" class="hover:text-ink-900">Status {{ $sort === 'status' ? ($dir === 'asc' ? '↑' : '↓') : '' }}</a>
                </th>
                <th class="px-3 py-3 border border-ink-900/10">Lock</th>
                <th class="px-3 py-3 border border-ink-900/10">Assigned to</th>
                <th class="px-3 py-3 border border-ink-900/10">
                    <a href="{{ route('admin.leads.index', array_merge(request()->except('page'), ['sort' => 'created_at', 'dir' => $sort === 'created_at' && $dir === 'asc' ? 'desc' : 'asc'])) }}" class="hover:text-ink-900">When {{ $sort === 'created_at' ? ($dir === 'asc' ? '↑' : '↓') : '' }}</a>
                </th>
                <th class="px-3 py-3 border border-ink-900/10">Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($leads as $lead)
            <tr class="hover:bg-ink-900/2 {{ $lead->is_duplicate_phone ? 'bg-amber-50' : '' }}">
                <td class="px-3 py-3 border border-ink-900/10">
                    <input type="checkbox" class="lead-checkbox" value="{{ $lead->id }}" onchange="updateBulkBar()">
                </td>
                <td class="px-4 py-3 border border-ink-900/10">
                    <div class="font-semibold">{{ $lead->name }}</div>
                    <div class="text-xs text-ink-900/50 mt-1">
                        {{ $lead->phone }}
                        @if($lead->is_duplicate_phone)
                            <span class="ml-1 px-1.5 py-0.5 rounded bg-amber-200 text-amber-900 text-[10px] font-bold"><i class="fa-solid fa-repeat fa-fw"></i> DUPLICATE</span>
                        @endif
                    </div>
                    @php $srcBadge = $lead->sourceBadge(); @endphp
                    <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-[11px] font-semibold whitespace-nowrap {{ $srcBadge['class'] }}">{{ $srcBadge['label'] }}</span>
                </td>
                <td class="px-3 py-3 border border-ink-900/10 text-xs">
                    @php $badge = $lead->inquiryTypeBadge(); @endphp
                    <span class="px-2 py-1 rounded-full text-xs font-semibold whitespace-nowrap {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                </td>
                <td class="px-3 py-3 border border-ink-900/10 text-xs">
                    {{ $lead->property?->name ?? 'General inquiry' }}
                    <div class="text-ink-900/50"><i class="fa-solid fa-location-dot fa-fw"></i> {{ $lead->display_location }}</div>
                </td>
                <td class="px-3 py-3 border border-ink-900/10 text-xs">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold whitespace-nowrap bg-ink-900/5 text-ink-900/70">{{ str_replace('_', ' ', $lead->status ?? 'new') }}</span>
                </td>
               <td class="px-3 py-3 border border-ink-900/10 text-xs">
                    @if($lead->is_locked && $lead->locked_by_user_id)
                        @php $claimedAt = $lead->unlocks->firstWhere('user_id', $lead->locked_by_user_id)?->created_at; @endphp
                        <span class="text-green-600 font-semibold block"><i class="fa-solid fa-lock-open fa-fw"></i> Claimed</span>
                        <span class="text-ink-900/60 block">{{ $lead->lockedBy?->name ?? 'Owner #' . $lead->locked_by_user_id }}</span>
                        @if($claimedAt)
                            <span class="text-ink-900/40 block">{{ $claimedAt->format('d M, h:i A') }}</span>
                        @endif
                    @elseif($lead->edit_locked_by && $lead->edit_locked_at && \Carbon\Carbon::parse($lead->edit_locked_at)->diffInMinutes(now()) <= 15)
                        <span class="text-red-600 font-semibold block"><i class="fa-solid fa-pencil fa-fw"></i> Being edited</span>
                        <span class="text-ink-900/50 block">{{ optional(\App\Models\User::find($lead->edit_locked_by))->name ?? 'Someone' }}</span>
                    @endif
                </td>
                <td class="px-3 py-3 border border-ink-900/10 text-xs">{{ $lead->telecaller?->name ?? '—' }}</td>
                <td class="px-3 py-3 border border-ink-900/10 text-xs text-ink-900/60">
                    {{ $lead->created_at->diffForHumans() }}
                    <div class="text-ink-900/40">{{ $lead->created_at->format('D, d M Y · h:i A') }}</div>
                </td>
                <td class="px-4 py-3 border border-ink-900/10">
                    <div class="flex gap-1 items-center flex-wrap">
                        <button 
                            onclick="openEditModal({{ $lead->id }})" 
                            class="px-2 py-1 bg-blue-500 text-white text-xs rounded font-semibold hover:bg-blue-600"
                        >
                            <i class="fa-solid fa-pencil fa-fw"></i> Edit
                        </button>

                        <button 
                            onclick="openRemarkModal({{ $lead->id }})" 
                            class="px-2 py-1 bg-purple-500 text-white text-xs rounded font-semibold hover:bg-purple-600"
                        >
                            <i class="fa-solid fa-comment-dots fa-fw"></i> Remark
                        </button>

                        <form method="POST" action="{{ route('admin.leads.assign', $lead) }}" class="flex gap-1">
                            @csrf @method('PATCH')
                            <select name="telecaller_id" class="text-xs px-2 py-1 rounded border border-ink-900/15">
                                <option value="">Assign…</option>
                                @foreach($telecallers as $tc)
                                    @if(in_array($tc->lead_specialization, ['both', $lead->inquiry_type]))
                                        <option value="{{ $tc->id }}" @selected($lead->assigned_telecaller_id === $tc->id)>{{ $tc->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <button class="text-xs px-2 py-1 rounded bg-ink-900 text-cream">Set</button>
                        </form>
                        

                        @if($lead->lead_type !== 'verified')
                        <form method="POST" action="{{ route('admin.leads.verify', $lead) }}" class="inline">
                            @csrf @method('PATCH')
                            <button class="px-2 py-1 bg-emerald-500 text-white text-xs rounded font-semibold hover:bg-emerald-600">
                                ✓ Verify
                            </button>
                        </form>
                    @else
                        <span class="px-2 py-1 bg-emerald-100 text-emerald-700 text-xs rounded font-semibold">✓ Verified</span>
                        @endif

                        @if($lead->status !== 'junk')
                        <form method="POST" action="{{ route('admin.leads.junk', $lead) }}" class="inline">
                            @csrf @method('PATCH')
                            <button class="px-2 py-1 bg-amber-500 text-white text-xs rounded font-semibold hover:bg-amber-600">
                                <i class="fa-solid fa-flag fa-fw"></i> Junk
                            </button>
                        </form>
                        @endif

                        <form method="POST" action="{{ route('admin.leads.destroy', $lead) }}" class="inline" onsubmit="return confirm('Delete this lead? This cannot be undone.')">
                            @csrf @method('DELETE')
                            <button class="px-2 py-1 bg-red-500 text-white text-xs rounded font-semibold hover:bg-red-600">
                                <i class="fa-solid fa-trash-can fa-fw"></i> Delete
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="mt-6">{{ $leads->links() }}</div>

<!-- ===== EDIT MODAL ===== -->
<div id="editModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 w-96 max-h-96 overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-display font-black text-2xl">Edit Lead</h2>
            <button onclick="closeEditModal()" class="text-2xl text-ink-900/50 hover:text-ink-900">×</button>
        </div>

        <div id="editClaimBanner" class="hidden mb-3 px-3 py-2 rounded-lg bg-green-50 border border-green-200 text-xs text-green-800"></div>

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
                    <option value="tenant">Tenant — looking for a PG</option>
                    <option value="owner">Owner — wants to list a PG</option>
                    <option value="unknown">Unknown</option>
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
    let currentEditId = null;
    let currentRemarkId = null;
    const apiUrl = '/api/leads';

    // ===== BULK ACTIONS =====
    function toggleAll(source) {
        document.querySelectorAll('.lead-checkbox').forEach(cb => cb.checked = source.checked);
        updateBulkBar();
    }

    function updateBulkBar() {
        const checked = document.querySelectorAll('.lead-checkbox:checked');
        document.getElementById('bulkBar').classList.toggle('hidden', checked.length === 0);
        document.getElementById('bulkCount').textContent = checked.length + ' selected';
    }

    function submitBulk(url, needsTelecaller = false) {
        const checked = [...document.querySelectorAll('.lead-checkbox:checked')].map(cb => cb.value);
        if (checked.length === 0) return;

        if (needsTelecaller) {
            const tc = document.getElementById('bulkTelecaller').value;
            if (!tc) { alert('Pick a telecaller to assign to first.'); return; }
            var hidden = `<input type="hidden" name="telecaller_id" value="${tc}">`;
        } else {
            var hidden = '';
        }

        const form = document.getElementById('bulkForm');
        form.action = url;
        form.innerHTML = document.querySelector('#bulkForm input[name="_token"]').outerHTML + hidden
            + checked.map(id => `<input type="hidden" name="lead_ids[]" value="${id}">`).join('');
        form.submit();
    }

    // ===== EDIT MODAL FUNCTIONS =====
   function openEditModal(id) {
        fetch(`${apiUrl}/${id}/edit`)
        .then(r => r.json())
        .then(res => {
            if(res.locked) {
                alert(res.message);
                return;
            }
            if(res.success) {
                const lead = res.data;
                currentEditId = lead.id;

                const banner = document.getElementById('editClaimBanner');
                if (res.claim) {
                    banner.textContent = '🔓 Claimed by ' + (res.claim.owner_name || 'an owner') + (res.claim.claimed_at ? ' on ' + res.claim.claimed_at : '') + ' — editing as admin.';
                    banner.classList.remove('hidden');
                } else {
                    banner.classList.add('hidden');
                }

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

    document.getElementById('editForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if(!currentEditId) return;

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
            if(res.success) {
                alert('Lead updated successfully!');
                closeEditModal();
                location.reload();
            } else {
                alert('Error: ' + res.message);
            }
        })
        .catch(e => alert('Error updating lead: ' + e));
    });

    // ===== REMARK MODAL FUNCTIONS =====
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
        .catch(e => alert('Error loading remarks: ' + e));
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
                user_type: 'admin'
            })
        })
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                document.getElementById('remarkText').value = '';
                loadRemarks(currentRemarkId);
                alert('Remark added successfully!');
            } else {
                alert('Error: ' + res.message);
            }
        })
        .catch(e => alert('Error saving remark: ' + e));
    }

    // ===== CLOSE MODALS ON OUTSIDE CLICK =====
    document.getElementById('editModal').addEventListener('click', function(e) {
        if(e.target === this) closeEditModal();
    });

    document.getElementById('remarkModal').addEventListener('click', function(e) {
        if(e.target === this) closeRemarkModal();
    });
</script>

@endsection