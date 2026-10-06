<?php

namespace Yalogica\HappyVR\System;

defined('ABSPATH') || exit;

use Yalogica\HappyVR\Models\DataModel;
use Yalogica\HappyVR\Models\AccessModel;
use Yalogica\HappyVR\Models\SettingsModel;

class Admin
{
  private const NOTICE_META_KEY = '_happyvr_admin_notice_hide_until';
  private const NOTICE_NONCE_ACTION = 'happyvr_dismiss_admin_notice';

  public function __construct()
  {
    add_action('init', [$this, 'init']);
    add_action('admin_init', [$this, 'redirect']);
  }

  public function init()
  {
    $this->registerPostType();

    if (!is_admin()) {
      return;
    }

    if (AccessModel::currentUserCan(AccessModel::ACCESS_RIGHT_VIEW)) {
      add_action('admin_menu', [$this, 'generalMenu']);
      add_filter('submenu_file', [$this, 'highlightAdminMenu'], 10, 2);

      add_action('admin_notices', [$this, 'adminNotices']);
      add_action('admin_enqueue_scripts', [$this, 'adminNoticesScripts']);
      add_action('wp_ajax_happyvr_dismiss_admin_notice', [$this, 'adminNoticesAjaxDismiss']);
    } else if (current_user_can('administrator')) {
      add_action('admin_menu', [$this, 'adminMenu']);
    }
  }

  public function registerPostType()
  {
    $labels = [
      'name' => esc_html__('HappyVR Virtual Tours', 'happyvr'),
      'singular_name' => esc_html__('HappyVR Virtual Tour', 'happyvr'),
    ];
    $args = [
      'labels' => $labels,
      'public' => false,  // removes the permalink option
      'publicly_queryable' => false, // remove preview page
      'exclude_from_search' => true,
      'show_ui' => false,
      'show_in_menu' => false,
      'show_in_rest' => false,
      'rewrite' => false,
      'query_var' => false,
      'capability_type' => 'post',
      'has_archive' => false,
      'hierarchical' => false,
      'supports' => ['title', 'editor']
    ];

    register_post_type(DataModel::POST_TYPE, $args);
  }

  public function generalMenu()
  {
    add_menu_page(esc_html__('HappyVR', 'happyvr'), esc_html__('HappyVR', 'happyvr'), 'read', 'happyvr', [$this, 'showPage'], 'data:image/svg+xml;base64,' . base64_encode(SettingsModel::MENU_ICON));
    add_submenu_page('happyvr', esc_html__('HappyVR', 'happyvr'), esc_html__('All Virtual Tours', 'happyvr'), 'read', 'happyvr', [$this, 'showPage']);
    
    $showSubmenu = DataModel::getTotalCount() < 5;
    
    if ($showSubmenu && (AccessModel::currentUserCan([AccessModel::ACCESS_RIGHT_CREATE, AccessModel::ACCESS_RIGHT_EDIT]))) {
      add_submenu_page('happyvr', esc_html__('HappyVR', 'happyvr'), esc_html__('Add New', 'happyvr'), 'read', 'happyvr&action=new', [$this, 'showPage']);
    }
    
    add_submenu_page('happyvr', esc_html__('HappyVR', 'happyvr'), esc_html__('How To', 'happyvr'), 'read', 'happyvr-guide', [$this, 'showPageGuide']);
    add_submenu_page('happyvr', esc_html__('HappyVR', 'happyvr'), esc_html__('Settings', 'happyvr'), 'manage_options', 'happyvr-settings', [$this, 'showPage']);
  }

  public function adminMenu()
  {
    add_menu_page(esc_html__('HappyVR', 'happyvr'), esc_html__('HappyVR', 'happyvr'), 'manage_options', 'happyvr-settings', [$this, 'showPage'], 'data:image/svg+xml;base64,' . base64_encode(SettingsModel::MENU_ICON));
  }

  public function highlightAdminMenu($submenu_file, $parent_file)
  {
     $page = sanitize_key(filter_input(INPUT_GET, 'page', FILTER_DEFAULT));
    if (in_array($page, ['happyvr'], true)) {
      $action = sanitize_key(filter_input(INPUT_GET, 'action', FILTER_DEFAULT));
      if (in_array($action, ['new'], true)) {
        $submenu_file = "happyvr&action={$action}";
      } elseif ($action === 'settings') {
        $submenu_file = 'happyvr-settings';
      }
    }
    return $submenu_file;
  }

  public function redirect()
  {
    $page = sanitize_key(filter_input(INPUT_GET, 'page', FILTER_DEFAULT));

    if ($page === 'happyvr-settings') {
      $redirect_url = add_query_arg(['page' => 'happyvr', 'action' => 'settings'], admin_url('admin.php'));
      wp_redirect($redirect_url, 301);
      exit;
    }

    if ($page === 'happyvr') {
      $action = sanitize_key(filter_input(INPUT_GET, 'action', FILTER_DEFAULT));

      if ($action === '') {
        $redirect_url = add_query_arg(['page' => 'happyvr', 'action' => 'list'], admin_url('admin.php'));
        wp_redirect($redirect_url);
        exit;
      } elseif (!in_array($action, ['list', 'new', 'edit', 'settings'], true)) {
        $redirect_url = admin_url();
        wp_redirect($redirect_url, 303);
        exit;
      } elseif ($action === 'list') {
        if ((!AccessModel::currentUserCan([AccessModel::ACCESS_RIGHT_VIEW]))) {
          wp_die(
            esc_html__('Sorry, you are not allowed to access this page.', 'happyvr'),
            esc_html__('Access Denied', 'happyvr'),
            403
          );
        }
      } elseif ($action === 'new') {
        if ((!AccessModel::currentUserCan([AccessModel::ACCESS_RIGHT_CREATE, AccessModel::ACCESS_RIGHT_EDIT]))) {
          wp_die(
            esc_html__('Sorry, you are not allowed to access this page.', 'happyvr'),
            esc_html__('Access Denied', 'happyvr'),
            403
          );
        }

        $id = DataModel::createItemAutoDraft();

        if (!$id) {
          wp_die(
            esc_html__('Failed to create a new virtual tour. Please try again or check your server logs for more details.', 'happyvr'),
            esc_html__('Server Error', 'happyvr'),
            500
          );
        }

        $redirect_url = add_query_arg(['page' => 'happyvr', 'action' => 'edit', 'id' => $id], admin_url('admin.php'));
        wp_redirect($redirect_url);
        exit;
      } elseif ($action === 'edit') {
        $id = sanitize_key(filter_input(INPUT_GET, 'id', FILTER_DEFAULT));
        if (!$id || !DataModel::currentUserCan($id, AccessModel::ACCESS_RIGHT_EDIT)) {
          wp_die(
            esc_html__('Sorry, you are not allowed to access this page.', 'happyvr'),
            esc_html__('Access Denied', 'happyvr'),
            403
          );
        }
      } elseif ($action === 'settings') {
        if (!current_user_can('manage_options')) {
          wp_die(
            esc_html__('Sorry, you are not allowed to manage plugin settings. Only administrators can access this page.', 'happyvr'),
            esc_html__('Access Denied', 'happyvr'),
            403
          );
        }
      }
    }
  }

  public function adminNotices()
  {
    if (!current_user_can('manage_options')) {
      return;
    }

    $page = sanitize_key($_GET['page'] ?? '');
    if (!in_array($page, ['happyvr', 'happyvr-guide'], true)) {
      return;
    }

    $user_id = get_current_user_id();
    $hide_until = (int) get_user_meta($user_id, self::NOTICE_META_KEY, true);
    if ($hide_until > time()) {
      return;
    }

    $upgrade_url = esc_url(DataModel::getUpgradeUrl());
    $free_vs_pro_url = esc_url(DataModel::getPluginUrl());

    echo '<div class="notice notice-info is-dismissible happyvr-admin-notice" style="margin-bottom: 10px; background-image: linear-gradient(to bottom, rgb(87 162 255 / 20%) 0%, #fff 100%);">';

    echo '<h3>';
    echo esc_html__('Thank you for using HappyVR! ✨', 'happyvr');
    echo '</h3>';

    echo '<p>' . wp_kses(__('The free version lets you build up to 5 virtual tours with 5 scenes and 5 layers per scene. Go further with <b>HappyVR PRO</b>:', 'happyvr'), ['b' => []]) . '</p>';
    echo '<ul style="margin: 0 0 0.75em 1.25em; list-style: none;">';
    echo '<li>✅ ' . esc_html__('Unlimited virtual tours, scenes, and layers.', 'happyvr') . '</li>';
    echo '<li>✅ ' . esc_html__('Custom CSS, custom JavaScript, and Handlebars templates for full design control.', 'happyvr') . '</li>';
    echo '<li>✅ ' . esc_html__('Extra widgets, extra icon sets, and custom attributes.', 'happyvr') . '</li>';
    echo '<li>✅ ' . esc_html__('Background sounds, voice guidance, and cinematic scene transitions.', 'happyvr') . '</li>';
    echo '<li>✅ ' . esc_html__('Global settings and user permissions to manage your whole team.', 'happyvr') . '</li>';
    echo '</ul>';

    echo '<p style="margin-top:25px">';
    echo '<a class="button button-primary" href="' . esc_url($upgrade_url) . '">' . esc_html__('Get HappyVR PRO', 'happyvr') . '</a> ';
    echo '<a class="button button-secondary" href="' . esc_url($free_vs_pro_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Learn More', 'happyvr') . '</a>';
    echo '</p>';

    echo '</div>';
  }

  public function adminNoticesScripts($hook_suffix)
  {
    $page = sanitize_key($_GET['page'] ?? '');
    if (!in_array($page, ['happyvr', 'happyvr-guide'], true)) {
      return;
    }

    wp_enqueue_script('happyvr-admin-notice', HAPPYVR_PLUGIN_URL . 'assets/js/admin-notice.js', ['jquery'], HAPPYVR_PLUGIN_VERSION, true);
    wp_localize_script('happyvr-admin-notice', 'yalogica_happyvr_global_admin_notice', [
      'ajaxurl' => admin_url('admin-ajax.php'),
      'nonce'   => wp_create_nonce(self::NOTICE_NONCE_ACTION),
    ]);
  }

  public function adminNoticesAjaxDismiss()
  {
    check_ajax_referer(self::NOTICE_NONCE_ACTION, 'nonce');

    update_user_meta(
      get_current_user_id(),
      self::NOTICE_META_KEY,
      time() + 30 * DAY_IN_SECONDS
    );

    wp_send_json_success();
  }

  public function showPage()
  {
    $page = sanitize_key(filter_input(INPUT_GET, 'page', FILTER_DEFAULT));

    if ($page !== 'happyvr') {
      return;
    }

    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    wp_enqueue_style('happyvr-style', HAPPYVR_PLUGIN_URL . 'assets/css/style.css', [], HAPPYVR_PLUGIN_VERSION, 'all');

    wp_enqueue_style('happyvr-admin', HAPPYVR_PLUGIN_URL . 'assets/components/admin/index.css', [], HAPPYVR_PLUGIN_VERSION, 'all');
    wp_enqueue_script('happyvr-admin', HAPPYVR_PLUGIN_URL . 'assets/components/admin/index.js', [], HAPPYVR_PLUGIN_VERSION, false);

    wp_localize_script('happyvr-admin', 'yalogica_happyvr_global', $this->getGlobals($id));

    wp_enqueue_media();

    require_once(HAPPYVR_PLUGIN_PATH . '/includes/Views/admin.php');
  }

  public function showPageGuide()
  {
    $page = sanitize_key(filter_input(INPUT_GET, 'page', FILTER_DEFAULT));

    if ($page !== 'happyvr-guide') {
      return;
    }

    wp_enqueue_style('happyvr-style', HAPPYVR_PLUGIN_URL . 'assets/css/style.css', [], HAPPYVR_PLUGIN_VERSION, 'all');

    require_once(HAPPYVR_PLUGIN_PATH . '/includes/Views/guide.php');
  }

  private function getGlobals($id = null)
  {
    $upload_dir = wp_upload_dir();
    

    $data = [
      'item_id' => $id,
      'site_url' => get_option('siteurl'),
      'plugin_url' => HAPPYVR_PLUGIN_URL,
      'icons_url' => HAPPYVR_PLUGIN_URL . 'assets/icons/',
      'upload_url' => $upload_dir['baseurl'] . '/happyvr/',
      'upgrade_url' => DataModel::getUpgradeUrl(),
      'is_pretty_permalinks' => get_option('permalink_structure') !== '',
      'rights' => [
        'view' => AccessModel::currentUserCan(AccessModel::ACCESS_RIGHT_VIEW),
        'create' => AccessModel::currentUserCan(AccessModel::ACCESS_RIGHT_CREATE),
        'edit' => AccessModel::currentUserCan(AccessModel::ACCESS_RIGHT_EDIT),
        'delete' => AccessModel::currentUserCan(AccessModel::ACCESS_RIGHT_DELETE),
        'publish' => AccessModel::currentUserCan(AccessModel::ACCESS_RIGHT_PUBLISH)
      ],
    ];

    return [
      'data' => $data,
      'api' => [
        'nonce' => wp_create_nonce('wp_rest'),
        'url' => esc_url_raw(rest_url(HAPPYVR_PLUGIN_REST_URL))
      ]
    ];
  }
}
