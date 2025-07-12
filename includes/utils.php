<?php

/**
 * Check if a string is valid JSON
 * 
 * @param string $string
 * @return bool
 */
function isJson($string) {
    if (!is_string($string)) {
        return false;
    }
    
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

/**
 * Generate a unique filename for uploaded files
 * 
 * @param string $originalName
 * @param string $prefix
 * @return string
 */
function generateUniqueFilename($originalName, $prefix = '') {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $timestamp = time();
    $random = mt_rand(0, 9999);
    return $prefix . '_' . $timestamp . '_' . $random . '.' . $extension;
}

/**
 * Sanitize user input to prevent XSS
 * 
 * @param string $input
 * @return string
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Format a date in a human-readable way
 * 
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'F j, Y') {
    return date($format, strtotime($date));
}
?>
