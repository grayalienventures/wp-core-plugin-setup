<?php

use WPCorePlugin\WPCorePlugin;
?>

<input type='checkbox' name='jwt_authentication_settings[enable_cors]' <?php checked($enable_cors, 1); ?> value='1' <?php echo ($is_global ? 'disabled' : ''); ?> />
<?php
if ($is_global) {
	echo '<br /><small>' . esc_html__('Defined in wp-config.php', WPCorePlugin::BASE_SLUG) . '</small>';
}
?>