@extends('layouts.dashboard')
@section('title', $tenant->name)
@section('content')

<div class="mb-6">
    <a href="{{ route('owner.tenants.index') }}" class="text-coral-500 font-bold">← Back to tenants</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 space-y-6">

        <div class="bg-white p-6 rounded-2xl border border-ink-100">
            <div class="flex items-start gap-4 flex-wrap">
                <div class="w-16 h-16 rounded-full bg-coral-100 text-coral-700 flex items-center justify-center font-bold text-2xl">
                    {{ strtoupper(substr($tenant->name, 0, 1)) }}
                </div>
                <div class="flex-1">
                    <h1 class="font-display font-black text-2xl">{{ $tenant->name }}</h1>
                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                        @if($tenant->status === 'active')
                            <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-bold">Active</span>
                        @endif
                        @if($tenant->kyc_status === 'approved')
                            <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-bold">✓ KYC Verified</span>
                        @elseif($tenant->kyc_status === 'submitted')
                            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-bold"><i class="fa-solid fa-clipboard-list fa-fw"></i> KYC Under Review</span>
                        @elseif($tenant->kyc_status === 'rejected')
                            <span class="text-xs bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full font-bold"><i class="fa-solid fa-circle-xmark fa-fw"></i> KYC Rejected</span>
                        @else
                            <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-bold"><i class="fa-solid fa-hourglass-half fa-fw"></i> KYC Pending</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-3 text-sm text-ink-700 mt-2 flex-wrap">
                        <span><i class="fa-solid fa-phone fa-fw"></i> {{ $tenant->phone }}</span>
                        @if($tenant->email)<span><i class="fa-solid fa-envelope fa-fw"></i> {{ $tenant->email }}</span>@endif
                    </div>
                </div>
                <a href="{{ route('owner.tenants.edit', $tenant) }}" class="px-4 py-2 bg-ink-950 text-cream rounded-xl text-sm font-bold"><i class="fa-solid fa-pencil fa-fw"></i> Edit Details</a>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-5 pt-5 border-t border-ink-100">
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">Property</div>
                    <div class="font-bold text-sm">{{ $tenant->property?->name }}</div>
                </div>
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">Room/Bed</div>
                    <div class="font-bold">{{ $tenant->room_number }}{{ $tenant->bed_number ? ' / '.$tenant->bed_number : '' }}</div>
                </div>
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">Monthly Rent</div>
                    <div class="font-bold text-coral-600">₹{{ number_format($tenant->monthly_rent) }}</div>
                </div>
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">Move-in</div>
                    <div class="font-bold">{{ $tenant->move_in_date?->format('d M Y') ?? '—' }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-ink-100">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                <h2 class="font-display font-bold text-xl"><i class="fa-solid fa-file-lines fa-fw"></i> KYC Documents</h2>
                <div class="text-sm font-bold text-emerald-700">{{ $tenant->kyc_progress }}% Complete</div>
            </div>

            @if($tenant->kyc_status === 'approved' && $tenant->documents->count() === 0)
                <div class="mb-5 p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-start gap-2">
                    <span class="text-lg">✓</span>
                    <div class="text-sm text-emerald-900">
                        <strong>KYC already approved</strong> — verified in person by the owner at move-in.
                        <span class="block text-emerald-700 text-xs mt-0.5">Document upload below is optional, for keeping a permanent digital record.</span>
                    </div>
                </div>
            @endif

            <div class="w-full bg-ink-100 rounded-full h-2 mb-5">
                <div class="bg-emerald-500 h-2 rounded-full transition-all" style="width: {{ $tenant->kyc_progress }}%"></div>
            </div>

            <form method="POST" action="{{ route('owner.tenants.documents.upload', $tenant) }}" enctype="multipart/form-data" class="space-y-3 mb-5 p-4 bg-cream rounded-xl">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <select name="document_type" required class="px-4 py-2.5 rounded-lg border border-ink-200 text-sm">
                        <option value="">— Select document type —</option>
                        <option value="aadhaar_front">Aadhaar (Front)</option>
                        <option value="aadhaar_back">Aadhaar (Back)</option>
                        <option value="pan">PAN Card</option>
                        <option value="employment_id">Employment ID</option>
                        <option value="student_id">Student ID</option>
                        <option value="address_proof">Address Proof</option>
                        <option value="photo">Photo</option>
                        <option value="other">Other</option>
                    </select>
                    <input name="document_number" placeholder="Document number (optional)" class="px-4 py-2.5 rounded-lg border border-ink-200 text-sm">
                </div>
                <input type="file" name="file" required accept="image/*,.pdf" class="w-full text-sm">
                <button class="px-5 py-2.5 bg-coral-500 hover:bg-coral-600 text-white rounded-lg text-sm font-bold"><i class="fa-solid fa-arrow-up-from-bracket fa-fw"></i> Upload Document</button>
            </form>

            @if($tenant->documents->count())
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($tenant->documents as $doc)
                        <div class="rounded-xl border border-ink-100 overflow-hidden">
                            @if(str_ends_with(strtolower($doc->file_path), '.pdf'))
                                <div class="aspect-square bg-rose-50 flex items-center justify-center text-4xl"><i class="fa-solid fa-file-lines fa-fw"></i></div>
                            @else
                                <a href="{{ $doc->url }}" target="_blank">
                                    <img src="{{ $doc->url }}" class="w-full aspect-square object-cover">
                                </a>
                            @endif
                            <div class="p-2">
                                <div class="text-xs font-bold">{{ $doc->type_label }}</div>
                                @if($doc->document_number)<div class="text-xs text-ink-500">{{ $doc->document_number }}</div>@endif
                                <div class="flex gap-1 mt-2">
                                    <a href="{{ $doc->url }}" target="_blank" class="flex-1 px-2 py-1 bg-blue-500 text-white rounded text-xs text-center font-bold">View</a>
                                    <form method="POST" action="{{ route('owner.tenants.documents.delete', $doc) }}" onsubmit="return confirm('Delete this document?')" class="inline-block">
                                        @csrf @method('DELETE')
                                        <button class="px-2 py-1 bg-rose-500 text-white rounded text-xs font-bold"><i class="fa-solid fa-trash-can fa-fw"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-ink-500 text-center py-4">No documents uploaded yet.</p>
            @endif

            @if($tenant->kyc_status === 'submitted' && $tenant->documents->count() > 0)
                <div class="mt-5 pt-5 border-t border-ink-100 flex gap-2 flex-wrap">
                    <form method="POST" action="{{ route('owner.tenants.kyc.approve', $tenant) }}" class="inline-block">
                        @csrf @method('PATCH')
                        <button class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg text-sm font-bold">✓ Approve KYC</button>
                    </form>
                    <form method="POST" action="{{ route('owner.tenants.kyc.reject', $tenant) }}" class="inline-flex gap-2 items-center">
                        @csrf @method('PATCH')
                        <input name="remarks" placeholder="Reason for rejection" class="px-3 py-2 border border-ink-200 rounded-lg text-sm">
                        <button class="px-5 py-2.5 bg-rose-500 hover:bg-rose-600 text-white rounded-lg text-sm font-bold"><i class="fa-solid fa-circle-xmark fa-fw"></i> Reject</button>
                    </form>
                </div>
            @endif

            @if($tenant->kyc_status === 'rejected' && $tenant->kyc_remarks)
                <div class="mt-4 p-3 bg-rose-50 border border-rose-200 rounded-xl">
                    <div class="text-xs font-bold text-rose-700 uppercase">Rejection Reason:</div>
                    <p class="text-sm text-rose-900 mt-1">{{ $tenant->kyc_remarks }}</p>
                </div>
            @endif
        </div>

        <div class="bg-white p-6 rounded-2xl border border-ink-100">
            <h2 class="font-display font-bold text-xl mb-4"><i class="fa-solid fa-clipboard-list fa-fw"></i> Personal Details</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div><span class="text-ink-500">DOB:</span> <strong>{{ $tenant->dob?->format('d M Y') ?? '—' }}</strong></div>
                <div><span class="text-ink-500">Gender:</span> <strong class="capitalize">{{ $tenant->gender ?? '—' }}</strong></div>
                <div><span class="text-ink-500">Occupation:</span> <strong>{{ $tenant->occupation ?? '—' }}</strong></div>
                <div><span class="text-ink-500">Company/College:</span> <strong>{{ $tenant->company_college ?? '—' }}</strong></div>
                <div class="sm:col-span-2"><span class="text-ink-500">Address:</span> <strong>{{ $tenant->address_line ?? '—' }}, {{ $tenant->city }}, {{ $tenant->state }} - {{ $tenant->pincode }}</strong></div>
            </div>
        </div>

        @if($tenant->emergency_name)
        <div class="bg-amber-50 p-6 rounded-2xl border border-amber-200">
            <h2 class="font-display font-bold text-xl mb-3"><i class="fa-solid fa-triangle-exclamation fa-fw"></i> Emergency Contact</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                <div><span class="text-amber-700">Name:</span> <strong>{{ $tenant->emergency_name }}</strong></div>
                <div><span class="text-amber-700">Phone:</span> <strong>{{ $tenant->emergency_phone }}</strong></div>
                <div><span class="text-amber-700">Relation:</span> <strong>{{ $tenant->emergency_relation }}</strong></div>
            </div>
        </div>
        @endif

    </div>

    <div class="lg:col-span-1">
        <div class="lg:sticky lg:top-24 space-y-4">
            <div class="bg-white p-5 rounded-2xl border border-ink-100">
                <h3 class="font-display font-bold mb-3"><i class="fa-solid fa-bolt fa-fw"></i> Quick Actions</h3>
                <div class="space-y-2">
                    <a href="https://wa.me/91{{ $tenant->phone }}" target="_blank" class="block w-full text-center px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg text-sm font-bold"><i class="fa-solid fa-comment-dots fa-fw"></i> WhatsApp</a>
                    <a href="tel:{{ $tenant->phone }}" class="block w-full text-center px-4 py-2.5 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm font-bold"><i class="fa-solid fa-phone fa-fw"></i> Call</a>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-ink-100">
                <h3 class="font-display font-bold mb-3">Change Status</h3>
                <form method="POST" action="{{ route('owner.tenants.status', $tenant) }}">
                    @csrf @method('PATCH')
                    <select name="status" onchange="this.form.submit()" class="w-full px-3 py-2.5 rounded-lg border border-ink-200 text-sm">
                        <option value="active" @selected($tenant->status==='active')>✓ Active</option>
                        <option value="notice_period" @selected($tenant->status==='notice_period')>Notice Period</option>
                        <option value="left" @selected($tenant->status==='left')>Left</option>
                        <option value="blacklisted" @selected($tenant->status==='blacklisted')>Blacklisted</option>
                    </select>
                </form>
            </div>

            <form method="POST" action="{{ route('owner.tenants.destroy', $tenant) }}" onsubmit="return confirm('Delete this tenant permanently?')">
                @csrf @method('DELETE')
                <button class="w-full px-4 py-2.5 bg-rose-500 hover:bg-rose-600 text-white rounded-lg text-sm font-bold"><i class="fa-solid fa-trash-can fa-fw"></i> Delete Tenant</button>
            </form>
        </div>
    </div>
</div>

@endsection