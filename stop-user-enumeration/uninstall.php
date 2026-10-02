<?php
/**
 * Fired when the plugin is deleted.
 *
 * WordPress runs this file in preference to any registered uninstall hook,
 * so no register_uninstall_hook() call is needed (that call writes to the
 * database on every request when made at load time).
 */

// Exit if not called by WordPress during uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/vendor/autoload.php';

\Stop_User_Enumeration\Includes\Uninstall::uninstall();
