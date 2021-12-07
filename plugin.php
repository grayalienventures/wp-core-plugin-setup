<?php

/**
 * Plugin Name: WP Core Plugin
 * Plugin URI: https:/intp.io
 * Description: A moving industry plugin to help manage your operations and client interactions.
 * Version: 0.1
 * Author: INTP LLC
 * Author URI: https://intp.io
 **/

namespace WP_Core_Plugin;


const BASE_SLUG = 'wp_core_plugin';


class WP_Core_Plugin
{

    const PLUGIN_VERSION = "1.0.0";
    const BASE_SLUG = 'wp_core_plugin';
    const PLUGIN_NAME = "wp_core_plugin";

    const NAME = "wp_core_plugin";
    const BASE_MENU_SLUG = 'wp_core_plugin_menu';
    const KEY_OPTION = "wp_core_plugin_option";
    const KEY_OPTIONS = "wp_core_plugin_options";
    const PLUGIN_PREFIX = "wp_core_plugin_";


    private $uri_base = "";
    protected static $instance = NULL;
    public static function get_instance()
    {
        NULL === self::$instance and self::$instance = new self;
        return self::$instance;
    }

    function __construct()
    {
        $this->plugin_name = BASE_SLUG;
        $this->plugin_version = self::PLUGIN_VERSION;
        $this->uri_base =  plugin_dir_url(__FILE__);

        $this->load_dependencies();
        $this->bootstrap();

        //add_action("init",array($this,"run"));
    }


    public function bootstrap()
    {
        add_action('plugins_loaded', array($this, 'plugins_loaded'));

        add_action('init', array($this, 'register_type'));
        add_action('admin_menu', __NAMESPACE__ . '\\Admin\\register');
    }


    public function plugins_loaded()
    {
    }


    /**
     *  register custome post 
     */
    public function register_type()
    {
    }


    /**
     * get_defaults_options
     */
    public static function get_defaults_options()
    {
        $defaults_fields = array(
            'server_socket_uri' => "",
            'map_key_api' => '',
            'is_production' => '',
            'fcm_api_key' => "",
            'push_notification_enabled' => true,
            "logging" => "",

        );
        return apply_filters(self::KEY_OPTIONS . "_defaults", $defaults_fields);
    }



    public static function  get_options()
    {
        $options = get_option(self::KEY_OPTION);
        if (is_array($options)) {
            return wp_parse_args($options, self::get_defaults_options());
        }
        return self::get_defaults_options();
    }



    public static function get_dir_path()
    {
        return plugin_dir_path(__FILE__);
    }


    public static function get_dir_url()
    {
        return plugin_dir_url(__FILE__);
    }


    public static function get_logo_url()
    {
        return self::get_dir_url() . 'public/images/logo.png';
    }


    /**
     * Get the URL for an admin page.
     *
     * @param array|string $params Map of parameter key => value, or wp_parse_args string.
     * @return string Requested URL.
     */
    public static function get_url($params = array())
    {
        $url = admin_url('admin.php');
        $params = array('page' => BASE_SLUG) + wp_parse_args($params);
        return add_query_arg(urlencode_deep($params), $url);
    }




    /**
     * Loads all dependencies in our plugin.
     *
     */
    public function load_dependencies()
    {

        $this->include_file("vendor/autoload.php");
        // JWT Classes.
        foreach (glob(self::get_dir_path() . '/includes/php-jwt/*.php') as $filename) {
            require_once $filename;
        }

        $this->include_file("functions.php");

        $this->include_file('parse_user_agent.php');

        $this->include_file('authentication/namespace.php');
        $this->include_file('authentication/class-jwt-authentication-api.php');
        $this->include_file('authentication/class-jwt-authentication-rest.php');
        $this->include_file('authentication/class-token-jwt.php');



        // endpoints
        $this->include_file('endpoints/namespace.php');

        if (is_admin()) {

            $this->include_file('admin/namespace.php');
            $this->include_file('admin/class-admin.php');
           
         
            $this->include_file('admin/class-user-companies-profile.php');

            $this->include_file('admin/class-jwt-authentication-settings.php');
            $this->include_file('admin/class-jwt-authentication-profile.php');
        }

    }




    /**
     * Includes a single file located inside /includes.
     *
     * @param string $path relative path to /includes
     * @since 1.0
     */
    private function include_file($path)
    {
        $plugin_name    = $this->plugin_name;
        $plugin_version = $this->plugin_version;

        $includes_dir = trailingslashit(plugin_dir_path(__FILE__) . 'includes');

        if (file_exists($includes_dir . $path)) {

            include_once $includes_dir . $path;
        }
    }
}


function run_plugin()
{
    global $wp_core_plugin;
    $wp_core_plugin = WP_Core_Plugin::get_instance();
}

run_plugin();

/**
 * 
 */
function plugin_activate()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-activator.php';
    Activator::activate();
}
register_activation_hook(__FILE__, __NAMESPACE__ . '\\plugin_activate');

function plugin_deactivate()
{
}
register_deactivation_hook(__FILE__,  __NAMESPACE__ . '\\plugin_deactivate');
