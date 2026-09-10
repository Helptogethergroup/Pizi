<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        $blogs = [
            [
                'title' => 'How to Choose the Right PG in Delhi NCR — A 2026 Guide',
                'excerpt' => 'Choosing a PG isn\'t just about price. Here\'s a checklist that will save you regret six months in.',
                'cover_image' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=1200&q=80',
                'content' => "<p>Finding the right Paying Guest accommodation in Delhi NCR is one of the most consequential housing decisions a young professional or student makes. Here are the seven things people wish they had checked before signing.</p><h2>1. Visit at the time you'll actually be there</h2><p>That gorgeous-looking PG at noon is a different animal at 11 PM when neighbours start dumpster-diving. Visit twice — once in the day, once at night.</p><h2>2. Check water pressure on the top floor</h2><p>Most Delhi PGs have rooftop tanks; if you're on the top floor in summer, water pressure can be a daily war.</p><h2>3. Ask current residents, not the owner</h2><p>The most honest reviews come from people who already live there. Ask them about food quality, owner attitude, and how complaints are handled.</p><h2>4. Negotiate the deposit, not the rent</h2><p>Owners rarely budge on monthly rent, but most will reduce a 2-month deposit to 1.5 if you sign a longer lease.</p><h2>5. Verify safety features in writing</h2><p>For girls' PGs especially: ask for written confirmation of CCTV operating hours, gate timings, and warden contact.</p><h2>6. Read the food menu, then taste it</h2><p>Insist on a sample meal during the visit. Stale roti is forever.</p><h2>7. Walk to the metro</h2><p>Distance to the nearest metro station compounds daily over 12 months. A 10-minute walk vs 25 minutes is 90 hours of your life per year.</p>",
                'is_published' => true,
                'published_at' => now()->subDays(3),
            ],
            [
                'title' => 'Best Localities for PGs in Noida — Sector-Wise Breakdown',
                'excerpt' => 'Sector 18 vs 62 vs 137 — where you live in Noida changes how you live. Here\'s an honest comparison.',
                'cover_image' => 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=1200&q=80',
                'content' => "<p>Noida is unusual — its PG market is sharply segmented by sector. Choose right and you save 90 minutes a day.</p><h2>Sector 18 — The convenience king</h2><p>Closest to DLF Mall, metro, and most weekend plans. PG rents are 10-15% higher than other sectors, but you save it all back in cab fare.</p><h2>Sector 62 — Tech professional hub</h2><p>If you work at any of the IT companies in Sector 62 itself or 63, this is the easy answer. Walk-to-office radius. Slightly older PGs but rents are very reasonable.</p><h2>Sector 137 — The new generation</h2><p>Newest construction, modern coliving spaces, mostly occupied by 22-28 year olds in tech and consulting. Aqua line metro makes it more accessible than people realize.</p><h2>Sector 16 / 15 — Old Noida charm</h2><p>Older neighbourhood with traditional PGs, lower rents (~₹6-9k), good for students and budget-conscious starters.</p><h2>Greater Noida West (Noida Extension)</h2><p>The cheapest end of the market — rents from ₹4,500. Trade-off: 45-60 min commute to South Delhi or central Noida.</p>",
                'is_published' => true,
                'published_at' => now()->subDays(8),
            ],
            [
                'title' => 'Girls\' PG Safety Checklist — What to Verify Before Moving In',
                'excerpt' => 'Beyond CCTV and gate timings — the questions that actually predict whether a girls\' PG is safe.',
                'cover_image' => 'https://images.unsplash.com/photo-1493809842364-78817add7ffb?w=1200&q=80',
                'content' => "<p>For a young woman moving alone to Delhi or Noida, choosing a safe PG is non-negotiable. Beyond the standard checklist, here are the indicators that genuinely matter.</p><h2>Female warden, available after 8 PM</h2><p>Ask if the warden actually lives on-site, not just visits. The difference is everything.</p><h2>Visitor logbook</h2><p>Good PGs maintain a visitor entry register with phone numbers. Skip any PG that doesn't.</p><h2>Police verification</h2><p>Ask if the PG conducts police verification of all male staff (cooks, cleaners, guards). Reputable PGs will show you the paperwork.</p><h2>Floor segregation</h2><p>In unisex PGs, ensure floors are gender-segregated and the women's floor has a separate access.</p><h2>Other residents are women you'd actually live with</h2><p>Spend 5 minutes in the common area on your visit. The vibe of the existing residents tells you what your life will look like.</p>",
                'is_published' => true,
                'published_at' => now()->subDays(15),
            ],
        ];

        foreach ($blogs as $b) {
            Blog::create([
                ...$b,
                'slug' => Str::slug($b['title']),
                'author_id' => $admin?->id,
                'meta_title' => $b['title'] . ' | PGFind',
                'meta_description' => $b['excerpt'],
            ]);
        }
    }
}
