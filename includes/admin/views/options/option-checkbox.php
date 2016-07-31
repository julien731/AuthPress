<?php
/**
 * @package   WP Google Authenticator
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://julienliabeuf.com
 * @copyright 2016 Julien Liabeuf
 */

/**
 * Option Checkbox
 *
 * @since 1.2
 *
 * @param array $option Current option parameters
 *
 * @return void
 */
function wpga_option_callback_checkbox( $option ) {

	if ( ! isset( $option['opts'] ) ) {
		return;
	}

	$option_id = esc_attr( $option['id'] );

	foreach ( (array) $option['opts'] as $val => $title ) {

		$id = $option_id . '_' . $val; ?>

		<label for="<?php echo $id; ?>">
			<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo WPGA()->settings->get_field_name( $option_id ); ?>[]" value="<?php echo esc_attr( $val ); ?>" <?php if ( in_array( $val, (array) WPGA()->settings->get_option( $option_id ) ) ) { echo 'checked="checked"'; } ?>> <?php echo $title; ?>
		</label>

	<?php }
}