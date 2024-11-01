<?php

/**
 * WP Super Stage - WordPress Staging Plugin
 *
 * @link              https://revmakx.com
 * @since             1.0.0
 * @package           Super_Stage_WP
 *
 * @wordpress-plugin
 * Plugin Name:       WP Super Stage
 * Plugin URI:        https://wpsuperstage.com
 * Description:       Instantly stage your WordPress Site.
 * Version:           1.0.1
 * Author:            Revmakx
 * Author URI:        https://revmakx.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpss
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

function wpss_special_log($key = null, $val = null){
    file_put_contents(WP_CONTENT_DIR . '/wpss-logs.txt', var_export($key, true) . ' ------ ' . var_export($val, true) . '--------' . "\n", FILE_APPEND);
}

register_shutdown_function('wpss_shutdown_capture');

function wpss_shutdown_capture(){
    $last_err = error_get_last();

    if(!empty($last_err)){
        wpss_special_log('------last_error-------', $last_err);
    }
}

include_once('wpss-constants.php');
$constants = new WPSS_Constants();
$constants->init_live_plugin();

include_once(WPSS_PLUGIN_DIR . 'wpss-common-functions.php');

include_once(WPSS_PLUGIN_DIR . 'wpss-init.php');

include_once(WPSS_PLUGIN_DIR . '/wpss-factory.php');
include_once(WPSS_PLUGIN_DIR . '/wpss-base-factory.php');
include_once(WPSS_PLUGIN_DIR . '/wpss-app-functions.php');
include_once(WPSS_PLUGIN_DIR . '/wpss-config.php');
include_once(WPSS_PLUGIN_DIR . 'wpss-base-config.php');
// include_once(WPSS_PLUGIN_DIR . '/wpss-exclude-option.php');

include_once(WPSS_PLUGIN_DIR . '/class-processed-base.php');
include_once(WPSS_PLUGIN_DIR . '/class-processed-files.php');
include_once(WPSS_PLUGIN_DIR . '/class-processed-iterator.php');
include_once(WPSS_PLUGIN_DIR . '/class-file-iterator.php');
include_once(WPSS_PLUGIN_DIR . '/class-replace-db-links.php');
include_once(WPSS_PLUGIN_DIR . '/class-database-backup.php');
include_once(WPSS_PLUGIN_DIR . '/class-logger.php');
include_once(WPSS_PLUGIN_DIR . '/class-filelist.php');

include_once(WPSS_PLUGIN_DIR . 'ExcludeOption/init.php');
include_once(WPSS_PLUGIN_DIR . 'ExcludeOption/Hooks.php');
include_once(WPSS_PLUGIN_DIR . 'ExcludeOption/HooksHandler.php');
include_once(WPSS_PLUGIN_DIR . 'ExcludeOption/Config.php');
include_once(WPSS_PLUGIN_DIR . 'ExcludeOption/ExcludeOption.php');

include_once(WPSS_PLUGIN_DIR . '/Staging/init.php');
// include_once(WPSS_PLUGIN_DIR . '/Staging/Config.php');
include_once(WPSS_PLUGIN_DIR . '/Staging/Hooks.php');
include_once(WPSS_PLUGIN_DIR . '/Staging/HooksHandler.php');
include_once(WPSS_PLUGIN_DIR . '/Staging/class-stage-common.php');
include_once(WPSS_PLUGIN_DIR . '/Staging/class-update-in-staging.php');

new WPSS_Init();
