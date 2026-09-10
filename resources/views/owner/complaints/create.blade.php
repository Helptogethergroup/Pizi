@extends('layouts.dashboard')
@section('title', 'New Complaint')
@section('content')

<div class="mb-6">
    <a href="{{ route('owner.complaints.index') }}" class="text-coral-500 font-bold">← Back</a>
    <h1 class="font-display font-black text-3xl mt-2">Register New Complaint</h1>
</div>

<form method="POST" action="{{ route('owner.complaints.store') }}" enctype="multipart/form-data" class="space-y-5 max-w-3xl">
    @csrf

    <div class="bg-white p-6 rounded-2xl border border-ink-100">
        <h2 class="font-display font-bold text-lg mb-4">🏠 Tenant Details</h2>
        <select name="tenant_id" required class="w-full px-4 py-3 rounded-xl border border-ink-200">
            <option value="">— Select tenant —</option>
            @foreach($tenants as $t)
                <option value="{{ $t->id }}" @selected($selectedTenant && $selectedTenant->id == $t->id)>
                    {{ $t->name }} ({{ $t->property?->name }}{{ $t->room_number ? ' - Room '.$t->room_number : '' }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-ink-100">
        <h2 class="font-display font-bold text-lg mb-4">📝 Complaint Details</h2>
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-bold uppercase text-ink-500">Category *</label>
                    <select name="category" required class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
                        <option value="plumbing">🚿 Plumbing</option>
                        <option value="electrical">⚡ Electrical</option>
                        <option value="wifi">📶 WiFi/Internet</option>
                        <option value="housekeeping">🧹 Housekeeping</option>
                        <option value="food">🍱 Food</option>
                        <option value="furniture">🪑 Furniture</option>
                        <option value="security">🔒 Security</option>
                        <option value="ac">❄️ AC</option>
                        <option value="water">💧 Water</option>
                        <option value="other">🔧 Other</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-ink-500">Priority *</label>
                    <select name="priority" required class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
                        <option value="low">🟢 Low</option>
                        <option value="medium" selected>🟡 Medium</option>
                        <option value="high">🟠 High</option>
                        <option value="urgent">🔴 Urgent</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="text-xs font-bold uppercase text-ink-500">Title *</label>
                <input name="title" required maxlength="200" placeholder="e.g. Bathroom tap leaking" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200">
            </div>

            <div>
                <label class="text-xs font-bold uppercase text-ink-500">Description</label>
                <textarea name="description" rows="4" placeholder="Detailed description of the issue..." class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200"></textarea>
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-ink-100">
        <h2 class="font-display font-bold text-lg mb-4">📸 Photos / Videos (Optional)</h2>
        <input type="file" name="media[]" multiple accept="image/*,video/mp4" class="w-full text-sm">
        <p class="text-xs text-ink-500 mt-2">Upload photos/videos of the issue (max 10 MB each).</p>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="px-8 py-4 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold text-lg shadow-lg shadow-coral-500/30">✓ Register Complaint</button>
        <a href="{{ route('owner.complaints.index') }}" class="px-8 py-4 border border-ink-200 rounded-xl font-bold">Cancel</a>
    </div>
</form>

@endsection