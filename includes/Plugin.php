<?php
namespace Yalogica\HappyVR;

defined( 'ABSPATH' ) || exit;

class Plugin {
    public const OPTION_VERSION = '_happyvr_version';
    public const OPTION_DATE = '_happyvr_date';

    public function run() {
        $this->updateVersion();
        $this->updateDate();
        $this->load();
    }

    private function updateVersion() {
        if ( version_compare( get_option( self::OPTION_VERSION ), HAPPYVR_PLUGIN_VERSION, '<' ) ) {
            update_option( self::OPTION_VERSION, HAPPYVR_PLUGIN_VERSION );
        }
    }

    private function updateDate() {
        if ( !get_option( self::OPTION_DATE ) ) {
            update_option( self::OPTION_DATE, time() );
        }
    }

    public function load() {
        new Rest\Routes();
        new System\Shortcode();
        new System\Admin();
    }
}