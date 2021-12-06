<?php

/**
 * 
 */

namespace WP_Core_Plugin\Authentication;

use WP_Core_Plugin\JWT\JWT as JWT;

use WP_User;
use WP_Error;
use WP_Http;
use WP_Query;
use Exception;

class Token_Jwt
{

	const EXPIRE_LINGHT = DAY_IN_SECONDS;
	protected $secret_key;
	protected static $instance = NULL;
	public static function get_instance()
	{
		NULL === self::$instance and self::$instance = new self;
		return self::$instance;
	}


	function __construct()
	{
		if (!defined('SECURE_AUTH_KEY')) {
			return;
		}

		$this->secret_key = SECURE_AUTH_KEY;
	}


	public function validate_token($token)
	{
		/** Try to decode the token */
		try {
			//error_log("validate_token->SECURE_AUTH_KEY ".SECURE_AUTH_KEY);
			$jwt = JWT::decode($token, $this->secret_key, array('HS256'));

			/** The Token is decoded now validate the iss */
			if ($jwt->iss != get_bloginfo('url')) {
				/** The iss do not match, return error */
				return new WP_Error(
					'smuvs_core_jwt_bad_iss',
					'The iss do not match with this server',
					array(
						'status' => 403,
					)
				);
			}


			// Determine if the token has expired.
			$expiration_valid = $this->validate_expiration($jwt);
			if (is_wp_error($expiration_valid)) {
				return $expiration_valid;
			}

			return $jwt;
		} catch (Exception $e) {
			//error_log("Exception " . $e->getMessage());
			return new WP_Error(
				'rest_authentication_token_error',
				__('Invalid bearer token.', 'smuvs_core_jwt'),
				array(
					'status' => 403,
				)
			);
		}
	}

	public function validate_expiration($token)
	{

		if (!isset($token->exp)) {
			return new WP_Error(
				'rest_authentication_missing_token_expiration',
				__('Token must have an expiration.', 'smuvs_core_jwt'),
				array(
					'status' => 403,
				)
			);
		}

		if (time() > $token->exp) {
			return new WP_Error(
				'rest_authentication_token_expired',
				__('Token has expired.', 'smuvs_core_jwt'),
				array(
					'status' => 403,
				)
			);
		}

		return true;
	}
	/**
	 * generation_token
	 * @param array $args   // add more args
	 * @param integer $expire_linght 
	 * @return string token
	 */
	public function generation_token($args = array(), $expires = null)
	{

		$issuedAt = time();

		$notBefore = apply_filters('easyeatery_core_jwt_not_before', $issuedAt, $issuedAt);
		if (!empty($expires)) {
			$_expire = (int)$expires;
		} else {
			$_expire = apply_filters('easyeatery_core_jwt_expire', $issuedAt + (static::EXPIRE_LINGHT), $issuedAt);
		}


		$token = array_merge(
			array(
				'iss' => get_bloginfo('url'),
				'iat' => $issuedAt,
				'nbf' => $notBefore,
				'exp' => $_expire,
			),
			$args
		);

		$token = JWT::encode(apply_filters('easyeatery_core_jwt_token_before_sign', $token, $args), $this->secret_key);

		return $token;
	}

	/**
	 * Generate random uuid
	 *
	 * @return string
	 *
	 * @access protected
	 * @version 6.0.0
	 */
	protected function generateUuid()
	{
		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low"
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),

			// 16 bits for "time_mid"
			mt_rand(0, 0xffff),

			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand(0, 0x0fff) | 0x4000,

			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand(0, 0x3fff) | 0x8000,

			// 48 bits for "node"
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff)
		);
	}
}
