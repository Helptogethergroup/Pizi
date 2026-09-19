@unless(isset($fa) && $fa === false)
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
@endunless
<script>
// Swaps any emoji that reaches the DOM (database values, controller messages,
// JS-rendered content) for the matching Font Awesome icon from config/emoji_icons.php.
(function () {
    var MAP = @json(config('emoji_icons', []));
    var RE = /(\p{Extended_Pictographic}(?:️|‍\p{Extended_Pictographic})*️?)/gu;
    var SKIP = { SCRIPT: 1, STYLE: 1, TEXTAREA: 1, OPTION: 1, SELECT: 1, TITLE: 1, INPUT: 1, NOSCRIPT: 1 };

    function spec(e) {
        var k = e.replace(/️/g, '');
        return MAP[k] || MAP[Array.from(k)[0]] || null;
    }
    function icon(s) {
        var p = s.split('|'), i = document.createElement('i');
        i.className = 'fa-solid ' + p[0] + ' fa-fw';
        if (p[1]) i.style.color = p[1];
        return i;
    }
    function skip(el) {
        for (; el && el.nodeType === 1; el = el.parentNode) {
            if (SKIP[el.nodeName] || el.isContentEditable || el.hasAttribute('data-no-icons')) return true;
        }
        return false;
    }
    function convertText(t) {
        var v = t.nodeValue;
        RE.lastIndex = 0;
        if (!RE.test(v) || skip(t.parentNode)) return;
        RE.lastIndex = 0;
        var frag = document.createDocumentFragment(), last = 0, m, changed = false;
        while ((m = RE.exec(v))) {
            var s = spec(m[0]);
            if (!s) continue;
            if (m.index > last) frag.appendChild(document.createTextNode(v.slice(last, m.index)));
            frag.appendChild(icon(s));
            last = m.index + m[0].length;
            changed = true;
        }
        if (!changed) return;
        if (last < v.length) frag.appendChild(document.createTextNode(v.slice(last)));
        t.parentNode.replaceChild(frag, t);
    }
    function walk(root) {
        if (root.nodeType === 3) return convertText(root);
        if (root.nodeType !== 1 || SKIP[root.nodeName]) return;
        var w = document.createTreeWalker(root, NodeFilter.SHOW_TEXT), n, list = [];
        while ((n = w.nextNode())) list.push(n);
        list.forEach(convertText);
    }

    new MutationObserver(function (muts) {
        muts.forEach(function (m) {
            if (m.type === 'characterData') convertText(m.target);
            else m.addedNodes.forEach(walk);
        });
    }).observe(document.documentElement, { childList: true, subtree: true, characterData: true });
    document.addEventListener('DOMContentLoaded', function () { walk(document.body); });
})();
</script>
