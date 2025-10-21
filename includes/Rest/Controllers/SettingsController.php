<?php
namespace Yalogica\HappyVR\Rest\Controllers;

defined( 'ABSPATH' ) || exit;

use Yalogica\HappyVR\Models\AccessModel;
use Yalogica\HappyVR\Models\SettingsModel;

class SettingsController {
    public function registerRestRoutes() {
        register_rest_route(
            HAPPYVR_PLUGIN_REST_URL,
            '/settings',
            [
                [
                    'methods' => \WP_REST_Server::READABLE,
                    'callback' => [ $this, 'getSettings' ],
                    'permission_callback' => [ $this, 'canManageOptions' ]
                ],
                [
                    'methods' => \WP_REST_Server::EDITABLE,
                    'callback' => [ $this, 'setSettings' ],
                    'permission_callback' => [ $this, 'canManageOptions' ]
                ]
            ]
        );

        register_rest_route(
            HAPPYVR_PLUGIN_REST_URL,
            '/permission-types',
            [
                [
                    'methods' => \WP_REST_Server::READABLE,
                    'callback' => [ $this, 'getPermissionTypes' ],
                    'permission_callback' => [ $this, 'canManageOptions' ]
                ]
            ]
        );
    }

    public function canManageOptions() {
        return current_user_can( 'manage_options' );
    }

    public function getSettings( \WP_REST_Request $request ) {
        try {
            $settings = SettingsModel::get();
            
            if ( $settings ) {
                return new \WP_REST_Response([
                    'message' => esc_html__( 'Settings have been successfully retrieved', 'happyvr' ),
                    'data' => [
                        'settings' => $settings
                    ]
                ], 200);
            } else {
                return new \WP_REST_Response([
                    'message' => esc_html__( 'Failed to get settings', 'happyvr' )
                ], 400);
            }
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'message' => esc_html__( 'Failed to get settings', 'happyvr' )
            ], 500);
        }
    }

    public function setSettings( \WP_REST_Request $request ) {
        $data = $request->get_json_params();

        try {
            $result = SettingsModel::set( $data );

            if ( $result ) {
                return new \WP_REST_Response([
                    'message' => esc_html__( 'Settings are successfully updated', 'happyvr' )
                ], 200);
            } else {
                return new \WP_REST_Response([
                    'message' => esc_html__( 'Failed to update settings', 'happyvr' )
                ], 400);
            }
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'message' => esc_html__( 'Failed to update settings', 'happyvr' )
            ], 500);
        }
    }

    public function getPermissionTypes( \WP_REST_Request $request ) {
        try {
            $permissionTypes = AccessModel::getPermissionTypes();
            
            if ( $permissionTypes ) {
                return new \WP_REST_Response([
                    'message' => esc_html__( 'Permission types have been successfully retrieved', 'happyvr' ),
                    'data' => [
                        'permissionTypes' => $permissionTypes
                    ]
                ], 200);
            } else {
                return new \WP_REST_Response([
                    'message' => esc_html__( 'Failed to get permission types', 'happyvr' )
                ], 400);
            }
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'message' => esc_html__( 'Failed to get permission types', 'happyvr' )
            ], 500);
        }
    }
}