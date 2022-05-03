<?php



namespace WPCorePlugin;

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
        do_action(WPCorePlugin::PLUGIN_NAME . "_activate");
    }
}
