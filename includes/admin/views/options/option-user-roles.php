<?php
/**
 * @package   WP Google Authenticator
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://julienliabeuf.com
 * @copyright 2016 Julien Liabeuf
 */

/**
 * Option User Roles
 *
 * @since 1.2
 *
 * @param array $option Current option parameters
 *
 * @return void
 */
function wpga_option_callback_user_roles( $option ) {

	$option_id      = esc_attr( $option['id'] );
	$value          = WPGA()->settings->get_option( $option_id, array() );
	$status         = WPGA()->settings->get_option( 'user_role_status', 'all' );
	$checked_all    = ( 'all' === $status ) ? 'checked="checked"' : '';
	$checked_custom = ( 'custom' === $status ) ? 'checked="checked"' : '';
	?>

	<div id="wpga-user-roles-noforce">
		<?php esc_html_e( 'You must enable the &laquo;Force Use&raquo; option above in order to select user roles.', 'wpga' ); ?>
	</div>

	<div id="wpga-user-roles">

		<div id="wpga-user-role-status" style="margin-bottom: 20px;">
			<label for="user_roles_all">
				<input type="radio" id="user_roles_all" name="<?php echo WPGA()->settings->get_field_name( 'user_role_status' ); ?>" value="all" <?php echo $checked_all; ?>> <?php esc_html_e( 'All', 'wpga' ); ?>
			</label>
			<label for="user_roles_custom">
				<input type="radio" id="user_roles_custom" name="<?php echo WPGA()->settings->get_field_name( 'user_role_status' ); ?>" value="custom" <?php echo $checked_custom; ?>> <?php esc_html_e( 'Custom', 'wpga' ); ?>
			</label>
		</div>

		<div id="wpga-all-roles">

			<?php foreach ( $option['opts'] as $val => $title ):

				$id = $option_id . '_' . $val; ?>

				<label for="<?php echo $id; ?>">
					<input type="checkbox" id="<?php echo $id; ?>" name="<?php echo WPGA()->settings->get_field_name( $option_id ); ?>[]" value="<?php echo esc_attr( $val ); ?>" <?php if ( in_array( $val, $value ) ) { echo 'checked="checked"'; } ?>> <?php echo esc_html( $title ); ?>
				</label><br>

			<?php endforeach; ?>

		</div>

		<?php if ( isset( $field['desc'] ) ) : ?>
			<p class="description"><?php echo esc_html( $field['desc'] ); ?></p><?php endif; ?>

	</div>

<?php }
