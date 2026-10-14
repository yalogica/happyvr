<?php
namespace Yalogica\HappyVR\System;

defined('ABSPATH') || exit;

use Yalogica\HappyVR\Models\DataModel;
use Yalogica\HappyVR\Models\AccessModel;

class EmbedBlock {
  public function __construct() {
    add_action('init', [$this, 'init']);
    add_action('admin_post_happyvr_embed_block_preview', [$this, 'renderPreview']);
  }

  public function init() {
    if (!function_exists('register_block_type')) {
      return;
    }

    wp_register_script(
      'happyvr-embed-block-editor',
      HAPPYVR_PLUGIN_URL . 'blocks/gutenberg/virtualtour-embed/index.js',
      ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-api-fetch'],
      HAPPYVR_PLUGIN_VERSION,
      true
    );
    wp_set_script_translations('happyvr-embed-block-editor', 'happyvr');

    wp_localize_script('happyvr-embed-block-editor', 'yalogica_happyvr_gutenberg_block', [
      'optionsUrl'  => esc_url_raw(rest_url(HAPPYVR_PLUGIN_PUBLIC_REST_URL . '/virtualtours/options')),
      'dataUrl'     => esc_url_raw(rest_url(HAPPYVR_PLUGIN_PUBLIC_REST_URL . '/virtualtours/')),
      'previewUrl'  => esc_url_raw(admin_url('admin-post.php?action=happyvr_embed_block_preview')),
      'editUrl'     => esc_url_raw(admin_url('admin.php?page=happyvr&action=edit&id=')),
      'canEdit'     => AccessModel::currentUserCan(AccessModel::ACCESS_RIGHT_EDIT),
    ]);

    register_block_type(HAPPYVR_PLUGIN_PATH . '/blocks/gutenberg/virtualtour-embed', [
      'render_callback' => [$this, 'render'],
    ]);
  }

  public function render($attributes, $content = '', $block = null) {
    $tourId = isset($attributes['tourId']) ? intval($attributes['tourId'], 10) : 0;

    if ($tourId <= 0) {
      return '';
    }

    $item = DataModel::getItemPublic($tourId);

    if (empty($item) || $item['status'] !== 'publish') {
      return '';
    }

    Shortcode::enqueuePlayerAssets($item);

    $shortcodeAtts = ['id' => $tourId];

    if (!empty($attributes['width'])) {
      $shortcodeAtts['width'] = (string) $attributes['width'];
    }
    if (!empty($attributes['height'])) {
      $shortcodeAtts['height'] = (string) $attributes['height'];
    }
    if (!empty($attributes['sceneid'])) {
      $shortcodeAtts['sceneid'] = (string) $attributes['sceneid'];
    }

    return '<div ' . get_block_wrapper_attributes() . '>' . Shortcode::render($shortcodeAtts, $item) . '</div>';
  }

  public function renderPreview() {
    if (!current_user_can('edit_posts')) {
      wp_die(
        esc_html__('Sorry, you are not allowed to access this page.', 'happyvr'),
        esc_html__('Access Denied', 'happyvr'),
        403
      );
    }

    nocache_headers();
    header('X-Robots-Tag: noindex, nofollow');

    $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
    $item = $id > 0 ? DataModel::getItemPublic($id) : null;

    if (empty($item) || $item['status'] !== 'publish') {
      status_header(404);
      die('Virtual tour not found');
    }

    require_once HAPPYVR_PLUGIN_PATH . '/includes/Views/embed.php';
  }
}