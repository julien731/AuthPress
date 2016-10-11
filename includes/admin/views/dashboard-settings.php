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
                            <a href="javascript:;" class="fa fa-cog"></a>
                            <a href="javascript:;" class="fa fa-times"></a>
                         </span>
			</header>
			<div class="panel-body">
				<form action="<?php echo add_query_arg( 'page', 'authpress', admin_url( 'users.php' ) ); ?>" class="form-horizontal">
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label class="control-label col-md-2" for="wpga-enable-2fa"><?php esc_html_e( 'Enable 2-Step Auth', 'wpga' ); ?></label>
								<div class="col-md-5">
									<input type="checkbox" checked class="switch-large" id="wpga-enable-2fa">
									<span class="help-block"><?php esc_html_e( 'Strengthen the login process by enabling one time password for your account.', 'wpga' ); ?></span>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label class="control-label col-md-2" for="wpga-enable-2fa"><?php esc_html_e( 'QR Code', 'wpga' ); ?></label>
								<div class="col-md-5">
									<div class="thumbnail" style="width: 300px; height: 300px;">
										<img src="http://www.placehold.it/300x300/EFEFEF/AAAAAA&amp;text=no+image" alt="">
									</div>
									<span class="help-block"><?php esc_html_e( 'Scan this QR code with your phone.', 'wpga' ); ?></span>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label class="control-label col-md-2" for="wpga-opt-confirm"><?php esc_html_e( 'OTP', 'wpga' ); ?></label>
								<div class="col-md-5">
									<div class="input-group">
										<input type="text" class="form-control" id="wpga-opt-confirm" placeholder="<?php esc_html_e( 'OTP', 'wpga' ); ?>">
										<span class="input-group-btn">
                                                        <button class="btn btn-info" type="button"><?php esc_html_e( 'Verify', 'wpga' ); ?></button>
													</span>
									</div>
									<span class="help-block"><?php esc_html_e( 'Input your One Time Password (OTP) to confirm 2-steps authentication.', 'wpga' ); ?></span>
								</div>
							</div>
						</div>
					</div>
					<button type="submit" class="btn btn-primary"><?php esc_html_e( 'Save', 'wpga' ); ?></button>
				</form>
			</div>
		</section>
	</div>
</div>