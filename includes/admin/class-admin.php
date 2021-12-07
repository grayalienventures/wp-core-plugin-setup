<?php

/**
 * 
 */

namespace WP_Core_Plugin\Admin;

use WP_Core_Plugin\WP_Core_Plugin;
use WP_Error;

class Admin
{


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
			array(get_class(), 'dispatch')
		);

		register_setting(WP_Core_Plugin::KEY_OPTIONS, WP_Core_Plugin::KEY_OPTION, array(get_class(), 'save_admin_sanitize_option'), 100);
	}



	public static function save_admin_sanitize_option($option)
	{

		$keys = array_keys(WP_Core_Plugin::get_defaults_options());
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
		$params = array('page' => BASE_SLUG) + wp_parse_args($params);
		return add_query_arg(urlencode_deep($params), $url);
	}




	public static function dispatch()
	{
		return self::render();
	}


	public static function render_tabs($current)
	{
		$base_slug = BASE_SLUG;
		$options = WP_Core_Plugin::get_options();
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
		return apply_filters(WP_Core_Plugin::PLUGIN_PREFIX . 'settings_tabs', $default_tabs);
	}



	public static function render()
	{
		$options = WP_Core_Plugin::get_options();
		$tabs = self::get_tabs();
		$current = (!isset($_GET['tab'])) ? current(array_keys($tabs)) : $_GET['tab'];


?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"></div>
			<h2><?php _e("MoveSheet Settings"); ?></h2>
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
