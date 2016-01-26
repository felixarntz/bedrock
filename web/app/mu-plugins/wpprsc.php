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
		call_user_func( array( 'WPPRSC\\Base\\' . $class, 'instance' ) )->run();
	}
}
wpprsc_init();

function wpprsc_modules_init() {
	$wpprsc_modules = array(
		'api_manager'		=> 'APIManager',
		'auto_updater'		=> 'AutoUpdater',
		'client_role'		=> 'ClientRole',
		'content_fixes'		=> 'ContentFixes',
		'frontend_cleanup'	=> 'FrontendCleanup',
		'github_updater'	=> 'GithubUpdater',
		'git_manager'		=> 'GitManager',
		'legal_council'		=> 'LegalCouncil',
		'nice_emails'		=> 'NiceEmails',
		'nice_login'		=> 'NiceLogin',
		'security'			=> 'Security',
	);
	foreach ( $wpprsc_modules as $setting => $class ) {
		$args = wpprsc_get_setting( 'module_' . $setting );
		if ( $args ) {
			if ( ! is_array( $args ) ) {
				$args = array();
			}
			call_user_func( array( 'WPPRSC\\Modules\\' . $class, 'instance' ), $args )->run();
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

function wpprsc_get_info( $field = null ) {
	return WPPRSC\Base\Config::instance()->get_info( $field );
}

function wpprsc_get_setting( $setting, $default = false ) {
	return WPPRSC\Base\Config::instance()->get_setting( $setting, $default );
}
