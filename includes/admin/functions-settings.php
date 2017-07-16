<?php
/**
 * @package   WP Google Authenticator/Admin/Functions/Settings
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://julienliabeuf.com
 * @copyright 2016 Julien Liabeuf
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_filter( 'wpga_get_settings', 'wpga_get_settings' );
/**
 * Plugin Settings
 *
 * Register all the core settings for the plugin.
 *
 * @since 1.0
 * @return array
 */
function wpga_get_settings( $options ) {

	$options['general'] = array(
		'title'   => esc_html__( 'General', 'wpga' ),
		'options' => array(
			array(
				'id'       => 'active',
				'title'    => __( 'Activate Plugin', 'wpga' ),
				'desc'     => __( 'Do you wish to enable the 2-factor authentication for this site?', 'wpga' ),
				'callback' => 'wpga_option_callback_checkbox',
				'opts'     => array( 'yes' => __( 'Yes', 'wpga' ) ),
				'default'  => '',
			),
			array(
				'id'       => 'force_2fa',
				'title'    => __( 'Force Use', 'wpga' ),
				'desc'     => __( 'Do you want to force your users to use 2-factor authentication (admins AND you included)?', 'wpga' ),
				'callback' => 'wpga_option_callback_checkbox',
				'opts'     => array( 'yes' => __( 'Yes', 'wpga' ) ),
			),
			array(
				'id'       => 'user_role_status',
				'title'    => '',
				'desc'     => __( 'Do you want to force your users to use 2-factor authentication (admins AND you included)?', 'wpga' ),
				'callback' => '',
				'default' => 'all',
			),
			array(
				'id'       => 'user_roles',
				'title'    => __( 'Force Roles', 'wpga' ),
				'desc'     => __( 'You can force users to use 2-factor authentication by role. Requires &laquo;Force Use&raquo; to be enabled. If no role is checked, 2FA will be forced for ALL roles.', 'wpga' ),
				'callback' => 'wpga_option_callback_user_roles',
				'opts'     => wpga_get_editable_roles(),
			),
			array(
				'id'       => 'blog_name',
				'title'    => __( 'Site Name', 'wpga' ),
				'desc'     => __( 'Name under which this site will appear in the Google Authenticator app.', 'wpga' ),
				'callback' => 'wpga_option_callback_text',
				'default'  => get_bloginfo( 'name' ),
			),
		),
	);

	$options['security'] = array(
		'title'   => esc_html__( 'Security', 'wpga' ),
		'options' => array(
			array(
				'id'       => 'max_attempts',
				'title'    => __( 'Max Attempts', 'wpga' ),
				'desc'     => __( 'If you chose to force users to use 2-factor authentication, you can specify a maximum number of times a user can login WITHOUT setting up the 2-factor authentication (leave <code>0</code> for unlimited attempts).', 'wpga' ),
				'callback' => 'wpga_option_callback_text_small',
				'default'  => 3,
			),
			array(
				'id'       => 'authorized_delay',
				'title'    => __( 'Authorized Clock Desynchronization', 'wpga' ),
				'desc'     => __( 'Must be in <code>min</code> (&plusmn;). Avoid invalid one-time passwords issues. Please read the contextual help for more info.', 'wpga' ),
				'callback' => 'wpga_option_callback_text_small',
				'default'  => 0,
			),
		),
	);

	return apply_filters( 'wpga_settings', $options );

}

/**
 * Get roles list.
 *
 * @since  1.0.9
 * @return array List of editable roles
 */
function wpga_get_editable_roles() {
	global $wp_roles;

	$all_roles      = $wp_roles->roles;
	$editable_roles = apply_filters( 'editable_roles', $all_roles );
	$list           = array();

	foreach ( $editable_roles as $role_id => $role ) {
		$list[ $role_id ] = $role['name'];
	}

	return $list;

}

/**
 * Get plugin option
 *
 * @since 1.2.0
 *
 * @param string $option  ID of the option to lookup
 * @param mixed  $default Default value to return
 *
 * @return mixed
 */
function wpga_get_option( $option, $default = null ) {
	return WPGA()->settings->get_option( $option, $default );
}