<?php
namespace Yalogica\HappyVR\Models;

defined( 'ABSPATH' ) || exit;

class Utils {
    /**
     * Sanitizes a CSS length, size, or dimension value.
     *
     * Accepts:
     * - Numeric values with units: 100px, 50%, 1.5em, etc.
     * - Zero without unit: 0
     * - CSS keywords: auto, initial, fit-content, etc.
     * - calc() expressions (basic, safe support)
     *
     * Returns a sanitized string or a default fallback.
     *
     * @param mixed  $value The input value to sanitize.
     * @param string $default Optional. Default value if sanitization fails. Default 'auto'.
     * @return string Sanitized CSS length or default value.
     */
    public static function sanitizeCssLength( $value, $default = 'auto' ) {
        // Supported CSS units
        $units = ['px', 'em', 'rem', '%', 'vh', 'vw', 'vmin', 'vmax', 'cm', 'mm', 'in', 'pt', 'pc'];

        // CSS keywords allowed for dimension properties
        $allowed_keywords = [
            'auto', 'initial', 'inherit', 'unset',
            'min-content', 'max-content', 'fit-content',
            'content',
        ];

        // Convert input to string and trim whitespace
        $value = trim( (string) $value );

        // 1. Allow exact keyword matches
        if ( in_array( $value, $allowed_keywords, true ) ) {
            return $value;
        }

        // 2. Allow numeric values only if zero (0 is valid without unit)
        if ( is_numeric( $value ) ) {
            return (float) $value == 0 ? '0' : $default;
        }

        // 3. Match values with units: e.g. 100px, 50%, 2.5rem
        $pattern = '/^(\d*\.?\d+)(' . implode('|', array_map('preg_quote', $units)) . ')$/i';
        if ( preg_match( $pattern, $value, $matches ) ) {
            $number = (float) $matches[1];
            $unit   = strtolower( $matches[2] );
            return $number . $unit;
        }

        // 4. Support calc() expressions with basic safety checks
        if ( str_starts_with( strtolower( $value ), 'calc(' ) && str_ends_with( $value, ')' ) ) {
            // Remove all disallowed characters
            $clean = preg_replace( '/[^0-9%pxremvhvwcmminptc\.\+\-\*\/\(\)]/i', '', $value );
            // Ensure no characters were removed and length is reasonable
            if ( $clean === $value && strlen( $value ) < 150 ) {
                return $value;
            }
        }

        // 5. If nothing matches, return the default fallback
        return $default;
    }
}