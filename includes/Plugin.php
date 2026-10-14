<?php

namespace Yalogica\HappyVR;

defined('ABSPATH') || exit;

class Plugin
{
  public const OPTION_VERSION = '_happyvr_version';
  public const OPTION_DATE = '_happyvr_date';

  private static $instance = null;

  private function __construct() {}

  public static function instance()
  {
    if (self::$instance === null) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public static function run()
  {
    $plugin = self::instance();
    $plugin->boot();
  }

  private function boot()
  {
    $this->updateVersion();
    $this->updateDate();
    $this->load();
  }

  private function updateVersion()
  {
    $stored = (string) get_option(self::OPTION_VERSION, '');
    if ($stored === '' || version_compare($stored, HAPPYVR_PLUGIN_VERSION, '<')) {
      update_option(self::OPTION_VERSION, HAPPYVR_PLUGIN_VERSION, false);
      add_action('init', 'flush_rewrite_rules', 99);
    }
  }

  private function updateDate()
  {
    $date = (int) get_option(self::OPTION_DATE, 0);
    if ($date === 0) {
      update_option(self::OPTION_DATE, time(), false);
    }
  }

  public function load()
  {
    new Rest\Routes();
    new System\Shortcode();
    new System\EmbedBlock();
    new System\Embed();
    new System\Admin();
  }
}
