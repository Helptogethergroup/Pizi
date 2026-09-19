{{-- Site-wide interactive layer: header search, saved PGs, compare, toasts, sliders, shrinking header. --}}
<style>
    /* ---------- announcement bar ---------- */
    #pzAnnounce { position: relative; overflow: hidden; background: linear-gradient(90deg, #ed4e3d, #ff6b5b, #ed4e3d); background-size: 200% 100%; animation: pzAnnGlow 8s linear infinite; max-height: 64px; transition: max-height .4s ease, opacity .3s ease; }
    #pzAnnounce.pz-closing { max-height: 0; opacity: 0; }
    @keyframes pzAnnGlow { to { background-position: -200% 0; } }

    /* ---------- header shrink ---------- */
    header.sticky { transition: box-shadow .3s ease, background-color .3s ease; }
    header.sticky > div { transition: height .3s ease; }
    .logo-img { transition: height .3s ease; }
    header.pz-shrunk { box-shadow: 0 10px 30px rgba(15, 39, 72, .08); }
    header.pz-shrunk > div { height: 3.75rem; }
    header.pz-shrunk .logo-img { height: 2.75rem !important; }

    /* header layout: nav sits centred between logo and actions so spacing stays even */
    header.sticky nav { flex: 1 1 auto; justify-content: center; margin: 0 .75rem; }
    @media (min-width: 1280px) and (max-width: 1535px) {
        header.sticky > div { padding-left: 1.5rem; padding-right: 1.5rem; }
        header.sticky .logo-img { height: 4.25rem !important; }
        header.sticky nav { gap: .125rem; }
        header.sticky nav > a, header.sticky nav > div > button, header.sticky nav > button { padding: .5rem .6rem; }
        header.sticky > div > div:last-child { gap: .625rem; }
    }

    /* ---------- header icon buttons ---------- */
    .pz-iconbtn { position: relative; width: 2.5rem; height: 2.5rem; border-radius: .75rem; display: inline-flex; align-items: center; justify-content: center; color: #0f2748; transition: background-color .2s, color .2s, transform .2s; }
    .pz-iconbtn:hover { background: #fff3f1; color: #ed4e3d; transform: translateY(-1px); }
    .pz-badge { position: absolute; top: -.2rem; right: -.2rem; min-width: 1.1rem; height: 1.1rem; padding: 0 .25rem; border-radius: 9999px; background: #ed4e3d; color: #fff; font-size: .65rem; font-weight: 700; display: none; align-items: center; justify-content: center; }
    .pz-badge.pz-on { display: inline-flex; animation: pzBadgePop .35s cubic-bezier(.34, 1.56, .64, 1); }
    @keyframes pzBadgePop { from { transform: scale(.3); } to { transform: scale(1); } }

    /* ---------- header search panel ---------- */
    #pzSearchPanel { position: absolute; left: 0; right: 0; top: 100%; background: rgba(254, 252, 246, .98); backdrop-filter: blur(10px); border-bottom: 1px solid rgba(15, 39, 72, .1); box-shadow: 0 24px 40px rgba(15, 39, 72, .12); max-height: 0; opacity: 0; overflow: hidden; visibility: hidden; transition: max-height .4s cubic-bezier(.16, 1, .3, 1), opacity .25s ease, visibility 0s linear .4s; }
    #pzSearchPanel.pz-open { max-height: 520px; opacity: 1; visibility: visible; transition: max-height .4s cubic-bezier(.16, 1, .3, 1), opacity .25s ease, visibility 0s; }
    #pzSuggest a { display: flex; align-items: center; gap: .75rem; padding: .6rem .75rem; border-radius: .75rem; transition: background-color .15s; animation: pzFadeUp .3s ease both; }
    #pzSuggest a:hover, #pzSuggest a:focus { background: #fff3f1; outline: none; }
    @keyframes pzFadeUp { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }

    /* ---------- card action buttons (save / compare) ---------- */
    .pz-card-actions { position: absolute; right: .5rem; bottom: .5rem; z-index: 5; display: flex; gap: .4rem; }
    .pz-cbtn { width: 2rem; height: 2rem; border-radius: 9999px; background: rgba(255, 255, 255, .95); color: #0f2748; display: inline-flex; align-items: center; justify-content: center; font-size: .8rem; box-shadow: 0 4px 12px rgba(15, 39, 72, .18); transition: transform .2s, background-color .2s, color .2s; cursor: pointer; border: 0; }
    .pz-cbtn:hover { transform: scale(1.12); }
    .pz-cbtn.pz-active { background: #ed4e3d; color: #fff; }
    .pz-cbtn.pz-active.pz-cmp { background: #0f2748; }
    .pz-cbtn.pz-pop { animation: pzHeartPop .5s cubic-bezier(.34, 1.56, .64, 1); }
    @keyframes pzHeartPop { 0% { transform: scale(1); } 40% { transform: scale(1.55); } 100% { transform: scale(1); } }

    /* ---------- overlay + drawer ---------- */
    .pz-overlay { position: fixed; inset: 0; z-index: 90; background: rgba(10, 26, 48, .55); backdrop-filter: blur(3px); opacity: 0; visibility: hidden; transition: opacity .3s ease, visibility 0s linear .3s; }
    .pz-overlay.pz-open { opacity: 1; visibility: visible; transition: opacity .3s ease, visibility 0s; }
    #pzSavedDrawer { position: fixed; top: 0; right: 0; bottom: 0; z-index: 91; width: 24rem; max-width: 92vw; background: #fefcf6; box-shadow: -20px 0 50px rgba(10, 26, 48, .25); transform: translateX(105%); transition: transform .4s cubic-bezier(.16, 1, .3, 1); display: flex; flex-direction: column; }
    #pzSavedDrawer.pz-open { transform: none; }
    .pz-saved-row { display: flex; gap: .75rem; padding: .75rem; border-radius: 1rem; background: #fff; border: 1px solid rgba(15, 39, 72, .08); animation: pzFadeUp .4s cubic-bezier(.16, 1, .3, 1) both; }

    /* ---------- compare tray + modal ---------- */
    #pzCompareTray { position: fixed; left: 50%; bottom: 1rem; z-index: 46; width: min(94vw, 46rem); transform: translate(-50%, 140%); transition: transform .45s cubic-bezier(.16, 1, .3, 1); background: #0f2748; color: #fefcf6; border-radius: 1.1rem; padding: .75rem; box-shadow: 0 24px 50px rgba(10, 26, 48, .4); display: flex; align-items: center; gap: .75rem; }
    #pzCompareTray.pz-open { transform: translate(-50%, 0); }
    .pz-slot { position: relative; width: 3.25rem; height: 3.25rem; border-radius: .75rem; background: rgba(255, 255, 255, .1); border: 1px dashed rgba(255, 255, 255, .3); overflow: hidden; flex: 0 0 auto; display: flex; align-items: center; justify-content: center; font-size: .8rem; color: rgba(255, 255, 255, .5); }
    .pz-slot.pz-filled { border-style: solid; border-color: rgba(255, 255, 255, .5); animation: pzHeartPop .45s cubic-bezier(.34, 1.56, .64, 1); }
    .pz-slot img { width: 100%; height: 100%; object-fit: cover; }
    .pz-slot button { position: absolute; top: 1px; right: 1px; width: 1rem; height: 1rem; border-radius: 9999px; background: rgba(0, 0, 0, .65); color: #fff; font-size: .55rem; display: flex; align-items: center; justify-content: center; border: 0; cursor: pointer; }
    #pzCompareModal { position: fixed; inset: 0; z-index: 92; display: flex; align-items: center; justify-content: center; padding: 1rem; opacity: 0; visibility: hidden; transition: opacity .3s ease, visibility 0s linear .3s; }
    #pzCompareModal.pz-open { opacity: 1; visibility: visible; transition: opacity .3s ease, visibility 0s; }
    #pzCompareModal .pz-modal-card { width: min(64rem, 100%); max-height: 90vh; overflow: auto; background: #fefcf6; border-radius: 1.5rem; box-shadow: 0 40px 80px rgba(10, 26, 48, .45); transform: translateY(24px) scale(.96); transition: transform .4s cubic-bezier(.16, 1, .3, 1); }
    #pzCompareModal.pz-open .pz-modal-card { transform: none; }
    .pz-cmp-table { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 34rem; }
    .pz-cmp-table th, .pz-cmp-table td { padding: .7rem .9rem; text-align: left; vertical-align: middle; border-bottom: 1px solid rgba(15, 39, 72, .08); font-size: .85rem; }
    .pz-cmp-table th.pz-label { width: 9rem; font-size: .7rem; text-transform: uppercase; letter-spacing: .06em; color: rgba(15, 39, 72, .55); font-weight: 700; position: sticky; left: 0; background: #fefcf6; }
    .pz-cmp-table tr { animation: pzFadeUp .45s cubic-bezier(.16, 1, .3, 1) both; }
    .pz-best { display: inline-flex; align-items: center; gap: .3rem; margin-left: .4rem; padding: .1rem .5rem; border-radius: 9999px; background: #d1fae5; color: #047857; font-size: .65rem; font-weight: 700; animation: pzHeartPop .6s .5s cubic-bezier(.34, 1.56, .64, 1) both; }

    /* ---------- toast ---------- */
    #pzToastHost { position: fixed; left: 50%; bottom: 5.5rem; transform: translateX(-50%); z-index: 95; display: flex; flex-direction: column; gap: .5rem; align-items: center; pointer-events: none; }
    .pz-toast { display: inline-flex; align-items: center; gap: .6rem; padding: .65rem 1rem; border-radius: 9999px; background: #0f2748; color: #fefcf6; font-size: .85rem; font-weight: 600; box-shadow: 0 16px 34px rgba(10, 26, 48, .35); animation: pzToastIn .4s cubic-bezier(.34, 1.56, .64, 1) both; }
    .pz-toast { pointer-events: auto; }
    .pz-toast-action { margin-left: .25rem; padding: .2rem .7rem; border-radius: 9999px; background: #ff6b5b; color: #fff; font-size: .75rem; font-weight: 700; border: 0; cursor: pointer; }
    .pz-toast.pz-out { animation: pzToastOut .3s ease both; }
    @keyframes pzToastIn { from { opacity: 0; transform: translateY(16px) scale(.9); } to { opacity: 1; transform: none; } }
    @keyframes pzToastOut { to { opacity: 0; transform: translateY(8px) scale(.95); } }

    /* ---------- slider arrows ---------- */
    .pz-slider-wrap { position: relative; }
    .pz-arrow { position: absolute; top: 50%; z-index: 6; width: 2.5rem; height: 2.5rem; margin-top: -1.5rem; border-radius: 9999px; background: #fff; color: #0f2748; border: 1px solid rgba(15, 39, 72, .12); box-shadow: 0 10px 24px rgba(15, 39, 72, .18); display: none; align-items: center; justify-content: center; cursor: pointer; opacity: 0; transition: opacity .25s, transform .2s, background-color .2s, color .2s; }
    .pz-arrow:hover { background: #ed4e3d; color: #fff; transform: scale(1.08); }
    .pz-arrow.pz-prev { left: -.75rem; }
    .pz-arrow.pz-next { right: -.75rem; }
    .pz-arrow[disabled] { display: none !important; }
    @media (min-width: 768px) { .pz-arrow { display: inline-flex; } .pz-slider-wrap:hover .pz-arrow { opacity: 1; } }
    .pz-dots { display: flex; justify-content: center; gap: .4rem; margin-top: .5rem; }
    .pz-dots span { width: .45rem; height: .45rem; border-radius: 9999px; background: rgba(15, 39, 72, .2); transition: width .3s, background-color .3s; }
    .pz-dots span.pz-on { width: 1.4rem; background: #ed4e3d; }

    /* ---------- lazy image shimmer ---------- */
    .pz-img-wait { opacity: 0; }
    img { transition: opacity .5s ease; }
    .pz-shimmer { background: linear-gradient(100deg, rgba(15, 39, 72, .04) 30%, rgba(15, 39, 72, .11) 50%, rgba(15, 39, 72, .04) 70%); background-size: 200% 100%; animation: pzShimmer 1.3s linear infinite; }
    @keyframes pzShimmer { to { background-position: -200% 0; } }

    @media (max-width: 767px) {
        #pzCompareTray { bottom: .5rem; }
        body:has(#pzMobileBar) #pzCompareTray { bottom: 4.6rem; }
        body.pz-tray-open .chat-bubble-wrapper, body.pz-tray-open #pzContactFab { bottom: 5.4rem !important; }
        body:has(#pzMobileBar).pz-tray-open .chat-bubble-wrapper, body:has(#pzMobileBar).pz-tray-open #pzContactFab { bottom: 9.6rem !important; }
        #pzToastHost { bottom: 7.5rem; }
        .pz-slot { width: 2.75rem; height: 2.75rem; }
    }
    @media (prefers-reduced-motion: reduce) {
        #pzAnnounce, .pz-toast, .pz-saved-row, .pz-cmp-table tr, #pzSuggest a, .pz-shimmer { animation: none !important; }
        #pzSearchPanel, #pzSavedDrawer, #pzCompareTray, #pzCompareModal .pz-modal-card { transition: none; }
    }
</style>

{{-- Header search panel (moved into the sticky header by JS) --}}
<div id="pzSearchPanel" aria-hidden="true">
    <div class="max-w-3xl mx-auto px-4 lg:px-8 py-5">
        <form action="{{ route('search') }}" method="GET" class="flex items-center gap-2 bg-white border border-ink-900/15 rounded-2xl px-4 py-2 focus-within:border-coral-500 transition" role="search">
            <i class="fa-solid fa-magnifying-glass text-ink-900/40"></i>
            <input id="pzSearchInput" type="text" name="q" autocomplete="off" placeholder="Search by PG name, locality, college or metro..." class="flex-1 min-w-0 py-2 text-sm bg-transparent outline-none">
            <button type="submit" class="px-4 py-2 rounded-xl bg-coral-500 hover:bg-coral-600 text-white text-sm font-bold transition">Search</button>
            <button type="button" id="pzSearchClose" class="w-9 h-9 rounded-xl text-ink-900/50 hover:text-coral-600 hover:bg-coral-50 transition" aria-label="Close search"><i class="fa-solid fa-xmark"></i></button>
        </form>
        <div id="pzSuggest" class="mt-2 space-y-0.5"></div>
        <div id="pzSearchChips" class="mt-3 flex flex-wrap items-center gap-2 text-xs">
            <span class="font-bold text-ink-900/50 uppercase tracking-wide mr-1">Try</span>
            <a href="{{ route('search', ['gender' => 'male']) }}" class="px-3 py-1.5 rounded-full bg-white border border-ink-900/10 font-semibold hover:border-coral-500 hover:text-coral-600 transition">Boys PG</a>
            <a href="{{ route('search', ['gender' => 'female']) }}" class="px-3 py-1.5 rounded-full bg-white border border-ink-900/10 font-semibold hover:border-coral-500 hover:text-coral-600 transition">Girls PG</a>
            <a href="{{ route('search', ['budget_max' => 8000]) }}" class="px-3 py-1.5 rounded-full bg-white border border-ink-900/10 font-semibold hover:border-coral-500 hover:text-coral-600 transition">Under &#8377;8,000</a>
            <a href="{{ route('search', ['food' => 1]) }}" class="px-3 py-1.5 rounded-full bg-white border border-ink-900/10 font-semibold hover:border-coral-500 hover:text-coral-600 transition">With food</a>
            <a href="{{ route('universities.index') }}" class="px-3 py-1.5 rounded-full bg-white border border-ink-900/10 font-semibold hover:border-coral-500 hover:text-coral-600 transition">Near universities</a>
        </div>
    </div>
</div>

{{-- Saved PGs drawer --}}
<div id="pzSavedOverlay" class="pz-overlay"></div>
<aside id="pzSavedDrawer" aria-hidden="true" aria-label="Saved PGs">
    <div class="flex items-center justify-between px-5 py-4 border-b border-ink-900/10">
        <h3 class="font-display font-black text-xl flex items-center gap-2"><i class="fa-solid fa-heart text-coral-500"></i> Saved PGs</h3>
        <button type="button" id="pzSavedClose" class="w-9 h-9 rounded-xl hover:bg-coral-50 hover:text-coral-600 transition" aria-label="Close saved PGs"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div id="pzSavedList" class="flex-1 overflow-y-auto p-4 space-y-3"></div>
    <div id="pzSavedFoot" class="p-4 border-t border-ink-900/10 hidden">
        <button type="button" id="pzSavedClear" class="w-full py-2.5 rounded-xl border border-ink-900/15 text-sm font-semibold text-ink-900/70 hover:border-coral-500 hover:text-coral-600 transition"><i class="fa-solid fa-trash-can"></i> Clear all</button>
    </div>
</aside>

{{-- Compare tray + modal --}}
<div id="pzCompareTray" role="region" aria-label="Compare PGs">
    <div id="pzCompareSlots" class="flex items-center gap-2"></div>
    <div class="flex-1 min-w-0 hidden sm:block">
        <div class="text-sm font-bold">Compare PGs</div>
        <div id="pzCompareHint" class="text-xs text-cream/60">Pick 2 or 3 PGs to compare side by side.</div>
    </div>
    <button type="button" id="pzCompareGo" class="ml-auto px-4 py-2.5 rounded-xl bg-coral-500 text-white text-sm font-bold whitespace-nowrap disabled:opacity-40 disabled:cursor-not-allowed hover:bg-coral-600 transition" disabled><i class="fa-solid fa-scale-balanced"></i> Compare</button>
    <button type="button" id="pzCompareClear" class="w-9 h-9 rounded-xl text-cream/60 hover:text-white hover:bg-white/10 transition" aria-label="Clear comparison"><i class="fa-solid fa-xmark"></i></button>
</div>
<div id="pzCompareModal" aria-hidden="true">
    <div class="absolute inset-0 bg-ink-950/60 backdrop-blur-sm" id="pzCompareBackdrop"></div>
    <div class="pz-modal-card relative">
        <div class="flex items-center justify-between px-5 py-4 border-b border-ink-900/10 sticky top-0 bg-cream z-10">
            <h3 class="font-display font-black text-xl flex items-center gap-2"><i class="fa-solid fa-scale-balanced text-coral-500"></i> Compare PGs</h3>
            <button type="button" id="pzCompareClose" class="w-9 h-9 rounded-xl hover:bg-coral-50 hover:text-coral-600 transition" aria-label="Close comparison"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div id="pzCompareBody" class="overflow-x-auto"></div>
    </div>
</div>

<div id="pzToastHost" aria-live="polite"></div>

<script>
(function () {
    'use strict';
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var SAVED = 'pz_saved_pgs', COMPARE = 'pz_compare_pgs', RECENT = 'pz_recent_pgs', CITY = 'pz_city';
    var searchUrl = @json(route('search'));
    var suggestUrl = @json(route('search.suggestions'));

    function load(k) { try { var v = JSON.parse(localStorage.getItem(k) || '[]'); return Array.isArray(v) ? v : []; } catch (e) { return []; } }
    function store(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} }
    function $(id) { return document.getElementById(id); }
    function h(tag, cls, html) { var n = document.createElement(tag); if (cls) n.className = cls; if (html) n.innerHTML = html; return n; }
    function money(v) { return '₹' + Number(v).toLocaleString('en-IN'); }
    function txt(node, s) { node.textContent = s; return node; }

    /* ---------- toast ---------- */
    window.pzToast = function (message, icon, action) {
        var host = $('pzToastHost'); if (!host) return;
        var t = h('div', 'pz-toast', '<i class="fa-solid ' + (icon || 'fa-circle-check') + '"></i>');
        t.appendChild(document.createTextNode(message));
        var close = function () { t.classList.add('pz-out'); setTimeout(function () { t.remove(); }, 320); };
        if (action) {
            var b = h('button', 'pz-toast-action'); b.type = 'button'; b.textContent = action.label;
            b.addEventListener('click', function () { close(); action.run(); });
            t.appendChild(b);
        }
        host.appendChild(t);
        setTimeout(close, action ? 4200 : 2600);
    };

    /* ---------- announcement bar ---------- */
    var ann = $('pzAnnounce'), annClose = $('pzAnnounceClose');
    if (ann && annClose) annClose.addEventListener('click', function () {
        try { localStorage.setItem('pz_ann_v1', '1'); } catch (e) {}
        ann.classList.add('pz-closing'); setTimeout(function () { ann.remove(); }, 450);
    });

    /* ---------- header: shrink on scroll + move search panel in ---------- */
    var header = document.querySelector('header.sticky');
    var panel = $('pzSearchPanel');
    if (header && panel) header.appendChild(panel);
    if (header) {
        var onScroll = function () { header.classList.toggle('pz-shrunk', window.scrollY > 60); };
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }
    var visitBtn = $('pzHeaderVisit');
    if (visitBtn) visitBtn.addEventListener('click', function (e) {
        var t = $('pzVisit'); if (!t) return;
        e.preventDefault(); t.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
    });

    /* ---------- header search panel ---------- */
    var sInput = $('pzSearchInput'), sBox = $('pzSuggest'), sTimer = null, sCtl = null;
    function openSearch() { if (!panel) return; panel.classList.add('pz-open'); panel.setAttribute('aria-hidden', 'false'); setTimeout(function () { sInput && sInput.focus(); }, 120); }
    function closeSearch() { if (!panel) return; panel.classList.remove('pz-open'); panel.setAttribute('aria-hidden', 'true'); }
    var sBtn = $('pzSearchBtn');
    if (sBtn) sBtn.addEventListener('click', function () { panel && panel.classList.contains('pz-open') ? closeSearch() : openSearch(); });
    if ($('pzSearchClose')) $('pzSearchClose').addEventListener('click', closeSearch);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { closeSearch(); closeSaved(); closeCompare(); }
        if (e.key === '/' && !/^(INPUT|TEXTAREA|SELECT)$/.test((document.activeElement || {}).tagName || '') && !(document.activeElement || {}).isContentEditable) { e.preventDefault(); openSearch(); }
    });
    document.addEventListener('click', function (e) {
        if (panel && panel.classList.contains('pz-open') && !panel.contains(e.target) && !(sBtn && sBtn.contains(e.target))) closeSearch();
    });
    if (sInput) sInput.addEventListener('input', function () {
        clearTimeout(sTimer);
        var q = sInput.value.trim();
        if (q.length < 2) { sBox.innerHTML = ''; return; }
        sTimer = setTimeout(function () {
            if (sCtl) sCtl.abort(); sCtl = new AbortController();
            fetch(suggestUrl + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' }, signal: sCtl.signal })
                .then(function (r) { return r.json(); })
                .then(function (list) {
                    sBox.innerHTML = '';
                    if (!list.length) { sBox.appendChild(txt(h('div', 'px-3 py-2 text-sm text-ink-900/50'), 'No PG names match. Press Enter to search everywhere.')); return; }
                    list.forEach(function (p, i) {
                        var a = h('a', '', '<span class="w-9 h-9 rounded-xl bg-coral-50 text-coral-600 flex items-center justify-center flex-shrink-0"><i class="fa-solid fa-house"></i></span>');
                        a.href = p.url; a.style.animationDelay = (i * 40) + 'ms';
                        var mid = h('span', 'flex-1 min-w-0');
                        mid.appendChild(txt(h('span', 'block text-sm font-semibold truncate'), p.name));
                        mid.appendChild(txt(h('span', 'block text-xs text-ink-900/55 truncate'), [p.locality, p.city].filter(Boolean).join(', ')));
                        a.appendChild(mid);
                        if (p.rent_min) a.appendChild(txt(h('span', 'text-sm font-bold text-ink-950 whitespace-nowrap'), money(p.rent_min)));
                        sBox.appendChild(a);
                    });
                }).catch(function () {});
        }, 250);
    });

    /* ---------- saved PGs + compare ---------- */
    function cardData(a) {
        var m = (a.getAttribute('href') || '').match(/\/pg\/([^\/?#]+)/); if (!m) return null;
        var t = a.querySelector('h3, h4'); if (!t) return null;
        var img = a.querySelector('img'), pm = (a.textContent || '').match(/₹\s*([\d,]+)/);
        return { slug: decodeURIComponent(m[1]), name: t.textContent.trim(), img: img ? (img.currentSrc || img.src) : '', price: pm ? pm[1] : '', url: a.href };
    }
    function mediaBox(a) { return a.querySelector('.pz-card-carousel') || a.querySelector('div.relative.overflow-hidden'); }
    function isSaved(slug) { return load(SAVED).some(function (x) { return x.slug === slug; }); }
    function inCompare(slug) { return load(COMPARE).some(function (x) { return x.slug === slug; }); }

    function enhance(a) {
        if (a.dataset.pzEnh) return;
        var d = cardData(a), box = d && mediaBox(a);
        if (!d || !box) return;
        a.dataset.pzEnh = '1';
        var bar = h('div', 'pz-card-actions');
        var cmp = h('button', 'pz-cbtn pz-cmp', '<i class="fa-solid fa-scale-balanced"></i>');
        cmp.type = 'button'; cmp.title = 'Add to compare'; cmp.setAttribute('aria-label', 'Add ' + d.name + ' to compare'); cmp.dataset.slug = d.slug;
        var heart = h('button', 'pz-cbtn pz-heart', '<i class="fa-regular fa-heart"></i>');
        heart.type = 'button'; heart.title = 'Save this PG'; heart.setAttribute('aria-label', 'Save ' + d.name); heart.dataset.slug = d.slug;
        [cmp, heart].forEach(function (b) { b.addEventListener('mousedown', function (e) { e.stopPropagation(); }); });
        cmp.addEventListener('click', function (e) { e.preventDefault(); e.stopPropagation(); toggleCompare(cardData(a) || d, cmp); });
        heart.addEventListener('click', function (e) { e.preventDefault(); e.stopPropagation(); toggleSave(cardData(a) || d, heart); });
        bar.appendChild(cmp); bar.appendChild(heart); box.appendChild(bar);
        syncButtons();
    }
    function scanCards(root) {
        (root.querySelectorAll ? root.querySelectorAll('a[href*="/pg/"]') : []).forEach(enhance);
    }
    function pop(btn) { if (!btn || reduceMotion) return; btn.classList.remove('pz-pop'); void btn.offsetWidth; btn.classList.add('pz-pop'); }

    function toggleSave(d, btn) {
        var list = load(SAVED), i = list.findIndex(function (x) { return x.slug === d.slug; });
        if (i > -1) { list.splice(i, 1); pzToast('Removed from saved PGs', 'fa-heart-crack'); }
        else { list.unshift({ slug: d.slug, name: d.name, img: d.img, price: d.price, url: d.url }); pzToast('Saved to your list', 'fa-heart', { label: 'View', run: openSaved }); }
        store(SAVED, list.slice(0, 40)); syncButtons(); renderSaved(); pop(btn);
    }
    function toggleCompare(d, btn) {
        var list = load(COMPARE), i = list.findIndex(function (x) { return x.slug === d.slug; });
        if (i > -1) list.splice(i, 1);
        else {
            if (list.length >= 3) { pzToast('You can compare up to 3 PGs at a time', 'fa-circle-exclamation'); return; }
            list.push({ slug: d.slug, name: d.name, img: d.img });
        }
        store(COMPARE, list); syncButtons(); renderTray(); pop(btn);
    }
    function syncButtons() {
        document.querySelectorAll('.pz-heart').forEach(function (b) {
            var on = isSaved(b.dataset.slug);
            b.classList.toggle('pz-active', on);
            b.firstElementChild.className = on ? 'fa-solid fa-heart' : 'fa-regular fa-heart';
            b.title = on ? 'Remove from saved' : 'Save this PG';
            b.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        document.querySelectorAll('.pz-cmp').forEach(function (b) {
            var on = inCompare(b.dataset.slug);
            b.classList.toggle('pz-active', on);
            b.title = on ? 'Remove from compare' : 'Add to compare';
            b.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        var n = load(SAVED).length, badge = $('pzSavedCount');
        if (badge) { var was = badge.classList.contains('pz-on'); badge.textContent = n; badge.classList.toggle('pz-on', n > 0); if (n > 0 && !was) { badge.style.animation = 'none'; void badge.offsetWidth; badge.style.animation = ''; } }
    }

    /* saved drawer */
    var drawer = $('pzSavedDrawer'), overlay = $('pzSavedOverlay');
    function openSaved() { renderSaved(); drawer.classList.add('pz-open'); overlay.classList.add('pz-open'); drawer.setAttribute('aria-hidden', 'false'); document.body.style.overflow = 'hidden'; }
    function closeSaved() { if (!drawer) return; drawer.classList.remove('pz-open'); overlay.classList.remove('pz-open'); drawer.setAttribute('aria-hidden', 'true'); document.body.style.overflow = ''; }
    function renderSaved() {
        var box = $('pzSavedList'); if (!box) return;
        var list = load(SAVED); box.innerHTML = '';
        $('pzSavedFoot').classList.toggle('hidden', !list.length);
        if (!list.length) {
            var empty = h('div', 'text-center py-14 px-6');
            empty.innerHTML = '<div class="w-16 h-16 mx-auto rounded-full bg-coral-50 text-coral-500 text-2xl flex items-center justify-center mb-4"><i class="fa-regular fa-heart"></i></div>';
            empty.appendChild(txt(h('div', 'font-display font-bold text-lg'), 'No saved PGs yet'));
            empty.appendChild(txt(h('p', 'text-sm text-ink-900/60 mt-1'), 'Tap the heart on any PG to keep it here for later.'));
            box.appendChild(empty); return;
        }
        list.forEach(function (p, i) {
            var row = h('div', 'pz-saved-row'); row.style.animationDelay = (i * 50) + 'ms';
            var a = h('a', 'flex gap-3 flex-1 min-w-0'); a.href = p.url;
            var im = h('div', 'w-16 h-16 rounded-xl bg-cream overflow-hidden flex-shrink-0 flex items-center justify-center text-coral-300');
            if (p.img) { var i2 = document.createElement('img'); i2.src = p.img; i2.alt = p.name; i2.className = 'w-full h-full object-cover'; im.appendChild(i2); } else { im.innerHTML = '<i class="fa-solid fa-house"></i>'; }
            a.appendChild(im);
            var mid = h('div', 'min-w-0');
            mid.appendChild(txt(h('div', 'font-semibold text-sm leading-tight line-clamp-2'), p.name));
            if (p.price) mid.appendChild(txt(h('div', 'text-sm font-black text-coral-600 mt-1'), '₹' + p.price + ' /mo'));
            a.appendChild(mid); row.appendChild(a);
            var rm = h('button', 'w-8 h-8 rounded-lg text-ink-900/40 hover:text-rose-600 hover:bg-rose-50 transition self-start', '<i class="fa-solid fa-xmark"></i>');
            rm.type = 'button'; rm.setAttribute('aria-label', 'Remove ' + p.name);
            rm.addEventListener('click', function () {
                row.style.transition = 'opacity .25s, transform .25s'; row.style.opacity = '0'; row.style.transform = 'translateX(30px)';
                setTimeout(function () { store(SAVED, load(SAVED).filter(function (x) { return x.slug !== p.slug; })); syncButtons(); renderSaved(); }, 240);
            });
            row.appendChild(rm); box.appendChild(row);
        });
    }
    window.pzOpenSaved = openSaved;
    if ($('pzSavedBtn')) $('pzSavedBtn').addEventListener('click', openSaved);
    if ($('pzSavedClose')) $('pzSavedClose').addEventListener('click', closeSaved);
    if (overlay) overlay.addEventListener('click', closeSaved);
    if ($('pzSavedClear')) $('pzSavedClear').addEventListener('click', function () { store(SAVED, []); syncButtons(); renderSaved(); pzToast('Saved list cleared', 'fa-trash-can'); });

    /* compare tray */
    var tray = $('pzCompareTray');
    function renderTray() {
        if (!tray) return;
        var list = load(COMPARE), slots = $('pzCompareSlots'); slots.innerHTML = '';
        for (var i = 0; i < 3; i++) {
            var p = list[i], s = h('div', 'pz-slot' + (p ? ' pz-filled' : ''));
            if (p) {
                if (p.img) { var im = document.createElement('img'); im.src = p.img; im.alt = p.name; s.appendChild(im); } else { s.innerHTML = '<i class="fa-solid fa-house"></i>'; }
                (function (slug) {
                    var x = h('button', '', '<i class="fa-solid fa-xmark"></i>'); x.type = 'button'; x.setAttribute('aria-label', 'Remove from compare');
                    x.addEventListener('click', function () { store(COMPARE, load(COMPARE).filter(function (q) { return q.slug !== slug; })); syncButtons(); renderTray(); });
                    s.appendChild(x);
                })(p.slug);
            } else { s.innerHTML = '<i class="fa-solid fa-plus"></i>'; }
            slots.appendChild(s);
        }
        $('pzCompareGo').disabled = list.length < 2;
        $('pzCompareHint').textContent = list.length < 2 ? 'Pick at least 2 PGs to compare side by side.' : 'Ready. Compare rent, amenities and more.';
        tray.classList.toggle('pz-open', list.length > 0);
        document.body.classList.toggle('pz-tray-open', list.length > 0);
    }
    if ($('pzCompareClear')) $('pzCompareClear').addEventListener('click', function () { store(COMPARE, []); syncButtons(); renderTray(); });

    var modal = $('pzCompareModal');
    function closeCompare() { if (!modal) return; modal.classList.remove('pz-open'); modal.setAttribute('aria-hidden', 'true'); document.body.style.overflow = ''; }
    function openCompare() {
        var list = load(COMPARE); if (list.length < 2) return;
        var body = $('pzCompareBody');
        body.innerHTML = '<div class="p-10 text-center text-ink-900/60"><i class="fa-solid fa-spinner fa-spin text-2xl text-coral-500"></i><p class="mt-3 text-sm">Loading comparison...</p></div>';
        modal.classList.add('pz-open'); modal.setAttribute('aria-hidden', 'false'); document.body.style.overflow = 'hidden';
        fetch(searchUrl + '?json=compare&slugs=' + encodeURIComponent(list.map(function (p) { return p.slug; }).join(',')), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (res) { renderCompare(res.data || []); })
            .catch(function () { body.innerHTML = '<div class="p-10 text-center text-rose-600 text-sm">Could not load the comparison. Please try again.</div>'; });
    }
    function renderCompare(data) {
        var body = $('pzCompareBody');
        if (data.length < 2) { body.innerHTML = '<div class="p-10 text-center text-ink-900/60 text-sm">These PGs are no longer available to compare.</div>'; return; }
        var best = Math.min.apply(null, data.map(function (p) { return p.rent_min || Infinity; }));
        var table = h('table', 'pz-cmp-table'), delay = 0;
        function row(label, fn) {
            var tr = document.createElement('tr'); tr.style.animationDelay = (delay += 60) + 'ms';
            tr.appendChild(txt(h('th', 'pz-label'), label));
            data.forEach(function (p) { var td = document.createElement('td'); fn(td, p); tr.appendChild(td); });
            table.appendChild(tr);
        }
        row('PG', function (td, p) {
            var a = h('a', 'block group'); a.href = p.url;
            var im = h('div', 'w-full aspect-[16/10] rounded-xl bg-cream overflow-hidden mb-2 flex items-center justify-center text-coral-300 text-3xl');
            if (p.image) { var i = document.createElement('img'); i.src = p.image; i.alt = p.name; i.className = 'w-full h-full object-cover group-hover:scale-105 transition duration-500'; im.appendChild(i); } else { im.innerHTML = '<i class="fa-solid fa-house"></i>'; }
            a.appendChild(im); a.appendChild(txt(h('div', 'font-display font-bold text-base leading-tight group-hover:text-coral-600 transition'), p.name));
            td.appendChild(a);
        });
        row('Rent / month', function (td, p) {
            var r = p.rent_min ? money(p.rent_min) + (p.rent_max && p.rent_max !== p.rent_min ? ' - ' + money(p.rent_max) : '') : 'On request';
            td.appendChild(txt(h('span', 'font-black text-base'), r));
            if (p.rent_min && p.rent_min === best) td.appendChild(h('span', 'pz-best', '<i class="fa-solid fa-trophy"></i> Lowest'));
        });
        row('Location', function (td, p) { td.textContent = [p.locality, p.city].filter(Boolean).join(', ') || '-'; });
        row('Type', function (td, p) { td.className = 'capitalize'; td.textContent = p.type || '-'; });
        row('For', function (td, p) { td.className = 'capitalize'; td.textContent = p.gender || '-'; });
        row('Food included', function (td, p) { td.innerHTML = p.food_included ? '<i class="fa-solid fa-circle-check text-emerald-500"></i> Yes' : '<i class="fa-solid fa-circle-xmark text-ink-900/30"></i> No'; });
        var counts = {};
        data.forEach(function (p) { (p.amenities || []).forEach(function (a) { counts[a] = (counts[a] || 0) + 1; }); });
        Object.keys(counts).sort(function (a, b) { return counts[b] - counts[a] || a.localeCompare(b); }).slice(0, 14).forEach(function (name) {
            row(name, function (td, p) { td.innerHTML = (p.amenities || []).indexOf(name) > -1 ? '<i class="fa-solid fa-check text-emerald-500"></i>' : '<i class="fa-solid fa-minus text-ink-900/25"></i>'; });
        });
        row('', function (td, p) { var a = h('a', 'inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-coral-500 hover:bg-coral-600 text-white text-xs font-bold transition', 'View details <i class="fa-solid fa-arrow-right"></i>'); a.href = p.url; td.appendChild(a); });
        var body2 = $('pzCompareBody'); body2.innerHTML = ''; body2.appendChild(table);
    }
    if ($('pzCompareGo')) $('pzCompareGo').addEventListener('click', openCompare);
    if ($('pzCompareClose')) $('pzCompareClose').addEventListener('click', closeCompare);
    if ($('pzCompareBackdrop')) $('pzCompareBackdrop').addEventListener('click', closeCompare);

    /* ---------- recently viewed (record) + remembered city ---------- */
    var path = location.pathname;
    var pgMatch = path.match(/^\/pg\/([^\/]+)\/?$/);
    if (pgMatch) {
        var titleEl = document.querySelector('h1'), og = document.querySelector('meta[property="og:image"]');
        if (titleEl) {
            var rec = load(RECENT).filter(function (x) { return x.slug !== pgMatch[1]; });
            rec.unshift({ slug: pgMatch[1], name: titleEl.textContent.trim(), img: og ? og.content : '', url: location.href.split('#')[0] });
            store(RECENT, rec.slice(0, 10));
        }
    }
    var cityMatch = path.match(/^\/pg-in-([a-z-]+)\/?$/);
    if (cityMatch) { try { localStorage.setItem(CITY, cityMatch[1]); } catch (e) {} }

    /* ---------- slider arrows (+ testimonial autoplay) ---------- */
    function enhanceSlider(track) {
        if (track.dataset.pzSlider) return; track.dataset.pzSlider = '1';
        var wrap = h('div', 'pz-slider-wrap'); track.parentNode.insertBefore(wrap, track); wrap.appendChild(track);
        var prev = h('button', 'pz-arrow pz-prev', '<i class="fa-solid fa-chevron-left"></i>'), next = h('button', 'pz-arrow pz-next', '<i class="fa-solid fa-chevron-right"></i>');
        prev.type = next.type = 'button'; prev.setAttribute('aria-label', 'Previous'); next.setAttribute('aria-label', 'Next');
        wrap.appendChild(prev); wrap.appendChild(next);
        var step = function () { return Math.max(track.clientWidth * .8, 200); };
        var update = function () { prev.disabled = track.scrollLeft < 8; next.disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 8; };
        prev.addEventListener('click', function () { track.scrollBy({ left: -step(), behavior: 'smooth' }); });
        next.addEventListener('click', function () { track.scrollBy({ left: step(), behavior: 'smooth' }); });
        track.addEventListener('scroll', function () { requestAnimationFrame(update); }, { passive: true });
        window.addEventListener('resize', update); setTimeout(update, 300);

        var sec = track.closest('section'), heading = sec && sec.querySelector('h2');
        if (heading && /residents say/i.test(heading.textContent) && !reduceMotion) {
            var dots = h('div', 'pz-dots'), cards = track.children.length;
            for (var i = 0; i < Math.min(cards, 8); i++) dots.appendChild(document.createElement('span'));
            wrap.appendChild(dots);
            var paint = function () {
                var idx = Math.round(track.scrollLeft / (track.scrollWidth / cards));
                [].forEach.call(dots.children, function (d, k) { d.classList.toggle('pz-on', k === Math.min(idx, dots.children.length - 1)); });
            };
            track.addEventListener('scroll', function () { requestAnimationFrame(paint); }, { passive: true }); paint();
            var paused = false, timer = setInterval(function () {
                if (paused || document.hidden) return;
                if (track.scrollLeft + track.clientWidth >= track.scrollWidth - 8) track.scrollTo({ left: 0, behavior: 'smooth' });
                else track.scrollBy({ left: track.firstElementChild.getBoundingClientRect().width + 16, behavior: 'smooth' });
            }, 4200);
            ['mouseenter', 'touchstart', 'focusin'].forEach(function (ev) { wrap.addEventListener(ev, function () { paused = true; }, { passive: true }); });
            ['mouseleave', 'touchend', 'focusout'].forEach(function (ev) { wrap.addEventListener(ev, function () { paused = false; }, { passive: true }); });
        }
    }

    /* ---------- lazy image shimmer ---------- */
    function watchImage(img) {
        if (img.dataset.pzWatch || img.complete || img.loading !== 'lazy') return;
        img.dataset.pzWatch = '1';
        var box = img.parentElement; if (box) box.classList.add('pz-shimmer');
        img.classList.add('pz-img-wait');
        var done = function () { img.classList.remove('pz-img-wait'); if (box) box.classList.remove('pz-shimmer'); };
        img.addEventListener('load', done, { once: true }); img.addEventListener('error', done, { once: true });
        setTimeout(done, 6000);
    }

    /* ---------- footer: WhatsApp alerts ---------- */
    var alertForm = $('pzAlertForm');
    if (alertForm) alertForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var msg = $('pzAlertMsg'), btn = $('pzAlertBtn');
        var say = function (t, ok) { msg.textContent = t; msg.className = 'text-xs mt-2 ' + (ok ? 'text-emerald-400' : 'text-rose-300'); };
        var phone = $('pzAlertPhone').value.replace(/\D/g, '').slice(-10);
        if (!/^[6-9][0-9]{9}$/.test(phone)) { say('Please enter a valid 10-digit WhatsApp number.', false); return; }
        var original = btn.innerHTML; btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending';
        var token = document.querySelector('meta[name=csrf-token]');
        fetch('/leads', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token ? token.content : '' },
            body: JSON.stringify({ name: 'WhatsApp alerts subscriber', phone: phone, message: '[WhatsApp alerts] Wants new PG alerts on WhatsApp.', source: 'footer_whatsapp_alerts' })
        }).then(function (r) {
            if (!r.ok) throw new Error('failed');
            alertForm.reset(); say('You are on the list. We will message you when new PGs go live.', true); pzToast('You will get new PG alerts on WhatsApp', 'fa-bell');
        }).catch(function () { say('Something went wrong. Please try again in a moment.', false); })
          .finally(function () { btn.disabled = false; btn.innerHTML = original; });
    });

    /* ---------- boot ---------- */
    function scan(root) {
        scanCards(root);
        (root.querySelectorAll ? root.querySelectorAll('.pzi-slider') : []).forEach(enhanceSlider);
        (root.querySelectorAll ? root.querySelectorAll('img[loading="lazy"]') : []).forEach(watchImage);
    }
    function init() {
        scan(document); syncButtons(); renderTray(); renderSaved();
        new MutationObserver(function (muts) {
            muts.forEach(function (m) { m.addedNodes.forEach(function (n) { if (n.nodeType === 1) { if (n.matches && n.matches('a[href*="/pg/"]')) enhance(n); scan(n); } }); });
        }).observe(document.body, { childList: true, subtree: true });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
    window.addEventListener('storage', function () { syncButtons(); renderTray(); renderSaved(); });
})();
</script>
