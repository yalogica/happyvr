<?php
namespace Yalogica\HappyVR\Models;

defined( 'ABSPATH' ) || exit;

class OptionModel {
    public static function get( $optionKey, $default ) {
        $data = get_option( $optionKey );

        if ( $data === false ) {
            return $default;
        }

        return array_merge( $default, $data );
    }

    public static function set( $optionKey, $default, $data = null ) {
        $current_data = self::get( $optionKey, $default );
        $sanitized_data = $current_data;

        if ($data !== null) {
            foreach ( $data as $key => $value ) {
                if ( array_key_exists( $key, $default ) ) {
                    $sanitized_data[ $key ] = $value;
                }
            }
        }

        if ( get_option( $optionKey ) === false ) {
            return add_option( $optionKey, $sanitized_data, '', 'no' );
        } else {
            $old_value = get_option( $optionKey );
            if ( $old_value === $sanitized_data ) {
                return true;
            } else {
                return update_option( $optionKey, $sanitized_data );
            }
        }
    }
}