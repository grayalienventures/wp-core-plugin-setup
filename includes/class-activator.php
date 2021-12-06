<?php

/**
 * Fired during plugin activation
 *
 * @link        http://www.intp.io
 * @since      1.0.0
 *
 * @package    Smuvers
 * @subpackage WP_Core_Plugin/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    WP_Core_Plugin
 * @subpackage WP_Core_Plugin/includes
 */

namespace WP_Core_Plugin;

class Activator
{

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function activate()
    {

        flush_rewrite_rules();
        do_action(WP_Core_Plugin::PLUGIN_NAME . "_activate");
    }
}
