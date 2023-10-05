<?php
/**
 * Created
 * User: alan
 * Date: 04/04/18
 * Time: 13:45
 */

namespace Stop_User_Enumeration\Admin;

use AlanEFPluginDonation\PluginDonation;


/**
 * Class Admin_Settings
 * @package Stop_User_Enumeration\Admin
 */
class Admin_Settings extends Admin_Pages {

	protected $settings_page;
	protected $settings_page_id = 'settings_page_stop-user-enumeration';
	protected $option_group = 'stop-user-enumeration';
	protected $settings_title;

    public $donation;

	/**
	 * Settings constructor.
	 *
	 * @param string $plugin_name
	 * @param string $version plugin version.
	 */

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name    = $plugin_name;
		$this->version        = $version;
		$this->settings_title = esc_html__( 'Stop User Enumeration', 'stop-user-enumeration' );
		$this->donation       = new PluginDonation(
			'stop-user-enumeration',
			$this->settings_page_id,
			'stop-user-enumeration/stop-user-enumeration.php',
			admin_url( 'options-general.php?page=stop-user-enumeration' ),
			$this->settings_title
		);
		add_filter( 'plugindonation_lib_strings_stop-user-enumeration', array( $this, 'set_strings' ) );
		parent::__construct();
	}


	public function register_settings() {
		/* Register our setting. */
		register_setting(
			$this->option_group,                         /* Option Group */
			'stop-user-enumeration',                   /* Option Name */
			array( $this, 'sanitize_settings' )          /* Sanitize Callback */
		);

		/* Add settings menu page */
		$this->settings_page = add_submenu_page(
			'stop-user-enumeration',
			'Settings', /* Page Title */
			'Settings',                       /* Menu Title */
			'manage_options',                 /* Capability */
			'stop-user-enumeration',                         /* Page Slug */
			array( $this, 'settings_page' )          /* Settings Page Function Callback */
		);

		register_setting(
			$this->option_group,                         /* Option Group */
			"{$this->option_group}-reset",                   /* Option Name */
			array( $this, 'reset_sanitize' )          /* Sanitize Callback */
		);

	}


	public function delete_options() {
		update_option( 'stop-user-enumeration', self::option_defaults( 'stop-user-enumeration' ) );

	}

	public static function option_defaults( $option ) {
		switch ( $option ) {
			case 'stop-user-enumeration':
				return array(
					// set defaults
					'stop_rest_user' => 'on',
					'stop_sitemap'   => 'on',
					'stop_oembed'    => 'on',
					'log_auth'       => 'on',
					'comment_jquery' => 'on',
				);
			default:
				return false;
		}
	}

	public function add_meta_boxes() {
		add_meta_box(
			'settings-1',                  /* Meta Box ID */
			esc_html__( 'Information', 'stop-user-enumeration' ),               /* Title */
			array( $this, 'meta_box_information' ),  /* Function Callback */
			$this->settings_page_id,               /* Screen: Our Settings Page */
			'normal',                 /* Context */
			'default'                 /* Priority */
		);
		add_meta_box(
			'settings-2',                  /* Meta Box ID */
			__( 'Options', 'stop-user-enumeration' ),               /* Title */
			array( $this, 'meta_box_options' ),  /* Function Callback */
			$this->settings_page_id,               /* Screen: Our Settings Page */
			'normal',                 /* Context */
			'default'                 /* Priority */
		);
	}


	public function meta_box_information() {
		?>
        <table class="form-table">
            <tbody>
			<?php $this->donation->display(); ?>
            <tr class="alternate">
                <th scope="row"><?php esc_html_e( 'About this Plugin', 'stop-user-enumeration' ); ?></th>
                <td><p>
						<?php esc_html_e( 'Stop User Enumeration detects attempts by malicious scanners to identify your users', 'stop-user-enumeration' ); ?>
                    </p>
                    <p>
						<?php
						esc_html_e(
							'If a bot or user is caught scanning for user names they are denied access and their IP is
                        logged',
							'stop-user-enumeration'
						);
						?>
                    </p>
                    <p>
						<?php
						esc_html_e(
							'When you are viewing an admin page, the plugin does nothing, this is designed this way as it is
                        assumed admin user have authority, bear this in mind when testing.',
							'stop-user-enumeration'
						);
						?>
                    </p><br>
                    <p>
						<?php
						esc_html_e(
							'This plugin is best used in conjunction with a blocking tool to exclude the IP for longer. If you
                        are on a VPS or dedicated server where you have root access you can install and configure',
							'stop-user-enumeration'
						);
						?>
                        <a href="https://www.fail2ban.org" target="_blank">fail2ban</a></p><br>
                    <p>
						<?php esc_html_e( 'Also note: It is very common for users to leave their Display Name and Nickname the same as their Username, in which case the Username is leaked by so many things. Best to check at least your admins don\'t do this', 'stop-user-enumeration' ); ?>
                    </p>
                </td>
            </tr>
            </tbody>
        </table>
		<?php
	}

	public function sanitize_settings( $settings ) {
		if ( ! isset( $settings['stop_rest_user'] ) ) {
			$settings['stop_rest_user'] = 'off';  // always set checkboxes if they dont exist
		}
		if ( ! isset( $settings['stop_sitemap'] ) ) {
			$settings['stop_sitemap'] = 'off';  // always set checkboxes if they dont exist
		}
		if ( ! isset( $settings['stop_oembed'] ) ) {
			$settings['stop_oembed'] = 'off';  // always set checkboxes if they dont exist
		}
		if ( ! isset( $settings['log_auth'] ) ) {
			$settings['log_auth'] = 'off';  // always set checkboxes if they dont exist
		}
		if ( ! isset( $settings['comment_jquery'] ) ) {
			$settings['comment_jquery'] = 'off';  // always set checkboxes if they dont exist
		}

		return $settings;
	}


	public function meta_box_options() {
		?>
		<?php
		$options = get_option( 'stop-user-enumeration' );
		if ( ! isset( $options['stop_rest_user'] ) ) {
			$options['stop_rest_user'] = 'off';
		}
		if ( ! isset( $options['stop_sitemap'] ) ) {
			$options['stop_sitemap'] = 'off';
		}
		if ( ! isset( $options['stop_oembed'] ) ) {
			$options['stop_oembed'] = 'off';
		}
		if ( ! isset( $options['log_auth'] ) ) {
			$options['log_auth'] = 'off';
		}
		if ( ! isset( $options['comment_jquery'] ) ) {
			$options['comment_jquery'] = 'off';
		}
		?>
        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row"><?php esc_html_e( 'Stop REST API User calls', 'stop-user-enumeration' ); ?></th>
                <td>
                    <label for="stop-user-enumeration[stop_rest_user]"><input type="checkbox"
                                                                              name="stop-user-enumeration[stop_rest_user]"
                                                                              id="stop-user-enumeration[stop_rest_user]"
                                                                              value="on"
							<?php checked( 'on', $options['stop_rest_user'] ); ?>>
						<?php esc_html_e( 'WordPress allows anyone to find users by API call, by checking this box the calls will be restricted to logged in users only. Only untick this box if you need to allow unfettered API access to users', 'stop-user-enumeration' ); ?>
                    </label>
                </td>
            </tr>
            <tr class="alternate">
                <th scope="row"><?php esc_html_e( 'Stop oEmbed calls revealing user ids', 'stop-user-enumeration' ); ?></th>
                <td>
                    <label for="stop-user-enumeration[stop_oembed]"><input type="checkbox"
                                                                           name="stop-user-enumeration[stop_oembed]"
                                                                           id="stop-user-enumeration[stop_oembed]"
                                                                           value="on"
							<?php checked( 'on', $options['stop_oembed'] ); ?>>
						<?php esc_html_e( 'WordPress reveals the user login ID through oEmbed calls by including the Author Archive link which contains the user id. When in many cases just the Author Name is enough. Note: remember it is not good idea to have login user id equal to your display name', 'stop-user-enumeration' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Disable WP Core Author sitemaps', 'stop-user-enumeration' ); ?></th>
                <td>
                    <label for="stop-user-enumeration[stop_sitemap]"><input type="checkbox"
                                                                            name="stop-user-enumeration[stop_sitemap]"
                                                                            id="stop-user-enumeration[stop_sitemap]"
                                                                            value="on"
							<?php checked( 'on', $options['stop_sitemap'] ); ?>>
						<?php esc_html_e( 'WordPress provides sitemaps for built-in content types like pages and author archives out of the box. The Author sitemap exposes the user id.', 'stop-user-enumeration' ); ?>
                    </label>
                </td>
            </tr>
            <tr class="alternate">
                <th scope="row"><?php esc_html_e( 'log attempts to AUTH LOG', 'stop-user-enumeration' ); ?></th>
                <td>
                    <label for="stop-user-enumeration[log_auth]"><input type="checkbox"
                                                                        name="stop-user-enumeration[log_auth]"
                                                                        id="stop-user-enumeration[log_auth]"
                                                                        value="on"
							<?php checked( 'on', $options['log_auth'] ); ?>>
						<?php
						printf(
							esc_html__(
								'Leave this ticked if you are using %1$sFail2Ban%2$s on your VPS to block attempts at enumeration.%3$s If you are not running Fail2Ban or on a shared host this does not need to be ticked, however it normally will not cause a problem being ticked.',
								'stop-user-enumeration'
							),
							'<a href="http://www.fail2ban.org/wiki/index.php/Main_Page" target="_blank">',
							'</a>',
							'<br>'
						);
						?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Remove numbers from comment authors', 'stop-user-enumeration' ); ?></th>
                <td>
                    <label for="stop-user-enumeration[comment_jquery]"><input type="checkbox"
                                                                              name="stop-user-enumeration[comment_jquery]"
                                                                              id="stop-user-enumeration[comment_jquery]"
                                                                              value="on"
							<?php checked( 'on', $options['comment_jquery'] ); ?>>
						<?php
						esc_html_e(
							'This plugin uses JavaScript to remove any numbers from a comment author name, this is because numbers trigger enumeration checking. You can untick this if you do not use comments on your site or you use a different comment method than standard',
							'stop-user-enumeration'
						);
						?>
                    </label>
                </td>
            </tr>
            </tbody>
        </table>
		<?php
	}

	public function set_strings( $strings ) {
		$strings = array(
			esc_html__( 'Gift a Donation', 'stop-wp-emails-going-to-spam' ),
			// 0
			esc_html__( 'Hi, I\'m Alan and I built this free plugin to solve problems I had, and I hope it solves your problem too.', 'stop-wp-emails-going-to-spam' ),
			// 1
			esc_html__( 'It would really help me know that others find it useful and a great way of doing this is to gift me a small donation', 'stop-wp-emails-going-to-spam' ),
			// 2
			esc_html__( 'Gift a donation: select your desired option', 'stop-wp-emails-going-to-spam' ),
			// 3
			esc_html__( 'My Bitcoin donation wallet', 'stop-wp-emails-going-to-spam' ),
			// 4
			esc_html__( 'Gift a donation via PayPal', 'stop-wp-emails-going-to-spam' ),
			// 5
			esc_html__( 'My Bitcoin Cash address', 'stop-wp-emails-going-to-spam' ),
			// 6
			esc_html__( 'My Ethereum address', 'stop-wp-emails-going-to-spam' ),
			// 7
			esc_html__( 'My Dogecoin address', 'stop-wp-emails-going-to-spam' ),
			// 8
			esc_html__( 'Contribute', 'stop-wp-emails-going-to-spam' ),
			// 9
			esc_html__( 'Contribute to the Open Source Project in other ways', 'stop-wp-emails-going-to-spam' ),
			// 10
			esc_html__( 'Submit a review', 'stop-wp-emails-going-to-spam' ),
			// 11
			esc_html__( 'Translate to your language', 'stop-wp-emails-going-to-spam' ),
			// 12
			esc_html__( 'SUBMIT A REVIEW', 'stop-wp-emails-going-to-spam' ),
			// 13
			esc_html__( 'If you are happy with the plugin then we would love a review. Even if you are not so happy feedback is always useful, but if you have issues we would love you to make a support request first so we can try and help.', 'stop-wp-emails-going-to-spam' ),
			// 14
			esc_html__( 'SUPPORT FORUM', 'stop-wp-emails-going-to-spam' ),
			// 15
			esc_html__( 'Providing some translations for a plugin is very easy and can be done via the WordPress system. You can easily contribute to the community and you don\'t need to translate it all.', 'stop-wp-emails-going-to-spam' ),
			// 16
			esc_html__( 'TRANSLATE INTO YOUR LANGUAGE', 'stop-wp-emails-going-to-spam' ),
			// 17
			esc_html__( 'As an open source project you are welcome to contribute to the development of the software if you can. The development plugin is hosted on GitHub.', 'stop-wp-emails-going-to-spam' ),
			// 18
			esc_html__( 'CONTRIBUTE ON GITHUB', 'stop-wp-emails-going-to-spam' ),
			// 19
			esc_html__( 'Get Support', 'stop-wp-emails-going-to-spam' ),
			// 20
			esc_html__( 'WordPress SUPPORT FORUM', 'stop-wp-emails-going-to-spam' ),
			// 21
			esc_html__( 'Hi I\'m Alan and I support the free plugin', 'stop-wp-emails-going-to-spam' ),
			// 22
			esc_html__( 'for you.  You have been using the plugin for a while now and WordPress has probably been through several updates by now. So I\'m asking if you can help keep this plugin free, by donating a very small amount of cash. If you can that would be a fantastic help to keeping this plugin updated.', 'stop-wp-emails-going-to-spam' ),
			// 23
			esc_html__( 'Donate via this page', 'stop-wp-emails-going-to-spam' ),
			// 24
			esc_html__( 'Remind me later', 'stop-wp-emails-going-to-spam' ),
			// 25
			esc_html__( 'I have already donated', 'stop-wp-emails-going-to-spam' ),
			// 26
			esc_html__( 'I don\'t want to donate, dismiss this notice permanently', 'stop-wp-emails-going-to-spam' ),
			// 27
			esc_html__( 'Hi I\'m Alan and you have been using this plugin', 'stop-wp-emails-going-to-spam' ),
			// 28
			esc_html__( 'for a while - that is awesome! Could you please do me a BIG favor and give it a 5-star rating on WordPress? Just to help spread the word and boost my motivation..', 'stop-wp-emails-going-to-spam' ),
			// 29
			esc_html__( 'OK, you deserve it', 'stop-wp-emails-going-to-spam' ),
			// 30
			esc_html__( 'Maybe later', 'stop-wp-emails-going-to-spam' ),
			// 31
			esc_html__( 'Already done', 'stop-wp-emails-going-to-spam' ),
			// 32
			esc_html__( 'No thanks, dismiss this request', 'stop-wp-emails-going-to-spam' ),
			// 33
			esc_html__( 'Donate to Support', 'stop-wp-emails-going-to-spam' ),
			// 34
			esc_html__( 'Settings', 'stop-wp-emails-going-to-spam' ),
			// 35
			esc_html__( 'Help Develop', 'stop-wp-emails-going-to-spam' ),
			// 36
			esc_html__( 'Buy Me a Coffee makes supporting fun and easy. In just a couple of taps, you can donate (buy me a coffee) and leave a message. You don’t even have to create an account!', 'plugin-donation-lib' ),
			// 37
		);

		return $strings;
	}
}

