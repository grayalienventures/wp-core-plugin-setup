<?php

/**
 * Most of the core functionality of the plugin happen here.
 *
 
 */

// Require the JWT library.
namespace WPCorePlugin\Controllers;

use Exception;
use LucrumCorePlugin\Controllers\Controller;
use WPCorePlugin\JWT\JWT as JWT;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_User;

use function WPCorePlugin\debug_log;

class AuthenticationController extends Controller
{

	protected $rest_base;

	protected $namespace;

	protected $secret_key;
	/**
	 * Store errors to display if the JWT is wrong
	 *
	 * @var WP_Error
	 */
	private $jwt_error = null;

	const _NAMESPACE_ = 'wp/v2';

	const _REST_BASE_ = 'token';

	private $expires_token = null;
	private $expires_refresh_token = null;
	/**
	 * Initialize the class and set its properties.
	 *
	 
	 */
	public function __construct()
	{

		$this->api_version    = 2;
		$this->namespace      = "wp" . '/v' . $this->api_version;
		$this->rest_base      = "token";
		if (!defined('SECURE_AUTH_KEY')) {
			return;
		}

		$this->secret_key = SECURE_AUTH_KEY;

		$this->expires_token = apply_filters('rest_authentication_token_expires', WEEK_IN_SECONDS);
		$this->expires_refresh_token = apply_filters('rest_authentication_refresh_token_expires', YEAR_IN_SECONDS);
		$this->init();
	}



	/**
	 * Initialize this class with hooks.
	 *
	 * @return void
	 */
	public function init()
	{

		// add_action('rest_api_init', array($this, 'add_api_routes'));
		add_filter('rest_api_init', array($this, 'add_cors_support'));
		add_filter('rest_pre_dispatch', array($this, 'rest_pre_dispatch'), 10, 2);
		add_filter('rest_authentication_errors', array($this, 'authenticate'));
		add_filter('http_request_args', array($this, 'http_request_args'), 10, 2);
	}

	/**
	 * when use wp_remote_request add token to header
	 * we need this when handle request from WP_Async_Request->dispatch
	 */
	public function http_request_args($r, $url)
	{
		//error_log('http_request_args');
		$admin_ajax = admin_url('admin-ajax.php');
		$header = $this->get_auth_header();
		if (is_wp_error($header)) {
			return $r;
		}

		// Get the Bearer token from the header.
		$token = $this->get_token($header);
		if (is_wp_error($token)) {
			//return $token;
			return $r;
		}
		//error_log('token: '.$token );
		$r['headers']['Authorization'] = 'Bearer ' . $token;
		return $r;
	}




	/**
	 * Add the endpoints to the API
	 */
	public function register_routes()
	{
		register_rest_route(
			$this->namespace,
			'token',
			array(
				array(
					'methods'  => 'POST',
					'callback' => array($this, 'generate_token'),
					'args'     => [
						'username' => [
							// 'required'          => true,
							'type'              => 'string',
							'validate_callback' => "rest_validate_request_arg",
						],
						'password'  => [
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => 'rest_validate_request_arg',
						],
						// 'code'       => [
						// 	'required'          => true,
						// 	'type'              => 'string',
						// 	'validate_callback' => 'rest_validate_request_arg',
						// ],
					],

				),
				'schema' => array($this, 'get_public_item_schema'),
			)
		);

		register_rest_route(
			$this->namespace,
			'token/validate',
			array(
				array(
					'methods'  => 'POST',
					'callback' => array($this, 'validate_token'),
				),
				'schema' => array($this, 'get_public_item_schema'),
			)

		);

		register_rest_route(
			$this->namespace,
			'token/refresh',
			array(
				array(
					'methods'  => 'POST',
					'callback' => array($this, 'refresh_token'),
				),
				'schema' => array($this, 'get_public_item_schema'),
			)

		);

		register_rest_route(
			$this->namespace,
			'token/revoke',
			array(
				'methods'  => 'POST',
				'callback' => array($this, 'revoke_token'),
			)
		);



		register_rest_route(
			$this->namespace,
			'token/password/lost',
			array(
				'methods'  => 'POST',
				'callback' => array($this, 'lost_password'),
				'args' => [
					'email'              => array(
						'description'    => __('The email address for the user.'),
						'type'           => 'string',
						'format'         => 'email',
						'required'       => true,
					),
				]
			)
		);

		register_rest_route(
			$this->namespace,
			'token/password/validate',
			array(
				'methods'  => 'POST',
				'callback' => array($this, 'password_validate_code_pin'),
				'args' => [
					'email'              => array(
						'description'    => __('The email address for the user.'),
						'type'           => 'string',
						'format'         => 'email',
						'required'       => true,
					),
					'code_pin' => array(
						'type'           => 'integer',
						'required'       => true,
					)
				]
			)
		);

		register_rest_route(
			$this->namespace,
			'token/password/reset',
			array(
				'methods'  => 'POST',
				'callback' => array($this, 'password_reset'),
				'args' => [
					'email'              => array(
						'description'    => __('The email address for the user.'),
						'type'           => 'string',
						'format'         => 'email',
						'required'       => true,
					),
					'code_pin' => array(
						'type'           => 'integer',
						'required'       => true,
					),
					'password' => array(
						'type'           => 'string',
						'required'       => true,
					),
					'confirm_password' => array(
						'type'           => 'string',
						'required'       => true,
					)

				]
			)
		);
	}

	/**
	 * Add CORs suppot to the request.
	 */
	public function add_cors_support()
	{
		// $enable_cors = AuthenticationApi::get_cors();
		$enable_cors = true;
		if ($enable_cors) {
			$headers = apply_filters('jwt_auth_cors_allow_headers', 'Access-Control-Allow-Headers, Content-Type, Authorization, x-custom-header');
			header(sprintf('Access-Control-Allow-Headers: %s', $headers));
		}
		// $request_uri    = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field($_SERVER['REQUEST_URI']) : false;
		// debug_log("add_cors_support",$request_uri);
	}


	/**
	 * Get the user and password in the request body and generate a JWT
	 *
	 * @param object $request a WP REST request object
	 * @return mixed Either a WP_Error or current user data.
	 */
	public function generate_token($request)
	{
		$secret_key = $this->secret_key;
		$username   = !empty($request->get_param('email')) ? $request->get_param('email') : $request->get_param('username');
		$password   = $request->get_param('password');
		/** First thing, check the secret key if not exist return a error*/
		if (!$secret_key) {
			return new WP_Error(
				'jwt_auth_bad_config',
				__('JWT is not configurated properly, please contact the admin. The key is missing.', 'jwt-authentication'),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}
		/** Try to authenticate the user with the passed credentials*/
		$user = wp_authenticate($username, $password);

		/** If the authentication fails return a error*/
		if (is_wp_error($user)) {
			$error_code = $user->get_error_code();
			return new WP_Error(
				'[jwt_auth] ' . $error_code,
				'Invalid email address or incorrect password.',
				array(
					'status' =>  400,
				)
			);
		}

		$expires = $this->expires_token;

		$payload = $this->generate_payload($user, $request, $expires, false);

		// Generate JWT token.
		$token = $this->jwt('encode', $payload, $this->secret_key);

		/**
		 * Return response containing the JWT token and $user data.
		 *
		 * @param array           $response The REST response.
		 * @param WP_User|Object  $user The authenticated user object.
		 * @param WP_RET_Request $request The authentication request.
		 */
		$response = array(
			'token' => $token,
			'data'  => $payload['data']['user'],
			'exp'   => $payload['exp'],
		);


		$response = $this->append_refresh_token($response, $user, $request, $payload['uuid']);

		//error_log("response" . print_r($response, true));
		$jwt_data   = $this->get_user_jwt_data($user->ID);
		$new_jwt_data = array(
			'uuid'      	=>  $payload['uuid'],
			// 'issued_at' => $payload['issued_at'],
			// 'expires'   => $payload['expires'],
			'ip'        	=> self::get_ip(),
			'ua'        	=> $_SERVER['HTTP_USER_AGENT'],
			'created'   	=> date('F j, Y g:i a',  time()),
			'last_used' 	=> time(),
			'token'  		=> $token,
			'refresh_token' => $response['refresh_token'],
		);
		$jwt_data[] = $new_jwt_data;

		//error_log("new_jwt_data" . print_r($new_jwt_data, true));
		update_user_meta($user->data->ID, 'jwt_data', apply_filters('jwt_auth_save_user_data', $jwt_data));


		return  apply_filters(
			'rest_authentication_token_rrefresh_token_response',
			$response,
			$user,
			$request
		);
	}



	/**
	 * Add a refresh token to the JWT token.
	 *
	 * @param WP_User|Object  $user    The authenticated user object.
	 * @param WP_REST_Request $request The authentication request.
	 * @param int             $expires The number of seconds until the token expires.
	 * @param boolean         $refresh Whether the payload is for a refresh token or not.
	 *
	 * @return array|WP_Error
	 */
	public function generate_payload(WP_User $user, WP_REST_Request $request, $expires, $refresh = false, $response = null, $uuid = null)
	{
		if (!isset($user->ID)) {
			return new WP_Error(
				'rest_authentication_missing_user_id',
				__('The user ID is missing from the user object.', 'jwt-auth'),
				array(
					'status' =>  rest_authorization_required_code(),
				)
			);
		}

		if (!isset($user->data->user_login)) {
			return new WP_Error(
				'rest_authentication_missing_user_login',
				__('The user_login is missing from the user object.', 'jwt-auth'),
				array(
					'status' =>  rest_authorization_required_code(),
				)
			);
		}

		if (!isset($user->data->user_email)) {
			return new WP_Error(
				'rest_authentication_missing_user_email',
				__('The user_email is missing from the user object.', 'jwt-auth'),
				array(
					'status' =>  rest_authorization_required_code(),
				)
			);
		}


		$issued_at  = time();
		$not_before = apply_filters('jwt_auth_not_before', $issued_at);
		$expire     = apply_filters('jwt_auth_expire', $issued_at + $expires, $issued_at, $user);

		if (!$uuid) {
			$uuid  = wp_generate_uuid4();
		}

		// JWT Reserved claims.
		$reserved = array(
			'uuid' => $uuid,
			'iss'  => get_bloginfo('url'), // Token issuer.
			'iat'  => $issued_at, // Token issued at.
			'nbf'  => $not_before, // Token accepted not before.
			'exp'  => $expire, // Token expiry.
		);

		// JWT Private claims.
		$private = array(
			'data' => array(
				'user' => array(
					'id'        	    => $user->ID,
					'type'       		=> isset($user->type) ? $user->type : 'wp_user',
					'user_login' 		=> $user->data->user_login,
					'user_email' 		=> $user->data->user_email,
					'user_nicename'     => $user->data->user_nicename,
					'user_display_name' => $user->data->display_name,
					'roles'			    => $user->roles,
				),
			),
		);

		if (true === $refresh && $response) {
			$private['data']['user']['token_type'] = 'refresh';
		}



		/**
		 * JWT Payload.
		 *
		 * The `data` private claim will always be added, but additional claims can be added via the
		 * `rest_authentication_token_private_claims` filter. The data array will be included in the
		 * REST response, do not include sensitive user data in that array.
		 *
		 * @param array           $payload The payload used to generate the token.
		 * @param WP_User|Object  $user The authenticated user object.
		 * @param WP_REST_Request $request The authentication request.
		 */
		$payload = apply_filters(
			'rest_authentication_token_private_claims',
			array_merge($reserved, $private),
			$user,
			$request
		);

		return $payload;
	}


	/**
	 * Get a users tokens.
	 *
	 *
	 * @param int $user_id User ID.
	 * @return array
	 */
	public function get_user_jwt_data($user_id)
	{
		$tokens = get_user_meta($user_id, "jwt_data", true);

		if (!is_array($tokens)) {
			return array();
		}

		return $tokens;
	}


	/**
	 * Append a refresh token to the JWT token REST response.
	 *
	 * @param array           $response The REST response.
	 * @param WP_User|Object  $user The authenticated user object.
	 * @param WP_REST_Request $request The authentication request.
	 *
	 * @return mixed
	 */
	public function append_refresh_token($response, $user, WP_REST_Request $request, $uuid = null)
	{

		/**
		 * Determines the number of seconds a refresh token will be valid.
		 *
		 * @param int $expires Number of seconds until the refresh token expires. Default is `31536000`, which is 1 year.
		 */
		$expires = $this->expires_refresh_token;

		// Generate the payload.
		$payload = $this->generate_payload($user, $request, $expires, true, $response, $uuid);

		if (is_wp_error($payload)) {
			return $payload;
		}


		// Generate JWT token.
		$response['refresh_token'] = $this->jwt('encode', $payload, $this->secret_key);

		// Setup some user meta data we can use for our UI.
		// error_log(print_r($response,true));
		return $response;
	}

	/**
	 * Decode the JSON Web Token.
	 *
	 * @param string $token The encoded JWT.
	 *
	 * @return object|WP_Error Return the decoded JWT, or WP_Error on failure.
	 */
	public function decode_token($token)
	{

		//error_log("decode_token".$token);
		try {
			return $this->jwt('decode', $token, $this->secret_key, array('HS256'));
		} catch (Exception $e) {

			// Return exceptions as WP_Errors.
			return new WP_Error(
				'rest_authentication_token_error',
				__('Invalid authentication token.', 'jwt-auth'),
				array(
					'status' =>  rest_authorization_required_code(),
				)
			);
		}
	}

	/**
	 * Get a JWT in the header and generate a JWT
	 *
	 * @return mixed Either a WP_Error or an object with a JWT token.
	 */
	public function refresh_token($request)
	{
		//error_log("refresh_token".print_r($request,true));
		// validate refresh token 
		$token = $this->validate_token(false, true);

		if (is_wp_error($token)) {
			return $token;
		}

		if (!isset($token->data->user->id)) {
			return new WP_Error(
				'rest_authentication_missing_refresh_token_user_id',
				__('Refresh token user must have an ID.', 'jwt-auth'),
				array(
					'status' =>  rest_authorization_required_code(),
				)
			);
		}
		// error_log("refresh_token->token".print_r($token,true));
		//error_log("refresh_token->user" . print_r($token->data->user, true));
		if (!isset($token->data->user->token_type) || 'refresh' !== $token->data->user->token_type) {
			return new WP_Error(
				'rest_authentication_invalid_token_type',
				__('Refresh token user must have a token_type of refresh.', 'jwt-auth'),
				array(
					'status' =>  rest_authorization_required_code(),
				)
			);
		}

		$user = new WP_User($token->data->user->id);

		if (false === $user) {
			return new WP_Error(
				'rest_authentication_invalid_refresh_token',
				__('The refresh token is invalid.', 'jwt-auth'),
				array(
					'status' =>  rest_authorization_required_code(),
				)
			);
		}

		$token_uuid = $token->uuid;

		$expires = $this->expires_token;

		$payload = $this->generate_payload($user, $request, $expires, false, null, $token_uuid);

		// Generate JWT token.
		$new_token = $this->jwt('encode', $payload, $this->secret_key);


		// Setup some user meta data we can use for our UI.
		$jwt_data  = $this->get_user_jwt_data($token->data->user->id);



		$user_ip    = self::get_ip();


		$payload['token'] = $new_token;

		$response = array(
			'token'	 => $new_token,
			'data'   => $payload['data'],
			'exp'    => $payload['exp'],
		);


		$expires = $this->expires_refresh_token;

		// Generate the payload. refresh token 
		$payload = $this->generate_payload($user, $request, $expires, true, $response, $payload['uuid']);

		if (is_wp_error($payload)) {
			return $payload;
		}

		$refresh_token =  $this->jwt('encode', $payload, $this->secret_key);


		$response['refresh_token'] = $refresh_token;
		// update log session devices 

		foreach ((array) $jwt_data as $_key => $item) {

			if (isset($item['uuid']) && $item['uuid'] == $token_uuid) {
				$jwt_data[$_key]['token'] = $new_token;
				$jwt_data[$_key]['refresh_token'] = $refresh_token;
				break;
			}
		}

		// error_log('response' . print_r($response, true));
		// foreach ((array) $jwt_data as $_key => $item) {

		// 	if (isset($item['uuid']) && $item['uuid'] == $token_uuid) {
		// 		error_log('jwt_data' . print_r($jwt_data[$_key], true));
		// 		break;
		// 	}
		// }


		update_user_meta($token->data->user->id, 'jwt_data', apply_filters('jwt_auth_save_user_data', $jwt_data));
		// Generate JWT token.
		// Let the user modify the data before send it back.
		return $response;
		// return apply_filters( 'jwt_auth_token_before_dispatch', $payload, $user );
	}


	/**
	 * Check if we should revoke a token.
	 *	 
	 */
	public function revoke_token()
	{
		$token = $this->validate_token(false);

		if (is_wp_error($token)) {
			if ($token->get_error_code() !== 'jwt_auth_no_auth_header') {
				// If there is a error, store it to show it after see rest_pre_dispatch.
				$this->jwt_error = $token;
				return false;
			} else {
				return false;
			}
		}

		$tokens     = get_user_meta($token->data->user->id, 'jwt_data', true) ?: false;
		$token_uuid = $token->uuid;

		if ($tokens && is_array($tokens)) {
			foreach ($tokens as $key => $token_data) {
				if ($token_data['uuid'] === $token_uuid) {
					unset($tokens[$key]);
					update_user_meta($token->data->user->id, 'jwt_data', $tokens);
					return array(
						'code' => 'jwt_auth_revoked_token',
						'data' => array(
							'status' => 200,
						),
					);
				}
			}
		}

		return array(
			'code' => 'jwt_auth_no_token_to_revoke',
			'data' => array(
				'status' =>  rest_authorization_required_code(),
			),
		);
	}


	/**
	 * Endpoint for requesting a password reset link.
	 * This is a slightly modified version of what WP core uses.
	 *
	 * @param object $request The request object that come in from WP Rest API.
	 
	 */
	public function lost_password($request)
	{
		$username   = !empty($request->get_param('email')) ? $request->get_param('email') : $request->get_param('username');
		if (!$username) {
			return new WP_Error(
				'[jwt_auth]_invalid_username',
				__('Wrong , Email..', 'jwt-authentication'),
				array(
					'status' =>  rest_authorization_required_code(),
				)
			);
		} elseif (strpos($username, '@')) {
			$user_data = get_user_by('email', trim($username));
		} else {
			$user_data = get_user_by('login', trim($username));
		}

		global $wpdb, $current_site;

		do_action('lostpassword_post');
		if (!$user_data) {
			return new WP_Error(
				"[jwt_auth]_invalid_username",
				__('Wrong , Email..', 'jwt-authentication'),
				array(
					'status' =>  rest_authorization_required_code(),
				)
			);
		}

		// redefining user_login ensures we return the right case in the email
		$user_login = $user_data->user_login;
		$user_email = $user_data->user_email;

		do_action('retreive_password', $user_login);  // Misspelled and deprecated
		do_action('retrieve_password', $user_login);

		$allow = apply_filters('allow_password_reset', true, $user_data->ID);

		if (!$allow) {
			return new WP_Error(
				"[jwt_auth]_reset_password_not_allowed",
				__('Error: Resetting password is not allowed.', 'jwt-authentication'),
				array(
					'status' =>  rest_authorization_required_code(),
				)
			);
		} elseif (is_wp_error($allow)) {
			return new WP_Error(
				"[jwt_auth]_reset_password_not_allowed",
				__('Error: Resetting password is not allowed.', 'jwt-authentication'),
				array(
					'status' =>  rest_authorization_required_code(),
				)
			);
		}



		$code_pin = $this->get_password_reset_code_pin($user_data);

		$key = get_password_reset_key($user_data);

		$message  = __('Someone requested that the password be reset for the following account:') . "\r\n\r\n";
		$message .= network_home_url('/') . "\r\n\r\n";
		// translators: %s is the users login name.
		$message .= sprintf(__('Username: %s'), $user_login) . "\r\n\r\n";
		$message .= __('If this was a mistake, just ignore this email and nothing will happen.') . "\r\n\r\n";

		$message .= "$code_pin is your account password reset or your or " . "\r\n\r\n";

		$message .= __('To reset your password, visit the following address:') . "\r\n\r\n";
		$message .= '<' . network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login') . ">\r\n";

		if (is_multisite()) {
			$blogname = $GLOBALS['current_site']->site_name;
		} else {
			// The blogname option is escaped with esc_html on the way into the database in sanitize_option
			// we want to reverse this for the plain text arena of emails.
			$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		}
		// translators: %s is the sites name (blogname)
		$title = sprintf(__('[%s] Password Reset'), $blogname);

		$title   = apply_filters('retrieve_password_title', $title);
		$message = apply_filters('retrieve_password_message', $message, $key);

		// $headers = 'From: noreply@atlaslabor.com' . "\r\n";
		//error_log("message rest password: " . $message);
		if ($message && !wp_mail($user_email, $title, $message)) {

			//update_user_meta($user_data->ID, "code_pin", "");
			error_log("Possible reason: your host may have disabled the mail() function...");
			return new WP_Error(
				"[jwt_auth]_reset_config_mail",
				__('fail please try again another time.', 'jwt-authentication'),
				array(
					'status' =>  rest_authorization_required_code(),
				)
			);
		}

		return array(
			'code'    => 'jwt_auth_password_reset',
			'message' => __('Reset Password  Successful check your email.', 'jwt-authentication'),
			'data'    => array(
				'status' => 200,
			),
		);
	}


	/**
	 * 
	 */
	public function password_reset($request)
	{
		global $wpdb;
		$username   = !empty($request->get_param('email')) ? $request->get_param('email') : $request->get_param('username');
		$code_pin   =  $request->get_param('code_pin');
		$password   =  $request->get_param('password');
		$confirm_password   =  $request->get_param('confirm_password');
		if (!$username || !$code_pin || !$password || !$confirm_password) {
			return new WP_Error(
				'[jwt_auth]_invalid_params',
				__('invalid params.', 'jwt-authentication'),
				array(
					'status' =>  rest_authorization_required_code(),
				)
			);
		}

		$user = $this->check_password_reset_code($code_pin, $username);
		if (is_wp_error($user)) {
			return $user;
		}

		if (empty($password) ||  $password !== $confirm_password) {
			return new WP_Error(
				'[jwt_auth]_password_reset_mismatch',
				__('The passwords do not match.', 'jwt-authentication'),
				array(
					'status' =>  rest_authorization_required_code(),
				)
			);
		}

		wp_set_password($password, $user->ID);

		$wpdb->update($wpdb->users, array('user_activation_key' => ''), array('ID' => $user->ID));

		update_user_meta($user->ID, "code_pin", "");

		return array(
			'code'    => 'jwt_auth_password_reset',
			'message' => __('Your password has been reset.', 'jwt-authentication'),
			'data'    => array(
				'status' => 200,
			),
		);
	}


	/**
	 * 
	 */
	public function password_validate_code_pin($request)
	{
		$username   = !empty($request->get_param('email')) ? $request->get_param('email') : $request->get_param('username');
		$code_pin   =  $request->get_param('code_pin');
		if (!$username || !$code_pin) {
			return new WP_Error(
				'[jwt_auth]_invalid_code_pin',
				__('Wrong , code.', 'jwt-authentication'),
				array(
					'status' =>  rest_authorization_required_code(),
				)
			);
		}

		$check_password_rest_code = $this->check_password_reset_code($code_pin, $username);
		if (is_wp_error($check_password_rest_code)) {
			return $check_password_rest_code;
		}

		return array(
			'code'    => 'jwt_auth_password_reset_code',
			'message' => __('Validete Code Pin  Successful.', 'jwt-authentication'),
			'data'    => array(
				'status' => 200,
			),
		);
	}

	/**
	 * 
	 */
	private function check_password_reset_code($code, $login)
	{
		$error =  new WP_Error('invalid_code', __('Invalid code.'));
		$code = preg_replace('/[^0-9]/i', '', $code);

		if (empty($code) || !is_numeric($code)) {
			return $error;
		}
		if (empty($login) || !is_string($login)) {
			return $error;
		}

		if (strpos($login, '@')) {
			$user = get_user_by('email', trim($login));
		} else {
			$user = get_user_by('login', trim($login));
		}


		if (!$user) {
			return $error;
		}

		$expiration_duration = apply_filters('jwt_code_reset_expiration', DAY_IN_SECONDS);

		$code_pin = get_user_meta($user->ID, "code_pin", true);

		if (false !== strpos($user->code_pin, ':')) {
			list($pass_request_time, $pass_code) = explode(':', $user->code_pin, 2);
			$expiration_time                      = $pass_request_time + $expiration_duration;
		} else {
			$pass_code        = $user->code_pin;
			$expiration_time = false;
		}

		if (!$pass_code) {
			return new WP_Error('invalid_code_pass_code', __('Invalid key.'));
		}

		if (!$expiration_time) {
			return new WP_Error('expired_key', __('Invalid key.'));
		}

		$code_is_correct = hash_equals($pass_code, $code);

		if ($code_is_correct &&  $expiration_time && time() < $expiration_time) {
			return $user;
		}

		return $error;
	}


	/**
	 * 
	 */
	private function get_password_reset_code_pin(WP_User $user)
	{
		$code_pin = substr(str_shuffle("0123456789"), 0, 6);
		$hashed = time() . ':' . $code_pin;
		update_user_meta($user->ID, "code_pin", $hashed);
		return $code_pin;
	}

	/**
	 * Authenticate and determine if the REST request has authentication errors.
	 *
	 * @filter rest_authentication_errors
	 *
	 *
	 * @param mixed $result Result of any other authentication errors.
	 *
	 * @return bool|null|WP_Error
	 */
	public function authenticate($result)
	{

		// Another authentication method was used.
		if (!is_null($result)) {
			return $result;
		}

		/**
		 * Check for REST request.
		 *
		 * @param bool $rest_request Whether or not this is a REST request.
		 */
		$rest_request = apply_filters('rest_authentication_is_rest_request', (defined('REST_REQUEST') && REST_REQUEST));

		// This is not the authentication you're looking for.
		if (!$rest_request) {
			return $result;
		}

		// Authentication is not required.
		if (!$this->require_token()) {

			return $result;
		}

		// Validate the bearer token.
		$token = $this->validate_token(false);
		//error_log("token: " . print_r($token, true));
		if (is_wp_error($token)) {
			return $token;
		}

		// If it's a wp_user based token, set the current user.
		if ('wp_user' === $token->data->user->type) {
			wp_set_current_user($token->data->user->id);

			$this->update_log_jwt_data(get_userdata($token->data->user->id), $token->uuid);
		}

		// Authentication succeeded.
		return true;
	}


	/**
	 * Determine if the request needs to be JWT authenticated.
	 *
	 *
	 * @return bool
	 */
	public function require_token()
	{
		$require_token  = true;
		$request_uri    = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field($_SERVER['REQUEST_URI']) : false;
		$request_method = isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field($_SERVER['REQUEST_METHOD']) : false;

		// User is already authenticated.
		$user = wp_get_current_user();
		if (isset($user->ID) && 0 !== $user->ID) {
			$require_token = false;
		}

		// Only check REST API requests.
		if (!strpos($request_uri, rest_get_url_prefix()) && !strpos($request_uri, '?rest_route=')) {
			$require_token = false;
		}

		/**
		 * GET requests do not typically require authentication, but if the
		 * Authorization header is provided, we will use it. WHat's happening
		 * here is that `WP_REST_Token::get_auth_header` returns the bearer
		 * token or a `WP_Error`. So if we have an error then we can safely skip
		 * the GET request.
		 */
		if (('GET' === $request_method || 'OPTIONS' === $request_method) && is_wp_error($this->get_auth_header())) {
			$require_token = false;
		}

		$paths_not_require_token = apply_filters("rest_paths_not_require_token", [
			sprintf('/%s/%s', $this->namespace, $this->rest_base),
			sprintf('/%s/%s', $this->namespace, $this->rest_base . '/refresh'),
			// sprintf('/%s/%s', $this->namespace, $this->rest_base . '/validate'),
			sprintf('/%s/%s', $this->namespace, $this->rest_base . '/psssword/lost'),
			sprintf('/%s/%s', $this->namespace, $this->rest_base . '/psssword/validate'),
			sprintf('/%s/%s', $this->namespace, $this->rest_base . '/psssword/reset'),
		]);


		foreach ($paths_not_require_token as $key => $value) {
			add_filter("rest_routes_not_require_jwt", function ($args) use ($value) {
				$args[] = $value;
				return $args;
			}, 10, 1);
			//error_log("value: " .  $paths_not_require_token[$key]);
			if (('POST' === $request_method || 'OPTIONS' === $request_method)  &&  strpos($request_uri, $value)) {
				$require_token = false;
				//error_log("request_uriee: " .  $value);
				break;
			}
		}
		//error_log("request_uri: " . $request_uri);

		//error_log("require_token: " . $require_token);
		/**
		 * Filters whether a REST endpoint requires JWT authentication.
		 *
		 * @param bool   $require_token Whether a token is required.
		 * @param string $request_uri The URI which was given by the server.
		 * @param string $request_method Which request method was used to access the server.
		 */

		return apply_filters('rest_authentication_require_token', $require_token, $request_uri, $request_method);
	}
	/**
	 * Determine if a valid Bearer token has been provided and return when it expires.
	 *
	 * @return array Return information about whether the token has expired or not.
	 */
	public function validate()
	{

		$response = array(
			'code'    => 'rest_authentication_invalid_bearer_token',
			'message' => __('Invalid bearer token.', 'jwt-auth'),
			'data'    => array(
				'status' =>  rest_authorization_required_code(),
			),
		);

		// Get HTTP Authorization Header.
		$header = $this->get_auth_header();
		if (is_wp_error($header)) {
			return $response;
		}

		// Get the Bearer token from the header.
		$token = $this->get_token($header);
		if (is_wp_error($token)) {
			return $response;
		}

		// Decode the token.
		$jwt = $this->decode_token($token);
		if (is_wp_error($jwt)) {
			return $response;
		}

		// Determine if the token issuer is valid.
		$issuer_valid = $this->validate_issuer($jwt->iss);
		if (is_wp_error($issuer_valid)) {
			return $response;
		}

		// Determine if the token user is valid.
		$user_valid = $this->validate_user($jwt);
		if (is_wp_error($user_valid)) {
			return $response;
		}

		// Determine if the token has expired.
		$expiration_valid = $this->validate_expiration($jwt);
		if (is_wp_error($expiration_valid)) {
			$response['code']    = 'rest_authentication_expired_bearer_token';
			$response['message'] = __('Expired bearer token.', 'jwt-auth');
			return $response;
		}

		$response = array(
			'code'    => 'rest_authentication_valid_access_token',
			'message' => __('Valid access token.', 'jwt-auth'),
			'data'    => array(
				'status' => 200,
				'exp'    => $jwt->exp - time(),
			),
		);

		if (isset($jwt->data->user->token_type) && 'refresh' === $jwt->data->user->token_type) {
			$response['code']    = 'rest_authentication_valid_refresh_token';
			$response['message'] = __('Valid refresh token.', 'jwt-auth');
		}

		return $response;
	}



	/**
	 *
	 */
	private function update_log_jwt_data($user, $uuid)
	{
		$jwt_data = get_user_meta($user->ID, 'jwt_data', true) ?: false;
		if (false === $jwt_data) {
			return new WP_Error(
				'jwt_auth_token_revoked',
				__('Token has been revoked.', 'jwt-authentication'),
				array(
					'status' =>  rest_authorization_required_code(),
				)
			);
		}
		// Loop through and check wether we have the current token uuid in the users meta.
		foreach ($jwt_data as $key => $token_data) {
			if (isset($token_data['uuid']) &&  $token_data['uuid'] === $uuid) {
				$jwt_data[$key]['last_used'] = time();
				$jwt_data[$key]['ua']        = $_SERVER['HTTP_USER_AGENT'];
				$jwt_data[$key]['ip']        = self::get_ip();
				break;
			}
		}

		update_user_meta($user->ID, 'jwt_data', apply_filters('jwt_auth_save_user_data', $jwt_data));

		return true;
	}
	/**
	 * Determine if a valid Bearer token has been provided.
	 *
	 * @return object|WP_Error Return the JSON Web Token object, or WP_Error on failure.
	 */
	public function validate_token($output = true, $is_refresh = false)
	{

		// Get HTTP Authorization Header.
		$header = $this->get_auth_header();
		if (is_wp_error($header)) {
			return $header;
		}

		// Get the Bearer token from the header.
		$token = $this->get_token($header);
		if (is_wp_error($token)) {
			return $token;
		}

		// Get the Bearer token from the header.
		$token_is_exists = $this->validate_exists($token, $is_refresh);
		if (is_wp_error($token_is_exists)) {
			return $token_is_exists;
		}

		// Decode the token.
		$jwt = $this->decode_token($token);
		if (is_wp_error($jwt)) {
			return $jwt;
		}

		// Determine if the token issuer is valid.
		$issuer_valid = $this->validate_issuer($jwt->iss);
		if (is_wp_error($issuer_valid)) {
			return $issuer_valid;
		}

		// Determine if the token user is valid.
		$user_valid = $this->validate_user($jwt);
		if (is_wp_error($user_valid)) {
			return $user_valid;
		}

		// Determine if the token has expired.
		$expiration_valid = $this->validate_expiration($jwt);
		if (is_wp_error($expiration_valid)) {
			return $expiration_valid;
		}

		/**
		 * Filter response containing the JWT token.
		 *
		 * @param object $jwt The JSON Web Token or error.
		 *
		 * @return object|WP_Error
		 */
		$jwt = apply_filters('rest_authentication_validate_token', $jwt);
		if (!$output) {
			return $jwt;
		}

		return array(
			'code' => 'jwt_auth_valid_token',
			'data' => array(
				"user" => $jwt->data->user,
				'status' => 200,
			),
		);
	}

	/**
	 * Get the HTTP Authorization Header.
	 *
	 *
	 * @return mixed
	 */
	public function get_auth_header()
	{

		// Get HTTP Authorization Header.
		$header = isset($_SERVER['HTTP_AUTHORIZATION']) ? sanitize_text_field($_SERVER['HTTP_AUTHORIZATION']) : false;

		// Check for alternative header.
		if (!$header && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
			$header = sanitize_text_field($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
		}

		// The HTTP Authorization Header is missing, return an error.
		if (!$header) {
			return new WP_Error(
				'rest_authentication_no_header',
				__('Authorization header was not found.', 'jwt-auth'),
				array(
					'status' =>  rest_authorization_required_code(),
				)
			);
		}

		return $header;
	}

	/**
	 * Get the Bearer token from the header.
	 *
	 *
	 * @param string $header The Authorization header.
	 *
	 * @return string|WP_Error
	 */
	public function get_token($header)
	{

		list($token) = sscanf($header, 'Bearer %s');

		if (!$token) {
			return new WP_Error(
				'rest_authentication_no_token',
				__('Authentication token is missing.', 'jwt-auth'),
				array(
					'status' =>  rest_authorization_required_code(),
				)
			);
		}

		return $token;
	}

	/**
	 * Determine if the token issuer is valid.
	 *
	 *
	 * @param string $issuer Issuer of the token.
	 *
	 * @return bool|WP_Error
	 */
	public function validate_issuer($issuer)
	{

		if (get_bloginfo('url') !== $issuer) {
			return new WP_Error(
				'rest_authentication_invalid_token_issuer',
				__('Token issuer is invalid.', 'jwt-auth'),
				array(
					'status' =>  rest_authorization_required_code(),
				)
			);
		}

		return true;
	}

	/**
	 * Determine if the token user is valid.
	 *
	 *
	 * @param object $token The token.
	 *
	 * @return bool|WP_Error
	 */
	public function validate_user($token)
	{

		if (!isset($token->data->user->id)) {
			return new WP_Error(
				'rest_authentication_missing_token_user_id',
				__('Token user must have an ID.', 'jwt-auth'),
				array(
					'status' =>  rest_authorization_required_code(),
				)
			);
		}

		if ('wp_user' === $token->data->user->type) {

			$userdata = get_userdata($token->data->user->id);

			if (false === $userdata) {
				return new WP_Error(
					'rest_authentication_invalid_token_wp_user',
					__('Token user is invalid.', 'jwt-auth'),
					array(
						'status' =>  rest_authorization_required_code(),
					)
				);
			}

			if ($token->data->user->user_login !== $userdata->user_login) {
				return new WP_Error(
					'rest_authentication_invalid_token_user_login',
					__('Token user_login is invalid.', 'jwt-auth'),
					array(
						'status' =>  rest_authorization_required_code(),
					)
				);
			}

			if ($token->data->user->user_email !== $userdata->user_email) {
				return new WP_Error(
					'rest_authentication_invalid_token_user_email',
					__('Token user_email is invalid.', 'jwt-auth'),
					array(
						'status' =>  rest_authorization_required_code(),
					)
				);
			}
		}

		return true;
	}

	/**
	 * Determine if the token has expired.
	 *
	 *
	 * @param object $token The token.
	 *
	 * @return bool|WP_Error
	 */
	public function validate_expiration($token)
	{

		if (!isset($token->exp)) {
			return new WP_Error(
				'rest_authentication_missing_token_expiration',
				__('Token must have an expiration.', 'jwt-auth'),
				array(
					'status' =>  rest_authorization_required_code(),
				)
			);
		}

		if (time() > $token->exp) {
			return new WP_Error(
				'rest_authentication_token_expired',
				__('Token has expired.', 'jwt-auth'),
				array(
					'status' =>  rest_authorization_required_code(),
				)
			);
		}

		return true;
	}

	/**
	 *  validate_exists
	 */
	public function validate_exists($token, $is_refresh = false)
	{
		$error = new WP_Error('rest_authentication_invalid_token', __('Token  is invalid.', 'jwt-auth'), array('status' =>  rest_authorization_required_code()));

		$jwt = $this->decode_token($token);
		if (is_wp_error($jwt)) {
			return $jwt;
		}

		$jwt_data  = $this->get_user_jwt_data($jwt->data->user->id);

		foreach ($jwt_data as $key => $token_data) {
			if (isset($token_data['uuid']) &&  $token_data['uuid'] === $jwt->uuid) {
				// error_log("token: " . print_r($token, true));
				// error_log("token->db: " . print_r($token_data['token'], true));
				// error_log("refresh_token->db: " . print_r($token_data['refresh_token'], true));
				// //error_log("jwt" . print_r($jwt, true));
				//error_log(print_r($token_data, true));
				if ($is_refresh && isset($token_data['refresh_token']) &&  $token_data['refresh_token'] == $token) {
					return true;
				} else if (isset($token_data['token']) &&  $token_data['token'] == $token) {

					return true;
				} else {
					return $error;
				}
			}
		}

		return $error;
	}
	/**
	 * Filter to hook the rest_pre_dispatch, if the is an error in the request
	 * send it, if there is no error just continue with the current request.
	 *
	 * @param $request
	 
	 */
	public function rest_pre_dispatch($request)
	{
		if (is_wp_error($this->jwt_error)) {
			return $this->jwt_error;
		}
		return $request;
	}


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
	 * Performs a static method call on the JWT class for testability.
	 *
	 * @codeCoverageIgnore
	 *
	 * @param mixed $args Method arguments. The method name is first.
	 *
	 * @return mixed
	 */
	public function jwt($args)
	{
		$args   = func_get_args();
		$class  = get_class(new JWT());
		$method = $args[0];
		$params = array_slice($args, 1);
		return call_user_func_array($class . '::' . $method, $params);
	}


	public function get_item_schema()
	{

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'jwt',
			'type'       => 'object',
			'properties' => array(
				'token'          => array(
					'description' => __('Unique identifier for the resource.'),
					'type'        => 'string',
					'context'     => array('embed', 'view', 'edit'),
					'readonly'    => true,
				),
				'data'        => array(
					'description' => __('Menu Item Name'),
					'type'        => 'object',
					'context'     => array('embed', 'view', 'edit'),
					'properties'    => array(
						'id'          => array(
							'description' => __('Unique identifier for the resource.'),
							'type'        => 'integer',
							'context'     => array('embed', 'view', 'edit'),
							'readonly'    => true,
						),
						'type'          => array(
							'description' => __('Unique identifier for the resource.'),
							'type'        => 'string',
							'context'     => array('embed', 'view', 'edit'),
							'readonly'    => true,
						),
						'user_login'          => array(
							'description' => __('Unique identifier for the resource.'),
							'type'        => 'string',
							'context'     => array('embed', 'view', 'edit'),
							'readonly'    => true,
						),
						'user_email'          => array(
							'description' => __('Unique identifier for the resource.'),
							'type'        => 'string',
							'context'     => array('embed', 'view', 'edit'),
							'readonly'    => true,
						),
						'user_nicename'          => array(
							'description' => __('Unique identifier for the resource.'),
							'type'        => 'string',
							'context'     => array('embed', 'view', 'edit'),
							'readonly'    => true,
						),
						'user_display_name'          => array(
							'description' => __('Unique identifier for the resource.'),
							'type'        => 'string',
							'context'     => array('embed', 'view', 'edit'),
							'readonly'    => true,
						),
						'roles'          => array(
							'description' => __('Unique identifier for the resource.'),
							'type'        => 'array',
							'context'     => array('embed', 'view', 'edit'),
							'items'       => array(
								'type' => 'string',
							),
							'readonly'    => true,
							// 'default'     => array(),
						),
					),
				),
				'exp'          => array(
					'description' => __('Unique identifier for the resource.'),
					'type'        => 'integer',
					'context'     => array('embed', 'view', 'edit'),
					'readonly'    => true,
				),

				'refresh_token'          => array(
					'description' => __('Unique identifier for the resource.'),
					'type'        => 'string',
					'context'     => array('embed', 'view', 'edit'),
					'readonly'    => true,
				),

			)
		);


		return $this->add_additional_fields_schema($schema);
	}
}

new AuthenticationController();
