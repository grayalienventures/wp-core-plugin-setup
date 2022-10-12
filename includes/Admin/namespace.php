<?php

namespace WPCorePlugin\Admin;

use Jwt_Authentication_Profile;

use WPCorePlugin\WPCorePlugin;

//add_action( 'admin_menu', "register" );

const BASE_SLUG =  WPCorePlugin::BASE_SLUG;
const PLUGIN_NAME = WPCorePlugin::PLUGIN_NAME;

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

    /**
     * Include anything we need that relies on admin classes/functions
     */
    $hook = add_menu_page(
        // Page title
        __('WP Core Plugin', PLUGIN_NAME),
        // Menu title
        _x('WP Core Plugin', 'menu title', PLUGIN_NAME),
        // Capability
        'manage_options',
        // Menu slug
        BASE_SLUG,
        // Callback
        __NAMESPACE__ . "\\dispatch"
    );

    /**
     * first menu item 
     */
    Settings::register();

    JwtAuthenticationSettings::get_instance()->register();
    JwtAuthenticationProfile::get_instance()->init();
    do_action(WPCorePlugin::PLUGIN_NAME . "_admin_register");
}


function dispatch()
{
}
