<?php

/**
 * Basic Tests for Schema Creator Plugin.
 *
 * @author  Derk-Jan Karrenbeld <derk-jan+github@karrenbeld.info>
 * @version 1.0
 * @package WordPress\Plugins\SchemaCreator\Tests
 */
class BasicTest extends WP_UnitTestCase
{
    /**
     * This is the name of the folder and file.
     */
    public $plugin_slug = 'schema-creator';

    /**
     * Setups this test
     */
    public function setUp() 
    {
        parent::setUp();
        $this->object = new \RavenSchema();
        $this->addTestPost();
    }

    /**
     * Tests if a quick link is added to the links array.
     */
    public function testQuickLink() 
    {
        $links = $this->object->quick_link(array(), SC_BASE);
        $this->assertCount(1, $links, 'Expected 1 link and got ' . count($links));
        $this->assertStringStartsWith('<a ', $links[0], "Expected link but didn't find it.");
    }

    /**
     * Tests if a schema test link is added to the admin bar
     */
    public function testSchemaTest() 
    {
        // This will simulate running WordPress' main query.
        // We want to be on a singular, non-admin page!
        $this->go_to('http://example.org/?name=example-post');

        // Since the global will be loaded, mock it.
        global $wp_admin_bar;
        $wp_admin_bar = $this->getMock('WP_Admin_Bar', array('add_node'));

        // We would like to know if the test fails because of a faulty go_to or
        // a faulty schema_creator implementation. Just a pre-caution. This is
        // not a Unit Test standard, since that should be tested in a WP_UnitTestCase
        // Unit Test. Since there is none...
        $this->assertFalse(is_admin(), 'Go-to needs to simulate a non-admin page');
        $this->assertTrue(is_singular(), 'Go-to needs to simulate a singular page');

        // We expect the add_node to be called once( to add the add_node )
        $wp_admin_bar->expects($this->once())->method('add_node');
        $this->object->schema_test($wp_admin_bar);
    }

    /**
     * Tests if the metabox is conditionally hidden.
     *
     * Tests if the metabox is only hidden when both post and body
     * are empty values in the option variable.
     */
    public function testMetaBox() 
    {
        $current = get_option('schema_options');

        $current['body'] = true;
        $current['post'] = true;
        update_option('schema_options', $current);
        $this->object->metabox_schema('post', 'side');
        $this->assertFalse(in_array('schema-post-box', $this->getMetaBoxes()), 'metabox is hidden when it should not be');
		$this->clearMetaBoxes();

        $current['body'] = true;
        $current['post'] = null;
        update_option('schema_options', $current);
        $this->object->metabox_schema('post', 'side');
        $this->assertFalse(in_array('schema-post-box', $this->getMetaBoxes()), 'metabox is hidden when it should not be');
		$this->clearMetaBoxes();

        $current['body'] = null;
        $current['post'] = true;
        update_option('schema_options', $current);
        $this->object->metabox_schema('post', 'side');
        $this->assertFalse(in_array('schema-post-box', $this->getMetaBoxes()), 'metabox is hidden when it should not be');
		$this->clearMetaBoxes();

        $current['body'] = null;
        $current['post'] = null;
        update_option('schema_options', $current);
        $this->object->metabox_schema('post', 'side');
        $this->assertFalse(in_array('schema-post-box', $this->getMetaBoxes()), 'metabox is visible when it should not be');
    }

    /**
     * Tests if the metabox is conditionally hidden.
     *
     * Tests if the metabox is only shown when context is side
     * are empty values in the option variable.
     *
     */
    public function testMetaBoxContext() 
    {
        $current = get_option('schema_options');
        $current['body'] = true;
        $current['post'] = true;
        update_option('schema_options', $current);

        $this->object->metabox_schema('post', 'advanced');
        $this->assertEmpty($this->getMetaBoxes(), 'meta box was added to wrong context');
        $this->clearMetaBoxes();

        $this->object->metabox_schema('post', 'high');
        $this->assertEmpty($this->getMetaBoxes(), 'meta box was added to wrong context');
        $this->clearMetaBoxes();
    }

    /**
     * Tests the default options
     */
    public function testStoreSettings() 
    {
        $this->markTestSkipped(
            'Not able to test this correctly.'
        );
        $this->object->default_settings();
        $schema_options = get_option('schema_options');
        $this->assertEquals(false, $schema_options['css'], 'default css option is not false but ' . var_export($schema_options['css'], true));
        $this->assertEquals(true, $schema_options['body'], 'default body option is not true but ' . var_export($schema_options['body'], true));
        $this->assertEquals(true, $schema_options['post'], 'default post option is not true but ' . var_export($schema_options['post'], true));
    }

    /**
     * Tests if style and script are loaded when editing a post.
     */
    public function testAdminScriptsPost() 
    {
        @set_current_screen();
        $this->object->admin_scripts('post.php');

        $this->assertTrue(wp_style_is('schema-admin'), 'Admin schema style is not enqueued.');
        $this->assertTrue(wp_script_is('schema-form'), 'Schema script is not enqueued.');

        wp_dequeue_style('schema-admin');
        wp_dequeue_script('schema-form');
    }

    /**
     * Tests if style and script are loaded when creating a post.
     */
    public function testAdminScriptsPostNew() 
    {
        @set_current_screen();
        $this->object->admin_scripts('post-new.php');

        $this->assertTrue(wp_style_is('schema-admin'), 'Admin schema style is not enqueued.');
        $this->assertTrue(wp_script_is('schema-form'), 'Schema script is not enqueued.');

        wp_dequeue_style('schema-admin');
        wp_dequeue_script('schema-form');
    }

    /**
     * Tests if style and script are loaded when on the settings page.
     */
    public function testAdminScriptsSettingsPage() 
    {
        set_current_screen('settings_page_schema-creator');

        $this->object->admin_scripts('settings.php');

        $this->assertTrue(wp_style_is('schema-admin'), 'Admin schema style is not enqueued.');
        $this->assertTrue(wp_script_is('schema-admin'), 'Admin Schema script is not enqueued.');

        wp_dequeue_style('schema-admin');
        wp_dequeue_script('schema-admin');
    }

    /**
     * Tests the attribution link
     */
    public function testSchemaFooter() 
    {
        set_current_screen('settings_page_schema-creator');

        $this->assertNotEmpty($this->object->schema_footer(''), 'footer is not altered when on settings page');

        @set_current_screen();
        $this->assertEmpty($this->object->schema_footer(''), 'footer is altered when not on settings page');
    }

    /**
     * Tests if the body class is added if set to true
     */
    public function testBodyClass() 
    {
        $current = get_option('schema_options');
        $current['body'] = true;
        update_option('schema_options', $current);

        // Got to a post
        $this->go_to('http://example.org/?name=example-post');

        $this->object->body_class('');
        $this->expectOutputRegex('/(?!typeof)/', 'Itemscope not inserted by default.');
    }

    /**
     * Tests if the body class is not added if set to false
     */
    public function testBodyClassNot() 
    {
        $current = get_option('schema_options');
        $current['body'] = 'false';
        update_option('schema_options', $current);

        // Got to a post
        $this->go_to('http://example.org/?name=example-post');

        // ob_start();
        $this->object->body_class('');
        $this->expectOutputString('', 'Itemscope was inserted when it should not.');
    }

    /**
     * Tests if the schema css is loaded for schema posts
     */
    public function testSchemaLoader() 
    {
        $current = get_option('schema_options');
        $current['css'] = false;
        update_option('schema_options', $current);

        $this->go_to('http://example.org/?name=example-post');

        $post = get_queried_object();
        $post->post_content = '[schema ]';
        $posts = array( $post );

        $this->object->schema_loader($posts);
        $this->assertTrue(wp_style_is('schema-style'));
        $this->assertNotEmpty(get_post_meta($post->ID, '_raven_schema_load', true), 'Raven Schema should be loaded for this post');

        wp_dequeue_style('schema-style');
    }

    /**
     * Tests if the schema css is not loaded for non schema posts
     */
    public function testSchemaLoaderNot() 
    {
        $current = get_option('schema_options');
        $current['css'] = false;
        update_option('schema_options', $current);

        $this->go_to('http://example.org/?name=example-post');

        // First load that schema valid post
        global $wp_query;
        $post = $wp_query->get_queried_object();
        $post->post_content = '';
        $posts = array( $post );

        $this->object->schema_loader($posts);
        $this->assertFalse(wp_style_is('schema-style'));
        $this->assertEmpty(get_post_meta($post->ID, '_raven_schema_load', true), 'Raven Schema should not be loaded for this post');

        wp_dequeue_style('schema-style');
    }

    /**
     * Tests if the schema css is not loaded for schema posts but option set
     * @depends testSchemaLoaderNot
     * @depends testSchemaLoader
     */
    public function testSchemaLoaderNotOptions() 
    {
        $current = get_option('schema_options');
        $current['css'] = 'true';
        update_option('schema_options', $current);

        $this->go_to('http://example.org/?name=example-post');

        global $wp_query;
        $post = $wp_query->get_queried_object();
        $post->post_content = '[schema ]';
        $posts = array( $post );

        $this->object->schema_loader($posts);
        $this->assertFalse(wp_style_is('schema-style'));
        $this->assertEmpty(get_post_meta($post->ID, '_raven_schema_load', true), 'Raven Schema should not be loaded for this post');

        wp_dequeue_style('schema-style');
    }

    /**
     * Tests if the post is wrapped
     * @depends testSchemaLoader
     */
    public function testSchemaWrapper() 
    {
        $current = get_option('schema_options');
        $current['css'] = false;
        $current['post'] = 'true';
        update_option('schema_options', $current);

        $this->go_to('http://example.org/?name=example-post');

        // First load that schema valid post
        global $wp_query;
        $post = $wp_query->get_queried_object();
        $post->post_content = '[schema ]';
        $posts = array( $post );
        $this->object->schema_loader($posts);

        // Now lets see it its wrapped
        $this->assertContains('typeof', $this->object->schema_wrapper(''), 'post should be wrapped');
    }

    /**
     * Tests if the post is not wrapped when option indicates that
     * @depends testSchemaLoader
     */
    public function testSchemaWrapperNot() 
    {
        $current = get_option('schema_options');
        $current['css'] = false;
        $current['post'] = 'false';
        update_option('schema_options', $current);

        $this->go_to('http://example.org/?name=example-post');

        // First load that schema valid post
        global $wp_query;
        $post = $wp_query->get_queried_object();
        $post->post_content = '[schema ]';
        $posts = array( $post );
        $this->object->schema_loader($posts);

        // Now lets see it its wrapped
        $this->assertEmpty($this->object->schema_wrapper(''), 'post should not be wrapped');
    }

    /**
     * Clear Meta Boxes
     */
    private function clearMetaBoxes() 
    {
        global $wp_meta_boxes;
        return $wp_meta_boxes = array();
    }

    /**
     * Get Meta Boxes
     */
    private function getMetaBoxes() 
    {
        global $wp_meta_boxes;
        return $wp_meta_boxes;
    }

    /**
     * Add Test Post
     */
    private function addTestPost() 
    {
        $post_id = -1;
        $author_id = 1;
        $slug = 'example-post';
        $title = 'My Example Post';

        if (get_page_by_title($title) == null) {
            $post_id = wp_insert_post(
                array(
                'comment_status' => 'closed',
                'ping_status' => 'closed',
                'post_author' => $author_id,
                'post_name' => $slug,
                'post_title' => $title,
                'post_status' => 'publish',
                'post_type' => 'post'
                )
            );
            return $post_id;
        }
    }
}