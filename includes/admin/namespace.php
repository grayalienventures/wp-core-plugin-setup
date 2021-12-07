<?php

namespace WP_Core_Plugin\Admin;

use Jwt_Authentication_Profile;
use Jwt_Authentication_Settings;
use WP_Core_Plugin\WP_Core_Plugin;

//add_action( 'admin_menu', "register" );

const BASE_SLUG =  WP_Core_Plugin::BASE_SLUG;
const PLUGIN_NAME = WP_Core_Plugin::PLUGIN_NAME;
/**
 * Get the URL for an admin page.
 *
 * @param array|string $params Map of parameter key => value, or wp_parse_args string.
 *
 * @return string Requested URL.
 */
function get_url($params = [])
{
    $url    = admin_url("admin.php");
    $params = ['page' => BASE_SLUG] + wp_parse_args($params);

    return add_query_arg(urlencode_deep($params), $url);
}


function register()
{

    Admin::register();
    Jwt_Authentication_Settings::get_instance()->register();
    Jwt_Authentication_Profile::get_instance()->init();
    do_action(WP_Core_Plugin::PLUGIN_NAME . "_admin_register");
}
