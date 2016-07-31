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

		// Standalone site menu
		if ( true === is_multisite() && false === WPGA()->is_network_enabled() ) {
			add_submenu_page( 'options-general.php', esc_html__( 'WP Google Authenticator Settings', 'wpga' ), esc_html__( 'Authenticator', 'wpga' ), 'administrator', 'wpga-settings', array(
				$this,
				'settings_page',
			) );
		}

		// Network admin menu
		else {
			add_submenu_page( 'settings.php', esc_html__( 'Authenticator Network Settings', 'wpga' ), esc_html__( 'Authenticator', 'wpga' ), 'administrator', 'wpga-settings', array(
				$this,
				'settings_page',
			) );
		}

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
		$redirect = is_network_admin() ? admin_url( 'network/settings.php' ) : admin_url( 'options-general.php' );
		wp_safe_redirect( add_query_arg( array( 'page' => 'wpga-settings', 'updated' => 'true' ), $redirect ) );
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

		if ( isset( $_GET['updated'] ) && 'true' === $_GET['updated'] ) { ?>
			<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible">
				<p><strong>Settings saved.</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
			</div>
		<?php }

		$form_action = is_network_admin() ? esc_url( add_query_arg( 'page', 'wpga-settings', admin_url( 'network/settings.php' ) ) ): esc_url( add_query_arg( 'page', 'wpga_options', admin_url( 'options-general.php' ) ) ); ?>
		<div class="wrap">

			<h2><?php esc_html_e( 'Authenticator Settings', 'wpga' ); ?></h2>

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

//	/**
//	 * Get Option
//	 *
//	 * This is used to access the theme/plugin options, which retrieves them from the
//	 * {@link $_options} property.  This imitates the functionality of
//	 * {@link http://codex.wordpress.org/Function_Reference/get_option}, but is only for
//	 * the plugin/theme's options.
//	 *
//	 * If the value doesn't exist, then the value of the $default parameter will be returned.
//	 *
//	 * @uses     apply_filters() Calls '$this->slug_pre_option_$option' before checking the option.
//	 *               Any value other than false will "short-cut" the retrieval of the option and
//	 *               return the returned filter value instead.
//	 * @uses     apply_filters() Calls '$this->slug_option_$option' on the retrieved/default value
//	 *               and passes the filter function(s) the $default value as well.
//	 *
//	 * @param    string $option      The option to retrieve the value of.
//	 * @param    mixed  $default     The default value to return if the $option being requested
//	 *                               doesn't exist/hasn't been set.  Default is (bool)false.
//	 *
//	 * @return   mixed               Either the option value if it's set or the value of $default.
//	 * @since    0.0.1
//	 * @access   public
//	 */
//	public function get_option( $option, $default = false ) {
//		// If the option isn't set, we'll try setting them just to make sure they've been loaded
//		if ( ! is_array( $this->_options ) || ! isset( $this->_options[ $option ] ) ) {
//			$this->_set_options();
//		}
//
//		// Allows filter hooks to short-cut option(s).
//		if ( ( $pre = apply_filters( $this->slug . '_pre_option_' . $option, false ) ) !== false ) {
//			return $pre;
//		}
//
//		return apply_filters( $this->slug . '_option_' . $option,
//			( isset( $this->_options[ $option ] ) ? $this->_options[ $option ] : $default ), $default );
//	}
//
//	/**
//	 * Get Multisite Option
//	 *
//	 * This is used to access the theme/plugin network/site-wide options, which retrieves
//	 * them from the {@link $_site_options} property.  This immitates the functionality of
//	 * {@link http://codex.wordpress.org/Function_Reference/get_site_option}, but is only for
//	 * the plugin/theme's options.
//	 *
//	 * If this is called on a non-multisite install, it will just return the value from
//	 * calling the {@link get_option()} method.
//	 *
//	 * If the value doesn't exist, then the value of the $default parameter will be returned.
//	 *
//	 * @uses     apply_filters() Calls '$this->slug_pre_site_option_$option' before checking the option.
//	 *               Any value other than false will "short-cut" the retrieval of the option and
//	 *               return the returned filter value instead.
//	 * @uses     apply_filters() Calls '$this->slug_site_option_$option' after retrieving the value
//	 *               and also passes the $default value to the filter function.
//	 *
//	 * @param    string $option      The site option to retrieve the value of.
//	 * @param    mixed  $default     The default value to return if the $option being requested
//	 *                               doesn't exist/hasn't been set.  Default is (bool)false.
//	 *
//	 * @return   mixed               Either the option value if it's set or the value of $default.
//	 * @since    0.0.1
//	 * @access   public
//	 */
//	public function get_site_option( $option, $default = false ) {
//		// Allows filter hooks to short-cut site option(s).
//		if ( ( $pre = apply_filters( $this->slug . '_pre_site_option_' . $option, false ) ) !== false ) {
//			return $pre;
//		}
//
//		if ( ! is_multisite() ) {
//			return apply_filters( $this->slug . '_site_option_' . $option,
//				$this->get_option( $option, $default ), $default );
//		}
//
//		if ( ! is_array( $this->_site_options ) || ( ! isset( $this->_site_options[ $option ] ) &&
//		                                             ! in_array( $option, $this->_deleted_site_options ) )
//		) {
//			$this->_set_site_options();
//		}
//
//		return apply_filters( $this->slug . '_site_option_' . $option,
//			isset( $this->_site_options[ $option ] ) ? $this->_site_options[ $option ] : $default, $default );
//	}
//
//	/**
//	 * Add Option
//	 *
//	 * This adds a new theme/plugin option, which will be added to the {@link $_options} property.
//	 * Theme/Plugin options aren't stored as individual WordPress options, so therefore
//	 * this function is used to keep them all grouped as one WP option.
//	 *
//	 * @uses     do_action() Calls '$this->slug_add_option' before adding the option and
//	 *               passes the hooked functions the option name being added and the value.
//	 * @uses     apply_fiters() Calls '$this->slug_add_option' before adding the option and
//	 *               passes the function(s) the option value and option name.
//	 * @uses     do_action() Calls '$this->slug_add_option_$option' after the option has
//	 *               been successfully added.  The function(s) are passed the set value.
//	 *               If adding the option fails, this isn't called.
//	 * @uses     do_action() Calls '$this->slug_added_option' after the option has been
//	 *               successfully added.  The function(s) are passed the option name and the
//	 *               set value.  If adding the option fails, this isn't called.
//	 *
//	 * @param    string $option The option name we're adding.
//	 * @param    mixed  $value  The value for the option we're adding
//	 *
//	 * @return   bool                true if added successfully, false otherwise
//	 * @since    0.0.1
//	 * @access   public
//	 */
//	public function add_option( $option, $value ) {
//		if ( strlen( ( $option = trim( $option ) ) ) < 1 ) {
//			return false;
//		}
//
//		// If the option already exists, user should use the update_option class method instead.
//		if ( $this->get_option( $option ) !== false ) {
//			return;
//		}
//
//		do_action( $this->slug . '_add_option', $option, $value );
//
//		// Remove it from the deleted_options array if it is in it
//		if ( ( $key = array_search( $option, $this->_deleted_options ) ) !== false ) {
//			unset( $this->_deleted_options[ $key ] );
//		}
//
//		$this->_options[ $option ] = apply_filters( $this->slug . '_add_option', $value, $option );
//
//		if ( $this->_update_wp_options( true ) ) {
//			do_action( $this->slug . '_add_optiion_' . $option, $value );
//			do_action( $this->slug . '_added_option', $option, $value );
//
//			return true;
//		}
//
//		return false;
//	}
//
//	/**
//	 * Add Site Option
//	 *
//	 * This adds a new theme/plugin multisite option, which will be added to the
//	 * {@link $_site_options} property.  Theme/Plugin options aren't stored as individual
//	 * WordPress options, so therefore this function is used to keep them all grouped
//	 * as one WP option.
//	 *
//	 * @uses     do_action() Calls '$this->slug_add_site_option' before adding the site option and
//	 *               passes the hooked functions the site option name being added and the value.
//	 * @uses     apply_fiters() Calls '$this->slug_add_site_option' before adding the option and
//	 *               passes the function(s) the option value and option name.
//	 * @uses     do_action() Calls '$this->slug_add_site_option_$option' after the site option has
//	 *               been successfully added.  The function(s) are passed the set value.
//	 *               If adding the site option fails, this isn't called.
//	 * @uses     do_action() Calls '$this->slug_added_site_option' after the site option has been
//	 *               successfully added.  The function(s) are passed the option name and the
//	 *               set value.  If adding the site option fails, this isn't called.
//	 *
//	 * @param    string $option The site option name we're adding.
//	 * @param    mixed  $value  The value for the site option we're adding
//	 *
//	 * @return   bool                true if added successfully, false otherwise
//	 * @since    0.0.1
//	 * @access   public
//	 */
//	public function add_site_option( $option, $value ) {
//		if ( strlen( ( $option = trim( $option ) ) ) < 1 ) {
//			return false;
//		}
//
//		// If the option already exists, user should use the update_option class method instead.
//		if ( $this->get_site_option( $option ) !== false ) {
//			return;
//		}
//
//		if ( ! is_multisite() ) {
//			return $this->add_option( $option, $value );
//		}
//
//		do_action( $this->slug . '_add_site_option', $option, $value );
//
//		// Remove it from the deleted_site_options array if it is in it
//		if ( ( $key = array_search( $option, $this->_deleted_site_options ) ) !== false ) {
//			unset( $this->_deleted_site_options[ $key ] );
//		}
//
//		$this->_site_options[ $option ] = apply_filters( $this->slug . '_add_site_option', $value, $option );
//
//		if ( $this->_update_wp_options( false, true ) ) {
//			do_action( $this->slug . '_add_site_optiion_' . $option, $option, $value );
//			do_action( $this->slug . '_added_site_option', $option, $value );
//
//			return true;
//		}
//
//		return false;
//	}
//
//	/**
//	 * Get Option Defaults
//	 *
//	 * @since 0.1.0
//	 * @return array
//	 */
//	protected function _get_default_options() {
//
//		$default = array();
//
//		foreach ( $this->_options as $option => $value ) {
//			$default[ sanitize_text_field( $option ) ] = trim( $value );
//		}
//
//		return apply_filters( 'sof_' . $this->slug . '_get_default_options', $default );
//
//	}
//
//	/**
//	 * Set Multisite/Network Options
//	 *
//	 * This method is used to set the plugin/theme network/site-wide options ({@link $_site_options} property)
//	 * if they aren't already set or if the values have been updated and need refreshing.
//	 *
//	 * @uses     do_action() Calls '$this->slug_pre_set_multisite_options' and passes the
//	 *           $refresh parameter by reference before the options are setup for multisite options.
//	 * @uses     apply_filters() Calls '$this->slug_add_default_multisite_options' and passes
//	 *           the {@link $_default_multisite_options} property values to the filter function
//	 *           just before adding the new site option for the first time.  This only runs once!
//	 * @uses     apply_filters() Calls '$this->slug_set_multisite_options' and passes the retrieved
//	 *           site options before setting the {@link $_site_options} property.
//	 *
//	 * @param    bool $refresh       If set to true, it will retrieve the values from the
//	 *                               database and set the {@link $_site_options} property with
//	 *                               the retrieved values.
//	 *
//	 * @return   void
//	 * @since    0.0.1
//	 * @access   protected
//	 */
//	protected function _set_multisite_options( $refresh = false ) {
//		// First validate the class settings
//		if ( ! $this->_validate_settings() ) {
//			wp_die( implode( "\n", $this->errors ) );
//		}
//
//		if ( ! is_multisite() ) {
//			return;
//		}
//
//		do_action( $this->slug . '_pre_set_multisite_options', $refresh );
//
//		// Return if there's nothing to do...
//		if ( is_array( $this->_site_options ) && ! $refresh ) {
//			return;
//		}
//
//		// See if the site options already exist as a WordPress option
//		if ( ( $opts = get_site_option( $this->slug . '_multisite_options' ) ) === false ) {
//			// If not, call the _set_default_options() method to add them
//			$this->_set_default_options();
//
//			$defaults = $this->_default_site_options;
//
//			// Add deleted site options value to the stored site options data
//			if ( ! isset( $defaults['_deleted_options'] ) ) {
//				$defaults['_deleted_options'] = array();
//			}
//
//			// Add the site option since it doesn't exist yet
//			add_site_option( $this->slug . '_multisite_options',
//				apply_filters( $this->slug . '_add_default_multisite_options', $defaults )
//			);
//
//			$opts = get_site_option( $this->slug . '_multisite_options' );
//		}
//		// Set deleted site options
//		$this->_deleted_site_options = $opts['_deleted_options'];
//
//		// Allows options to be overidden or added via filters
//		$this->_site_options = apply_filters( $this->slug . '_set_multisite_options', $opts['options'] );
//	}
//
//	/**
//	 * Update Option
//	 *
//	 * Updates the theme/plugin option and mimicks the behavior of WordPress's
//	 * {@link http://codex.wordpress.org/Function_Reference/update_option} function.
//	 *
//	 * @uses     apply_filters() Calls '{$this->slug}_sanitize_option_{$option}' and passes
//	 *               the function(s) the new value so it can be sanitized before being updated.
//	 * @uses     apply_filters() Calls '{$this->slug}_pre_update_option_{$option}' and passes
//	 *               the function(s) the new value and the old value before updating the option.
//	 * @uses     do_action() Calls '{$this->slug}_update_option' and passes the
//	 *               function(s) the option name, the new value, and the old value before
//	 *               updating the option.  If the option doesn't already exist, this won't be called.
//	 * @uses     do_action() Calls '{$this->slug}_update_option_{$option}' and passes the function(s)
//	 *               the old value and the new value if the option was successfully updated.
//	 * @uses     do_action() Calls '{$this->slug}_updated_option' and passes the function(s)
//	 *               the option name, the old value, and the new value if the option was
//	 *               successfully updated.
//	 *
//	 * @param    string $option The option name to update.
//	 * @param    mixed  $value  The updated value to assign to the option.
//	 *
//	 * @return   bool                true if it was updated, false if updated failed.
//	 * @since    0.0.1
//	 * @access   public
//	 */
//	public function update_option( $option, $value ) {
//		if ( strlen( ( $option = trim( $option ) ) ) < 1 ) {
//			return false;
//		}
//
//		// Allow sanitization filters to be added on a per-option basis
//		$value = apply_filters( $this->slug . '_sanitize_option_' . $option, $value );
//
//		$oldvalue = $this->get_option( $option );
//		$value    = apply_filters( $this->slug . '_pre_update_option_' . $option, $value, $oldvalue );
//
//		// No need to update the option if the values match
//		if ( $value === $oldvalue ) {
//			return false;
//		}
//
//		// If the option doesn't already exist, we'll add it now ...
//		if ( $oldvalue === false && ! isset( $this->_options[ $option ] ) ) {
//			return $this->add_option( $option, $value );
//		}
//
//		do_action( $this->slug . '_update_option', $option, $value, $oldvalue );
//
//		$this->_options[ $option ] = $value;
//
//		if ( $this->_update_wp_options( true ) ) {
//			do_action( $this->slug . '_update_option_' . $option, $oldvalue, $newvalue );
//			do_action( $this->slug . '_updated_option', $option, $oldvalue, $newvalue );
//
//			return true;
//		}
//
//		return false;
//	}
//
//	/**
//	 * Update Site Option
//	 *
//	 * Updates the theme/plugin's multisite option and mimicks the behavior of WordPress's
//	 * {@link http://codex.wordpress.org/Function_Reference/update_site_option} function.
//	 *
//	 * @uses     apply_filters() Calls '{$this->slug}_sanitize_site_option_{$option}' and passes
//	 *               the function(s) the new value so it can be sanitized before being updated.
//	 * @uses     apply_filters() Calls '{$this->slug}_pre_update_site_option_{$option}' and passes
//	 *               the function(s) the new value and the old value before updating the option.
//	 * @uses     do_action() Calls '{$this->slug}_update_site_option' and passes the
//	 *               function(s) the option name, the new value, and the old value before
//	 *               updating the option.  If the option doesn't already exist, this won't be called.
//	 * @uses     do_action() Calls '{$this->slug}_update_site_option_{$option}' and passes the function(s)
//	 *               the old value and the new value if the option was successfully updated.
//	 * @uses     do_action() Calls '{$this->slug}_site_updated_option' and passes the function(s)
//	 *               the option name, the old value, and the new value if the option was
//	 *               successfully updated.
//	 *
//	 * @param    string $option The site option name to update.
//	 * @param    mixed  $value  The updated value to assign to the option.
//	 *
//	 * @return   bool                true if it was updated, false if updated failed.
//	 * @since    0.0.1
//	 * @access   public
//	 */
//	public function update_site_option( $option, $value ) {
//		if ( strlen( ( $option = trim( $option ) ) ) < 1 ) {
//			return false;
//		}
//
//		// Allow sanitization filters to be added on a per-option basis
//		$value = apply_filters( $this->slug . '_sanitize_site_option_' . $option, $value );
//
//		$oldvalue = $this->get_site_option( $option );
//		$value    = apply_filters( $this->slug . '_pre_update_site_option_' . $option, $value, $oldvalue );
//
//		// No need to update the option if the values match
//		if ( $value === $oldvalue ) {
//			return false;
//		}
//
//		if ( ! is_multisite() ) {
//			$result = $this->update_option( $option, $value );
//		} else {
//			// If the option doesn't already exist, we'll add it now ...
//			if ( $oldvalue === false && ! isset( $this->_site_options[ $option ] ) ) {
//				return $this->add_site_option( $option, $value );
//			}
//
//			$this->_site_options[ $option ] = $value;
//
//			$result = $this->_update_wp_options( false, true );
//		}
//
//		do_action( $this->slug . '_update_site_option', $option, $value, $oldvalue );
//
//		if ( $result ) {
//			do_action( $this->slug . '_update_site_option_' . $option, $oldvalue, $newvalue );
//			do_action( $this->slug . '_updated_site_option', $option, $oldvalue, $newvalue );
//
//			return true;
//		}
//
//		return false;
//	}
//
//	/**
//	 * Delete Option
//	 *
//	 * This is used to "delete" options, although this differs a bit from WordPress's
//	 * delete_option() function since any unset options that have default values specified
//	 * in the {@link $_default_options} will be set again.
//	 * Therefore any options with defaults are added to the {@link $_deleted_options} array
//	 * to prevent this after being deleted.
//	 *
//	 * @uses     do_action() Calls '{$this->slug}_delete_option_{$option}' and passes the
//	 *               function(s) the value of the {@link $_options} property.  This is called
//	 *               BEFORE the value is removed from the {@link $_options} property.
//	 * @uses     do_action() Calls '{$this->slug}_delete_option' and passes the function(s)
//	 *               the name of the option being deleted and the value of the {@link $_options} property.
//	 *               This is called AFTER the option has been removed from the {@link $_options} property.
//	 * @uses     do_action() Calls '{$this->slug}_deleted_option' and passes the function(s)
//	 *               the deleted option name if the option was successfully deleted.
//	 *
//	 * @param    string $option The option to delete
//	 *
//	 * @return   bool            true if it was deleted, false if not or if it doesn't exist
//	 * @since    0.0.1
//	 * @access   public
//	 */
//	public function delete_option( $option ) {
//		if ( ! isset( $this->_options[ $option ] ) ) {
//			return false;
//		}
//
//		if ( is_array( $this->_default_options ) && isset( $this->_default_options[ $option ] ) ) {
//			$this->_deleted_options[] = $option;
//		}
//
//		do_action( $this->slug . '_delete_option_' . $option, $this->_options );
//
//		unset( $this->_options[ $option ] );
//
//		do_action( $this->slug . '_delete_option', $option, $this->_options );
//
//		$result = $this->_update_wp_options( true );
//
//		if ( $result ) {
//			do_action( $this->slug . '_deleted_option', $option );
//
//			return true;
//		}
//
//		return false;
//	}
//
//	/**
//	 * Delete Site Option
//	 *
//	 * This is used to "delete" options, although this differs a bit from WordPress's
//	 * delete_site_option() function since any unset options that have default values specified
//	 * in the {@link $_default_site_options} will be set again.
//	 * Therefore any site options with defaults are added to the {@link $_deleted_site_options} array
//	 * to prevent this after being deleted.
//	 *
//	 * @uses     do_action() Calls '{$this->slug}_delete_site_option_{$option}' and passes the
//	 *               function(s) the value of the {@link $_options} property.  This is called
//	 *               BEFORE the value is removed from the {@link $_options} property.
//	 * @uses     do_action() Calls '{$this->slug}_delete_site_option' and passes the function(s)
//	 *               the name of the option being deleted and the value of the {@link $_options} property.
//	 *               This is called AFTER the option has been removed from the {@link $_options} property.
//	 * @uses     do_action() Calls '{$this->slug}_deleted_site_option' and passes the function(s)
//	 *               the deleted option name if the option was successfully deleted.
//	 *
//	 * @param    string $option The option to delete
//	 *
//	 * @return   bool            true if it was deleted, false if not or if it doesn't exist
//	 * @since    0.0.1
//	 * @access   public
//	 */
//	public function delete_site_option( $option ) {
//		if ( ! isset( $this->_site_options[ $option ] ) ) {
//			return false;
//		}
//
//		if ( ! is_multisite() ) {
//			return $this->delete_option( $option );
//		}
//
//		if ( is_array( $this->_default_site_options ) && isset( $this->_default_site_options[ $option ] ) ) {
//			$this->_deleted_site_options[] = $option;
//		}
//
//		do_action( $this->slug . '_delete_site_option_' . $option, $this->_site_options );
//
//		unset( $this->_site_options[ $option ] );
//
//		do_action( $this->slug . '_delete_site_option', $option, $this->_site_options );
//
//		$result = $this->_update_wp_options( false, true );
//
//		if ( $result ) {
//			do_action( $this->slug . '_deleted_site_option', $option );
//
//			return true;
//		}
//
//		return false;
//	}
//
//	/**
//	 * Update ALL Plugin/Theme Options
//	 *
//	 * This stores the current values for {@link $_options}, {@link $_site_options},
//	 * {@link $_deleted_options}, and {@link $_deleted_site_options} properties to the
//	 * single WordPress option for options and another single option for site options for
//	 * the Theme/Plugin.
//	 *
//	 * Since this class stores/retrieves all option values from only two WordPress options,
//	 * all of the options must be updated together and this does just that.
//	 *
//	 * @param    bool $local If the local/normal options need to be updated.
//	 * @param    bool $site  If the site/multisite options need to be updated.
//	 *
//	 * @return   bool            true on success, false if update failed
//	 * @since    0.0.1
//	 * @access   protected
//	 */
//	protected function _update_wp_option( $local = true, $site = false ) {
//		if ( ( $local && ! is_array( $this->_options ) ) || ( $site && ! is_array( $this->_site_options ) ) ) {
//			wp_die( 'The options couldn\'t be updated because the `$_options` and/or `$_site_options` ' .
//			        'properties aren\'t arrays!' );
//		}
//		// Update local options
//		if ( $local ) {
//			$options = array( 'options' => $this->_options, '_deleted_options' => $this->_deleted_options );
//			$result  = update_option( $this->slug . '_options', $options );
//
//			if ( ! is_multisite() ) {
//				return $result;
//			}
//		}
//		// Update multisite options
//		if ( $site ) {
//			$site_options = array(
//				'options'          => $this->_site_options,
//				'_deleted_options' => $this->_deleted_site_options,
//			);
//
//			return update_option( $this->slug . '_multisite_options', $site_options );
//		}
//		// If we're here, something went wrong!
//		trigger_error( 'Nothing to do!  Check how `_update_wp_option()` is being called.' );
//
//		return false;
//	}
//
//	/**
//	 * Validate Class Settings
//	 *
//	 * This is used to verify various required portions of this class have been correctly
//	 * defined/setup.
//	 *
//	 * @param    void
//	 *
//	 * @return   bool    true if they are valid, false if not
//	 * @since    0.0.1
//	 * @access   private
//	 */
//	private function _validate_settings() {
//		try {
//			if ( strlen( $this->slug ) < 1 ) {
//				throw new Exception( 'A valid slug must be defined for the $slug property!' );
//			}
//
//		} catch ( Exception $e ) {
//			$this->errors[] = $e->getMessage();
//
//			return false;
//		}
//	}
}

/**
 * WordPress Settings Class
 *
 * This class will help register settings for any
 * theme or plugin based on the WordPress Settings API.
 *
 * @author Julien Liabeuf
 *
 * TODO
 * - Clean from TAV constants
 * - Remove useless features
 */
class TAV_Settings {
	
	public function __construct( $init = array() ) {

		$this->settings   = array();
		$this->page       = $init['page'];
		$this->group      = $init['prefix'];
		$this->option     = isset( $init['name'] ) ? $init['name'] : 'tav_options';
		$this->menu_name  = isset( $init['menu_name'] ) ? $init['menu_name'] : 'Settings';
		$this->slug       = isset( $init['slug'] ) ? $init['slug'] : 'tav-settings';
		$this->parent     = isset( $init['parent'] ) ? $init['parent'] : false;
		$this->page_title = isset( $init['page_title'] ) ? $init['page_title'] : 'Settings';
		$this->icon       = isset( $init['icon'] ) ? $init['icon'] : 'icon-options-general';
		$this->capability = isset( $init['capability'] ) ? $init['capability'] : 'administrator';
		$this->callback   = isset( $init['callback'] ) ? $init['callback'] : 'settingsPage';

		add_action( 'admin_menu', array( $this, 'addMenuItems' ) );
		add_action( 'admin_init', array( $this, 'registerSettings' ), 10 );
		add_action( 'admin_init', array( $this, 'parseOptions' ), 11 );

	}

	/**
	 * Get the settings
	 * 
	 * @return array defined settings
	 */
	public function getSettings() {
		return $this->settings;
	}

	public function getOption( $opt = false, $default = false ) {

		if( !$opt )
			return $default;

		/* Get the serialized values */
		$options = get_option( $this->option, $default );

		if( $options && is_array( $options ) && !empty( $options ) ) {

			if( isset( $options[$opt] ) ) {
				return $options[$opt];
			} else {
				return $default;
			}

		} else {
			return $default;
		}

	}

	/**
	 * Add required menu items
	 */
	public function addMenuItems() {

		$callback = method_exists( $this, $this->callback ) ? array( $this, $this->callback ) : array( $this, 'settingsPage' );

		if( $this->parent ) {
			add_submenu_page( $this->parent, $this->page_title, $this->menu_name, $this->capability, $this->slug, $callback );
		} else {
			add_menu_page( $this->page_title, $this->menu_name, $this->capability, $this->slug, $callback );
		}
		
	}

	/**
	 * Register the global settings as we use
	 * only one row in the database for each options
	 * set (framework & theme).
	 */
	public function registerSettings() {

		register_setting( $this->page, $this->option );

	}

	/**
	 * Add a new tabbed section
	 * in the option pannel.
	 */
	public function addSection( $id = false, $group = false, $title = false  ) {

		if( !$id )
			return false;

		if( isset( $this->settings[$id] ) )
			return false;

		/* Declare the new section and its options */
		$section = array(
			'id' 		=> $id,
			'options' 	=> array()
		);

		/* Add the title if declared */
		if( $title ) {
			$section['title'] = $title;
		} else {
			$section['title'] = ucwords( $id );
		}

		if( $group )
			$section['group'] = $group;

		/* Add the new section */
		$this->settings[$id] = $section;

	}

	public function addOption( $section_id = false, $args = array() ) {

		/* We check if this option can be assigned to a metabox */
		if( !$section_id || !isset( $this->settings[$section_id] ) || empty( $args ) )
			return false;

		/* Check if the required information is set */
		if( !isset( $args['id'] ) || !isset( $args['title'] ) || !isset( $args['field'] ) )
			return false;

		$option = array(
			'id' 		=> $args['id'],
			'title' 	=> $args['title'],
			'field' 	=> $args['field']
		);

		isset( $args['desc'] ) ? $option['desc'] = $args['desc'] : false;				// Optional field description
		isset( $args['opts'] ) ? $option['opts'] = $args['opts'] : false;				// Available options for radio / checkbox / select
		isset( $args['validate'] ) ? $option['validate'] = $args['validate'] : false;	// Adds HTML5 validation pattern
		isset( $args['limit'] ) ? $option['limit'] = $args['limit'] : false;			// Limits field content lenght

		/* Add the new option */
		$this->settings[$section_id]['options'][] = $option;

	}

	public function parseOptions() {

		/* Iterate through the options */
		foreach( $this->settings as $section ) {
			
			/**
			 * Register a new section
			 */

			/* Define section ID */
			$section_id = $this->page;

			/* Define title */
			isset( $section['title'] ) ? $title = $section['title'] : $title = '';

			/* Add the section */
			add_settings_section( 'tav_' . $section['id'], $title, false, $this->page );

			/* Assign options to current section */
			foreach( $section['options'] as $key => $option ) {

				/* Check if we have a callback */
				if( !isset( $option['field'] ) )
					continue;

				/**
				 * Register the section's fields
				 */

				/* Checking for dependencies */
				if( isset( $option['dependency'] ) )
					$dep = tav_get_theme_options( $option['dependency'][0] );
				
				/* Displaying option */
				if( !isset( $option['dependency'] ) || isset( $option['dependency'] ) && $dep == $option['dependency'][1] ) {

					/* Preparing data for later validation */
					if( isset( $option['validate'] ) && '' != $option['validate'] ) {
						$this->to_validate[$option['id']] = $option['validate'];
					}

					/**
					 * We prepare the arguments
					 */
					$args 													= array();
					$args['id'] 											= $option['id'];				// Field ID
					$args['title'] 											= $option['title'];				// Field title
					$args['group'] 											= $this->group . '_options'; 	// Option group (used to identify how to retrieve the options)
					$args['type'] 											= $option['field'];
					if( isset( $option['desc'] ) ) 		$args['desc']		= $option['desc'];				// Field description
					if( isset( $option['opts'] ) ) 		$args['opts']		= $option['opts'];				// Options available (select, checkboxes, radio)
					if( isset( $option['validate'] ) ) 	$args['validate'] 	= $option['validate'];			// Validation type (for HTML5 patterns)
					if( isset( $option['std'] ) ) 		$args['std'] 		= $option['std'];				// Default option
					if( isset( $option['level'] ) ) 	$args['level'] 		= $option['level'];				// Level required to edit option

					/* We finally add the field */
					add_settings_field( $option['id'], $option['title'], array( $this, 'filedsCallbacks' ), $this->page, TAV_SHORTNAME . '_' . $section['id'], $args );
				}
			}
		}
	}

	/**
	 * Display the settings page
	 */
	public function settingsPage() {

		?>
		<div class="wrap">  
			<div class="icon32" id="<?php echo esc_attr( $this->icon ); ?>"></div>
			<h2><?php esc_html_e( 'WP Google Authenticator Settings', 'wpga' ); ?></h2>
			  
			<form action="options.php" method="post">
				<?php
				settings_fields( $this->page );
				do_settings_sections( $this->page ); 
				?>
				<p class="submit">  
					<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e( 'Save','wpga' ); ?>" />  
				</p>  
				  
			</form>  
		</div>
		<?php

	}

	public function filedsCallbacks( $field ) {

		$value = $this->getOption( $field['id'], '' );

		switch ( $field['type'] ):

			/**
			 * Markup for regular text fields
			 */
			case 'text': ?>

				<input type="text" id="<?php echo esc_attr( $field['id'] ); ?>" name="<?php echo esc_attr( $this->option . '[' . $field['id'] . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
				<?php if ( isset( $field['desc'] ) ) : ?><p class="description"><?php echo wp_kses( $field['desc'], array( 'code' => array() ) ); ?></p><?php endif;

			break;

			/**
			 * Markup for small text fields
			 */
			case 'smalltext': ?>

				<input type="text" id="<?php echo esc_attr( $field['id'] ); ?>" name="<?php echo esc_attr( $this->option . '[' . $field['id'] . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>" class="small-text" />
				<?php if ( isset( $field['desc'] ) ) : ?><p class="description"><?php echo wp_kses( $field['desc'], array( 'code' => array() ) ); ?></p><?php endif;

			break;

			/**
			 * Markup for checkboxes
			 */
			case 'checkbox':

				foreach( $field['opts'] as $val => $title ) :

					$checked = ( is_array( $value ) && in_array( $val, $value ) ) ? 'checked="checked"' : '';
					$id = $field['id'] . '_' . $val; ?>

					<label for="<?php echo $id; ?>">
						<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $this->option . '[' . $field['id'] . ']' ); ?>[]" value="<?php echo esc_attr( $val ); ?>" <?php echo $checked; ?>> <?php echo $title; ?>
					</label>

				<?php endforeach;

				if ( isset( $field['desc'] ) ) : ?><p class="description"><?php echo esc_html( $field['desc'] ); ?></p><?php endif;

			break;

			/**
			 * Markup for user roles.
			 *
			 * Gets all available roles for this install and create
			 * a checkbox list with it.
			 *
			 * @since  1.0.9
			 */
			case 'user_roles':

				$status         = $this->getOption( 'user_role_status', 'all' );
				$checked_all    = ( 'all' === $status ) ? 'checked="checked"' : '';
				$checked_custom = ( 'custom' === $status ) ? 'checked="checked"' : '';
				?>

				<div id="wpga-user-roles-noforce">
					<?php esc_html_e( 'You must enable the &laquo;Force Use&raquo; option above in order to select user roles.', 'wpga' ); ?>
				</div>

				<div id="wpga-user-roles">

					<div id="wpga-user-role-status" style="margin-bottom: 20px;">
						<label for="user_roles_all">
							<input type="radio" id="user_roles_all" name="wpga_options[user_role_status]" value="all" <?php echo $checked_all; ?>> <?php esc_html_e( 'All', 'wpga' ); ?>
						</label>
						<label for="user_roles_custom">
							<input type="radio" id="user_roles_custom" name="wpga_options[user_role_status]" value="custom" <?php echo $checked_custom; ?>> <?php esc_html_e( 'Custom', 'wpga' ); ?>
						</label>
					</div>

					<div id="wpga-all-roles">

						<?php foreach ( $field['opts'] as $val => $title ):

							$checked = ( is_array( $value ) && in_array( $val, $value ) ) ? 'checked="checked"' : '';
							$id = $field['id'] . '_' . $val; ?>

							<label for="<?php echo $id; ?>">
								<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $this->option . '[' . $field['id'] . ']' ); ?>[]" value="<?php echo esc_attr( $val ); ?>" <?php echo $checked; ?>> <?php echo esc_html( $title ); ?>
							</label><br>

						<?php endforeach; ?>

					</div>

					<?php if ( isset( $field['desc'] ) ) : ?><p class="description"><?php echo esc_html( $field['desc'] ); ?></p><?php endif; ?>

				</div>

			<?php break;

		endswitch; ?>

	<?php }

	public function get_editable_roles() {
		global $wp_roles;

		$all_roles = $wp_roles->roles;
		$editable_roles = apply_filters('editable_roles', $all_roles);

		return $editable_roles;
	}

}