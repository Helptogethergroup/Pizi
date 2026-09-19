@extends('layouts.dashboard')
@section('title', $ownerProspect->name . ' — Owner Prospect')
@section('content')

<a href="{{ route('telecaller.owner-prospects.index') }}" class="text-sm text-ink-900/60">← Back to owner prospects</a>

<div class="flex items-center justify-between flex-wrap gap-3 mt-2 mb-6">
    <div>
        <h1 class="font-display font-black text-3xl">{{ $ownerProspect->name }}</h1>
        <p class="text-ink-900/60"><i class="fa-solid fa-phone fa-fw"></i> {{ $ownerProspect->phone }}</p>
    </div>
    <span class="px-3 py-1.5 rounded-full text-sm font-bold {{ $ownerProspect->stageBadge() }}">{{ $ownerProspect->stageLabel() }}</span>
</div>

@if(session('success'))
    <div class="mb-6 bg-emerald-50 border-l-4 border-emerald-500 px-4 py-3 rounded text-emerald-700 text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- CALL SECTION --}}
    <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
        <h2 class="font-display font-bold text-lg mb-4"><i class="fa-solid fa-phone fa-fw"></i> Call outcome</h2>

        @if($ownerProspect->called_at)
            <div class="bg-cream rounded-xl p-3 mb-3 text-sm">
                Last call: <strong>{{ ucfirst(str_replace('_',' ',$ownerProspect->call_status)) }}</strong>
                · {{ $ownerProspect->called_at->diffForHumans() }}
                @if($ownerProspect->call_attempts)<div class="text-xs text-ink-900/50 mt-1">Attempts: {{ $ownerProspect->call_attempts }}</div>@endif
            </div>
        @endif

        <a href="tel:{{ $ownerProspect->phone }}" class="block text-center py-3 mb-4 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-bold text-sm">
            <i class="fa-solid fa-mobile-screen fa-fw"></i> Call {{ $ownerProspect->phone }}
        </a>

        <form method="POST" action="{{ route('telecaller.owner-prospects.call', $ownerProspect) }}" class="space-y-3">
            @csrf
            <textarea name="notes" rows="2" placeholder="What did the owner say..." class="w-full px-3 py-2.5 rounded-xl border border-ink-900/15 text-sm">{{ $ownerProspect->notes }}</textarea>

            <div class="grid grid-cols-2 gap-2">
                <button type="submit" name="call_status" value="attended" class="py-3 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-bold text-sm"><i class="fa-solid fa-circle-check fa-fw"></i> Attended</button>
                <button type="submit" name="call_status" value="no_answer" class="py-3 bg-amber-100 hover:bg-amber-200 text-amber-800 rounded-xl font-bold text-sm border border-amber-300"><i class="fa-solid fa-phone-slash fa-fw"></i> No Answer</button>
            </div>

            <div id="rejectWrap" class="hidden bg-rose-50 border border-rose-200 rounded-xl p-3">
                <label class="text-xs font-bold uppercase text-rose-700">Reason</label>
                <select name="rejection_reason" id="rejectReason" class="w-full mt-1 px-3 py-2.5 rounded-xl border border-rose-300 text-sm">
                    <option value="">— Select reason —</option>
                    <option value="not_interested_in_platform">Not interested in listing online</option>
                    <option value="already_on_other_platform">Already on another platform</option>
                    <option value="no_vacant_rooms">No vacant rooms currently</option>
                    <option value="pricing_concern">Concerned about pricing/commission</option>
                    <option value="other">Other</option>
                </select>
                <input type="hidden" name="call_status" id="rejectCallStatus" value="">
                <button type="submit" id="confirmRejectBtn" disabled class="w-full mt-3 py-2.5 bg-rose-600 text-white rounded-xl font-bold text-sm disabled:opacity-40">Confirm — Not Interested</button>
            </div>

            <button type="button" id="notInterestedBtn" class="w-full py-3 bg-white hover:bg-rose-50 text-rose-700 rounded-xl font-bold text-sm border border-rose-300">✗ Not Interested</button>
        </form>

        <script>
            (function () {
                const openBtn = document.getElementById('notInterestedBtn');
                const wrap = document.getElementById('rejectWrap');
                const select = document.getElementById('rejectReason');
                const hidden = document.getElementById('rejectCallStatus');
                const confirmBtn = document.getElementById('confirmRejectBtn');

                openBtn?.addEventListener('click', function () {
                    wrap.classList.remove('hidden');
                    openBtn.classList.add('hidden');
                });
                select?.addEventListener('change', function () {
                    confirmBtn.disabled = !select.value;
                    hidden.value = select.value ? 'not_interested' : '';
                });
            })();
        </script>
    </div>

    {{-- CONVERSION PIPELINE --}}
    <div class="bg-white p-6 rounded-2xl border border-ink-900/10">
        <h2 class="font-display font-bold text-lg mb-4"><i class="fa-solid fa-chart-line fa-fw"></i> Conversion pipeline</h2>
        <p class="text-xs text-ink-900/50 mb-4">Owner khud jaake ye steps karega Pizi par — jab wo bata de (call/WhatsApp par), yahan manually mark kar do.</p>

        @if($matchedOwner)
            <div class="bg-sky-50 border border-sky-200 rounded-xl p-3 mb-4 text-xs text-sky-800">
                <i class="fa-solid fa-lightbulb fa-fw"></i> Is phone number se ek owner account **already exists** on Pizi: <strong>{{ $matchedOwner->name }}</strong>
                ({{ $propertyCount }} propert{{ $propertyCount == 1 ? 'y' : 'ies' }} listed{{ $hasPaidPlan ? ', paid plan active' : '' }})
            </div>
        @endif

        <div class="space-y-3">
            <div class="flex items-center justify-between p-3 rounded-xl {{ $ownerProspect->registered_at ? 'bg-sky-50 border border-sky-200' : 'bg-ink-50' }}">
                <div>
                    <div class="font-semibold text-sm"><i class="fa-solid fa-circle-check fa-fw"></i> Registered (free)</div>
                    @if($ownerProspect->registered_at)<div class="text-xs text-ink-900/50">{{ $ownerProspect->registered_at->diffForHumans() }}</div>@endif
                </div>
                <form method="POST" action="{{ route('telecaller.owner-prospects.stage', $ownerProspect) }}">
                    @csrf
                    <input type="hidden" name="stage" value="{{ $ownerProspect->registered_at ? 'undo_registered' : 'registered' }}">
                    <button class="px-3 py-1.5 rounded-lg text-xs font-bold {{ $ownerProspect->registered_at ? 'bg-ink-100 text-ink-700' : 'bg-sky-500 text-white' }}">
                        {{ $ownerProspect->registered_at ? 'Undo' : 'Mark done' }}
                    </button>
                </form>
            </div>

            <div class="flex items-center justify-between p-3 rounded-xl {{ $ownerProspect->property_listed_at ? 'bg-emerald-50 border border-emerald-200' : 'bg-ink-50' }}">
                <div>
                    <div class="font-semibold text-sm"><i class="fa-solid fa-house fa-fw"></i> Property listed</div>
                    @if($ownerProspect->property_listed_at)<div class="text-xs text-ink-900/50">{{ $ownerProspect->property_listed_at->diffForHumans() }}</div>@endif
                </div>
                <form method="POST" action="{{ route('telecaller.owner-prospects.stage', $ownerProspect) }}">
                    @csrf
                    <input type="hidden" name="stage" value="{{ $ownerProspect->property_listed_at ? 'undo_property_listed' : 'property_listed' }}">
                    <button class="px-3 py-1.5 rounded-lg text-xs font-bold {{ $ownerProspect->property_listed_at ? 'bg-ink-100 text-ink-700' : 'bg-emerald-500 text-white' }}">
                        {{ $ownerProspect->property_listed_at ? 'Undo' : 'Mark done' }}
                    </button>
                </form>
            </div>

            <div class="flex items-center justify-between p-3 rounded-xl {{ $ownerProspect->paid_plan_at ? 'bg-green-50 border border-green-200' : 'bg-ink-50' }}">
                <div>
                    <div class="font-semibold text-sm"><i class="fa-solid fa-credit-card fa-fw"></i> Paid plan bought</div>
                    @if($ownerProspect->paid_plan_at)<div class="text-xs text-ink-900/50">{{ $ownerProspect->paid_plan_at->diffForHumans() }}</div>@endif
                </div>
                <form method="POST" action="{{ route('telecaller.owner-prospects.stage', $ownerProspect) }}">
                    @csrf
                    <input type="hidden" name="stage" value="{{ $ownerProspect->paid_plan_at ? 'undo_paid_plan' : 'paid_plan' }}">
                    <button class="px-3 py-1.5 rounded-lg text-xs font-bold {{ $ownerProspect->paid_plan_at ? 'bg-ink-100 text-ink-700' : 'bg-green-600 text-white' }}">
                        {{ $ownerProspect->paid_plan_at ? 'Undo' : 'Mark done' }}
                    </button>
                </form>
            </div>
        </div>

        @if($ownerProspect->notes)
            <div class="mt-4 pt-4 border-t border-ink-100 text-xs text-ink-900/60">
                <strong>Notes:</strong> {{ $ownerProspect->notes }}
            </div>
        @endif
    </div>
</div>

@endsection
