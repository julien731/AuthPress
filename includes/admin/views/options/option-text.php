<?php
/**
 * @package   WP Google Authenticator
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://julienliabeuf.com
 * @copyright 2016 Julien Liabeuf
 */

/**
 * Option Text
 *
 * @since 1.2
 *
 * @param array  $option Current option parameters
 * @param string $class  An optional class to add to the field. Useful for printing small and large inputs
 *
 * @return void
 */
function wpga_option_callback_text( $option, $class = 'regular-text' ) {
	$option_id = esc_attr( $option['id'] ); ?>
	<input type="text" id="<?php echo esc_attr( $option['id'] ); ?>" name="<?php echo WPGA()->settings->get_field_name( $option_id ); ?>" value="<?php echo WPGA()->settings->get_option( $option_id ); ?>" class="<?php echo $class; ?>">
<?php }