<?php
/**
 * PHPUnit bootstrap file for Stop User Enumeration tests
 */

// Get the WordPress tests directory
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	// wp-env uses /wordpress-phpunit
	if ( file_exists( '/wordpress-phpunit/includes/functions.php' ) ) {
		$_tests_dir = '/wordpress-phpunit';
	} elseif ( file_exists( '/tmp/wordpress-tests-lib/includes/functions.php' ) ) {
		$_tests_dir = '/tmp/wordpress-tests-lib';
	} else {
		$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
	}
}

// Forward custom PHPUnit Polyfills configuration
if ( ! getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	putenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH=' . dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills' );
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php" . PHP_EOL;
	echo "Tests directory: {$_tests_dir}" . PHP_EOL;
	exit( 1 );
}

// Give access to tests_add_filter() function
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Manually load the plugin being tested
 */
function _manually_load_plugin() {
	// In wp-env, the plugin is at the root of the plugins directory
	$plugin_file = dirname( dirname( __DIR__ ) ) . '/stop-user-enumeration/stop-user-enumeration.php';
	if ( ! file_exists( $plugin_file ) ) {
		// Try alternate path
		$plugin_file = dirname( __DIR__ ) . '/stop-user-enumeration.php';
	}
	if ( file_exists( $plugin_file ) ) {
		// First define the plugin directory constant
		if ( ! defined( 'STOP_USER_ENUMERATION_PLUGIN_DIR' ) ) {
			define( 'STOP_USER_ENUMERATION_PLUGIN_DIR', dirname( $plugin_file ) . '/' );
		}
		// Load the autoloader before the plugin
		$autoload_file = dirname( $plugin_file ) . '/includes/vendor/autoload.php';
		if ( file_exists( $autoload_file ) ) {
			require_once $autoload_file;
		}
		require $plugin_file;
		// Activate the plugin to ensure it's initialized
		do_action( 'activate_' . plugin_basename( $plugin_file ) );
	} else {
		echo "Could not find plugin file!" . PHP_EOL;
		echo "Tried: " . $plugin_file . PHP_EOL;
	}
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment
require "{$_tests_dir}/includes/bootstrap.php";