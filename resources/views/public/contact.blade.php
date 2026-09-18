@extends('layouts.app')
@section('title', 'Contact Pizi — PG Booking Help & Owner Listings | Delhi NCR')
@section('meta_description', 'Contact Pizi for PG and hostel bookings in Delhi NCR. WhatsApp, call 8006680092, or email info@pizi.in. List your property or get help finding a PG. Response within 30 minutes.')

@push('head')
<meta name="keywords" content="contact pizi, pg booking help, pg owner listing, pizi support, pg near me delhi, hostel booking contact">
<link rel="canonical" href="https://pizi.in/contact">

{{-- Open Graph --}}
<meta property="og:title" content="Contact Pizi — PG Booking Help & Owner Listings">
<meta property="og:description" content="Need help finding a PG or listing your property? Contact Pizi. Response within 30 minutes.">
<meta property="og:type" content="website">
<meta property="og:url" content="https://pizi.in/contact">

{{-- Schema: ContactPage + LocalBusiness --}}
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "ContactPage",
  "name": "Contact Pizi",
  "description": "Get in touch with Pizi for PG bookings and owner listings",
  "url": "https://pizi.in/contact"
}
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "Pizi",
  "image": "https://pizi.in/logo.png",
  "telephone": "+918006680092",
  "email": "info@pizi.in",
  "url": "https://pizi.in",
  "address": {
    "@type": "PostalAddress",
    "addressLocality": "Delhi NCR",
    "addressRegion": "Delhi",
    "addressCountry": "IN"
  },
  "openingHoursSpecification": {
    "@type": "OpeningHoursSpecification",
    "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"],
    "opens": "09:00",
    "closes": "21:00"
  },
  "priceRange": "₹₹"
}
</script>
<style>
@keyframes pzContactFadeUp { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: translateY(0); } }
.pz-reveal2 { opacity: 0; }
.pz-reveal2.pz-in { animation: pzContactFadeUp .7s cubic-bezier(.16,1,.3,1) forwards; }
@keyframes pzCheckPop { 0% { transform: scale(0); opacity: 0; } 60% { transform: scale(1.15); opacity: 1; } 100% { transform: scale(1); } }
.pz-check-pop { animation: pzCheckPop .5s cubic-bezier(.16,1,.3,1) forwards; }
@keyframes pzSpin { to { transform: rotate(360deg); } }
.pz-spin { animation: pzSpin .7s linear infinite; }
</style>
@endpush

@section('content')

{{-- HERO (Compact) --}}
<section class="py-10 lg:py-14 bg-gradient-to-b from-cream to-white">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <span class="pz-reveal2 text-coral-600 font-semibold text-sm tracking-wider uppercase">Get in touch</span>
        <h1 class="pz-reveal2 font-display font-black text-3xl sm:text-4xl lg:text-5xl mt-3">We're here to <span class="italic text-coral-600">help.</span></h1>
        <p class="pz-reveal2 text-base lg:text-lg text-ink-900/70 mt-4 max-w-2xl mx-auto">
            Need a PG? Want to list your property? Our team responds within 30 minutes.
        </p>
    </div>
</section>

{{-- CONTACT CARDS (Compact) --}}
<section class="pb-10">
    <div class="max-w-5xl mx-auto px-4 grid md:grid-cols-3 gap-4">
        <a href="https://wa.me/918006680092?text=Hi%2C%20I%20need%20help%20with%20Pizi" class="pz-tilt-card pz-reveal2 p-6 rounded-2xl border border-ink-900/10 hover:border-emerald-500 hover:shadow-md transition group">
            <div class="w-11 h-11 rounded-xl bg-emerald-100 flex items-center justify-center text-xl mb-3"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 0 1-8.9 8.5 9 9 0 0 1-3.6-.7L3 21l1.7-5.5A8.4 8.4 0 0 1 12.6 3a8.4 8.4 0 0 1 8.4 8.5z"/></svg></div>
            <h2 class="font-display font-bold text-lg">WhatsApp</h2>
            <p class="text-sm text-ink-900/60 mt-1">Fastest response. Chat in 30 sec.</p>
            <div class="mt-3 text-emerald-600 font-semibold text-sm group-hover:underline">8006680092 →</div>
        </a>
        <a href="tel:+918006680092" class="pz-tilt-card pz-reveal2 p-6 rounded-2xl border border-ink-900/10 hover:border-coral-500 hover:shadow-md transition group" style="animation-delay:.08s">
            <div class="w-11 h-11 rounded-xl bg-coral-50 flex items-center justify-center text-xl mb-3"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6.6 10.8c1.4 2.8 3.8 5.2 6.6 6.6l2.2-2.2c.3-.3.7-.4 1.1-.2 1.2.4 2.4.6 3.7.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C11.4 21 3 12.6 3 2.3 3 1.7 3.4 1.3 4 1.3h3.4c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.7.1.4 0 .8-.2 1.1l-2.2 2.2z"/></svg></div>
            <h2 class="font-display font-bold text-lg">Call us</h2>
            <p class="text-sm text-ink-900/60 mt-1">Real person. 9 AM – 9 PM.</p>
            <div class="mt-3 text-coral-600 font-semibold text-sm group-hover:underline">+91 8006680092 →</div>
        </a>
        <a href="mailto:info@pizi.in" class="pz-tilt-card pz-reveal2 p-6 rounded-2xl border border-ink-900/10 hover:border-ink-900 hover:shadow-md transition group" style="animation-delay:.16s">
            <div class="w-11 h-11 rounded-xl bg-ink-100 flex items-center justify-center text-xl mb-3"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>️</div>
            <h2 class="font-display font-bold text-lg">Email</h2>
            <p class="text-sm text-ink-900/60 mt-1">Business & partnership queries.</p>
            <div class="mt-3 text-ink-900 font-semibold text-sm group-hover:underline">info@pizi.in →</div>
        </a>
    </div>
</section>

{{-- FORM + INFO (2-column) --}}
<section class="pb-12">
    <div class="max-w-5xl mx-auto px-4 grid lg:grid-cols-5 gap-6">
        
        {{-- LEFT: Form --}}
        <div class="lg:col-span-3 bg-white p-6 lg:p-8 rounded-2xl border border-ink-900/10 pz-reveal2">

            <div id="pzContactFormWrap">
                <h2 class="font-display font-bold text-2xl">Send us a message</h2>
                <p class="text-sm text-ink-900/60 mt-1">We'll get back within 24 hours.</p>

                <div id="pzContactFormError" class="hidden mt-4 p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-sm"></div>

                <form id="pzContactForm" method="POST" action="{{ route('contact.submit') }}" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <label class="text-xs font-semibold text-ink-900/60 uppercase">I am a...</label>
                        <div class="grid grid-cols-2 gap-3 mt-1">
                            <label class="flex items-center gap-2 px-4 py-3 rounded-xl border border-ink-900/15 cursor-pointer has-[:checked]:border-coral-500 has-[:checked]:bg-coral-50">
                                <input type="radio" name="inquiry_type" value="tenant" checked class="accent-coral-500">
                                <span class="text-sm font-semibold"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="8" width="16" height="12" rx="2"/><path d="M9 8V6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M10 12v4M14 12v4"/></svg> Looking for a PG</span>
                            </label>
                            <label class="flex items-center gap-2 px-4 py-3 rounded-xl border border-ink-900/15 cursor-pointer has-[:checked]:border-coral-500 has-[:checked]:bg-coral-50">
                                <input type="radio" name="inquiry_type" value="owner" class="accent-coral-500">
                                <span class="text-sm font-semibold"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11.5 12 4l9 7.5"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/></svg> I own a PG</span>
                            </label>
                        </div>
                    </div>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-semibold text-ink-900/60 uppercase">Name *</label>
                            <input name="name" required class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none transition">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-ink-900/60 uppercase">Phone *</label>
                            <input name="phone" required type="tel" maxlength="10" pattern="[0-9]{10}" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none transition">
                        </div>
                    </div>
                    <div id="pzContactCityWrap">
                        <label class="text-xs font-semibold text-ink-900/60 uppercase">Which city are you looking in? *</label>
                        <select name="preferred_city" id="pzContactCity" required class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none transition">
                            <option value="">— Select city —</option>
                            @foreach($cities as $city)
                                <option value="{{ $city->name }}">{{ $city->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-ink-900/60 uppercase">Email</label>
                        <input name="email" type="email" class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none transition">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-ink-900/60 uppercase">Message *</label>
                        <textarea name="message" rows="4" required class="w-full mt-1 px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none transition" placeholder="Tell us what you need..."></textarea>
                    </div>
                    <button id="pzContactSubmitBtn" type="submit" class="w-full py-3.5 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-semibold transition flex items-center justify-center gap-2">
                        <span id="pzContactBtnText">Send message →</span>
                    </button>
                </form>
            </div>

            {{-- Animated success state --}}
            <div id="pzContactSuccess" class="hidden py-10 text-center">
                <div class="pz-check-pop w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-3xl mx-auto"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7" stroke-width="2.5"/></svg></div>
                <h3 class="font-display font-bold text-xl mt-4">Message sent!</h3>
                <p class="text-sm text-ink-900/60 mt-1">Our team will get back to you within 24 hours.</p>
                <button onclick="pzResetContactForm()" class="mt-5 text-sm font-semibold text-coral-600 hover:text-coral-700 transition">Send another message</button>
            </div>
        </div>

        {{-- RIGHT: Quick Info --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Office Hours --}}
            <div class="pz-reveal2 p-6 rounded-2xl bg-ink-950 text-cream" style="animation-delay:.05s">
                <h3 class="font-display font-bold text-lg mb-3">⏰ Office Hours</h3>
                <div class="space-y-1.5 text-sm text-cream/80">
                    <div class="flex justify-between">
                        <span>Mon – Sat</span>
                        <span class="font-semibold">9 AM – 9 PM</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Sunday</span>
                        <span class="font-semibold">10 AM – 6 PM</span>
                    </div>
                </div>
            </div>

            {{-- For Owners --}}
            <div class="pz-reveal2 p-6 rounded-2xl bg-coral-50 border border-coral-200" style="animation-delay:.1s">
                <h3 class="font-display font-bold text-lg text-ink-950 mb-2"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="3" width="14" height="18" rx="1"/><path d="M9 7h1M14 7h1M9 11h1M14 11h1M9 15h1M14 15h1M10 21v-3h4v3"/></svg> PG Owner?</h3>
                <p class="text-sm text-ink-900/70 mb-3">List your property and start getting verified tenant leads.</p>
                <a href="{{ route('register') }}" class="inline-block px-4 py-2 bg-coral-500 hover:bg-coral-600 text-white rounded-lg font-bold text-sm transition">
                    List your PG →
                </a>
            </div>

            {{-- For Tenants --}}
            <div class="pz-reveal2 p-6 rounded-2xl bg-emerald-50 border border-emerald-200" style="animation-delay:.15s">
                <h3 class="font-display font-bold text-lg text-ink-950 mb-2"><svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11.5 12 4l9 7.5"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/></svg> Looking for a PG?</h3>
                <p class="text-sm text-ink-900/70 mb-3">Browse verified PGs across Delhi NCR.</p>
                <a href="{{ route('search') }}" class="inline-block px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg font-bold text-sm transition">
                    Browse PGs →
                </a>
            </div>
        </div>
    </div>
</section>

{{-- FAQ (Compact, SEO) --}}
<section class="pb-16">
    <div class="max-w-3xl mx-auto px-4">
        <h2 class="font-display font-black text-2xl text-center mb-6">Common Questions</h2>
        <div class="space-y-3">
            <details class="group p-5 rounded-xl border border-ink-900/10 bg-white">
                <summary class="font-semibold text-ink-950 cursor-pointer flex justify-between items-center">
                    How fast does Pizi respond?
                    <span class="text-coral-500 group-open:rotate-180 transition">▾</span>
                </summary>
                <p class="text-sm text-ink-900/70 mt-3">We respond within 30 minutes during office hours (9 AM – 9 PM) via WhatsApp or call.</p>
            </details>
            <details class="group p-5 rounded-xl border border-ink-900/10 bg-white">
                <summary class="font-semibold text-ink-950 cursor-pointer flex justify-between items-center">
                    Is listing a PG free on Pizi?
                    <span class="text-coral-500 group-open:rotate-180 transition">▾</span>
                </summary>
                <p class="text-sm text-ink-900/70 mt-3">Listing is free. You only buy credits to unlock tenant leads. No commission, no recurring charges.</p>
            </details>
            <details class="group p-5 rounded-xl border border-ink-900/10 bg-white">
                <summary class="font-semibold text-ink-950 cursor-pointer flex justify-between items-center">
                    Which cities does Pizi cover?
                    <span class="text-coral-500 group-open:rotate-180 transition">▾</span>
                </summary>
                <p class="text-sm text-ink-900/70 mt-3">We currently cover Delhi NCR including Noida, Gurugram, Greater Noida, and surrounding areas near major universities.</p>
            </details>
        </div>
    </div>
</section>

{{-- FAQ Schema --}}
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "How fast does Pizi respond?",
      "acceptedAnswer": {"@type": "Answer", "text": "We respond within 30 minutes during office hours via WhatsApp or call."}
    },
    {
      "@type": "Question",
      "name": "Is listing a PG free on Pizi?",
      "acceptedAnswer": {"@type": "Answer", "text": "Listing is free. You only buy credits to unlock tenant leads. No commission."}
    },
    {
      "@type": "Question",
      "name": "Which cities does Pizi cover?",
      "acceptedAnswer": {"@type": "Answer", "text": "We cover Delhi NCR including Noida, Gurugram, and Greater Noida."}
    }
  ]
}
</script>

{{-- Scroll reveal + animated AJAX form submit --}}
<script>
(function () {
    var els = document.querySelectorAll('.pz-reveal2');
    if (!('IntersectionObserver' in window)) {
        els.forEach(function (el) { el.classList.add('pz-in'); });
        return;
    }
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (e.isIntersecting) { e.target.classList.add('pz-in'); io.unobserve(e.target); }
        });
    }, { threshold: 0.12 });
    els.forEach(function (el) { io.observe(el); });
})();

// City is only required when enquiring as a tenant — an owner's own
// PG-listing intent doesn't need it.
(function () {
    var cityWrap = document.getElementById('pzContactCityWrap');
    var cityField = document.getElementById('pzContactCity');
    var radios = document.querySelectorAll('input[name="inquiry_type"]');

    function syncCityField() {
        var isTenant = document.querySelector('input[name="inquiry_type"]:checked')?.value === 'tenant';
        cityWrap.classList.toggle('hidden', !isTenant);
        cityField.required = isTenant;
    }

    radios.forEach(function (r) { r.addEventListener('change', syncCityField); });
    syncCityField();
})();

function pzResetContactForm() {
    document.getElementById('pzContactFormWrap').classList.remove('hidden');
    document.getElementById('pzContactSuccess').classList.add('hidden');
    document.getElementById('pzContactForm').reset();
}

document.getElementById('pzContactForm').addEventListener('submit', function (e) {
    e.preventDefault();
    var form = e.target;
    var btn = document.getElementById('pzContactSubmitBtn');
    var btnText = document.getElementById('pzContactBtnText');
    var errorBox = document.getElementById('pzContactFormError');

    errorBox.classList.add('hidden');
    btn.disabled = true;
    btnText.innerHTML = '<span class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full pz-spin"></span> Sending...';

    fetch(form.action, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(form),
    })
    .then(function (res) {
        if (!res.ok) throw new Error('Something went wrong. Please try again.');
        document.getElementById('pzContactFormWrap').classList.add('hidden');
        document.getElementById('pzContactSuccess').classList.remove('hidden');
    })
    .catch(function () {
        errorBox.textContent = '<svg class="inline-block w-[1em] h-[1em] align-[-0.15em]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 5l14 14M19 5L5 19" stroke-width="2.2"/></svg> Failed to send. Please try again or WhatsApp us directly.';
        errorBox.classList.remove('hidden');
    })
    .finally(function () {
        btn.disabled = false;
        btnText.textContent = 'Send message →';
    });
});
</script>

@endsection