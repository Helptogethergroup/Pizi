<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The SEO expert's meta title and description for the city pages. A city page
 * shows cities.meta_title / meta_description when they are filled in, so the
 * text has to be written here as well as in the view.
 */
return new class extends Migration
{
    public function up(): void
    {
        $text = [
            'delhi' => ['PG in Delhi – Verified Boys & Girls PGs | Pizi',
                'Find verified PGs in Delhi for boys, girls and working professionals. Compare affordable rents, amenities and locations with zero brokerage and free site visits on Pizi.'],
            'noida' => ['PG in Noida – Verified Boys & Girls PGs | Pizi',
                'Find verified PGs in Noida for boys, girls and working professionals. Compare rents, amenities and locations with zero brokerage and free site visits on Pizi.'],
            'gurgaon' => ['PG in Gurgaon – Verified Boys & Girls PGs | Pizi',
                'Find verified PGs in Gurgaon for boys, girls and working professionals. Compare rents, amenities and locations with zero brokerage and free site visits on Pizi.'],
            'ghaziabad' => ['PG in Ghaziabad – Verified Boys & Girls PGs | Pizi',
                'Find verified PGs in Ghaziabad for boys, girls and working professionals. Compare rents, amenities and locations with zero brokerage and free site visits on Pizi.'],
            'faridabad' => ['PG in Faridabad – Verified Boys & Girls PGs | Pizi',
                'Find verified PGs in Faridabad for boys, girls and working professionals. Compare rents, amenities and locations with zero brokerage and free site visits on Pizi.'],
        ];

        foreach ($text as $slug => [$title, $description]) {
            DB::table('cities')->where('slug', $slug)->update([
                'meta_title' => $title,
                'meta_description' => $description,
            ]);
        }
    }

    public function down(): void
    {
        // the old text is not kept; edit the cities' meta fields to change it again
    }
};
