<?php
/**
 * Tests for the login name exposure warning (PLUGINS_SMALL-12).
 *
 * @package Stop_User_Enumeration
 */

use Stop_User_Enumeration\Admin\Login_Exposure;

class Test_Login_Exposure extends WP_UnitTestCase {

	/** @var Login_Exposure */
	private $exposure;

	public function setUp(): void {
		parent::setUp();
		$this->exposure = new Login_Exposure();
		// The test install's own admin (user 1) has names set by the installer; start from a clean slate.
		foreach ( get_users( array( 'fields' => 'ID' ) ) as $id ) {
			wp_update_user(
				array(
					'ID'            => $id,
					'display_name'  => 'Someone Else',
					'user_nicename' => 'someone-else-' . $id,
				)
			);
		}
		$this->exposure->clear_cache();
	}

	/**
	 * A user created the way a fresh install creates its admin: no first, last or display name.
	 */
	private function default_user( $login, $role ) {
		return self::factory()->user->create(
			array(
				'user_login'   => $login,
				'role'         => $role,
				'display_name' => '',
			)
		);
	}

	private function render_notice( $screen = 'dashboard' ) {
		set_current_screen( $screen );
		ob_start();
		$this->exposure->admin_notice();
		return ob_get_clean();
	}

	public function test_default_admin_is_flagged_for_display_name_and_slug() {
		$id = $this->default_user( 'siteowner', 'administrator' );
		$this->assertSame( 'siteowner', get_userdata( $id )->display_name );
		$this->assertSame(
			array( $id => array( 'display' => true, 'slug' => true ) ),
			$this->exposure->find()
		);
	}

	public function test_setting_a_display_name_clears_that_part_only() {
		$id = $this->default_user( 'siteowner', 'administrator' );
		wp_update_user( array( 'ID' => $id, 'first_name' => 'Pat', 'display_name' => 'Pat' ) );
		$this->assertSame( array( $id => array( 'display' => false, 'slug' => true ) ), $this->exposure->get() );
	}

	public function test_match_is_case_insensitive() {
		$id = self::factory()->user->create( array( 'user_login' => 'SiteOwner', 'role' => 'author', 'display_name' => 'siteowner', 'user_nicename' => 'pat' ) );
		$this->assertSame( array( $id => array( 'display' => true, 'slug' => false ) ), $this->exposure->find() );
	}

	public function test_slug_is_compared_with_the_sanitised_login() {
		$id = $this->default_user( 'jo.smith@example.com', 'editor' );
		$this->assertSame( sanitize_title( 'jo.smith@example.com' ), get_userdata( $id )->user_nicename );
		$this->assertTrue( $this->exposure->find()[ $id ]['slug'] );
	}

	public function test_subscriber_is_not_flagged_until_they_can_publish() {
		$id = $this->default_user( 'reader', 'subscriber' );
		$this->assertSame( array(), $this->exposure->get() );

		( new WP_User( $id ) )->set_role( 'author' );
		$this->assertArrayHasKey( $id, $this->exposure->get(), 'set_user_role must drop the cache' );
	}

	public function test_result_is_cached_and_profile_update_drops_it() {
		$id = $this->default_user( 'siteowner', 'administrator' );
		$this->exposure->get();
		$this->assertIsArray( get_option( Login_Exposure::CACHE_OPTION ) );

		wp_update_user( array( 'ID' => $id, 'first_name' => 'Pat', 'display_name' => 'Pat' ) );
		$this->assertFalse( get_option( Login_Exposure::CACHE_OPTION ), 'profile_update must drop the cache' );
	}

	public function test_new_user_drops_the_cache() {
		$this->exposure->get();
		$this->default_user( 'newauthor', 'author' );
		$this->assertFalse( get_option( Login_Exposure::CACHE_OPTION ) );
	}

	public function test_notice_shows_for_an_administrator_on_the_dashboard() {
		$id = $this->default_user( 'siteowner', 'administrator' );
		wp_set_current_user( $id );
		$html = $this->render_notice();
		$this->assertStringContainsString( 'some login names are on show', $html );
		$this->assertStringContainsString( esc_url( get_edit_user_link( $id ) ), $html );
		$this->assertStringContainsString( 'cannot be changed on the profile screen', $html );
	}

	public function test_notice_is_not_shown_to_an_editor_or_on_other_screens() {
		$this->default_user( 'siteowner', 'administrator' );
		wp_set_current_user( $this->default_user( 'theeditor', 'editor' ) );
		$this->assertSame( '', $this->render_notice() );

		wp_set_current_user( $this->default_user( 'otheradmin', 'administrator' ) );
		$this->assertSame( '', $this->render_notice( 'edit-post' ) );
		$this->assertNotSame( '', $this->render_notice( 'users' ) );
	}

	public function test_dismissed_notice_returns_only_for_a_new_match() {
		$admin = $this->default_user( 'siteowner', 'administrator' );
		wp_set_current_user( $admin );
		$this->exposure->record_dismissal( $admin );
		$this->assertSame( '', $this->render_notice() );

		$author = $this->default_user( 'newauthor', 'author' );
		$html   = $this->render_notice();
		$this->assertStringContainsString( esc_url( get_edit_user_link( $author ) ), $html );
		$this->assertStringNotContainsString( esc_url( get_edit_user_link( $admin ) ), $html, 'dismissed matches stay dismissed' );
	}

	public function test_dismissal_is_per_administrator() {
		$admin = $this->default_user( 'siteowner', 'administrator' );
		$this->exposure->record_dismissal( $admin );

		wp_set_current_user( $this->default_user( 'secondadmin', 'administrator' ) );
		$this->assertStringContainsString( 'some login names are on show', $this->render_notice() );
	}

	public function test_settings_box_lists_dismissed_matches_too() {
		$admin = $this->default_user( 'siteowner', 'administrator' );
		$this->exposure->record_dismissal( $admin );
		ob_start();
		$this->exposure->meta_box();
		$this->assertStringContainsString( esc_url( get_edit_user_link( $admin ) ), ob_get_clean() );
	}

	public function test_display_name_advice_only_when_a_display_name_is_on_show() {
		$id = $this->default_user( 'siteowner', 'administrator' );
		wp_update_user( array( 'ID' => $id, 'first_name' => 'Pat', 'display_name' => 'Pat' ) );
		ob_start();
		$this->exposure->meta_box();
		$html = ob_get_clean();
		$this->assertStringNotContainsString( 'To fix a display name', $html );
		$this->assertStringContainsString( 'cannot be changed on the profile screen', $html );
	}

	public function test_settings_box_says_when_nothing_is_on_show() {
		ob_start();
		$this->exposure->meta_box();
		$this->assertStringContainsString( 'No user who can publish', ob_get_clean() );
	}

	public function test_dismiss_refuses_a_missing_nonce() {
		wp_set_current_user( $this->default_user( 'siteowner', 'administrator' ) );
		$this->expectException( WPDieException::class );
		$this->exposure->dismiss();
	}

	public function test_uninstall_removes_cache_and_dismissals() {
		$admin = $this->default_user( 'siteowner', 'administrator' );
		$this->exposure->record_dismissal( $admin );
		Login_Exposure::uninstall();
		$this->assertFalse( get_option( Login_Exposure::CACHE_OPTION ) );
		$this->assertSame( '', get_user_meta( $admin, Login_Exposure::DISMISSED_META, true ) );
	}
}
