<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MapLinkController extends Controller
{
    /**
     * Resolve any Google Maps link — short (share.google, maps.app.goo.gl)
     * or full — into {lat, lng}. Short links redirect server-side before
     * the real URL (with coordinates) appears, which a browser can't follow
     * itself due to CORS, so this has to happen here.
     */
    public function resolve(Request $request)
    {
        $data = $request->validate([
            'url' => 'required|string|max:2000',
        ]);

        $url = trim($data['url']);

        try {
            // Follow redirects server-side (short links like share.google
            // and maps.app.goo.gl bounce through several hops before landing
            // on the real maps.google.com URL that has the coordinates).
            $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->timeout(10)
                ->withOptions([
                    'allow_redirects' => ['max' => 10],
                    // Local XAMPP's cURL/OpenSSL build fails to validate Google's
                    // cert chain even with a fresh CA bundle — confirmed local-only
                    // quirk (works fine on live). Skip verification only in local.
                    'verify' => !app()->environment('local'),
                ])
                ->get($url);

            $finalUrl = (string) $response->effectiveUri();
            $body = $response->body();

            $coords = $this->extractCoords($finalUrl) ?? $this->extractCoords($body);

            if (!$coords) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not find coordinates in that link. Try dragging the pin manually instead.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'lat' => $coords[0],
                'lng' => $coords[1],
            ]);
        } catch (\Exception $e) {
            \Log::warning('Map link resolve failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Could not open that link. Try dragging the pin manually instead.',
            ], 422);
        }
    }

    /**
     * Try every coordinate pattern Google Maps URLs commonly use.
     */
    private function extractCoords(string $text): ?array
    {
        // https://www.google.com/maps/@28.6139,77.2090,17z
        if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $text, $m) && $this->isValidIndiaCoord($m[1], $m[2])) {
            return [(float) $m[1], (float) $m[2]];
        }

        // .../place/.../data=!...!3d28.6139!4d77.2090
        if (preg_match('/!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/', $text, $m) && $this->isValidIndiaCoord($m[1], $m[2])) {
            return [(float) $m[1], (float) $m[2]];
        }

        // https://www.google.com/maps?q=28.6139,77.2090
        if (preg_match('/[?&]q=(-?\d+\.\d+),(-?\d+\.\d+)/', $text, $m) && $this->isValidIndiaCoord($m[1], $m[2])) {
            return [(float) $m[1], (float) $m[2]];
        }

        // Bare "28.6139, 77.2090" anywhere — riskiest pattern (matches any
        // two decimals in a page's body), so only for a genuine Maps page,
        // never for a business/search-listing page's unrelated numbers.
        if (preg_match('/(-?\d{1,2}\.\d{4,}),\s*(-?\d{2,3}\.\d{4,})/', $text, $m) && $this->isValidIndiaCoord($m[1], $m[2])) {
            return [(float) $m[1], (float) $m[2]];
        }

        return null;
    }

    /**
     * Reject anything outside India's rough bounding box — catches garbage
     * matches (e.g. from a Business/Search-listing page's unrelated numbers)
     * before they get saved as a property's real location.
     */
    private function isValidIndiaCoord($lat, $lng): bool
    {
        $lat = (float) $lat;
        $lng = (float) $lng;
        return $lat >= 6 && $lat <= 38 && $lng >= 68 && $lng <= 98;
    }
}
