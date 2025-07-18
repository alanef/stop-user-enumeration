<?php
/*
Plugin Name: Stop User Enumeration
Plugin URI: https://fullworksplugins.com/products/stop-user-enumeration/
Description: Helps secure your site against hacking attacks through detecting  User Enumeration
Version: 1.7.5
Author: Fullworks
Requires at least: 6.3
Requires PHP: 7.4
Text Domain: stop-user-enumeration
Domain Path: /languages
Author URI: https://fullworksplugins.com/
License: GPLv2 or later.
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

namespace Stop_User_Enumeration;

use Stop_User_Enumeration\Includes\Core;

// Check if the WPINC constant is defined to ensure the script is being run within WordPress.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define the plugin directory path constant.
define( 'STOP_USER_ENUMERATION_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Define the plugin version constant.
define( 'STOP_USER_ENUMERATION_PLUGIN_VERSION', '1.7.5' );

// Include the autoloader to dynamically include the classes.
require_once STOP_USER_ENUMERATION_PLUGIN_DIR  . 'includes/vendor/autoload.php';

/**
 * Run the Stop User Enumeration plugin.
 *
 * This function registers the activation and uninstall hooks,
 * creates an instance of the Core class, and runs the plugin.
 *
 * @return void
 */
function run_stop_user_enumeration() {
	register_activation_hook( __FILE__, array( '\Stop_User_Enumeration\Includes\Activator', 'activate' ) );
	register_uninstall_hook( __FILE__, array( '\Stop_User_Enumeration\Includes\Uninstall', 'uninstall' ) );
	$plugin = new Core();
	$plugin->run();
}

// Execute the run_stop_user_enumeration function to start the plugin.
run_stop_user_enumeration();

