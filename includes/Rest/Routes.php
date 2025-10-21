<?php
namespace Yalogica\HappyVR\Rest;

defined( 'ABSPATH' ) || exit;

class Routes {
    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'restApiInit' ] );
    }

    public function restApiInit() {
        $controllers = [
            'SettingsController',
            'DataController'
        ];

        foreach ( $controllers as $controller ) {
            $class = __NAMESPACE__ . "\\Controllers\\{$controller}";
            $obj = new $class();
            $obj->registerRestRoutes();
        }
    }
}