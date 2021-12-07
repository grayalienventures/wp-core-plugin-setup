<?php

/**
 * 
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}



use WP_Core_Plugin\WP_Core_Plugin;


// --------------
function log_viewer()
{

    echo '<div class="wrap">';
    echo '<h1>Log viewer</h1>';

    $logFile = ini_get('error_log');
    $logFile = str_replace('\\', DIRECTORY_SEPARATOR, $logFile);
    $logFile = str_replace('/', DIRECTORY_SEPARATOR, $logFile);

    if (isset($_POST['command']) && $_POST['command'] == 'CLEAR') {

        file_put_contents($logFile, '');

        $current_user = wp_get_current_user();
        error_log('Log is cleared (' . $current_user->user_login . ' - ' . $current_user->user_email . ')');

        echo '<div class="notice notice-success is-dismissible">';
        echo '<p>' . __('Log cleared.',  WP_Core_Plugin::PLUGIN_NAME) . '</p>';
        echo '</div>';
    }

    if (!empty($logFile) && filesize($logFile) > 0) {

        echo '<p>' . __('Viewing file:',  WP_Core_Plugin::PLUGIN_NAME) . ' ' . $logFile . '. <a href="">' . __('Click to update', WP_Core_Plugin::PLUGIN_NAME) . '</a>.</p>';

        echo '<pre class="log">';
        $myfile = fopen($logFile, 'r') or die(__('Unable to open file!',  WP_Core_Plugin::PLUGIN_NAME));
        echo fread($myfile, filesize($logFile));
        fclose($myfile);
        echo '</pre>';

        echo '<form method="post" action="" novalidate="novalidate" onsubmit="return confirm(\'' . __('You are about to erase the file.',  WP_Core_Plugin::PLUGIN_NAME) . '\\n\\n' . __('Are you sure?', WP_Core_Plugin::PLUGIN_NAME) . '\');">';
        echo '<input type="hidden" name="command" id="command" value="CLEAR"></input>';
        echo '<p class="submit">';
        echo '<input type="submit" name="submit" id="submit" class="button button-secondary" value="' . __('Clear log file',  WP_Core_Plugin::PLUGIN_NAME) . '">';
        echo '</p>';
        echo '</form>';
    } else {

        echo '<div class="notice notice-error">';
        echo '<p>' . __('Viewer error: log file name is empty.',  WP_Core_Plugin::PLUGIN_NAME) . '</p>';
        echo '</div>';
    }

    echo '</div>';
}

log_viewer();
