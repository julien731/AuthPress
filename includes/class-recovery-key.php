<?php
/**
 * @package   WP Google Authenticator/Classes/Recovery Key
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://julienliabeuf.com
 * @copyright 2016 Julien Liabeuf
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class WPGA_Recovery_Key {

	/**
	 * Generate a new, unique recovery key
	 *
	 * @since 1.2
	 * @return string
	 */
	public function generate_key() {

		$key = $this->generate_random_key();

		if ( true === $this->key_exists( $key ) ) {
			do {
				$key = $this->generate_random_key();
			} while ( true === $this->key_exists( $key ) );
		}

		return $key;

	}

	/**
	 * Generate a random recovery key
	 *
	 * @since 1.2
	 * @return string
	 */
	private function generate_random_key() {

		$length = apply_filters( 'wpga_recovery_code_length', 24 );
		$max    = ceil( $length / 40 );
		$random = '';

		for ( $i = 0; $i < $max; $i ++ ) {
			$random .= sha1( microtime( true ) . mt_rand( 10000, 90000 ) );
		}

		return substr( $random, 0, $length );

	}

	/**
	 * Add a new recovery key
	 *
	 * @since 1.2
	 *
	 * @param int    $user_id The ID of the user whose key it is
	 * @param string $key     The key to add
	 * @param string $name    An optional name for the key to be added
	 * @param string $type    The type of entry
	 *
	 * @return int|false New row ID if insertion is successful, false otherwise
	 */
	public function add_key( $user_id, $key, $name = '', $type = 'recovery_key' ) {

		$user = get_user_by( 'id', $user_id );

		// Make sure the user exists
		if ( ! is_object( $user ) || ! is_a( $user, 'WP_User' ) ) {
			return false;
		}

		// Sanitize the entry type
		if ( ! $this->type_exists( $type ) ) {
			$type = 'recovery_key';
		}

		global $wpdb;

		$data = array(
			'ID'      => false,
			'user_id' => (int) $user_id,
			'time'    => current_time( 'mysql' ),
			'code'    => md5( sanitize_key( $key ) ),
			'name'    => sanitize_text_field( $name ),
			'type'    => $type,
			'count'   => 0,
		);

		$insert = $wpdb->insert( wpga_recovery_keys_table, $data, array( '%s', '%s', '%d', '%s', '%s', '%s', '%s' ) );

		return false === $insert ? false : $wpdb->insert_id;

	}

	/**
	 * Check if a given key type is valid
	 *
	 * @since 1.2
	 *
	 * @param string $type Key type
	 *
	 * @return bool
	 */
	public function type_exists( $type ) {
		return in_array( $type, array( 'recovery_key', 'app_password' ) ) ? true : false;
	}

	/**
	 * Get a recovery key
	 *
	 * @since 1.2
	 *
	 * @param string $field  What field to use to lookup the key
	 * @param mixed  $value  Value of the lookup field
	 * @param bool   $single Should the method return a single result or not
	 * @param string $type   Optional specify what type of key should be looked up
	 *
	 * @return array|WP_Error An array containing the key information on success
	 */
	public function get_key_by( $field = 'ID', $value, $single = false, $type = '' ) {

		// Sanitize field
		if ( ! in_array( $field, array( 'ID', 'code', 'user_id', 'type' ) ) ) {
			return new WP_Error( 'invalid_field', esc_html__( 'The field you are trying to lookup is invalid', 'wpga' ) );
		}

		// Make sure the user exists
		if ( 'user_id' === $field ) {

			$user = get_user_by( 'id', $value );

			if ( ! is_object( $user ) || ! is_a( $user, 'WP_User' ) ) {
				return new WP_Error( 'invalid_user', esc_html__( 'The user is invalid', 'wpga' ) );
			}

		}

		// Make sure the id field is uppercase
		if ( 'id' === $field ) {
			$field = 'ID';
		}

		if ( 'code' === $field ) {
			$value = md5( $value );
		}

		// Set the base query arguments
		$args = array( 'where' => "$field = '$value'" );

		// Possibly add the key type
		if ( ! empty( $type ) && $this->type_exists( $type ) ) {
			$args['where'] .= " AND type = '$type'";
		}

		$result = $this->get( $args );

		if ( is_array( $result ) && count( $result ) > 1 && true === $single ) {
			$result = $result[0];
		}

		return $result;

	}

	/**
	 * Check if a given key exists
	 *
	 * @since 1.2
	 *
	 * @param string $key The key to check
	 *
	 * @return bool
	 */
	public function key_exists( $key ) {

		$key = $this->get_key_by( 'code', $key, true );

		if ( is_array( $key ) && ! empty( $key ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Get all the recovery keys
	 *
	 * @since 1.2
	 * @return array
	 */
	public function get_keys() {
		return $this->get();
	}

	/**
	 * Get all keys for a particular user
	 *
	 * @since 1.2
	 *
	 * @param int $user_id ID of the user whose keys we want to retrieve
	 *
	 * @return array
	 */
	public function get_user_keys( $user_id ) {
		return $this->get_key_by( 'user_id', $user_id );
	}

	/**
	 * Get a user's recovery keys
	 *
	 * @since 1.2
	 *
	 * @param $user_id
	 *
	 * @return bool|string
	 */
	public function get_recovery_keys( $user_id ) {

		$keys     = array();
		$recovery = $this->get_key_by( 'user_id', $user_id );

		if ( is_array( $recovery ) ) {
			foreach ( $recovery as $key ) {
				if ( 'recovery_key' === $key['type'] ) {
					$keys[] = $key['code'];
				}
			}
		}

		return $keys;

	}

	/**
	 * Get the actual recovery code from a key entry
	 *
	 * @since 1.2
	 *
	 * @param array $key A user's key
	 *
	 * @return false|string
	 */
	public function get_key_code( $key ) {

		if ( ! is_array( $key ) || ! isset( $key['code'] ) ) {
			return false;
		}

		return $key['code'];

	}

	/**
	 * Delete a key from the database
	 *
	 * @since 1.2
	 *
	 * @param int $id ID of the key to delete
	 *
	 * @return bool
	 */
	public function delete_key( $id ) {

		$key = $this->get_key_by( 'ID', $id, true );

		if ( false === $key || is_wp_error( $key ) ) {
			return false;
		}

		global $wpdb;

		$wpdb->delete( wpga_recovery_keys_table, array( 'ID' => (int) $id ), array( '%d' ) );

		return true;

	}

	/**
	 * Run a query on the recovery keys table
	 *
	 * @since 1.2
	 *
	 * @global       $wpdb
	 *
	 * @param array  $args   Query arguments
	 * @param string $output Desired output format
	 *
	 * @return array
	 */
	private function get( $args = array(), $output = 'ARRAY_A' ) {

		global $wpdb;

		$query = 'SELECT * FROM ' . wpga_recovery_keys_table;

		if ( isset( $args['where'] ) ) {
			$query .= ' WHERE ' . $args['where'];
		} else {
			$query .= ' WHERE 1';
		}

		$row = $wpdb->get_results( $query, $output );

		return $row;

	}

}