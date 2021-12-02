<?php

/**
 * Fired during plugin activation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    PluginName
 * @subpackage PluginName/includes
 */

/**
 * Fired during plugin activation.
 */

namespace Stop_User_Enumeration\Includes;

use Stop_User_Enumeration\Admin\Admin_Settings;

class Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		// options set up
		if ( ! get_option( 'stop-user-enumeration' ) ) {
			update_option( 'stop-user-enumeration', Admin_Settings::option_defaults( 'stop-user-enumeration' ) );
		}

	}

}
