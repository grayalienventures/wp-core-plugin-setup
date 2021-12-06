<?php

/**
 * 
 */



if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use Smuvers\Smuvers;

?>
<div class="wrap">

    <h1>Smuvers Config Credentials</h1>
    <form method="post" action="options.php">
        <table class="form-table">

            <tr valign="top">
                <th scope="row"><?php _e('Client Key', Smuvers::BASE_SLUG); ?><br /></th>
                <td>
                    <input size="50" type="text" name="<?php echo Smuvers::KEY_OPTION; ?>[client_id]" placeholder="<?php esc_html('Client Key', Smuvers::BASE_SLUG); ?>" value="<?php echo htmlspecialchars($options['client_id']); ?>" class="regular-text" />

                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Client Secret', Smuvers::BASE_SLUG); ?><br /></th>
                <td>
                    <input size="50" type="text" name="<?php echo Smuvers::KEY_OPTION; ?>[client_secret]" placeholder="<?php esc_html('Client Secret', Smuvers::BASE_SLUG); ?>" value="<?php echo htmlspecialchars($options['client_secret']); ?>" class="regular-text" />

                </td>
            </tr>

        </table>
        <?php settings_fields(Smuvers::KEY_OPTIONS); ?>
        <input type="submit" class="button-primary" value="<?php _e('Save Changes', Smuvers::BASE_SLUG) ?>" />
    </form>
</div>