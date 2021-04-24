<?php
/**
 * @package   WP Google Authenticator
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://julienliabeuf.com
 * @copyright 2016 Julien Liabeuf
 */
final class WPGA_Settings {

	/**
	 * Plugin Options
	 *
	 * This is the array that contains all our registered options with their parameters such as option type and default
	 * value.
	 *
	 * @var      array
	 * @since    1.2.0
	 */
	protected $_options = null;

	/**
	 * Plugin Current Values
	 *
	 * These are the values that are stored in the database.
	 *
	 * @since 0.1.0
	 * @var array
	 */
	protected $_values_current = null;

	/**
	 * Plugin Default Values
	 *
	 * An array of key/value pairs containing the default values for all registered options as declared in the options
	 * array (also accessible through $_options in this instance).
	 *
	 * @since 1.2
	 * @var array
	 */
	protected $_values_default = null;

	/**
	 * Priority pattern
	 *
	 * Because this framework is designed to be used seamlessly in a multisite environment, we need to know what to
	 * prioritize when the plugin/theme is network-enabled but the options are being worked with on one specific site
	 * of the network and not in the network administration.
	 *
	 * @var string
	 * @since 0.1.0
	 */
	protected $_priority = 'site';

	/**
	 * Error Messages
	 *
	 * @var      array
	 * @since    0.0.1
	 * @access   public
	 */
	public $errors = array();

	/**
	 * Class Constructor
	 *
	 * @param string $priority    Defines who to give the priority to in the case of a MS activation when options are
	 *                            handled by one site of the network
	 */
	public function __construct( $priority = 'site' ) {

		$this->_priority        = in_array( trim( $priority ), array(
			'site',
			'network',
		) ) ? trim( $priority ) : 'site';

		// Hook the menu item
		add_action( 'network_admin_menu', array( $this, 'network_settings_page' ) );
		add_action( 'admin_menu', array( $this, 'network_settings_page' ) );
		add_action( 'admin_init', array( $this, 'save_options' ) );

	}

	/**
	 * Register the options page
	 *
	 * @since 1.2
	 * @return void
	 */
	function network_settings_page() {

		// Network admin menu.
		if ( true === is_multisite() && true === WPGA()->is_network_enabled() ) {
			$menu_page = add_submenu_page( 'settings.php', esc_html__( 'Authenticator Network Settings', 'wpga' ), esc_html__( 'Authenticator', 'wpga' ), 'administrator', 'wpga-settings', array(
				$this,
				'settings_page',
			) );
		}

		// Standalone admin menu.
		else {
			$menu_page = add_submenu_page( 'options-general.php', sprintf( esc_html__( '%1$s Settings', 'wpga' ), WPGA_NAME ), esc_html__( 'Authenticator', 'wpga' ), 'administrator', 'wpga-settings', array(
				$this,
				'settings_page',
			) );
		}

		// Adds my_help_tab when my_admin_page loads
		add_action( 'load-' . $menu_page, 'wpga_contextual_help' );

	}

	/**
	 * Set Options
	 *
	 * Get the plugin options that can be defined throughout the plugin and save them in the _options property of this
	 * instance.
	 *
	 * @since 1.2
	 * @return void
	 */
	protected function _set_options() {

		if ( 'site' === $this->_get_context() ) {
			$this->_options = get_option( $key, $this->get_defaults() );
		} else {
			$this->_options = get_site_option( $key, $this->get_defaults() );
		}

		$this->_options = apply_filters( 'wpga_options', $this->get_option(), $this->_get_context() );
	}

	/**
	 * Get Options
	 *
	 * Get the plugins options as defined in the settings functions file.
	 *
	 * @since 1.2
	 * @return array
	 */
	protected function _get_options() {

		if ( is_null( $this->_options ) ) {
			$this->_set_options();
		}

		return $this->_options;

	}

	/**
	 * Get Options
	 *
	 * @since 0.1.0
	 * @return array
	 */
	public function get_options() {
		return apply_filters( 'wpga_get_options', $this->_get_values(), $this->_get_context() );
	}

	/**
	 * Get Option
	 *
	 * This method is used to retrieve a plugin option from database. It imitates WordPress' get_option()
	 * function but it is limited to the plugin's options. This method also returns the scoped value based on the
	 * current context.
	 *
	 * If $default is set to null, the method will return the option's default value as declared in the _options
	 * property.
	 *
	 * @since 1.2
	 *
	 * @param string $option  The ID of the option to retrieve
	 * @param mixed  $default The default value to return if no value is found in database. Set to null to get the
	 *                        option's default value as declared in $_options
	 *
	 * @return mixed Option value if found, null otherwise
	 */
	public function get_option( $option, $default = null ) {

		$value = $default;

		if ( array_key_exists( $option, $this->get_options() ) ) {
			$value = $this->get_options()[ $option ];
		} elseif ( is_null( $value ) ) {
			$value = $this->get_default( $option );
		}

		return apply_filters( 'wpga_get_option_' . $option, $value, $this->_get_context() );

	}

	/**
	 * Get Settings
	 *
	 * The settings are the list of options organized by section defined by the developers.
	 *
	 * @since 0.1.0
	 * @return array
	 */
	public function get_settings() {
		return apply_filters( 'wpga_get_settings', array(), $this->_get_context() );
	}

	/**
	 * Get Settings Options
	 *
	 * Get the list of options out of the defined settings. This is basically the full list of options without the
	 * sections.
	 *
	 * @since 1.2
	 *
	 * @return array
	 */
	public function get_settings_options() {

		$options = array();

		foreach ( $this->get_settings() as $section ) {
			foreach ( $section['options'] as $option ) {
				array_push( $options, $option );
			}
		}

		return $options;

	}

	/**
	 * Set Current Values
	 *
	 * Lookup values in the database and store them in the _values_current of this instance.
	 *
	 * @since 1.2
	 * @return array
	 */
	protected function _set_values() {
		$key                   = 'wpga_options';
		$this->_values_current = 'site' === $this->_get_context() ? get_option( $key, false ) : get_site_option( $key, false );

		if ( false === $this->_values_current ) {
			$this->_setup_options();
			$this->_values_current = $this->get_defaults();
		}
	}

	/**
	 * Get Current Values
	 *
	 * Get the options values stored in the database.
	 *
	 * @since 1.2
	 * @return array
	 */
	protected function _get_values() {

		if ( is_null( $this->_values_current ) ) {
			$this->_set_values();
		}

		return $this->_values_current;

	}

	/**
	 * Set Defaults
	 *
	 * Loop through all registered options and retrieve their declared default value. If no default value is declared
	 * an empty one is used instead.
	 *
	 * @since 1.2
	 * @return array
	 */
	protected function _set_defaults() {

		$defaults = array();

		foreach ( $this->get_settings_options() as $option ) {
			if ( ! array_key_exists( $option['id'], $defaults ) ) {
				$defaults[ $option['id'] ] = isset( $option['default'] ) ? $option['default'] : '';
			}
		}

		$this->_values_default = $defaults;

	}

	/**
	 * Get Default Values
	 *
	 * Return all the options' default values as declared in $_options.
	 *
	 * @since 1.2
	 *
	 * @return array
	 */
	public function get_defaults() {

		if ( is_null( $this->_values_default ) ) {
			$this->_set_defaults();
		}

		return apply_filters( 'wpga_get_defaults', $this->_values_default, $this->_get_context() );

	}

	/**
	 * Gt Default Value
	 *
	 * Get the default value for a specific option as defined in the options array accessible through $_options.
	 *
	 * @since 1.2
	 *
	 * @param string $option ID of the option to retrieve the default value for
	 *
	 * @return mixed Option default value, false if the option doesn't exist
	 */
	public function get_default( $option ) {

		$value = false;

		if ( array_key_exists( $option, $this->get_defaults() ) ) {
			$value = $this->get_defaults()[ $option ];
		}

		return apply_filters( 'wpga_get_default', $value, $this->_get_context() );

	}

	/**
	 * Get the current context based on the $priority property
	 *
	 * @since 0.1.0
	 * @return string
	 */
	protected function _get_context() {

		if ( ! is_multisite() || is_multisite() && ! is_network_admin() && 'site' === $this->_priority ) {
			return 'site';
		} else {
			return 'network';
		}
	}

	/**
	 * Setup Options
	 *
	 * Save all options and their default values when it is being used for the very first time and the entry does not
	 * yet exist in the database.
	 *
	 * @since 1.2
	 * @return void
	 */
	protected function _setup_options() {
		$this->add_option( 'wpga_options', $this->get_defaults() );
	}

	/**
	 * Update Plugin Settings
	 *
	 * @since 1.2
	 * @return void
	 */
	public function save_options() {

		if ( ! isset( $_POST['wpga_nonce'] ) || ! wp_verify_nonce( $_POST['wpga_nonce'], 'save_options' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options = $this->get_settings_options();
		$new     = array();

		foreach ( $options as $option ) {
			if ( isset( $_POST[ WPGA()->settings->get_field_name( $option['id'] ) ] ) ) {
				$new[ $option['id'] ] = $_POST[ WPGA()->settings->get_field_name( $option['id'] ) ];
			} else {
				if ( isset( $options[ $option['id'] ] ) ) {

				}
			}
		}

		$this->update_option( 'wpga_options', $new );

		// Read-only redirect
		wp_safe_redirect( add_query_arg( 'updated', 'true', wpga_get_option_page_link() ) );
		exit;

	}

	/**
	 * Add Option Based on Context
	 *
	 * @since 1.2
	 *
	 * @param string $name  Option ID
	 * @param mixed  $value Option value
	 *
	 * @return void
	 */
	public function add_option( $name, $value ) {
		if ( 'site' === $this->_get_context() ) {
			add_option( $name, $value );
		} else {
			add_site_option( $name, $value );
		}
	}

	/**
	 * Update Option Based on Context
	 *
	 * @since 1.2
	 *
	 * @param string $name  Option ID
	 * @param mixed  $value Option value
	 *
	 * @return void
	 */
	public function update_option( $name, $value ) {
		if ( 'site' === $this->_get_context() ) {
			update_option( $name, $value );
		} else {
			update_site_option( $name, $value );
		}
	}

	public function delete_option() {}

	/**
	 * Settings Page
	 *
	 * Display the settings page contents.
	 *
	 * @since 1.2
	 * @return void
	 */
	public function settings_page() {

		if ( isset( $_GET['updated'] ) && 'true' === $_GET['updated'] && is_network_admin() ) { ?>
			<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible">
				<p><strong>Settings saved.</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
			</div>
		<?php }

		$form_action = wpga_get_option_page_link(); ?>
		<div class="wrap">

			<h2><?php printf( esc_html__( '%1$s Settings', 'wpga' ), WPGA_NAME ); ?></h2>

			<form action="<?php echo $form_action; ?>" method="post">
				<?php
				foreach ( $this->get_settings() as $section ) {

					printf( '<h2>%1$s</h2>', esc_attr( $section['title'] ) );
					echo '<table class="form-table">';

					foreach ( $section['options'] as $option ) {

						// If no callback is defined we skip this option
						if ( ! isset( $option['callback'] ) || ! function_exists( $option['callback'] ) ) {
							continue;
						}

						// Open a new row and print the option title
						printf( '<th scope="row">%1$s</th><td>', esc_attr( $option['title'] ) );

						// Run the option callback
						call_user_func( $option['callback'], $option );

						// Possibly add a description
						if ( isset( $option['desc'] ) ) {
							printf( '<p class="description">%1$s</p>', wp_kses( $option['desc'], array( 'code' => array() ) ) );
						}

						// Close the row
						printf( '</td></tr>' );

					}

					echo '</table>';

				}
				?>
				<p class="submit">
					<?php wp_nonce_field( 'save_options', 'wpga_nonce' ); ?>
					<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e( 'Save', 'wpga' ); ?>"/>
				</p>

			</form>
		</div>
	<?php }

	/**
	 * Get Field Name
	 *
	 * Prefix and sanitize the field's name attribute.
	 *
	 * @since 1.2
	 *
	 * @param string $id Option ID
	 *
	 * @return string
	 */
	public function get_field_name( $id ) {
		return 'wpga_' . sanitize_key( $id );
	}

}
