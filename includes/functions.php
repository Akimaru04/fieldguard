<?php
// /includes/functions.php

/**
 * Calculates distance between two GPS points in meters using the Haversine formula.
 * $earth_radius = 6,371,000 meters.
 */
function getDistance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371000;
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat / 2) * sin($dLat / 2) + 
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
         
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earth_radius * $c;
}

/**
 * Checks if a user is within the allowed geofence radius of a site.
 */
function isWithinGeofence($userLat, $userLng, $siteLat, $siteLng, $radius) {
    return getDistance($userLat, $userLng, $siteLat, $siteLng) <= $radius;
}

/**
 * Sanitize output for safe HTML rendering.
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}