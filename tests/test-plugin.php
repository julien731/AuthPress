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
class PluginTests extends WP_UnitTestCase {

	/**
	 * Test that the main instance of the plugin is, indeed, the AuthPress class.
	 */
	function test_init() {
		$this->assertInstanceOf( AuthPress::class, authpress() );
	}

	function test_wordpress_min_version() {
		$this->assertEquals( '3.8', authpress()->wordpress_version_required );
	}

	function test_php_min_version() {
		$this->assertEquals( '5.6', authpress()->php_version_required );
	}
}