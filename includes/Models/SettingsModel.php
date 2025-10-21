<?php
namespace Yalogica\HappyVR\Models;

defined( 'ABSPATH' ) || exit;

class SettingsModel {
    public const MENU_ICON = '<svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <g style="fill:rgba(255,255,255,1);stroke-width:0;">
        <path d="M 10.208394,8.4165794 7,13 v 1.904297 C 8.4746222,14.650323 10.178883,14.5 12,14.5 c 1.821117,0 3.525378,0.150323 5,0.404297 V 13 l -1.687132,-2.095734 a 0.38925055,0.38925055 179.12534 0 0 -0.613726,0.0094 l -0.89392,1.182581 a 0.35955181,0.35955181 1.697884 0 1 -0.585991,-0.01737 L 10.782065,8.4230975 a 0.34740916,0.34740916 0.65097634 0 0 -0.573671,-0.00652 z" />
        <path d="m 12,3.0019531 c -2.9413062,0 -5.6035203,0.3652357 -7.5722656,0.9746094 C 3.4433617,4.2812493 2.6306798,4.6438944 2.0292969,5.0839844 2.0190584,5.0914769 2.0101367,5.0998594 2,5.1074219 1.4147342,5.5440583 1,6.0963257 1,6.7519531 V 17.25 c 0,0.655627 0.4147342,1.207895 1,1.644531 0.010137,0.0076 0.019058,0.01594 0.029297,0.02344 0.6013829,0.440089 1.4140648,0.802734 2.3984375,1.107422 C 4.6108167,20.082057 4.8052569,20.135152 5,20.1875 c 0.3202037,0.08607 0.6506535,0.168491 1,0.242188 V 19.402344 15.097656 14.070312 9.9316406 8.90625 C 5.6475045,8.8282395 5.3146197,8.7427228 5,8.6523438 4.9084559,8.6260463 4.8107533,8.6014868 4.7226562,8.5742188 3.8069082,8.2907727 3.0823656,7.9527915 2.6210938,7.6152344 2.1598217,7.2776771 2,6.9824307 2,6.7519531 2,6.5214758 2.1598217,6.2281823 2.6210938,5.890625 3.0823656,5.5530677 3.8069082,5.2150865 4.7226562,4.9316406 6.5541528,4.3647489 9.1423327,4.0019531 12,4.0019531 c 2.857667,0 5.445848,0.3627958 7.277344,0.9296875 0.915748,0.2834459 1.640291,0.6214271 2.101562,0.9589844 C 21.840179,6.2281823 22,6.5214758 22,6.7519531 22,6.9824307 21.840179,7.2776771 21.378906,7.6152344 20.917635,7.9527915 20.193092,8.2907727 19.277344,8.5742188 19.189244,8.6014867 19.091544,8.6260458 19,8.6523438 18.68538,8.7427228 18.352496,8.8282395 18,8.90625 v 1.0253906 4.1386714 1.027344 4.304688 1.027344 c 0.349347,-0.0737 0.679796,-0.156115 1,-0.242188 0.194743,-0.05235 0.389183,-0.105441 0.572266,-0.162109 0.984372,-0.304688 1.797055,-0.667333 2.398437,-1.107422 0.01024,-0.0075 0.01916,-0.01587 0.0293,-0.02344 0.585265,-0.436636 1,-0.988904 1,-1.644531 V 6.7519531 C 23,6.0963257 22.585265,5.5440583 22,5.1074219 21.98986,5.0998619 21.980943,5.0914764 21.970703,5.0839844 21.369324,4.6438939 20.556638,4.2812493 19.572266,3.9765625 17.60352,3.3671888 14.941306,3.0019531 12,3.0019531 Z M 5.4863281,15.220703 c -0.083305,0.02121 -0.1651347,0.04251 -0.2460937,0.06445 0.080959,-0.02194 0.1627886,-0.04325 0.2460937,-0.06445 z m 13.0273439,0 c 0.0833,0.02121 0.165135,0.04251 0.246094,0.06445 -0.08096,-0.02194 -0.162789,-0.04325 -0.246094,-0.06445 z M 5.2402344,19.214844 c 0.080959,0.02194 0.1627886,0.04325 0.2460937,0.06445 -0.083305,-0.0212 -0.1651347,-0.04251 -0.2460937,-0.06445 z m 13.5195316,0 c -0.08096,0.02194 -0.162789,0.04325 -0.246094,0.06445 0.0833,-0.0212 0.165135,-0.04251 0.246094,-0.06445 z" />
        <path d="M 16,7.5 A 1.5,1.5 0 0 1 14.5,9 1.5,1.5 0 0 1 13,7.5 1.5,1.5 0 0 1 14.5,6 1.5,1.5 0 0 1 16,7.5 Z" />
        </g>
        </svg>';

    public static function echoIcon() {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo self::MENU_ICON;
    }

    public static function getSup() {
        return 'lite';
    }

    public static function get() {
        $data = [];
        $access = AccessModel::get();

        $roles = [];
        foreach ( wp_roles()->roles as $key => $role ) {
            if ( array_key_exists( 'read', $role['capabilities'] ) ) {
                $roles[] = [
                    'id' => $key,
                    'title' => translate_user_role( $role['name'] )
                ];
            }
        }

        foreach( $roles as $role ) {
            $permissionType = AccessModel::ACCESS_TYPE_EDIT_ALL;
            $rights = AccessModel::getDefaultRights();

            foreach ( $access[AccessModel::ACCESS_PERMISSIONS] as $permission ) {
                if( $permission[AccessModel::ACCESS_ROLE] == $role['id'] ) {
                    $permissionType = $permission[AccessModel::ACCESS_TYPE];
                    $rights = $permission[AccessModel::ACCESS_RIGHTS];
                    break;
                }
            }

            $data[AccessModel::ACCESS_PERMISSIONS][] = [
                AccessModel::ACCESS_ROLE => [
                    'id' => $role['id'],
                    'title' => $role['title']
                ],
                AccessModel::ACCESS_TYPE => [
                    'id' => $permissionType,
                    'title' => AccessModel::getPermissionTypeTitleById( $permissionType )
                ],
                'rights' => $rights
            ];
        }

        return $data;
    }

    public static function set( $data = null ) {
        $access = [];
        foreach ( $data[AccessModel::ACCESS_PERMISSIONS] as $permission ) {
            $access[AccessModel::ACCESS_PERMISSIONS][] = [
                'role' => sanitize_key( $permission[AccessModel::ACCESS_ROLE]['id'] ),
                'type' => sanitize_key( $permission[AccessModel::ACCESS_TYPE]['id'] ),
                'rights' => [
                    AccessModel::ACCESS_RIGHT_VIEW    => boolval( $permission[AccessModel::ACCESS_RIGHTS][AccessModel::ACCESS_RIGHT_VIEW] ),
                    AccessModel::ACCESS_RIGHT_CREATE  => boolval( $permission[AccessModel::ACCESS_RIGHTS][AccessModel::ACCESS_RIGHT_CREATE] ),
                    AccessModel::ACCESS_RIGHT_EDIT    => boolval( $permission[AccessModel::ACCESS_RIGHTS][AccessModel::ACCESS_RIGHT_EDIT] ),
                    AccessModel::ACCESS_RIGHT_DELETE  => boolval( $permission[AccessModel::ACCESS_RIGHTS][AccessModel::ACCESS_RIGHT_DELETE] ),
                    AccessModel::ACCESS_RIGHT_PUBLISH => boolval( $permission[AccessModel::ACCESS_RIGHTS][AccessModel::ACCESS_RIGHT_PUBLISH] )
                ]
            ];
        }
        return AccessModel::set( $access );
    }
};