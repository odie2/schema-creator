<?php
/*
Plugin Name: Schema Creator by Raven
Plugin URI: http://schema-creator.org/?utm_source=wp&utm_medium=plugin&utm_campaign=schema
Description: Insert schema.org microdata into posts and pages
Version: 1.031
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
		echo '<label for="schema_disable_body">Disable body itemscopes on this post.</label>';
		echo '</p>';

		echo '<p class="schema-post-option">';
		echo '<input type="checkbox" name="schema_disable_post" id="schema_disable_post" value="true" '.checked($disable_post, 'true', false).'>';
		echo '<label for="schema_disable_post">Disable content itemscopes on this post.</label>';
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
		<h2>Schema Creator Settings</h2>
        
	        <div class="schema_options">
            	<div class="schema_form_text">
            	<p>By default, the <a href="http://schema-creator.org/?utm_source=wp&utm_medium=plugin&utm_campaign=schema" target="_blank">Schema Creator</a> plugin by <a href="http://raventools.com/?utm_source=wp&utm_medium=plugin&utm_campaign=schema" target="_blank">Raven Internet Marketing Tools</a> includes unique CSS IDs and classes. You can reference the CSS to control the style of the HTML that the Schema Creator plugin outputs.</p>
            	<p>The plugin can also automatically include <a href="http://schema.org/Blog" target="_blank">http://schema.org/Blog</a> and <a href="http://schema.org/BlogPosting" target="_blank">http://schema.org/BlogPosting</a> schemas to your pages and posts.</p>
				<p>Google also offers a <a href="http://www.google.com/webmasters/tools/richsnippets/" target="_blank">Rich Snippet Testing tool</a> to review and test the schemas in your pages and posts.</p>
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
                <label for="schema_options[css]"><input type="checkbox" id="schema_css" name="schema_options[css]" class="schema_checkbox" value="true" <?php echo $css_hide; ?>/> Exclude default CSS for schema output</label>
                <span class="ap_tooltip" tooltip="<?php echo $this->tooltip['default_css']; ?>">(?)</span>
                </p>

				<p>
                <label for="schema_options[body]"><input type="checkbox" id="schema_body" name="schema_options[body]" class="schema_checkbox" value="true" <?php echo $body_tag; ?> /> Apply itemprop &amp; itemtype to main body tag</label>
                <span class="ap_tooltip" tooltip="<?php echo $this->tooltip['body_class']; ?>">(?)</span>
                </p>

				<p>
                <label for="schema_options[post]"><input type="checkbox" id="schema_post" name="schema_options[post]" class="schema_checkbox" value="true" <?php echo $post_tag; ?> /> Apply itemscope &amp; itemtype to content wrapper</label>
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
				$sc_build .= '<div class="email" itemprop="email">'.antispambot($email).'</div>';

			if(!empty($phone))
				$sc_build .= '<div class="phone" itemprop="telephone">'.$phone.'</div>';

			if(!empty($bday))
				$sc_build .= '<div class="bday"><meta itemprop="birthDate" content="'.$bday.'">DOB: '.date('m/d/Y', strtotime($bday)).'</div>';
	
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
				$sc_build .= '<div class="brand" itemprop="brand" itemscope itemtype="http://schema.org/Organization"><span class="desc_type">Brand:</span> <span itemprop="name">'.$brand.'</span></div>';

			if(!empty($manfu))
				$sc_build .= '<div class="manufacturer" itemprop="manufacturer" itemscope itemtype="http://schema.org/Organization"><span class="desc_type">Manufacturer:</span> <span itemprop="name">'.$manfu.'</span></div>';

			if(!empty($model))
				$sc_build .= '<div class="model"><span class="desc_type">Model:</span> <span itemprop="model">'.$model.'</span></div>';

			if(!empty($prod_id))
				$sc_build .= '<div class="prod_id"><span class="desc_type">Product ID:</span> <span itemprop="productID">'.$prod_id.'</span></div>';

			if(!empty($single_rating) && !empty($agg_rating)) {
				$sc_build .= '<div itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating">';
				$sc_build .= '<span itemprop="ratingValue">'.$single_rating.'</span> based on ';
				$sc_build .= '<span itemprop="reviewCount">'.$agg_rating.'</span> reviews';
				$sc_build .= '</div>';
			}

				// secondary check if one part of review is missing to keep markup consistent
				if(empty($agg_rating) && !empty($single_rating) )
					$sc_build .= '<div itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating"><span itemprop="ratingValue"><span class="desc_type">Review:</span> '.$single_rating.'</span></div>';
					
				if(empty($single_rating) && !empty($agg_rating) )
					$sc_build .= '<div itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating"><span itemprop="reviewCount">'.$agg_rating.'</span> total reviews</div>';

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
				$sc_build .= '<div>Edition: <span itemprop="bookEdition">'.$edition.'</span></div>';

			if(!empty($isbn)) 
				$sc_build .= '<div>ISBN: <span itemprop="isbn">'.$isbn.'</span></div>';

			if( !empty($ebook) || !empty($paperback) || !empty($hardcover) ) { 
				$sc_build .= '<div>Available in: ';

					if(!empty($ebook)) 
						$sc_build .= '<link itemprop="bookFormat" href="http://schema.org/Ebook">Ebook ';
	
					if(!empty($paperback)) 
						$sc_build .= '<link itemprop="bookFormat" href="http://schema.org/Paperback">Paperback ';
	
					if(!empty($hardcover)) 
						$sc_build .= '<link itemprop="bookFormat" href="http://schema.org/Hardcover">Hardcover ';

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
				$sc_build .= '<div itemprop="author" itemscope itemtype="http://schema.org/Person">Written by: <span itemprop="name">'.$author.'</span></div>';

			if(!empty($pubdate))
				$sc_build .= '<div class="pubdate"><meta itemprop="datePublished" content="'.$pubdate.'">Date Published: '.date('m/d/Y', strtotime($pubdate)).'</div>';

			if(!empty($rev_body))
				$sc_build .= '<div class="schema_review_body" itemprop="reviewBody">'.esc_textarea($rev_body).'</div>';

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
		
		$button = '<a href="#TB_inline?width=650&inlineId=schema_build_form" class="thickbox schema_clear" id="add_schema" title="' . __('Schema Creator Form') . '">' . __('Schema Creator Form') . '</a>';

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
					<label for="schema_type">Schema Type</label>
					<select name="schema_type" id="schema_type" class="schema_drop schema_thindrop">
						<option class="holder" value="none">(Select A Type)</option>
						<option value="person">Person</option>
						<option value="product">Product</option>
						<option value="event">Event</option>
						<option value="organization">Organization</option>
						<option value="movie">Movie</option>
						<option value="book">Book</option>
						<option value="review">Review</option>
					</select>
				</div>
			<!-- end schema type dropdown -->	

				<div id="sc_evtype" class="sc_option" style="display:none">
					<label for="schema_evtype">Event Type</label>
					<select name="schema_evtype" id="schema_evtype" class="schema_drop schema_thindrop">
						<option value="Event">General</option>
						<option value="BusinessEvent">Business</option>
						<option value="ChildrensEvent">Childrens</option>
						<option value="ComedyEvent">Comedy</option>
						<option value="DanceEvent">Dance</option>
						<option value="EducationEvent">Education</option>
						<option value="Festival">Festival</option>
						<option value="FoodEvent">Food</option>
						<option value="LiteraryEvent">Literary</option>
						<option value="MusicEvent">Music</option>
						<option value="SaleEvent">Sale</option>
						<option value="SocialEvent">Social</option>
						<option value="SportsEvent">Sports</option>
						<option value="TheaterEvent">Theater</option>
						<option value="UserInteraction">User Interaction</option>
						<option value="VisualArtsEvent">Visual Arts</option>
					</select>
				</div>

				<div id="sc_orgtype" class="sc_option" style="display:none">
					<label for="schema_orgtype">Organziation Type</label>
					<select name="schema_orgtype" id="schema_orgtype" class="schema_drop schema_thindrop">
						<option value="Organization">General</option>
						<option value="Corporation">Corporation</option>
						<option value="EducationalOrganization">School</option>
						<option value="GovernmentOrganization">Government</option>
						<option value="LocalBusiness">Local Business</option>
						<option value="NGO">NGO</option>
						<option value="PerformingGroup">Performing Group</option>
						<option value="SportsTeam">Sports Team</option>
					</select>
				</div>

				<div id="sc_name" class="sc_option" style="display:none">
					<label for="schema_name">Name</label>
					<input type="text" name="schema_name" class="form_full" value="" id="schema_name" />
				</div>

				<div id="sc_orgname" class="sc_option" style="display:none">
					<label for="schema_orgname">Organization</label>
					<input type="text" name="schema_orgname" class="form_full" value="" id="schema_orgname" />
				</div>
	
				<div id="sc_jobtitle" class="sc_option" style="display:none">
					<label for="schema_jobtitle">Job Title</label>
					<input type="text" name="schema_jobtitle" class="form_full" value="" id="schema_jobtitle" />
				</div>
	
				<div id="sc_url" class="sc_option" style="display:none">
					<label for="schema_url">Website</label>
					<input type="text" name="schema_url" class="form_full" value="" id="schema_url" />
				</div>
	
				<div id="sc_description" class="sc_option" style="display:none">
					<label for="schema_description">Description</label>
					<textarea name="schema_description" id="schema_description"></textarea>
				</div>

				<div id="sc_rev_name" class="sc_option" style="display:none">
					<label for="schema_rev_name">Item Name</label>
					<input type="text" name="schema_rev_name" class="form_full" value="" id="schema_rev_name" />
				</div>

				<div id="sc_rev_body" class="sc_option" style="display:none">
					<label for="schema_rev_body">Item Review</label>
					<textarea name="schema_rev_body" id="schema_rev_body"></textarea>
				</div>

				<div id="sc_director" class="sc_option" style="display:none">
					<label for="schema_director">Director</label>
					<input type="text" name="schema_director" class="form_full" value="" id="schema_director" />
				</div>

				<div id="sc_producer" class="sc_option" style="display:none">
					<label for="schema_producer">Producer</label>
					<input type="text" name="schema_producer" class="form_full" value="" id="schema_producer" />
				</div>

				<div id="sc_actor_1" class="sc_option sc_actor sc_repeater" style="display:none">
                        <label for="schema_actor_1">Actor</label>
                        <input type="text" name="schema_actor_1" class="form_full actor_input" value="" id="schema_actor_1" />
				</div>

				<input type="button" id="clone_actor" value="Add Another Actor" style="display:none;" />


				<div id="sc_sdate" class="sc_option" style="display:none">
					<label for="schema_sdate">Start Date</label>
					<input type="text" id="schema_sdate" name="schema_sdate" class="schema_datepicker timepicker form_third" value="" />
					<input type="hidden" id="schema_sdate-format" class="schema_datepicker-format" value="" />
				</div>

				<div id="sc_stime" class="sc_option" style="display:none">
					<label for="schema_stime">Start Time</label>
					<input type="text" id="schema_stime" name="schema_stime" class="schema_timepicker form_third" value="" />
				</div>

				<div id="sc_edate" class="sc_option" style="display:none">
					<label for="schema_edate">End Date</label>
					<input type="text" id="schema_edate" name="schema_edate" class="schema_datepicker form_third" value="" />
					<input type="hidden" id="schema_edate-format" class="schema_datepicker-format" value="" />
				</div>

				<div id="sc_duration" class="sc_option" style="display:none">
					<label for="schema_duration">Duration</label>
					<input type="text" id="schema_duration" name="schema_duration" class="schema_timepicker form_third" value="" />
				</div>
	
				<div id="sc_bday" class="sc_option" style="display:none">
					<label for="schema_bday">Birthday</label>
					<input type="text" id="schema_bday" name="schema_bday" class="schema_datepicker form_third" value="" />
					<input type="hidden" id="schema_bday-format" class="schema_datepicker-format" value="" />
				</div>
	
				<div id="sc_street" class="sc_option" style="display:none">
					<label for="schema_street">Address</label>
					<input type="text" name="schema_street" class="form_full" value="" id="schema_street" />
				</div>
	
				<div id="sc_pobox" class="sc_option" style="display:none">
					<label for="schema_pobox">PO Box</label>
					<input type="text" name="schema_pobox" class="form_third schema_numeric" value="" id="schema_pobox" />
				</div>
	
				<div id="sc_city" class="sc_option" style="display:none">
					<label for="schema_city">City</label>
					<input type="text" name="schema_city" class="form_full" value="" id="schema_city" />
				</div>
	
				<div id="sc_state" class="sc_option" style="display:none">
					<label for="schema_state">State / Region</label>
					<input type="text" name="schema_state" class="form_third" value="" id="schema_state" />
				</div>
	
				<div id="sc_postalcode" class="sc_option" style="display:none">
					<label for="schema_postalcode">Postal Code</label>
					<input type="text" name="schema_postalcode" class="form_third" value="" id="schema_postalcode" />
				</div>

				<div id="sc_country" class="sc_option" style="display:none">
					<label for="schema_country">Country</label>
					<select name="schema_country" id="schema_country" class="schema_drop schema_thindrop">
						<option class="holder" value="none">(Select A Country)</option>
						<option value="US">United States</option>
						<option value="CA">Canada</option>
						<option value="MX">Mexico</option>
						<option value="GB">United Kingdom</option>
						<option value="AF">Afghanistan</option>
						<option value="AX">Åland Islands</option>
						<option value="AL">Albania</option>
						<option value="DZ">Algeria</option>
						<option value="AS">American Samoa</option>
						<option value="AD">Andorra</option>
						<option value="AO">Angola</option>
						<option value="AI">Anguilla</option>
						<option value="AQ">Antarctica</option>
						<option value="AG">Antigua And Barbuda</option>
						<option value="AR">Argentina</option>
						<option value="AM">Armenia</option>
						<option value="AW">Aruba</option>
						<option value="AU">Australia</option>
						<option value="AT">Austria</option>
						<option value="AZ">Azerbaijan</option>
						<option value="BS">Bahamas</option>
						<option value="BH">Bahrain</option>
						<option value="BD">Bangladesh</option>
						<option value="BB">Barbados</option>
						<option value="BY">Belarus</option>
						<option value="BE">Belgium</option>
						<option value="BZ">Belize</option>
						<option value="BJ">Benin</option>
						<option value="BM">Bermuda</option>
						<option value="BT">Bhutan</option>
						<option value="BO">Bolivia, Plurinational State Of</option>
						<option value="BQ">Bonaire, Sint Eustatius And Saba</option>
						<option value="BA">Bosnia And Herzegovina</option>
						<option value="BW">Botswana</option>
						<option value="BV">Bouvet Island</option>
						<option value="BR">Brazil</option>
						<option value="IO">British Indian Ocean Territory</option>
						<option value="BN">Brunei Darussalam</option>
						<option value="BG">Bulgaria</option>
						<option value="BF">Burkina Faso</option>
						<option value="BI">Burundi</option>
						<option value="KH">Cambodia</option>
						<option value="CM">Cameroon</option>
						<option value="CV">Cape Verde</option>
						<option value="KY">Cayman Islands</option>
						<option value="CF">Central African Republic</option>
						<option value="TD">Chad</option>
						<option value="CL">Chile</option>
						<option value="CN">China</option>
						<option value="CX">Christmas Island</option>
						<option value="CC">Cocos (Keeling) Islands</option>
						<option value="CO">Colombia</option>
						<option value="KM">Comoros</option>
						<option value="CG">Congo</option>
						<option value="CD">Congo, The Democratic Republic Of The</option>
						<option value="CK">Cook Islands</option>
						<option value="CR">Costa Rica</option>
						<option value="CI">Côte D'Ivoire</option>
						<option value="HR">Croatia</option>
						<option value="CU">Cuba</option>
						<option value="CW">Curaçao</option>
						<option value="CY">Cyprus</option>
						<option value="CZ">Czech Republic</option>
						<option value="DK">Denmark</option>
						<option value="DJ">Djibouti</option>
						<option value="DM">Dominica</option>
						<option value="DO">Dominican Republic</option>
						<option value="EC">Ecuador</option>
						<option value="EG">Egypt</option>
						<option value="SV">El Salvador</option>
						<option value="GQ">Equatorial Guinea</option>
						<option value="ER">Eritrea</option>
						<option value="EE">Estonia</option>
						<option value="ET">Ethiopia</option>
						<option value="FK">Falkland Islands (Malvinas)</option>
						<option value="FO">Faroe Islands</option>
						<option value="FJ">Fiji</option>
						<option value="FI">Finland</option>
						<option value="FR">France</option>
						<option value="GF">French Guiana</option>
						<option value="PF">French Polynesia</option>
						<option value="TF">French Southern Territories</option>
						<option value="GA">Gabon</option>
						<option value="GM">Gambia</option>
						<option value="GE">Georgia</option>
						<option value="DE">Germany</option>
						<option value="GH">Ghana</option>
						<option value="GI">Gibraltar</option>
						<option value="GR">Greece</option>
						<option value="GL">Greenland</option>
						<option value="GD">Grenada</option>
						<option value="GP">Guadeloupe</option>
						<option value="GU">Guam</option>
						<option value="GT">Guatemala</option>
						<option value="GG">Guernsey</option>
						<option value="GN">Guinea</option>
						<option value="GW">Guinea-Bissau</option>
						<option value="GY">Guyana</option>
						<option value="HT">Haiti</option>
						<option value="HM">Heard Island And Mcdonald Islands</option>
						<option value="VA">Vatican City</option>
						<option value="HN">Honduras</option>
						<option value="HK">Hong Kong</option>
						<option value="HU">Hungary</option>
						<option value="IS">Iceland</option>
						<option value="IN">India</option>
						<option value="ID">Indonesia</option>
						<option value="IR">Iran</option>
						<option value="IQ">Iraq</option>
						<option value="IE">Ireland</option>
						<option value="IM">Isle Of Man</option>
						<option value="IL">Israel</option>
						<option value="IT">Italy</option>
						<option value="JM">Jamaica</option>
						<option value="JP">Japan</option>
						<option value="JE">Jersey</option>
						<option value="JO">Jordan</option>
						<option value="KZ">Kazakhstan</option>
						<option value="KE">Kenya</option>
						<option value="KI">Kiribati</option>
						<option value="KP">North Korea</option>
						<option value="KR">South Korea</option>
						<option value="KW">Kuwait</option>
						<option value="KG">Kyrgyzstan</option>
						<option value="LA">Laos</option>
						<option value="LV">Latvia</option>
						<option value="LB">Lebanon</option>
						<option value="LS">Lesotho</option>
						<option value="LR">Liberia</option>
						<option value="LY">Libya</option>
						<option value="LI">Liechtenstein</option>
						<option value="LT">Lithuania</option>
						<option value="LU">Luxembourg</option>
						<option value="MO">Macao</option>
						<option value="MK">Macedonia</option>
						<option value="MG">Madagascar</option>
						<option value="MW">Malawi</option>
						<option value="MY">Malaysia</option>
						<option value="MV">Maldives</option>
						<option value="ML">Mali</option>
						<option value="MT">Malta</option>
						<option value="MH">Marshall Islands</option>
						<option value="MQ">Martinique</option>
						<option value="MR">Mauritania</option>
						<option value="MU">Mauritius</option>
						<option value="YT">Mayotte</option>
						<option value="FM">Micronesia</option>
						<option value="MD">Moldova</option>
						<option value="MC">Monaco</option>
						<option value="MN">Mongolia</option>
						<option value="ME">Montenegro</option>
						<option value="MS">Montserrat</option>
						<option value="MA">Morocco</option>
						<option value="MZ">Mozambique</option>
						<option value="MM">Myanmar</option>
						<option value="NA">Namibia</option>
						<option value="NR">Nauru</option>
						<option value="NP">Nepal</option>
						<option value="NL">Netherlands</option>
						<option value="NC">New Caledonia</option>
						<option value="NZ">New Zealand</option>
						<option value="NI">Nicaragua</option>
						<option value="NE">Niger</option>
						<option value="NG">Nigeria</option>
						<option value="NU">Niue</option>
						<option value="NF">Norfolk Island</option>
						<option value="MP">Northern Mariana Islands</option>
						<option value="NO">Norway</option>
						<option value="OM">Oman</option>
						<option value="PK">Pakistan</option>
						<option value="PW">Palau</option>
						<option value="PS">Palestine</option>
						<option value="PA">Panama</option>
						<option value="PG">Papua New Guinea</option>
						<option value="PY">Paraguay</option>
						<option value="PE">Peru</option>
						<option value="PH">Philippines</option>
						<option value="PN">Pitcairn</option>
						<option value="PL">Poland</option>
						<option value="PT">Portugal</option>
						<option value="PR">Puerto Rico</option>
						<option value="QA">Qatar</option>
						<option value="RE">Réunion</option>
						<option value="RO">Romania</option>
						<option value="RU">Russian Federation</option>
						<option value="RW">Rwanda</option>
						<option value="BL">St. Barthélemy</option>
						<option value="SH">St. Helena</option>
						<option value="KN">St. Kitts And Nevis</option>
						<option value="LC">St. Lucia</option>
						<option value="MF">St. Martin (French Part)</option>
						<option value="PM">St. Pierre And Miquelon</option>
						<option value="VC">St. Vincent And The Grenadines</option>
						<option value="WS">Samoa</option>
						<option value="SM">San Marino</option>
						<option value="ST">Sao Tome And Principe</option>
						<option value="SA">Saudi Arabia</option>
						<option value="SN">Senegal</option>
						<option value="RS">Serbia</option>
						<option value="SC">Seychelles</option>
						<option value="SL">Sierra Leone</option>
						<option value="SG">Singapore</option>
						<option value="SX">Sint Maarten (Dutch Part)</option>
						<option value="SK">Slovakia</option>
						<option value="SI">Slovenia</option>
						<option value="SB">Solomon Islands</option>
						<option value="SO">Somalia</option>
						<option value="ZA">South Africa</option>
						<option value="GS">South Georgia</option>
						<option value="SS">South Sudan</option>
						<option value="ES">Spain</option>
						<option value="LK">Sri Lanka</option>
						<option value="SD">Sudan</option>
						<option value="SR">Suriname</option>
						<option value="SJ">Svalbard</option>
						<option value="SZ">Swaziland</option>
						<option value="SE">Sweden</option>
						<option value="CH">Switzerland</option>
						<option value="SY">Syria</option>
						<option value="TW">Taiwan</option>
						<option value="TJ">Tajikistan</option>
						<option value="TZ">Tanzania</option>
						<option value="TH">Thailand</option>
						<option value="TL">Timor-Leste</option>
						<option value="TG">Togo</option>
						<option value="TK">Tokelau</option>
						<option value="TO">Tonga</option>
						<option value="TT">Trinidad And Tobago</option>
						<option value="TN">Tunisia</option>
						<option value="TR">Turkey</option>
						<option value="TM">Turkmenistan</option>
						<option value="TC">Turks And Caicos Islands</option>
						<option value="TV">Tuvalu</option>
						<option value="UG">Uganda</option>
						<option value="UA">Ukraine</option>
						<option value="AE">United Arab Emirates</option>
						<option value="UM">United States Minor Outlying Islands</option>
						<option value="UY">Uruguay</option>
						<option value="UZ">Uzbekistan</option>
						<option value="VU">Vanuatu</option>
						<option value="VE">Venezuela</option>
						<option value="VN">Vietnam</option>
						<option value="VG">British Virgin Islands </option>
						<option value="VI">U.S. Virgin Islands </option>
						<option value="WF">Wallis And Futuna</option>
						<option value="EH">Western Sahara</option>
						<option value="YE">Yemen</option>
						<option value="ZM">Zambia</option>
						<option value="ZW">Zimbabwe</option>
					</select>
				</div>

				<div id="sc_email" class="sc_option" style="display:none">
					<label for="schema_email">Email Address</label>
					<input type="text" name="schema_email" class="form_full" value="" id="schema_email" />
				</div>
	
				<div id="sc_phone" class="sc_option" style="display:none">
					<label for="schema_phone">Telephone</label>
					<input type="text" name="schema_phone" class="form_half" value="" id="schema_phone" />
				</div>

				<div id="sc_fax" class="sc_option" style="display:none">
					<label for="schema_fax">Fax</label>
					<input type="text" name="schema_fax" class="form_half" value="" id="schema_fax" />
				</div>
	
   				<div id="sc_brand" class="sc_option" style="display:none">
					<label for="schema_brand">Brand</label>
					<input type="text" name="schema_brand" class="form_full" value="" id="schema_brand" />
				</div>

   				<div id="sc_manfu" class="sc_option" style="display:none">
					<label for="schema_manfu">Manufacturer</label>
					<input type="text" name="schema_manfu" class="form_full" value="" id="schema_manfu" />
				</div>

   				<div id="sc_model" class="sc_option" style="display:none">
					<label for="schema_model">Model</label>
					<input type="text" name="schema_model" class="form_full" value="" id="schema_model" />
				</div>

   				<div id="sc_prod_id" class="sc_option" style="display:none">
					<label for="schema_prod_id">Product ID</label>
					<input type="text" name="schema_prod_id" class="form_full" value="" id="schema_prod_id" />
				</div>

   				<div id="sc_ratings" class="sc_option" style="display:none">
					<label for="sc_ratings">Aggregate Rating</label>
                    <div class="labels_inline">
					<label for="schema_single_rating">Avg Rating</label>
                    <input type="text" name="schema_single_rating" class="form_eighth schema_numeric" value="" id="schema_single_rating" />
                    <label for="schema_agg_rating">based on </label>
					<input type="text" name="schema_agg_rating" class="form_eighth schema_numeric" value="" id="schema_agg_rating" />
                    <label>reviews</label>
                    </div>
				</div>

   				<div id="sc_reviews" class="sc_option" style="display:none">
					<label for="sc_reviews">Rating</label>
                    <div class="labels_inline">
					<label for="schema_user_review">Rating</label>
                    <input type="text" name="schema_user_review" class="form_eighth schema_numeric" value="" id="schema_user_review" />
                    <label for="schema_min_review">Minimum</label>
					<input type="text" name="schema_min_review" class="form_eighth schema_numeric" value="" id="schema_min_review" />
                    <label for="schema_max_review">Maximum</label>
					<input type="text" name="schema_max_review" class="form_eighth schema_numeric" value="" id="schema_max_review" />
                    </div>
				</div>


   				<div id="sc_price" class="sc_option" style="display:none">
					<label for="schema_price">Price</label>
					<input type="text" name="schema_price" class="form_third sc_currency" value="" id="schema_price" />
				</div>

				<div id="sc_condition" class="sc_option" style="display:none">
					<label for="schema_condition">Condition</label>
					<select name="schema_condition" id="schema_condition" class="schema_drop">
						<option class="holder" value="none">(Select)</option>
						<option value="New">New</option>
						<option value="Used">Used</option>
						<option value="Refurbished">Refurbished</option>
						<option value="Damaged">Damaged</option>
					</select>
				</div>

   				<div id="sc_author" class="sc_option" style="display:none">
					<label for="schema_author">Author</label>
					<input type="text" name="schema_author" class="form_full" value="" id="schema_author" />
				</div>

   				<div id="sc_publisher" class="sc_option" style="display:none">
					<label for="schema_publisher">Publisher</label>
					<input type="text" name="schema_publisher" class="form_full" value="" id="schema_publisher" />
				</div>

				<div id="sc_pubdate" class="sc_option" style="display:none">
					<label for="schema_pubdate">Published Date</label>
					<input type="text" id="schema_pubdate" name="schema_pubdate" class="schema_datepicker form_third" value="" />
					<input type="hidden" id="schema_pubdate-format" class="schema_datepicker-format" value="" />
				</div>

   				<div id="sc_edition" class="sc_option" style="display:none">
					<label for="schema_edition">Edition</label>
					<input type="text" name="schema_edition" class="form_full" value="" id="schema_edition" />
				</div>

   				<div id="sc_isbn" class="sc_option" style="display:none">
					<label for="schema_isbn">ISBN</label>
					<input type="text" name="schema_isbn" class="form_full" value="" id="schema_isbn" />
				</div>

   				<div id="sc_formats" class="sc_option" style="display:none">
				<label class="list_label">Formats</label>
                	<div class="form_list">
                    <span><input type="checkbox" class="schema_check" id="schema_ebook" name="schema_ebook" value="ebook" /><label for="schema_ebook" rel="checker">Ebook</label></span>
                    <span><input type="checkbox" class="schema_check" id="schema_paperback" name="schema_paperback" value="paperback" /><label for="schema_paperback" rel="checker">Paperback</label></span>
                    <span><input type="checkbox" class="schema_check" id="schema_hardcover" name="schema_hardcover" value="hardcover" /><label for="schema_hardcover" rel="checker">Hardcover</label></span>
                    </div>
				</div>

				<div id="sc_revdate" class="sc_option" style="display:none">
					<label for="schema_revdate">Review Date</label>
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
                <p class="start">Select a schema type above to get started</p>
                <p class="pending" style="display:none;">This schema type is currently being constructed.</p>
                </div>
	
			</div>
			</div>
	
	<?php }


/// end class
}


// Instantiate our class
$ravenSchema = new ravenSchema();
