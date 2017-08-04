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
		$this->assertEquals( '4.6', authpress()->wordpress_version_required );
	}

	function test_php_min_version() {
		$this->assertEquals( '5.6', authpress()->php_version_required );
	}

	function test_textdomain_loaded() {
		add_filter( 'plugin_locale', array( $this, 'set_locale_fr' ) );
		$this->assertTrue( authpress()->load_plugin_textdomain() );
	}

	function set_locale_fr() {
		return 'fr_FR';
	}

	function test_can_init() {
		// Because our tests only run with a compatible environment, this test should pass at this point.
		$this->assertTrue( authpress()->can_init() );
	}

	function test_php_version_pass() {
		// We only run tests on PHP 5.6+ so this test must pass as is.
		$this->assertTrue( authpress()->is_php_version_ok() );
	}

	function test_php_version_fail() {
		authpress()->php_version_required = '8';
		$this->assertFalse( authpress()->is_php_version_ok() );
	}

	function test_wordpress_version_pass() {
		// We only run tests on WordPress 4.6 so this test must pass as is.
		$this->assertTrue( authpress()->is_wordpress_version_ok() );
	}

	function test_wordpress_version_fail() {
		authpress()->wordpress_version_required = '5';
		$this->assertFalse( authpress()->is_wordpress_version_ok() );
	}

	function test_can_init_fail() {
		// Now that we have modified the required versions of both PHP and WordPress so that the requirements fail, this test should not pass as the variables haven't been reset.
		$this->assertFalse( authpress()->can_init() );

		// Now that can_init() has been re-run with invalid requirements, we can also test the error messages.
		$this->assertInstanceOf( WP_Error::class, authpress()->get_errors() );
		$this->assertContains( 'php_version_too_old', authpress()->get_errors()->get_error_codes() );
		$this->assertContains( 'wordpress_version_too_old', authpress()->get_errors()->get_error_codes() );
	}

	function test_dependencies_loaded() {
		$this->assertTrue( class_exists( 'Dismissible_Notices_Handler' ) );
	}
}