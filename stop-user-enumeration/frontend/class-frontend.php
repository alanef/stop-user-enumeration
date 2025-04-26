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
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version The version of the plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * This method enqueues the JavaScript file for the plugin on the public-facing side of the site,
	 * ensuring it is not loaded in the admin area.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( ! is_admin() ) {
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/frontend.js', array(), $this->version, array( 'strategy' => 'defer' ) );
		}
	}

	/**
	 * Validate incoming request and restrict access based on certain conditions.
	 *
	 * This method checks if the request is from a logged-out user and contains an 'author' parameter.
	 * If the 'author' parameter contains numeric digits, it logs the attempt and terminates the request.
	 *
	 * @return void
	 */
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
				wp_die( esc_html__( 'forbidden - number in author name not allowed = ', 'stop-user-enumeration' ) . esc_html( $author ), 403 );
			}
		}
	}

	/**
	 * Checks if a given string contains any numeric digits.
	 *
	 * This method uses a regular expression to determine if the input string
	 * contains any numeric digits (0-9).
	 *
	 * @param string $String The input string to check.
	 *
	 * @return bool Returns true if the string contains any numeric digits, false otherwise.
	 */
	private function ContainsNumbers( $String ) {
		return preg_match( '/\\d/', $String ) > 0;
	}

	/**
	 * Logs an attempted user enumeration to the system log or a fallback logging mechanism.
	 *
	 * This method retrieves the IP address of the request and logs an attempted user enumeration
	 * if logging is enabled in the plugin options. It first checks if the `syslog` function is available
	 * and uses it to log the message. If `syslog` is not available, it falls back to using `error_log`.
	 *
	 * @return void
	 */
	private function sue_log() {
		// Get the IP address of the request
		$ip = $this->get_ip();
		
		// Allow filtering of the IP address for integration with external services
		$ip = apply_filters( 'stop_user_enumeration_ip', $ip );

		// Check if the IP address is valid and logging is enabled in the plugin options
		if ( false !== $ip && 'on' === Core::sue_get_option( 'log_auth', 'off' ) ) {

			// Check if the syslog function is available
			if ( function_exists( 'syslog' ) ) {
				// Open a connection to the system logger
				\openlog( 'wordpress(' . ( isset( $_SERVER['HTTP_HOST'] ) ? esc_html( sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) ) : '' ) . ')', LOG_NDELAY | LOG_PID, LOG_AUTH );

				// Log the attempted user enumeration
				\syslog( LOG_INFO, "Attempted user enumeration from " . esc_html( $ip ) );

				// Close the connection to the system logger
				\closelog();
			} else {
				// Fallback logging mechanism using error_log
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- fall back logging
				error_log( "Attempted user enumeration from " . esc_html( $ip ) );
			}
			
			// Action hook for add-ons to process enumeration attempts (limit login, blocklists, etc.)
			do_action( 'stop_user_enumeration_attempt', $ip );
		}
	}

	/**
	 * Retrieves the IP address of the client making the request.
	 *
	 * This method checks various server variables to determine the client's IP address.
	 * It checks for the presence of Cloudflare, client, and forwarded IP addresses,
	 * and falls back to the remote address if none of the others are set.
	 *
	 * @return string|false The client's IP address if found, or false if not found.
	 */
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

	/**
	 * Restricts access to the User endpoint in the REST API to logged-in users only.
	 *
	 * This method checks if the 'stop_rest_user' option is enabled. If it is, it validates the request URI
	 * and REST route to see if they match the pattern for user endpoints. If the request is not from a logged-in user
	 * and does not match the exception pattern, it logs the attempt and returns an error.
	 *
	 * @param mixed $access The current access status.
	 *
	 * @return mixed The modified access status or a WP_Error if access is denied.
	 */
	public function only_allow_logged_in_rest_access_to_users( $access ) {
		if ( 'on' === Core::sue_get_option( 'stop_rest_user', 'off' ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification  -- not saved just checking the request
			$request_uri = ( isset( $_SERVER['REQUEST_URI'] ) ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			// phpcs:ignore WordPress.Security.NonceVerification  -- not saved just checking the request
			$rest_route = ( isset( $_REQUEST['rest_route'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['rest_route'] ) ) : '';
			$pattern    = apply_filters( 'stop_user_enumeration_rest_stop_match', '/users/i' );
			if ( ( preg_match( $pattern, $request_uri ) !== 0 ) || ( preg_match( $pattern, $rest_route ) !== 0 ) ) {
				if ( ! is_user_logged_in() ) {
					$exception = apply_filters( 'stop_user_enumeration_rest_allowed_match', '/simple-jwt-login/i' ); //default exception rule simple-jwt-login
					if ( ( preg_match( $exception, $request_uri ) !== 0 ) || ( preg_match( $exception, $rest_route ) !== 0 ) ) {
						return $access; // check not exception
					}
					
					// Get IP address for logging and filtering
					$ip = $this->get_ip();
					
					// Filter to allow extensions to determine if blocking should occur
					$should_block = apply_filters( 'stop_user_enumeration_should_block', true, $ip );
					
					if ( $should_block ) {
						$this->sue_log();
						return new WP_Error( 'rest_cannot_access', esc_html__( 'Only authenticated users can access the User endpoint REST API.', 'stop-user-enumeration' ), array( 'status' => rest_authorization_required_code() ) );
					}
				}
			}
		}

		return $access;
	}

	/**
	 * Removes the author sitemap provider if the name is 'users'.
	 *
	 * This method is used to prevent the author sitemap from being generated.
	 *
	 * @param mixed $provider The current sitemap provider.
	 * @param string $name The name of the sitemap.
	 *
	 * @return mixed The modified sitemap provider or false if the name is 'users'.
	 */
	public function remove_author_sitemap( $provider, $name ) {
		if ( 'users' === $name ) {
			return false;
		}

		return $provider;
	}

	/**
	 * Removes the author URL from oEmbed data.
	 *
	 * This method unsets the 'author_url' field from the oEmbed response data.
	 *
	 * @param array $data The oEmbed response data.
	 *
	 * @return array The modified oEmbed response data.
	 */
	public function remove_author_url_from_oembed( $data ) {
		unset( $data['author_url'] );

		return $data;
	}
}
