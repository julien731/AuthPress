<?php
/**
 * @package   WP Google Authenticator/Functions/Misc
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://julienliabeuf.com
 * @copyright 2016 Julien Liabeuf
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'admin_notices', 'wpga_admin_notices' );
/**
 * Add admin notices
 */
function wpga_admin_notices() {

	if ( isset( $_GET['2fa_reset'] ) && 'true' == $_GET['2fa_reset'] ) { ?>

		<div class="error">
			<p><?php printf( wp_kses( __( '2-factor authentication has been deactivated for your account. If you want to reactivate it, go to your %sprofile page%s.', 'wpga' ), array( 'a' => array( 'href' => array() ) ) ), '<a href="' . admin_url( 'profile.php' ) . '#wpga">', '</a>' ); ?></p>
		</div>

	<?php }

	if ( ! isset( $_GET['update'] ) ) {
		return;
	}

	$uid = isset( $_GET['user_id'] ) ? $_GET['user_id'] : '';

	$messages = array(
		'10' => esc_html__( 'Your secret key has been regenerated.', 'wpga' ),
		'11' => sprintf( esc_html__( 'The key for user %s has been revoked.', 'wpga' ), $uid ),
		'12' => sprintf( esc_html__( 'The attempts count has been reset.', 'wpga' ), $uid ),
	);

	if ( ! isset( $messages[ $_GET['update'] ] ) ) {
		return;
	}

	?>
	<div class="updated">
		<p><?php echo esc_html( $messages[ $_GET['update'] ] ); ?></p>
	</div>
	<?php

}

add_action( 'admin_notices', 'wpga_force_set_secret' );
/**
 * Ask user to setup a secret key
 *
 * If the admin sets the use of 2-factor authentication,
 * every user will be reminded to setup a secret key. The
 * message will only disappear after the user completed
 * the configuration.
 *
 * @return void
 */
function wpga_force_set_secret() {

	$user   = wp_get_current_user();
	$active = wpga_get_option( 'active', array() );
	$force  = wpga_get_option( 'force_2fa', array() );
	$roles  = wpga_get_option( 'user_roles', array() );

	$affected = ! empty( $roles ) ? $roles : $user->roles;

	if ( in_array( 'yes', $active ) && in_array( 'yes', $force ) ) {

		if ( 'all' === wpga_get_option( 'user_role_status', 'all' ) || array_intersect( $user->roles, $affected ) ) {

			$secret       = esc_attr( get_user_meta( $user->ID, 'wpga_secret', true ) );
			$max_attempts = (int) wpga_get_option( 'max_attempts' );
			$attempts     = (int) get_user_meta( $user->ID, 'wpga_attempts', true );
			$left         = $max_attempts - $attempts;

			if ( '' == $secret ) {

				?>
				<div class="error">
					<p>
						<?php printf( wp_kses( __( 'The admin is requesting all users to activate 2-factor authentication. <a href="%s">Please do it now</a>.', 'wpga' ), array( 'a' => array( 'href' => array() ) ) ), admin_url( 'profile.php#wpga' ), $left ); ?>
						<?php if ( $max_attempts > 0 ) {
							printf( wp_kses( __( 'You only have <strong>%s</strong> login attempts left.', 'wpga' ), array( 'strong' => array() ) ), $left );
						} ?>
					</p>
				</div>
				<?php

			}

		}

	}

}

/**
 * Register the contextual help for the plugin admin screen
 */
function wpga_contextual_help() {

	if ( ! isset( $_GET['page'] ) || $_GET['page'] != 'wpga-settings' ) {
		return;
	}

	$screen = get_current_screen();

	$screen->add_help_tab( array(
			'id'      => 'desynchronization',
			'title'   => esc_html__( 'Desynchronization', 'wpga' ),
			'content' => wp_kses( __( '<h2>Authorized Clock Desynchronization</h2><p>First of all, you have to understand how the 2-factor authentication works.</p><p>The Google Authenticator will generate a TOTP which stands for Time based One Time Pasword. This one time password, as you might now understand, is generated based on the current time.</p><p>If the server\'s (where your site is hosted) clock and the user\'s phone clock are not perfectly synchronized, the one time password generated won\'t work, as it will be generated on a time which is different from the server.</p><p>The authorized desynchronization will allow your users more time to use their one time password. By default, one code will be valid for <strong>30 seconds</strong>. If you want to give them more time, you can specify a delay in <strong>minutes</strong>.</p><p>Of course, if you give users more time, the security will be lowered. It is advised to stick with the default 30 secs.</p>', 'wpga' ), array( 'h2'     => array(),
			                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    'p'      => array(),
			                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    'strong' => array()
			) ),
		)
	);

}

add_filter( 'admin_footer_text', 'wpga_version_footer' );
/**
 * Add version number in footer
 */
function wpga_version_footer() {

	if ( ! isset( $_GET['page'] ) OR isset( $_GET['page'] ) && 'wpga_options' !== $_GET['page'] ) {
		return;
	}

	printf( wp_kses( __( WPGA_NAME . ' version ' . WPGA_VERSION . ' by <a href="%s">' . WPGA_AUTHOR . '</a>.', 'gtsp' ), array( 'a' => array( 'href' => array() ), ) ), esc_url( WPGA_URI ) );

}

/**
 * Wrapper function for dnh_register_notice()
 *
 * The function dnh_register_notice() comes with the WP Dismissible Notices Handler library
 *
 * @since 1.2
 *
 * @param string $id      Notice ID, used to identify it
 * @param string $type    Type of notice to display
 * @param string $content Notice content
 * @param array  $args    Additional parameters
 *
 * @return bool
 */
function wpga_register_notice( $id, $type, $content, $args = array() ) {

	if ( ! function_exists( 'dnh_register_notice' ) ) {

		$file = WPGA_PATH . 'vendor/julien731/wp-dismissible-notices-handler/handler.php';

		if ( file_exists( $file ) ) {
			require_once( $file );
		}

	}

	if ( function_exists( 'dnh_register_notice' ) ) {
		dnh_register_notice( $id, $type, $content, $args );
	}

}

/**
 * Get Options Page URL
 *
 * Return the URL to the option page whether the plugin is network activated or only activated on a single site.
 *
 * @since 1.2
 * @return string
 */
function wpga_get_option_page_link() {

	$base = is_network_admin() ? admin_url( 'network/settings.php' ) : admin_url( 'options-general.php' );
	$url  = add_query_arg( 'page', 'wpga-settings', $base );

	return esc_url( $url );

}

add_filter( 'plugin_action_links_' . WPGA_BASENAME, 'wpga_settings_page_link' );
add_filter( 'network_admin_plugin_action_links_' . WPGA_BASENAME, 'wpga_settings_page_link' );
/**
 * Add a link to the settings page
 *
 * @since 1.2
 *
 * @param  array $links Plugin links
 *
 * @return array        Links with the settings
 */
function wpga_settings_page_link( $links ) {
	$link    = wpga_get_option_page_link();
	$links[] = "<a href='$link'>" . esc_html__( 'Settings', 'wpga' ) . "</a>";

	return $links;
}
