@extends('layouts.tenant')
@section('title', 'New Complaint')
@section('content')

<a href="{{ route('tenant.complaints.index') }}" class="text-coral-600 text-sm font-bold">← Back to Complaints</a>
<h1 class="font-display font-black text-3xl text-ink-950 mt-2">Raise New Complaint</h1>
<p class="text-ink-900/60 mt-1">Owner ko notify ho jayega — jaldi action lenge</p>

<form method="POST" action="{{ route('tenant.complaints.store') }}" class="mt-6 bg-white rounded-2xl border border-ink-900/10 p-6 space-y-4 max-w-2xl">
    @csrf

    <div>
        <label class="text-xs font-bold uppercase text-ink-900/60">Category *</label>
        <select name="category" required class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none">
            <option value="">— Select category —</option>
            <option value="electricity">Electricity</option>
            <option value="water">Water</option>
            <option value="plumbing">Plumbing</option>
            <option value="wifi">WiFi / Internet</option>
            <option value="food">Food</option>
            <option value="cleaning">Cleaning / Housekeeping</option>
            <option value="security">Security</option>
            <option value="ac">AC / Cooling</option>
            <option value="furniture">Furniture / Damage</option>
            <option value="other">Other</option>
        </select>
    </div>

    <div>
        <label class="text-xs font-bold uppercase text-ink-900/60">Title *</label>
        <input name="title" required value="{{ old('title') }}" placeholder="e.g. Geyser not working in bathroom" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none">
    </div>

    <div>
        <label class="text-xs font-bold uppercase text-ink-900/60">Description *</label>
        <textarea name="description" required rows="5" placeholder="Detail me batao kya issue hai..." class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none">{{ old('description') }}</textarea>
    </div>

    <div>
        <label class="text-xs font-bold uppercase text-ink-900/60">Priority</label>
        <div class="grid grid-cols-4 gap-2 mt-2">
            @foreach(['low' => '🟢 Low', 'medium' => '🟡 Medium', 'high' => '🟠 High', 'urgent' => '🔴 Urgent'] as $val => $label)
                <label class="border-2 border-ink-900/10 hover:border-coral-500 rounded-xl p-3 text-center cursor-pointer text-sm font-bold has-[:checked]:border-coral-500 has-[:checked]:bg-coral-50">
                    <input type="radio" name="priority" value="{{ $val }}" {{ $val === 'medium' ? 'checked' : '' }} class="sr-only">
                    {{ $label }}
                </label>
            @endforeach
        </div>
    </div>

    <button type="submit" class="px-8 py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold">
        <i class="fa-solid fa-rocket fa-fw"></i> Submit Complaint
    </button>
</form>

@endsection