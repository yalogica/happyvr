<?php
namespace Yalogica\HappyVR\System;

defined('ABSPATH') || exit;

use Yalogica\HappyVR\Models\DataModel;

class Embed {
  public function __construct() {
    add_action('init', [$this, 'addRewriteRules']);
    add_filter('query_vars', [$this, 'addQueryVars']);
    add_action('template_redirect', [$this, 'handleEmbedRequest'], 9);
  }

  public static function activate() {
    $embed = new self();
    $embed->addRewriteRules();
    flush_rewrite_rules();
  }

  public function addRewriteRules() {
    add_rewrite_rule(
      '^happyvr/embed/([0-9]+)/?$',
      'index.php?happyvr_embed=$matches[1]',
      'top'
    );
  }

  public function addQueryVars($query_vars) {
    $query_vars[] = 'happyvr_embed';
    return $query_vars;
  }

  public function handleEmbedRequest() {
    $embed_id = get_query_var('happyvr_embed');
    if (!empty($embed_id)) {
      $this->renderEmbed($embed_id);
      exit;
    }
  }

  private function renderEmbed($embed_id) {
    $embed_id = intval($embed_id);
    $item = DataModel::getItemPublic($embed_id);

    if (empty($item)) {
      status_header(404);
      wp_redirect(home_url(), 302);
      exit;
    }
    
    if ($item['status'] !== 'publish' || !($item['config']['embed']['enabled'] ?? false)) {
      status_header(404);
      wp_redirect(home_url(), 302);
      exit;
    }

    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: max-age=60, must-revalidate');

    require_once HAPPYVR_PLUGIN_PATH . '/includes/Views/embed.php';
  }
};