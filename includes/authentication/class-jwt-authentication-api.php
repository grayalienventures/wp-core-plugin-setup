<?php

namespace WP_Core_Plugin\Authentication;
// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}


class Jwt_Authentication_Api
{

	/**
	 * Get current user IP.
	 *
	 * @return string
	 */
	public static function get_ip()
	{
		return !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : __('Unknown', 'jwt-authentication');
	}


	/**
	 * Check wether setting is defined globally in wp-config.php
	 *
	 * @param  string  $key settings key
	 * @return boolean
	 */
	public static function is_global($key)
	{
		return defined($key);
	}


	/**
	 * Get plugin settings array.
	 *
	 * @return array
	 */
	public static function get_db_settings()
	{
		return get_option('jwt_authentication_settings');
	}


	/**
	 * Get the auth key.
	 *
	 * @return string
	 */
	public static function get_key()
	{
		if (defined('SECURE_AUTH_KEY')) {
			return SECURE_AUTH_KEY;
		} else {
			$settings = self::get_db_settings();
			if ($settings) {
				return $settings['secret_key'];
			}
		}
		return false;
	}


	public static function get_cors()
	{
		if (defined('JWT_AUTHENTICATION_CORS_ENABLE')) {
			return JWT_AUTHENTICATION_CORS_ENABLE;
		} else {
			$settings = self::get_db_settings();
			if ($settings) {
				return $settings['enable_cors'];
			}
		}
		return false;
	}
}
