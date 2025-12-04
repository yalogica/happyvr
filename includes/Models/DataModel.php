<?php
namespace Yalogica\HappyVR\Models;

defined( 'ABSPATH' ) || exit;

class DataModel {
    public const POST_TYPE = 'happyvr';
    public const POST_STATUS_AUTO_DRAFT = 'auto-draft';
    public const POST_STATUS_DRAFT = 'draft';
    public const POST_STATUS_PUBLISHED = 'publish';
    public const POST_STATUS_TRASH = 'trash';

    public const META_CONFIG = '_happyvr_config';
    public const META_EDITED_BY = '_happyvr_edited_by';
    public const META_ROLE = '_happyvr_role';
    
    private static function getDirId($prefix, $dir) {
        if ( preg_match("/^{$prefix}_([a-z0-9]{10})$/", $dir, $matches)) {
            $id = $matches[1];
            return $id;
        }
        // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        throw new \Exception("Invalid {$prefix} key format or length.");
    }

    private static function getTotal( $status, $post_statuses, $filter = '%' ) {
        $permissionType = AccessModel::getCurrentUserPermissionType();

        if ( $permissionType ) {
            global $wpdb;
            $placeholder = implode( ',', array_fill( 0, count( $post_statuses ), '%s' ) );

            switch ( $permissionType ) {
                case AccessModel::ACCESS_TYPE_EDIT_ALL: {
                    $sql = "
                        SELECT COUNT(P.id) as total
                        FROM {$wpdb->posts} AS P 
                        WHERE P.post_type = %s AND P.post_status IN ({$placeholder}) AND IFNULL(P.post_title,'') LIKE %s"
                        . ($status == 'mine' ? " AND P.post_author=%d" : " AND 0=%d");

                    $args = [
                        self::POST_TYPE,
                        ...$post_statuses,
                        $filter,
                        $status == 'mine' ? get_current_user_id() : 0
                    ];
                } break;
                case AccessModel::ACCESS_TYPE_EDIT_BY_ROLE: {
                    $sql = "
                        SELECT COUNT(P.id) as total
                        FROM {$wpdb->posts} AS P
                        LEFT JOIN {$wpdb->postmeta} AS MUR
                        ON MUR.post_id=P.id AND MUR.meta_key=%s 
                        WHERE MUR.meta_value = %s AND P.post_type = %s AND P.post_status IN ({$placeholder}) AND IFNULL(P.post_title,'') LIKE %s"
                        . ($status == 'mine' ? " AND P.post_author=%d" : " AND 0=%d");

                    $args = [
                        self::META_ROLE,
                        AccessModel::getCurrentUserRole(),
                        self::POST_TYPE,
                        ...$post_statuses,
                        $filter,
                        $status == "mine" ? get_current_user_id() : 0
                    ];
                } break;
                case AccessModel::ACCESS_TYPE_EDIT_PRIVATE: {
                    $sql = "
                        SELECT COUNT(P.id) as total
                        FROM {$wpdb->posts} AS P
                        WHERE P.post_author = %d AND P.post_type = %s AND P.post_status IN ({$placeholder}) AND IFNULL(P.post_title,'') LIKE %s";

                        $args = [
                            get_current_user_id(),
                            self::POST_TYPE,
                            ...$post_statuses,
                            $filter
                        ];
                } break;
            }

            // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $sql = $wpdb->prepare( $sql, ...$args );
            return $wpdb->get_var( $sql );
            // phpcs:enable
        }

        return null;
    }

    private static function getRows( $status, $post_statuses, $filter, $orderby, $order, $offset, $count ) {
        $permissionType = AccessModel::getCurrentUserPermissionType();

        if ( $permissionType ) {
            global $wpdb;
            $placeholder = implode( ',', array_fill( 0, count( $post_statuses ), '%s' ) );

            switch ( $permissionType ) {
                case AccessModel::ACCESS_TYPE_EDIT_ALL: {
                    $sql = "
                        SELECT P.id as id, P.post_title AS title, P.post_status AS status, P.post_author AS author, MLE.meta_value AS edited_by, MUR.meta_value AS role, P.post_date AS created, P.post_modified AS edited
                        FROM {$wpdb->posts} AS P
                        LEFT JOIN {$wpdb->postmeta} AS MLE
                        ON MLE.post_id = P.id AND MLE.meta_key = %s
                        LEFT JOIN {$wpdb->postmeta} AS MUR
                        ON MUR.post_id = P.id AND MUR.meta_key = %s
                        WHERE P.post_type=%s AND P.post_status IN ({$placeholder}) AND IFNULL(P.post_title,'') LIKE %s"
                        . ($status == 'mine' ? " AND P.post_author=%d" : " AND 0=%d") . "
                        ORDER BY %1s %1s
                        LIMIT %d, %d";

                        $args = [
                            self::META_EDITED_BY,
                            self::META_ROLE,
                            self::POST_TYPE,
                            ...$post_statuses,
                            $filter,
                            $status == 'mine' ? get_current_user_id() : 0,
                            $orderby,
                            $order,
                            $offset,
                            $count
                        ];
                } break;
                case AccessModel::ACCESS_TYPE_EDIT_BY_ROLE: {
                    $sql = "
                        SELECT P.id as id, P.post_title AS title, P.post_status AS status, P.post_author AS author, MLE.meta_value AS edited_by, MUR.meta_value AS role, P.post_date AS created, P.post_modified AS edited
                        FROM {$wpdb->posts} AS P
                        LEFT JOIN {$wpdb->postmeta} AS MLE
                        ON MLE.post_id = P.id AND MLE.meta_key = %s
                        LEFT JOIN {$wpdb->postmeta} AS MUR
                        ON MUR.post_id = P.id AND MUR.meta_key = %s
                        WHERE MUR.meta_value = %s AND P.post_type = %s AND P.post_status IN ({$placeholder}) AND IFNULL(P.post_title,'') LIKE %s"
                        . ($status == 'mine' ? " AND P.post_author=%d" : " AND 0=%d") . "
                        ORDER BY %1s %1s
                        LIMIT %d, %d";

                        $args = [
                            self::META_EDITED_BY,
                            self::META_ROLE,
                            AccessModel::getCurrentUserRole(),
                            self::POST_TYPE,
                            ...$post_statuses,
                            $filter,
                            $status == 'mine' ? get_current_user_id() : 0,
                            $orderby,
                            $order,
                            $offset,
                            $count
                        ];
                } break;
                case AccessModel::ACCESS_TYPE_EDIT_PRIVATE: {
                    $sql = "
                        SELECT P.id as id, P.post_title AS title, P.post_status AS status, P.post_author AS author, MLE.meta_value AS edited_by, MUR.meta_value AS role, P.post_date AS created, P.post_modified AS edited
                        FROM {$wpdb->posts} AS P
                        LEFT JOIN {$wpdb->postmeta} AS MLE
                        ON MLE.post_id = P.id AND MLE.meta_key = %s
                        LEFT JOIN {$wpdb->postmeta} AS MUR
                        ON MUR.post_id = P.id AND MUR.meta_key = %s
                        WHERE P.post_author = %d AND P.post_type = %s AND P.post_status IN ({$placeholder}) AND IFNULL(P.post_title,'') LIKE %s
                        ORDER BY %1s %1s
                        LIMIT %d, %d";

                    $args = [
                        self::META_EDITED_BY,
                        self::META_ROLE,
                        get_current_user_id(),
                        self::POST_TYPE,
                        ...$post_statuses,
                        $filter,
                        $orderby,
                        $order,
                        $offset,
                        $count
                    ];
                } break;
            }

            // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $sql = $wpdb->prepare( $sql, ...$args );
            return $wpdb->get_results( $sql, 'ARRAY_A' );
            // phpcs:enable
        }

        return null;
    }

    private static function getColumns() {
        return [
            [ 'id' => 'id',        'name' => 'id',        'sortable' => false ],
            [ 'id' => 'title',     'name' => 'title',     'sortable' => true  ],
            [ 'id' => 'status',    'name' => 'status',    'sortable' => false ],
            [ 'id' => 'shortcode', 'name' => 'shortcode', 'sortable' => false ],
            [ 'id' => 'author',    'name' => 'author',    'sortable' => false ],
            [ 'id' => 'edited_by', 'name' => 'edited by', 'sortable' => false ],
            [ 'id' => 'role',      'name' => 'role',      'sortable' => false ],
            [ 'id' => 'created',   'name' => 'created',   'sortable' => true  ],
            [ 'id' => 'edited',    'name' => 'edited',    'sortable' => true  ]
        ];
    }

    public static function getItems( $page, $perpage = 10, $status = 'all', $orderby = null, $order = null, $search = null ) {
        global $wpdb;

        $page = intval( $page, 10 );
        $page = $page <= 0 ? 1 : $page;
        $count = intval( $perpage, 10 );
        $offset = ( $page - 1 ) * $count;

        $status_valid = [ 'all', 'published', 'draft' ];
        $status = in_array( $status, $status_valid, true ) ? $status : $status_valid[0];

        switch ( $status ) {
            case 'all': $post_statuses = [ self::POST_STATUS_PUBLISHED, self::POST_STATUS_DRAFT ]; break;
            case 'published': $post_statuses = [ self::POST_STATUS_PUBLISHED ]; break;
            case 'draft': $post_statuses = [ self::POST_STATUS_DRAFT ]; break;
        }

        $orderby_valid = [ 'title', 'created', 'edited' ];
        $orderby = in_array( $orderby, $orderby_valid, true ) ? $orderby : 'edited';
        switch ( $orderby ) {
            case 'title': $orderBy = 'post_title'; break;
            case 'created': $orderBy = 'post_date'; break;
            case 'edited': $orderBy = 'post_modified'; break;
        }

        $order_valid = [ 'asc', 'desc' ];
        $order = in_array( $order, $order_valid, true ) ? $order : 'desc';

        $filter  = $search ? '%' . $wpdb->esc_like( sanitize_text_field( $search ) ) . '%' : '%';

        $total = self::getTotal( $status, $post_statuses, $filter );
        $items = self::getRows( $status, $post_statuses, $filter, $orderBy, $order, $offset, $count );

        $data = null;
        if ( !$wpdb->last_error ) {
            $items = array_map(function($item) {
                $item['author'] = get_the_author_meta( 'display_name', $item['author'] );
                $item['edited_by'] = get_the_author_meta( 'display_name', $item['edited_by'] );
                $item['role'] = translate_user_role( $item['role'] );
                return $item;
            }, $items);

            $data['total'] = intval( $total, 10 );
            $data['pages'] = $count ? ceil( $data['total'] / $count ) : 1;
            $data['page'] = $page;
            $data['perpage'] = $perpage;
            $data['order'] = $order;
            $data['orderby'] = $orderby;
            $data['search'] = $search;
            $data['items'] = $items;
            $data['columns'] = self::getColumns();
        }
        return $data;
    }

    public static function getItemsCount() {
        global $wpdb;

        $sql = "
            SELECT COUNT(P.id) as total
            FROM {$wpdb->posts} AS P
            WHERE P.post_type = %s";

        $args = [
            self::POST_TYPE
        ];

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $sql = $wpdb->prepare( $sql, ...$args );
        $count = intval( $wpdb->get_var( $sql ) );
        // phpcs:enable

        return $count;
    }

    public static function getFilterCounts() {
        return [
            'all' => self::getTotal( 'all', [ self::POST_STATUS_PUBLISHED, self::POST_STATUS_DRAFT ] ),
            'mine' => self::getTotal( 'mine', [ self::POST_STATUS_PUBLISHED, self::POST_STATUS_DRAFT ] ),
            'published' => self::getTotal( 'published', [ self::POST_STATUS_PUBLISHED ] ),
            'draft' => self::getTotal( 'draft', [ self::POST_STATUS_DRAFT ] ),
            'trash' => self::getTotal( 'all', [ self::POST_STATUS_TRASH ] )
        ];
    }

    public static function getCurrentUserItems() {
        $permissionType = AccessModel::getCurrentUserPermissionType();

        if ( $permissionType ) {
            global $wpdb;

            switch ( $permissionType ) {
                case AccessModel::ACCESS_TYPE_EDIT_ALL: {
                    $sql = "
                        SELECT P.id as id, P.post_title AS title
                        FROM {$wpdb->posts} P
                        WHERE P.post_type = %s AND P.post_status = %s
                        ORDER BY P.post_title";

                    $args = [
                        self::POST_TYPE,
                        self::POST_STATUS_PUBLISHED
                    ];
                } break;
                case AccessModel::ACCESS_TYPE_EDIT_BY_ROLE: {
                    $sql = "
                        SELECT P.id as id, P.post_title AS title
                        FROM {$wpdb->posts} P
                        LEFT JOIN {$wpdb->postmeta} AS MUR
                        ON MUR.post_id = P.id AND MUR.meta_key = %s
                        WHERE MUR.meta_value = %s AND P.post_type = %s AND P.post_status = %s
                        ORDER BY P.post_title";

                    $args = [
                        self::META_ROLE,
                        AccessModel::getCurrentUserRole(),
                        self::POST_TYPE,
                        self::POST_STATUS_PUBLISHED
                    ];
                } break;
                case AccessModel::ACCESS_TYPE_EDIT_PRIVATE: {
                    $sql = "
                        SELECT P.id as id, P.post_title AS title
                        FROM {$wpdb->posts} P
                        WHERE P.post_author = %d AND P.post_type = %s AND P.post_status = %s
                        ORDER BY P.post_title";

                    $args = [
                        get_current_user_id(),
                        self::POST_TYPE,
                        self::POST_STATUS_PUBLISHED
                    ];
                } break;
            }

            // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $sql = $wpdb->prepare( $sql, ...$args );
            $items = $wpdb->get_results( $sql, 'ARRAY_A' );
            // phpcs:enable

            if ( !$wpdb->last_error ) {
                return $items;
            }
        }

        return null;
    }

    public static function currentUserCan( $post_id, $right ) {
        $permissionType = AccessModel::getCurrentUserPermissionType();

        switch ( $permissionType ) {
            case AccessModel::ACCESS_TYPE_EDIT_ALL: {
                return AccessModel::currentUserCan( $right );
            } break;

            case AccessModel::ACCESS_TYPE_EDIT_BY_ROLE: {
                $role = get_post_meta( $post_id, self::META_ROLE, true );
                if ( $role === AccessModel::getCurrentUserRole() ) {
                    return AccessModel::currentUserCan( $right );
                }
            } break;

            case AccessModel::ACCESS_TYPE_EDIT_PRIVATE: {
                $post = get_post( $post_id );
                if ( !$post ) {
                    return false;
                }
                if ( (int)$post->post_author === get_current_user_id() ) {
                    return AccessModel::currentUserCan( $right );
                }
            } break;
        }

        return false;
    }

    public static function getItemPublic( $id ) {
        $post = get_post( $id );
        if ( $post && $post->post_type === self::POST_TYPE) {
            $data = [];
            $data['id'] = $post->ID;
            $data['title'] = $post->post_title;
            $data['status'] = $post->post_status;
            
            $all_meta = get_post_meta( $post->ID );
            $meta = function ( $key ) use ( $all_meta ) {
                return $all_meta[ $key ][0] ?? '';
            };

            $json = $meta( self::META_CONFIG );
            $data['config'] = json_decode( $json, true );

            return $data;
        }        
        return null;
    }

    public static function getItem( $id ) {
        if ( AccessModel::currentUserCan(AccessModel::ACCESS_RIGHT_VIEW) ) {
            $post = get_post( $id );
            if ( $post && $post->post_type === self::POST_TYPE) {
                $data = [];
                $data['id'] = $post->ID;
                $data['title'] = $post->post_title;
                $data['status'] = $post->post_status;
                $data['author'] = get_the_author_meta( 'display_name', $post->post_author );
                $data['created'] = $post->post_date;
                $data['edited'] = $post->post_modified;

                $all_meta = get_post_meta( $post->ID );
                $meta = function ( $key ) use ( $all_meta ) {
                    return $all_meta[ $key ][0] ?? '';
                };

                $json = $meta( self::META_CONFIG );
                $data['config'] = json_decode( $json, true );

                $edited_by_id = $meta( self::META_EDITED_BY );
                $data['edited_by'] = get_the_author_meta( 'display_name', $edited_by_id );
                
                $role_key = $meta( self::META_ROLE );
                $data['role'] = translate_user_role( $role_key );

                return $data;
            }
        }
        return null;
    }

    public static function createItemAutoDraft() {
        if ( AccessModel::currentUserCan([AccessModel::ACCESS_RIGHT_CREATE, AccessModel::ACCESS_RIGHT_EDIT]) ) {
            $post = [
                'post_type' => self::POST_TYPE,
                'post_name' => uniqid(),
                'post_status' => self::POST_STATUS_AUTO_DRAFT,
                'meta_input' => [
                    self::META_EDITED_BY => get_current_user_id(),
                    self::META_ROLE => AccessModel::getCurrentUserRole()
                ]
            ];
            $post_id = wp_insert_post( $post );

            if ( $post_id !== null ) {
                return $post_id;
            }
        }
        return null;
    }

    public static function createItem( $data ) {
        if ( is_array( $data ) && AccessModel::currentUserCan([AccessModel::ACCESS_RIGHT_CREATE, AccessModel::ACCESS_RIGHT_EDIT]) ) {
            $config = wp_slash( json_encode( $data['config'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
            if ($config === false) {
                return null;
            }

            $post = [
                'post_type' => self::POST_TYPE,
                'post_name' => uniqid(),
                'post_status' => self::POST_STATUS_DRAFT,
                'post_title' => isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '',
                'meta_input' => [
                    self::META_CONFIG => $config,
                    self::META_EDITED_BY => get_current_user_id(),
                    self::META_ROLE => AccessModel::getCurrentUserRole()
                ]
            ];
            $post_id = wp_insert_post( $post, true );

            if ( !is_wp_error( $post_id ) ) {
                return $post_id;
            }
        }
        return null;
    }

    public static function trashItem( $id ) {
        if ( self::currentUserCan( $id, AccessModel::ACCESS_RIGHT_DELETE ) ) {            
            $post = get_post( $id );
            if ( !$post || $post->post_type !== self::POST_TYPE ) {
                return;
            }

            wp_trash_post( $id );
        }
    }

    public static function restoreItem( $id ) {
        if ( self::currentUserCan( $id, AccessModel::ACCESS_RIGHT_DELETE ) ) {
            $post = get_post( $id );
            if ( !$post || $post->post_type !== self::POST_TYPE ) {
                return;
            }

            wp_untrash_post( $id );
        }
    }

    public static function deleteItem( $id ) {
        if ( !self::currentUserCan( $id, AccessModel::ACCESS_RIGHT_DELETE ) ) {
            return false;
        }

        $id = intval( $id );
        if ( $id <= 0 ) {
            return false;
        }

        $post = get_post( $id );
        if ( !$post || $post->post_type !== self::POST_TYPE ) {
            return false;
        }

        $result = wp_delete_post( $id, true );

        if ($result !== false) {
            // Base directory
            $uploadDir = wp_upload_dir();
            $baseDir = trailingslashit($uploadDir['basedir']) . 'happyvr';
            
            $virtualTourDir = trailingslashit($baseDir) . 'tour_' . $id;

            // Initialize the WordPress filesystem
            global $wp_filesystem;
            if (empty($wp_filesystem)) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                WP_Filesystem();
            }

            // Attempt to delete the directory and its contents
            if ( !$wp_filesystem->delete( $virtualTourDir, true ) ) { // The second parameter 'true' allows recursive deletion
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new \Exception("Failed to delete virtual tour directory [{$virtualTourDir}].");
            }

            return true;
        }

        return false;
    }

    public static function recursiveCopy($src, $dst) {
        global $wp_filesystem;
        if ( empty( $wp_filesystem ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }

        if ( $wp_filesystem->is_dir( $src ) ) {
            $wp_filesystem->mkdir( $dst );

            $contents = $wp_filesystem->dirlist( $src );
            foreach ( $contents as $file => $info ) {
                if ($file === '.' || $file === '..') continue;
                DataModel::recursiveCopy( $src . '/' . $file, $dst . '/' . $file );
            }
        } elseif ( $wp_filesystem->is_file( $src ) ) {
            $wp_filesystem->copy( $src, $dst );
        }
    }

    public static function copyItem( $id ) {
        if ( !self::currentUserCan( $id, [AccessModel::ACCESS_RIGHT_CREATE, AccessModel::ACCESS_RIGHT_EDIT] ) ) {
            return null;
        }

        $id = intval( $id );
        if ( $id <= 0 ) {
            return null;
        }

        $post = get_post( $id );
        if ( !$post || $post->post_type !== self::POST_TYPE ) {
            return null;
        }

        $title = $post->post_title . (strripos( $post->post_title, '[Copy]' ) !== FALSE ? '' : ' [Copy]');
        $config = get_post_meta( $post->ID, self::META_CONFIG, true );
        $config = $config !== '' ? $config : json_encode( [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

        $post = [
            'post_type' => self::POST_TYPE,
            'post_name' => uniqid(),
            'post_status' => self::POST_STATUS_DRAFT,
            'post_title'  => $title,
            'meta_input' => [
                self::META_CONFIG => $config,
                self::META_EDITED_BY => get_current_user_id(),
                self::META_ROLE => AccessModel::getCurrentUserRole()
            ]
        ];
        $post_id = wp_insert_post( $post, true );
        
        if ( !is_wp_error( $post_id ) ) {
            // Base directory
            $uploadDir = wp_upload_dir();
            $baseDir = trailingslashit($uploadDir['basedir']) . 'happyvr';
            
            $srcVirtualTourDir = trailingslashit($baseDir) . 'tour_' . $id;
            $dstVirtualTourDir = trailingslashit($baseDir) . 'tour_' . $post_id;

            DataModel::recursiveCopy( $srcVirtualTourDir, $dstVirtualTourDir );

            return $post_id;
        }

        return null;
    }

    public static function updateItem( $id, $data ) {
        $id = intval( $id );
        if ( $id <= 0 ) {
            return false;
        }

        $post = get_post( $id );
        if ( !$post || $post->post_type !== self::POST_TYPE ) {
            return false;
        }

        if ( is_array( $data ) && self::currentUserCan( $id, AccessModel::ACCESS_RIGHT_EDIT ) ) {
            if ( !isset( $data['config'] ) || !is_array( $data['config'] ) ) {
                throw new \Exception('Config is missing or invalid.');
            }

            $config = wp_slash( json_encode( $data['config'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
            if ($config === false) {
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new \Exception('JSON encoding error: ' . json_last_error_msg());
            }

            $post = [
                'ID' => $id,
                'post_title' => isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '',
                'meta_input' => [
                    self::META_CONFIG => $config,
                    self::META_EDITED_BY => get_current_user_id()
                ]
            ];

            if ( in_array( $data['status'], [ self::POST_STATUS_AUTO_DRAFT ] ) ) {
                $post['post_status'] = self::POST_STATUS_DRAFT;
            }

            $post_id = wp_update_post( $post );

            if ($post_id === 0) {
                throw new \Exception('Failed to update post.');
            }
            
            $scenes = $data['config']['scenes'] ?? [];
            if ( !is_array($scenes) ) {
                $scenes = [];
            }

            // Base directory
            $uploadDir = wp_upload_dir();
            $baseDir = trailingslashit($uploadDir['basedir']) . 'happyvr';
        
            // Ensure the happyvr directory exists
            if ( !wp_mkdir_p( $baseDir ) ) {
                throw new \Exception('Failed to create happyvr directory.');
            }

            // Ensure the tour directory exists
            $virtualTourDir = trailingslashit($baseDir) . 'tour_' . $id;
            if ( !wp_mkdir_p( $virtualTourDir ) ) {
                throw new \Exception('Failed to create virtual tour directory.');
            }

            // Initialize the WordPress filesystem
            global $wp_filesystem;
            if ( empty( $wp_filesystem ) ) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                WP_Filesystem();
            }

            // Remove 
            $sceneDirs = scandir($virtualTourDir);
            if ( $sceneDirs === false ) {
                throw new \Exception('Failed to read virtual tour scene directories.');
            }

            foreach ( $sceneDirs as $sceneDir ) {
                if ( $sceneDir === '.' || $sceneDir === '..' ) {
                    continue;
                }

                $doDelete = true;
                $currentScene = null; 
                foreach( $scenes as $scene ) {
                    if ( $scene['id'] === DataModel::getDirId( 'scene', $sceneDir ) ) {
                        $currentScene = $scene;
                        $doDelete = false;
                        break;
                    }
                }

                if ( $doDelete ) {
                    $fullPath = trailingslashit($virtualTourDir) . $sceneDir;
                    
                    // Attempt to delete the unused scene directory and its contents
                    if ( !$wp_filesystem->delete( $fullPath, true ) ) { // The second parameter 'true' allows recursive deletion
                        // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                        throw new \Exception("Failed to delete scene directory [{$sceneDir}].");
                    }
                } else if ( $currentScene !== null && isset( $currentScene['pano']['id'] ) ) {
                    $scenePath = trailingslashit($virtualTourDir) . $sceneDir;

                    $panoDirs = scandir($scenePath);
                    if ( $panoDirs === false ) {
                        throw new \Exception('Failed to read virtual tour scene pano directories.');
                    }
                    
                    foreach ( $panoDirs as $panoDir ) {
                        if ( $panoDir === '.' || $panoDir === '..' ) {
                            continue;
                        }

                        if ( $currentScene['pano']['id'] !== DataModel::getDirId( 'pano', $panoDir ) ) {
                            $fullPath = trailingslashit($scenePath) . $panoDir;

                            // Attempt to delete the unused pano directory and its contents
                            if ( !$wp_filesystem->delete( $fullPath, true ) ) {
                                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                                throw new \Exception("Failed to delete scene pano directory [{$panoDir}].");
                            }
                        }     
                    }
                }
            }

            return true;
            
        }

        return false;
    }

    public static function updateItemStatus( $id, $status ) {
        $id = intval( $id );
        if ( $id <= 0 ) {
            return null;
        }

        $post = get_post( $id );
        if ( !$post || $post->post_type !== self::POST_TYPE ) {
            return false;
        }

        if ( in_array( $status, [ self::POST_STATUS_DRAFT, self::POST_STATUS_PUBLISHED ] ) && self::currentUserCan( $id, AccessModel::ACCESS_RIGHT_PUBLISH ) ) {
            $post = [
                'ID' => $id,
                'post_status' => sanitize_key( $status )
            ];
            $post_id = wp_update_post( $post );

            if ( $post_id !== null ) {
                return true;
            }
        }

        return false;
    }

    public static function getItemConfig( $id ) {
        $id = intval( $id );
        if ( $id <= 0 ) {
            return null;
        }

        $post = get_post( $id );
        if ( !$post || $post->post_type !== self::POST_TYPE || $post->post_status !== self::POST_STATUS_PUBLISHED ) {
            return null;
        }

        $config = get_post_meta( $post->ID, self::META_CONFIG, true );
        if ( $config === '' ) {
            return null;
        }

        $jsonData = json_decode( $config, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return null;
        }

        return $jsonData;
    }

    public static function updateSceneTiles( $id, $sceneId, $scenePanoId, $level, $face, $tileSize, $tiles ) {
        $id = intval( $id );
        if ( $id <= 0 ) {
            throw new \Exception('Incorrect ID value.');
        }

        if (!is_array($tiles)) {
            throw new \Exception('Tiles must be an array.');
        }

        // Base directory
        $uploadDir = wp_upload_dir();
        $baseDir = trailingslashit($uploadDir['basedir']) . 'happyvr';

        // Ensure the happyvr directory exists
        if ( !wp_mkdir_p( $baseDir ) ) {
            throw new \Exception('Failed to create happyvr directory.');
        }

        // Ensure the tour directory exists
        $virtualTourDir = trailingslashit($baseDir) . 'tour_' . $id;
        if ( !wp_mkdir_p( $virtualTourDir ) ) {
            throw new \Exception('Failed to create virtual tour directory.');
        }

        // Ensure the scene directory exists
        $sceneDir = trailingslashit($virtualTourDir) . 'scene_' . $sceneId;
        if ( !wp_mkdir_p( $sceneDir ) ) {
            throw new \Exception('Failed to create scene directory.');
        }

        // Ensure the scene pano directory exists
        $panoDir = trailingslashit($sceneDir) . 'pano_' . $scenePanoId;
        if ( !wp_mkdir_p( $panoDir ) ) {
            throw new \Exception('Failed to create scene pano directory.');
        }

        // Ensure the scene tiles directory exists
        $tilesDir = trailingslashit($panoDir) . 'tiles';
        if ( !wp_mkdir_p( $tilesDir ) ) {
            throw new \Exception('Failed to create scene tiles directory.');
        }

        // Ensure the scene tiles level directory exists
        $levelDir = trailingslashit($tilesDir) . $level;
        if ( !wp_mkdir_p( $levelDir ) ) {
            throw new \Exception('Failed to create scene tiles level directory.');
        }

        // Process and save each tile in $tile_array
        foreach ( $tiles as $tile ) {
            $x = intval( $tile['x'], 10 );
            $y = intval( $tile['y'], 10 );
            $data = $tile['data'];

            // Decode the base64 image data
            list($type, $data) = explode( ';', $data );
            list(, $data) = explode( ',', $data );
            $decodedData = base64_decode( $data );

            // Create a file path for the tile
            $filePath = sprintf( '%s/%s_x%d_y%d.jpg', $levelDir, $face, $x, $y );

            // Save the tile data to the file system
            if ( file_put_contents( $filePath, $decodedData ) === false ) {
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new \Exception('Failed to save scene tile data: ' . $filePath);
            }
        }

        return true;
    }

    public static function updateSceneImage( $id, $sceneId, $scenePanoId, $imageName, $imageData ) {
        $id = intval( $id );
        if ( $id <= 0 ) {
            throw new \Exception('Incorrect ID value.');
        }

        // Base directory
        $uploadDir = wp_upload_dir();
        $baseDir = trailingslashit($uploadDir['basedir']) . 'happyvr';

        // Ensure the happyvr directory exists
        if ( !wp_mkdir_p( $baseDir ) ) {
            throw new \Exception('Failed to create happyvr directory.');
        }

        // Ensure the tour directory exists
        $virtualTourDir = trailingslashit($baseDir) . 'tour_' . $id;
        if ( !wp_mkdir_p( $virtualTourDir ) ) {
            throw new \Exception('Failed to create virtual tour directory.');
        }

        // Ensure the scene directory exists
        $sceneDir = trailingslashit($virtualTourDir) . 'scene_' . $sceneId;
        if ( !wp_mkdir_p( $sceneDir ) ) {
            throw new \Exception('Failed to create scene directory.');
        }

        // Ensure the scene pano directory exists
        $panoDir = trailingslashit($sceneDir) . 'pano_' . $scenePanoId;
        if ( !wp_mkdir_p( $panoDir ) ) {
            throw new \Exception('Failed to create scene pano directory.');
        }

        // Decode the base64 image data
        list($type, $imageData) = explode( ';', $imageData );
        list(, $imageData) = explode( ',', $imageData );
        $decodedData = base64_decode( $imageData );

        // Create a file path for the tile
        $filePath = sprintf( '%s/%s.jpg', $panoDir, $imageName );

        // Save the tile data to the file system
        if ( file_put_contents( $filePath, $decodedData ) === false ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new \Exception('Failed to save scene image data: ' . $filePath);
        }

        return true;
    }

    public static function getIconSets() {
        $iconsDir = HAPPYVR_PLUGIN_PATH . '/assets/icons/';

        if ( !is_dir( $iconsDir ) ) {
             throw new \Exception('Failed to read icons directory.');
        }

        $iconSets = [];
        $iterator = new \DirectoryIterator( $iconsDir );
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDir() && !$fileInfo->isDot()) {
                $iconSets[] = $fileInfo->getFilename();
            }
        }

        return $iconSets;
    }

    public static function getIcons( $set = 'lucide' ) {
        $iconsDir = HAPPYVR_PLUGIN_PATH . '/assets/icons';

        if ( !is_dir( $iconsDir ) ) {
             throw new \Exception('Failed to read icons directory.');
        }

        $iconsDir = $iconsDir . '/' . $set . '/';

        if ( !is_dir( $iconsDir ) ) {
             throw new \Exception('Failed to read icons set directory.');
        }

        $files = scandir( $iconsDir );
        $icons = [];

        foreach ( $files as $file ) {
            if ( $file === '.' || $file === '..' ) {
                continue;
            }

            $ext = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
            if ( in_array( $ext, [ 'svg' ] ) ) {
                $icons[] = pathinfo( $file, PATHINFO_FILENAME );
            }
        }

        return $icons;
    }
}