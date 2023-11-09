<?php


/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, hooks & filters
 *
 */

namespace Stop_User_Enumeration\FrontEnd;

use Stop_User_Enumeration\Includes\Core;

use WP_Error;

class FrontEnd {

	/**
	 * The ID of this plugin.
	 *
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 */
	private $version;


	/**
	 * Initialize the class and set its properties.
	 *
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/frontend.js', array(), $this->version, false );
	}


	public function check_request() {
		/*
		* Validate incoming request
		 *
		 */
		/* phpcs:ignore WordPress.Security.NonceVerification  -- not saved just checking the request */
		if ( ! is_user_logged_in() && isset( $_REQUEST['author'] ) ) {
			/* phpcs:ignore WordPress.Security.NonceVerification  -- not saved just checking the request */
			if ( $this->ContainsNumbers( $_REQUEST['author'] ) ) {
				$this->sue_log();
				/* phpcs:ignore WordPress.Security.NonceVerification  -- not saved just checking the request */
				wp_die( esc_html__( 'forbidden - number in author name not allowed = ', 'stop-user-enumeration' ) . esc_html( $_REQUEST['author'] ) );
			}
		}
	}

	private function ContainsNumbers( $String ) {
		return preg_match( '/\\d/', $String ) > 0;
	}

	private function sue_log() {
		$ip = $this->get_ip();
		if ( false !== $ip && 'on' === Core::sue_get_option( 'log_auth', 'off' ) ) {
			openlog( 'wordpress(' . sanitize_text_field( $_SERVER['HTTP_HOST'] ) . ')', LOG_NDELAY | LOG_PID, LOG_AUTH );
			syslog( LOG_INFO, esc_html( "Attempted user enumeration from " . $ip ) );
			closelog();
		}
	}

	private function get_ip() {
		$ipaddress = false;
		if ( getenv( 'HTTP_CF_CONNECTING_IP' ) ) {
			$ipaddress = getenv( 'HTTP_CLIENT_IP' );
		} elseif ( getenv( 'HTTP_CLIENT_IP' ) ) {
			$ipaddress = getenv( 'HTTP_CLIENT_IP' );
		} elseif ( getenv( 'HTTP_X_FORWARDED_FOR' ) ) {
			$ipaddress = getenv( 'HTTP_X_FORWARDED_FOR' );
		} elseif ( getenv( 'HTTP_X_FORWARDED' ) ) {
			$ipaddress = getenv( 'HTTP_X_FORWARDED' );
		} elseif ( getenv( 'HTTP_FORWARDED_FOR' ) ) {
			$ipaddress = getenv( 'HTTP_FORWARDED_FOR' );
		} elseif ( getenv( 'HTTP_FORWARDED' ) ) {
			$ipaddress = getenv( 'HTTP_FORWARDED' );
		} elseif ( getenv( 'REMOTE_ADDR' ) ) {
			$ipaddress = getenv( 'REMOTE_ADDR' );
		}

		// sanitize IP address
		return filter_var( $ipaddress, FILTER_VALIDATE_IP );
	}

	public function only_allow_logged_in_rest_access_to_users( $access ) {
		if ( 'on' === Core::sue_get_option( 'stop_rest_user', 'off' ) ) {
			/* phpcs:ignore WordPress.Security.NonceVerification  -- not saved just checking the request */
			if ( ( preg_match( '/users/i', $_SERVER['REQUEST_URI'] ) !== 0 ) || ( isset( $_REQUEST['rest_route'] ) && ( preg_match( '/users/i', $_REQUEST['rest_route'] ) !== 0 ) ) ) {
				if ( ! is_user_logged_in() ) {
					$this->sue_log();

					return new WP_Error( 'rest_cannot_access', esc_html__( 'Only authenticated users can access the User endpoint REST API.', 'stop-user-enumeration' ), array( 'status' => rest_authorization_required_code() ) );
				}
			}
		}

		return $access;
	}

	public function remove_author_sitemap( $provider, $name ) {
		if ( 'users' === $name ) {
			return false;
		}

		return $provider;
	}

	public function remove_author_url_from_oembed( $data ) {
		unset( $data['author_url'] );

		return $data;
	}

}
