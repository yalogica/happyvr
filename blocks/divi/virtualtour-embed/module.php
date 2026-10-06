<?php
namespace Yalogica\HappyVR\Blocks\Divi;

defined('ABSPATH') || exit;

use Yalogica\HappyVR\Models\DataModel;
use Yalogica\HappyVR\System\Shortcode;

use ET\Builder\Framework\Utility\HTMLUtility;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Module;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;
use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;

class VirtualTourEmbedModule implements DependencyInterface {
  public function load() {
    if (!class_exists(\ET\Builder\Packages\ModuleLibrary\ModuleRegistration::class)) {
      return;
    }
    add_action('init', [self::class, 'register_module']);
  }

  public static function register_module() {
    ModuleRegistration::register_module(
      __DIR__,
      ['render_callback' => [self::class, 'render_callback']]
    );
  }

  public static function render_callback($attrs, $content, $block, $elements) {
    $get = fn(string $path): string => $attrs[$path]['innerContent']['desktop']['value'] ?? '';

    $tourId  = (int) $get('tourId');
    $width   = $get('width') ?: '100%';
    $height  = $get('height') ?: '400px';
    $sceneId = $get('sceneId');

    $item = $tourId > 0 ? DataModel::getItemPublic($tourId) : null;

    if (!empty($item) && $item['status'] === 'publish') {
      Shortcode::enqueuePlayerAssets($item);
      $tour_html = Shortcode::render(
        ['id' => $tourId, 'width' => $width, 'height' => $height, 'sceneid' => $sceneId],
        $item
      );
    } else {
      $tour_html = HTMLUtility::render([
        'tag' => 'div',
        'attributes' => ['class' => 'happyvr_virtualtour_embed__empty'],
        'childrenSanitizer' => 'esc_html',
        'children' => __('HappyVR: virtual tour not found.', 'happyvr'),
      ]);
    }

    return Module::render([
      'orderIndex'          => $block->parsed_block['orderIndex'],
      'storeInstance'       => $block->parsed_block['storeInstance'],
      'id'                  => $block->parsed_block['id'],
      'name'                => $block->block_type->name,
      'moduleCategory'      => $block->block_type->category,
      'attrs'               => $attrs,
      'elements'            => $elements,
      'moduleClassName'     => 'happyvr_virtualtour_embed',
      'classnamesFunction'  => [self::class, 'module_classnames'],
      'stylesComponent'     => [self::class, 'module_styles'],
      'scriptDataComponent' => [self::class, 'module_script_data'],
      'children'            => [
        $elements->style_components(['attrName' => 'module']),
        HTMLUtility::render([
          'tag'               => 'div',
          'attributes'        => ['class' => 'et_pb_module_inner'],
          'childrenSanitizer' => 'et_core_esc_previously',
          'children'          => $tour_html,
        ]),
        $content,
      ],
    ]);
  }

  public static function module_styles($args) {
    Style::add([
      'id'            => $args['id'],
      'name'          => $args['name'],
      'orderIndex'    => $args['orderIndex'],
      'storeInstance' => $args['storeInstance'],
      'styles'        => [
        $args['elements']->style(['attrName' => 'module']),
      ],
    ]);
  }

  public static function module_script_data($args) {
    $args['elements']->script_data(['attrName' => 'module']);
  }

  public static function module_classnames($args) {
    $args['classnamesInstance']->add(
      ElementClassnames::classnames(['attrs' => $args['attrs']['module']['decoration'] ?? []])
    );
  }
}