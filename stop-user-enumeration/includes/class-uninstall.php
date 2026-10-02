<?php


/**
 * Fired during plugin uninstall.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 *
 */

namespace Stop_User_Enumeration\Includes;

class Uninstall {

	/**
	 * Uninstall specific code
	 */
	public static function uninstall() {

		delete_option( 'stop-user-enumeration' );
		\Fullworks_Free_Plugin_Lib\Main::plugin_uninstall( 'SUE' );

	}

}
