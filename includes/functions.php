<?php

namespace WPCorePlugin;


add_filter('rest_prepare_user', __NAMESPACE__ . "\\rest_prepare_user", 10, 3);

/**
 * handle request when update user 
 */
add_filter("rest_insert_user", __NAMESPACE__ . "\\filter_edit_rest_user", 10, 3);


function rest_prepare_user($response, $user, $request)
{

    // add email to response when request the same user 
    if ($request->get_route() == "/wp/v2/users/me") {

        $response->data['roles'] = $user->roles;
        $response->data['capabilities'] = (object) $user->allcaps;
        $response->data['extra_capabilities'] = (object) $user->caps;
    }
    $address = get_user_meta($user->ID, "address", true);
    $response->data['address'] = $address;
    $response->data['email'] = $user->user_email;
    $response->data['address2'] = get_user_meta($user->ID, 'address2', true);
    $response->data['firstName'] = get_user_meta($user->ID, 'first_name', true);
    $response->data['lastName'] = get_user_meta($user->ID, 'last_name', true);
    $response->data['phone'] = get_user_meta($user->ID, 'phone', true);
    $response->data['profilePic'] = get_user_meta($user->ID, 'profile_picture', true);
    $addresses =  get_user_meta($user->ID, "addresses", true);
    $response->data['addresses'] = is_array($addresses) ? $addresses : [];

    return $response;
}



function filter_edit_rest_user($user, $request, $is_create)
{
    // check if is not create  
    if (!$is_create) {
        $default_fields = array(
            'address'          => 'address',
            'address2'         => 'address2',
            'firstName'        => 'first_name',
            'lastName'         => 'last_name',
            'phone'            => 'phone',
            'profilePic'       => 'profile_picture',
        );
        foreach ($default_fields as $key => $key_meta) {
            if (isset($request[$key])) {
                $value = sanitize_text_field($request[$key]);

                update_user_meta($user->ID, $key_meta, $value);
            }
        }
    }
}


function class_basename($class)
{
	$class = is_object($class) ? get_class($class) : $class;

	return basename(str_replace('\\', '/', $class));
}


function debug_log($title = "", $data = "", $level = 'i', $show_path_call = false)
{
	if (is_array($title) || is_object($title)) {
		$title = print_r($title, true);
	}

	switch (strtolower($level)) {
		case 'e':
		case 'error':
			$level = 'ERROR';
			break;
		case 'i':
		case 'info':
			$level = 'INFO';
			break;
		case 'd':
		case 'debug':
			$level = 'DEBUG';
			break;
		default:
			$level = 'INFO';
	}

	$file_called_from = "";
	if ($show_path_call) {
		$trace = debug_backtrace();
		$file_called_from = basename($trace[0]['file']);
		// $file_called_from= print_r($trace[0], true);
	}
	$message = " [" . $level . "] "  . $title . " " . print_r($data, true) . " " . $file_called_from;
	error_log($message);
}



/**
 * 
 * @param string $key key in params.
 * @param array  $params  get params from request.
 * @param string|array|object $default Optional.
 * @return string|array|object|$default
 */
function rest_get_param($key, $params, $default = "")
{
	if (is_array($params) && isset($params[$key])) {
		return $params[$key];
	}
	return $default;
}
