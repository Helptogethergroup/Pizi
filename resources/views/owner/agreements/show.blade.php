@extends('layouts.dashboard')
@section('title', 'Agreement ' . $agreement->agreement_number)
@section('content')

<div class="mb-6">
    <a href="{{ route('owner.agreements.index') }}" class="text-coral-500 font-bold">← Back to agreements</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 space-y-6">

        <div class="bg-white p-6 rounded-2xl border border-ink-100">
            <div class="flex items-start justify-between gap-3 flex-wrap mb-3">
                <div>
                    <span class="text-xs font-mono font-bold bg-cream px-2 py-1 rounded">{{ $agreement->agreement_number }}</span>
                    
                    @if($agreement->status === 'active')
                        <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-1 rounded-full font-bold ml-2">{{ $agreement->status_label }}</span>
                    @elseif($agreement->status === 'draft')
                        <span class="text-xs bg-ink-100 text-ink-700 px-2 py-1 rounded-full font-bold ml-2">{{ $agreement->status_label }}</span>
                    @elseif(in_array($agreement->status, ['expired', 'terminated']))
                        <span class="text-xs bg-rose-100 text-rose-700 px-2 py-1 rounded-full font-bold ml-2">{{ $agreement->status_label }}</span>
                    @else
                        <span class="text-xs bg-amber-100 text-amber-700 px-2 py-1 rounded-full font-bold ml-2">{{ $agreement->status_label }}</span>
                    @endif

                    <h1 class="font-display font-black text-2xl mt-2">Rent Agreement</h1>
                    <p class="text-sm text-ink-700 mt-1">{{ $agreement->tenant?->name }} · {{ $agreement->property?->name }}</p>
                </div>
                <a href="{{ route('owner.agreements.preview', $agreement) }}" target="_blank" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-xl text-sm font-bold"><i class="fa-solid fa-file-lines fa-fw"></i> Preview / Print</a>
            </div>

            @if($agreement->is_expiring_soon)
                <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-xl">
                    <p class="text-sm text-amber-900 font-bold"><i class="fa-solid fa-triangle-exclamation fa-fw"></i> Expires in {{ $agreement->days_remaining }} days. Consider renewal.</p>
                </div>
            @endif

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-5 pt-5 border-t border-ink-100 text-sm">
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">Start</div>
                    <div class="font-bold">{{ $agreement->start_date->format('d M Y') }}</div>
                </div>
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">End</div>
                    <div class="font-bold">{{ $agreement->end_date->format('d M Y') }}</div>
                </div>
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">Duration</div>
                    <div class="font-bold">{{ $agreement->duration_months }} months</div>
                </div>
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">Monthly Rent</div>
                    <div class="font-bold text-coral-600">₹{{ number_format($agreement->monthly_rent, 0) }}</div>
                </div>
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">Deposit</div>
                    <div class="font-bold">₹{{ number_format($agreement->security_deposit, 0) }}</div>
                </div>
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">Lock-in</div>
                    <div class="font-bold">{{ $agreement->lock_in_months }} months</div>
                </div>
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">Notice</div>
                    <div class="font-bold">{{ $agreement->notice_period_days }} days</div>
                </div>
                <div>
                    <div class="text-xs text-ink-500 uppercase font-bold">Rent Due</div>
                    <div class="font-bold">{{ $agreement->rent_due_day }} of month</div>
                </div>
            </div>

            <div class="mt-5 pt-5 border-t border-ink-100">
                <h3 class="font-bold mb-2">Inclusions</h3>
                <div class="flex gap-2 flex-wrap">
                    @if($agreement->electricity_included)<span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-1 rounded-full font-bold"><i class="fa-solid fa-bolt fa-fw"></i> Electricity</span>@else<span class="text-xs bg-ink-100 text-ink-700 px-2 py-1 rounded-full"><i class="fa-solid fa-bolt fa-fw"></i> Electricity NOT included</span>@endif
                    @if($agreement->water_included)<span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-1 rounded-full font-bold"><i class="fa-solid fa-droplet fa-fw"></i> Water</span>@endif
                    @if($agreement->food_included)<span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-1 rounded-full font-bold"><i class="fa-solid fa-utensils fa-fw"></i> Food</span>@endif
                </div>
            </div>

            @if($agreement->additional_terms)
                <div class="mt-4 p-3 bg-cream rounded-xl">
                    <div class="text-xs font-bold uppercase text-ink-500 mb-1">Additional Terms</div>
                    <p class="text-sm whitespace-pre-line">{{ $agreement->additional_terms }}</p>
                </div>
            @endif

            @if($agreement->house_rules)
                <div class="mt-3 p-3 bg-cream rounded-xl">
                    <div class="text-xs font-bold uppercase text-ink-500 mb-1">House Rules</div>
                    <p class="text-sm whitespace-pre-line">{{ $agreement->house_rules }}</p>
                </div>
            @endif
        </div>

        <div class="bg-white p-6 rounded-2xl border border-ink-100">
            <h2 class="font-display font-bold text-lg mb-4"><i class="fa-solid fa-signature fa-fw"></i> Signatures</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 rounded-xl border-2 {{ $agreement->ownerSigned() ? 'border-emerald-300 bg-emerald-50' : 'border-amber-200 bg-amber-50' }}">
                    <div class="flex items-center justify-between mb-2">
                        <div class="font-bold"><i class="fa-solid fa-house fa-fw"></i> Owner</div>
                        @if($agreement->ownerSigned())
                            <span class="text-xs bg-emerald-200 text-emerald-900 px-2 py-0.5 rounded-full font-bold">✓ Signed</span>
                        @else
                            <span class="text-xs bg-amber-200 text-amber-900 px-2 py-0.5 rounded-full font-bold">Pending</span>
                        @endif
                    </div>
                    @if($agreement->ownerSigned())
                        @php $sig = $agreement->signatures->firstWhere('signer_type', 'owner'); @endphp
                        <div class="text-sm">
                            <div class="font-bold">{{ $sig->signer_name }}</div>
                            <div class="text-xs text-ink-700">{{ $sig->signed_at->format('d M Y, h:i A') }}</div>
                        </div>
                    @else
                        <form method="POST" action="{{ route('owner.agreements.sign.owner', $agreement) }}">
                            @csrf
                            <button class="w-full mt-2 px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg text-sm font-bold"><i class="fa-solid fa-signature fa-fw"></i> Sign as Owner</button>
                        </form>
                    @endif
                </div>

                <div class="p-4 rounded-xl border-2 {{ $agreement->tenantSigned() ? 'border-emerald-300 bg-emerald-50' : 'border-amber-200 bg-amber-50' }}">
                    <div class="flex items-center justify-between mb-2">
                        <div class="font-bold"><i class="fa-solid fa-user fa-fw"></i> Tenant</div>
                        @if($agreement->tenantSigned())
                            <span class="text-xs bg-emerald-200 text-emerald-900 px-2 py-0.5 rounded-full font-bold">✓ Signed</span>
                        @else
                            <span class="text-xs bg-amber-200 text-amber-900 px-2 py-0.5 rounded-full font-bold">Pending</span>
                        @endif
                    </div>
                    @if($agreement->tenantSigned())
                        @php $sig = $agreement->signatures->firstWhere('signer_type', 'tenant'); @endphp
                        <div class="text-sm">
                            <div class="font-bold">{{ $sig->signer_name }}</div>
                            <div class="text-xs text-ink-700">{{ $sig->signed_at->format('d M Y, h:i A') }}</div>
                        </div>
                    @else
                        <form method="POST" action="{{ route('owner.agreements.sign.tenant', $agreement) }}">
                            @csrf
                            <button class="w-full mt-2 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm font-bold"><i class="fa-solid fa-signature fa-fw"></i> Record Tenant Sign</button>
                        </form>
                    @endif
                </div>
            </div>

            @if($agreement->status === 'active')
                <div class="mt-4 p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-center">
                    <p class="text-sm font-bold text-emerald-900"><i class="fa-solid fa-circle-check fa-fw"></i> Agreement Fully Signed & Active</p>
                </div>
            @endif
        </div>

    </div>

    <div class="lg:col-span-1">
        <div class="lg:sticky lg:top-24 space-y-4">

            <div class="bg-white p-5 rounded-2xl border border-ink-100">
                <h3 class="font-display font-bold mb-3">Tenant</h3>
                <div class="space-y-1 text-sm">
                    <div class="font-bold">{{ $agreement->tenant?->name }}</div>
                    <div class="text-ink-700"><i class="fa-solid fa-mobile-screen fa-fw"></i> {{ $agreement->tenant?->phone }}</div>
                    @if($agreement->room_number)<div class="text-ink-700"><i class="fa-solid fa-door-open fa-fw"></i> Room {{ $agreement->room_number }}</div>@endif
                </div>
                <a href="https://wa.me/91{{ $agreement->tenant?->phone }}" target="_blank" class="block mt-3 w-full text-center px-4 py-2 bg-emerald-500 text-white rounded-lg text-sm font-bold"><i class="fa-solid fa-comment-dots fa-fw"></i> WhatsApp</a>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-ink-100">
                <h3 class="font-display font-bold mb-3">Actions</h3>
                <div class="space-y-2">
                    <a href="{{ route('owner.agreements.preview', $agreement) }}" target="_blank" class="block w-full text-center px-4 py-2 bg-blue-500 text-white rounded-lg text-sm font-bold"><i class="fa-solid fa-file-lines fa-fw"></i> Print / PDF</a>

                    @if(in_array($agreement->status, ['draft', 'sent']))
                        <a href="{{ route('owner.agreements.edit', $agreement) }}" class="block w-full text-center px-4 py-2 bg-ink-950 text-cream rounded-lg text-sm font-bold"><i class="fa-solid fa-pencil fa-fw"></i> Edit</a>
                    @endif

                    @if(in_array($agreement->status, ['active', 'expired']))
                        <form method="POST" action="{{ route('owner.agreements.renew', $agreement) }}">
                            @csrf
                            <button class="w-full px-4 py-2 bg-coral-500 text-white rounded-lg text-sm font-bold"><i class="fa-solid fa-rotate fa-fw"></i> Renew</button>
                        </form>
                    @endif

                    @if($agreement->status === 'active')
                        <button onclick="document.getElementById('termModal').classList.remove('hidden')" class="w-full px-4 py-2 bg-amber-500 text-white rounded-lg text-sm font-bold"><i class="fa-solid fa-circle-xmark fa-fw"></i> Terminate</button>
                    @endif

                    @if(in_array($agreement->status, ['draft', 'sent']))
                        <form method="POST" action="{{ route('owner.agreements.destroy', $agreement) }}" onsubmit="return confirm('Delete?')">
                            @csrf @method('DELETE')
                            <button class="w-full px-4 py-2 bg-rose-500 hover:bg-rose-600 text-white rounded-lg text-sm font-bold"><i class="fa-solid fa-trash-can fa-fw"></i> Delete</button>
                        </form>
                    @endif
                </div>
            </div>

            @if($agreement->termination_reason)
                <div class="bg-rose-50 p-4 rounded-2xl border border-rose-200">
                    <h3 class="font-bold text-rose-900 mb-2"><i class="fa-solid fa-circle-xmark fa-fw"></i> Terminated</h3>
                    <p class="text-sm text-rose-800">{{ $agreement->termination_reason }}</p>
                    <p class="text-xs text-rose-700 mt-1">{{ $agreement->terminated_at?->format('d M Y') }}</p>
                </div>
            @endif

        </div>
    </div>
</div>

<div id="termModal" class="hidden fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-6 max-w-md w-full">
        <h2 class="font-display font-bold text-xl mb-4">Terminate Agreement</h2>
        <form method="POST" action="{{ route('owner.agreements.terminate', $agreement) }}">
            @csrf @method('PATCH')
            <label class="text-xs font-bold uppercase text-ink-500">Reason for Termination *</label>
            <textarea name="termination_reason" required rows="4" placeholder="e.g. Tenant violated rules, mutual cancellation..." class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200 mb-4"></textarea>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-5 py-2.5 bg-rose-500 text-white rounded-lg font-bold">Terminate</button>
                <button type="button" onclick="document.getElementById('termModal').classList.add('hidden')" class="px-5 py-2.5 border border-ink-200 rounded-lg font-bold">Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection