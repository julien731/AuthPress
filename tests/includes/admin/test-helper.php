<?php
/**
 * @package   AuthPress/Tests
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://julienliabeuf.com
 * @copyright 2017 Julien Liabeuf
 */

/**
 * Sample test case.
 */
class HelpersTests extends WP_UnitTestCase {

	function test_authpress_register_notice() {
		// Let's register an admin notice.
		authpress_register_notice( 'test_notice', 'error', 'test notice' );

		// Now let's try to find it.
		$notices = DNH()->get_notices();

		$this->assertArrayHasKey( 'test_notice', $notices );
	}

}
