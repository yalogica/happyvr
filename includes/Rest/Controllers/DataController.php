<?php
namespace Yalogica\HappyVR\Rest\Controllers;

defined( 'ABSPATH' ) || exit;

use Yalogica\HappyVR\Models\DataModel;
use Yalogica\HappyVR\Models\AccessModel;

class DataController {
    public function __construct() {
    }

    public function registerRestRoutes() {
        register_rest_route(
            HAPPYVR_PLUGIN_PUBLIC_REST_URL,
            '/virtualtours/(?P<id>\d+)',
            [
                [
                    'methods' => \WP_REST_Server::READABLE,
                    'callback' => [ $this, 'getVirtualTourDataPublic' ],
                    'permission_callback' => '__return_true',
                    'args' => [
                        'id' => [
                            'type' => 'integer',
                            'required' => true,
                        ]
                    ]
                ],
            ]
        );

        register_rest_route(
            HAPPYVR_PLUGIN_REST_URL,
            '/virtualtours',
            [
                [
                    'methods' => \WP_REST_Server::READABLE,
                    'callback' => [ $this, 'getVirtualTours' ],
                    'permission_callback' => function() {
                        return AccessModel::currentUserCan( AccessModel::ACCESS_RIGHT_VIEW );
                    },
                    'args' => [
                        'page' => [
                            'default' => 1,
                            'type' => 'integer',
                            'sanitize_callback' => 'absint',
                            'validate_callback' => function( $param ) {
                                return is_numeric($param) && $param >= 1;
                            }
                        ],
                        'perpage' => [
                            'default' => 5,
                            'type' => 'integer',
                            'sanitize_callback' => 'absint',
                            'validate_callback' => function($param) {
                                return is_numeric($param) && $param >= 1 && $param <= 100;
                            }
                        ]
                    ]
                ]
            ]
        );

        register_rest_route(
            HAPPYVR_PLUGIN_REST_URL,
            '/virtualtours/(?P<id>\d+)',
            [
                [
                    'methods' => \WP_REST_Server::READABLE,
                    'callback' => [ $this, 'getVirtualTour' ],
                    'permission_callback' => function() {
                        return AccessModel::currentUserCan( AccessModel::ACCESS_RIGHT_VIEW );
                    },
                    'args' => [
                        'id' => [
                            'type' => 'integer',
                            'required' => true,
                        ]
                    ]
                ],
                [
                    'methods' => \WP_REST_Server::EDITABLE,
                    'callback' => [ $this, 'updateVirtualTour' ],
                    'permission_callback' => function() {
                        return AccessModel::currentUserCan( AccessModel::ACCESS_RIGHT_EDIT );
                    },
                    'args' => [
                        'id' => [
                            'type' => 'integer',
                            'required' => true,
                        ]
                    ]
                ],
                [
                    'methods' => \WP_REST_Server::DELETABLE,
                    'callback' => [ $this, 'deleteVirtualTour' ],
                    'permission_callback' => function() {
                        return AccessModel::currentUserCan( AccessModel::ACCESS_RIGHT_DELETE );
                    },
                    'args' => [
                        'id' => [
                            'type' => 'integer',
                            'required' => true,
                        ]
                    ]
                ]
            ]
        );

        register_rest_route(
            HAPPYVR_PLUGIN_REST_URL,
            '/virtualtours/(?P<id>\d+)/copy',
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [ $this, 'copyVirtualTour' ],
                'permission_callback' => function() {
                    return AccessModel::currentUserCan( AccessModel::ACCESS_RIGHT_CREATE );
                },
                'args' => [
                    'id' => [
                        'type' => 'integer',
                        'required' => true,
                    ]
                ]
            ]
        );

        register_rest_route(
            HAPPYVR_PLUGIN_REST_URL,
            '/virtualtours/(?P<id>\d+)/status/(?P<status>(?i)draft|publish)',
            [
                [
                    'methods' => \WP_REST_Server::EDITABLE,
                    'callback' => [ $this, 'updateVirtualTourStatus' ],
                    'permission_callback' => function() {
                        return AccessModel::currentUserCan( AccessModel::ACCESS_RIGHT_PUBLISH );
                    }
                ]
            ]
        );

        register_rest_route(
            HAPPYVR_PLUGIN_REST_URL,
            '/virtualtours/(?P<id>\d+)/scenes/(?P<sceneId>[0-9a-z]{10})/tiles',
            [
                [
                    'methods' => \WP_REST_Server::EDITABLE,
                    'callback' => [ $this, 'updateVirtualTourSceneTiles' ],
                    'permission_callback' => function() {
                        return AccessModel::currentUserCan( AccessModel::ACCESS_RIGHT_EDIT );
                    }
                ]
            ]
        );

        register_rest_route(
            HAPPYVR_PLUGIN_REST_URL,
            '/virtualtours/(?P<id>\d+)/scenes/(?P<sceneId>[0-9a-z]{10})/thumb',
            [
                [
                    'methods' => \WP_REST_Server::EDITABLE,
                    'callback' => [ $this, 'updateVirtualTourSceneThumb' ],
                    'permission_callback' => function() {
                        return AccessModel::currentUserCan( AccessModel::ACCESS_RIGHT_EDIT );
                    }
                ]
            ]
        );

        register_rest_route(
            HAPPYVR_PLUGIN_REST_URL,
            '/icons/sets',
            [
                [
                    'methods' => \WP_REST_Server::READABLE,
                    'callback' => [ $this, 'getIconSets' ],
                    'permission_callback' => function() {
                        return AccessModel::currentUserCan( AccessModel::ACCESS_RIGHT_VIEW );
                    }
                ]
            ]
        );

        register_rest_route(
            HAPPYVR_PLUGIN_REST_URL,
            '/icons/sets/(?P<set_id>[0-9a-z_-]+)',
            [
                [
                    'methods' => \WP_REST_Server::READABLE,
                    'callback' => [ $this, 'getIcons' ],
                    'permission_callback' => function() {
                        return AccessModel::currentUserCan( AccessModel::ACCESS_RIGHT_VIEW );
                    },
                    'args' => [
                        'set_id' => [
                            'type' => 'string',
                            'required' => true,
                        ]
                    ]
                ]
            ]
        );
    }

    public function getVirtualTourDataPublic( \WP_REST_Request $request ) {
        $id = intval( $request->get_param( 'id' ), 10 );

        try {
            $item = DataModel::getItemPublic( $id );
            
            if ( $item ) {
                return new \WP_REST_Response([
                    'message' => esc_html__( 'Virtual tour data has been retrieved.', 'happyvr' ),
                    'data' => [
                        'item' => $item
                    ]
                ], 200);
            } else {
                return new \WP_REST_Response([
                    'message' => esc_html__( 'Failed to get virtual tour data.', 'happyvr' )
                ], 400);
            }
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'message' => esc_html__( 'Failed to get virtual tour data.', 'happyvr' ) . ' ' . esc_html( $e->getMessage() )
            ], 500);
        }
    }

    public function getVirtualTours( \WP_REST_Request $request ) {
        $page = intval( $request->get_param( 'page' ), 10 );
        $perpage = intval( $request->get_param( 'perpage' ), 10 );
        $filter = sanitize_key( $request->get_param( 'filter' ) );
        $orderby = sanitize_key( $request->get_param( 'orderby' ) );
        $order = sanitize_key( $request->get_param( 'order' ) );
        $search = sanitize_text_field( $request->get_param( 'search' ) );

        try {
            $virtualTours = DataModel::getItems( $page, $perpage, $filter, $orderby, $order, $search );
            
            if ( $virtualTours ) {
                return new \WP_REST_Response([
                    'message' => esc_html__( 'Virtual tour items have been retrieved.', 'happyvr' ),
                    'data' => [
                        'virtualtours' => $virtualTours
                    ]
                ], 200);
            } else {
                return new \WP_REST_Response([
                    'message' => esc_html__( 'Failed to get virtual tour items.', 'happyvr' )
                ], 400);
            }
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'message' => esc_html__( 'Failed to get virtual tour items.', 'happyvr' ) . ' ' . esc_html( $e->getMessage() )
            ], 500);
        }
    }

    public function getVirtualTour( \WP_REST_Request $request ) {
        $id = intval( $request->get_param( 'id' ), 10 );

        try {
            $item = DataModel::getItem( $id );
            
            if ( $item ) {
                return new \WP_REST_Response([
                    'message' => esc_html__( 'Virtual tour data has been retrieved.', 'happyvr' ),
                    'data' => [
                        'item' => $item
                    ]
                ], 200);
            } else {
                return new \WP_REST_Response([
                    'message' => esc_html__( 'Failed to get virtual tour data.', 'happyvr' )
                ], 400);
            }
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'message' => esc_html__( 'Failed to get virtual tour data.', 'happyvr' ) . ' ' . esc_html( $e->getMessage() )
            ], 500);
        }
    }

    public function updateVirtualTour( \WP_REST_Request $request ) {
        $id = intval( $request->get_param( 'id' ), 10 );
        $data = $request->get_json_params();
        
        try {
            $result = DataModel::updateItem( $id, $data );

            if ( $result ) {
                return new \WP_REST_Response([
                    'message' => esc_html__( 'Virtual tour data has been saved.', 'happyvr' )
                ], 200);
            } else {
                return new \WP_REST_Response([
                    'message' => esc_html__( 'Failed to save virtual tour data.', 'happyvr' )
                ], 400);
            }
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'message' => esc_html__( 'Failed to save virtual tour data.', 'happyvr' ) . ' ' . esc_html( $e->getMessage() )
            ], 500);
        }
    }

    public function deleteVirtualTour( \WP_REST_Request $request ) {
        $id = intval( $request->get_param( 'id' ), 10 );
        
        try {
            $result = DataModel::deleteItem( $id );

            if ( $result ) {
                return new \WP_REST_Response([
                    'message' => esc_html__( 'Virtual tour has been deleted.', 'happyvr' )
                ], 200);
            } else {
                return new \WP_REST_Response([
                    'message' => esc_html__( 'Failed to delete virtual tour.', 'happyvr' )
                ], 400);
            }
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'message' => esc_html__( 'Failed to delete virtual tour.', 'happyvr' ) . ' ' . esc_html( $e->getMessage() )
            ], 500);
        }
    }

    public function copyVirtualTour( \WP_REST_Request $request ) {
         $id = intval( $request->get_param( 'id' ), 10 );
        
        try {
            $id = DataModel::copyItem( $id );

            if ( $id ) {
                return new \WP_REST_Response([
                    'message' => esc_html__( 'Virtual tour has been copied.', 'happyvr' ),
                    'data' => [
                        'id' => $id
                    ]
                ], 200);
            } else {
                return new \WP_REST_Response([
                    'message' => esc_html__( 'Failed to copy virtual tour.', 'happyvr' )
                ], 400);
            }
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'message' => esc_html__( 'Failed to copy virtual tour.', 'happyvr' ) . ' ' . esc_html( $e->getMessage() )
            ], 500);
        }
    }
    
    public function updateVirtualTourStatus( \WP_REST_Request $request ) {
        $id = intval( $request->get_param( 'id' ), 10 );
        $status = sanitize_key( $request->get_param( 'status' ) );

        try {
            $result = DataModel::updateItemStatus( $id, $status ) ;
        
            if ( $result ) {
                return new \WP_REST_Response([
                    'message' => esc_html__( 'Virtual tour status has been updated.', 'happyvr' )
                ], 200);
            } else {
                return new \WP_REST_Response([
                    'message' => esc_html__( 'Failed to update virtual tour status.', 'happyvr' )
                ], 400);
            }
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'message' => esc_html__( 'Failed to update virtual tour status.', 'happyvr' ) . ' ' . esc_html( $e->getMessage() )
            ], 500);
        }
    }

    public function updateVirtualTourSceneTiles( \WP_REST_Request $request ) {
        $id = intval( $request->get_param( 'id' ), 10 );
        $sceneId = sanitize_key( $request->get_param( 'sceneId' ) );
        $data = $request->get_json_params();

        $required = ['scenePanoId', 'level', 'face', 'tileSize', 'tiles'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return new \WP_REST_Response([
                    'message' => "Missing field: $field"
                ], 400);
            }
        }

        $scenePanoId = sanitize_key( $data['scenePanoId'] );
        $level = intval( $data['level'], 10 );
        $face = sanitize_key( $data['face'] );
        $tileSize = intval( $data['tileSize'], 10 );
        $tiles = $data['tiles'];

        $allowed_faces = ['f', 'b', 'l', 'r', 'u', 'd', 'preview'];
        if (!in_array($face, $allowed_faces, true)) {
            return new \WP_REST_Response([
                'message' => esc_html__( 'Invalid face.', 'happyvr' )
            ], 400);
        }

        try {
            $result = false;

            if ($face === 'preview') {
                $result = DataModel::updateSceneImage( $id, $sceneId, $scenePanoId, 'preview', $tiles ) ;
            } else {
                $result = DataModel::updateSceneTiles( $id, $sceneId, $scenePanoId, $level, $face, $tileSize, $tiles ) ;
            }
        
            if ( $result ) {
                return new \WP_REST_Response([
                    'message' => esc_html__( 'Tile images have been updated.', 'happyvr' )
                ], 200);
            } else {
                return new \WP_REST_Response([
                    'message' => esc_html__( 'Failed to update tile images.', 'happyvr' )
                ], 400);
            }
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'message' => esc_html__( 'Failed to update tile images.', 'happyvr' ) . ' ' . esc_html( $e->getMessage() )
            ], 500);
        }
    }

    public function updateVirtualTourSceneThumb( \WP_REST_Request $request ) {
        $id = intval( $request->get_param( 'id' ), 10 );
        $sceneId = sanitize_key( $request->get_param( 'sceneId' ) );
        $data = $request->get_json_params();

        $scenePanoId = sanitize_key( $data['scenePanoId'] );
        $imageName = 'thumb';
        $imageData = $data['thumb'];

        try {
            $result = DataModel::updateSceneImage( $id, $sceneId, $scenePanoId, $imageName, $imageData ) ;
        
            if ( $result ) {
                return new \WP_REST_Response([
                    'message' => esc_html__( 'Scene thumb image has been updated.', 'happyvr' )
                ], 200);
            } else {
                return new \WP_REST_Response([
                    'message' => esc_html__( 'Failed to update scene thumb image.', 'happyvr' )
                ], 400);
            }
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'message' => esc_html__( 'Failed to update scene thumb image.', 'happyvr' ) . ' ' . esc_html( $e->getMessage() )
            ], 500);
        }
    }

    public function getIconSets( \WP_REST_Request $request ) {
         try {
            $iconSets = DataModel::getIconSets();
            
            if ( $iconSets ) {
                return new \WP_REST_Response([
                    'message' => esc_html__( 'Icon sets have been retrieved.', 'happyvr' ),
                    'data' => $iconSets
                ], 200);
            } else {
                return new \WP_REST_Response([
                    'message' => esc_html__( 'Failed to get icon sets.', 'happyvr' )
                ], 400);
            }
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'message' => esc_html__( 'Failed to get icon sets.', 'happyvr' ) . ' ' . esc_html( $e->getMessage() )
            ], 500);
        }
    }

    public function getIcons( \WP_REST_Request $request ) {
        $setId = sanitize_key( $request->get_param( 'set_id' ) );
        
        try {
            $icons = DataModel::getIcons( $setId );
            
            if ( $icons ) {
                return new \WP_REST_Response([
                    'message' => esc_html__( 'Icons have been retrieved.', 'happyvr' ),
                    'data' => $icons
                ], 200);
            } else {
                return new \WP_REST_Response([
                    'message' => esc_html__( 'Failed to get icons.', 'happyvr' )
                ], 400);
            }
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'message' => esc_html__( 'Failed to get icons.', 'happyvr' ) . ' ' . esc_html( $e->getMessage() )
            ], 500);
        }
    }
}