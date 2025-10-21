<?php
namespace Yalogica\HappyVR\System;

use Yalogica\HappyVR\Models\DataModel;
use Yalogica\HappyVR\Models\AccessModel;
use Yalogica\HappyVR\Models\FreemiusModel;
use Yalogica\HappyVR\Models\SettingsModel;

defined( 'ABSPATH' ) || exit;

class Admin {
    public function __construct() {
        add_action( 'init', [ $this, 'init' ] );
        add_action( 'admin_init', [ $this, 'redirect' ] );
    }

    public function init() {
        $this->registerPostType();

        if ( AccessModel::currentUserCan( AccessModel::ACCESS_RIGHT_VIEW ) ) {
            add_action( 'admin_menu', [ $this, 'generalMenu' ] );
            add_filter( 'submenu_file', [ $this, 'highlightAdminMenu' ], 10, 2 );
            add_action( 'in_admin_header', [ $this, 'removeNotices' ] );
        } else if ( current_user_can( 'administrator' ) ) {
            add_action( 'admin_menu', [ $this, 'adminMenu' ] );
        }
    }

    public function registerPostType() {
        $labels = [
            'name' => esc_html__( 'HappyVR Virtual Tours', 'happyvr' ),
            'singular_name' => esc_html__( 'HappyVR Virtual Tour', 'happyvr' ),
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
            'supports' => [ 'title' ]
        ];

        register_post_type( DataModel::POST_TYPE, $args );
    }

    public function generalMenu() {
        add_menu_page( esc_html__( 'HappyVR', 'happyvr' ), esc_html__( 'HappyVR', 'happyvr' ), 'read', 'happyvr', [ $this, 'showPage' ], 'data:image/svg+xml;base64,' . base64_encode( SettingsModel::MENU_ICON ) );
        add_submenu_page( 'happyvr', esc_html__( 'HappyVR', 'happyvr' ), esc_html__( 'All Virtual Tours', 'happyvr' ), 'read', 'happyvr', [ $this, 'showPage' ] );
        if ( ( AccessModel::currentUserCan( [ AccessModel::ACCESS_RIGHT_CREATE, AccessModel::ACCESS_RIGHT_EDIT ] ) ) ) {
            add_submenu_page( 'happyvr', esc_html__( 'HappyVR', 'happyvr' ), esc_html__( 'Add New Virtual Tour', 'happyvr' ), 'read', 'happyvr&action=new', [ $this, 'showPage' ] );
        }
        add_submenu_page( 'happyvr', esc_html__( 'HappyVR', 'happyvr' ), esc_html__( 'Settings', 'happyvr' ), 'manage_options', 'happyvr-settings', [ $this, 'showPage' ] );
    }

    public function adminMenu() {
        add_menu_page( esc_html__( 'HappyVR', 'happyvr' ), esc_html__( 'HappyVR', 'happyvr' ), 'manage_options', 'happyvr-settings', [ $this, 'showPage' ], 'data:image/svg+xml;base64,' . base64_encode( SettingsModel::MENU_ICON ) ); 
    }

    public function highlightAdminMenu( $submenu_file, $parent_file ) {
        $page = sanitize_key( filter_input( INPUT_GET, 'page', FILTER_DEFAULT ) );
        if ( in_array( $page, [ 'happyvr' ], true ) ) {
            $action = sanitize_key( filter_input( INPUT_GET, 'action', FILTER_DEFAULT ) );
            if ( in_array( $action, [ 'new' ], true ) ) {
                $submenu_file = "happyvr&action={$action}";
            }
        }
        return $submenu_file;
    }

    public function redirect() {
        $page = sanitize_key( filter_input( INPUT_GET, 'page', FILTER_DEFAULT ) );

        if ( $page === 'happyvr-settings' ) {
            $redirect_url = add_query_arg( [ 'page' => 'happyvr', 'action' => 'settings' ], admin_url( 'admin.php' ) );
            wp_redirect( $redirect_url, 301 );
            exit;
        }

        if ( $page === 'happyvr' ) {
            $action = sanitize_key( filter_input( INPUT_GET, 'action', FILTER_DEFAULT ) );

            if ( $action === '' ) {
                $redirect_url = add_query_arg( [ 'page' => 'happyvr', 'action' => 'list' ], admin_url( 'admin.php' ) );
                wp_redirect( $redirect_url );
                exit;
            } elseif ( !in_array( $action, [ 'list', 'new', 'edit', 'settings' ], true ) ) {
                $redirect_url = admin_url();
                wp_redirect( $redirect_url, 303 );
                exit;
            } elseif ( $action === 'list' ) {
                if ( ( !AccessModel::currentUserCan( [ AccessModel::ACCESS_RIGHT_VIEW ] ) ) ) {
                    wp_die( 
                        esc_html__( 'Sorry, you are not allowed to access this page.', 'happyvr' ),
                        esc_html__( 'Access Denied', 'happyvr' ),
                        403 
                    );
                }
            } elseif ( $action === 'new' ) {
                if ( ( !AccessModel::currentUserCan( [ AccessModel::ACCESS_RIGHT_CREATE, AccessModel::ACCESS_RIGHT_EDIT ] ) ) ) {
                    wp_die( 
                        esc_html__( 'Sorry, you are not allowed to access this page.', 'happyvr' ),
                        esc_html__( 'Access Denied', 'happyvr' ),
                        403 
                    );
                }

                $id = DataModel::createItemAutoDraft();

                if ( ! $id ) {
                    wp_die(
                        esc_html__( 'Failed to create a new virtual tour. Please try again or check your server logs for more details.', 'happyvr' ),
                        esc_html__( 'Server Error', 'happyvr' ),
                        500
                    );
                }

                $redirect_url = add_query_arg( [ 'page' => 'happyvr', 'action' => 'edit', 'id' => $id ], admin_url( 'admin.php' ) );
                wp_redirect( $redirect_url );
                exit;
            } elseif ( $action === 'edit' ) {
                $id = sanitize_key( filter_input( INPUT_GET, 'id', FILTER_DEFAULT ) );
                if ( !$id || !DataModel::currentUserCan( $id, AccessModel::ACCESS_RIGHT_EDIT ) ) {
                    wp_die( 
                        esc_html__( 'Sorry, you are not allowed to access this page.', 'happyvr' ),
                        esc_html__( 'Access Denied', 'happyvr' ),
                        403 
                    );
                }
            } elseif ( $action === 'settings' ) {
                if ( ! current_user_can( 'manage_options' ) ) {
                    wp_die(
                        esc_html__( 'Sorry, you are not allowed to manage plugin settings. Only administrators can access this page.', 'happyvr' ),
                        esc_html__( 'Access Denied', 'happyvr' ),
                        403
                    );
                }
            }
        }
    }

    public function removeNotices() {
        $page = sanitize_key( filter_input( INPUT_GET, 'page', FILTER_DEFAULT ) );
        if ( in_array( $page, [ 'happyvr', 'happyvr-settings' ], true ) ) {
            remove_all_actions( 'admin_notices' );
            remove_all_actions( 'all_admin_notices' );
        }
    }

    public function showPage() {
        $page = sanitize_key( filter_input( INPUT_GET, 'page', FILTER_DEFAULT ) );
        
        if ( $page !== 'happyvr' ) {
            return;
        }

        $id = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT );

        wp_enqueue_style( 'happyvr-style', HAPPYVR_PLUGIN_URL . 'assets/css/style.css', [], HAPPYVR_PLUGIN_VERSION, 'all' );

        wp_enqueue_style( 'happyvr-admin', HAPPYVR_PLUGIN_URL . 'assets/components/admin/index.css', [], HAPPYVR_PLUGIN_VERSION, 'all' );
        wp_enqueue_script( 'happyvr-admin', HAPPYVR_PLUGIN_URL . 'assets/components/admin/index.js', [], HAPPYVR_PLUGIN_VERSION, false );

        wp_localize_script( 'happyvr-admin', 'happyvr_global', $this->getGlobals( $id )  );

        wp_enqueue_media();

        require_once( HAPPYVR_PLUGIN_PATH . '/includes/Views/admin.php' );
        
    }

    private function getGlobals( $id = null ) {
        $upload_dir = wp_upload_dir();

        $data = [
            'item_id' => $id,
            'site_url' => get_option('siteurl'),
            'icons_url' => HAPPYVR_PLUGIN_URL . 'assets/icons/',
            'upload_url' => $upload_dir['baseurl'] . '/happyvr/',
            'upgrade_url' => FreemiusModel::getUpgradeUrl(),
            'is_licensed' => FreemiusModel::isLicensed(),
            'rights' => [ 
                'view' => AccessModel::currentUserCan( AccessModel::ACCESS_RIGHT_VIEW ),
                'create' => AccessModel::currentUserCan( AccessModel::ACCESS_RIGHT_CREATE ),
                'edit' => AccessModel::currentUserCan( AccessModel::ACCESS_RIGHT_EDIT ),
                'delete' => AccessModel::currentUserCan( AccessModel::ACCESS_RIGHT_DELETE ),
                'publish' => AccessModel::currentUserCan( AccessModel::ACCESS_RIGHT_PUBLISH ) 
            ]
        ];

        return [
            'data' => $data,
            'api' => [
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'url' => esc_url_raw( rest_url( HAPPYVR_PLUGIN_REST_URL ) )
            ]
        ];
    }
}