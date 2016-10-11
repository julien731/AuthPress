<?php
/**
 * @package   WP Google Authenticator/Admin/Views/Dashboard/App Passwords
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
				<?php esc_html_e( 'Application Passwords', 'wpga' ); ?>
				<span class="tools pull-right">
                            <a href="javascript:;" class="fa fa-chevron-down"></a>
                            <a href="javascript:;" class="fa fa-cog"></a>
                            <a href="javascript:;" class="fa fa-times"></a>
                         </span>
			</header>
			<div class="panel-body">
				<?php
				global $current_user;
				$passwords = WPGA()->recovery->get_key_by( 'user_id', $current_user->ID, false, 'app_password' );

				if ( ! empty( $passwords ) ): ?>
					<table class="table table-hover general-table">
						<thead>
						<tr>
							<th><?php esc_html_e( 'Description', 'wpga' ); ?></th>
							<th><?php esc_html_e( 'Use Count', 'wpga' ); ?></th>
							<th class="hidden-phone"><?php esc_html_e( 'Last Used', 'wpga' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'wpga' ); ?></th>
						</tr>
						</thead>
						<tbody>
						<?php foreach ( $passwords as $password ): ?>
							<tr>
								<td><?php echo $password['name']; ?></td>
								<td><?php echo $password['count']; ?></td>
								<td class="hidden-phone">-</td>
								<td><a href="#" type="button" class="btn btn-danger"><i class="fa fa-trash"></i></a></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</section>
	</div>
</div>