<?php
/**
 * @package   WP Google Authenticator/Admin/Functions/User Profile
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://julienliabeuf.com
 * @copyright 2016 Julien Liabeuf
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'show_user_profile', 'wpga_user_profile_fields' );
/**
 * Add profile custom fields
 *
 * @param WP_User $user The WP_User object of the user being edited.
 * @return void
 */
function wpga_user_profile_fields( $user ) {

	// Register thickbox
	add_thickbox();

	$force  = wpga_get_option( 'force_2fa' );
	$qr     = true;
	$width  = apply_filters( 'wpga_user_profile_qr_width', 300 + 10 );
	$height = apply_filters( 'wpga_user_profile_qr_height', 300 + 10 );
	$secret = get_user_meta( $user->ID, 'wpga_secret', true );
	$args   = array( 'action' => 'regenerate' );
	$backup = get_user_meta( $user->ID, 'wpga_backup_key', true );

	if ( isset( $_GET['user_id'] ) ) {
		$args['user_id'] = (int) $_GET['user_id'];
	}

	$regenerate = wp_nonce_url( add_query_arg( $args, admin_url( 'profile.php' ) ), 'regenerate_key' );

	if ( '' == $secret ) {
		$secret = wpga_generate_secret_key();
		$qr 	= false;
	}
	?>

	<h3 id="wpga"><?php printf( esc_html__( '%1$s Settings', 'wpga' ), WPGA_NAME ); ?></h3>

	<table class="form-table">

		<?php if ( ! $force || $force && is_array( $force ) && ! in_array( 'yes', $force ) ) :

			$active = esc_attr( get_user_meta( $user->ID, 'wpga_active', true ) );

			if ( 'yes' == $active ) {
				$checked = 'checked="checked"';
			} else {
				$checked = '';
			} ?>

			<tr>
				<th><label for="wpga_active"><?php esc_html_e( 'Activate', 'wpga' ); ?></label></th>
				<td>
					<input type="checkbox" name="wpga_active" id="wpga_active" value="yes" <?php echo $checked; ?> /><br />
					<p class="description"><?php esc_html_e( 'Do you wish to use 2-factor authentication (require the Google Authenticator app)?', 'wpga' ); ?></p>
				</td>
			</tr>

		<?php endif; ?>

		<tr>
			<th><label for="wpga_secret"><?php esc_html_e( 'Secret', 'wpga' ); ?></label></th>
			<td>
				<?php if( !$qr ): ?>
					<input type="hidden" name="wpga_secret" id="wpga_secret" disabled value="<?php echo esc_attr( $secret ); ?>" />
					<button type="submit" class="button button-secondary wpgas-generate-key"><?php esc_html_e( 'Generate Key', 'wpga' ); ?></button>
					<p class="description"><?php esc_html_e( 'This is going to be your secret key. Please save changes and scroll back to this field to get your QR code.', 'wpga' ); ?></p>
				<?php else: ?>
					<input type="text" name="wpga_secret" id="wpga_secret" value="<?php echo esc_attr( $secret ); ?>" disabled="disabled" />
					<input type="hidden" name="wpga_secret" id="wpga_secret" value="<?php echo esc_attr( $secret ); ?>" />
					<a href="#TB_inline?width=<?php echo $width; ?>&height=<?php echo esc_attr( $height ); ?>&inlineId=wpga-qr-code" class="thickbox button button-secondary wpga_generate_qrcode"><?php _e( 'Get QR Code', 'wpga' ); ?></a>
					<a href="<?php echo $regenerate; ?>" class="button button-secondary"><?php esc_html_e( 'Regenerate Key', 'wpga' ); ?></a>
					<p class="description"><?php esc_html_e( 'This is your personal secret key. Don\'t share it!', 'wpga' ); ?></p>
				<?php endif; ?>
				<div id="wpga-qr-code" style="display:none;">
					<p totp="<?php echo wpga_get_qr_code_info(); ?>" style="padding:0;margin:0"></p>
				</div>
			</td>
		</tr>

		<?php if ( '' != $backup ):

			$time  = get_user_meta( $user->ID, 'wpga_backup_key_time', true );
			$limit = $time + 300; // Recovery key generation time + 5 mins
			?>
			<tr id="wpga-recovery-field">
				<th><label for="wpga_active"><?php esc_html_e( 'Recovery Code', 'wpga' ); ?></label></th>
				<td>

					<?php
					/**
					 * After it was generated, the rescue code
					 * will be displayed for 5 minutes. After that,
					 * the user will need to type his password
					 * to reveal the rescue code.
					 */
					if ( time() <= $limit ) : ?>

						<div style='font-size:18px; font-weight: bold;'><?php echo esc_html( $backup ); ?></div><p><?php esc_html_e( 'Write this down and keep it safe', 'wpga' ); ?></p>

					<?php else: ?>

						<p class="wpga-check-pwd-link"><a href="#" class="wpga-check-password"><?php esc_html_e( 'Show', 'wpga' ); ?></a></p>

						<div id="wpga-recovery" style="display:none;">
							<p><?php esc_html_e( 'For security reasons, please type your password to see your recovery code.', 'wpga' ); ?></p>
							<label for="pwd" class="sr-only"><?php esc_html_e( 'Your Password', 'wpga' ); ?></label><input type="password" name="pwd" id="pwd">
							<input type="submit" value="OK" placeholder="<?php esc_attr_e( 'Account password', 'wpga' ); ?>" class="button button-secondary wpga-show-recovery">
							<p class="description"><?php esc_html_e( 'If you are unable to use the Google Authenticator for any reason, you can use this one time recovery code instead of the TOTP. Save this code in a safe place.', 'wpga' ); ?></p>
						</div>

					<?php endif; ?>

				</td>
			</tr>
		<?php endif; ?>

	</table>
<?php }

add_action( 'edit_user_profile', 'wpga_admin_custom_profile_fields' );
/**
 * Add admin control fields in user profile
 */
function wpga_admin_custom_profile_fields() {

	if ( ! current_user_can( 'edit_users' ) || ! isset( $_GET['user_id'] ) ) {
		return;
	}

	$user_id      = (int) $_GET['user_id'];
	$secret       = esc_attr( get_user_meta( $user_id, 'wpga_secret', true ) );
	$args         = array( 'action' => 'revoke', 'user_id' => $user_id );
	$rst_arg      = array( 'action' => 'reset', 'user_id' => $user_id );
	$revoke       = wp_nonce_url( add_query_arg( $args, admin_url( 'user-edit.php' ) ), 'revoke_key' );
	$rst          = wp_nonce_url( add_query_arg( $rst_arg, admin_url( 'user-edit.php' ) ), 'reset_key' );
	$attempts     = (int) get_user_meta( $user_id, 'wpga_attempts', true );
	$max_attempts = apply_filters( 'wpga_totp_max_attempts', WPGA()->settings->get_option( 'max_attempts' ) );
	?>
	<h3><?php esc_html_e( 'Authenticator Settings', 'wpga' ); ?></h3>

	<table class="form-table">

		<tr>
			<th><label for="wpga_secret"><?php esc_html_e( 'Secret', 'wpga' ); ?></label></th>
			<td>
				<?php if ( '' == $secret ) : ?>
					<p><strong><?php esc_html_e( 'This user didn&#039;t set a secret key.', 'wpga' ); ?></strong></p>
				<?php else: ?>
					<p><strong><?php esc_html_e( 'This user has a secret key.', 'wpga' ); ?></strong> <a href="<?php echo esc_url( $revoke ); ?>" class="button button-secondary"><?php esc_html_e( 'Revoke Key', 'wpga' ); ?></a></p>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th><label for="wpga_attempts"><?php esc_html_e( 'Login Attempts', 'wpga' ); ?></label></th>
			<td>
				<input type="text" name="wpga_attempts" id="wpga_attempts" value="<?php echo esc_attr( $attempts ); ?>" class="small-text" disabled="disabled" />
				<a href="<?php echo esc_url( $rst ); ?>" class="button button-secondary"><?php esc_html_e( 'Reset', 'wpga' ); ?></a>
				<?php if ( $max_attempts != 0 && $attempts > $max_attempts ) { echo '<span style="color: red;"><strong>' . esc_html_e( '(This user is locked out)', 'wpga' ) . '</strong></span>'; } ?>
				<p class="description"><?php esc_html_e( 'Number of times the user logged-in without using the TOTP.', 'wpga' ); ?></p>
			</td>
		</tr>

	</table>
	<?php

}

/**
 * Get QR Code text
 *
 * Do API calls to get the data for the QR code.  rawurlencode() blog_name and account
 *
 * @return string QR Code URL
 */
function wpga_get_qr_code_info() {

	$blogname = rawurlencode( wpga_get_option( 'blog_name' ) );
	$secret   = esc_attr( get_user_meta( get_current_user_id(), 'wpga_secret', true ) );
	$account  = get_user_meta( get_current_user_id(), 'user_login', true );
	$label    = $blogname . ':' . rawurlencode( $account );

	return 'otpauth://totp/' . $label . '?secret=' . $secret . '&issuer=' . $blogname;

}

add_action( 'personal_options_update', 'wpga_save_profile_custom_fields' );
/**
 * Save custom profile fields
 *
 * @param integer $user_id User ID
 *
 * @return void
 */
function wpga_save_profile_custom_fields( $user_id ) {

	if ( ! isset( $_POST['wpga_secret'] ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}

	if ( '' == get_user_meta( $user_id, 'wpga_secret', true ) ) {

		update_user_meta( $user_id, 'wpga_active', 'yes' );

	} else {

		if ( isset( $_POST['wpga_active'] ) ) {
			update_user_meta( $user_id, 'wpga_active', $_POST['wpga_active'] );
		} else {
			delete_user_meta( $user_id, 'wpga_active' );
		}

	}

	update_user_meta( $user_id, 'wpga_secret', $_POST['wpga_secret'] );

	/**
	 * Delete the user login attempts without using 2FA.
	 * This avoids an incorrect number of allowed attempts
	 * in case the user deactivates the 2FA for his account.
	 *
	 * @since  1.0.8
	 */
	delete_user_meta( $user_id, 'wpga_attempts' );

	/* Check if backup key exist */
	$backup = get_user_meta( $user_id, 'wpga_backup_key', true );

	if ( '' == $backup ) {

		/* Generate a new backup key */
		$key = wpga_generate_backup_key();

		/* Save the backup key */
		update_user_meta( $user_id, 'wpga_backup_key', sanitize_key( $key ) );

		/**
		 * Set a session var to allow user seeing the backup key
		 * without having to enter his password. This will only happen once
		 */
		update_user_meta( $user_id, 'wpga_backup_key_time', time() );

	}
}