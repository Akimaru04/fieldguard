<?php
// /includes/functions.php

function getDistance(float $lat1, float $lon1, float $lat2, float $lon2): float {
    $earth_radius = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earth_radius * $c;
}

function isWithinGeofence(float $userLat, float $userLng, float $siteLat, float $siteLng, int $radius = 100): bool {
    return getDistance($userLat, $userLng, $siteLat, $siteLng) <= $radius;
}

function formatDistance(float $meters): string {
    return ($meters >= 1000) 
        ? round($meters / 1000, 2) . ' km' 
        : round($meters) . ' m';
}

function e(string $string): string {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function isActive(string $path): string {
    return (strpos($_SERVER['REQUEST_URI'], $path) === 0) ? 'bg-blue-600 text-white' : 'text-slate-600';
}