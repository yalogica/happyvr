<?php

/**
 * Plugin Name:       HappyVR - Virtual Tour Creator, 360 Panorama & Real Estate Viewer
 * Plugin URI:        https://yalogica.com/happyvr
 * Description:       Create interactive virtual tours with stunning 360° panoramas. Easily add scenes, interactive hotspot and controls, perfect for real estate, education, and business presentations.
 * Version:           1.1.3
 * Requires at least: 6.3
 * Requires PHP:      8.2
 * Author:            Yalogica
 * Author URI:        https://yalogica.com
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       happyvr
 * Domain Path:       /languages
 */
namespace Yalogica\HappyVR;

defined( 'ABSPATH' ) || exit;
define( 'HAPPYVR_PLUGIN_NAME', 'happyvr' );
define( 'HAPPYVR_PLUGIN_VERSION', '1.1.3' );
define( 'HAPPYVR_PLUGIN_BASE_NAME', plugin_basename( __FILE__ ) );
define( 'HAPPYVR_PLUGIN_PATH', __DIR__ );
define( 'HAPPYVR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'HAPPYVR_PLUGIN_REST_URL', 'happyvr/v1' );
define( 'HAPPYVR_PLUGIN_PUBLIC_REST_URL', 'happyvr/public/v1' );
define( 'HAPPYVR_SHORTCODE_NAME', 'happyvr' );
define( 'HAPPYVR_DOCS_URL', 'https://yalogica.com/docs/happyvr/' );
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/autoload.php';
if ( function_exists( 'happyvr_fs' ) ) {
    happyvr_fs()->set_basename( false, __FILE__ );
} else {
    // Create a helper function for easy SDK access.
    function happyvr_fs() {
        global $happyvr_fs;
        if ( !isset( $happyvr_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/vendor/freemius/wordpress-sdk/start.php';
            $happyvr_fs = fs_dynamic_init( [
                'id'               => '21242',
                'type'             => 'plugin',
                'slug'             => 'happyvr',
                'premium_slug'     => 'happyvr-pro',
                'public_key'       => 'pk_e9a830df091290edaa2b007d9e6ff',
                'is_premium'       => false,
                'premium_suffix'   => 'Pro',
                'has_addons'       => false,
                'has_paid_plans'   => true,
                'is_org_compliant' => true,
                'trial'            => [
                    'days'               => 7,
                    'is_require_payment' => false,
                ],
                'menu'             => [
                    'slug'    => 'happyvr',
                    'support' => false,
                    'contact' => true,
                ],
                'is_live'          => true,
            ] );
        }
        return $happyvr_fs;
    }

    // Init Freemius.
    happyvr_fs();
    // Signal that SDK was initiated.
    do_action( 'happyvr_fs_loaded' );
    ( new Plugin() )->run();
}