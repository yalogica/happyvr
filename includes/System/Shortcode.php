<?php
namespace Yalogica\HappyVR\System;

defined('ABSPATH') || exit;

use Yalogica\HappyVR\Models\DataModel;
use Yalogica\HappyVR\Lib\Utils;

class Shortcode {
  public function __construct() {
    add_action('init', [$this, 'init']);
  }

  public function init() {
    add_shortcode(HAPPYVR_SHORTCODE_NAME, [$this, 'run']);
  }

  public static function enqueuePlayerAssets(?array $item) {
    $isLegacy = self::isLegacyItem($item);
    $handle   = $isLegacy ? 'happyvr-shortcode-legacy' : 'happyvr-shortcode';
    $base     = $isLegacy ? 'assets/legacy/player/' : 'assets/components/player/';

    wp_enqueue_style($handle, HAPPYVR_PLUGIN_URL . $base . 'index.css', [], HAPPYVR_PLUGIN_VERSION, 'all');
    wp_enqueue_script($handle, HAPPYVR_PLUGIN_URL . $base . 'index.js', [], HAPPYVR_PLUGIN_VERSION, true);
    wp_localize_script($handle, 'yalogica_happyvr_global', self::getGlobals());
  }

  public function run( $attributes = [] ) {
    $atts = $this->normalizeAttributes($attributes);

    $item = $atts['id'] ? DataModel::getItemPublic($atts['id']) : null;

    if (empty($item) || $item['status'] !== 'publish') {
      return self::render($attributes, $item);
    }

    self::enqueuePlayerAssets($item);
    return self::render($attributes, $item);
  }

  public static function render( $attributes = [], $item = null ) {
    $atts = self::normalizeAttributes($attributes);

    $styleEmpty = "padding:10px;background:#ffeed0;color:#774e09;border:1px dashed #cf8507;border-radius:5px;font-size:14px;";

    if (empty($atts['id'])) {
      return "<div style='{$styleEmpty}'>" . esc_html__('HappyVR: Virtual tour ID not defined.', 'happyvr') . "</div>";
    }

    if ($item === null) {
      $item = DataModel::getItemPublic($atts['id']);
    }

    if (empty($item)) {
      return "<div style='{$styleEmpty}'>" . esc_html__('HappyVR: Virtual tour not found.', 'happyvr') . "</div>";
    }

    if ($item['status'] !== 'publish') {
      return;
    }

    $classes = array_filter([
      'happyvr-player',
      $atts['class'],
    ]);

    $css_rules = [];

    if ($atts['width'] !== null)  $css_rules[] = "width: {$atts['width']};";
    if ($atts['height'] !== null) $css_rules[] = "height: {$atts['height']};";

    $config = $item['config'] ?? [];


    $viewerStyle = isset($config['viewerStyle']) && is_array($config['viewerStyle']) ? $config['viewerStyle'] : [];
    $viewerBackground = Utils::sanitizeColor($viewerStyle['background'] ?? '');
    if ($viewerBackground !== '') {
      $css_rules[] = "background-color: {$viewerBackground};";
    }
    if (isset($viewerStyle['radius']) && is_numeric($viewerStyle['radius'])) {
      $radius = max(0.0, (float) $viewerStyle['radius']);
      $css_rules[] = "border-radius: {$radius}px;";
    }

    $viewport = isset($config['viewport']) && is_array($config['viewport']) ? $config['viewport'] : [];
    $background = isset($viewport['background']) && is_array($viewport['background']) ? $viewport['background'] : [];

    if (!empty($background['enabled'])) {
      $fill = Utils::sanitizeColor($background['fill'] ?? '');

      $imageUrl = '';
      if (isset($background['image']['url']) && is_string($background['image']['url'])) {
        $imageUrl = esc_url($background['image']['url']);
      }

      if ($fill !== '') {
        $css_rules[] = "background-color: {$fill};";
      }
      if ($imageUrl !== '') {
        $css_rules[] = "background-image: url(\"{$imageUrl}\");";
        $css_rules[] = 'background-position: center;';
        $css_rules[] = 'background-repeat: no-repeat;';
        $css_rules[] = 'background-size: cover;';
      }
    }

    $id = esc_attr($atts['id']);
    $classes = esc_attr(implode(' ', $classes));

    $style_tag = '';
    if (!empty($css_rules)) {
      $selector = ".happyvr-player[data-id='{$id}']";
      $css = $selector . " {\n\t" . implode("\n\t", $css_rules) . "\n}";
      $style_tag = '<style>' . PHP_EOL . $css . PHP_EOL . '</style>' . PHP_EOL;
    }

    $legacyAttr = self::isLegacyItem($item) ? "data-legacy" : '';
    $sceneIdAttr = $atts['sceneid'] ? "data-scene-id='{$atts['sceneid']}'" : '';
    $classAttr = $classes ? "class='{$classes}'" : '';
    
    $output  = $style_tag;
    $output .= "<div data-id='{$id}' {$sceneIdAttr} {$legacyAttr} {$classAttr} role='application' aria-label='HappyVR Virtual Tour Player'>";
    $output .= "</div>";

    return $output;
  }
  
  public static function isLegacyItem(?array $item): bool {
    $version = (string)($item['config']['version'] ?? '');
    return $version === '' || version_compare($version, '2.0', '<');
  }

  private static function normalizeAttributes($attributes = []): array {
    $attributes = array_change_key_case((array) $attributes, CASE_LOWER);

    $defaults = [
      'id'      => null,
      'width'   => '100%',
      'height'  => '400px',
      'class'   => '',
      'sceneid' => ''
    ];
    $atts = shortcode_atts($defaults, $attributes);

    return [
      'id'      => intval($atts['id'], 10),
      'width'   => Utils::sanitizeCssLength($atts['width'], null),
      'height'  => Utils::sanitizeCssLength($atts['height'], null),
      'class'   => Utils::sanitizeHtmlClasses($atts['class']),
      'sceneid' => sanitize_key($atts['sceneid']),
    ];
  }

  private static function getGlobals() {
    $upload_dir = wp_upload_dir();

    $data = [
      'site_url' => get_option('siteurl'),
      'icons_url' => HAPPYVR_PLUGIN_URL . 'assets/icons/',
      'upload_url' => $upload_dir['baseurl'] . '/happyvr/',
    ];

    return [
      'data' => $data,
      'api' => [
        'url' => esc_url_raw( rest_url( HAPPYVR_PLUGIN_PUBLIC_REST_URL ) )
      ]
    ];
  }
}