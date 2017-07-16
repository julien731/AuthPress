<?php
/**
 * @package   WP Google Authenticator
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://julienliabeuf.com
 * @copyright 2016 Julien Liabeuf
 */

/**
 * Option Text Small
 *
 * @since 1.2
 *
 * @param array  $option Current option parameters
 *
 * @return void
 */
function wpga_option_callback_text_small( $option ) {
	wpga_option_callback_text( $option, 'small-text' );
}