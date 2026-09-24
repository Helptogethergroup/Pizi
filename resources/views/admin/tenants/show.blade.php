@extends('layouts.dashboard')
@section('title', $tenant->name . ' — Admin')
@section('content')

<div class="mb-6">
    <a href="{{ route('admin.tenants.index') }}" class="text-coral-500 font-bold">← Back to all tenants</a>
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
                    <div class="text-sm text-ink-700 mt-2">
                        <i class="fa-solid fa-phone fa-fw"></i> {{ $tenant->phone }}
                        @if($tenant->email) · <i class="fa-solid fa-envelope fa-fw"></i> {{ $tenant->email }}@endif
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-5 pt-5 border-t border-ink-100">
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">Property</div>
                    <div class="font-bold text-sm">{{ $tenant->property?->name }}</div>
                </div>
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">Owner</div>
                    <div class="font-bold text-sm">{{ $tenant->owner?->name }}</div>
                </div>
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">Room/Bed</div>
                    <div class="font-bold">{{ $tenant->room_number }}{{ $tenant->bed_number ? '/'.$tenant->bed_number : '' }}</div>
                </div>
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">Rent</div>
                    <div class="font-bold text-coral-600">₹{{ number_format($tenant->monthly_rent) }}</div>
                </div>
            </div>
        </div>

        {{-- KYC Documents --}}
        <div class="bg-white p-6 rounded-2xl border border-ink-100">
            <h2 class="font-display font-bold text-xl mb-4"><i class="fa-solid fa-file-lines fa-fw"></i> KYC Documents ({{ $tenant->documents->count() }})</h2>

            @if($tenant->documents->count())
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-4">
                    @foreach($tenant->documents as $doc)
                        <a href="{{ $doc->url }}" target="_blank" class="rounded-xl border border-ink-100 overflow-hidden hover:border-coral-300">
                            @if(str_ends_with(strtolower($doc->file_path), '.pdf'))
                                <div class="aspect-square bg-rose-50 flex items-center justify-center text-4xl"><i class="fa-solid fa-file-lines fa-fw"></i></div>
                            @else
                                <img src="{{ $doc->url }}" class="w-full aspect-square object-cover">
                            @endif
                            <div class="p-2">
                                <div class="text-xs font-bold">{{ $doc->type_label }}</div>
                                @if($doc->document_number)
                                    <div class="text-xs text-ink-500">{{ $doc->document_number }}</div>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>

           @if($tenant->kyc_status === 'submitted' || $tenant->kyc_status === 'pending')
                    <div class="pt-4 border-t border-ink-100 space-y-3">
                        <form method="POST" action="{{ route('admin.tenants.kyc.approve', $tenant) }}" class="space-y-3">
                            @csrf @method('PATCH')

                            @if(empty($tenant->property_id))
                                <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg">
                                    <div class="text-xs font-bold text-amber-900 mb-2"><i class="fa-solid fa-triangle-exclamation fa-fw"></i> Property assign nahi hai — abhi assign karo (journey complete karne ke liye zaroori):</div>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                        <select name="property_id" required class="px-3 py-2 border border-ink-200 rounded-lg text-sm">
                                            <option value="">Select Property *</option>
                                            @foreach($properties as $p)
                                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                                            @endforeach
                                        </select>
                                        <input name="room_number" placeholder="Room no." class="px-3 py-2 border border-ink-200 rounded-lg text-sm">
                                        <input name="bed_number" placeholder="Bed no." class="px-3 py-2 border border-ink-200 rounded-lg text-sm">
                                    </div>
                                </div>
                            @endif

                            <button class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg text-sm font-bold">
                                ✓ Admin Approve KYC{{ empty($tenant->property_id) ? ' + Assign Room' : '' }}
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.tenants.kyc.reject', $tenant) }}" class="inline-flex gap-2 items-center">
                            @csrf @method('PATCH')
                            <input name="remarks" placeholder="Reason" class="px-3 py-2 border border-ink-200 rounded-lg text-sm">
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
            @else
                <p class="text-sm text-ink-500 text-center py-4">No documents uploaded yet by owner.</p>
            @endif
        </div>

        {{-- Recent Bills --}}
        @if($tenant->bills->count())
        <div class="bg-white p-6 rounded-2xl border border-ink-100">
            <h2 class="font-display font-bold text-xl mb-4"><i class="fa-solid fa-sack-dollar fa-fw"></i> Recent Bills</h2>
            <div class="space-y-2">
                @foreach($tenant->bills->take(5) as $b)
                    <a href="{{ route('admin.rent.show', $b) }}" class="flex items-center justify-between gap-3 p-3 bg-cream rounded-xl hover:bg-cream-200 transition">
                        <div>
                            <div class="font-bold text-sm">{{ $b->month_label }}</div>
                            <div class="text-xs text-ink-700">{{ $b->bill_number }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold">₹{{ number_format($b->total_amount, 0) }}</div>
                            @if($b->status === 'paid')
                                <span class="text-xs text-emerald-600 font-bold">✓ Paid</span>
                            @else
                                <span class="text-xs text-rose-600 font-bold">Due: ₹{{ number_format($b->due_amount, 0) }}</span>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Recent Complaints --}}
        @if($tenant->complaints->count())
        <div class="bg-white p-6 rounded-2xl border border-ink-100">
            <h2 class="font-display font-bold text-xl mb-4"><i class="fa-solid fa-screwdriver-wrench fa-fw"></i> Recent Complaints</h2>
            <div class="space-y-2">
                @foreach($tenant->complaints->take(5) as $c)
                    <a href="{{ route('admin.complaints.show', $c) }}" class="flex items-center justify-between gap-3 p-3 bg-cream rounded-xl hover:bg-cream-200 transition">
                        <div>
                            <div class="font-bold text-sm">{{ $c->title }}</div>
                            <div class="text-xs text-ink-700">{{ $c->category_label }} · {{ $c->created_at->diffForHumans() }}</div>
                        </div>
                        <span class="text-xs bg-cream px-2 py-0.5 rounded-full font-bold">{{ $c->status_label }}</span>
                    </a>
                @endforeach
            </div>
        </div>
        @endif

    </div>

    <div class="lg:col-span-1">
        <div class="lg:sticky lg:top-24 space-y-4">
            <div class="bg-amber-50 p-5 rounded-2xl border border-amber-200">
                <h3 class="font-display font-bold text-amber-900 mb-3"><i class="fa-solid fa-bolt fa-fw"></i> Admin Actions</h3>
                <p class="text-xs text-amber-800 mb-3">As admin, you can override owner actions.</p>

                <form method="POST" action="{{ route('admin.tenants.status', $tenant) }}" class="mb-3">
                    @csrf @method('PATCH')
                    <label class="text-xs font-bold uppercase text-amber-900">Force Status Change</label>
                    <select name="status" onchange="this.form.submit()" class="w-full mt-1 px-3 py-2 rounded-lg border border-amber-200 text-sm">
                        <option value="active" @selected($tenant->status==='active')>Active</option>
                        <option value="notice_period" @selected($tenant->status==='notice_period')>Notice Period</option>
                        <option value="left" @selected($tenant->status==='left')>Left</option>
                        <option value="blacklisted" @selected($tenant->status==='blacklisted')>Blacklisted</option>
                    </select>
                </form>

                <form method="POST" action="{{ route('admin.tenants.destroy', $tenant) }}" onsubmit="return confirm('Permanently delete this tenant and all data?')">
                    @csrf @method('DELETE')
                    <button class="w-full px-4 py-2 bg-rose-500 hover:bg-rose-600 text-white rounded-lg text-sm font-bold"><i class="fa-solid fa-trash-can fa-fw"></i> Delete Tenant</button>
                </form>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-ink-100">
                <h3 class="font-display font-bold mb-3">Contact</h3>
                <div class="space-y-2">
                    <a href="https://wa.me/91{{ $tenant->phone }}" target="_blank" class="block w-full text-center px-4 py-2.5 bg-emerald-500 text-white rounded-lg text-sm font-bold"><i class="fa-solid fa-comment-dots fa-fw"></i> WhatsApp</a>
                    <a href="tel:{{ $tenant->phone }}" class="block w-full text-center px-4 py-2.5 bg-blue-500 text-white rounded-lg text-sm font-bold"><i class="fa-solid fa-phone fa-fw"></i> Call Tenant</a>
                    <a href="tel:{{ $tenant->owner?->phone }}" class="block w-full text-center px-4 py-2.5 bg-purple-500 text-white rounded-lg text-sm font-bold"><i class="fa-solid fa-phone fa-fw"></i> Call Owner</a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection