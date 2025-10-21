<?php
namespace Yalogica\HappyVR\System;

use Yalogica\HappyVR\Models\DataModel;
use Yalogica\HappyVR\Models\Utils;

defined( 'ABSPATH' ) || exit;

class Shortcode {
    public function __construct() {
        add_action( 'init', [ $this, 'init' ] );
    }

    public function init() {
        add_shortcode( HAPPYVR_SHORTCODE_NAME, [ $this, 'run' ] );
    }

    public function run( $attributes = [] ) {
        wp_enqueue_style( 'happyvr-shortcode', HAPPYVR_PLUGIN_URL . 'assets/components/player/index.css', [], HAPPYVR_PLUGIN_VERSION, 'all' );
        wp_enqueue_script( 'happyvr-shortcode', HAPPYVR_PLUGIN_URL . 'assets/components/player/index.js', [], HAPPYVR_PLUGIN_VERSION, false );
        wp_localize_script( 'happyvr-shortcode', 'happyvr_global', $this->getGlobals() );

        return self::render( $attributes );
    }

    public static function render( $attributes = [] ) {
        // Normalize keys and apply defaults
        $attributes = array_change_key_case( (array) $attributes, CASE_LOWER );
        $defaults   = [
            'id'     => null,
            'width'  => '100%',
            'height' => '400px',
            'class'  => '',
        ];
        $atts = shortcode_atts( $defaults, $attributes );

        // Sanitize and extract values
        $id = intval( $atts['id'], 10 );
        $width = Utils::sanitizeCssLength( $atts['width'], null );
        $height = Utils::sanitizeCssLength( $atts['height'], null );
        $class = sanitize_html_class( $atts['class'] );

        $styleEmpty = "padding:10px;background:#ffeed0;color:#774e09;border:1px dashed #cf8507;border-radius:5px;font-size:14px;";

        if ( empty( $id ) ) {
            return "<div style='{$styleEmpty}'>" . esc_html__( 'HappyVR: Virtual tour ID is not defined.', 'happyvr' ) . "</div>";
        }

        $item = DataModel::getItem( $id );
        if ( empty( $item ) ) {
            return "<div style='{$styleEmpty}'>" . esc_html__( 'HappyVR: Virtual tour not found.', 'happyvr' ) . "</div>";
        }

        if ( $item['status'] !== 'publish' ) {
             return;
        }
        
        $classes = array_filter( [
            'happyvr-player',
            $class,
        ] );

        // // Build inline CSS dynamically
        $css_rules = [];

        if ( $width )  $css_rules[] = "width: {$width};";
        if ( $height ) $css_rules[] = "height: {$height};";

        $config = $item['config'] ?? [];

        if ( ! empty( $config['viewerStyle'] ) ) {
            if ( ! empty( $config['viewerStyle']['background'] ) ) {
                $css_rules[] = "background-color: {$config['viewerStyle']['background']};";
                
            }
            if ( ! empty( $config['viewerStyle']['radius'] ) ) {
                $css_rules[] = "border-radius: {$config['viewerStyle']['radius']}px;";
            }
        }

        $id = esc_attr( $id );
        $classes = esc_attr( implode( ' ', $classes ) );

        // Generate style tag if CSS rules exist
        $style_tag = '';
        if ( ! empty( $css_rules ) ) {
            $selector = ".happyvr-player[data-id='{$id}']";
            $css = $selector . " {\n\t" . implode( "\n\t", $css_rules ) . "\n}";
            $style_tag = '<style>' . PHP_EOL . $css . PHP_EOL . '</style>' . PHP_EOL;
        }
        
        $output  = $style_tag;
        $output .= "<div data-id='{$id}' class='{$classes}' role='application' aria-label='HappyVR Virtual Tour Player'>";
        $output .= "</div>";

        return $output;
    }

    private function getGlobals() {
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