<?php

/**
 *
 * @package WP_Core_Plugin
 *
 */

namespace WP_Core_Plugin\Authentication;

use WP_Core_Plugin\JWT\JWT as JWT;
use Exception;


use WP_Error;



function esc_quotes($string)
{
    return str_replace(array("%22", '"'), array("", ""), $string);
}


function validate_user($token)
{

    if (!isset($token->data->user->id)) {
        return new WP_Error(
            'rest_authentication_missing_token_user_id',
            __('Token user must have an ID.', 'jwt-auth'),
            array(
                'status' => 403,
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
                    'status' => 403,
                )
            );
        }

        if ($token->data->user->user_login !== $userdata->user_login) {
            return new WP_Error(
                'rest_authentication_invalid_token_user_login',
                __('Token user_login is invalid.', 'jwt-auth'),
                array(
                    'status' => 403,
                )
            );
        }

        if ($token->data->user->user_email !== $userdata->user_email) {
            return new WP_Error(
                'rest_authentication_invalid_token_user_email',
                __('Token user_email is invalid.', 'jwt-auth'),
                array(
                    'status' => 403,
                )
            );
        }
    }

    return true;
}



function invalid_auth_error()
{
    return new WP_Error(
        "smuvers_rest_clients_auth",
        'ERROR Authenticate',
        array(
            'status' =>  rest_authorization_required_code(),
        )
    );
}

/**
 * generation_token
 * @param array $args   // add more args
 * @param integer $expire_linght 
 * @return string token
 */
function generation_token($args = array(), $expires = null)
{

    $issuedAt = time();

    $notBefore = apply_filters('easyeatery_core_jwt_not_before', $issuedAt, $issuedAt);
    if (!empty($expires)) {
        $_expire = (int)$expires;
    } else {
        $_expire = apply_filters('easyeatery_core_jwt_expire', $issuedAt + (DAY_IN_SECONDS), $issuedAt);
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
    // $token= apply_filters('easyeatery_core_jwt_token_before_sign', $token, $args);
    $token = JWT::encode($token, SECURE_AUTH_KEY);

    return $token;
}



function decode_token($token)
{

    try {
        return JWT::decode(esc_quotes($token), SECURE_AUTH_KEY, array('HS256'));
    } catch (Exception $e) {
        // Return exceptions as WP_Errors.
        return new WP_Error(
            'rest_authentication_token_error',
            __('Invalid bearer token.', 'smuversp'),
            array(
                'status' => 403,
            )
        );
    }
}



function get_authorization_header()
{

    // Get HTTP Authorization Header.
    $header = isset($_SERVER['HTTP_AUTHORIZATION']) ? sanitize_text_field($_SERVER['HTTP_AUTHORIZATION']) : false;

    // Check for alternative header.
    if (!$header && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $header = sanitize_text_field($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    }


    if (!$header) {
        return null;
    }

    return $header;
}




/**
 * Extracts the token from the authorization header or the current request.
 *
 * @return string|null Token on success, null on failure.
 */
function get_provided_token()
{
    $header = get_authorization_header();
    if ($header) {
        return get_token_from_bearer_header($header);
    }

    $token = get_token_from_request();
    if ($token) {
        return $token;
    }

    return null;
}



function get_token_from_bearer_header($header)
{

    list($token) = sscanf($header, 'Bearer %s');

    if (!$token) {
        return null;
    }

    return $token;
}

/**
 * Extracts the token from the current request.
 *
 * @return string|null Token on succes, null on failure.
 */
function get_token_from_request()
{
    if (empty($_GET['access_token'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return null;
    }

    $token = $_GET['access_token']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput
    if (is_string($token)) {
        return $token;
    }

    // Got a token, but it's not valid.
    global $smuversp_ouath_error;
    $smuversp_ouath_error = create_invalid_token_error($token);
    return null;
}




/**
 * Creates an error object for the given invalid token.
 *
 * @param mixed $token Invalid token.
 *
 * @return WP_Error
 */
function create_invalid_token_error($token)
{
    return new WP_Error(
        'smuverp.authentication.attempt_authentication.invalid_token',
        __('Supplied token is invalid.', 'smuverp'),
        [
            'status' => \WP_Http::FORBIDDEN,
            'token'  => $token,
        ]
    );
}
