<?php
// /includes/functions.php

/**
 * Calculates distance between two GPS points in meters using the Haversine formula.
 */
function getDistance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371000;
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earth_radius * $c;
}

/**
 * Checks if a user is within the allowed geofence radius.
 */
function isWithinGeofence($userLat, $userLng, $siteLat, $siteLng, $radius) {
    return getDistance($userLat, $userLng, $siteLat, $siteLng) <= $radius;
}

/**
 * Human-readable distance formatter.
 */
function formatDistance($meters) {
    return ($meters >= 1000) 
        ? round($meters / 1000, 2) . ' km' 
        : round($meters) . ' m';
}

/**
 * Sanitize output for safe HTML rendering.
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function isActive($path) {
    $currentPath = $_SERVER['REQUEST_URI'];
    return (strpos($currentPath, $path) !== false) ? 'bg-blue-50 text-blue-700' : 'text-slate-600';
}

function getDistanceBetweenPoints($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // Meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earthRadius * $c;
}