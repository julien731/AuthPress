<?php
/**
 * @package   WP Google Authenticator/Admin/Views/Dashboard
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://julienliabeuf.com
 * @copyright 2016 Julien Liabeuf
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
} ?>

<div class="wrap">
	<div class="icon32" id="icon-options-general"></div>
	<h2><?php esc_html_e( 'My AuthPress', 'wpga' ); ?></h2>
	<section id="main-content">
		<section class="wrapper">
			<?php include( 'dashboard-settings.php' ); ?>
			<?php include( 'dashboard-app-passwords.php' ); ?>
			<?php include( 'dashboard-log.php' ); ?>
		</section>
	</section>
</div>
