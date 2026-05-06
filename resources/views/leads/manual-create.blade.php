@extends('layouts.dashboard')
@section('title', 'Add Manual Lead')
@section('content')

<div class="max-w-3xl">
    <div class="flex items-center gap-2 text-sm text-ink-900/60 mb-2">
        @if(auth()->user()->isAdmin())
            <a href="{{ route('admin.leads.index') }}" class="hover:text-coral-600">← Back to leads</a>
        @elseif(auth()->user()->isTeleCaller())
            <a href="{{ route('telecaller.leads.index') }}" class="hover:text-coral-600">← Back to leads</a>
        @endif
    </div>

    <h1 class="font-display font-black text-4xl">+ Add manual lead</h1>
    <p class="text-ink-900/60 mt-2">Walk-in, phone inquiry, referral — capture it here.</p>

    {{-- Duplicate warning --}}
    @if(session('duplicate'))
        @php $dup = session('duplicate'); @endphp
        <div class="mt-6 p-5 rounded-2xl bg-amber-50 border-2 border-amber-300">
            <div class="flex items-start gap-3">
                <span class="text-2xl">⚠️</span>
                <div class="flex-1">
                    <h3 class="font-bold text-amber-900">Possible duplicate detected</h3>
                    <p class="text-sm text-amber-900/80 mt-1">
                        A lead with phone <strong>{{ $dup->phone }}</strong> was added 
                        <strong>{{ $dup->created_at->diffForHumans() }}</strong>
                        @if($dup->createdBy) by <strong>{{ $dup->createdBy->name }}</strong> @endif.
                        Status: <strong>{{ str_replace('_', ' ', $dup->status) }}</strong>.
                    </p>
                    <p class="text-sm text-amber-900/80 mt-2">
                        If this is a different inquiry from the same person, tick "Confirm anyway" below to proceed.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('leads.manual.store') }}" class="mt-6 bg-white p-8 rounded-2xl border border-ink-900/10 space-y-6">
        @csrf

        {{-- Basic info --}}
        <div>
            <h2 class="font-display font-bold text-xl mb-4">Contact details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-semibold text-ink-900/60 uppercase">Name *</label>
                    <input name="name" required value="{{ old('name') }}" placeholder="Full name" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500">
                </div>
                <div>
                    <label class="text-xs font-semibold text-ink-900/60 uppercase">Phone *</label>
                    <input name="phone" required value="{{ old('phone') }}" placeholder="10-digit number" pattern="[0-9]{10,15}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500 font-mono">
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-semibold text-ink-900/60 uppercase">Email</label>
                    <input name="email" type="email" value="{{ old('email') }}" placeholder="optional@example.com" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500">
                </div>
            </div>
        </div>

        <hr class="border-ink-900/5">

        {{-- Lead source --}}
        <div>
            <h2 class="font-display font-bold text-xl mb-4">How did this lead come in?</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                @foreach([
                    'walk_in' => '🚶 Walk-in',
                    'tele_inbound' => '📞 Phone call',
                    'referral' => '🤝 Referral',
                    'offline_campaign' => '📋 Offline event',
                    'manual' => '✏️ Other',
                ] as $val => $label)
                    <label>
                        <input type="radio" name="source" value="{{ $val }}" class="peer hidden" @checked(old('source', 'walk_in') === $val) required>
                        <div class="text-center text-sm py-3 rounded-xl border-2 border-ink-900/15 cursor-pointer peer-checked:bg-ink-900 peer-checked:text-cream peer-checked:border-ink-900">{{ $label }}</div>
                    </label>
                @endforeach
            </div>
        </div>

        <hr class="border-ink-900/5">

        {{-- Property of interest --}}
        <div>
            <h2 class="font-display font-bold text-xl mb-4">Interested in...</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="text-xs font-semibold text-ink-900/60 uppercase">Specific property (optional)</label>
                    <select name="property_id" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
                        <option value="">— No specific property / general inquiry —</option>
                        @foreach($properties as $p)
                            <option value="{{ $p->id }}" @selected(old('property_id') == $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-ink-900/60 uppercase">Preferred city</label>
                    <select name="preferred_city" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
                        <option value="">Any</option>
                        @foreach($cities as $c)
                            <option value="{{ $c->name }}" @selected(old('preferred_city') === $c->name)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-ink-900/60 uppercase">Preferred locality</label>
                    <input name="preferred_locality" value="{{ old('preferred_locality') }}" placeholder="e.g. Sector 62" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
                </div>
            </div>
        </div>

        <hr class="border-ink-900/5">

        {{-- Preferences --}}
        <div>
            <h2 class="font-display font-bold text-xl mb-4">Preferences</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-semibold text-ink-900/60 uppercase">Looking for</label>
                    <select name="preferred_gender" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
                        <option value="">Any</option>
                        <option value="male" @selected(old('preferred_gender') === 'male')>Boys PG</option>
                        <option value="female" @selected(old('preferred_gender') === 'female')>Girls PG</option>
                        <option value="unisex" @selected(old('preferred_gender') === 'unisex')>Unisex / Coliving</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-ink-900/60 uppercase">Move-in date</label>
                    <input name="move_in_date" type="date" value="{{ old('move_in_date') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
                </div>
                <div>
                    <label class="text-xs font-semibold text-ink-900/60 uppercase">Min budget (₹)</label>
                    <input name="budget_min" type="number" min="0" step="500" value="{{ old('budget_min') }}" placeholder="6000" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
                </div>
                <div>
                    <label class="text-xs font-semibold text-ink-900/60 uppercase">Max budget (₹)</label>
                    <input name="budget_max" type="number" min="0" step="500" value="{{ old('budget_max') }}" placeholder="15000" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15">
                </div>
            </div>
        </div>

        <hr class="border-ink-900/5">

        {{-- Notes --}}
        <div>
            <label class="text-xs font-semibold text-ink-900/60 uppercase">Notes / message from lead</label>
            <textarea name="message" rows="3" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500" placeholder="What did the lead say? Any specific requirements?">{{ old('message') }}</textarea>
        </div>

        {{-- Telecaller-only: Mark as verified --}}
        @if(auth()->user()->isTeleCaller() || auth()->user()->isAdmin())
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="mark_as_verified" value="1" class="mt-1 rounded">
                    <div>
                        <div class="font-semibold text-emerald-900">Mark as Verified Lead 🎯</div>
                        <p class="text-sm text-emerald-900/70 mt-1">If you've spoken to this person and qualified them (budget, intent, timeline), tick this box. Verified leads cost owners more credits to unlock — only mark if confident.</p>
                    </div>
                </label>
            </div>
        @endif

        {{-- Confirm duplicate --}}
        @if(session('duplicate'))
            <label class="flex items-start gap-3 p-4 rounded-xl bg-amber-50 border border-amber-200">
                <input type="checkbox" name="confirm_duplicate" value="1" required class="mt-1 rounded">
                <span class="text-sm text-amber-900">I've checked the existing lead above and confirm this is a separate, valid inquiry. Proceed.</span>
            </label>
        @endif

        <div class="flex gap-3 pt-4">
            <button class="px-8 py-4 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold text-lg">
                Save lead →
            </button>
            <button type="reset" class="px-6 py-4 rounded-xl border border-ink-900/15 font-semibold">Reset</button>
        </div>
    </form>
</div>

@endsection