<?php
class QodyWidget extends QodyOwnable
{
	var $m_our_widget = null;
	var $m_base_class_name;
	
	var $m_widget_options;
	var $m_control_options;
	
	var $m_widget_slug;
	var $m_widget_name;
	var $m_widget_description;
	
	function __construct()
	{
		parent::__construct();
		
		$this->m_base_class_name = 'base'.get_class( $this->Owner() );
		
		$fields = array();
		$fields['classname'] = 'example';
		$fields['description'] = $this->m_widget_description;
		$this->m_widget_options = $fields;
		
		$fields = array();
		$fields['width'] = 300;
		$fields['height'] = 350;
		$fields['id_base'] = 'example-widget';
		$this->m_control_options = $fields;
		
		add_action( 'widgets_init', array( $this, 'RegisterWithWordpress' ) );
		
		$this->m_our_widget = $this->CreateDynamicWidgetClass();
	}
	
	function SetSlug( $slug )
	{
		$this->m_widget_slug = $this->GetPre().'-'.$slug;
	}
	
	function SetName( $name )
	{
		$this->m_widget_name = $name;
	}
	
	function SetDescription( $text )
	{
		$this->m_widget_description = $text;
	}
	
	function RegisterWithWordpress()
	{
		register_widget( $this->m_base_class_name );
	}
	
	/**
	 * How to display the widget on the screen.
	 */
	function widget( $args, $instance )
	{
		extract( $args );

		/* Our variables from the widget settings. */
		$title = apply_filters('widget_title', $instance['title'] );
		$name = $instance['name'];
		$sex = $instance['sex'];
		$show_sex = isset( $instance['show_sex'] ) ? $instance['show_sex'] : false;

		/* Before widget (defined by themes). */
		echo $before_widget;

		/* Display the widget title if one was input (before and after defined by themes). */
		if ( $title )
			echo $before_title . $title . $after_title;

		/* Display name from widget settings if one was input. */
		if ( $name )
			printf( '<p>' . __('Hello. My name is %1$s.', 'example') . '</p>', $name );

		/* If show sex was selected, display the user's sex. */
		if ( $show_sex )
			printf( '<p>' . __('I am a %1$s.', 'example.') . '</p>', $sex );

		/* After widget (defined by themes). */
		echo $after_widget;
	}
	
	function update( $new_instance, $old_instance )
	{
		$instance = $old_instance;

		/* Strip tags for title and name to remove HTML (important for text inputs). */
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['name'] = strip_tags( $new_instance['name'] );

		/* No need to strip tags for sex and show_sex. */
		$instance['sex'] = $new_instance['sex'];
		$instance['show_sex'] = $new_instance['show_sex'];

		return $instance;
	}
	
	/**
	 * Displays the widget settings controls on the widget panel.
	 * Make use of the get_field_id() and get_field_name() function
	 * when creating your form elements. This handles the confusing stuff.
	 */
	function the_form( $instance )
	{
		/* Set up some default widget settings. */
		$defaults = array( 'title' => __('Example', 'example'), 'name' => __('John Doe', 'example'), 'sex' => 'male', 'show_sex' => true );
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<!-- Widget Title: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
		</p>

		<!-- Your Name: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'name' ); ?>"><?php _e('Your Name:', 'example'); ?></label>
			<input id="<?php echo $this->get_field_id( 'name' ); ?>" name="<?php echo $this->get_field_name( 'name' ); ?>" value="<?php echo $instance['name']; ?>" style="width:100%;" />
		</p>

		<!-- Sex: Select Box -->
		<p>
			<label for="<?php echo $this->get_field_id( 'sex' ); ?>"><?php _e('Sex:', 'example'); ?></label> 
			<select id="<?php echo $this->get_field_id( 'sex' ); ?>" name="<?php echo $this->get_field_name( 'sex' ); ?>" class="widefat" style="width:100%;">
				<option <?php if ( 'male' == $instance['format'] ) echo 'selected="selected"'; ?>>male</option>
				<option <?php if ( 'female' == $instance['format'] ) echo 'selected="selected"'; ?>>female</option>
			</select>
		</p>

		<!-- Show Sex? Checkbox -->
		<p>
			<input class="checkbox" type="checkbox" <?php checked( $instance['show_sex'], true ); ?> id="<?php echo $this->get_field_id( 'show_sex' ); ?>" name="<?php echo $this->get_field_name( 'show_sex' ); ?>" /> 
			<label for="<?php echo $this->get_field_id( 'show_sex' ); ?>"><?php _e('Display sex publicly?', 'example'); ?></label>
		</p>

	<?php
	}
	
	// this is probably a terrible idea, but is seemling like the only one available to be dynamic.
	// the source of this hack is the wordpress function register_widget must take a class name 
	// that extends the WP_Widget class
	function CreateDynamicWidgetClass()
	{
		// store $this in this since Wordpress handles creating instances of these sometimes
		global $custom_widget_owners;
		
		$custom_widget_owners[ $this->m_base_class_name ] = $this;
		
		$dynamic_definition = '	
		class '.$this->m_base_class_name.' extends WP_Widget
		{
			var $m_owner = null;
			
			function __construct()
			{
				global $custom_widget_owners;
				
				$this->m_owner = $custom_widget_owners["'.$this->m_base_class_name.'"];
				
				/* Create the widget. */
				$this->WP_Widget( "'.$this->m_widget_slug.'", "'.$this->m_widget_name.'", $this->Owner()->m_widget_options, $this->Owner()->m_control_options );
			}
			
			function Owner()
			{
				return $this->m_owner;
			}
			
			/**
			 * How to display the widget on the screen.
			 */
			function widget( $args, $instance )
			{
				return $this->Owner()->widget( $args, $instance );
			}
		
			/**
			 * Update the widget settings.
			 */
			function update( $new_instance, $old_instance )
			{
				return $this->Owner()->update( $new_instance, $old_instance );
			}
		
			/**
			 * Displays the widget settings controls on the widget panel.
			 * Make use of the get_field_id() and get_field_name() function
			 * when creating your form elements. This handles the confusing stuff.
			 */
			function form( $instance )
			{
				return $this->Owner()->the_form( $instance );
			}
		}';
		
		eval( $dynamic_definition );
		
		return new $this->m_base_class_name( $this );
	}
}
 ?>