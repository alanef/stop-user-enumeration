<?php
/**
 * Warns administrators when a publishing user's display name or author URL gives away their login name.
 *
 * @package Stop_User_Enumeration
 */

namespace Stop_User_Enumeration\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Login_Exposure
 *
 * WordPress uses the login name as the display name unless a first or last name is given, and builds
 * the author URL (user_nicename) from it. Themes, feeds and oEmbed show both to visitors, so the login
 * name this plugin otherwise keeps private is on show. This audits users who can publish and tells
 * administrators which ones to fix.
 */
class Login_Exposure {

	/**
	 * Cached audit result, not autoloaded. Deleted whenever a user or a role changes.
	 */
	const CACHE_OPTION = 'stop-user-enumeration-login-exposure';

	/**
	 * User meta, per administrator: the matches they dismissed, as "<user id>:display" / "<user id>:slug".
	 */
	const DISMISSED_META = 'sue_login_exposure_dismissed';

	const DISMISS_ACTION = 'sue_dismiss_login_exposure';

	/**
	 * Admin screens the notice shows on.
	 */
	const NOTICE_SCREENS = array( 'dashboard', 'users', 'plugins', 'settings_page_stop-user-enumeration' );

	/**
	 * Users who can publish and whose display name or author URL matches their login name.
	 *
	 * @return array<int, array{display: bool, slug: bool}> Keyed by user ID, matches only.
	 */
	public function find() {
		$exposed = array();
		$users   = get_users(
			array(
				'capability' => 'edit_posts',
				'fields'     => array( 'ID', 'user_login', 'display_name', 'user_nicename' ),
			)
		);
		foreach ( $users as $user ) {
			$login   = strtolower( $user->user_login );
			$display = strtolower( $user->display_name ) === $login;
			$slug    = strtolower( $user->user_nicename ) === strtolower( sanitize_title( $user->user_login ) );
			if ( $display || $slug ) {
				$exposed[ (int) $user->ID ] = array(
					'display' => $display,
					'slug'    => $slug,
				);
			}
		}

		return $exposed;
	}

	/**
	 * The audit, from the cache when there is one.
	 *
	 * @return array<int, array{display: bool, slug: bool}>
	 */
	public function get() {
		$cached = get_option( self::CACHE_OPTION );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		$exposed = $this->find();
		update_option( self::CACHE_OPTION, $exposed, false );

		return $exposed;
	}

	/**
	 * Drop the cache so the next read audits again. Hooked to user and role changes.
	 */
	public function clear_cache() {
		delete_option( self::CACHE_OPTION );
	}

	/**
	 * The audit as match keys: "<user id>:display", "<user id>:slug".
	 *
	 * @param array $exposed Audit result.
	 * @return string[]
	 */
	public static function keys( array $exposed ) {
		$keys = array();
		foreach ( $exposed as $user_id => $parts ) {
			foreach ( array( 'display', 'slug' ) as $part ) {
				if ( ! empty( $parts[ $part ] ) ) {
					$keys[] = $user_id . ':' . $part;
				}
			}
		}

		return $keys;
	}

	/**
	 * The audit less the matches this administrator dismissed.
	 *
	 * @param int $admin_id Administrator user ID.
	 * @return array<int, array{display: bool, slug: bool}>
	 */
	public function undismissed( $admin_id ) {
		$dismissed = get_user_meta( $admin_id, self::DISMISSED_META, true );
		$dismissed = is_array( $dismissed ) ? $dismissed : array();
		$left      = array();
		foreach ( $this->get() as $user_id => $parts ) {
			$display = ! empty( $parts['display'] ) && ! in_array( $user_id . ':display', $dismissed, true );
			$slug    = ! empty( $parts['slug'] ) && ! in_array( $user_id . ':slug', $dismissed, true );
			if ( $display || $slug ) {
				$left[ $user_id ] = array(
					'display' => $display,
					'slug'    => $slug,
				);
			}
		}

		return $left;
	}

	/**
	 * Admin notice, on the dashboard, Users, Plugins and this plugin's settings page.
	 */
	public function admin_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! in_array( $screen->id, self::NOTICE_SCREENS, true ) ) {
			return;
		}
		$exposed = $this->undismissed( get_current_user_id() );
		if ( empty( $exposed ) ) {
			return;
		}
		$dismiss_url = wp_nonce_url(
			add_query_arg( 'action', self::DISMISS_ACTION, admin_url( 'admin-post.php' ) ),
			self::DISMISS_ACTION
		);
		?>
		<div class="notice notice-warning">
			<p><strong><?php esc_html_e( 'Stop User Enumeration: some login names are on show to visitors', 'stop-user-enumeration' ); ?></strong></p>
			<?php $this->render_list( $exposed ); ?>
			<p><a href="<?php echo esc_url( $dismiss_url ); ?>"><?php esc_html_e( 'Dismiss', 'stop-user-enumeration' ); ?></a></p>
		</div>
		<?php
	}

	/**
	 * Settings page meta box: the full list, dismissed or not.
	 */
	public function meta_box() {
		$exposed = $this->get();
		?>
		<p><?php esc_html_e( 'This plugin keeps login names private, but a display name or author URL that matches the login name gives it away in feeds, embeds and author bylines.', 'stop-user-enumeration' ); ?></p>
		<?php
		if ( empty( $exposed ) ) {
			?>
			<p><?php esc_html_e( 'No user who can publish has a display name or author URL that gives away their login name.', 'stop-user-enumeration' ); ?></p>
			<?php
			return;
		}
		$this->render_list( $exposed );
	}

	/**
	 * The affected users, each linked to their profile, with what is on show.
	 *
	 * @param array $exposed Audit result.
	 */
	private function render_list( array $exposed ) {
		$any_display = false;
		$any_slug    = false;
		echo '<ul>';
		foreach ( $exposed as $user_id => $parts ) {
			$user = get_userdata( $user_id );
			if ( ! $user ) {
				continue;
			}
			$shown = array();
			if ( $parts['display'] ) {
				$shown[]     = __( 'display name', 'stop-user-enumeration' );
				$any_display = true;
			}
			if ( $parts['slug'] ) {
				$shown[]  = __( 'author URL', 'stop-user-enumeration' );
				$any_slug = true;
			}
			printf(
				'<li><a href="%1$s">%2$s</a>: %3$s</li>',
				esc_url( get_edit_user_link( $user_id ) ),
				esc_html( $user->display_name ),
				esc_html( implode( ', ', $shown ) )
			);
		}
		echo '</ul>';
		if ( $any_display ) {
			?>
			<p><?php esc_html_e( 'To fix a display name, open the profile, enter a first name or nickname, and choose it under "Display name publicly as".', 'stop-user-enumeration' ); ?></p>
			<?php
		}
		if ( $any_slug ) {
			?>
			<p><?php esc_html_e( 'The author URL is made from the login name and cannot be changed on the profile screen. Changing it also changes that user\'s author archive links, so this plugin does not do it for you.', 'stop-user-enumeration' ); ?></p>
			<?php
		}
	}

	/**
	 * Dismiss: records every current match for this administrator, then goes back.
	 */
	public function dismiss() {
		check_admin_referer( self::DISMISS_ACTION );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'stop-user-enumeration' ), 403 );
		}
		$this->record_dismissal( get_current_user_id() );

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
		exit;
	}

	/**
	 * Add every current match to this administrator's dismissals. A match that appears later is not in
	 * the list, so the notice shows again for it.
	 *
	 * @param int $admin_id Administrator user ID.
	 */
	public function record_dismissal( $admin_id ) {
		$dismissed = get_user_meta( $admin_id, self::DISMISSED_META, true );
		$dismissed = is_array( $dismissed ) ? $dismissed : array();
		update_user_meta( $admin_id, self::DISMISSED_META, array_values( array_unique( array_merge( $dismissed, self::keys( $this->get() ) ) ) ) );
	}

	/**
	 * Remove the cache and every administrator's dismissals.
	 */
	public static function uninstall() {
		delete_option( self::CACHE_OPTION );
		delete_metadata( 'user', 0, self::DISMISSED_META, '', true );
	}
}
