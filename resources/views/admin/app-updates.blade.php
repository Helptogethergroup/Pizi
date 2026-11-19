@extends('layouts.dashboard')
@section('title', 'App Update Reminders')
@section('content')

<h1 class="font-display font-black text-3xl mb-1">Owner App Update Reminders</h1>
<p class="text-ink-900/60 text-sm">Every day, owners whose Pizi Owner app is older than the latest version get a WhatsApp message with the Play Store link. It stops by itself for an owner the moment they update and open the app.</p>

@if(session('error'))
    <div class="mt-4 bg-rose-50 border border-rose-200 text-rose-900 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-2 md:grid-cols-5 gap-3 mt-6">
    @foreach([
        ['Owners using the app', $counts['owners_on_app'], 'text-ink-900'],
        ['Out of date (get messages)', $counts['outdated'], 'text-rose-700'],
        ['Already updated', $counts['updated'], 'text-emerald-700'],
        ['Messages sent today', $counts['sent_today'], 'text-ink-900'],
        ['Messages sent in total', $counts['sent_total'], 'text-ink-900'],
    ] as [$label, $value, $color])
        <div class="p-4 rounded-xl bg-white border border-ink-900/10">
            <div class="text-xs text-ink-900/50">{{ $label }}</div>
            <div class="font-display font-black text-2xl mt-1 {{ $color }}">{{ $value }}</div>
        </div>
    @endforeach
</div>

<form method="POST" action="{{ route('admin.app-updates.save') }}" class="mt-8 bg-white rounded-2xl border border-ink-900/10 p-6 space-y-4">
    @csrf
    <label class="flex items-center gap-3">
        <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $enabled)) class="w-5 h-5 rounded">
        <span><span class="font-bold">Send the daily reminders</span><br><span class="text-xs text-ink-900/50">Off means nobody is messaged.</span></span>
    </label>

    <label class="block text-sm">
        <span class="font-bold">Latest version of the owner app</span>
        <span class="block text-xs text-ink-900/50 mb-1">Like <code>1.2.2+24</code> (the version, then + and the build number). Set it only when that version is live on the Play Store. Owners with an older app get the reminder.</span>
        <input name="latest_version" value="{{ old('latest_version', $latest) }}" placeholder="1.2.2+24" class="w-full max-w-xs px-3 py-2 rounded-lg border border-ink-200">
    </label>

    <label class="block text-sm">
        <span class="font-bold">Most messages one owner can ever get</span>
        <span class="block text-xs text-ink-900/50 mb-1">So nobody is messaged for ever if they never update. One message a day at most.</span>
        <input name="max_reminders" type="number" min="1" max="365" value="{{ old('max_reminders', $max) }}" class="w-32 px-3 py-2 rounded-lg border border-ink-200">
    </label>

    <button class="px-5 py-2.5 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold text-sm">Save</button>
</form>

<div class="mt-6 p-4 rounded-xl bg-amber-50 border border-amber-200 text-sm text-amber-900 leading-relaxed">
    How an owner is counted as updated: the app tells the server its version with every request. If an owner's app does not
    tell its version yet (an old app), they count as out of date until they update to a version that does.
    The message uses the WhatsApp template <strong>owner_app_update</strong>, which must be approved on Meta first.
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
    <div class="bg-white rounded-2xl border border-ink-900/10 overflow-hidden">
        <div class="p-5 border-b border-ink-900/10"><h2 class="font-display font-bold text-lg">Out of date (first 25)</h2></div>
        <table class="w-full text-sm">
            <thead class="bg-ink-900/5 text-left text-ink-900/60 text-xs uppercase"><tr><th class="px-5 py-3">Owner</th><th>Their app</th></tr></thead>
            <tbody>
            @forelse($outdated as $o)
                <tr class="border-t border-ink-900/5"><td class="px-5 py-3"><div class="font-semibold">{{ $o->name }}</div><div class="text-xs text-ink-900/50">{{ $o->phone }}</div></td><td class="text-xs">{{ $o->version ?: 'older (does not report)' }}</td></tr>
            @empty
                <tr><td colspan="2" class="px-5 py-8 text-center text-ink-900/50">{{ $latest ? 'Nobody is out of date.' : 'Set the latest version to see who is out of date.' }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-2xl border border-ink-900/10 overflow-hidden">
        <div class="p-5 border-b border-ink-900/10"><h2 class="font-display font-bold text-lg">Last messages sent</h2></div>
        <table class="w-full text-sm">
            <thead class="bg-ink-900/5 text-left text-ink-900/60 text-xs uppercase"><tr><th class="px-5 py-3">Owner</th><th>Had</th><th>When</th></tr></thead>
            <tbody>
            @forelse($recent as $r)
                <tr class="border-t border-ink-900/5"><td class="px-5 py-3"><div class="font-semibold">{{ $r->name }}</div><div class="text-xs text-ink-900/50">{{ $r->phone }}</div></td><td class="text-xs">{{ $r->version_seen ?: '-' }}</td><td class="text-xs">{{ \Illuminate\Support\Carbon::parse($r->sent_at)->format('d M, h:i A') }}</td></tr>
            @empty
                <tr><td colspan="3" class="px-5 py-8 text-center text-ink-900/50">Nothing sent yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
