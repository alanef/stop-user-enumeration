<?php


/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 */

namespace Stop_User_Enumeration\Includes;

use Stop_User_Enumeration\Admin\Admin_Settings;
use Stop_User_Enumeration\FrontEnd\FrontEnd;


class Core {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 */
	protected $version;


	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 */
	public function __construct() {
		$this->plugin_name = 'stop-user-enumeration';
		$this->version     = STOP_USER_ENUMERATION_PLUGIN_VERSION;

		// convert settings from legacy settings
		if ( $opts = get_option( 'sue_settings_settings' ) ) {
			$newopts = array(
				'stop_rest_user' => ( $opts['general_stop_rest_user'] == 1 ) ? 'on' : '',
				'log_auth'       => ( $opts['general_log_auth'] == 1 ) ? 'on' : '',
				'comment_jquery' => ( $opts['general_comment_jquery'] == 1 ) ? 'on' : '',
			);
			update_option( 'stop-user-enumeration', $newopts );
			delete_option( 'sue_settings_settings' );
		}
        if ( false === get_option( 'stop-user-enumeration' ) ) {
	        update_option( 'stop-user-enumeration', Admin_Settings::option_defaults( 'stop-user-enumeration' ) );
	     }


		$this->load_dependencies();
		$this->set_locale();
		$this->settings_pages();
		$this->define_public_hooks();

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 */
	public function run() {


		$this->loader->run();
	}

	/**
	 *
	 *  Loader. Orchestrates the hooks of the plugin.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */

		$this->loader = new Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *

	 */
	private function set_locale() {

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */

		$plugin_i18n = new i18n();

		$this->loader->add_action( 'init', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin./home/alan/Google/Projects/WordPressPlugins/get-directions-orig
	 *
	 */

	private function settings_pages() {

		$settings = new Admin_Settings( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_menu', $settings, 'settings_setup' );

	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return    string    The name of the plugin.
	 * @since     1.0.0
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 * @since     1.0.0
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		$plugin_public = new FrontEnd( $this->get_plugin_name(), $this->get_version() );
		if ( 'on' === $this->sue_get_option( 'comment_jquery', 'off' ) ) {
			$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		}

		$this->loader->add_action( 'init', $plugin_public, 'check_request' );
		$this->loader->add_action( 'rest_authentication_errors', $plugin_public, 'only_allow_logged_in_rest_access_to_users' );
		if ( 'on' === $this->sue_get_option( 'stop_sitemap', 'off' ) ) {
			$this->loader->add_filter( 'wp_sitemaps_add_provider', $plugin_public, 'remove_author_sitemap', 10, 2 );
		}
		if ( 'on' === $this->sue_get_option( 'stop_oembed', 'off' ) ) {
			$this->loader->add_filter( 'oembed_response_data', $plugin_public, 'remove_author_url_from_oembed', 10, 1 );
		}


	}


	public static function sue_get_option( $key = '', $default = false ) {

		$opts = get_option( 'stop-user-enumeration', $default );
		$val  = $default;
		if ( 'all' == $key ) {
			$val = $opts;
		} elseif ( is_array( $opts ) && array_key_exists( $key, $opts ) && false !== $opts[ $key ] ) {
			$val = $opts[ $key ];
		}

		return $val;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 */
	public function get_loader() {
		return $this->loader;
	}

}
