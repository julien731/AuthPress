<?php
/**
 * @package   WP Google Authenticator/Views/TOTP Prompt
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://julienliabeuf.com
 * @copyright 2016 Julien Liabeuf
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

login_header(); ?>

	<?php if ( $error_message ) : ?>
	<div id="login_error">
		<?php echo wp_kses( $error_message, array( 'strong' => array() ) ); ?>
	</div>
<?php endif; ?>

	<form action="<?php echo esc_url( $action_url ); ?>" method="post" autocomplete="off">
		<input type="hidden" name="user_id"           value="<?php echo absint( $user->ID ); ?>" />
		<input type="hidden" name="redirect_to"       value="<?php echo esc_attr( $redirect_to ) ?>" />

		<?php wpga_customize_login_form(); ?>

		<p class="submit">
			<input type="submit" id="gapup_token_prompt" name="gapup_token_prompt" class="button button-primary button-large" value="<?php esc_attr_e( 'Log In' ); ?>" />
		</p>
	</form>

<?php login_footer( 'authenticator' );