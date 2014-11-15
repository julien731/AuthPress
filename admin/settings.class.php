<?php
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
	 * @return (array) defined settings
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
			<div class="icon32" id="<?php echo $this->icon; ?>"></div>  
			<h2><?php _e( 'WP Google Authenticator Settings', 'wpga' ); ?></h2>
			  
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

		switch( $field['type'] ):

			/**
			 * Markup for regular text fields
			 */
			case 'text': ?>

				<input type="text" id="<?php echo $field['id']; ?>" name="<?php echo $this->option . '[' . $field['id'] . ']'; ?>" value="<?php echo $value; ?>" class="regular-text" />
				<?php if( isset( $field['desc'] ) ): ?><p class="description"><?php echo $field['desc']; ?></p><?php endif;

			break;

			/**
			 * Markup for small text fields
			 */
			case 'smalltext': ?>

				<input type="text" id="<?php echo $field['id']; ?>" name="<?php echo $this->option . '[' . $field['id'] . ']'; ?>" value="<?php echo $value; ?>" class="small-text" />
				<?php if( isset( $field['desc'] ) ): ?><p class="description"><?php echo $field['desc']; ?></p><?php endif;

			break;

			/**
			 * Markup for checkboxes
			 */
			case 'checkbox':

				foreach( $field['opts'] as $val => $title ):

					$checked = ( is_array( $value ) && in_array( $val, $value ) ) ? 'checked="checked"' : '';
					$id = $field['id'] . '_' . $val; ?>

					<label for="<?php echo $id; ?>">
						<input type="checkbox" id="<?php echo $id; ?>" name="<?php echo $this->option . '[' . $field['id'] . ']'; ?>[]" value="<?php echo $val; ?>" <?php echo $checked; ?>> <?php echo $title; ?>
					</label>

				<?php endforeach;

				if( isset( $field['desc'] ) ): ?><p class="description"><?php echo $field['desc']; ?></p><?php endif;

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
					<?php _e( 'You must enable the &laquo;Force Use&raquo; option above in order to select user roles.', 'wpga' ); ?>
				</div>

				<div id="wpga-user-roles">

					<div id="wpga-user-role-status" style="margin-bottom: 20px;">
						<label for="user_roles_all">
							<input type="radio" id="user_roles_all" name="wpga_options[user_role_status]" value="all" <?php echo $checked_all; ?>> <?php _e( 'All', 'wpga' ); ?>
						</label>
						<label for="user_roles_custom">
							<input type="radio" id="user_roles_custom" name="wpga_options[user_role_status]" value="custom" <?php echo $checked_custom; ?>> <?php _e( 'Custom', 'wpga' ); ?>
						</label>
					</div>

					<div id="wpga-all-roles">

						<?php foreach ( $field['opts'] as $val => $title ):

							$checked = ( is_array( $value ) && in_array( $val, $value ) ) ? 'checked="checked"' : '';
							$id = $field['id'] . '_' . $val; ?>

							<label for="<?php echo $id; ?>">
								<input type="checkbox" id="<?php echo $id; ?>" name="<?php echo $this->option . '[' . $field['id'] . ']'; ?>[]" value="<?php echo $val; ?>" <?php echo $checked; ?>> <?php echo $title; ?>
							</label><br>

						<?php endforeach; ?>

					</div>

					<?php if( isset( $field['desc'] ) ): ?><p class="description"><?php echo $field['desc']; ?></p><?php endif; ?>

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