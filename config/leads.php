<?php

return [
    /*
     * Which leads an owner is shown, by AREA. One word in .env (LEADS_AREA_MODE), no deploy needed
     * beyond `php artisan config:clear`:
     *
     *  balanced (default)
     *      - a lead for one of the owner's PGs                                -> shown
     *      - a lead for someone else's PG, or one that asks for a locality,
     *        only if the owner has a PG in that locality                      -> shown
     *      - a lead that gives only a city                                    -> shown to owners in that city
     *      - a lead whose area nobody knows                                   -> held back (a telecaller adds the area)
     *
     *  strict
     *      same as balanced, but a city-only lead is shown only after the team has spoken
     *      to the person (lead type "verified"). Fewer leads, more relevant ones.
     *
     *  off
     *      the old behaviour: any lead of the owner's city, and every lead of unknown area.
     */
    'area_mode' => env('LEADS_AREA_MODE', 'balanced'),

    // An owner can report an unlocked lead as not genuine within this many hours of unlocking it.
    'report_hours' => (int) env('LEADS_REPORT_HOURS', 72),
];
