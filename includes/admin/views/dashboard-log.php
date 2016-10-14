<?php
/**
 * @package   WP Google Authenticator/Admin/Views/Dashboard/Log
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
				<?php esc_html_e( 'Access Log', 'wpga' ); ?>
				<span class="tools pull-right">
					<a href="javascript:;" class="fa fa-chevron-down"></a>
				</span>
			</header>
			<div class="panel-body">
				<?php
				global $current_user;
				$log = WPGA()->access_log->get_entries_by( 'user_id', $current_user->ID );

				if ( ! empty( $log ) ): ?>
					<table class="table table-hover general-table">
						<thead>
						<tr>
							<th><?php esc_html_e( 'Password', 'wpga' ); ?></th>
							<th><?php esc_html_e( 'Date', 'wpga' ); ?></th>
							<th><?php esc_html_e( 'IP', 'wpga' ); ?></th>
							<th class="hidden-phone"><?php esc_html_e( 'User Agent', 'wpga' ); ?></th>
						</tr>
						</thead>
						<tbody>
							<?php foreach ( $log as $entry ): ?>
								<tr>
									<td>#<?php echo $entry['key_id']; ?></td>
									<td><?php echo date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $entry['time'] ) ); ?></td>
									<td><?php echo $entry['ip']; ?></td>
									<td class="hidden-phone"><?php echo $entry['user_agent']; ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</section>
	</div>
</div>