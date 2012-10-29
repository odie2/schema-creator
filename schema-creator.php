<?php
/*
Plugin Name: Schema Creator by Raven
Plugin URI: http://schema-creator.org/?utm_source=wp&utm_medium=plugin&utm_campaign=schema
Description: Insert schema.org microdata into posts and pages
Version: 1.032
Author: Raven Internet Marketing Tools
Author URI: http://raventools.com/?utm_source=wp&utm_medium=plugin&utm_campaign=schema
License: GPL v2

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA


	Resources

	http://schema-creator.org/
	http://foolip.org/microdatajs/live/
	http://www.google.com/webmasters/tools/richsnippets
	
*/


class ravenSchema
{

	/**
	 * This is our constructor
	 *
	 * @return ravenSchema
	 */
	public function __construct() {
		add_action					( 'admin_menu',				array( $this, 'schema_settings'		)			);
		add_action					( 'admin_init', 			array( $this, 'reg_settings'		)			);
		add_action					( 'admin_enqueue_scripts',	array( $this, 'admin_scripts'		)			);
		add_action					( 'admin_footer',			array( $this, 'schema_form'			)			);
		add_action					( 'the_posts', 				array( $this, 'schema_loader'		)			);
		add_action					( 'do_meta_boxes',			array( $this, 'metabox_schema'		), 10,	2	);
		add_action					( 'save_post',				array( $this, 'save_metabox'		)			);
		
		add_filter					( 'body_class',             array( $this, 'body_class'			)			);
		add_filter					( 'media_buttons_context',	array( $this, 'media_button'		)			);
		add_filter					( 'the_content',			array( $this, 'schema_wrapper'		)			);
		add_filter					( 'admin_footer_text',		array( $this, 'schema_footer'		)			);
		add_shortcode				( 'schema',					array( $this, 'shortcode'			)			);
		register_activation_hook	( __FILE__, 				array( $this, 'store_settings'		)			);
		// i18n Support
		load_plugin_textdomain('schema-creator',false,dirname( plugin_basename( __FILE__ ) ).'/languages/');
	}

	/**
	 * display metabox
	 *
	 * @return ravenSchema
	 */

	public function metabox_schema( $page, $context ) {

		// check to see if they have options first
		$schema_options	= get_option('schema_options');

		// they haven't enabled this? THEN YOU LEAVE NOW
		if(empty($schema_options['body']) && empty($schema_options['post']) )
			return;

		$types	= array('post' => 'post');	
    	
		if ( in_array( $page,  $types ) && 'side' == $context )
		add_meta_box('schema-post-box', __('Schema Display Options'), array(&$this, 'schema_post_box'), $page, $context, 'high');
		

	}

	/**
	 * Display checkboxes for disabling the itemprop and itemscope
	 *
	 * @return ravenSchema
	 */

	public function schema_post_box() {
	
		global $post;
		$disable_body	= get_post_meta($post->ID, '_schema_disable_body', true);
		$disable_post	= get_post_meta($post->ID, '_schema_disable_post', true);
		
		// use nonce for security
		wp_nonce_field( plugin_basename( __FILE__ ), 'schema_nonce' );

		echo '<p class="schema-post-option">';
		echo '<input type="checkbox" name="schema_disable_body" id="schema_disable_body" value="true" '.checked($disable_body, 'true', false).'>';
		echo '<label for="schema_disable_body">' . __('Disable body itemscopes on this post.', 'schema-creator') . '</label>';
		echo '</p>';

		echo '<p class="schema-post-option">';
		echo '<input type="checkbox" name="schema_disable_post" id="schema_disable_post" value="true" '.checked($disable_post, 'true', false).'>';
		echo '<label for="schema_disable_post">' . __('Disable content itemscopes on this post.', 'schema-creator') . '</label>';
		echo '</p>';

	}

	/**
	 * save the data
	 *
	 * @return ravenSchema
	 */


	public function save_metabox($post_id) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return;

		if ( !wp_verify_nonce( $_POST['schema_nonce'], plugin_basename( __FILE__ ) ) )
			return;

		if ( !current_user_can( 'edit_post', $post_id ) )
			return;

		// OK, we're authenticated: we need to find and save the data

		$disable_body = $_POST['schema_disable_body'];
		$disable_post = $_POST['schema_disable_post'];

		$db_check	= isset($disable_body) ? 'true' : 'false';
		$dp_check	= isset($disable_post) ? 'true' : 'false';
		
		update_post_meta($post_id, '_schema_disable_body', $db_check);
		update_post_meta($post_id, '_schema_disable_post', $dp_check);

	}

	/**
	 * build out settings page
	 *
	 * @return ravenSchema
	 */


	public function schema_settings() {
	    add_submenu_page('options-general.php', 'Schema Creator', 'Schema Creator', 'manage_options', 'schema-creator', array( $this, 'schema_creator_display' ));
	}

	/**
	 * Register settings
	 *
	 * @return ravenSchema
	 */


	public function reg_settings() {
		register_setting( 'schema_options', 'schema_options');		

	}

	/**
	 * Store settings
	 * 
	 *
	 * @return ravenSchema
	 */


	public function store_settings() {
		
		// check to see if they have options first
		$options_check	= get_option('schema_options');

		// already have options? LEAVE THEM ALONE SIR		
		if(!empty($options_check))
			return;

		// got nothin? well then, shall we?
		$schema_options['css']	= 'false';
		$schema_options['body']	= 'true';
		$schema_options['post']	= 'true';

		update_option('schema_options', $schema_options);

	}

	/**
	 * Content for pop-up tooltips
	 *
	 * @return ravenSchema
	 */

	private $tooltip = array (
		"default_css"	=> "<h5 style='font-size:16px;margin:0 0 5px;text-align:right;'>Including CSS</h5><p style='font-size:13px;line-height:16px;margin:0 0 5px;'>Check to remove Schema Creator CSS from the microdata HTML output.</p>",
		"body_class"	=> "<h5 style='font-size:16px;margin:0 0 5px;text-align:right;'>Schema Body Tag</h5><p style='font-size:13px;line-height:16px;margin:0 0 5px;'>Check to add the <a href='http://schema.org/Blog' target='_blank'>http://schema.org/Blog</a> schema itemtype to the BODY element on your pages and posts. Your theme must have the body_class template tag for this to work.</p>",
		"post_class"	=> "<h5 style='font-size:16px;margin:0 0 5px;text-align:right;'>Schema Post Wrapper</h5><p style='font-size:13px;line-height:16px;margin:0 0 5px;'>Check to add the <a href='http://schema.org/BlogPosting' target='_blank'>http://schema.org/BlogPosting</a> schema itemtype to the content wrapper on your pages and posts.</p>",
		"pending_tip"	=> "<h5 style='font-size:16px;margin:0 0 5px;text-align:right;'>Pending</h5><p style='font-size:13px;line-height:16px;margin:0 0 5px;'>This fancy little box will have helpful information in it soon.</p>",
		// end tooltip content
	);

	/**
	 * Display main options page structure
	 *
	 * @return ravenSchema
	 */
	 
	public function schema_creator_display() { 
		
		if (!current_user_can('manage_options') )
			return;
		?>
	
		<div class="wrap">
    	<div class="icon32" id="icon-schema"><br></div>
		<h2><?php _e('Schema Creator Settings', 'schema-creator'); ?></h2>
	        <div class="schema_options">
            	<div class="schema_form_text">
            	<p><?php _e('Schema Creator Settings', 'schema-creator'); ?>

								<p><?php _e('By default, the', 'schema-creator'); ?>
								 <a href="http://schema-creator.org/?utm_source=wp&utm_medium=plugin&utm_campaign=schema" target="_blank">
								 </a><?php _e('Schema Creator', 'schema-creator'); ?></a>
								 <?php _e('plugin by', 'schema-creator'); ?>
								 <a href="http://raventools.com/?utm_source=wp&utm_medium=plugin&utm_campaign=schema" target="_blank">Raven Internet Marketing Tools</a> 
								 <?php _e('includes unique CSS IDs and classes. You can reference the CSS to control the style of the HTML that the Schema Creator plugin outputs.', 'schema-creator'); ?>
								 </p>
            	<p><?php _e('The plugin can also automatically include', 'schema-creator'); ?> <a href="http://schema.org/Blog" target="_blank">http://schema.org/Blog</a> <?php _e('and', 'schema-creator'); ?>
            	 <a href="http://schema.org/BlogPosting" target="_blank">http://schema.org/BlogPosting</a> 
            	 <?php _e('schemas to your pages and posts', 'schema-creator'); ?>.</p>
				<p><?php _e('Google also offers a', 'schema-creator'); ?> <a href="http://www.google.com/webmasters/tools/richsnippets/" target="_blank">
					<?php _e('Rich Snippet Testing tool', 'schema-creator'); ?>
					</a> <?php _e('to review and test the schemas in your pages and posts', 'schema-creator'); ?>.</p>
                </div>
                
                <div class="schema_form_options">
	            <form method="post" action="options.php">
			    <?php
                settings_fields( 'schema_options' );
				$schema_options	= get_option('schema_options');

				$css_hide	= (isset($schema_options['css']) && $schema_options['css'] == 'true' ? 'checked="checked"' : '');
				$body_tag	= (isset($schema_options['body']) && $schema_options['body'] == 'true' ? 'checked="checked"' : '');
				$post_tag	= (isset($schema_options['post']) && $schema_options['post'] == 'true' ? 'checked="checked"' : '');								
				?>
        
				<p>
                <label for="schema_options[css]"><input type="checkbox" id="schema_css" name="schema_options[css]" class="schema_checkbox" value="true" <?php echo $css_hide; ?>/><?php 
                _e('Exclude default CSS for schema output', 'schema-creator'); ?></label>
                <span class="ap_tooltip" tooltip="<?php echo $this->tooltip['default_css']; ?>">(?)</span>
                </p>
				<p>
                <label for="schema_options[body]"><input type="checkbox" id="schema_body" name="schema_options[body]" class="schema_checkbox" value="true" <?php echo $body_tag; ?> /> <?php _e('Apply itemprop &amp; itemtype to main body tag', 'schema-creator'); ?></label>
                <span class="ap_tooltip" tooltip="<?php echo $this->tooltip['body_class']; ?>">(?)</span>
                </p>

				<p>
                <label for="schema_options[post]"><input type="checkbox" id="schema_post" name="schema_options[post]" class="schema_checkbox" value="true" <?php echo $post_tag; ?> /><?php _e('Apply itemscope &amp; itemtype to content wrapper', 'schema-creator'); ?></label>
                <span class="ap_tooltip" tooltip="<?php echo $this->tooltip['post_class']; ?>">(?)</span>
                </p>                
    
	    		<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" /></p>
				</form>
                </div>
    
            </div>

        </div>    

	
	<?php }
		

	/**
	 * load scripts and style for admin settings page
	 *
	 * @return ravenSchema
	 */


	public function admin_scripts($hook) {
		// for post editor
		if ( $hook == 'post-new.php' || $hook == 'post.php' ) :
			wp_enqueue_style( 'schema-admin', plugins_url('/lib/css/schema-admin.css', __FILE__) );
			
			wp_enqueue_script( 'jquery-ui-core');
			wp_enqueue_script( 'jquery-ui-datepicker');
			wp_enqueue_script( 'jquery-ui-slider');
			wp_enqueue_script( 'jquery-timepicker', plugins_url('/lib/js/jquery.timepicker.js', __FILE__) , array('jquery'), null, true );
			wp_enqueue_script( 'format-currency', plugins_url('/lib/js/jquery.currency.min.js', __FILE__) , array('jquery'), null, true );
			wp_enqueue_script( 'schema-form', plugins_url('/lib/js/schema.form.init.js', __FILE__) , array('jquery'), null, true );
		endif;

		// for admin settings screen
		$current_screen = get_current_screen();
		if ( 'settings_page_schema-creator' == $current_screen->base ) :
			wp_enqueue_style( 'schema-admin', plugins_url('/lib/css/schema-admin.css', __FILE__) );
			
			wp_enqueue_script( 'jquery-qtip', plugins_url('/lib/js/jquery.qtip.min.js', __FILE__) , array('jquery'), null, true );			
			wp_enqueue_script( 'schema-admin', plugins_url('/lib/js/schema.admin.init.js', __FILE__) , array('jquery'), null, true );
		endif;
	}


	/**
	 * add attribution link to settings page
	 *
	 * @return ravenSchema
	 */

	public function schema_footer($text) {
		$current_screen = get_current_screen();
		if ( 'settings_page_schema-creator' == $current_screen->base )
			$text = '<span id="footer-thankyou">This plugin brought to you by the fine folks at <a title="Internet Marketing Tools for SEO and Social Media" target="_blank" href="http://raventools.com/?utm_source=wp&utm_medium=plugin&utm_campaign=schema">Raven Internet Marketing Tools</a>.</span>';

		if ( 'settings_page_schema-creator' !== $current_screen->base )
			$text = '<span id="footer-thankyou">Thank you for creating with <a href="http://wordpress.org/">WordPress</a>.</span>';

		return $text;
	}

	/**
	 * load body classes
	 *
	 * @return ravenSchema
	 */


	public function body_class( $classes ) {

		$schema_options = get_option('schema_options');

		$bodytag = isset($schema_options['body']) && $schema_options['body'] == 'true' ? true : false;

		// user disabled the tag. so bail.
		if($bodytag === false )
			return $classes;

		// check for single post disable
		global $post;
		$disable_body	= get_post_meta($post->ID, '_schema_disable_body', true);

		if($disable_body == 'true' )
			return $classes;

		$backtrace = debug_backtrace();
		if ( $backtrace[4]['function'] === 'body_class' )
			echo 'itemtype="http://schema.org/Blog" ';
			echo 'itemscope="" ';
		
		return $classes;
	}

	/**
	 * load front-end CSS if shortcode is present
	 *
	 * @return ravenSchema
	 */


	public function schema_loader($posts) {

		// no posts present. nothing more to do here
		if ( empty($posts) )
			return $posts;		

		// they said they didn't want the CSS. their loss.
		$schema_options = get_option('schema_options');

		if(isset($schema_options['css']) && $schema_options['css'] == 'true' )
			return $posts;		

		
		// false because we have to search through the posts first
		$found = false;
		 
		// search through each post
		foreach ($posts as $post) {
			$meta_check	= get_post_meta($post->ID, '_raven_schema_load', true);
			// check the post content for the short code
			$content	= $post->post_content;
			if ( preg_match('/schema(.*)/', $content) )
				// we have found a post with the short code
				$found = true;
				// stop the search
				break;
			}
		 
			if ($found == true )
				wp_enqueue_style( 'schema-style', plugins_url('/lib/css/schema-style.css', __FILE__) );
		
			if (empty($meta_check) && $found == true )
				update_post_meta($post->ID, '_raven_schema_load', 'true');

			if ($found == false )
				delete_post_meta($post->ID, '_raven_schema_load');

			return $posts;
		}

	/**
	 * wrap content in markup
	 *
	 * @return ravenSchema
	 */

	public function schema_wrapper($content) {

		$schema_options = get_option('schema_options');

		$wrapper = isset($schema_options['post']) && $schema_options['post'] == 'true' ? true : false;
		
		// user disabled content wrapper. just return the content as usual
		if ($wrapper === false)
			return $content;

		// check for single post disable
		global $post;
		$disable_post	= get_post_meta($post->ID, '_schema_disable_post', true);

		if($disable_post == 'true' )
			return $content;
		
		// updated content filter to wrap the itemscope
        $content = '<div itemscope itemtype="http://schema.org/BlogPosting">'.$content.'</div>';
		
    // Returns the content.
    return $content;		
		
	}


	/**
	 * Build out shortcode with variable array of options
	 *
	 * @return ravenSchema
	 */

	public function shortcode( $atts, $content = null ) {
		extract( shortcode_atts( array(
			'type'				=> '',
			'evtype'			=> '',
			'orgtype'			=> '',
			'name'				=> '',
			'orgname'			=> '',
			'jobtitle'			=> '',
			'url'				=> '',
			'description'		=> '',
			'bday'				=> '',
			'street'			=> '',
			'pobox'				=> '',
			'city'				=> '',
			'state'				=> '',
			'postalcode'		=> '',
			'country'			=> '',
			'email'				=> '',		
			'phone'				=> '',
			'fax'				=> '',
			'brand'				=> '',
			'manfu'				=> '',
			'model'				=> '',
			'single_rating'		=> '',
			'agg_rating'		=> '',
			'prod_id'			=> '',
			'price'				=> '',
			'condition'			=> '',
			'sdate'				=> '',
			'stime'				=> '',
			'edate'				=> '',
			'duration'			=> '',
			'director'			=> '',
			'producer'			=> '',		
			'actor_1'			=> '',
			'author'			=> '',
			'publisher'			=> '',
			'pubdate'			=> '',
			'edition'			=> '',
			'isbn'				=> '',
			'ebook'				=> '',
			'paperback'			=> '',
			'hardcover'			=> '',
			'rev_name'			=> '',
			'rev_body'			=> '',
			'user_review'		=> '',
			'min_review'		=> '',
			'max_review'		=> '',

			
		), $atts ) );
		
		// create array of actor fields	
		$actors = array();
		foreach ( $atts as $key => $value ) {
			if ( strpos( $key , 'actor' ) === 0 )
				$actors[] = $value;
		}

		// wrap schema build out
		$sc_build = '<div id="schema_block" class="schema_'.$type.'">';
		
		// person 
		if(isset($type) && $type == 'person') {
		
		$sc_build .= '<div itemscope itemtype="http://schema.org/Person">';
		
			if(!empty($name) && !empty($url) ) {
				$sc_build .= '<a class="schema_url" target="_blank" itemprop="url" href="'.esc_url($url).'">';
				$sc_build .= '<div class="schema_name" itemprop="name">'.$name.'</div>';
				$sc_build .= '</a>';
			}

			if(!empty($name) && empty($url) )
				$sc_build .= '<div class="schema_name" itemprop="name">'.$name.'</div>';

			if(!empty($orgname)) {
				$sc_build .= '<div itemscope itemtype="http://schema.org/Organization">';
				$sc_build .= '<span class="schema_orgname" itemprop="name">'.$orgname.'</span>';
				$sc_build .= '</div>';
			}
			
			if(!empty($jobtitle))
				$sc_build .= '<div class="schema_jobtitle" itemprop="jobtitle">'.$jobtitle.'</div>';

			if(!empty($description))
				$sc_build .= '<div class="schema_description" itemprop="description">'.esc_attr($description).'</div>';

			if(	!empty($street) ||
				!empty($pobox) ||
				!empty($city) ||
				!empty($state) ||
				!empty($postalcode) ||
				!empty($country)
				)
				$sc_build .= '<div itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">';

			if(!empty($street))
				$sc_build .= '<div class="street" itemprop="streetAddress">'.$street.'</div>';
			
			if(!empty($pobox))
				$sc_build .= '<div class="pobox">P.O. Box: <span itemprop="postOfficeBoxNumber">'.$pobox.'</span></div>';

			if(!empty($city) && !empty($state)) {
				$sc_build .= '<div class="city_state">';
				$sc_build .= '<span class="locale" itemprop="addressLocality">'.$city.'</span>,';
				$sc_build .= '<span class="region" itemprop="addressRegion">'.$state.'</span>';
				$sc_build .= '</div>';
			}

				// secondary check if one part of city / state is missing to keep markup consistent
				if(empty($state) && !empty($city) )
					$sc_build .= '<div class="city_state"><span class="locale" itemprop="addressLocality">'.$city.'</span></div>';
					
				if(empty($city) && !empty($state) )
					$sc_build .= '<div class="city_state"><span class="region" itemprop="addressRegion">'.$state.'</span></div>';

			if(!empty($postalcode))
				$sc_build .= '<div class="postalcode" itemprop="postalCode">'.$postalcode.'</div>';

			if(!empty($country))
				$sc_build .= '<div class="country" itemprop="addressCountry">'.$country.'</div>';

			if(	!empty($street) ||
				!empty($pobox) ||
				!empty($city) ||
				!empty($state) ||
				!empty($postalcode) ||
				!empty($country)
				)
				$sc_build .= '</div>';

			if(!empty($email))
				$sc_build .= '<div class="email" itemprop="email">' . antispambot( $email ) .'</div>';

			if(!empty($phone))
				$sc_build .= '<div class="phone" itemprop="telephone">'.$phone.'</div>';

			if(!empty($bday))
				$sc_build .= '<div class="bday"><meta itemprop="birthDate" content="'.$bday.'">: '
				. __('DOB', 'schema-creator') . ': '. date('m/d/Y', strtotime($bday)).'</div>';
	
			// close it up
			$sc_build .= '</div>';

		}

		// product 
		if(isset($type) && $type == 'product') {
		
		$sc_build .= '<div itemscope itemtype="http://schema.org/Product">';
		
			if(!empty($name) && !empty($url) ) {
				$sc_build .= '<a class="schema_url" target="_blank" itemprop="url" href="'.esc_url($url).'">';
				$sc_build .= '<div class="schema_name" itemprop="name">'.$name.'</div>';
				$sc_build .= '</a>';
			}

			if(!empty($name) && empty($url) )
				$sc_build .= '<div class="schema_name" itemprop="name">'.$name.'</div>';

			if(!empty($description))
				$sc_build .= '<div class="schema_description" itemprop="description">'.esc_attr($description).'</div>';

			if(!empty($brand))
				$sc_build .= '<div class="brand" itemprop="brand" itemscope itemtype="http://schema.org/Organization"><span class="desc_type">' 
				. __('Brand', 'schema-creator') . ':</span> <span itemprop="name">'.$brand.'</span></div>';

			if(!empty($manfu))
				$sc_build .= '<div class="manufacturer" itemprop="manufacturer" itemscope itemtype="http://schema.org/Organization"><span class="desc_type">'
				. __('Manufacturer', 'schema-creator') . ':</span> <span itemprop="name">'.$manfu.'</span></div>';

			if(!empty($model))
				$sc_build .= '<div class="model"><span class="desc_type">'
				. __('Model', 'schema-creator') . ':</span> <span itemprop="model">'.$model.'</span></div>';

			if(!empty($prod_id))
				$sc_build .= '<div class="prod_id"><span class="desc_type">'
				. __('Product ID', 'schema-creator') . ':</span> <span itemprop="productID">'.$prod_id.'</span></div>';

			if(!empty($single_rating) && !empty($agg_rating)) {
				$sc_build .= '<div itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating">';
				$sc_build .= '<span itemprop="ratingValue">'.$single_rating.'</span>';
				$sc_build .= ' ' . __('based on', 'schema-creator') . ' ';
				$sc_build .= '<span itemprop="reviewCount">'.$agg_rating.'</span>';
				$sc_build .= ' ' . __('reviews', 'schema-creator') . ' ';
				$sc_build .= '</div>';
			}

				// secondary check if one part of review is missing to keep markup consistent
				if(empty($agg_rating) && !empty($single_rating) )
					$sc_build .= '<div itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating"><span itemprop="ratingValue"><span class="desc_type">'
					. __('Review', 'schema-creator') . ':</span> '.$single_rating.'</span></div>';
					
				if(empty($single_rating) && !empty($agg_rating) )
					$sc_build .= '<div itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating"><span itemprop="reviewCount">' 
					. $agg_rating . '</span> ' . __('total reviews', 'schema-creator') . '</div>';

			if(!empty($price) && !empty($condition)) {
				$sc_build .= '<div class="offers" itemprop="offers" itemscope itemtype="http://schema.org/Offer">';
				$sc_build .= '<span class="price" itemprop="price">'.$price.'</span>';
				$sc_build .= '<link itemprop="itemCondition" href="http://schema.org/'.$condition.'Condition" /> '.$condition.'';
				$sc_build .= '</div>';
			}

			if(empty($condition) && !empty ($price))
				$sc_build .= '<div class="offers" itemprop="offers" itemscope itemtype="http://schema.org/Offer"><span class="price" itemprop="price">'.$price.'</span></div>';

	
			// close it up
			$sc_build .= '</div>';

		}
		
		// event
		if(isset($type) && $type == 'event') {
		
		$default   = (!empty($evtype) ? $evtype : 'Event');
		$sc_build .= '<div itemscope itemtype="http://schema.org/'.$default.'">';

			if(!empty($name) && !empty($url) ) {
				$sc_build .= '<a class="schema_url" target="_blank" itemprop="url" href="'.esc_url($url).'">';
				$sc_build .= '<div class="schema_name" itemprop="name">'.$name.'</div>';
				$sc_build .= '</a>';
			}

			if(!empty($name) && empty($url) )
				$sc_build .= '<div class="schema_name" itemprop="name">'.$name.'</div>';

			if(!empty($description))
				$sc_build .= '<div class="schema_description" itemprop="description">'.esc_attr($description).'</div>';

			if(!empty($sdate) && !empty($stime) ) {
				$metatime = $sdate.'T'.date('G:i', strtotime($sdate.$stime));
				$sc_build .= '<div><meta itemprop="startDate" content="'.$metatime.'">Starts: '.date('m/d/Y', strtotime($sdate)).' '.$stime.'</div>';
			}
				// secondary check for missing start time
				if(empty($stime) && !empty($sdate) )
					$sc_build .= '<div><meta itemprop="startDate" content="'.$sdate.'">Starts: '.date('m/d/Y', strtotime($sdate)).'</div>';

			if(!empty($edate))
				$sc_build .= '<div><meta itemprop="endDate" content="'.$edate.':00.000">Ends: '.date('m/d/Y', strtotime($edate)).'</div>';

			if(!empty($duration)) {
					
				$hour_cnv	= date('G', strtotime($duration));
				$mins_cnv	= date('i', strtotime($duration));
				
				$hours		= (!empty($hour_cnv) && $hour_cnv > 0 ? $hour_cnv.' hours' : '');
				$minutes	= (!empty($mins_cnv) && $mins_cnv > 0 ? ' and '.$mins_cnv.' minutes' : '');
				
				$sc_build .= '<div><meta itemprop="duration" content="0000-00-00T'.$duration.'">Duration: '.$hours.$minutes.'</div>';
			}

			// close actual event portion
			$sc_build .= '</div>';
				
			if(	!empty($street) ||
				!empty($pobox) ||
				!empty($city) ||
				!empty($state) ||
				!empty($postalcode) ||
				!empty($country)
				)
				$sc_build .= '<div itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">';

			if(!empty($street))
				$sc_build .= '<div class="street" itemprop="streetAddress">'.$street.'</div>';
			
			if(!empty($pobox))
				$sc_build .= '<div class="pobox">P.O. Box: <span itemprop="postOfficeBoxNumber">'.$pobox.'</span></div>';

			if(!empty($city) && !empty($state)) {
				$sc_build .= '<div class="city_state">';
				$sc_build .= '<span class="locale" itemprop="addressLocality">'.$city.'</span>,';
				$sc_build .= '<span class="region" itemprop="addressRegion"> '.$state.'</span>';
				$sc_build .= '</div>';
			}

				// secondary check if one part of city / state is missing to keep markup consistent
				if(empty($state) && !empty($city) )
					$sc_build .= '<div class="city_state"><span class="locale" itemprop="addressLocality">'.$city.'</span></div>';
					
				if(empty($city) && !empty($state) )
					$sc_build .= '<div class="city_state"><span class="region" itemprop="addressRegion">'.$state.'</span></div>';

			if(!empty($postalcode))
				$sc_build .= '<div class="postalcode" itemprop="postalCode">'.$postalcode.'</div>';

			if(!empty($country))
				$sc_build .= '<div class="country" itemprop="addressCountry">'.$country.'</div>';

			if(	!empty($street) ||
				!empty($pobox) ||
				!empty($city) ||
				!empty($state) ||
				!empty($postalcode) ||
				!empty($country)
				)
				$sc_build .= '</div>';
				
		}

		// organization
		if(isset($type) && $type == 'organization') {

		$default   = (!empty($orgtype) ? $orgtype : 'Organization');
		$sc_build .= '<div itemscope itemtype="http://schema.org/'.$default.'">';

			if(!empty($name) && !empty($url) ) {
				$sc_build .= '<a class="schema_url" target="_blank" itemprop="url" href="'.esc_url($url).'">';
				$sc_build .= '<div class="schema_name" itemprop="name">'.$name.'</div>';
				$sc_build .= '</a>';
			}

			if(!empty($name) && empty($url) )
				$sc_build .= '<div class="schema_name" itemprop="name">'.$name.'</div>';

			if(!empty($description))
				$sc_build .= '<div class="schema_description" itemprop="description">'.esc_attr($description).'</div>';

			if(	!empty($street) ||
				!empty($pobox) ||
				!empty($city) ||
				!empty($state) ||
				!empty($postalcode) ||
				!empty($country)
				)
				$sc_build .= '<div itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">';

			if(!empty($street))
				$sc_build .= '<div class="street" itemprop="streetAddress">'.$street.'</div>';
			
			if(!empty($pobox))
				$sc_build .= '<div class="pobox">P.O. Box: <span itemprop="postOfficeBoxNumber">'.$pobox.'</span></div>';

			if(!empty($city) && !empty($state)) {
				$sc_build .= '<div class="city_state">';
				$sc_build .= '<span class="locale" itemprop="addressLocality">'.$city.'</span>,';
				$sc_build .= '<span class="region" itemprop="addressRegion"> '.$state.'</span>';
				$sc_build .= '</div>';
			}

				// secondary check if one part of city / state is missing to keep markup consistent
				if(empty($state) && !empty($city) )
					$sc_build .= '<div class="city_state"><span class="locale" itemprop="addressLocality">'.$city.'</span></div>';
					
				if(empty($city) && !empty($state) )
					$sc_build .= '<div class="city_state"><span class="region" itemprop="addressRegion">'.$state.'</span></div>';

			if(!empty($postalcode))
				$sc_build .= '<div class="postalcode" itemprop="postalCode">'.$postalcode.'</div>';

			if(!empty($country))
				$sc_build .= '<div class="country" itemprop="addressCountry">'.$country.'</div>';

			if(	!empty($street) ||
				!empty($pobox) ||
				!empty($city) ||
				!empty($state) ||
				!empty($postalcode) ||
				!empty($country)
				)
				$sc_build .= '</div>';

			if(!empty($email))
				$sc_build .= '<div class="email" itemprop="email">'.antispambot($email).'</div>';

			if(!empty($phone))
				$sc_build .= '<div class="phone" itemprop="telephone">'.$phone.'</div>';

			if(!empty($fax))
				$sc_build .= '<div class="fax" itemprop="faxNumber">'.$fax.'</div>';

			// close it up
			$sc_build .= '</div>';
			
		}

		// movie 
		if(isset($type) && $type == 'movie') {
		
		$sc_build .= '<div itemscope itemtype="http://schema.org/Movie">';
		
			if(!empty($name) && !empty($url) ) {
				$sc_build .= '<a class="schema_url" target="_blank" itemprop="url" href="'.esc_url($url).'">';
				$sc_build .= '<div class="schema_name" itemprop="name">'.$name.'</div>';
				$sc_build .= '</a>';
			}

			if(!empty($name) && empty($url) )
				$sc_build .= '<div class="schema_name" itemprop="name">'.$name.'</div>';

			if(!empty($description))
				$sc_build .= '<div class="schema_description" itemprop="description">'.esc_attr($description).'</div>';


			if(!empty($director)) 
				$sc_build .= '<div itemprop="director" itemscope itemtype="http://schema.org/Person">Directed by: <span itemprop="name">'.$director.'</span></div>';

			if(!empty($producer)) 
				$sc_build .= '<div itemprop="producer" itemscope itemtype="http://schema.org/Person">Produced by: <span itemprop="name">'.$producer.'</span></div>';

			if(!empty($actor_1)) {
				$sc_build .= '<div>Starring:';
					foreach ($actors as $actor) {
						$sc_build .= '<div itemprop="actors" itemscope itemtype="http://schema.org/Person">';
						$sc_build .= '<span itemprop="name">'.$actor.'</span>';
						$sc_build .= '</div>';
					}
				$sc_build .= '</div>';			
			}

	
			// close it up
			$sc_build .= '</div>';

		}

		// book 
		if(isset($type) && $type == 'book') {
		
		$sc_build .= '<div itemscope itemtype="http://schema.org/Book">';
		
			if(!empty($name) && !empty($url) ) {
				$sc_build .= '<a class="schema_url" target="_blank" itemprop="url" href="'.esc_url($url).'">';
				$sc_build .= '<div class="schema_name" itemprop="name">'.$name.'</div>';
				$sc_build .= '</a>';
			}

			if(!empty($name) && empty($url) )
				$sc_build .= '<div class="schema_name" itemprop="name">'.$name.'</div>';

			if(!empty($description))
				$sc_build .= '<div class="schema_description" itemprop="description">'.esc_attr($description).'</div>';

			if(!empty($author)) 
				$sc_build .= '<div itemprop="author" itemscope itemtype="http://schema.org/Person">Written by: <span itemprop="name">'.$author.'</span></div>';

			if(!empty($publisher)) 
				$sc_build .= '<div itemprop="publisher" itemscope itemtype="http://schema.org/Organization">Published by: <span itemprop="name">'.$publisher.'</span></div>';

			if(!empty($pubdate))
				$sc_build .= '<div class="bday"><meta itemprop="datePublished" content="'.$pubdate.'">Date Published: '.date('m/d/Y', strtotime($pubdate)).'</div>';

			if(!empty($edition)) 
				$sc_build .= '<div>' . __('Edition','schema-creator') . ': <span itemprop="bookEdition">'.$edition.'</span></div>';

			if(!empty($isbn)) 
				$sc_build .= '<div>' . __('ISBN','schema-creator') . ': <span itemprop="isbn">'.$isbn.'</span></div>';

			if( !empty($ebook) || !empty($paperback) || !empty($hardcover) ) { 
				$sc_build .= '<div>' . __('Available in','schema-creator') . ': ';

					if(!empty($ebook)) 
						$sc_build .= '<link itemprop="bookFormat" href="http://schema.org/Ebook">' . __('Ebook','schema-creator') . ' ';
	
					if(!empty($paperback)) 
						$sc_build .= '<link itemprop="bookFormat" href="http://schema.org/Paperback">' . __('Paperback','schema-creator') . ' ';
					if(!empty($hardcover)) 
						$sc_build .= '<link itemprop="bookFormat" href="http://schema.org/Hardcover">' . __('Hardcover','schema-creator') . ' ';

				$sc_build .= '</div>';
			}
			

			// close it up
			$sc_build .= '</div>';

		}

		// review 
		if(isset($type) && $type == 'review') {
		
		$sc_build .= '<div itemscope itemtype="http://schema.org/Review">';
		
			if(!empty($name) && !empty($url) ) {
				$sc_build .= '<a class="schema_url" target="_blank" itemprop="url" href="'.esc_url($url).'">';
				$sc_build .= '<div class="schema_name" itemprop="name">'.$name.'</div>';
				$sc_build .= '</a>';
			}

			if(!empty($name) && empty($url) )
				$sc_build .= '<div class="schema_name" itemprop="name">'.$name.'</div>';

			if(!empty($description))
				$sc_build .= '<div class="schema_description" itemprop="description">'.esc_attr($description).'</div>';

			if(!empty($rev_name)) 
				$sc_build .= '<div class="schema_review_name" itemprop="itemReviewed" itemscope itemtype="http://schema.org/Thing"><span itemprop="name">'.$rev_name.'</span></div>';

			if(!empty($author)) 
				$sc_build .= '<div itemprop="author" itemscope itemtype="http://schema.org/Person">'
					. __('Written by','schema-creator') . ': <span itemprop="name">'.$author.'</span></div>';

			if(!empty($pubdate))
				$sc_build .= '<div class="pubdate"><meta itemprop="datePublished" content="'.$pubdate.'">'
					. __('Date Published','schema-creator') . ': '.date('m/d/Y', strtotime($pubdate)).'</div>';

			if(!empty($rev_body))
				$sc_build .= '<div class="schema_review_body" itemprop="reviewBody">'
					. esc_textarea($rev_body).'</div>';

			if(!empty($user_review) ) {
				$sc_build .= '<div itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating">';

				// minimum review scale
				if(!empty($min_review))
					$sc_build .= '<meta itemprop="worstRating" content="'.$min_review.'">';

				$sc_build .= '<span itemprop="ratingValue">'.$user_review.'</span>';

				// max review scale
				if(!empty($max_review))
					$sc_build .= ' / <span itemprop="bestRating">'.$max_review.'</span> stars';


				$sc_build .= '</div>';
			}

			

			// close it up
			$sc_build .= '</div>';

		}

		
		// close schema wrap
		$sc_build .= '</div>';

	// return entire build array
	return $sc_build;
	
	}

	/**
	 * Add button to top level media row
	 *
	 * @return ravenSchema
	 */

	public function media_button($context) {
		
		// don't show on dashboard (QuickPress)
		$current_screen = get_current_screen();
		if ( 'dashboard' == $current_screen->base )
			return $context;

		// don't display button for users who don't have access
		if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
			return;
		
		$button = '<a href="#TB_inline?width=650&inlineId=schema_build_form" class="thickbox schema_clear" id="add_schema" title="' . __('Schema Creator Form', 'schema-creator') . '">' . __('Schema Creator Form', 'schema-creator') . '</a>';

	return $context . $button;
}

	/**
	 * Build form and add into footer
	 *
	 * @return ravenSchema
	 */

	public function schema_form() { 
		
		// don't display form for users who don't have access
		if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
		return;

	?>
	
		<script type="text/javascript">
			function InsertSchema() {
				//select field options
					var type			= jQuery('#schema_builder select#schema_type').val();
					var evtype			= jQuery('#schema_builder select#schema_evtype').val();
					var orgtype			= jQuery('#schema_builder select#schema_orgtype').val();
					var country			= jQuery('#schema_builder select#schema_country').val();				
					var condition		= jQuery('#schema_builder select#schema_condition').val();
				//text field options
					var name			= jQuery('#schema_builder input#schema_name').val();
					var orgname			= jQuery('#schema_builder input#schema_orgname').val();
					var jobtitle		= jQuery('#schema_builder input#schema_jobtitle').val();
					var url				= jQuery('#schema_builder input#schema_url').val();
					var bday			= jQuery('#schema_builder input#schema_bday-format').val();
					var street			= jQuery('#schema_builder input#schema_street').val();
					var pobox			= jQuery('#schema_builder input#schema_pobox').val();
					var city			= jQuery('#schema_builder input#schema_city').val();
					var state			= jQuery('#schema_builder input#schema_state').val();
					var postalcode		= jQuery('#schema_builder input#schema_postalcode').val();
					var email			= jQuery('#schema_builder input#schema_email').val();
					var phone			= jQuery('#schema_builder input#schema_phone').val();
					var fax				= jQuery('#schema_builder input#schema_fax').val();
					var brand			= jQuery('#schema_builder input#schema_brand').val();
					var manfu			= jQuery('#schema_builder input#schema_manfu').val();
					var model			= jQuery('#schema_builder input#schema_model').val();
					var prod_id			= jQuery('#schema_builder input#schema_prod_id').val();
					var single_rating	= jQuery('#schema_builder input#schema_single_rating').val();
					var agg_rating		= jQuery('#schema_builder input#schema_agg_rating').val();
					var price			= jQuery('#schema_builder input#schema_price').val();
					var sdate			= jQuery('#schema_builder input#schema_sdate-format').val();
					var stime			= jQuery('#schema_builder input#schema_stime').val();
					var edate			= jQuery('#schema_builder input#schema_edate-format').val();
					var duration		= jQuery('#schema_builder input#schema_duration').val();
					var actor_group		= jQuery('#schema_builder input#schema_actor_1').val();
					var director		= jQuery('#schema_builder input#schema_director').val();
					var producer		= jQuery('#schema_builder input#schema_producer').val();
					var author			= jQuery('#schema_builder input#schema_author').val();
					var publisher		= jQuery('#schema_builder input#schema_publisher').val();
					var edition			= jQuery('#schema_builder input#schema_edition').val();
					var isbn			= jQuery('#schema_builder input#schema_isbn').val();
					var pubdate			= jQuery('#schema_builder input#schema_pubdate-format').val();
					var ebook			= jQuery('#schema_builder input#schema_ebook').is(':checked');
					var paperback		= jQuery('#schema_builder input#schema_paperback').is(':checked');
					var hardcover		= jQuery('#schema_builder input#schema_hardcover').is(':checked');
					var rev_name		= jQuery('#schema_builder input#schema_rev_name').val();
					var user_review		= jQuery('#schema_builder input#schema_user_review').val();
					var min_review		= jQuery('#schema_builder input#schema_min_review').val();
					var max_review		= jQuery('#schema_builder input#schema_max_review').val();
				// textfield options
					var description		= jQuery('#schema_builder textarea#schema_description').val();
					var rev_body		= jQuery('#schema_builder textarea#schema_rev_body').val();

			// output setups
			output = '[schema ';
				output += 'type="' + type + '" ';

				// person
				if(type == 'person' ) {
					if(name)
						output += 'name="' + name + '" ';
					if(orgname)
						output += 'orgname="' + orgname + '" ';
					if(jobtitle)
						output += 'jobtitle="' + jobtitle + '" ';
					if(url)
						output += 'url="' + url + '" ';
					if(description)
						output += 'description="' + description + '" ';
					if(bday)
						output += 'bday="' + bday + '" ';
					if(street)
						output += 'street="' + street + '" ';
					if(pobox)
						output += 'pobox="' + pobox + '" ';
					if(city)
						output += 'city="' + city + '" ';
					if(state)
						output += 'state="' + state + '" ';
					if(postalcode)
						output += 'postalcode="' + postalcode + '" ';
					if(country && country !== 'none')
						output += 'country="' + country + '" ';
					if(email)
						output += 'email="' + email + '" ';
					if(phone)
						output += 'phone="' + phone + '" ';
				}

				// product
				if(type == 'product' ) {
					if(url)
						output += 'url="' + url + '" ';
					if(name)
						output += 'name="' + name + '" ';
					if(description)
						output += 'description="' + description + '" ';
					if(brand)
						output += 'brand="' + brand + '" ';
					if(manfu)
						output += 'manfu="' + manfu + '" ';
					if(model)
						output += 'model="' + model + '" ';
					if(prod_id)
						output += 'prod_id="' + prod_id + '" ';
					if(single_rating)
						output += 'single_rating="' + single_rating + '" ';
					if(agg_rating)
						output += 'agg_rating="' + agg_rating + '" ';
					if(price)
						output += 'price="' + price + '" ';
					if(condition && condition !=='none')
						output += 'condition="' + condition + '" ';
				}

				// event
				if(type == 'event' ) {
					if(evtype && evtype !== 'none')
						output += 'evtype="' + evtype + '" ';
					if(url)
						output += 'url="' + url + '" ';
					if(name)
						output += 'name="' + name + '" ';
					if(description)
						output += 'description="' + description + '" ';
					if(sdate)
						output += 'sdate="' + sdate + '" ';
					if(stime)
						output += 'stime="' + stime + '" ';
					if(edate)
						output += 'edate="' + edate + '" ';
					if(duration)
						output += 'duration="' + duration + '" ';
					if(street)
						output += 'street="' + street + '" ';
					if(pobox)
						output += 'pobox="' + pobox + '" ';
					if(city)
						output += 'city="' + city + '" ';
					if(state)
						output += 'state="' + state + '" ';
					if(postalcode)
						output += 'postalcode="' + postalcode + '" ';
					if(country && country !== 'none')
						output += 'country="' + country + '" ';	
				}

				// organization
				if(type == 'organization' ) {
					if(orgtype)
						output += 'orgtype="' + orgtype + '" ';
					if(url)
						output += 'url="' + url + '" ';
					if(name)
						output += 'name="' + name + '" ';
					if(description)
						output += 'description="' + description + '" ';
					if(street)
						output += 'street="' + street + '" ';
					if(pobox)
						output += 'pobox="' + pobox + '" ';
					if(city)
						output += 'city="' + city + '" ';
					if(state)
						output += 'state="' + state + '" ';
					if(postalcode)
						output += 'postalcode="' + postalcode + '" ';
					if(country && country !== 'none')
						output += 'country="' + country + '" ';
					if(email)
						output += 'email="' + email + '" ';
					if(phone)
						output += 'phone="' + phone + '" ';
					if(fax)
						output += 'fax="' + fax + '" ';
				}

				// movie
				if(type == 'movie' ) {
					if(url)
						output += 'url="' + url + '" ';
					if(name)
						output += 'name="' + name + '" ';
					if(description)
						output += 'description="' + description + '" ';
					if(director)
						output += 'director="' + director + '" ';						
					if(producer)
						output += 'producer="' + producer + '" ';
					if(actor_group) {
						var count = 0;
						jQuery('div.sc_actor').each(function(){
							count++;
							var actor = jQuery(this).find('input').val();
							output += 'actor_' + count + '="' + actor + '" ';
						});
					}
				}

				// book
				if(type == 'book' ) {
					if(url)
						output += 'url="' + url + '" ';
					if(name)
						output += 'name="' + name + '" ';
					if(description)
						output += 'description="' + description + '" ';
					if(author)
						output += 'author="' + author + '" ';						
					if(publisher)
						output += 'publisher="' + publisher + '" ';
					if(pubdate)
						output += 'pubdate="' + pubdate + '" ';
					if(edition)
						output += 'edition="' + edition + '" ';
					if(isbn)
						output += 'isbn="' + isbn + '" ';
					if(ebook === true )
						output += 'ebook="yes" ';
					if(paperback === true )
						output += 'paperback="yes" ';
					if(hardcover === true )
						output += 'hardcover="yes" ';
				}

				// review
				if(type == 'review' ) {
					if(url)
						output += 'url="' + url + '" ';
					if(name)
						output += 'name="' + name + '" ';
					if(description)
						output += 'description="' + description + '" ';
					if(rev_name)
						output += 'rev_name="' + rev_name + '" ';
					if(rev_body)
						output += 'rev_body="' + rev_body + '" ';					
					if(author)
						output += 'author="' + author + '" ';
					if(pubdate)
						output += 'pubdate="' + pubdate + '" ';
					if(user_review)
						output += 'user_review="' + user_review + '" ';
					if(min_review)
						output += 'min_review="' + min_review + '" ';
					if(max_review)
						output += 'max_review="' + max_review + '" ';
				}


			output += ']';
	
			window.send_to_editor(output);
			}
		</script>
	
			<div id="schema_build_form" style="display:none;">
			<div id="schema_builder" class="schema_wrap">
			<!-- schema type dropdown -->	
				<div id="sc_type">
					<label for="schema_type"><?php _e('Schema Type', 'schema-creator'); ?></label>
					<select name="schema_type" id="schema_type" class="schema_drop schema_thindrop">
						<option class="holder" value="none">(<?php _e('Select A Type', 'schema-creator'); ?>)</option>
						<option value="person"><?php _e('Person', 'schema-creator'); ?></option>
						<option value="product"><?php _e('Product', 'schema-creator'); ?></option>
						<option value="event"><?php _e('Event', 'schema-creator'); ?></option>
						<option value="organization"><?php _e('Organization', 'schema-creator'); ?></option>
						<option value="movie"><?php _e('Movie', 'schema-creator'); ?></option>
						<option value="book"><?php _e('Book', 'schema-creator'); ?></option>
						<option value="review"><?php _e('Review', 'schema-creator'); ?></option>
					</select>
				</div>
			<!-- end schema type dropdown -->	

				<div id="sc_evtype" class="sc_option" style="display:none">
					<label for="schema_evtype"><?php _e('Event Type', 'schema-creator'); ?></label>
					<select name="schema_evtype" id="schema_evtype" class="schema_drop schema_thindrop">
						<option value="Event"><?php _e('General', 'schema-creator'); ?></option>
						<option value="BusinessEvent"><?php _e('Business', 'schema-creator'); ?></option>
						<option value="ChildrensEvent"><?php _e('Childrens', 'schema-creator'); ?></option>
						<option value="ComedyEvent"><?php _e('Comedy', 'schema-creator'); ?></option>
						<option value="DanceEvent"><?php _e('Dance', 'schema-creator'); ?></option>
						<option value="EducationEvent"><?php _e('Education', 'schema-creator'); ?></option>
						<option value="Festival"><?php _e('Festival', 'schema-creator'); ?></option>
						<option value="FoodEvent"><?php _e('Food', 'schema-creator'); ?></option>
						<option value="LiteraryEvent"><?php _e('Literary', 'schema-creator'); ?></option>
						<option value="MusicEvent"><?php _e('Music', 'schema-creator'); ?></option>
						<option value="SaleEvent"><?php _e('Sale', 'schema-creator'); ?></option>
						<option value="SocialEvent"><?php _e('Social', 'schema-creator'); ?></option>
						<option value="SportsEvent"><?php _e('Sports', 'schema-creator'); ?></option>
						<option value="TheaterEvent"><?php _e('Theater', 'schema-creator'); ?></option>
						<option value="UserInteraction"><?php _e('User Interaction', 'schema-creator'); ?></option>
						<option value="VisualArtsEvent"><?php _e('Visual Arts', 'schema-creator'); ?></option>
					</select>
				</div>

				<div id="sc_orgtype" class="sc_option" style="display:none">
					<label for="schema_orgtype"><?php _e('Organziation Type', 'schema-creator'); ?></label>
					<select name="schema_orgtype" id="schema_orgtype" class="schema_drop schema_thindrop">
						<option value="Organization"><?php _e('General', 'schema-creator'); ?></option>
						<option value="Corporation"><?php _e('Corporation', 'schema-creator'); ?></option>
						<option value="EducationalOrganization"><?php _e('School', 'schema-creator'); ?></option>
						<option value="GovernmentOrganization"><?php _e('Government', 'schema-creator'); ?></option>
						<option value="LocalBusiness"><?php _e('Local Business', 'schema-creator'); ?></option>
						<option value="NGO"><?php _e('NGO', 'schema-creator'); ?></option>
						<option value="PerformingGroup"><?php _e('Performing Group', 'schema-creator'); ?></option>
						<option value="SportsTeam"><?php _e('Sports Team', 'schema-creator'); ?></option>
					</select>
				</div>

				<div id="sc_name" class="sc_option" style="display:none">
					<label for="schema_name"><?php _e('Name', 'schema-creator'); ?></label>
					<input type="text" name="schema_name" class="form_full" value="" id="schema_name" />
				</div>

				<div id="sc_orgname" class="sc_option" style="display:none">
					<label for="schema_orgname"><?php _e('Organization', 'schema-creator'); ?></label>
					<input type="text" name="schema_orgname" class="form_full" value="" id="schema_orgname" />
				</div>
	
				<div id="sc_jobtitle" class="sc_option" style="display:none">
					<label for="schema_jobtitle"><?php _e('Job Title', 'schema-creator'); ?></label>
					<input type="text" name="schema_jobtitle" class="form_full" value="" id="schema_jobtitle" />
				</div>
	
				<div id="sc_url" class="sc_option" style="display:none">
					<label for="schema_url"><?php _e('Website', 'schema-creator'); ?></label>
					<input type="text" name="schema_url" class="form_full" value="" id="schema_url" />
				</div>
	
				<div id="sc_description" class="sc_option" style="display:none">
					<label for="schema_description"><?php _e('Description', 'schema-creator'); ?></label>
					<textarea name="schema_description" id="schema_description"></textarea>
				</div>

				<div id="sc_rev_name" class="sc_option" style="display:none">
					<label for="schema_rev_name"><?php _e('Item Name', 'schema-creator'); ?></label>
					<input type="text" name="schema_rev_name" class="form_full" value="" id="schema_rev_name" />
				</div>

				<div id="sc_rev_body" class="sc_option" style="display:none">
					<label for="schema_rev_body"><?php _e('Item Review', 'schema-creator'); ?></label>
					<textarea name="schema_rev_body" id="schema_rev_body"></textarea>
				</div>

				<div id="sc_director" class="sc_option" style="display:none">
					<label for="schema_director"><?php _e('Director', 'schema-creator'); ?></label>
					<input type="text" name="schema_director" class="form_full" value="" id="schema_director" />
				</div>

				<div id="sc_producer" class="sc_option" style="display:none">
					<label for="schema_producer"><?php _e('Productor', 'schema-creator'); ?></label>
					<input type="text" name="schema_producer" class="form_full" value="" id="schema_producer" />
				</div>

				<div id="sc_actor_1" class="sc_option sc_actor sc_repeater" style="display:none">
					<label for="schema_actor_1"><?php _e('Actor', 'schema-creator'); ?></label>
					<input type="text" name="schema_actor_1" class="form_full actor_input" value="" id="schema_actor_1" />
				</div>

				<input type="button" id="clone_actor" value="<?php _e('Add Another Actor', 'schema-creator'); ?>" style="display:none;" />


				<div id="sc_sdate" class="sc_option" style="display:none">
					<label for="schema_sdate"><?php _e('Start Date', 'schema-creator'); ?></label>
					<input type="text" id="schema_sdate" name="schema_sdate" class="schema_datepicker timepicker form_third" value="" />
					<input type="hidden" id="schema_sdate-format" class="schema_datepicker-format" value="" />
				</div>

				<div id="sc_stime" class="sc_option" style="display:none">
					<label for="schema_stime"><?php _e('Start Time', 'schema-creator'); ?></label>
					<input type="text" id="schema_stime" name="schema_stime" class="schema_timepicker form_third" value="" />
				</div>

				<div id="sc_edate" class="sc_option" style="display:none">
					<label for="schema_edate"><?php _e('End Date', 'schema-creator'); ?></label>
					<input type="text" id="schema_edate" name="schema_edate" class="schema_datepicker form_third" value="" />
					<input type="hidden" id="schema_edate-format" class="schema_datepicker-format" value="" />
				</div>

				<div id="sc_duration" class="sc_option" style="display:none">
					<label for="schema_duration"><?php _e('Duration', 'schema-creator'); ?></label>
					<input type="text" id="schema_duration" name="schema_duration" class="schema_timepicker form_third" value="" />
				</div>
	
				<div id="sc_bday" class="sc_option" style="display:none">
					<label for="schema_bday"><?php _e('Birthday', 'schema-creator'); ?></label>
					<input type="text" id="schema_bday" name="schema_bday" class="schema_datepicker form_third" value="" />
					<input type="hidden" id="schema_bday-format" class="schema_datepicker-format" value="" />
				</div>
	
				<div id="sc_street" class="sc_option" style="display:none">
					<label for="schema_street"><?php _e('Address', 'schema-creator'); ?></label>
					<input type="text" name="schema_street" class="form_full" value="" id="schema_street" />
				</div>
	
				<div id="sc_pobox" class="sc_option" style="display:none">
					<label for="schema_pobox"><?php _e('PO Box', 'schema-creator'); ?></label>
					<input type="text" name="schema_pobox" class="form_third schema_numeric" value="" id="schema_pobox" />
				</div>
	
				<div id="sc_city" class="sc_option" style="display:none">
					<label for="schema_city"><?php _e('City', 'schema-creator'); ?></label>
					<input type="text" name="schema_city" class="form_full" value="" id="schema_city" />
				</div>
	
				<div id="sc_state" class="sc_option" style="display:none">
					<label for="schema_state"><?php _e('State / Region', 'schema-creator'); ?></label>
					<input type="text" name="schema_state" class="form_third" value="" id="schema_state" />
				</div>
	
				<div id="sc_postalcode" class="sc_option" style="display:none">
					<label for="schema_postalcode"><?php _e('Postal Code', 'schema-creator'); ?></label>
					<input type="text" name="schema_postalcode" class="form_third" value="" id="schema_postalcode" />
				</div>

				<div id="sc_country" class="sc_option" style="display:none">
					<label for="schema_country"><?php _e('Country', 'schema-creator'); ?></label>
					<select name="schema_country" id="schema_country" class="schema_drop schema_thindrop">
						<option class="holder" value="none">(<?php _e('Select A Country', 'schema-creator'); ?>)</option>
													<option value="US"><?php _e('United States', 'schema-creator'); ?></option>
						<option value="CA"><?php _e('Canada', 'schema-creator'); ?></option>
						<option value="MX"><?php _e('Mexico', 'schema-creator'); ?></option>
						<option value="GB"><?php _e('United Kingdom', 'schema-creator'); ?></option>
						<option value="AF"><?php _e('Afghanistan', 'schema-creator'); ?></option>
						<option value="AX"><?php _e('Åland Islands', 'schema-creator'); ?></option>
						<option value="AL"><?php _e('Albania', 'schema-creator'); ?></option>
						<option value="DZ"><?php _e('Algeria', 'schema-creator'); ?></option>
						<option value="AS"><?php _e('American Samoa', 'schema-creator'); ?></option>
						<option value="AD"><?php _e('Andorra', 'schema-creator'); ?></option>
						<option value="AO"><?php _e('Angola', 'schema-creator'); ?></option>
						<option value="AI"><?php _e('Anguilla', 'schema-creator'); ?></option>
						<option value="AQ"><?php _e('Antarctica', 'schema-creator'); ?></option>
						<option value="AG"><?php _e('Antigua And Barbuda', 'schema-creator'); ?></option>
						<option value="AR"><?php _e('Argentina', 'schema-creator'); ?></option>
						<option value="AM"><?php _e('Armenia', 'schema-creator'); ?></option>
						<option value="AW"><?php _e('Aruba', 'schema-creator'); ?></option>
						<option value="AU"><?php _e('Australia', 'schema-creator'); ?></option>
						<option value="AT"><?php _e('Austria', 'schema-creator'); ?></option>
						<option value="AZ"><?php _e('Azerbaijan', 'schema-creator'); ?></option>
						<option value="BS"><?php _e('Bahamas', 'schema-creator'); ?></option>
						<option value="BH"><?php _e('Bahrain', 'schema-creator'); ?></option>
						<option value="BD"><?php _e('Bangladesh', 'schema-creator'); ?></option>
						<option value="BB"><?php _e('Barbados', 'schema-creator'); ?></option>
						<option value="BY"><?php _e('Belarus', 'schema-creator'); ?></option>
						<option value="BE"><?php _e('Belgium', 'schema-creator'); ?></option>
						<option value="BZ"><?php _e('Belize', 'schema-creator'); ?></option>
						<option value="BJ"><?php _e('Benin', 'schema-creator'); ?></option>
						<option value="BM"><?php _e('Bermuda', 'schema-creator'); ?></option>
						<option value="BT"><?php _e('Bhutan', 'schema-creator'); ?></option>
						<option value="BO"><?php _e('Bolivia, Plurinational State Of', 'schema-creator'); ?></option>
						<option value="BQ"><?php _e('Bonaire, Sint Eustatius And Saba', 'schema-creator'); ?></option>
						<option value="BA"><?php _e('Bosnia And Herzegovina', 'schema-creator'); ?></option>
						<option value="BW"><?php _e('Botswana', 'schema-creator'); ?></option>
						<option value="BV"><?php _e('Bouvet Island', 'schema-creator'); ?></option>
						<option value="BR"><?php _e('Brazil', 'schema-creator'); ?></option>
						<option value="IO"><?php _e('British Indian Ocean Territory', 'schema-creator'); ?></option>
						<option value="BN"><?php _e('Brunei Darussalam', 'schema-creator'); ?></option>
						<option value="BG"><?php _e('Bulgaria', 'schema-creator'); ?></option>
						<option value="BF"><?php _e('Burkina Faso', 'schema-creator'); ?></option>
						<option value="BI"><?php _e('Burundi', 'schema-creator'); ?></option>
						<option value="KH"><?php _e('Cambodia', 'schema-creator'); ?></option>
						<option value="CM"><?php _e('Cameroon', 'schema-creator'); ?></option>
						<option value="CV"><?php _e('Cape Verde', 'schema-creator'); ?></option>
						<option value="KY"><?php _e('Cayman Islands', 'schema-creator'); ?></option>
						<option value="CF"><?php _e('Central African Republic', 'schema-creator'); ?></option>
						<option value="TD"><?php _e('Chad', 'schema-creator'); ?></option>
						<option value="CL"><?php _e('Chile', 'schema-creator'); ?></option>
						<option value="CN"><?php _e('China', 'schema-creator'); ?></option>
						<option value="CX"><?php _e('Christmas Island', 'schema-creator'); ?></option>
						<option value="CC"><?php _e('Cocos (Keeling) Islands', 'schema-creator'); ?></option>
						<option value="CO"><?php _e('Colombia', 'schema-creator'); ?></option>
						<option value="KM"><?php _e('Comoros', 'schema-creator'); ?></option>
						<option value="CG"><?php _e('Congo', 'schema-creator'); ?></option>
						<option value="CD"><?php _e('Congo, The Democratic Republic Of The', 'schema-creator'); ?></option>
						<option value="CK"><?php _e('Cook Islands', 'schema-creator'); ?></option>
						<option value="CR"><?php _e('Costa Rica', 'schema-creator'); ?></option>
						<option value="CI"><?php _e("Côte D'Ivoire", 'schema-creator'); ?></option>
						<option value="HR"><?php _e('Croatia', 'schema-creator'); ?></option>
						<option value="CU"><?php _e('Cuba', 'schema-creator'); ?></option>
						<option value="CW"><?php _e('Curaçao', 'schema-creator'); ?></option>
						<option value="CY"><?php _e('Cyprus', 'schema-creator'); ?></option>
						<option value="CZ"><?php _e('Czech Republic', 'schema-creator'); ?></option>
						<option value="DK"><?php _e('Denmark', 'schema-creator'); ?></option>
						<option value="DJ"><?php _e('Djibouti', 'schema-creator'); ?></option>
						<option value="DM"><?php _e('Dominica', 'schema-creator'); ?></option>
						<option value="DO"><?php _e('Dominican Republic', 'schema-creator'); ?></option>
						<option value="EC"><?php _e('Ecuador', 'schema-creator'); ?></option>
						<option value="EG"><?php _e('Egypt', 'schema-creator'); ?></option>
						<option value="SV"><?php _e('El Salvador', 'schema-creator'); ?></option>
						<option value="GQ"><?php _e('Equatorial Guinea', 'schema-creator'); ?></option>
						<option value="ER"><?php _e('Eritrea', 'schema-creator'); ?></option>
						<option value="EE"><?php _e('Estonia', 'schema-creator'); ?></option>
						<option value="ET"><?php _e('Ethiopia', 'schema-creator'); ?></option>
						<option value="FK"><?php _e('Falkland Islands (Malvinas)', 'schema-creator'); ?></option>
						<option value="FO"><?php _e('Faroe Islands', 'schema-creator'); ?></option>
						<option value="FJ"><?php _e('Fiji', 'schema-creator'); ?></option>
						<option value="FI"><?php _e('Finland', 'schema-creator'); ?></option>
						<option value="FR"><?php _e('France', 'schema-creator'); ?></option>
						<option value="GF"><?php _e('French Guiana', 'schema-creator'); ?></option>
						<option value="PF"><?php _e('French Polynesia', 'schema-creator'); ?></option>
						<option value="TF"><?php _e('French Southern Territories', 'schema-creator'); ?></option>
						<option value="GA"><?php _e('Gabon', 'schema-creator'); ?></option>
						<option value="GM"><?php _e('Gambia', 'schema-creator'); ?></option>
						<option value="GE"><?php _e('Georgia', 'schema-creator'); ?></option>
						<option value="DE"><?php _e('Germany', 'schema-creator'); ?></option>
						<option value="GH"><?php _e('Ghana', 'schema-creator'); ?></option>
						<option value="GI"><?php _e('Gibraltar', 'schema-creator'); ?></option>
						<option value="GR"><?php _e('Greece', 'schema-creator'); ?></option>
						<option value="GL"><?php _e('Greenland', 'schema-creator'); ?></option>
						<option value="GD"><?php _e('Grenada', 'schema-creator'); ?></option>
						<option value="GP"><?php _e('Guadeloupe', 'schema-creator'); ?></option>
						<option value="GU"><?php _e('Guam', 'schema-creator'); ?></option>
						<option value="GT"><?php _e('Guatemala', 'schema-creator'); ?></option>
						<option value="GG"><?php _e('Guernsey', 'schema-creator'); ?></option>
						<option value="GN"><?php _e('Guinea', 'schema-creator'); ?></option>
						<option value="GW"><?php _e('Guinea-Bissau', 'schema-creator'); ?></option>
						<option value="GY"><?php _e('Guyana', 'schema-creator'); ?></option>
						<option value="HT"><?php _e('Haiti', 'schema-creator'); ?></option>
						<option value="HM"><?php _e('Heard Island And Mcdonald Islands', 'schema-creator'); ?></option>
						<option value="VA"><?php _e('Vatican City', 'schema-creator'); ?></option>
						<option value="HN"><?php _e('Honduras', 'schema-creator'); ?></option>
						<option value="HK"><?php _e('Hong Kong', 'schema-creator'); ?></option>
						<option value="HU"><?php _e('Hungary', 'schema-creator'); ?></option>
						<option value="IS"><?php _e('Iceland', 'schema-creator'); ?></option>
						<option value="IN"><?php _e('India', 'schema-creator'); ?></option>
						<option value="ID"><?php _e('Indonesia', 'schema-creator'); ?></option>
						<option value="IR"><?php _e('Iran', 'schema-creator'); ?></option>
						<option value="IQ"><?php _e('Iraq', 'schema-creator'); ?></option>
						<option value="IE"><?php _e('Ireland', 'schema-creator'); ?></option>
						<option value="IM"><?php _e('Isle Of Man', 'schema-creator'); ?></option>
						<option value="IL"><?php _e('Israel', 'schema-creator'); ?></option>
						<option value="IT"><?php _e('Italy', 'schema-creator'); ?></option>
						<option value="JM"><?php _e('Jamaica', 'schema-creator'); ?></option>
						<option value="JP"><?php _e('Japan', 'schema-creator'); ?></option>
						<option value="JE"><?php _e('Jersey', 'schema-creator'); ?></option>
						<option value="JO"><?php _e('Jordan', 'schema-creator'); ?></option>
						<option value="KZ"><?php _e('Kazakhstan', 'schema-creator'); ?></option>
						<option value="KE"><?php _e('Kenya', 'schema-creator'); ?></option>
						<option value="KI"><?php _e('Kiribati', 'schema-creator'); ?></option>
						<option value="KP"><?php _e('North Korea', 'schema-creator'); ?></option>
						<option value="KR"><?php _e('South Korea', 'schema-creator'); ?></option>
						<option value="KW"><?php _e('Kuwait', 'schema-creator'); ?></option>
						<option value="KG"><?php _e('Kyrgyzstan', 'schema-creator'); ?></option>
						<option value="LA"><?php _e('Laos', 'schema-creator'); ?></option>
						<option value="LV"><?php _e('Latvia', 'schema-creator'); ?></option>
						<option value="LB"><?php _e('Lebanon', 'schema-creator'); ?></option>
						<option value="LS"><?php _e('Lesotho', 'schema-creator'); ?></option>
						<option value="LR"><?php _e('Liberia', 'schema-creator'); ?></option>
						<option value="LY"><?php _e('Libya', 'schema-creator'); ?></option>
						<option value="LI"><?php _e('Liechtenstein', 'schema-creator'); ?></option>
						<option value="LT"><?php _e('Lithuania', 'schema-creator'); ?></option>
						<option value="LU"><?php _e('Luxembourg', 'schema-creator'); ?></option>
						<option value="MO"><?php _e('Macao', 'schema-creator'); ?></option>
						<option value="MK"><?php _e('Macedonia', 'schema-creator'); ?></option>
						<option value="MG"><?php _e('Madagascar', 'schema-creator'); ?></option>
						<option value="MW"><?php _e('Malawi', 'schema-creator'); ?></option>
						<option value="MY"><?php _e('Malaysia', 'schema-creator'); ?></option>
						<option value="MV"><?php _e('Maldives', 'schema-creator'); ?></option>
						<option value="ML"><?php _e('Mali', 'schema-creator'); ?></option>
						<option value="MT"><?php _e('Malta', 'schema-creator'); ?></option>
						<option value="MH"><?php _e('Marshall Islands', 'schema-creator'); ?></option>
						<option value="MQ"><?php _e('Martinique', 'schema-creator'); ?></option>
						<option value="MR"><?php _e('Mauritania', 'schema-creator'); ?></option>
						<option value="MU"><?php _e('Mauritius', 'schema-creator'); ?></option>
						<option value="YT"><?php _e('Mayotte', 'schema-creator'); ?></option>
						<option value="FM"><?php _e('Micronesia', 'schema-creator'); ?></option>
						<option value="MD"><?php _e('Moldova', 'schema-creator'); ?></option>
						<option value="MC"><?php _e('Monaco', 'schema-creator'); ?></option>
						<option value="MN"><?php _e('Mongolia', 'schema-creator'); ?></option>
						<option value="ME"><?php _e('Montenegro', 'schema-creator'); ?></option>
						<option value="MS"><?php _e('Montserrat', 'schema-creator'); ?></option>
						<option value="MA"><?php _e('Morocco', 'schema-creator'); ?></option>
						<option value="MZ"><?php _e('Mozambique', 'schema-creator'); ?></option>
						<option value="MM"><?php _e('Myanmar', 'schema-creator'); ?></option>
						<option value="NA"><?php _e('Namibia', 'schema-creator'); ?></option>
						<option value="NR"><?php _e('Nauru', 'schema-creator'); ?></option>
						<option value="NP"><?php _e('Nepal', 'schema-creator'); ?></option>
						<option value="NL"><?php _e('Netherlands', 'schema-creator'); ?></option>
						<option value="NC"><?php _e('New Caledonia', 'schema-creator'); ?></option>
						<option value="NZ"><?php _e('New Zealand', 'schema-creator'); ?></option>
						<option value="NI"><?php _e('Nicaragua', 'schema-creator'); ?></option>
						<option value="NE"><?php _e('Niger', 'schema-creator'); ?></option>
						<option value="NG"><?php _e('Nigeria', 'schema-creator'); ?></option>
						<option value="NU"><?php _e('Niue', 'schema-creator'); ?></option>
						<option value="NF"><?php _e('Norfolk Island', 'schema-creator'); ?></option>
						<option value="MP"><?php _e('Northern Mariana Islands', 'schema-creator'); ?></option>
						<option value="NO"><?php _e('Norway', 'schema-creator'); ?></option>
						<option value="OM"><?php _e('Oman', 'schema-creator'); ?></option>
						<option value="PK"><?php _e('Pakistan', 'schema-creator'); ?></option>
						<option value="PW"><?php _e('Palau', 'schema-creator'); ?></option>
						<option value="PS"><?php _e('Palestine', 'schema-creator'); ?></option>
						<option value="PA"><?php _e('Panama', 'schema-creator'); ?></option>
						<option value="PG"><?php _e('Papua New Guinea', 'schema-creator'); ?></option>
						<option value="PY"><?php _e('Paraguay', 'schema-creator'); ?></option>
						<option value="PE"><?php _e('Peru', 'schema-creator'); ?></option>
						<option value="PH"><?php _e('Philippines', 'schema-creator'); ?></option>
						<option value="PN"><?php _e('Pitcairn', 'schema-creator'); ?></option>
						<option value="PL"><?php _e('Poland', 'schema-creator'); ?></option>
						<option value="PT"><?php _e('Portugal', 'schema-creator'); ?></option>
						<option value="PR"><?php _e('Puerto Rico', 'schema-creator'); ?></option>
						<option value="QA"><?php _e('Qatar', 'schema-creator'); ?></option>
						<option value="RE"><?php _e('Réunion', 'schema-creator'); ?></option>
						<option value="RO"><?php _e('Romania', 'schema-creator'); ?></option>
						<option value="RU"><?php _e('Russian Federation', 'schema-creator'); ?></option>
						<option value="RW"><?php _e('Rwanda', 'schema-creator'); ?></option>
						<option value="BL"><?php _e('St. Barthélemy', 'schema-creator'); ?></option>
						<option value="SH"><?php _e('St. Helena', 'schema-creator'); ?></option>
						<option value="KN"><?php _e('St. Kitts And Nevis', 'schema-creator'); ?></option>
						<option value="LC"><?php _e('St. Lucia', 'schema-creator'); ?></option>
						<option value="MF"><?php _e('St. Martin (French Part)', 'schema-creator'); ?></option>
						<option value="PM"><?php _e('St. Pierre And Miquelon', 'schema-creator'); ?></option>
						<option value="VC"><?php _e('St. Vincent And The Grenadines', 'schema-creator'); ?></option>
						<option value="WS"><?php _e('Samoa', 'schema-creator'); ?></option>
						<option value="SM"><?php _e('San Marino', 'schema-creator'); ?></option>
						<option value="ST"><?php _e('Sao Tome And Principe', 'schema-creator'); ?></option>
						<option value="SA"><?php _e('Saudi Arabia', 'schema-creator'); ?></option>
						<option value="SN"><?php _e('Senegal', 'schema-creator'); ?></option>
						<option value="RS"><?php _e('Serbia', 'schema-creator'); ?></option>
						<option value="SC"><?php _e('Seychelles', 'schema-creator'); ?></option>
						<option value="SL"><?php _e('Sierra Leone', 'schema-creator'); ?></option>
						<option value="SG"><?php _e('Singapore', 'schema-creator'); ?></option>
						<option value="SX"><?php _e('Sint Maarten (Dutch Part)', 'schema-creator'); ?></option>
						<option value="SK"><?php _e('Slovakia', 'schema-creator'); ?></option>
						<option value="SI"><?php _e('Slovenia', 'schema-creator'); ?></option>
						<option value="SB"><?php _e('Solomon Islands', 'schema-creator'); ?></option>
						<option value="SO"><?php _e('Somalia', 'schema-creator'); ?></option>
						<option value="ZA"><?php _e('South Africa', 'schema-creator'); ?></option>
						<option value="GS"><?php _e('South Georgia', 'schema-creator'); ?></option>
						<option value="SS"><?php _e('South Sudan', 'schema-creator'); ?></option>
						<option value="ES"><?php _e('Spain', 'schema-creator'); ?></option>
						<option value="LK"><?php _e('Sri Lanka', 'schema-creator'); ?></option>
						<option value="SD"><?php _e('Sudan', 'schema-creator'); ?></option>
						<option value="SR"><?php _e('Suriname', 'schema-creator'); ?></option>
						<option value="SJ"><?php _e('Svalbard', 'schema-creator'); ?></option>
						<option value="SZ"><?php _e('Swaziland', 'schema-creator'); ?></option>
						<option value="SE"><?php _e('Sweden', 'schema-creator'); ?></option>
						<option value="CH"><?php _e('Switzerland', 'schema-creator'); ?></option>
						<option value="SY"><?php _e('Syria', 'schema-creator'); ?></option>
						<option value="TW"><?php _e('Taiwan', 'schema-creator'); ?></option>
						<option value="TJ"><?php _e('Tajikistan', 'schema-creator'); ?></option>
						<option value="TZ"><?php _e('Tanzania', 'schema-creator'); ?></option>
						<option value="TH"><?php _e('Thailand', 'schema-creator'); ?></option>
						<option value="TL"><?php _e('Timor-Leste', 'schema-creator'); ?></option>
						<option value="TG"><?php _e('Togo', 'schema-creator'); ?></option>
						<option value="TK"><?php _e('Tokelau', 'schema-creator'); ?></option>
						<option value="TO"><?php _e('Tonga', 'schema-creator'); ?></option>
						<option value="TT"><?php _e('Trinidad And Tobago', 'schema-creator'); ?></option>
						<option value="TN"><?php _e('Tunisia', 'schema-creator'); ?></option>
						<option value="TR"><?php _e('Turkey', 'schema-creator'); ?></option>
						<option value="TM"><?php _e('Turkmenistan', 'schema-creator'); ?></option>
						<option value="TC"><?php _e('Turks And Caicos Islands', 'schema-creator'); ?></option>
						<option value="TV"><?php _e('Tuvalu', 'schema-creator'); ?></option>
						<option value="UG"><?php _e('Uganda', 'schema-creator'); ?></option>
						<option value="UA"><?php _e('Ukraine', 'schema-creator'); ?></option>
						<option value="AE"><?php _e('United Arab Emirates', 'schema-creator'); ?></option>
						<option value="UM"><?php _e('United States Minor Outlying Islands', 'schema-creator'); ?></option>
						<option value="UY"><?php _e('Uruguay', 'schema-creator'); ?></option>
						<option value="UZ"><?php _e('Uzbekistan', 'schema-creator'); ?></option>
						<option value="VU"><?php _e('Vanuatu', 'schema-creator'); ?></option>
						<option value="VE"><?php _e('Venezuela', 'schema-creator'); ?></option>
						<option value="VN"><?php _e('Vietnam', 'schema-creator'); ?></option>
						<option value="VG"><?php _e('British Virgin Islands ', 'schema-creator'); ?></option>
						<option value="VI"><?php _e('U.S. Virgin Islands ', 'schema-creator'); ?></option>
						<option value="WF"><?php _e('Wallis And Futuna', 'schema-creator'); ?></option>
						<option value="EH"><?php _e('Western Sahara', 'schema-creator'); ?></option>
						<option value="YE"><?php _e('Yemen', 'schema-creator'); ?></option>
						<option value="ZM"><?php _e('Zambia', 'schema-creator'); ?></option>
						<option value="ZW"><?php _e('Zimbabwe', 'schema-creator'); ?></option>
					</select>
				</div>

				<div id="sc_email" class="sc_option" style="display:none">
					<label for="schema_email"><?php _e('Email Address', 'schema-creator'); ?></label>
					<input type="text" name="schema_email" class="form_full" value="" id="schema_email" />
				</div>
	
				<div id="sc_phone" class="sc_option" style="display:none">
					<label for="schema_phone"><?php _e('Telephone', 'schema-creator'); ?></label>
					<input type="text" name="schema_phone" class="form_half" value="" id="schema_phone" />
				</div>

				<div id="sc_fax" class="sc_option" style="display:none">
					<label for="schema_fax"><?php _e('Fax', 'schema-creator'); ?></label>
					<input type="text" name="schema_fax" class="form_half" value="" id="schema_fax" />
				</div>
	
   				<div id="sc_brand" class="sc_option" style="display:none">
					<label for="schema_brand"><?php _e('Brand', 'schema-creator'); ?></label>
					<input type="text" name="schema_brand" class="form_full" value="" id="schema_brand" />
				</div>

   				<div id="sc_manfu" class="sc_option" style="display:none">
					<label for="schema_manfu"><?php _e('Manufacturer', 'schema-creator'); ?></label>
					<input type="text" name="schema_manfu" class="form_full" value="" id="schema_manfu" />
				</div>

   				<div id="sc_model" class="sc_option" style="display:none">
					<label for="schema_model"><?php _e('Model', 'schema-creator'); ?></label>
					<input type="text" name="schema_model" class="form_full" value="" id="schema_model" />
				</div>

   				<div id="sc_prod_id" class="sc_option" style="display:none">
					<label for="schema_prod_id"><?php _e('Product ID', 'schema-creator'); ?></label>
					<input type="text" name="schema_prod_id" class="form_full" value="" id="schema_prod_id" />
				</div>

   				<div id="sc_ratings" class="sc_option" style="display:none">
					<label for="sc_ratings"><?php _e('Aggregate Rating', 'schema-creator'); ?></label>
                    <div class="labels_inline">
					<label for="schema_single_rating"><?php _e('Avg Rating', 'schema-creator'); ?></label>
                    <input type="text" name="schema_single_rating" class="form_eighth schema_numeric" value="" id="schema_single_rating" />
                    <label for="schema_agg_rating"><?php _e('based on', 'schema-creator'); ?> </label>
					<input type="text" name="schema_agg_rating" class="form_eighth schema_numeric" value="" id="schema_agg_rating" />
                    <label><?php _e('reviews', 'schema-creator'); ?></label>
                    </div>
				</div>

   				<div id="sc_reviews" class="sc_option" style="display:none">
					<label for="sc_reviews"><?php _e('Rating', 'schema-creator'); ?></label>
                    <div class="labels_inline">
					<label for="schema_user_review"><?php _e('Rating', 'schema-creator'); ?></label>
                    <input type="text" name="schema_user_review" class="form_eighth schema_numeric" value="" id="schema_user_review" />
                    <label for="schema_min_review"><?php _e('Minimum', 'schema-creator'); ?></label>
					<input type="text" name="schema_min_review" class="form_eighth schema_numeric" value="" id="schema_min_review" />
                    <label for="schema_max_review"><?php _e('Minimum', 'schema-creator'); ?></label>
					<input type="text" name="schema_max_review" class="form_eighth schema_numeric" value="" id="schema_max_review" />
                    </div>
				</div>


   				<div id="sc_price" class="sc_option" style="display:none">
					<label for="schema_price"><?php _e('Price', 'schema-creator'); ?></label>
					<input type="text" name="schema_price" class="form_third sc_currency" value="" id="schema_price" />
				</div>

				<div id="sc_condition" class="sc_option" style="display:none">
					<label for="schema_condition"><?php _e('Condition', 'schema-creator'); ?></label>
					<select name="schema_condition" id="schema_condition" class="schema_drop">
						<option class="holder" value="none">(<?php _e('Select', 'schema-creator'); ?>)</option>
						<option value="New"><?php _e('New', 'schema-creator'); ?></option>
						<option value="Used"><?php _e('Used', 'schema-creator'); ?></option>
						<option value="Refurbished"><?php _e('Refurbished', 'schema-creator'); ?></option>
						<option value="Damaged"><?php _e('Damaged', 'schema-creator'); ?></option>
					</select>
				</div>

   				<div id="sc_author" class="sc_option" style="display:none">
					<label for="schema_author"><?php _e('Author', 'schema-creator'); ?></label>
					<input type="text" name="schema_author" class="form_full" value="" id="schema_author" />
				</div>

   				<div id="sc_publisher" class="sc_option" style="display:none">
					<label for="schema_publisher"><?php _e('Publisher', 'schema-creator'); ?></label>
					<input type="text" name="schema_publisher" class="form_full" value="" id="schema_publisher" />
				</div>

				<div id="sc_pubdate" class="sc_option" style="display:none">
					<label for="schema_pubdate"><?php _e('Published Date', 'schema-creator'); ?></label>
					<input type="text" id="schema_pubdate" name="schema_pubdate" class="schema_datepicker form_third" value="" />
					<input type="hidden" id="schema_pubdate-format" class="schema_datepicker-format" value="" />
				</div>

   				<div id="sc_edition" class="sc_option" style="display:none">
					<label for="schema_edition"><?php _e('Edition', 'schema-creator'); ?></label>
					<input type="text" name="schema_edition" class="form_full" value="" id="schema_edition" />
				</div>

   				<div id="sc_isbn" class="sc_option" style="display:none">
					<label for="schema_isbn"><?php _e('ISBN', 'schema-creator'); ?></label>
					<input type="text" name="schema_isbn" class="form_full" value="" id="schema_isbn" />
				</div>

   				<div id="sc_formats" class="sc_option" style="display:none">
				<label class="list_label"><?php _e('Formats', 'schema-creator'); ?></label>
                	<div class="form_list">
                    <span>
											<input type="checkbox" class="schema_check" id="schema_ebook" name="schema_ebook" value="ebook" />
											<label for="schema_ebook" rel="checker">
												<?php _e('Ebook', 'schema-creator'); ?>
											</label>
										</span>
                    <span>
											<input type="checkbox" class="schema_check" id="schema_paperback" name="schema_paperback" value="paperback" />
											<label for="schema_paperback" rel="checker">
												<?php _e('Paperback', 'schema-creator'); ?>
											</label>
										</span>
                    <span>
											<input type="checkbox" class="schema_check" id="schema_hardcover" name="schema_hardcover" value="hardcover" />
											<label for="schema_hardcover" rel="checker">
												<?php _e('Hardcover', 'schema-creator'); ?>
											</label>
                   </span>
                </div>
				</div>

				<div id="sc_revdate" class="sc_option" style="display:none">
					<label for="schema_revdate"><?php _e('Review Date', 'schema-creator'); ?></label>
					<input type="text" id="schema_revdate" name="schema_revdate" class="schema_datepicker form_third" value="" />
					<input type="hidden" id="schema_revdate-format" class="schema_datepicker-format" value="" />
				</div>
                
			<!-- button for inserting -->	
				<div class="insert_button" style="display:none">
					<input class="schema_insert schema_button" type="button" value="<?php _e('Insert'); ?>" onclick="InsertSchema();"/>
					<input class="schema_cancel schema_clear schema_button" type="button" value="<?php _e('Cancel'); ?>" onclick="tb_remove(); return false;"/>                
				</div>

			<!-- various messages -->
				<div id="sc_messages">
                <p class="start"><?php _e('Select a schema type above to get started', 'schema-creator'); ?></p>
                <p class="pending" style="display:none;">
								<?php _e('This schema type is currently being constructed.', 'schema-creator'); ?>
                </p>
                </div>
	
			</div>
			</div>
	
	<?php }


/// end class
}


// Instantiate our class
$ravenSchema = new ravenSchema();
