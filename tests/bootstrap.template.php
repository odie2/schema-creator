<?php
/**
 * Load WordPress test environment.
 *
 * @remarks Copy this file to bootstrap.php and replace the $path variable
 * @see https://github.com/nb/wordpress-tests
 * @version 1.0
 * @package WordPress\Plugins\SchemaCreator\Tests
 *
 * @global $path the path to bootstrap.php in the wordpress-tests folder
 */
 
$path = 'path/to/wordpress-tests/bootstrap.php';

if( !file_exists( $path ) )
	exit( "Couldn't find path to wordpress-tests/bootstrap.php\n" );
	
require_once $path;