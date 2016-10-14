<?php
/**
 * @package   WP Google Authenticator/Admin/Views/Dashboard/Settings
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://julienliabeuf.com
 * @copyright 2016 Julien Liabeuf
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
} ?>
<div class="row">
	<div class="col-sm-12">
		<section class="panel">
			<header class="panel-heading">
				<?php esc_html_e( 'Settings', 'wpga' ); ?>
				<span class="tools pull-right">
					<a href="javascript:;" class="fa fa-chevron-down"></a>
				</span>
			</header>
			<div class="panel-body">
				<?php if ( isset( $_GET['message'] ) ):

					$message = filter_input( INPUT_GET, 'message', FILTER_SANITIZE_STRING );
					$messages = array(
						'otp_confirmed' => array(
							'type'    => 'alert-success',
							'message' => esc_html__( 'The OTP was validated. 2-step authentication is now enabled for your account..', 'wpga' ),
						),
						'otp_failed'    => array(
							'type'    => 'alert-danger',
							'message' => esc_html__( 'The OTP could not be validated. Please try again.', 'wpga' ),
						),
					);

					if ( array_key_exists( $message, $messages ) ): ?>
						<div id="wpga-activation-warning" class="alert <?php echo $messages[$message]['type']; ?> fade in">
							<button data-dismiss="alert" class="close close-sm" type="button"><i class="fa fa-times"></i></button>
							<?php echo $messages[$message]['message']; ?>
						</div>
					<?php endif;
				endif; ?>
				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							<label class="control-label col-md-2" for="wpga-enable-2fa"><?php esc_html_e( 'Enable 2-Step Auth', 'wpga' ); ?></label>
							<div class="col-md-5">
								<input type="checkbox" <?php if ( true === (bool) wpga_get_user_option( 'active' ) || false !== wpga_get_user_temp_secret() ): ?>checked<?php endif; ?> class="switch-large" id="wpga-enable-2fa" name="wpga-enable-2fa">
								<span class="help-block"><?php esc_html_e( 'Strengthen the login process by enabling one time password for your account.', 'wpga' ); ?></span>
								<div id="wpga-activation-warning" class="alert alert-warning fade in" <?php if ( false === wpga_get_user_temp_secret() ): ?>style="display:none;"<?php endif; ?>>
									<button data-dismiss="alert" class="close close-sm" type="button">
										<i class="fa fa-times"></i>
									</button>
									<?php esc_html_e( '2-step authentication is not currently active. You need to enter your OTP hereafter in order to fully activate it.', 'wpga' ); ?>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div id="wpga-otp-validation-wrapper" class="wpga-otp-validation" <?php if ( false === wpga_get_user_temp_secret() ): ?>style="display:none;"<?php endif; ?>>
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label class="control-label col-md-2" for="wpga-enable-2fa"><?php esc_html_e( 'QR Code', 'wpga' ); ?></label>
								<div class="col-md-5">
									<div class="thumbnail" id="wpga-2fa-validation-qr" style="width: 300px; height: 300px;">
										<div class="spinner is-active" style="margin:130px 140px"></div>
									</div>
									<span class="help-block"><?php esc_html_e( 'Scan this QR code with your phone.', 'wpga' ); ?></span>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<form action="<?php echo add_query_arg( 'page', 'authpress', admin_url( 'users.php' ) ); ?>" class="form-horizontal" method="post">
							<?php wp_nonce_field( 'validate_otp', 'wpga_otp_confirm' ); ?>
							<div class="col-md-12">
								<div class="form-group">
									<label class="control-label col-md-2" for="wpga-opt-confirm"><?php esc_html_e( 'OTP', 'wpga' ); ?></label>
									<div class="col-md-5">
										<div class="input-group">
											<input type="text" class="form-control" id="wpga-opt-confirm" name="wpga_otp" placeholder="<?php esc_html_e( 'OTP', 'wpga' ); ?>">
											<span class="input-group-btn"><input type="submit" class="btn btn-info"><?php esc_html_e( 'Verify', 'wpga' ); ?></input></span>
										</div>
										<span class="help-block"><?php esc_html_e( 'Input your One Time Password (OTP) to confirm 2-steps authentication activation.', 'wpga' ); ?></span>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</section>
	</div>
</div>