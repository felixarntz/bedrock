<?php
/*
Plugin Name: WP PRoject SCaffolding
Plugin URI: https://github.com/felixarntz/bedrock/
Description: MU Plugin with useful standard functionality for WordPress projects.
Version: 1.0.0
Author: Felix Arntz
Author URI: http://leaves-and-love.net
License: GNU General Public License v2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

function wpprsc_init() {
	$wpprsc_base = array(
		'PluginAutoloader',
		'ThemeFallback',
	);
	foreach ( $wpprsc_base as $class ) {
		call_user_func( array( 'WPPRSC\\Base\\' . $class, 'instance' ) );
	}
}
wpprsc_init();

function wpprsc_modules_init() {
	$wpprsc_modules = array();
	foreach ( $wpprsc_modules as $constant => $class ) {
		if ( defined( $constant ) && constant( $constant ) ) {
			call_user_func( array( 'WPPRSC\\Modules\\' . $class, 'instance' ) );
		}
	}
}
add_action( 'muplugins_loaded', 'wpprsc_modules_init' );

function wpprsc_get_path( $relative_path = '' ) {
	return plugin_dir_path( __FILE__ ) . $relative_path;
}

function wpprsc_get_url( $relative_path = '' ) {
	return plugin_dir_url( __FILE__ ) . $relative_path;
}
