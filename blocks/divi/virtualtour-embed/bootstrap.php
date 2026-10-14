<?php
namespace Yalogica\HappyVR\Blocks\Divi;

defined('ABSPATH') || exit;

add_action('divi_module_library_modules_dependency_tree', function ($tree) {
  require_once HAPPYVR_PLUGIN_PATH . '/blocks/divi/virtualtour-embed/module.php';
  $tree->add_dependency(new VirtualTourEmbedModule());
});

add_action('divi_visual_builder_assets_before_enqueue_scripts', function () {
  if (!function_exists('et_builder_d5_enabled') || !et_builder_d5_enabled()
    || !function_exists('et_core_is_fb_enabled') || !et_core_is_fb_enabled()) {
    return;
  }

  \ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build([
    'name'    => 'happyvr-divi-virtualtour-embed',
    'version' => HAPPYVR_PLUGIN_VERSION,
    'script'  => [
      'src'                => HAPPYVR_PLUGIN_URL . 'blocks/divi/virtualtour-embed/index.js',
      'deps'               => ['divi-module-library', 'divi-rest', 'divi-vendor-wp-hooks', 'divi-field-library'],
      'enqueue_top_window' => false,
      'enqueue_app_window' => true,
    ],
  ]);

  $metadata = json_decode((string) file_get_contents(HAPPYVR_PLUGIN_PATH . '/blocks/divi/virtualtour-embed/module.json'), true);

  if (!is_array($metadata)) {
    error_log('HappyVR: module.json not readable at ' . HAPPYVR_PLUGIN_PATH . '/blocks/divi/virtualtour-embed/module.json');
    return;
  }

  $config = (string) wp_json_encode([
    'metadata'   => $metadata,
    'previewUrl' => esc_url_raw(admin_url('admin-post.php?action=happyvr_embed_block_preview')),
    'editUrl'    => esc_url_raw(admin_url('admin.php?page=happyvr&action=edit&id=')),
  ]);

  add_action('wp_enqueue_scripts', function () use ($config) {
    echo '<script>window.happyvrDivi = ' . $config . ';</script>' . "\n";
  }, 999);
});