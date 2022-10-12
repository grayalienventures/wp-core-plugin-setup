<?php

/**
 * Plugin Name: WP Core Plugin
 * Plugin URI: https:/intp.io
 * Description: A moving industry plugin to help manage your operations and client interactions.
 * Version: 0.1
 * Author: INTP LLC
 * Author URI: https://intp.io
 **/

namespace WPCorePlugin;


const BASE_SLUG = 'wp_core_plugin';


class WPCorePlugin
{


    const PLUGIN_VERSION = "1.0.0";
    const BASE_SLUG = 'wp_core_plugin';
    const PLUGIN_NAME = "wp_core_plugin";
    const TITLE = "WP Core Plugin";

    const BASE_MENU_SLUG =  self::BASE_SLUG . '_menu';
    const KEY_OPTION =  self::BASE_SLUG . "_option";
    const KEY_OPTIONS =  self::BASE_SLUG . "_options";
    const PLUGIN_PREFIX = self::BASE_SLUG . "_";


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


        /**
         * notes 
         * we use includes/composer.json  for loading files in /includes 
         * autoload->psr-4
         * 
         */
        $this->include_file("vendor/autoload.php");
    
        // Authentication
        $this->include_file('Authentication/namespace.php');
        /**
         * Controllers
         * run function register 
         */
        $this->include_file('Controllers/namespace.php');

        if (is_admin()) {
            $this->include_file('Admin/namespace.php');
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


    /**
     * get_site_url
     */
    public static  function  get_site_url()
    {
        //$protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
        //  wp_guess_url()

        $protocol =  "http://";
        if (
            //straight
            isset($_SERVER['HTTPS']) && in_array($_SERVER['HTTPS'], ['on', 1])
            ||
            //proxy forwarding
            isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
        ) {
            $protocol = 'https://';
        }

        $parse_url = wp_parse_url(home_url());
        $path = "";
        if (is_array($parse_url) && isset($parse_url['path'])) {
            $path = $parse_url['path'];
        }


        $domainName = $_SERVER['HTTP_HOST'];
        return $protocol . $domainName . $path;
    }
}


function run_plugin()
{
    global $wp_core_plugin;
    $wp_core_plugin = WPCorePlugin::get_instance();
}

run_plugin();

/**
 * 
 */
function plugin_activate()
{
    require_once plugin_dir_path(__FILE__) . 'includes/Activator.php';
    Activator::activate();
}
register_activation_hook(__FILE__, __NAMESPACE__ . '\\plugin_activate');

function plugin_deactivate()
{
}
register_deactivation_hook(__FILE__,  __NAMESPACE__ . '\\plugin_deactivate');
