<?php

// exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;


// check if class already exists
if( !class_exists('acf_field_image_mapping') ) :


class acf_field_image_mapping extends acf_field {


	public $settings;

	/*
	*  __construct
	*
	*  This function will setup the field type data
	*
	*  @type	function
	*  @date	5/03/2014
	*  @since	5.0.0
	*
	*  @param	n/a
	*  @return	n/a
	*/

	function __construct( array $settings ) {

		/*
		*  name (string) Single word, no spaces. Underscores allowed
		*/

		$this->name = 'image_mapping';


		/*
		*  label (string) Multiple words, can include spaces, visible when selecting a field type
		*/

		$this->label = __('Image Mapping', 'acf-image_mapping');


		/*
		*  category (string) basic | content | choice | relational | jquery | layout | CUSTOM GROUP NAME
		*/

		$this->category = 'basic';


		/*
		*  defaults (array) Array of default settings which are merged into the field object. These are used later in settings
		*/

		$this->defaults = array(
			'font_size'	=> 14,
			'x'	=> 0,
			'y'	=> 0,
		);


		/*
		*  l10n (array) Array of strings that are used in JavaScript. This allows JS strings to be translated in PHP and loaded via:
		*  var message = acf._e('image_mapping', 'error');
		*/

		$this->l10n = array(
			'error'	=> __('Error! Please enter click to create a coordinate pair', 'acf-image_mapping'),
		);


		/*
		*  settings (array) Store plugin settings (url, path, version) as a reference for later use with assets
		*/

		$this->settings = $settings;


		// do not delete!
    	parent::__construct();

	}


	/*
	*  render_field_settings()
	*
	*  Create extra settings for your field. These are visible when editing a field
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$field (array) the $field being edited
	*  @return	n/a
	*/

	function render_field_settings( $field ) {

		/*
		*  acf_render_field_setting
		*
		*  This function will create a setting for your field. Simply pass the $field parameter and an array of field settings.
		*  The array of settings does not require a `value` or `prefix`; These settings are found from the $field array.
		*
		*  More than one setting can be added by copy/paste the above code.
		*  Please note that you must also have a matching $defaults value for the field name (font_size)
		*/

        acf_render_field_setting( $field, array(
            'label'			=> __( 'Source Type', 'acf-image_mapping' ),
            'instructions'	=> __( 'Choose where the image is sourced from - "Field" lets you specify an ACF field name, "Function" is a PHP function that returns the image URL, Attachment is to use the chosen image when "location" is set to "attachment"' ),
            'type'			=> 'button_group',
            'name'			=> 'source_type',
            'choices'       => [
                'field' => __('Field', 'acf-image-mapping' ),
                'function' => __('Function', 'acf-image-mapping' ),
                'attachment' => __('Attachment', 'acf-image-mapping' ),
            ]
        ));

		acf_render_field_setting( $field, [
			'label'			=> __( 'Image Field Label', 'acf-image_mapping' ),
			'instructions'	=> __( 'Field label of image field to link to', 'acf-image_mapping' ),
			'placeholder'   => __( 'acf_image_field_name', 'acf-image_mapping' ),
			'type'			=> 'text',
			'name'			=> 'image_field_label',
			'required'      => true,
			'conditional_logic' => [
				[
					[ 'field' => 'source_type', 'operator' => '==', 'value' => 'field' ],
				]
			],
		] );

		acf_render_field_setting( $field, [
			'label'			=> __( 'Function Name', 'acf-image_mapping' ),
			'instructions'	=> __( 'Function name to call (function must return image URL), no ()' , 'acf-image_mapping' ),
			'placeholder'   => __( 'my_custom_image_function', 'acf-image_mapping' ),
			'type'			=> 'text',
			'name'			=> 'image_field_function',
			'required'      => true,
			'conditional_logic' => [
				[
					[ 'field' => 'source_type', 'operator' => '==', 'value' => 'function' ],
				]
			],
		] );

		acf_render_field_setting( $field, array(
			'label'			=> __( 'Percentage Based Coordinates', 'acf-image_mapping' ),
			'instructions'	=> __( 'Convert the coordinate pair to percentages instead of the raw X / Y pair', 'acf-image_mapping' ),
			'type'			=> 'true_false',
			'name'			=> 'percent_based',
		));

	}



	/*
	*  render_field()
	*
	*  Create the HTML interface for your field
	*
	*  @param	$field (array) the $field being rendered
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$field (array) the $field being edited
	*  @return	n/a
	*/

	function render_field( $field ) {


			/* Stock ACF fields, if these aren't set then the world has ended */
            $field_name    = esc_attr( $field['name'] );
			$field_value   = esc_attr( $field['value'] );

			/* Our custom ACF field settings, use isset to ensure backwards compatibilty as new fields are added */
            $source_type   = isset( $field['source_type'] ) ? esc_attr( $field['source_type'] ) : '';
            $img_label     = isset( $field['image_field_label'] ) ? esc_attr( $field['image_field_label'] ) : '' ;
            $img_function  = isset( $field['image_field_function'] ) ? esc_attr( $field['image_field_function'] ) : '';
            $percent_based = isset( $field['percent_based'] ) && $field['percent_based'] ? 1 : 0;
			
			/* Computed values / other vars */
			$xy_pair       = explode( ',', $field_value );
            $url = '';

            if ( $source_type === 'function' && function_exists( $img_function ) ){
                $url = esc_attr( $img_function() );
            }

		if ( 1 < count( $xy_pair ) ) {
			$x = $xy_pair[0];
			$y = $xy_pair[1];
		} else {
			$x = 0;
			$y = 0;
		}

        $out = '<!-- Image where we will catch the user\'s clicks -->';
        $out .= "<div class='$this->name-image'>";

        if ( $source_type === 'attachment' ){
            $out .=	"<img src='' data-percent-based='$percent_based' data-source-type='$source_type'/>";
        } elseif ( $source_type === 'function' ){
            $out .=	"<img src='' data-percent-based='$percent_based' data-source-type='$source_type' data-url='$url' />";
        } else {
            $out .=	"<img src='' data-percent-based='$percent_based' data-source-type='$source_type' data-label='$img_label' />";
        }
        $out .=	"<span style='left:$x;top:$y;'></span>";
        $out .= '</div>';

        $out .= "<!-- XY Coordinate Pair -->";
        $out .= "<input class='$this->name-input' type='text' name='$field_name' value='$field_value' />";

        echo $out;
	}


	/*
	*  input_admin_enqueue_scripts()
	*
	*  This action is called in the admin_enqueue_scripts action on the edit screen where your field is created.
	*  Use this action to add CSS + JavaScript to assist your render_field() action.
	*
	*  @type	action (admin_enqueue_scripts)
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	n/a
	*  @return	n/a
	*/



	function input_admin_enqueue_scripts() {

		// vars
		$url = $this->settings['url'];
		$version = $this->settings['version'];


		// register & include JS
		// wp_register_script( 'acf-input-image_mapping', "{$url}assets/js/input.js", array('acf-input'), $version );
		wp_register_script( 'acf-input-image_mapping', "{$url}assets/js/input.js", array('acf-input'), null );
		wp_enqueue_script('acf-input-image_mapping');


		// register & include CSS
		wp_register_style( 'acf-input-image_mapping', "{$url}assets/css/input.css", array('acf-input'), $version );
		wp_enqueue_style('acf-input-image_mapping');

	}




	/*
	*  input_admin_head()
	*
	*  This action is called in the admin_head action on the edit screen where your field is created.
	*  Use this action to add CSS and JavaScript to assist your render_field() action.
	*
	*  @type	action (admin_head)
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	n/a
	*  @return	n/a
	*/

	/*

	function input_admin_head() {



	}

	*/


	/*
   	*  input_form_data()
   	*
   	*  This function is called once on the 'input' page between the head and footer
   	*  There are 2 situations where ACF did not load during the 'acf/input_admin_enqueue_scripts' and
   	*  'acf/input_admin_head' actions because ACF did not know it was going to be used. These situations are
   	*  seen on comments / user edit forms on the front end. This function will always be called, and includes
   	*  $args that related to the current screen such as $args['post_id']
   	*
   	*  @type	function
   	*  @date	6/03/2014
   	*  @since	5.0.0
   	*
   	*  @param	$args (array)
   	*  @return	n/a
   	*/

   	/*

   	function input_form_data( $args ) {



   	}

   	*/


	/*
	*  input_admin_footer()
	*
	*  This action is called in the admin_footer action on the edit screen where your field is created.
	*  Use this action to add CSS and JavaScript to assist your render_field() action.
	*
	*  @type	action (admin_footer)
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	n/a
	*  @return	n/a
	*/

	/*

	function input_admin_footer() {



	}

	*/


	/*
	*  field_group_admin_enqueue_scripts()
	*
	*  This action is called in the admin_enqueue_scripts action on the edit screen where your field is edited.
	*  Use this action to add CSS + JavaScript to assist your render_field_options() action.
	*
	*  @type	action (admin_enqueue_scripts)
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	n/a
	*  @return	n/a
	*/

	/*

	function field_group_admin_enqueue_scripts() {

	}

	*/


	/*
	*  field_group_admin_head()
	*
	*  This action is called in the admin_head action on the edit screen where your field is edited.
	*  Use this action to add CSS and JavaScript to assist your render_field_options() action.
	*
	*  @type	action (admin_head)
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	n/a
	*  @return	n/a
	*/

	/*

	function field_group_admin_head() {

	}

	*/


	/*
	*  load_value()
	*
	*  This filter is applied to the $value after it is loaded from the db
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value (mixed) the value found in the database
	*  @param	$post_id (mixed) the $post_id from which the value was loaded
	*  @param	$field (array) the field array holding all the field options
	*  @return	$value
	*/

	/*

	function load_value( $value, $post_id, $field ) {

		return $value;

	}

	*/


	/*
	*  update_value()
	*
	*  This filter is applied to the $value before it is saved in the db
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value (mixed) the value found in the database
	*  @param	$post_id (mixed) the $post_id from which the value was loaded
	*  @param	$field (array) the field array holding all the field options
	*  @return	$value
	*/

	/*

	function update_value( $value, $post_id, $field ) {

		return $value;

	}

	*/


	/*
	*  format_value()
	*
	*  This filter is appied to the $value after it is loaded from the db and before it is returned to the template
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value (mixed) the value which was loaded from the database
	*  @param	$post_id (mixed) the $post_id from which the value was loaded
	*  @param	$field (array) the field array holding all the field options
	*
	*  @return	$value (mixed) the modified value
	*/

	/*

	function format_value( $value, $post_id, $field ) {

		// bail early if no value
		if( empty($value) ) {

			return $value;

		}


		// apply setting
		if( $field['font_size'] > 12 ) {

			// format the value
			// $value = 'something';

		}


		// return
		return $value;
	}

	*/


	/*
	*  validate_value()
	*
	*  This filter is used to perform validation on the value prior to saving.
	*  All values are validated regardless of the field's required setting. This allows you to validate and return
	*  messages to the user if the value is not correct
	*
	*  @type	filter
	*  @date	11/02/2014
	*  @since	5.0.0
	*
	*  @param	$valid (boolean) validation status based on the value and the field's required setting
	*  @param	$value (mixed) the $_POST value
	*  @param	$field (array) the field array holding all the field options
	*  @param	$input (string) the corresponding input name for $_POST value
	*  @return	$valid
	*/

	/*

	function validate_value( $valid, $value, $field, $input ){

		// Basic usage
		if( $value < $field['custom_minimum_setting'] )
		{
			$valid = false;
		}


		// Advanced usage
		if( $value < $field['custom_minimum_setting'] )
		{
			$valid = __('The value is too little!','acf-image_mapping'),
		}


		// return
		return $valid;

	}

	*/


	/*
	*  delete_value()
	*
	*  This action is fired after a value has been deleted from the db.
	*  Please note that saving a blank value is treated as an update, not a delete
	*
	*  @type	action
	*  @date	6/03/2014
	*  @since	5.0.0
	*
	*  @param	$post_id (mixed) the $post_id from which the value was deleted
	*  @param	$key (string) the $meta_key which the value was deleted
	*  @return	n/a
	*/

	/*

	function delete_value( $post_id, $key ) {



	}

	*/


	/*
	*  load_field()
	*
	*  This filter is applied to the $field after it is loaded from the database
	*
	*  @type	filter
	*  @date	23/01/2013
	*  @since	3.6.0
	*
	*  @param	$field (array) the field array holding all the field options
	*  @return	$field
	*/

	/*

	function load_field( $field ) {

		return $field;

	}

	*/


	/*
	*  update_field()
	*
	*  This filter is applied to the $field before it is saved to the database
	*
	*  @type	filter
	*  @date	23/01/2013
	*  @since	3.6.0
	*
	*  @param	$field (array) the field array holding all the field options
	*  @return	$field
	*/

	/*

	function update_field( $field ) {

		return $field;

	}

	*/


	/*
	*  delete_field()
	*
	*  This action is fired after a field is deleted from the database
	*
	*  @type	action
	*  @date	11/02/2014
	*  @since	5.0.0
	*
	*  @param	$field (array) the field array holding all the field options
	*  @return	n/a
	*/

	/*

	function delete_field( $field ) {



	}

	*/


}


// initialize
new acf_field_image_mapping( $this->settings );


// class_exists check
endif;

?>
