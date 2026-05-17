@extends('layouts.app')
@section('title', 'Contact PGFind — We are here to help')
@section('meta_description', 'Get in touch with PGFind. Call us, WhatsApp, or email — we respond within 30 minutes.')
@section('content')

{{-- Hero --}}
<section class="bg-ink-950 text-cream py-16 lg:py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <span class="inline-block px-4 py-1.5 rounded-full bg-coral-500/10 text-coral-300 text-sm font-semibold border border-coral-500/20 mb-6">
            Get in touch
        </span>
        <h1 class="font-display font-black text-4xl sm:text-5xl lg:text-6xl mb-4 text-balance">
            We respond in <span class="text-coral-400 italic">30 minutes</span>
        </h1>
        <p class="text-lg text-cream/70">
            Have a question? Need help finding a PG? Want to list yours? We're here.
        </p>
    </div>
</section>

<section class="py-16 lg:py-20 bg-cream">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">

            {{-- Contact methods --}}
            <div>
                <h2 class="font-display font-black text-3xl text-ink-950 mb-3">Reach us</h2>
                <p class="text-ink-700 mb-8">Pick what works for you. We're available 9 AM – 9 PM, all days.</p>

                <div class="space-y-4">
                    <a href="https://wa.me/{{ env('BRAND_WHATSAPP', '919999999999') }}?text={{ urlencode('Hi PGFind, I need help.') }}" target="_blank" class="hover-lift flex items-center gap-4 bg-white p-5 rounded-2xl border border-ink-100">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-500 flex items-center justify-center flex-shrink-0">
                            <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163a11.867 11.867 0 01-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 018.413 3.488 11.824 11.824 0 013.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 01-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 001.51 5.26l-.999 3.648 3.978-.607z"/></svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-xs text-ink-500 uppercase font-bold">Fastest response</div>
                            <div class="font-display font-bold text-lg text-ink-950">WhatsApp Chat</div>
                            <div class="text-sm text-ink-600">{{ env('BRAND_WHATSAPP', '+91 99999 99999') }}</div>
                        </div>
                        <svg class="w-5 h-5 text-ink-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>

                    <a href="tel:{{ env('BRAND_PHONE', '+919999999999') }}" class="hover-lift flex items-center gap-4 bg-white p-5 rounded-2xl border border-ink-100">
                        <div class="w-14 h-14 rounded-2xl bg-coral-500 flex items-center justify-center flex-shrink-0">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-xs text-ink-500 uppercase font-bold">Talk to us</div>
                            <div class="font-display font-bold text-lg text-ink-950">Call us</div>
                            <div class="text-sm text-ink-600">{{ env('BRAND_PHONE', '+91 99999 99999') }}</div>
                        </div>
                        <svg class="w-5 h-5 text-ink-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>

                    <a href="mailto:{{ env('BRAND_EMAIL', 'contact@pgfind.in') }}" class="hover-lift flex items-center gap-4 bg-white p-5 rounded-2xl border border-ink-100">
                        <div class="w-14 h-14 rounded-2xl bg-ink-950 flex items-center justify-center flex-shrink-0">
                            <svg class="w-7 h-7 text-coral-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-xs text-ink-500 uppercase font-bold">For business</div>
                            <div class="font-display font-bold text-lg text-ink-950">Email</div>
                            <div class="text-sm text-ink-600">{{ env('BRAND_EMAIL', 'contact@pgfind.in') }}</div>
                        </div>
                        <svg class="w-5 h-5 text-ink-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>

                <div class="mt-8 p-5 bg-ink-950 text-cream rounded-2xl">
                    <h3 class="font-display font-bold text-lg mb-2">📍 Visit us</h3>
                    <p class="text-cream/70 text-sm leading-relaxed">
                        PGFind HQ<br>
                        Mukherjee Nagar, Delhi 110009<br>
                        India
                    </p>
                </div>
            </div>

            {{-- Contact form --}}
            <div>
                <div class="bg-white p-6 lg:p-8 rounded-2xl border border-ink-100">
                    <h2 class="font-display font-black text-3xl text-ink-950 mb-2">Send a message</h2>
                    <p class="text-ink-600 mb-6">Fill the form — we reply within 30 minutes.</p>

                    <form method="POST" action="{{ route('contact.submit') }}" class="space-y-4">
                        @csrf

                        <div>
                            <label class="text-xs font-bold uppercase text-ink-700">Your name *</label>
                            <input name="name" required value="{{ old('name') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200 focus:border-coral-500 outline-none transition" placeholder="Rohit Sharma">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-bold uppercase text-ink-700">Phone *</label>
                                <input name="phone" required value="{{ old('phone') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200 focus:border-coral-500 outline-none transition" placeholder="98765 43210">
                            </div>
                            <div>
                                <label class="text-xs font-bold uppercase text-ink-700">Email</label>
                                <input name="email" type="email" value="{{ old('email') }}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200 focus:border-coral-500 outline-none transition" placeholder="you@email.com">
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-bold uppercase text-ink-700">I am a *</label>
                            <select name="user_type" required class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200 focus:border-coral-500 outline-none transition">
                                <option value="">Select...</option>
                                <option value="tenant">Tenant looking for a PG</option>
                                <option value="owner">PG Owner</option>
                                <option value="agent">Agent / Broker</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-xs font-bold uppercase text-ink-700">Message *</label>
                            <textarea name="message" required rows="5" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-200 focus:border-coral-500 outline-none transition resize-none" placeholder="Tell us how we can help...">{{ old('message') }}</textarea>
                        </div>

                        <button type="submit" class="w-full py-4 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold text-base transition shadow-lg shadow-coral-500/30">
                            Send message →
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- FAQ --}}
<section class="py-16 lg:py-20 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="font-display font-black text-3xl lg:text-5xl text-ink-950">Frequently asked</h2>
        </div>

        <div class="space-y-3">
            @foreach([
                ['Is PGFind really free for tenants?', 'Yes. You only pay the security deposit and rent to the PG owner directly. No brokerage, no platform fees, ever.'],
                ['How are PGs verified?', 'Our field team physically visits every listed property to verify photos, amenities, owner identity, and basic safety standards before publishing.'],
                ['What if I do not like the PG after visiting?', 'No problem. You only pay when you decide to move in. Free site visits with our field executive — visit as many as you need.'],
                ['How do I list my PG?', 'Click "List Your PG" or register as an owner. Submit your property details and photos. Our team reviews within 24-48 hours and gets you live.'],
                ['Are there charges for owners?', 'Listing is free. Owners purchase credits to unlock tenant contact details (₹499–₹2,499 packages). Only pay when you get qualified leads.'],
            ] as $faq)
                <details class="bg-cream rounded-2xl p-5 group cursor-pointer">
                    <summary class="font-display font-bold text-lg text-ink-950 flex items-center justify-between list-none">
                        <span>{{ $faq[0] }}</span>
                        <svg class="w-5 h-5 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <p class="mt-3 text-ink-700 leading-relaxed">{{ $faq[1] }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>

@endsection