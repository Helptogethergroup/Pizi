<?php

namespace App\Services;

class GeoService
{
    /**
     * Calculate distance between two GPS coordinates in meters.
     * Uses Haversine formula.
     */
    public function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        $earthRadius = 6371000; // meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return (int) round($earthRadius * $c);
    }

    /**
     * Check if user is within geo-fence radius of a target point.
     */
    public function isWithinFence(
        float $userLat, float $userLng,
        float $targetLat, float $targetLng,
        int $radiusMeters = 100
    ): bool {
        return $this->distanceMeters($userLat, $userLng, $targetLat, $targetLng) <= $radiusMeters;
    }

    /**
     * Format distance for display.
     */
    public function formatDistance(int $meters): string
    {
        if ($meters < 1000) {
            return $meters . 'm';
        }
        return round($meters / 1000, 1) . 'km';
    }
}