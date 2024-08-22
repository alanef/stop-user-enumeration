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
			$author = sanitize_text_field( wp_unslash( $_REQUEST['author'] ) );
			/* phpcs:ignore WordPress.Security.NonceVerification -- not saved just checking the request */
			if ( $this->ContainsNumbers( $author ) ) {
				$this->sue_log();
				/* phpcs:ignore WordPress.Security.NonceVerification  -- not saved just logging the request, not form input so no unslash*/
				wp_die( esc_html__( 'forbidden - number in author name not allowed = ', 'stop-user-enumeration' ) . esc_html( $author ), array( 'response' => 403 ) );
			}
		}
	}

	private function ContainsNumbers( $String ) {
		return preg_match( '/\\d/', $String ) > 0;
	}

	private function sue_log() {
		$ip = $this->get_ip();
		if ( false !== $ip && 'on' === Core::sue_get_option( 'log_auth', 'off' ) ) {
			openlog( 'wordpress(' . ( isset( $_SERVER['HTTP_HOST'] ) ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '' . ')', LOG_NDELAY | LOG_PID, LOG_AUTH );
			syslog( LOG_INFO, esc_html( "Attempted user enumeration from " . $ip ) );
			closelog();
		}
	}

	private function get_ip() {
		$ipaddress = false;
		if ( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- not form input
			$ipaddress = filter_var( $_SERVER['HTTP_CF_CONNECTING_IP'] );
		} elseif ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- not form input
			$ipaddress = filter_var( $_SERVER['HTTP_CLIENT_IP'] );
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- not form input
			$ipaddress = filter_var( $_SERVER['HTTP_X_FORWARDED_FOR'] );
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- not form input
			$ipaddress = filter_var( $_SERVER['HTTP_X_FORWARDED'] );
		} elseif ( isset( $_SERVER['HTTP_FORWARDED_FOR'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- not form input
			$ipaddress = filter_var( $_SERVER['HTTP_FORWARDED_FOR'] );
		} elseif ( isset( $_SERVER['HTTP_FORWARDED'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- not form input
			$ipaddress = filter_var( $_SERVER['HTTP_FORWARDED'] );
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- not form input
			$ipaddress = filter_var( $_SERVER['REMOTE_ADDR'] );
		}

		return $ipaddress;
	}

	public function only_allow_logged_in_rest_access_to_users( $access ) {
		if ( 'on' === Core::sue_get_option( 'stop_rest_user', 'off' ) ) {
			/* phpcs:ignore WordPress.Security.NonceVerification  -- not saved just checking the request */
			$request_uri = ( isset( $_SERVER['REQUEST_URI'] ) ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$rest_route  = ( isset( $_REQUEST['rest_route'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['rest_route'] ) ) : '';
			$pattern     = apply_filters( 'stop_user_enumeration_rest_stop_match', '/users/i' );
			if ( ( preg_match( $pattern, $request_uri ) !== 0 ) || ( preg_match( $pattern, $rest_route ) !== 0 ) ) {
				if ( ! is_user_logged_in() ) {
					$exception = apply_filters( 'stop_user_enumeration_rest_allowed_match', '/simple-jwt-login/i' ); //default exception rule simple-jwt-login
					if ( ( preg_match( $exception, $request_uri ) !== 0 ) || ( preg_match( $exception, $rest_route ) !== 0 ) ) {
						return $access; // check not exception
					}
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
