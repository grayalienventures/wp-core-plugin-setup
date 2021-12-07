<?php

/**
 * 
 */



if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use WP_Core_Plugin\WP_Core_Plugin;

?>
<div class="wrap">

    <h1>Move Sheet Settings</h1>
    <form method="post" action="options.php">
        <table class="form-table">

            <tr valign="top">
                <th scope="row"><?php _e('Server socket uri', $base_slug); ?><br /></th>
                <td>
                    <input size="50" type="text" placeholder="http://localhost:3000" name="<?php echo WP_Core_Plugin::KEY_OPTION; ?>[server_socket]" placeholder="<?php _e('Server Socket URL', $base_slug); ?>" value="<?php echo htmlspecialchars($options['server_socket_uri']); ?>" class="regular-text" />
                    <br />
                    <small><?php _e('', $base_slug); ?></small>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row"><?php _e('Environment is production'); ?><br /></th>
                <?php


                $checked = '';

                $is_production = isset($options['is_production']) ? true : false;

                if (isset($options['is_production']) && $options['is_production'] == true) $checked = 'checked="checked"';
                ?>
                <td>

                    <input size="50" type="checkbox" name="<?php echo WP_Core_Plugin::KEY_OPTION; ?>[is_production]" onchange="chState(this)" placeholder="<?php _e('Is Production'); ?>" value="<?= $is_production ?>" <?= $checked ?> class="regular-text" />
                    <br />
                    <small><?php _e(''); ?></small>
                </td>
            </tr>


        </table>
        <?php settings_fields(WP_Core_Plugin::KEY_OPTIONS); ?>
        <input type="submit" class="button-primary" value="<?php _e('Save Changes', $base_slug) ?>" />
    </form>
</div>