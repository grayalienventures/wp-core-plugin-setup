<?php

namespace WPCorePlugin\Admin;

use ToklotCorePlugin\Authentication\AuthenticationApi;

/**
 * The user profile specific functionality.
 *
 */

class JwtAuthenticationSettings
{

	/**
	 * Initialize the class and set its properties.
	 *s
	 */

	protected static $instance = NULL;
	public static function get_instance()
	{
		NULL === self::$instance and self::$instance = new self;
		return self::$instance;
	}

	public function __construct()
	{
	}

	public function register()
	{

		add_action('admin_menu', array($this, 'add_admin_menu'), 1000);
		add_action('admin_init', array($this, 'settings_init'));
	}


	/**
	 * Adds the menu page to options.
	 *
	 */
	public function add_admin_menu()
	{
		add_options_page(
			'JWT Authentication',
			'JWT Authentication',
			'manage_options',
			'jwt_authentication',
			array($this, 'jwt_authentication_options_page')
		);
	}


	/**
	 * Initialize all settings.
	 *	 
	 */
	public function settings_init()
	{
		register_setting('jwt_authentication', 'jwt_authentication_settings');

		add_settings_section(
			'jwt_authentication_section',
			__('Basic configuration', 'jwt-authentication'),
			array($this, 'settings_section_callback'),
			'jwt_authentication'
		);

		add_settings_field(
			'secret_key',
			__('Secret Key', 'intp-jwt-authentication'),
			array($this, 'settings_secret_callback'),
			'jwt_authentication',
			'jwt_authentication_section'
		);

		add_settings_field(
			'enable_cors',
			// translators: %s is a link to CORS docs.
			sprintf(__('Enable %s', 'intp-jwt-authentication'), '<a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS" target="_blank" rel="nofollow">CORS</a>'),
			array($this, 'settings_cors_callback'),
			'jwt_authentication',
			'jwt_authentication_section'
		);
	}


	/**
	 * Secret key field callback.
	 *	 
	 */
	public function settings_secret_callback()
	{
		$secret_key = AuthenticationApi::get_key();
		$is_global  = AuthenticationApi::is_global('JWT_AUTHENTICATION_SECRET_KEY');
		include plugin_dir_path(__FILE__) . 'views/settings-jwt/secret-key.php';
	}


	/**
	 * Enable/disable cors field callback.
	 *	 
	 */
	public function settings_cors_callback()
	{
		$enable_cors = AuthenticationApi::get_cors();
		$is_global   = AuthenticationApi::is_global('JWT_AUTHENTICATION_CORS_ENABLE');
		include plugin_dir_path(__FILE__) . 'views/settings-jwt/enable-cors.php';
	}


	/**
	 * Section callback.
	 *	 
	 */
	public function settings_section_callback()
	{
		echo sprintf(__('This is all you need to start using JWT authentication.<br /> You can also specify these in wp-config.php instead using %1$s %2$s', 'jwt-authentication'), "<br /><br /><code>define( 'JWT_AUTHENTICATION_SECRET_KEY', YOURKEY );</code>", "<br /><br /><code>define( 'JWT_AUTHENTICATION_CORS_ENABLE', true );</code>"); // phpcs:ignore

	}


	/**
	 * Settings form callback.
	 *	 
	 */
	public function jwt_authentication_options_page()
	{
		include plugin_dir_path(__FILE__) . 'views/settings-jwt/page.php';
	}
}
