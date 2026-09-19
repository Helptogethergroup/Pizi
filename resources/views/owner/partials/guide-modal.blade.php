@php $guideSections = config('dashboard_guide.sections'); @endphp

@if(!auth()->user()->dashboard_guide_seen_at)
<div id="guideModal" class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[85vh] flex flex-col">

        <div class="p-5 border-b border-ink-100 flex items-center justify-between">
            <div>
                <h2 class="font-display font-black text-xl"><i class="fa-solid fa-hand fa-fw"></i> Welcome to your Pizi Dashboard</h2>
                <p class="text-sm text-ink-500 mt-0.5" id="guideSubtitle">Quick guide — what you can do here</p>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="setGuideLang('en')" id="guideBtnEn" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-ink-950 text-white">EN</button>
                <button onclick="setGuideLang('hi')" id="guideBtnHi" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-ink-100 text-ink-700">हिं</button>
            </div>
        </div>

        <div class="p-5 overflow-y-auto space-y-4">
            @foreach($guideSections as $s)
                <div class="flex gap-3">
                    <div class="text-2xl">{{ $s['icon'] }}</div>
                    <div>
                        <div class="font-bold guide-en">{{ $s['title_en'] }}</div>
                        <div class="font-bold guide-hi hidden">{{ $s['title_hi'] }}</div>
                        <div class="text-sm text-ink-700 mt-0.5 guide-en">{{ $s['body_en'] }}</div>
                        <div class="text-sm text-ink-700 mt-0.5 guide-hi hidden">{{ $s['body_hi'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="p-5 border-t border-ink-100 flex gap-3">
            <button onclick="closeGuideModal()" class="flex-1 px-5 py-3 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold">
                Got it, let's go →
            </button>
            <a href="{{ route('owner.guide.show') }}" target="_blank" class="px-5 py-3 border border-ink-200 rounded-xl font-bold text-sm">
                <i class="fa-solid fa-file-lines fa-fw"></i> View as PDF
            </a>
        </div>
    </div>
</div>

<script>
function setGuideLang(lang) {
    document.querySelectorAll('.guide-en').forEach(el => el.classList.toggle('hidden', lang !== 'en'));
    document.querySelectorAll('.guide-hi').forEach(el => el.classList.toggle('hidden', lang !== 'hi'));
    document.getElementById('guideBtnEn').className = 'px-3 py-1.5 rounded-lg text-xs font-bold ' + (lang === 'en' ? 'bg-ink-950 text-white' : 'bg-ink-100 text-ink-700');
    document.getElementById('guideBtnHi').className = 'px-3 py-1.5 rounded-lg text-xs font-bold ' + (lang === 'hi' ? 'bg-ink-950 text-white' : 'bg-ink-100 text-ink-700');
    document.getElementById('guideSubtitle').textContent = lang === 'en' ? 'Quick guide — what you can do here' : 'Quick guide — aap yahan kya kya kar sakte hain';
}

function closeGuideModal() {
    document.getElementById('guideModal').remove();
    fetch("{{ route('owner.guide.dismiss') }}", {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        },
    });
}
</script>
@endif