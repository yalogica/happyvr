<?php
/**
 * Plugin Name:       HappyVR - Virtual Tour Builder & 360 Panorama Viewer
 * Plugin URI:        https://yalogica.com/happyvr
 * Description:       Create interactive virtual tours with stunning 360° panoramas. Easily add scenes, interactive hotspot and controls, perfect for real estate, education, and business presentations.
 * Version:           2.6.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Yalogica
 * Author URI:        https://yalogica.com
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       happyvr
 * Domain Path:       /languages
 */

namespace Yalogica\HappyVR;

defined('ABSPATH') || exit;

define('HAPPYVR_PLUGIN_NAME', 'happyvr');
define('HAPPYVR_PLUGIN_VERSION', '2.6.1');
define('HAPPYVR_PLUGIN_BASE_NAME', plugin_basename(__FILE__));
define('HAPPYVR_PLUGIN_PATH', __DIR__);
define('HAPPYVR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HAPPYVR_PLUGIN_REST_URL', 'happyvr/v1');
define('HAPPYVR_PLUGIN_PUBLIC_REST_URL', 'happyvr/public/v1');
define('HAPPYVR_SHORTCODE_NAME', 'happyvr');
define('HAPPYVR_DOCS_URL', 'https://yalogica.com/docs/happyvr/');

require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/includes/autoload.php');
require_once(__DIR__ . '/blocks/divi/virtualtour-embed/bootstrap.php');

register_activation_hook(__FILE__, ['Yalogica\HappyVR\System\Embed', 'activate']);

if (function_exists('happyvr_fs')) {
  happyvr_fs()->set_basename(false, __FILE__);
} else {
  // Create a helper function for easy SDK access.
  function happyvr_fs()
  {
    global $happyvr_fs;

    if (! isset($happyvr_fs)) {
      // Include Freemius SDK.
      require_once dirname(__FILE__) . '/vendor/freemius/wordpress-sdk/start.php';
      $happyvr_fs = fs_dynamic_init([
        'id'                  => '21242',
        'type'                => 'plugin',
        'slug'                => 'happyvr',
        'premium_slug'        => 'happyvr-pro',
        'public_key'          => 'pk_e9a830df091290edaa2b007d9e6ff',
        'premium_suffix'      => 'Pro',
        'is_premium'          => false,
        'has_addons'          => false,
        'has_paid_plans'      => true,
        'is_org_compliant'    => true,
        'trial' => [
          'days' => 7,
          'is_require_payment' => false,
        ],
        'menu' => [
          'slug'    => 'happyvr',
          'support' => false,
          'contact' => true,
        ],
        'is_live'             => true,
      ]);
    }

    $happyvr_fs->add_filter('pricing/show_annual_in_monthly', '__return_false');
    $happyvr_fs->add_filter('plugin_icon', function () {
      return dirname(__FILE__) . '/assets/img/happyvr.png';
    });

    return $happyvr_fs;
  }

  // Init Freemius.
  happyvr_fs();
  // Signal that SDK was initiated.
  do_action('happyvr_fs_loaded');

  Plugin::run();
}