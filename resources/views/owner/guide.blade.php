@php $guideSections = config('dashboard_guide.sections'); @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pizi Dashboard Guide</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { padding: 0 !important; }
        }
    </style>
@include('partials.emoji-icons')
</head>
<body class="bg-gray-50 p-6">

    <div class="max-w-3xl mx-auto">

        <div class="no-print flex items-center justify-between mb-6">
            <div class="flex items-center gap-2">
                <button onclick="setLang('en')" id="btnEn" class="px-4 py-2 rounded-lg text-sm font-bold bg-gray-900 text-white">English</button>
                <button onclick="setLang('hi')" id="btnHi" class="px-4 py-2 rounded-lg text-sm font-bold bg-gray-200 text-gray-700">हिंदी</button>
            </div>
            <button onclick="window.print()" class="px-5 py-2.5 bg-orange-500 hover:bg-orange-600 text-white rounded-lg font-bold text-sm">
                <i class="fa-solid fa-print fa-fw"></i> Print / Save as PDF
            </button>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-8">
            <div class="flex items-center gap-3 mb-1">
                <div class="w-10 h-10 rounded-xl bg-orange-500 text-white flex items-center justify-center font-black">P</div>
                <div class="font-black text-2xl">Pizi</div>
            </div>
            <h1 class="font-black text-3xl mt-6 lang-en">Owner Dashboard Guide</h1>
            <h1 class="font-black text-3xl mt-6 lang-hi hidden">ओनर डैशबोर्ड गाइड</h1>
            <p class="text-gray-500 mt-2 lang-en">A quick overview of everything you can do from your Pizi dashboard.</p>
            <p class="text-gray-500 mt-2 lang-hi hidden">Pizi dashboard se aap kya-kya kar sakte hain, uska ek quick overview.</p>

            <div class="mt-8 space-y-6">
                @foreach($guideSections as $s)
                    <div class="flex gap-4 pb-6 border-b border-gray-100 last:border-0">
                        <div class="text-3xl">{{ $s['icon'] }}</div>
                        <div>
                            <div class="font-bold text-lg lang-en">{{ $s['title_en'] }}</div>
                            <div class="font-bold text-lg lang-hi hidden">{{ $s['title_hi'] }}</div>
                            <div class="text-gray-600 mt-1 lang-en">{{ $s['body_en'] }}</div>
                            <div class="text-gray-600 mt-1 lang-hi hidden">{{ $s['body_hi'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="text-xs text-gray-400 mt-8 pt-6 border-t border-gray-100">
                Generated on {{ now()->format('d M Y') }} · This guide updates automatically as new features are added.
            </p>
        </div>
    </div>

    <script>
    function setLang(lang) {
        document.querySelectorAll('.lang-en').forEach(el => el.classList.toggle('hidden', lang !== 'en'));
        document.querySelectorAll('.lang-hi').forEach(el => el.classList.toggle('hidden', lang !== 'hi'));
        document.getElementById('btnEn').className = 'px-4 py-2 rounded-lg text-sm font-bold ' + (lang === 'en' ? 'bg-gray-900 text-white' : 'bg-gray-200 text-gray-700');
        document.getElementById('btnHi').className = 'px-4 py-2 rounded-lg text-sm font-bold ' + (lang === 'hi' ? 'bg-gray-900 text-white' : 'bg-gray-200 text-gray-700');
    }
    </script>

</body>
</html>