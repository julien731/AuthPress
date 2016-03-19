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

add_action( 'init', 'wpga_init_settings' );
/**
 * Instantiate the settings class
 */
function wpga_init_settings() {

	if ( ! class_exists( 'TAV_Settings' ) ) {
		return;
	}

	// Prepare arguments
	$args = array(
		'name'       => WPGA_PREFIX . '_options',
		'menu_name'  => esc_html__( 'Authenticator', 'wpga' ),
		'parent'     => 'options-general.php',
		'page_title' => esc_html__( 'WP Google Authenticator Settings', 'wpga' ),
		'slug'       => WPGA_PREFIX . '_options',
		'page'       => 'wpga-settings',
		'prefix'     => WPGA_PREFIX,
		'row_name'   => WPGA_PREFIX . '_options'
	);

	// Instantiate the options class
	$settings_page = new TAV_Settings( $args );

	// Get the settings
	$settings = wpga_get_settings();

	// Register sections and settings
	foreach ( $settings as $section => $options ) {

		$settings_page->addSection( $section, $section );

		foreach ( $options as $option ) {
			$settings_page->addOption( $section, $option );
		}

	}

}

/**
 * Register plugin settings that will be displayed in the WP backend
 *
 * @return array
 */
function wpga_get_settings() {

	$settings = array(
		'general'  => array(
			array(
				'id'    => 'active',
				'title' => __( 'Activate Plugin', 'wpga' ),
				'desc'  => __( 'Do you wish to enable the 2-factor authentication for this site?', 'wpga' ),
				'field' => 'checkbox',
				'opts'  => array( 'yes' => __( 'Yes', 'wpga' ) )
			),
			array(
				'id'    => 'force_2fa',
				'title' => __( 'Force Use', 'wpga' ),
				'desc'  => __( 'Do you want to force your users to use 2-factor authentication (admins AND you included)?', 'wpga' ),
				'field' => 'checkbox',
				'opts'  => array( 'yes' => __( 'Yes', 'wpga' ) )
			),
			array(
				'id'    => 'user_roles',
				'title' => __( 'Force Roles', 'wpga' ),
				'desc'  => __( 'You can force users to use 2-factor authentication by role. Requires &laquo;Force Use&raquo; to be enabled. If no role is checked, 2FA will be forced for ALL roles.', 'wpga' ),
				'field' => 'user_roles',
				'opts'  => wpga_get_editable_roles()
			),
			array(
				'id'    => 'blog_name',
				'title' => __( 'Site Name', 'wpga' ),
				'desc'  => __( 'Name under which this site will appear in the Google Authenticator app.', 'wpga' ),
				'field' => 'text'
			)
		),
		'security' => array(
			array(
				'id'    => 'max_attempts',
				'title' => __( 'Max Attempts', 'wpga' ),
				'desc'  => __( 'If you chose to force users to use 2-factor authentication, you can specify a maximum number of times a user can login WITHOUT setting up the 2-factor authentication (leave <code>0</code> for unlimited attempts).', 'wpga' ),
				'field' => 'smalltext'
			),
			array(
				'id'    => 'authorized_delay',
				'title' => __( 'Authorized Clock Desynchronization', 'wpga' ),
				'desc'  => __( 'Must be in <code>min</code> (&plusmn;). Avoid invalid one-time passwords issues. Please read the contextual help for more info.', 'wpga' ),
				'field' => 'smalltext'
			)
		)
	);

	return apply_filters( 'wpga_settings', $settings );

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
 * @param string $opt     ID of the option to lookup
 * @param mixed  $default Default value to return
 *
 * @return mixed
 */
function wpga_get_option( $opt, $default = false ) {

	if ( ! $opt ) {
		return $default;
	}

	/* Get the serialized values */
	$options = get_option( WPGA_PREFIX . '_options', $default );

	if ( $options && is_array( $options ) && ! empty( $options ) ) {

		if ( isset( $options[ $opt ] ) ) {
			return $options[ $opt ];
		} else {
			return $default;
		}

	} else {
		return $default;
	}

}