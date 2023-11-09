<?php
/*
Plugin Name: Stop User Enumeration
Plugin URI: https://fullworksplugins.com/products/stop-user-enumeration/
Description: Helps secure your site against hacking attacks through detecting  User Enumeration
Version: 1.4.8
Author: Fullworks
Requires at least: 4.6
Requires PHP: 5.6
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

if ( ! defined( 'WPINC' ) ) {
	die;
}
define( 'STOP_USER_ENUMERATION_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'STOP_USER_ENUMERATION_PLUGIN_VERSION', '1.4.8' );


// Include the autoloader, so we can dynamically include the classes.
require_once STOP_USER_ENUMERATION_PLUGIN_DIR . 'includes/vendor/autoload.php';
require_once STOP_USER_ENUMERATION_PLUGIN_DIR . 'includes/autoloader.php';


function run_stop_user_enumeration() {
	register_activation_hook( __FILE__, array( '\Stop_User_Enumeration\Includes\Activator', 'activate' ) );
	register_uninstall_hook( __FILE__, array( '\Stop_User_Enumeration\Includes\Uninstall', 'uninstall' ) );
	$plugin = new Core();
	$plugin->run();
}

run_stop_user_enumeration();

