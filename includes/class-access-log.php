<?php
/**
 * @package   WP Google Authenticator/Classes/Access Log
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://julienliabeuf.com
 * @copyright 2016 Julien Liabeuf
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class WPGA_Access_Log {

	/**
	 * Log a new access using an app password
	 *
	 * @since 1.2
	 * @return false|int
	 */
	public function log_access( $user_id, $key_id, $time = '', $ip, $user_agent, $method = '' ) {

		$user = get_user_by( 'id', $user_id );

		// Make sure the user exists
		if ( ! is_object( $user ) || ! is_a( $user, 'WP_User' ) ) {
			return false;
		}

		if ( empty( $data['time'] ) || '0000-00-00 00:00:00' == $data['time'] ) {
			$data['time'] = current_time( 'mysql' );
		}

		global $wpdb;

		$data = array(
			'ID'         => false,
			'user_id'    => (int) $user_id,
			'key_id'     => (int) $key_id,
			'time'       => $time,
			'ip'         => $ip,
			'user_agent' => sanitize_text_field( $user_agent ),
			'method'     => sanitize_text_field( $method ),
		);

		$insert = $wpdb->insert( wpga_apps_access_log_table, $data, array( '%s', '%s', '%d', '%s', '%s', '%s', '%s' ) );

		return false === $insert ? false : $wpdb->insert_id;

	}

	/**
	 * Get log entries
	 *
	 * @since 1.2
	 * @return array
	 */
	public function get_entries() {}

	/**
	 * Get log entries by field
	 *
	 * @since 1.2
	 * @return array
	 */
	public function get_entries_by() {}

}