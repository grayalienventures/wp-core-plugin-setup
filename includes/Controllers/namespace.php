<?php

/**
 *
 * @package WPCorePlugin
 *
 */


namespace WPCorePlugin\Controllers;

use Exception;
use WP_REST_Controller;

use function WPCorePlugin\class_basename;

/**
 * Register specific Controllers.
 */
function register()
{
  
    /**
     * use forloop and ReflectionClass
     */
    $routes = [
        // wp/v2/token
        AuthenticationController::class,
    ];
   
    foreach ($routes as $key => $class) {
        $refl = new \ReflectionClass($class);
        $instance =  $refl->newInstance();
        if (($instance instanceof Controller || $instance instanceof  WP_REST_Controller) == false) {
            throw new Exception(class_basename($class) . " it's not instance WP_REST_Controller");
        }
        $instance->register_routes();
    }

}
