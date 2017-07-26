<?php
/**
 * @package   AuthPress
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://julienliabeuf.com
 * @copyright 2017 Julien Liabeuf
 *
 * @wordpress-plugin
 * Plugin Name:       AuthPress
 * Plugin URI:        https://wordpress.org/plugins/wp-google-authenticator/
 * Description:       AuthPress protects your WordPress site by adding 2-factor authentication to prevent unauthorized access to your admin.
 * Version:           2.0.0
 * Author:            Julien Liabeuf
 * Author URI:        https://julienliabeuf.com
 * Text Domain:       authpress
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'AuthPress' ) ) :

	/**
	 * Main AuthPress class
	 *
	 * This class is the one and only instance of the plugin. It is used
	 * to load the core and all its components.
	 *
	 * @since 2.0.0
	 */
	final class AuthPress {

		/**
		 * Holds the unique instance of the plugin.
		 *
		 * @var AuthPress Holds the unique instance of AuthPress.
		 * @since 2.0.0
		 */
		private static $instance;

		/**
		 * Possible error message.
		 *
		 * If something goes wrong, or some requirements aren't met during plugin initialization, the error(s) are stored in the $error variable.
		 *
		 * @since 2.0.0
		 * @var null|WP_Error
		 */
		protected $error = null;

		/**
		 * Minimum version of WordPress required ot run the plugin
		 *
		 * @since 2.0.0
		 * @var string
		 */
		public $wordpress_version_required = '3.8';

		/**
		 * Required version of PHP.
		 *
		 * PHP version 5.6 might seem high to some, but this plugin aims at increasing the security of a WordPress site. Using an outdated version of PHP is insecure.
		 *
		 * PHP 5.6 is the oldest version that still gets security support (until 31 Dec. 2018). Once 5.6 stops getting security support,
		 * the minimum required version for AuthPress will be bumped to the oldest version of PHP getting security support (most likely 7.0).
		 *
		 * @since 2.0.0
		 * @var string
		 */
		public $php_version_required = '5.6';

		/**
		 * Instantiate and return the unique AuthPress object.
		 *
		 * @since  2.0.0
		 * @return AuthPress
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof AuthPress ) ) {
				self::$instance = new AuthPress;
				self::$instance->init();
			}
			return self::$instance;
		}

		/**
		 * Throw error on object clone.
		 *
		 * The whole idea of the singleton design pattern is that there is a single
		 * object therefore, we don't want the object to be cloned.
		 *
		 * @since 2.0.0
		 * @return void
		 */
		public function __clone() {
			// Cloning instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'authpress' ), '2.0.0' );
		}

		/**
		 * Disable unserializing of the class.
		 *
		 * @since 2.0.0
		 * @return void
		 */
		public function __wakeup() {
			// Unserializing instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wpga' ), '2.0.0' );
		}

		/**
		 * Add error.
		 *
		 * Add a new error to the WP_Error object
		 * and create the object if it doesn't exist yet.
		 *
		 * @since  2.0.0
		 *
		 * @param string $code    Error code.
		 * @param string $message Error message to add.
		 *
		 * @return void
		 */
		private function add_error( $code = 'init_error', $message ) {
			if ( ! is_object( $this->error ) || ! is_a( $this->error, 'WP_Error' ) ) {
				$this->error = new WP_Error();
			}
			$this->error->add( $code, $message );
		}

		/**
		 * Initialize the plugin.
		 *
		 * This method is what loads everything we need. It is responsible for triggering all the functions that make this plugin work.
		 *
		 * @since 2.0.0
		 * @return void
		 */
		private function init() {

			// First of all, we need to declare our constants.
			self::$instance->setup_constants();
		}

		/**
		 * Declare plugin constants.
		 *
		 * @since 2.0.0
		 * @return void
		 */
		private function setup_constants() {
			define( 'AUTHPRESS_DB_VERSION', 2 );
			define( 'AUTHPRESS_BASENAME', plugin_basename( __FILE__ ) );
		}
	}

endif;

/**
 * The main function responsible for returning the unique AuthPress instance.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @since 2.0.0
 * @return AuthPress
 */
function authpress() {
	return AuthPress::instance();
}

/**
 * Initialize the plugin.
 *
 * Such a great responsibility for such a small function... Initializing the entire plugin. Wow, that's deep man.
 */
authpress();
