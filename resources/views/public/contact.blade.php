@extends('layouts.app')
@section('title', 'Contact — PGFind')
@section('content')
<section class="max-w-4xl mx-auto px-4 lg:px-8 py-20">
    <h1 class="font-display font-black text-5xl">Talk to us.</h1>
    <p class="text-ink-900/70 text-lg mt-3">Reply within 30 minutes during business hours.</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mt-12">
        <form method="POST" action="{{ route('contact.submit') }}" class="space-y-4">
            @csrf
            <input name="name" required placeholder="Your name" class="w-full px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500">
            <input name="phone" required placeholder="Phone number" pattern="[0-9]{10}" class="w-full px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500">
            <input name="email" type="email" placeholder="Email" class="w-full px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500">
            <textarea name="message" required rows="5" placeholder="How can we help?" class="w-full px-4 py-3 rounded-xl border border-ink-900/15 outline-none focus:border-coral-500"></textarea>
            <button class="w-full py-4 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold">Send message</button>
        </form>

        <div class="space-y-6">
            <div>
                <div class="text-sm font-semibold text-ink-900/60 uppercase">Phone</div>
                <a href="tel:{{ env('BRAND_PHONE') }}" class="font-display text-2xl font-bold hover:text-coral-600">{{ env('BRAND_PHONE', '+91 99999 99999') }}</a>
            </div>
            <div>
                <div class="text-sm font-semibold text-ink-900/60 uppercase">WhatsApp</div>
                <a href="https://wa.me/{{ env('BRAND_WHATSAPP', '919999999999') }}" target="_blank" class="font-display text-2xl font-bold hover:text-coral-600">Chat on WhatsApp</a>
            </div>
            <div>
                <div class="text-sm font-semibold text-ink-900/60 uppercase">Email</div>
                <a href="mailto:{{ env('BRAND_EMAIL') }}" class="font-display text-2xl font-bold hover:text-coral-600">{{ env('BRAND_EMAIL', 'contact@pgfind.in') }}</a>
            </div>
        </div>
    </div>
</section>
@endsection
