<?php

namespace WP_Core_Plugin;


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
