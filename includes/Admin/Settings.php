<?php

/**
 * 
 */

namespace WPCorePlugin\Admin;

use WPCorePlugin\WPCorePlugin;
use WP_Error;

class Settings
{

	const BASE_SLUG_SETTINGS = BASE_SLUG . "_settings";

	public static function get_admin_url()
	{
		return admin_url("admin.php");
	}



	public static function get_base_slug()
	{
		return  BASE_SLUG;
	}
	/**
	 * Register the admin page
	 */
	public static function register()
	{

		add_submenu_page(BASE_SLUG, 'Settings', 'Settings', 'manage_options', self::BASE_SLUG_SETTINGS, 	array(get_class(), 'dispatch'));
		/**
		 * with different name for first submenu item 
		 */
		remove_submenu_page(BASE_SLUG, BASE_SLUG);

		register_setting(WPCorePlugin::KEY_OPTIONS, WPCorePlugin::KEY_OPTION, array(get_class(), 'save_admin_sanitize_option'), 100);
	}



	public static function save_admin_sanitize_option($option)
	{

		$keys = array_keys(WPCorePlugin::get_defaults_options());
		foreach ($keys as $key) {
			if (!isset($option[$key])) {
				$option[$key] = '';
			}
		}
		return $option;
	}



	/**
	 * Get the URL for an admin page.
	 *
	 * @param array|string $params Map of parameter key => value, or wp_parse_args string.
	 * @return string Requested URL.
	 */
	protected static function get_url($params = array())
	{
		$url = self::get_admin_url();
		$params = array('page' => self::BASE_SLUG_SETTINGS) + wp_parse_args($params);
		return add_query_arg(urlencode_deep($params), $url);
	}




	public static function dispatch()
	{
		return self::render();
	}


	public static function render_tabs($current)
	{
		$base_slug = BASE_SLUG;
		$options = WPCorePlugin::get_options();
		switch ($current) {
			case 'logs':
				include plugin_dir_path(__FILE__) . 'views/settings/logs.php';
				break;

			default:
				include plugin_dir_path(__FILE__) . 'views/settings/page.php';
				break;
		}
	}


	/**
	 * get_tabs
	 */
	public static function get_tabs()
	{
		$default_tabs = array(
			'general' => __('Settings'),
			'logs' => __('Logs'),
			//'extensions' => __( 'Get Extensions', TWL_TD ),
		);
		return apply_filters(WPCorePlugin::PLUGIN_PREFIX . 'settings_tabs', $default_tabs);
	}



	public static function render()
	{
		$options = WPCorePlugin::get_options();
		$tabs = self::get_tabs();
		$current = (!isset($_GET['tab'])) ? current(array_keys($tabs)) : $_GET['tab'];


?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"></div>
			<h2><?php _e(WPCorePlugin::TITLE . " Settings"); ?></h2>
			<h2 class="nav-tab-wrapper">
				<?php
				foreach ($tabs as $tab => $name) {
					$classes = array('nav-tab');
					if ($tab == $current) {
						$classes[] = 'nav-tab-active';
					}
					$href = esc_url(add_query_arg('tab', $tab, self::get_url()));
					echo '<a class="' . implode(' ', $classes) . '" href="' . $href . '">' . $name . '</a>';
				}
				?>
			</h2>

			<?php self::render_tabs($current); ?>
		</div>
<?php
	}
}
