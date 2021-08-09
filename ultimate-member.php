<?php
/*
Plugin Name: Ultimate Member
Plugin URI: http://ultimatemember.com/
Description: The easiest way to create powerful online communities and beautiful user profiles with WordPress
Version: 3.0-alpha
Author: Ultimate Member
Author URI: http://ultimatemember.com/
Text Domain: ultimate-member
*/

defined( 'ABSPATH' ) || exit;


// development constant
define( 'um_v3', true );

require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
$plugin_data = get_plugin_data( __FILE__ );

define( 'um_url', plugin_dir_url( __FILE__ ) );
define( 'um_path', plugin_dir_path( __FILE__ ) );
define( 'um_plugin', plugin_basename( __FILE__ ) );
define( 'ultimatemember_version', $plugin_data['Version'] );
define( 'ultimatemember_plugin_name', $plugin_data['Name'] );

if ( defined( 'um_v3' ) && um_v3 ) {
	require_once 'includes/class-functions.php';
	require_once 'includes/class-init.php';
} else {
	require_once 'includes/class-functions.php';
	require_once 'includes/class-init.php';
}
