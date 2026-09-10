{{--
    ========================================================================
    STEP 13: PROPERTY CARD RATING
    Search/listing cards me ⭐ dikhane ke liye. Apne property card blade me
    (jahan card ka content hai) ye paste kar — $p tera loop variable hona chahiye
    (agar $property hai to neeche $p ko $property se replace kar de).
    ========================================================================
--}}

@if (($p->rating_count ?? 0) > 0)
    <div class="flex items-center gap-1.5 mt-1">
        <div class="text-amber-400 text-sm leading-none">
            @for ($i = 1; $i <= 5; $i++){!! $i <= round($p->rating_avg) ? '&#9733;' : '<span class="text-gray-300">&#9733;</span>' !!}@endfor
        </div>
        <span class="text-sm font-semibold text-gray-800">{{ number_format($p->rating_avg, 1) }}</span>
        <span class="text-xs text-gray-400">({{ $p->rating_count }})</span>
    </div>
@else
    <div class="text-xs text-gray-400 mt-1">No reviews yet</div>
@endif

{{--
    ------------------------------------------------------------------------
    SORT + FILTER (search page controller me add kar):

    // Sort by rating (URL: ?sort=rating)
    $query->when($request->sort === 'rating', fn ($q) =>
        $q->orderByDesc('rating_avg')->orderByDesc('rating_count'));

    // Filter "4★ & up" (URL: ?min_rating=4)
    $query->when($request->filled('min_rating'), fn ($q) =>
        $q->where('rating_avg', '>=', (float) $request->min_rating));

    SEARCH PAGE UI me ye options daal:

    <select name="sort" onchange="this.form.submit()">
        <option value="">Sort: Default</option>
        <option value="rating" @selected(request('sort')==='rating')>Highest Rated</option>
    </select>

    <label><input type="checkbox" name="min_rating" value="4" onchange="this.form.submit()"
        @checked(request('min_rating')==='4')> 4&#9733; &amp; up</label>
    ------------------------------------------------------------------------
--}}
