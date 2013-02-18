<?php 

// uses: http://plugins.svn.wordpress.org/advanced-excerpt/tests/wp-test/lib/testcase.php
// [...] schema-creator/schema-creator.php
class BasicTest extends WP_UnitTestCase {
    public $plugin_slug = 'schema-creator';

    public function setUp() {
        parent::setUp();
        $this->my_plugin = new ravenSchema();
    }

    public function testAppendContent() {
        $this->assertEquals( true, true );
    }

    /**
     * A contrived example using some WordPress functionality
     */
    public function testPostTitle() {
        // This will simulate running WordPress' main query.
        // See wordpress-tests/lib/testcase.php
        $this->go_to('http://example.org/?p=1');

        // Now that the main query has run, we can do tests that are more functional in nature
        global $wp_query;
        $post = $wp_query->get_queried_object();
        $this->assertEquals('Hello world!', $post->post_title );
    }
}