<?php
namespace Yalogica\HappyVR\Models;

defined( 'ABSPATH' ) || exit;

class AccessModel {
    private const OPTION_KEY = '_happyvr_access';
    private const OPTION_DEFAULT_DATA = [
        self::ACCESS_PERMISSIONS => [
             [
                 self::ACCESS_ROLE => 'administrator',
                 self::ACCESS_TYPE => self::ACCESS_TYPE_EDIT_ALL,
                 self::ACCESS_RIGHTS => [
                    self::ACCESS_RIGHT_VIEW => true,
                    self::ACCESS_RIGHT_CREATE => true,
                    self::ACCESS_RIGHT_EDIT => true,
                    self::ACCESS_RIGHT_DELETE => true,
                    self::ACCESS_RIGHT_PUBLISH => true
                ]
            ]
        ]
    ];

    public const ACCESS_ROLE = 'role';
    public const ACCESS_PERMISSIONS = 'permissions';

    public const ACCESS_TYPE = 'type';
    public const ACCESS_TYPE_EDIT_ALL = 'edit_all';
    public const ACCESS_TYPE_EDIT_BY_ROLE = 'edit_by_role';
    public const ACCESS_TYPE_EDIT_PRIVATE = 'edit_private';

    public const ACCESS_RIGHTS = 'rights';
    public const ACCESS_RIGHT_VIEW = 'view';
    public const ACCESS_RIGHT_CREATE = 'create'; // only create items
    public const ACCESS_RIGHT_EDIT = 'edit'; // edit & copy items
    public const ACCESS_RIGHT_DELETE = 'delete'; // trash (move to trash), restore (retrieve from trash), and permanent delete (remove completely)
    public const ACCESS_RIGHT_PUBLISH = 'publish'; // publish & back to draft

    public static function getPermissionTypeTitleById( $id ) {
        switch ( $id ) {
            case self::ACCESS_TYPE_EDIT_ALL: return esc_html__( 'All', 'happyvr' ); break;
            case self::ACCESS_TYPE_EDIT_BY_ROLE: return esc_html__( 'By Role', 'happyvr' ); break;
            case self::ACCESS_TYPE_EDIT_PRIVATE: return esc_html__( 'Private', 'happyvr' ); break;
        };
        return null;
    }

    public static function getPermissionTypes() {
        return [
            [ 'id' => self::ACCESS_TYPE_EDIT_ALL, 'title' => self::getPermissionTypeTitleById( self::ACCESS_TYPE_EDIT_ALL ) ],
            [ 'id' => self::ACCESS_TYPE_EDIT_BY_ROLE, 'title' => self::getPermissionTypeTitleById( self::ACCESS_TYPE_EDIT_BY_ROLE ) ],
            [ 'id' => self::ACCESS_TYPE_EDIT_PRIVATE, 'title' => self::getPermissionTypeTitleById( self::ACCESS_TYPE_EDIT_PRIVATE ) ],
        ];
    }

    public static function getDefaultRights() {
        return [
            self::ACCESS_RIGHT_VIEW => false,
            self::ACCESS_RIGHT_CREATE => false,
            self::ACCESS_RIGHT_EDIT => false,
            self::ACCESS_RIGHT_DELETE => false,
            self::ACCESS_RIGHT_PUBLISH => false
        ];
    }

    public static function get() {
        return OptionModel::get( self::OPTION_KEY, self::OPTION_DEFAULT_DATA );
    }

    public static function set( $data = null ) {
        return OptionModel::set( self::OPTION_KEY, self::OPTION_DEFAULT_DATA, $data );
    }

    public static function getCurrentUserRole() {
        $access = self::get();
        $user = get_user_by( 'id', get_current_user_id() );

        if ( $access && $user ) {
            $userRoles = $user->roles;

            foreach ( $access[self::ACCESS_PERMISSIONS] as $permission ) {
                if ( in_array( $permission[self::ACCESS_ROLE], $userRoles ) ) {
                    return $permission[self::ACCESS_ROLE]; // return the first founded role that has the permission
                }
            }
        }

        return null;
    }

    public static function getCurrentUserPermissionType() {
        $access = self::get();
        $user = get_user_by( 'id', get_current_user_id() );

        if ( $access && $user ) {
            $userRoles = $user->roles;

            foreach ( $access[self::ACCESS_PERMISSIONS] as $permission ) {
                if ( in_array( $permission[self::ACCESS_ROLE], $userRoles ) && $permission[self::ACCESS_RIGHTS][self::ACCESS_RIGHT_VIEW] ) {
                    return $permission[self::ACCESS_TYPE];
                }
            }
        }

        return null;
    }

    public static function currentUserCan( $rights ) {
        $access = self::get();
        $user = get_user_by( 'id', get_current_user_id() );

        if ( !$access || !$user ) {
            return false;
        }
       
        $userRoles = $user->roles;
        $rights = is_array( $rights ) ? $rights : [$rights];

        foreach ( $access[self::ACCESS_PERMISSIONS] as $permission ) {
            // Skip if the user does not have this role
            if ( !in_array( $permission[self::ACCESS_ROLE], $userRoles ) ) {
                continue;
            }

            // Assume all required rights are present
            $hasAllRights = true;
            
            // Check each requested right
            foreach ( $rights as $right ) {
                // If any of the required rights are missing, mark as false
                if ( !isset( $permission[self::ACCESS_RIGHTS][$right] ) || $permission[self::ACCESS_RIGHTS][$right] !== true ) {
                    $hasAllRights = false;
                    break; // No need to check further
                }
            }
        }

        // If all rights are satisfied for this role, grant access
        if ($hasAllRights) {
            return true;
        }

        return false;
    }
}