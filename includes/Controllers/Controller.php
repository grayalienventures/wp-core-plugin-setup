<?php

/**
 * 
 */

namespace WPCorePlugin\Controllers;

use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use WPCorePlugin\WPCorePlugin;
use WP_Error;
use WP_REST_Controller;

use function WPCorePlugin\debug_log;
use function WPCorePlugin\rest_get_param;

abstract class Controller extends WP_REST_Controller
{
    public $namespace;
    public $rest_base;


    public function __construct()
    {

        add_filter("rest_authentication_require_token", array($this, "check_jwt_auth_except"), 10, 3);
        $this->init();
    }


    /**
     *  use for initialize, set properties  
     */
    public function init()
    {
        debug_log("'Controller::init",  sprintf(__("Method '%s' must be overridden."), __METHOD__), 'e');
        _doing_it_wrong(
            'Controller::init',
            /* translators: %s: register_routes() */
            sprintf(__("Method '%s' must be overridden."), __METHOD__),
            '4.7.0'
        );
    }


    /**
     * get_auth_permissions_check
     */
    public function get_auth_permissions_check($request)
    {
        if (!is_user_logged_in()) {
            return new WP_Error("rest_" . get_called_class() . "_error_auth", __('Authentication error'), array('status' => rest_authorization_required_code()));
        }
        return true;
    }


    /**
     * get_item_permissions_check
     */
    public function get_items_permissions_check($request)
    {
        if (!is_user_logged_in()) {
            return new WP_Error("rest_" . get_called_class() . "_error_auth", __('Authentication error'), array('status' => rest_authorization_required_code()));
        }

        return true;
    }



    public function update_item_permissions_check($request)
    {
        return $this->get_auth_permissions_check($request);
    }
    /**
     * 
     */
    public function except_auth_jwt()
    {
        // $routes =  [
        //     // if we will add new path to this endpoint and we not need check jwt auth header add in here 
        //     sprintf('/%s/%s', $this->namespace, $this->rest_base),        
        // ];
        return [];
    }


    /**
     * check_jwt_auth_except
     */
    public function check_jwt_auth_except($require_token, $request_uri, $request_method)
    {

     
        $paths_not_require_token = $this->except_auth_jwt();
        foreach ($paths_not_require_token as $key => $value) {
            if (('POST' === $request_method || 'OPTIONS' === $request_method) && strpos($request_uri, $value)) {
                $require_token = false;
                break;
            }
        }
        return $require_token;
    }




    /**
     * get_collection_params
     */
    public function get_collection_params()
    {
        $query_params = parent::get_collection_params();
        $query_params['context']['default'] = 'view';

        $query_params['exclude'] = array(
            'description'        => __('Ensure result set excludes specific ids.'),
            'type'               => 'array',
            'default'            => array(),
            'sanitize_callback'  => 'wp_parse_id_list',
        );
        $query_params['exclude'] = array(
            'description' => __('Ensure result set excludes specific IDs.'),
            'type'        => 'array',
            'items'       => array(
                'type' => 'integer',
            ),
            'default'     => array(),
        );

        $query_params['include'] = array(
            'description' => __('Limit result set to specific IDs.'),
            'type'        => 'array',
            'items'       => array(
                'type' => 'integer',
            ),
            'default'     => array(),
        );

        $query_params['offset'] = array(
            'description' => __('Offset the result set by a specific number of items.'),
            'type'        => 'integer',
        );

        $query_params['order'] = array(
            'default'     => 'asc',
            'description' => __('Order sort attribute ascending or descending.'),
            'enum'        => array('asc', 'desc'),
            'type'        => 'string',
        );

        $query_params['expand'] = array(
            'description' => __('You can expand multiple objects at once by identifying multiple items in the expand array.'),
            'type'        => 'array',
            'items'       => array(
                'type' => 'string',
            ),
            'default'     => array(),
        );

        $query_params['all'] = array(
            'default'     => false,
            'description' => __('All results'),
            'type'        => 'boolean',
        );



        return $query_params;
    }

    /**
     * check_is_array
     */
    public function check_is_array($value, $request, $param)
    {

        if (!is_array($value)) {
            return false;
        }

        return $value;
    }


    /**
     * 
     */
    public function send_response_error($code = "", $message = '', $status = 404)
    {
        return new WP_Error($code, $message, array('status' => $status));
    }
    /**
     * 
     */
    protected function prepare_value($value, $schema)
    {

        if (is_wp_error(rest_validate_value_from_schema($value, $schema))) {
            return new WP_Error('rest_invalid_params', __('Invalid params.'), array('status' => 400));
        }

        return rest_sanitize_value_from_schema($value, $schema);
    }


    /**
     * 
     */
    protected function prepare_date_response($date_gmt, $date = null)
    {
        // Use the date if passed.
        if (isset($date)) {
            return mysql_to_rfc3339($date);
        }
        // Return null if $date_gmt is empty/zeros.
        if ('0000-00-00 00:00:00' === $date_gmt) {
            return null;
        }
        // Return the formatted datetime.
        return mysql_to_rfc3339($date_gmt);
    }

    /**
     *  get_client_timezone_offest
     */
    public function get_client_timezone_offest()
    {

        $timezone_offset = rest_get_param("HTTP_X_TIMEZONE_OFFSET", $_SERVER, 0);

        $timezone_offset_second = $timezone_offset  * 60;
        return $timezone_offset_second;
    }


    /**
     * get_url_from_request
     */
    protected function get_url_from_request($request)
    {
        return   WPCorePlugin::get_site_url() . "/" . rest_get_url_prefix()  . $request->get_route();
    }
    /**
     * get_base_url
     */
    protected function get_base_url($path = "")
    {
        $path = !empty($path) ? "/" . $path : "";

        return WPCorePlugin::get_site_url() . "/" . rest_get_url_prefix() . "/" . $this->namespace . "/" . $this->rest_base . $path;
    }
}
